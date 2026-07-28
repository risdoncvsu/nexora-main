@php
    $portal = $portal ?? 'client';
    $active = $active ?? ($portal === 'admin' ? 'clients' : 'employees');
    $title = $title ?? ($portal === 'admin' ? 'Client Management' : 'Employee Management');
    $entityLabel = $entityLabel ?? ($portal === 'admin' ? 'client' : 'employee');
    $entityLabelPlural = $entityLabelPlural ?? ($portal === 'admin' ? 'clients' : 'employees');
    $primaryIdLabel = $primaryIdLabel ?? ($portal === 'admin' ? 'Client ID' : 'Employee ID');
    $accessRoleLabels = [
        'department_employee' => 'Department Employee',
        'department_manager' => 'Department Manager',
        'auditor' => 'Auditor / Viewer',
    ];
    $permissionGroups = [
        'hr' => [
            'label' => 'Human Resources',
            'permissions' => [
                'hr.view_employee_records' => 'View employee records',
                'hr.create_employees' => 'Create employee records',
                'hr.edit_employee_records' => 'Edit employee records',
                'hr.delete_employees' => 'Delete employee records',
                'hr.manage_departments' => 'Manage departments',
                'hr.manage_onboarding' => 'Manage employee onboarding',
                'hr.manage_attendance' => 'Manage attendance records',
                'hr.view_attendance_reports' => 'View attendance reports',
                'hr.manage_leave_requests' => 'Manage leave requests',
                'hr.approve_leave' => 'Approve or reject leave',
                'hr.manage_employee_documents' => 'Manage employee documents',
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'permissions' => [
                'inventory.manage_catalog' => 'Manage item catalog',
                'inventory.receive_stock' => 'Receive stock',
                'inventory.adjust_stock' => 'Adjust stock',
            ],
        ],
        'procurement' => [
            'label' => 'Procurement',
            'permissions' => ['procurement.approve_purchase_orders' => 'Approve purchase orders'],
        ],
        'order_fulfillment' => [
            'label' => 'Order Fulfillment',
            'permissions' => ['order_fulfillment.update_orders' => 'Update order fulfillment'],
        ],
        'manufacturing' => [
            'label' => 'Manufacturing',
            'permissions' => [
                'manufacturing.manage_work_orders' => 'Manage work orders',
                'manufacturing.record_quality_checks' => 'Record quality checks',
            ],
        ],
        'finance' => [
            'label' => 'Finance',
            'permissions' => ['finance.manage_invoices' => 'Manage invoices'],
        ],
        'ecommerce' => [
            'label' => 'E-commerce',
            'permissions' => ['ecommerce.manage_storefront' => 'Manage storefront'],
        ],
        'bi' => [
            'label' => 'Business Intelligence',
            'permissions' => ['bi.view_analytics' => 'View analytics'],
        ],
    ];
    $navItems = $portal === 'admin'
        ? [
            ['label' => 'Registration', 'route' => route('admin.itsm.registration'), 'key' => 'registration'],
            ['label' => 'Client Management', 'route' => route('admin.itsm.clients'), 'key' => 'clients'],
            ['label' => 'Service Desk', 'route' => route('admin.itsm.service-desk'), 'key' => 'service-desk'],
        ]
        : [
            ['label' => 'Employee Management', 'route' => route('client.itsm.employees'), 'key' => 'employees'],
            ['label' => 'Service Desk', 'route' => route('client.itsm.service-desk'), 'key' => 'service-desk'],
            ['label' => 'Compliance Tracking', 'route' => route('client.itsm.compliance'), 'key' => 'compliance'],
            ['label' => 'Risk Management', 'route' => route('client.itsm.risk'), 'key' => 'risk'],
        ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | {{ $title }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header
            :home-route="$portal === 'admin' ? route('admin.itsm.registration') : route('client.itsm.employees')"
            :active="$active"
            :nav-items="$navItems"
        />

        <main class="relative flex-1 overflow-hidden p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">

            <div class="relative z-10 grid min-h-[calc(100vh-10rem)] grid-cols-[22rem_1fr] gap-6">
                <aside class="rounded-[1.875rem] bg-white p-8 text-slate-950">
                    <nav class="space-y-6 text-xl">
                        <a href="{{ $portal === 'admin' ? route('admin.itsm.clients') : route('client.itsm.employees') }}" class="block {{ $active === 'employees' || $active === 'clients' ? 'font-extrabold' : 'font-medium hover:text-[#346DCB]' }}">All {{ $portal === 'admin' ? ucfirst($entityLabelPlural) : 'Employees' }}</a>
                        @if ($portal === 'admin')
                            <a href="{{ route('admin.itsm.pending-approvals') }}" class="block font-medium hover:text-[#346DCB]">Pending Approvals</a>
                        @else
                            <a href="{{ route('client.itsm.employees') }}" class="block font-medium hover:text-[#346DCB]">HR Sync Queue</a>
                            <a href="{{ route('client.itsm.pending-approvals') }}" class="block {{ $active === 'pending-approvals' ? 'font-extrabold text-[#346DCB]' : 'font-medium hover:text-[#346DCB]' }}">Pending Approvals</a>
                        @endif
                        @if ($portal === 'admin')
                            <a href="{{ route('admin.itsm.roles') }}" class="block font-medium hover:text-[#346DCB]">Roles & Permissions</a>
                        @endif

                    </nav>
                </aside>

                <section class="flex flex-col gap-6">
                    <div class="flex items-center justify-between rounded-[1.875rem] bg-white/90 px-10 py-6 text-slate-950">
                        <h1 class="text-5xl font-bold">{{ $title }}</h1>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-3 rounded-full bg-slate-200 px-6 py-3 text-2xl text-slate-500">
                                <span>Search</span>
                                <input type="text" id="tableSearch" class="w-48 border-0 bg-transparent text-xl text-slate-900 outline-none">
                            </label>

                            

                            @if ($portal === 'admin' && $active !== 'pending-approvals')
                                <button type="button" id="editSelectedButton" disabled class="rounded-full bg-slate-500 px-6 py-3 text-xl font-semibold text-white opacity-50 transition enabled:bg-[#0B1E3D] enabled:opacity-100 enabled:hover:bg-[#132B52]">
                                    Edit selected
                                </button>
                            @endif

                            @if ($portal === 'admin')
                                <button type="button" id="deleteSelectedButton" disabled class="rounded-full bg-red-500 px-6 py-3 text-xl font-semibold text-white opacity-50 transition enabled:opacity-100 enabled:hover:bg-red-600">
                                    Delete selected
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 rounded-[1.875rem] bg-white p-8 text-slate-950">
                        @if ($errors->any())
                            <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('generated_credentials'))
                            <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                <p class="font-bold">Company admin credentials</p>
                                <p class="mt-2">Username: <span class="font-mono">{{ session('generated_credentials.username') }}</span></p>
                                <p>Password: <span class="font-mono">{{ session('generated_credentials.password') }}</span></p>
                            </div>
                        @endif

                        @if (session('hr_credentials'))
                            <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                <p class="font-bold">Approved HR manager credentials</p>
                                <p class="mt-2">Username: <span class="font-mono">{{ session('hr_credentials.username') }}</span></p>
                                <p>Password: <span class="font-mono">{{ session('hr_credentials.password') }}</span></p>
                            </div>
                        @endif

                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold">{{ $active === 'pending-approvals' ? ($portal === 'admin' ? 'Client accounts awaiting root review' : 'Employee accounts awaiting your approval') : 'All ' . $entityLabelPlural }}</h2>
                            @if ($portal === 'admin' && $active !== 'pending-approvals')
                                <label class="flex items-center gap-2 text-base">
                                    <input type="checkbox" id="selectAllCheckbox" class="h-5 w-5 accent-[#346DCB]">
                                    Select All
                                </label>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse" id="usersTable">
                                <thead>
                                    <tr class="border-b-2 border-slate-200 text-left text-lg font-semibold">
                                        <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">{{ $primaryIdLabel }}</th>
                                        <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">{{ $portal === 'admin' ? 'Company' : 'Username' }}</th>
                                        <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">{{ $portal === 'admin' ? 'Primary Contact' : 'Full Name' }}</th>
                                        <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">{{ $portal === 'admin' ? 'Admin Login' : 'Email' }}</th>
                                        <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">{{ $portal === 'admin' ? 'Industry' : 'Department' }}</th>
                                        @if ($portal === 'client')
                                            <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">ERP Role</th>
                                        @endif
                                        <th class="sortable cursor-pointer whitespace-nowrap px-2 py-4">Status</th>
                                        <th class="px-2 py-4 text-center">{{ $portal === 'client' || $active === 'pending-approvals' ? 'Action' : '' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        <tr
                                            class="border-b border-slate-200"
                                            data-row-id="{{ $user->id }}"
                                            data-company-name="{{ $portal === 'admin' ? e($user->company_name) : '' }}"
                                            data-admin-name="{{ $portal === 'admin' ? e($user->admin_name) : '' }}"
                                            data-company-email="{{ $portal === 'admin' ? e($user->company_email) : '' }}"
                                            data-phone-no="{{ $portal === 'admin' ? e($user->phone_no) : '' }}"
                                            data-industry="{{ $portal === 'admin' ? e($user->industry) : '' }}"
                                            data-status="{{ e($user->status ?? 'Active') }}"
                                            data-username="{{ $portal === 'client' ? e($user->username ?? '') : '' }}"
                                            data-name="{{ $portal === 'client' ? e($user->name ?? '') : '' }}"
                                            data-email="{{ $portal === 'client' ? e($user->email ?? '') : '' }}"
                                            data-department="{{ $portal === 'client' ? e($user->department ?? '') : '' }}"
                                            data-access-role="{{ $portal === 'client' ? e($user->access_role ?? 'department_employee') : '' }}"
                                            data-access-permissions="{{ $portal === 'client' ? e(json_encode($user->access_permissions ?? [])) : '[]' }}"
                                        >
                                            <td class="px-2 py-4">{{ $portal === 'admin' ? 'CL-' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT) : 'EMP-' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT) }}</td>
                                            <td class="px-2 py-4">{{ $portal === 'admin' ? $user->company_name : ($user->username ?? 'employee') }}</td>
                                            <td class="px-2 py-4">{{ $portal === 'admin' ? $user->admin_name : ($user->name ?? $user->full_name ?? 'Employee') }}</td>
                                            <td class="px-2 py-4">{{ $portal === 'admin' ? ($user->adminUser?->username ?? 'Not generated') : ($user->email ?? 'employee@company.com') }}</td>
                                            <td class="px-2 py-4">{{ $portal === 'admin' ? ($user->industry ?? 'ERP Client') : ($user->department ?? 'General') }}</td>
                                            @if ($portal === 'client')
                                                <td class="px-2 py-4">{{ $accessRoleLabels[$user->access_role ?? 'department_employee'] ?? 'Department Employee' }}</td>
                                            @endif
                                            <td class="px-2 py-4">{{ $user->status ?? 'Active' }}</td>
                                            <td class="px-2 py-4 text-center">
                                                @if ($active === 'pending-approvals' && $portal === 'client')
                                                    <form method="POST" action="{{ route('client.itsm.pending-approvals.approve', ['employee' => $user->id]) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded-md bg-[#346DCB] px-4 py-2 font-semibold text-white hover:bg-[#2554a3]">Approve</button>
                                                    </form>
                                                @elseif ($active === 'pending-approvals')
                                                    <span class="text-sm text-slate-500">Awaiting review</span>
                                                @elseif ($portal === 'client')
                                                    <button type="button" class="edit-employee-button inline-flex items-center gap-2 rounded-md bg-[#132B52] px-4 py-2 font-semibold text-white hover:bg-[#2554a3]" aria-label="Edit {{ $user->name ?? 'employee' }}">
                                                        <span aria-hidden="true">&#9998;</span> Edit
                                                    </button>
                                                @else
                                                    <input type="checkbox" class="row-checkbox h-5 w-5 accent-[#346DCB]">
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $portal === 'client' ? 8 : 7 }}" class="px-2 py-12 text-center text-slate-500">No {{ $entityLabelPlural }} found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-6">
            <div class="w-full max-w-4xl rounded-2xl bg-white p-8 text-slate-950 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Edit {{ ucfirst($entityLabel) }}</h2>
                    <button type="button" id="closeEditModal" class="text-2xl font-bold text-slate-500 hover:text-slate-950">&times;</button>
                </div>

                <form id="editForm" method="POST" action="#" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    @if ($portal === 'admin')
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Company Name</span>
                            <input type="text" name="company_name" id="edit_company_name" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Admin Name</span>
                            <input type="text" name="admin_name" id="edit_admin_name" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Company Email</span>
                            <input type="email" name="company_email" id="edit_company_email" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Phone No.</span>
                            <input type="text" name="phone_no" id="edit_phone_no" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Industry</span>
                            <input type="text" name="industry" id="edit_industry" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Set Client System Admin Password</span>
                            <input type="password" name="admin_password" id="edit_admin_password" class="h-11 w-full rounded border border-slate-300 px-3" autocomplete="new-password">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Confirm Password</span>
                            <input type="password" name="admin_password_confirmation" id="edit_admin_password_confirmation" class="h-11 w-full rounded border border-slate-300 px-3" autocomplete="new-password">
                        </label>
                    @else
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Username</span>
                            <input type="text" name="username" id="edit_username" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Full Name</span>
                            <input type="text" name="name" id="edit_name" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Email</span>
                            <input type="email" name="email" id="edit_email" class="h-11 w-full rounded border border-slate-300 px-3">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">Department</span>
                            <input type="text" name="department" id="edit_department" readonly class="h-11 w-full rounded border border-slate-300 bg-slate-100 px-3 text-slate-600">
                            <span class="mt-1 block text-xs text-slate-500">Managed in HR; it determines the relevant access controls below.</span>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">ERP access role</span>
                            <select name="access_role" id="edit_access_role" class="h-11 w-full rounded border border-slate-300 px-3">
                                @foreach ($accessRoleLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <fieldset class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                            <legend class="px-2 text-sm font-semibold">Operational permissions</legend>
                            <p class="mb-4 text-xs text-slate-500">Permissions are shown only for this employee's assigned department. They control actions inside its module and do not change the HR title or department.</p>
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($permissionGroups as $module => $group)
                                    <div data-permission-module="{{ $module }}" hidden>
                                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#346DCB]">{{ $group['label'] }}</p>
                                        <div class="space-y-2">
                                            @foreach ($group['permissions'] as $value => $label)
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="access_permissions[]" value="{{ $value }}" data-access-permission class="h-4 w-4 rounded border-slate-300 accent-[#346DCB]">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p id="accessPermissionEmpty" class="hidden text-sm text-slate-500">No module permissions are configured for this department yet.</p>
                        </fieldset>
                    @endif

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold">Status</span>
                        <select name="status" id="edit_status" class="h-11 w-full rounded border border-slate-300 px-3">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Pending">Pending</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </label>

                    <div class="flex justify-end gap-3 pt-5 md:col-span-2">
                        <button type="button" id="cancelEditModal" class="rounded-md border border-slate-300 px-5 py-2 font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="rounded-md bg-[#346DCB] px-5 py-2 font-semibold text-white hover:bg-[#2554a3]">Save changes</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($portal === 'admin')
            <div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-6">
                <div class="w-full max-w-xl rounded-2xl bg-white p-8 text-slate-950 shadow-2xl">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-red-700">Delete client</h2>
                        <button type="button" id="closeDeleteModal" class="text-2xl font-bold text-slate-500 hover:text-slate-950">&times;</button>
                    </div>

                    <p class="mb-4 text-base text-slate-700">
                        This will delete <strong id="deleteCompanyName"></strong>, its generated system admin login, and its ITSM employee table.
                    </p>
                    <p class="mb-5 text-base text-slate-700">
                        To confirm, type the system admin name:
                        <strong id="deleteAdminName"></strong>
                    </p>

                    <form id="deleteForm" method="POST" action="#" class="space-y-5">
                        @csrf
                        @method('DELETE')

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold">System admin name</span>
                            <input type="text" name="admin_name_confirmation" id="delete_admin_name_confirmation" class="h-11 w-full rounded border border-slate-300 px-3" autocomplete="off">
                        </label>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" id="cancelDeleteModal" class="rounded-md border border-slate-300 px-5 py-2 font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                            <button type="submit" id="confirmDeleteButton" disabled class="rounded-md bg-red-600 px-5 py-2 font-semibold text-white opacity-50 transition enabled:opacity-100 enabled:hover:bg-red-700">Delete client</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>

    <script>
        const portal = @json($portal);
        const updateUrlTemplate = @json($portal === 'admin'
            ? route('admin.itsm.clients.update', ['company' => '__ID__'])
            : route('client.itsm.employees.update', ['employee' => '__ID__']));
        const deleteUrlTemplate = @json($portal === 'admin'
            ? route('admin.itsm.clients.destroy', ['company' => '__ID__'])
            : null);
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const searchInput = document.getElementById('tableSearch');
        const tableBody = document.querySelector('#usersTable tbody');
        const editSelectedButton = document.getElementById('editSelectedButton');
        const deleteSelectedButton = document.getElementById('deleteSelectedButton');
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editForm');
        const deleteModal = document.getElementById('deleteModal');
        const deleteForm = document.getElementById('deleteForm');
        const deleteAdminInput = document.getElementById('delete_admin_name_confirmation');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');
        let expectedDeleteAdminName = '';
        const getRowCheckboxes = () => document.querySelectorAll('.row-checkbox');
        const checkedRows = () => Array.from(getRowCheckboxes())
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.closest('tr'));

        function updateEditButtonState() {
            if (!editSelectedButton) return;
            const hasOneSelected = checkedRows().length === 1;
            editSelectedButton.disabled = !hasOneSelected;
            if (deleteSelectedButton) deleteSelectedButton.disabled = !hasOneSelected;
        }

        function setField(id, value) {
            const field = document.getElementById(id);
            if (field) field.value = value ?? '';
        }

        const permissionModuleByDepartment = {
            'human resources': 'hr',
            'hr': 'hr',
            'human resource': 'hr',
            'inventory management': 'inventory',
            'inventory': 'inventory',
            'procurement management': 'procurement',
            'procurement': 'procurement',
            'order management': 'order_fulfillment',
            'order fulfillment': 'order_fulfillment',
            'order fulfillment & operations': 'order_fulfillment',
            'production management': 'manufacturing',
            'manufacturing': 'manufacturing',
            'production': 'manufacturing',
            'finance': 'finance',
            'finance and accounting': 'finance',
            'finance & accounting': 'finance',
            'e-commerce': 'ecommerce',
            'ecommerce': 'ecommerce',
            'e-commerce and crm': 'ecommerce',
            'business intelligence': 'bi',
            'business intelligence and analytics': 'bi',
            'bi': 'bi',
        };

        function showPermissionsForDepartment(department, selectedPermissions) {
            const module = permissionModuleByDepartment[(department || '').trim().toLowerCase()] || '';
            let visibleCount = 0;

            document.querySelectorAll('[data-permission-module]').forEach((group) => {
                const visible = group.dataset.permissionModule === module;
                group.hidden = !visible;
                if (visible) visibleCount += 1;

                group.querySelectorAll('[data-access-permission]').forEach((checkbox) => {
                    checkbox.disabled = !visible;
                    checkbox.checked = visible && selectedPermissions.includes(checkbox.value);
                });
            });

            document.getElementById('accessPermissionEmpty')?.classList.toggle('hidden', visibleCount > 0);
        }

        function openEditModal(row) {
            const id = row.dataset.rowId;
            editForm.action = updateUrlTemplate.replace('__ID__', id);

            if (portal === 'admin') {
                setField('edit_company_name', row.dataset.companyName);
                setField('edit_admin_name', row.dataset.adminName);
                setField('edit_company_email', row.dataset.companyEmail);
                setField('edit_phone_no', row.dataset.phoneNo);
                setField('edit_industry', row.dataset.industry);
                setField('edit_admin_password', '');
                setField('edit_admin_password_confirmation', '');
            } else {
                setField('edit_username', row.dataset.username);
                setField('edit_name', row.dataset.name);
                setField('edit_email', row.dataset.email);
                setField('edit_department', row.dataset.department);
                setField('edit_access_role', row.dataset.accessRole || 'department_employee');
                let permissions = [];
                try { permissions = JSON.parse(row.dataset.accessPermissions || '[]'); } catch (_) { permissions = []; }
                showPermissionsForDepartment(row.dataset.department, permissions);
            }

            setField('edit_status', row.dataset.status || 'Active');
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
        }

        function openDeleteModal(row) {
            if (!deleteModal || !deleteForm) return;

            const id = row.dataset.rowId;
            expectedDeleteAdminName = row.dataset.adminName || '';

            deleteForm.action = deleteUrlTemplate.replace('__ID__', id);
            document.getElementById('deleteCompanyName').textContent = row.dataset.companyName || 'this client';
            document.getElementById('deleteAdminName').textContent = expectedDeleteAdminName;
            if (deleteAdminInput) deleteAdminInput.value = '';
            if (confirmDeleteButton) confirmDeleteButton.disabled = true;

            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
            deleteAdminInput?.focus();
        }

        function closeEditModal() {
            editModal.classList.add('hidden');
            editModal.classList.remove('flex');
        }

        function closeDeleteModal() {
            deleteModal?.classList.add('hidden');
            deleteModal?.classList.remove('flex');
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                Array.from(getRowCheckboxes())
                    .filter((checkbox) => !checkbox.closest('tr').classList.contains('hidden'))
                    .forEach((checkbox) => checkbox.checked = this.checked);
                updateEditButtonState();
            });
        }

        getRowCheckboxes().forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    getRowCheckboxes().forEach((other) => {
                        if (other !== checkbox) other.checked = false;
                    });
                }
                updateEditButtonState();
            });
        });

        editSelectedButton?.addEventListener('click', () => {
            const row = checkedRows()[0];
            if (row) openEditModal(row);
        });

        document.querySelectorAll('.edit-employee-button').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('tr');
                if (row) openEditModal(row);
            });
        });

        deleteSelectedButton?.addEventListener('click', () => {
            const row = checkedRows()[0];
            if (row) openDeleteModal(row);
        });

        document.getElementById('closeEditModal')?.addEventListener('click', closeEditModal);
        document.getElementById('cancelEditModal')?.addEventListener('click', closeEditModal);
        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) closeEditModal();
        });

        document.getElementById('closeDeleteModal')?.addEventListener('click', closeDeleteModal);
        document.getElementById('cancelDeleteModal')?.addEventListener('click', closeDeleteModal);
        deleteModal?.addEventListener('click', (event) => {
            if (event.target === deleteModal) closeDeleteModal();
        });
        deleteAdminInput?.addEventListener('input', () => {
            if (confirmDeleteButton) {
                confirmDeleteButton.disabled = deleteAdminInput.value !== expectedDeleteAdminName;
            }
        });

        document.getElementById('addEntityButton')?.addEventListener('click', () => {
            if (portal === 'admin') {
                window.location.href = @json(route('admin.itsm.registration'));
                return;
            }
        });

        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function (event) {
                const query = event.target.value.toLowerCase();

                tableBody.querySelectorAll('tr').forEach((row) => {
                    const rowText = Array.from(row.querySelectorAll('td'))
                        .map((cell) => cell.textContent.toLowerCase())
                        .join(' ');

                    row.classList.toggle('hidden', !rowText.includes(query));
                });

                updateEditButtonState();
            });
        }

        document.querySelectorAll('#usersTable th.sortable').forEach((header) => {
            header.addEventListener('click', () => {
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                const index = Array.from(header.parentNode.children).indexOf(header);
                const direction = header.dataset.direction === 'asc' ? -1 : 1;

                document.querySelectorAll('#usersTable th.sortable').forEach((item) => item.dataset.direction = '');
                header.dataset.direction = direction === 1 ? 'asc' : 'desc';

                rows.sort((a, b) => a.children[index].textContent.trim().localeCompare(
                    b.children[index].textContent.trim(),
                    undefined,
                    { numeric: true }
                ) * direction);

                tableBody.append(...rows);
            });
        });
    </script>
</body>
</html>
