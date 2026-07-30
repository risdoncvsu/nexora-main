<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ServiceTicket;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;

class EmployeePortalController extends Controller
{
    public function index()
    {
        $clientId = (int) session('employee_client_id');
        $requester = $this->requester();
        $tickets = ServiceTicket::query()
            ->where('company_id', $clientId)
            ->where('ticket_type', 'erp_module')
            ->where('requester', $requester)
            ->latest()
            ->get();

        [$department, $moduleUrl] = $this->moduleDestination();
        return view('employee-portal', [
            'company' => Company::find($clientId),
            'department' => $department,
            'moduleUrl' => $moduleUrl,
            'showEmployeeSelfService' => (bool) session('employee_logged_in'),
            'attendanceUrl' => route('employee.portal.attendance'),
            'leaveUrl' => route('employee.portal.leave'),
            'tickets' => $tickets,
        ]);
    }

    public function attendance(Request $request)
    {
        $employee = $this->currentEmployee();
        abort_unless($employee, 403);

        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $attendances->getCollection()->each(
            fn (Attendance $attendance) => $attendance->setRelation('employee', $employee)
        );

        $records = Attendance::query()
            ->where('employee_id', $employee->id)
            ->get();

        return view('employee-portal.attendance', [
            'employee' => $employee,
            'attendances' => $attendances,
            'stats' => [
                'present' => $records->filter(fn (Attendance $record) => $record->displayStatus() === 'Present')->count(),
                'absent' => $records->filter(fn (Attendance $record) => $record->displayStatus() === 'Absent' && $record->status !== 'Leave')->count(),
                'leave' => $records->where('status', 'Leave')->count(),
                'total' => $records->count(),
            ],
        ]);
    }

    public function leave()
    {
        $employee = $this->currentEmployee();
        abort_unless($employee, 403);

        return view('employee-portal.leave', [
            'employee' => $employee,
            'leaveRequests' => LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();
        abort_unless($employee, 403);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['vacation', 'sick', 'maternity', 'paternity', 'bereavement', 'others'])],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
        $toDate = Carbon::parse($validated['to_date'])->startOfDay();
        $totalDays = $fromDate->diffInDays($toDate) + 1;
        $durationRules = [
            'vacation' => [5, 15],
            'sick' => [5, 15],
            'maternity' => [1, 105],
            'paternity' => [1, 7],
            'bereavement' => [1, 5],
        ];

        if (isset($durationRules[$validated['type']])) {
            [$minimumDays, $maximumDays] = $durationRules[$validated['type']];
            if ($totalDays < $minimumDays || $totalDays > $maximumDays) {
                return back()->withInput()->withErrors([
                    'to_date' => ucfirst($validated['type'])." leave must be between {$minimumDays} and {$maximumDays} days long.",
                ]);
            }
        }

        $attachments = [];
        foreach ($request->file('attachments', []) as $attachment) {
            $attachments[] = $attachment->store('hr/leave-attachments/'.(int) $employee->client_id, 'public');
        }

        $leaveRequest = LeaveRequest::create([
            'client_id' => $employee->client_id,
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'total_days' => $totalDays,
            'reason' => $validated['reason'] ?? null,
            'attachments' => $attachments ?: null,
            'status' => 'pending',
        ]);
        $leaveRequest->update(['reference_id' => sprintf('LR-%s-%04d', now()->format('Y'), $leaveRequest->id)]);

        return redirect()->route('employee.portal.leave')->with('success', 'Your leave request has been submitted for HR review.');
    }

    public function storeTicket(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $clientId = (int) session('employee_client_id');
        $company = Company::find($clientId);

        ServiceTicket::create($validated + [
            'company_id' => $clientId,
            'created_by' => null,
            'ticket_no' => $this->nextTicketNo(),
            'ticket_type' => 'erp_module',
            'requester' => $this->requester(),
            'client_name' => $company?->company_name,
            'module' => (string) session('employee_department', 'General ERP'),
            'status' => 'Open',
        ]);

        return back()->with('success', 'Your support ticket was sent to your client ITSM team.');
    }

    private function requester(): string
    {
        $name = trim((string) session('employee_name', 'Employee'));
        $email = trim((string) session('employee_email', ''));

        return $email !== '' ? "{$name} <{$email}>" : $name;
    }

    private function currentEmployee(): ?Employee
    {
        $employeeId = (int) session('employee_id');
        $clientId = (int) session('employee_client_id');

        return $employeeId > 0 && $clientId > 0
            ? Employee::query()->whereKey($employeeId)->where('client_id', $clientId)->first()
            : null;
    }

    private function nextTicketNo(): string
    {
        return 'NX-' . str_pad((string) ((int) ServiceTicket::max('id') + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array{0: string, 1: string} */
    private function moduleDestination(): array
    {
        $department = (string) session('employee_department', 'Human Resources');
        $assignment = Str::lower($department.' '.(string) session('employee_position', ''));

        return match (true) {
            str_contains($assignment, 'inventory'), str_contains($assignment, 'warehouse') => ['Inventory & Warehouse', route('inventory.index')],
            str_contains($assignment, 'procurement'), str_contains($assignment, 'purchasing') => ['Procurement', route('procurement.dashboard')],
            str_contains($assignment, 'fulfillment'), str_contains($assignment, 'operations'), str_contains($assignment, 'shipping'), str_contains($assignment, 'order') => ['Order Fulfillment', route('order-fulfillment.dashboard')],
            str_contains($assignment, 'manufacturing'), str_contains($assignment, 'production') => ['Manufacturing & Production', route('manufacturing.dashboard')],
            str_contains($assignment, 'finance'), str_contains($assignment, 'accounting') => ['Finance & Accounting', route('finance.dashboard')],
            str_contains($assignment, 'business intelligence'), str_contains($assignment, 'business analytics'), preg_match('/(^|\s)bi(\s|$)/', $assignment) === 1 => ['Business Intelligence', route('bi.dashboard')],
            str_contains($assignment, 'e-commerce'), str_contains($assignment, 'ecommerce'), str_contains($assignment, 'electronic commerce'), str_contains($assignment, 'crm') => ['E-commerce & CRM', url('/ecommerce-admin')],
            default => ['Human Resources', session('employee_role') === 'admin' ? route('hr.dashboard') : route('hr.employee.dashboard')],
        };
    }
}
