<?php

namespace Modules\HR\Http\Controllers;

use Modules\HR\Models\Employee;
use Modules\HR\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $role = session('employee_role');
        $department = strtolower(trim(session('employee_department', '')));
        $clientId = (int) session('employee_client_id');

        if ($role !== 'admin' && $department !== 'human resources') {
            return redirect()->route('hr.employee.dashboard');
        }

        // Employee already carries the HR client scope, but keep the dashboard
        // explicitly tied to the signed-in company for clarity and safety.
        $employeeCount = Employee::query()
            ->where('client_id', $clientId)
            ->count();
        $presentToday = Attendance::where('client_id', $clientId)
            ->whereDate('attendance_date', today())
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->count();

        $currentYear = today()->year;

        $monthlyHireStats = Employee::query()
            ->selectRaw('EXTRACT(MONTH FROM created_at)::int as month, COUNT(*) as hires')
            ->where('client_id', $clientId)
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->get()
            ->keyBy(fn ($item) => (int) $item->month);

        // The final HR dashboard renders this series directly.  Keeping all
        // twelve months also prevents an empty chart for newly created clients.
        $monthlyHireTrend = collect(range(1, 12))->map(function ($month) use ($monthlyHireStats, $currentYear) {
            return [
                'month' => Carbon::create($currentYear, $month, 1)->format('M'),
                'month_name' => Carbon::create($currentYear, $month, 1)->format('F'),
                'hires' => (int) ($monthlyHireStats->get($month)?->hires ?? 0),
            ];
        });

        $monthStats = Attendance::selectRaw('EXTRACT(MONTH FROM attendance_date)::int as month, COUNT(*) as present_days')
            ->where('client_id', $clientId)
            ->whereYear('attendance_date', $currentYear)
            ->whereNotNull('time_in')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['Absent', 'Leave']);
            })
            ->groupBy('month')
            ->get()
            ->keyBy(function ($item) {
                return (int) $item->month;
            });

        $totalPresentDaysYear = (int) $monthStats->sum('present_days');

        $monthlyAttendance = collect(range(1, 12))->map(function ($month) use ($monthStats, $employeeCount, $currentYear) {
            $stats = $monthStats->get($month);
            $presentDays = (int) ($stats?->present_days ?? 0);
            $daysInMonth = Carbon::create($currentYear, $month, 1)->daysInMonth;
            $possibleDays = max(1, $employeeCount * $daysInMonth);
            $rate = $employeeCount > 0
                ? round(($presentDays / $possibleDays) * 100, 2)
                : 0;

            return [
                'month' => Carbon::create($currentYear, $month, 1)->format('M'),
                'month_name' => Carbon::create($currentYear, $month, 1)->format('F'),
                'month_number' => $month,
                'present_days' => $presentDays,
                'rate' => $rate,
            ];
        });

        $daysElapsedThisYear = today()->dayOfYear;
        $possibleDaysYear = max(1, $employeeCount * $daysElapsedThisYear);
        $overallAttendanceRate = $employeeCount > 0
            ? round(($totalPresentDaysYear / $possibleDaysYear) * 100, 2)
            : 0;

        $previousMonth = today()->copy()->subMonth();
        $prevPresent = (int) ($monthStats->get($previousMonth->month)?->present_days ?? 0);
        $prevPossible = max(1, $employeeCount * $previousMonth->daysInMonth);
        $prevRate = $employeeCount > 0 ? ($prevPresent / $prevPossible) * 100 : 0;
        $rateChange = round($overallAttendanceRate - $prevRate, 1);

        $currentMonth = today()->month;

        return view('dashboard.index', compact(
            'employeeCount',
            'presentToday',
            'monthlyHireTrend',
            'monthlyAttendance',
            'currentMonth',
            'overallAttendanceRate',
            'totalPresentDaysYear',
            'rateChange'
        ));
    }

    public function employeeIndex()
    {
        $employeeCount = Employee::count();
        $isHr = session('employee_role') === 'admin'
            || strtolower(trim(session('employee_department', ''))) === 'human resources';

        return view('dashboard.employee-dashboard', compact('employeeCount', 'isHr'));
    }
}
