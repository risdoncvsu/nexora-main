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

    /**
     * Serve both legacy module-bundled photos and newly uploaded public photos.
     * This avoids broken avatars after the HR module was moved into the ERP
     * while keeping filenames out of the public path traversal surface.
     */
    public function picture(string $filename)
    {
        $filename = basename($filename);

        foreach ([
            public_path('profile_pictures/'.$filename),
            base_path('Modules/HR/public/profile_pictures/'.$filename),
        ] as $path) {
            if (is_file($path)) {
                return response()->file($path, [
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        abort(404);
    }
}
