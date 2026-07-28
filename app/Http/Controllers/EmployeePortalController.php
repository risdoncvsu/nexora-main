<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmployeeAccessProfile;
use App\Models\ServiceTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $accessProfile = EmployeeAccessProfile::query()
            ->where('company_id', $clientId)
            ->where('employee_id', (int) session('employee_id'))
            ->first();
        [$department, $moduleUrl] = $this->moduleDestination($accessProfile?->module_access ?? []);
        return view('employee-portal', [
            'company' => Company::find($clientId),
            'department' => $department,
            'moduleUrl' => $moduleUrl,
            // HR owns these workflows. ITSM is now the employee landing page,
            // so it exposes links while leaving the HR routes and access rules
            // unchanged.
            // HR managers are employees too.  Their role is `admin` only
            // within the HR module and must not hide their own attendance and
            // leave links from the shared employee portal.
            'showHrSelfService' => true,
            'attendanceUrl' => route('hr.employee.attendance'),
            'leaveUrl' => route('hr.employee.leave'),
            'tickets' => $tickets,
        ]);
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

    private function nextTicketNo(): string
    {
        return 'NX-' . str_pad((string) ((int) ServiceTicket::max('id') + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array{0: string, 1: string} */
    private function moduleDestination(array $allowedModules = []): array
    {
        $department = (string) session('employee_department', 'Human Resources');
        $assignment = Str::lower($department.' '.(string) session('employee_position', ''));

        $departmentModule = match (true) {
            str_contains($assignment, 'inventory'), str_contains($assignment, 'warehouse') => 'inventory',
            str_contains($assignment, 'procurement'), str_contains($assignment, 'purchasing') => 'procurement',
            str_contains($assignment, 'fulfillment'), str_contains($assignment, 'operations'), str_contains($assignment, 'shipping'), str_contains($assignment, 'order') => 'order_fulfillment',
            str_contains($assignment, 'manufacturing'), str_contains($assignment, 'production') => 'manufacturing',
            str_contains($assignment, 'finance'), str_contains($assignment, 'accounting') => 'finance',
            str_contains($assignment, 'business intelligence'), str_contains($assignment, 'business analytics'), preg_match('/(^|\s)bi(\s|$)/', $assignment) === 1 => 'bi',
            str_contains($assignment, 'e-commerce'), str_contains($assignment, 'ecommerce'), str_contains($assignment, 'electronic commerce'), str_contains($assignment, 'crm') => 'ecommerce',
            default => 'hr',
        };

        // A profile with no selected modules preserves existing department
        // behaviour. Once a client admin saves explicit module access, use the
        // department module when allowed, otherwise a permitted fallback.
        $module = $allowedModules === [] || in_array($departmentModule, $allowedModules, true)
            ? $departmentModule
            : $allowedModules[0];

        return $this->moduleFor($module);
    }

    /** @return array{0: string, 1: string} */
    private function moduleFor(string $module): array
    {
        return match ($module) {
            'inventory' => ['Inventory & Warehouse', route('inventory.index')],
            'procurement' => ['Procurement', route('procurement.dashboard')],
            'order_fulfillment' => ['Order Fulfillment', route('order-fulfillment.dashboard')],
            'manufacturing' => ['Manufacturing & Production', route('manufacturing.dashboard')],
            'finance' => ['Finance & Accounting', route('finance.dashboard')],
            'bi' => ['Business Intelligence', route('bi.dashboard')],
            'ecommerce' => ['E-commerce & CRM', url('/ecommerce-admin')],
            default => ['Human Resources', session('employee_role') === 'admin' ? route('hr.dashboard') : route('hr.employee.dashboard')],
        };
    }
}
