<?php

namespace Modules\HR\Http\Controllers;

use Modules\HR\Http\Controllers\Concerns\ResolvesPerPage;
use Modules\HR\Http\Controllers\Concerns\RespondsWithAjaxList;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Illuminate\Http\Request;

class ReportsAnalyticsController extends Controller
{
    use ResolvesPerPage;
    use RespondsWithAjaxList;

    public function index(Request $request)
    {
        $clientId = $this->currentClientId();

        $employees = Employee::query()
            ->withCount([
                'attendances as present_days' => function ($query) use ($clientId) {
                    $query->where('attendances.client_id', $clientId);
                    $query->whereNotNull('time_in')
                        ->where(function ($q) {
                            $q->whereNull('status')
                                ->orWhereNotIn('status', ['Absent', 'Leave']);
                        });
                },
                'attendances as absent_days' => function ($query) use ($clientId) {
                    $query->where('attendances.client_id', $clientId);
                    $query->where(function ($q) {
                        $q->where('status', 'Absent')
                            ->orWhereNull('time_in');
                    })->where(function ($q) {
                        $q->whereNull('status')
                            ->orWhere('status', '!=', 'Leave');
                    });
                },
                'attendances as leave_days' => function ($query) use ($clientId) {
                    $query->where('attendances.client_id', $clientId)
                        ->where('status', 'Leave');
                },
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                });
            })
            ->when($request->filled('department'), function ($query) use ($request) {
                $query->where('department', $request->department);
            })
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $totalPresentDays = (int) Attendance::where('client_id', $clientId)
            ->whereNotNull('time_in')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['Absent', 'Leave']);
            })
            ->count();

        $totalAbsentDays = (int) Attendance::where('client_id', $clientId)->where(function ($q) {
            $q->where('status', 'Absent')->orWhereNull('time_in');
        })->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'Leave');
        })->count();

        $totalLeaveDays = (int) Attendance::where('client_id', $clientId)->where('status', 'Leave')->count();
        $employeeCount = Employee::count();

        if ($this->wantsAjaxList($request)) {
            return $this->ajaxListResponse(
                'reports-analytics.partials.attendance-overview-results',
                compact('employees')
            );
        }

        return view(
            'reports-analytics.attendance-overview',
            compact('employees', 'employeeCount', 'totalPresentDays', 'totalAbsentDays', 'totalLeaveDays')
        );
    }

    public function employeeAttendance(Request $request, $employee)
    {
        $employee = Employee::findOrFail($employee);
        $clientId = $this->currentClientId();

        $attendances = Attendance::where('client_id', $clientId)
            ->where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $attendances->getCollection()->each(
            fn (Attendance $row) => $row->setRelation('employee', $employee)
        );

        if ($this->wantsAjaxList($request)) {
            return $this->ajaxListResponse(
                'reports-analytics.partials.employee-attendance-results',
                compact('attendances')
            );
        }

        $allRecords = Attendance::where('client_id', $clientId)
            ->where('employee_id', $employee->id)
            ->get();

        $stats = [
            'present' => $allRecords->filter(fn (Attendance $row) => $row->displayStatus() === 'Present')->count(),
            'absent' => $allRecords->filter(fn (Attendance $row) => $row->displayStatus() === 'Absent' && $row->status !== 'Leave')->count(),
            'leave' => $allRecords->where('status', 'Leave')->count(),
            'total' => $allRecords->count(),
        ];

        return view(
            'reports-analytics.employee-attendance',
            compact('employee', 'attendances', 'stats')
        );
    }

    /**
     * Employee self-service attendance reuses the same safe, client-scoped
     * report used by HR managers. The route is separately protected by the
     * employee middleware, so an employee can only pass their session ID.
     */
    public function selfAttendance(Request $request)
    {
        $employeeId = (int) session('employee_id');

        if ($employeeId <= 0) {
            return redirect()->route('login');
        }

        return $this->employeeAttendance($request, $employeeId);
    }

    public function leave(Request $request)
    {
        $employees = Employee::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                });
            })
            ->when($request->filled('department'), function ($query) use ($request) {
                $query->where('department', $request->department);
            })
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $leaveRequests = LeaveRequest::query()
            ->with('employee')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('id')
            ->paginate($this->perPage($request), ['*'], 'leave_page')
            ->withQueryString();

        $approvedCount = LeaveRequest::where('status', 'approved')->count();
        $rejectedCount = LeaveRequest::where('status', 'rejected')->count();
        $totalReviewed = $approvedCount + $rejectedCount;
        $reportsThisMonth = LeaveRequest::whereIn('status', ['approved', 'rejected'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        if ($this->wantsAjaxList($request)) {
            return $this->ajaxListResponse(
                'reports-analytics.partials.leave-results',
                compact('employees', 'leaveRequests')
            );
        }

        return view('reports-analytics.leave', compact(
            'employees',
            'leaveRequests',
            'approvedCount',
            'rejectedCount',
            'totalReviewed',
            'reportsThisMonth'
        ));
    }

    private function currentClientId(): int
    {
        return (int) session('employee_client_id');
    }
}
