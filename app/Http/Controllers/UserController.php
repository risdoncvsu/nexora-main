<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmployeeAccessProfile;
use App\Services\HrEmployeeProfileProvisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    private const ACCESS_ROLES = [
        'department_employee',
        'department_manager',
        'auditor',
    ];

    private const ACCESS_PERMISSIONS = [
        'hr.view_employee_records',
        'hr.create_employees',
        'hr.edit_employee_records',
        'hr.delete_employees',
        'hr.manage_departments',
        'hr.manage_onboarding',
        'hr.manage_attendance',
        'hr.view_attendance_reports',
        'hr.manage_leave_requests',
        'hr.approve_leave',
        'hr.manage_employee_documents',
        'inventory.manage_catalog',
        'inventory.receive_stock',
        'inventory.adjust_stock',
        'procurement.approve_purchase_orders',
        'order_fulfillment.update_orders',
        'manufacturing.manage_work_orders',
        'manufacturing.record_quality_checks',
        'finance.manage_invoices',
        'ecommerce.manage_storefront',
        'bi.view_analytics',
    ];

    private const DEPARTMENT_PERMISSION_MODULES = [
        'human resources' => 'hr',
        'hr' => 'hr',
        'human resource' => 'hr',
        'inventory management' => 'inventory',
        'inventory' => 'inventory',
        'procurement management' => 'procurement',
        'procurement' => 'procurement',
        'order management' => 'order_fulfillment',
        'order fulfillment' => 'order_fulfillment',
        'order fulfillment & operations' => 'order_fulfillment',
        'production management' => 'manufacturing',
        'manufacturing' => 'manufacturing',
        'production' => 'manufacturing',
        'finance' => 'finance',
        'finance and accounting' => 'finance',
        'finance & accounting' => 'finance',
        'e-commerce' => 'ecommerce',
        'ecommerce' => 'ecommerce',
        'e-commerce and crm' => 'ecommerce',
        'business intelligence' => 'bi',
        'business intelligence and analytics' => 'bi',
        'bi' => 'bi',
    ];
    public function __construct(
        private readonly HrEmployeeProfileProvisioner $hrEmployeeProfileProvisioner,
    )
    {
    }


    public function clients()
    {
        $companies = Company::with('adminUser')->orderByDesc('created_at')->get();

        return view('users.index', [
            'users' => $companies,
            'portal' => 'admin',
            'active' => 'clients',
            'title' => 'Client Management',
            'entityLabel' => 'client',
            'entityLabelPlural' => 'clients',
            'primaryIdLabel' => 'Client ID',
        ]);
    }

    public function employees()
    {
        $company = $this->clientCompany();
        $employees = $company ? $this->hrEmployeeProfileProvisioner->employeesForCompany($company) : collect();

        if ($company && $employees->isNotEmpty()) {
            $profiles = EmployeeAccessProfile::query()
                ->select([
                    'id',
                    'company_id',
                    'employee_id',
                    'access_role',
                    'access_permissions',
                ])
                ->where('company_id', $company->id)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->keyBy('employee_id');

            $employees->each(function (object $employee) use ($profiles): void {
                $profile = $profiles->get($employee->id);
                $employee->access_role = $profile?->access_role ?? $this->suggestAccessRole($employee);
                $permissions = $profile?->access_permissions ?? [];

                // Older profile rows can contain JSON as a raw string. Normalise it
                // before serialising it into the edit modal after a saved redirect.
                if (is_string($permissions)) {
                    $permissions = json_decode($permissions, true) ?: [];
                }

                $employee->access_permissions = array_values(array_map('strval', is_array($permissions) ? $permissions : []));
            });
        }

        return view('users.index', [
            'users' => $employees,
            'portal' => 'client',
            'active' => 'employees',
            'title' => 'Employee Management',
            'entityLabel' => 'employee',
            'entityLabelPlural' => 'employees',
            'primaryIdLabel' => 'Employee ID',
            'accessRoles' => self::ACCESS_ROLES,
            'accessPermissions' => self::ACCESS_PERMISSIONS,
        ]);
    }

    public function pendingApprovals()
    {
        $company = $this->clientCompany();
        $employees = $company
            ? $this->hrEmployeeProfileProvisioner->employeesForCompany($company)
                ->filter(fn (object $employee): bool => $employee->status === 'Pending')
                ->values()
            : collect();

        return view('users.index', [
            'users' => $employees,
            'portal' => 'client',
            'active' => 'pending-approvals',
            'title' => 'Pending Approvals',
            'entityLabel' => 'approval',
            'entityLabelPlural' => 'approvals',
            'primaryIdLabel' => 'Employee ID',
        ]);
    }

    public function updateEmployee(Request $request, int $employee): RedirectResponse
    {
        $company = $this->clientCompany();
        abort_unless($company, 403);
        $validated = $request->validate([
            'username' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Pending,Suspended'],
            'access_role' => ['nullable', 'in:department_employee,department_manager,auditor'],
            'access_permissions' => ['nullable', 'array'],
            'access_permissions.*' => ['in:' . implode(',', self::ACCESS_PERMISSIONS)],
        ]);

        $currentEmployee = $this->hrEmployeeProfileProvisioner->findEmployeeForCompany($company, $employee);
        abort_unless($currentEmployee, 404);

        $allowedPermissions = $this->permissionsForDepartment($currentEmployee->department ?? null);
        $selectedPermissions = array_values(array_unique($validated['access_permissions'] ?? []));
        abort_if(
            array_diff($selectedPermissions, $allowedPermissions) !== [],
            422,
            'Access permissions must belong to the employee\'s assigned department.'
        );

        if ($currentEmployee->status === 'Pending' && $validated['status'] === 'Active') {
            return redirect()
                ->route('client.itsm.pending-approvals')
                ->withErrors(['status' => 'Approve the HR manager from Pending Approvals to create their login credentials.']);
        }

        $this->hrEmployeeProfileProvisioner->updateEmployeeForCompany($company, $employee, $validated);

        EmployeeAccessProfile::query()->updateOrCreate(
            ['company_id' => $company->id, 'employee_id' => $employee],
            [
                'access_role' => $validated['access_role'] ?? $this->suggestAccessRole($currentEmployee),
                'access_permissions' => $selectedPermissions,
            ],
        );

        return redirect()
            ->route('client.itsm.employees')
            ->with('success', 'Employee updated successfully.');
    }

    public function approveHrManager(int $employee): RedirectResponse
    {
        $company = $this->clientCompany();
        abort_unless($company, 403);

        $manager = $this->hrEmployeeProfileProvisioner->findEmployeeForCompany($company, $employee);
        abort_unless(
            $manager && $manager->status === 'Pending',
            404
        );

        if ($manager->department !== 'Human Resources' || $manager->username) {
            $this->hrEmployeeProfileProvisioner->approveEmployeeForCompany($company, $employee);

            return redirect()
                ->route('client.itsm.pending-approvals')
                ->with('success', 'Employee approved. They will be required to change their password on first sign-in.');
        }

        $password = Str::password(16, symbols: true);
        $provisioned = $this->hrEmployeeProfileProvisioner->provisionApprovedHrManager($company, $manager, $password);

        $company->update(['hr_employee_id' => $provisioned['employee_id']]);

        return redirect()
            ->route('client.itsm.pending-approvals')
            ->with('success', 'HR manager approved and login credentials generated.')
            ->with('hr_credentials', [
                'username' => $provisioned['email'],
                'password' => $password,
            ]);
    }

    private function clientCompany(): ?Company
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'company_admin') {
            return null;
        }

        return Company::find($user->company_id);
    }

    private function suggestAccessRole(object $employee): string
    {
        $position = strtolower((string) ($employee->position ?? ''));

        return str_contains($position, 'manager') || str_contains($position, 'supervisor')
            ? 'department_manager'
            : 'department_employee';
    }

    private function permissionsForDepartment(?string $department): array
    {
        $module = self::DEPARTMENT_PERMISSION_MODULES[strtolower(trim((string) $department))] ?? null;

        return $module
            ? array_values(array_filter(
                self::ACCESS_PERMISSIONS,
                fn (string $permission): bool => str_starts_with($permission, $module . '.')
            ))
            : [];
    }


     public function index()
    {
        return $this->employees();
    }

    public function pending()
    {
        // Root-side pending approvals are company registrations/onboarding
        // records, not HR employees (those belong to the client's own ITSM
        // Pending Approvals screen).  Rendering the shared client table here
        // used to query an obsolete users.status column and caused a 500.
        $companies = Company::with('adminUser')
            ->where('status', 'Pending')
            ->latest()
            ->get();

        return view('users.index', [
            'users' => $companies,
            'portal' => 'admin',
            'active' => 'pending-approvals',
            'title' => 'Pending Client Approvals',
            'entityLabel' => 'client',
            'entityLabelPlural' => 'clients',
            'primaryIdLabel' => 'Client ID',
        ]);
    }
}
