<?php

namespace Modules\HR\Http\Controllers;

use Modules\HR\Models\Employee;

class EmployeeProfileController extends Controller
{
    /**
     * The profile page came from the final HR UI. Resolve only the employee
     * stored in the authenticated HR session; the Employee model's client
     * scope prevents cross-client profile access.
     */
    public function show()
    {
        $employeeId = (int) session('employee_id');
        abort_unless($employeeId > 0, 403);

        $employee = Employee::query()->findOrFail($employeeId);

        return view('employees.employee-profile', compact('employee'));
    }
}
