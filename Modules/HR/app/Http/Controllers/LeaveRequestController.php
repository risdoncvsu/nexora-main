<?php

namespace Modules\HR\Http\Controllers;

use App\Models\EmployeeAccessProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    private const DURATION_RULES = [
        'vacation' => [5, 15],
        'sick' => [5, 15],
        'maternity' => [1, 105],
        'paternity' => [1, 7],
        'bereavement' => [1, 5],
    ];

    public function employeeLeave(Request $request)
    {
        $employee = $this->currentEmployee();

        if (! $employee || session('employee_role') !== 'employee') {
            return redirect()->route('hr.dashboard')
                ->with('error', 'Only employee accounts can view leave requests.');
        }

        $leaveRequests = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('reports-analytics.employee-leave', compact('employee', 'leaveRequests'));
    }

    public function store(Request $request)
    {
        $employee = $this->currentEmployee();

        if (! $employee || session('employee_role') !== 'employee') {
            return redirect()->route('hr.dashboard')
                ->with('error', 'Only employee accounts can submit leave requests.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_merge(array_keys(self::DURATION_RULES), ['others']))],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
        $toDate = Carbon::parse($validated['to_date'])->startOfDay();
        $totalDays = $fromDate->diffInDays($toDate) + 1;

        if (isset(self::DURATION_RULES[$validated['type']])) {
            [$minimumDays, $maximumDays] = self::DURATION_RULES[$validated['type']];

            if ($totalDays < $minimumDays || $totalDays > $maximumDays) {
                $label = ucfirst($validated['type']);

                return back()->withInput()->withErrors([
                    'to_date' => "{$label} leave must be between {$minimumDays} and {$maximumDays} days long.",
                ]);
            }
        }

        $attachmentPaths = [];
        foreach ($request->file('attachments', []) as $attachment) {
            $attachmentPaths[] = $attachment->store(
                'hr/leave-attachments/'.((int) $employee->client_id),
                'public'
            );
        }

        $leaveRequest = LeaveRequest::create([
            'client_id' => $employee->client_id,
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'total_days' => $totalDays,
            'reason' => $validated['reason'] ?? null,
            'attachments' => $attachmentPaths ?: null,
            'status' => 'pending',
        ]);

        $leaveRequest->update([
            'reference_id' => sprintf('LR-%s-%04d', now()->format('Y'), $leaveRequest->id),
        ]);

        return redirect()->route('hr.employee.leave')
            ->with('success', 'Your leave request has been submitted for HR review.');
    }

    public function index(Request $request)
    {
        $this->ensureHrPermission('hr.manage_leave_requests');

        $leaveRequests = LeaveRequest::query()
            ->with('employee')
            ->where('status', 'pending')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalSubmitted = LeaveRequest::count();
        $pendingCount = LeaveRequest::where('status', 'pending')->count();
        $submittedToday = LeaveRequest::whereDate('created_at', today())->count();

        return view('employee-management.leave-management', compact(
            'leaveRequests',
            'totalSubmitted',
            'pendingCount',
            'submittedToday'
        ));
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $this->ensureHrPermission('hr.manage_leave_requests');

        $leaveRequest->load('employee');

        return view('employee-management.leave-approval', [
            'leaveRequest' => $leaveRequest,
            'employee' => $leaveRequest->employee,
            'attachments' => $leaveRequest->attachments ?? [],
        ]);
    }

    public function review(Request $request, LeaveRequest $leaveRequest)
    {
        $this->ensureHrPermission('hr.approve_leave');

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return redirect()->route('hr.leave-requests.show', $leaveRequest)
                ->with('error', 'This leave request has already been reviewed.');
        }

        $leaveRequest->update([
            'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
            'status_note' => $validated['remarks'] ?? null,
            'reviewed_by_name' => session('employee_name') ?: 'HR Manager',
            'reviewed_by_position' => session('employee_position') ?: 'Human Resources',
            'reviewed_at' => now(),
        ]);

        return redirect()->route('hr.leave-management.index')
            ->with('success', 'The leave request has been '.($validated['action'] === 'approve' ? 'approved' : 'rejected').'.');
    }

    private function currentEmployee(): ?Employee
    {
        $employeeId = (int) session('employee_id');

        return $employeeId > 0 ? Employee::query()->find($employeeId) : null;
    }

    private function ensureHrPermission(string $permission): void
    {
        if (session('employee_role') === 'admin') {
            return;
        }

        $permissions = EmployeeAccessProfile::query()
            ->where('company_id', (int) session('employee_client_id'))
            ->where('employee_id', (int) session('employee_id'))
            ->value('access_permissions') ?? [];

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        abort_unless(in_array($permission, (array) $permissions, true), 403,
            'You do not have permission to manage leave requests.');
    }
}
