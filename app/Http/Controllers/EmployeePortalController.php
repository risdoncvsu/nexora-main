<?php

namespace App\Http\Controllers;

use App\Models\Company;
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

        [$department, $moduleUrl] = $this->moduleDestination();
        return view('employee-portal', [
            'company' => Company::find($clientId),
            'department' => $department,
            'moduleUrl' => $moduleUrl,
            // HR owns these workflows. ITSM is now the employee landing page,
            // so it exposes links while leaving the HR routes and access rules
            // unchanged.
            // These are employee self-service workflows. HR managers use the
            // HR management routes, whose permissions are separate from an
            // employee's own leave/attendance screens.
            'showHrSelfService' => session('employee_role') === 'employee',
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
