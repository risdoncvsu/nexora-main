<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmployeeAccessProfile;
use App\Services\HrEmployeeProfileProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientAccessController extends Controller
{
    private const ROLES = [
        'department_employee' => 'Department Employee',
        'department_manager' => 'Department Manager',
        'auditor' => 'Auditor / Viewer',
    ];

    private const MODULES = [
        'hr' => 'Human Resources',
        'inventory' => 'Inventory & Warehouse',
        'procurement' => 'Procurement',
        'order_fulfillment' => 'Order Fulfillment',
        'manufacturing' => 'Manufacturing & Production',
        'finance' => 'Finance & Accounting',
        'bi' => 'Business Intelligence',
        'ecommerce' => 'E-commerce & CRM',
    ];

    public function __construct(private readonly HrEmployeeProfileProvisioner $hrEmployees)
    {
    }

    public function index()
    {
        $company = $this->company();
        abort_unless($company, 403);

        $profiles = EmployeeAccessProfile::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('employee_id');

        $employees = $this->hrEmployees->employeesForCompany($company)->map(function (object $employee) use ($profiles): object {
            $profile = $profiles->get($employee->id);
            $employee->access_role = $profile?->access_role ?? $this->suggestRole($employee);
            $employee->module_access = $profile?->module_access ?? $this->suggestModules($employee);

            return $employee;
        });

        return view('client-access.index', [
            'company' => $company,
            'employees' => $employees,
            'roles' => self::ROLES,
            'modules' => self::MODULES,
        ]);
    }

    public function update(Request $request, int $employee): RedirectResponse
    {
        $company = $this->company();
        abort_unless($company, 403);
        abort_unless($this->hrEmployees->hasEmployeeForCompany($company, $employee), 404);

        $data = $request->validate([
            'access_role' => ['required', Rule::in(array_keys(self::ROLES))],
            'module_access' => ['nullable', 'array'],
            'module_access.*' => [Rule::in(array_keys(self::MODULES))],
        ]);

        $modules = array_values(array_unique($data['module_access'] ?? []));

        EmployeeAccessProfile::query()->updateOrCreate(
            ['company_id' => $company->id, 'employee_id' => $employee],
            ['access_role' => $data['access_role'], 'module_access' => $modules],
        );

        return back()->with('success', 'Access role and module access were updated.');
    }

    private function company(): ?Company
    {
        $user = Auth::user();

        return $user?->role === 'company_admin' && $user->company_id
            ? Company::find($user->company_id)
            : null;
    }

    private function suggestRole(object $employee): string
    {
        $title = strtolower((string) ($employee->position ?? ''));

        return str_contains($title, 'manager') || str_contains($title, 'supervisor')
            ? 'department_manager'
            : 'department_employee';
    }

    private function suggestModules(object $employee): array
    {
        $assignment = strtolower((string) ($employee->department ?? '') . ' ' . (string) ($employee->position ?? ''));

        return match (true) {
            str_contains($assignment, 'human resources'), str_contains($assignment, ' hr ') => ['hr'],
            str_contains($assignment, 'inventory'), str_contains($assignment, 'warehouse') => ['inventory'],
            str_contains($assignment, 'procurement'), str_contains($assignment, 'purchasing') => ['procurement'],
            str_contains($assignment, 'fulfillment'), str_contains($assignment, 'shipping'), str_contains($assignment, 'order') => ['order_fulfillment'],
            str_contains($assignment, 'manufacturing'), str_contains($assignment, 'production') => ['manufacturing'],
            str_contains($assignment, 'finance'), str_contains($assignment, 'accounting') => ['finance'],
            str_contains($assignment, 'business intelligence'), str_contains($assignment, 'analytics') => ['bi'],
            str_contains($assignment, 'e-commerce'), str_contains($assignment, 'ecommerce'), str_contains($assignment, 'crm') => ['ecommerce'],
            default => [],
        };
    }
}
