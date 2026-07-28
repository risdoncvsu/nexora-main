<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Access Control</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    @php
        $navItems = [
            ['label' => 'Employee Management', 'route' => route('client.itsm.employees'), 'key' => 'employees'],
            ['label' => 'Access Control', 'route' => route('client.itsm.access-control.index'), 'key' => 'access-control'],
            ['label' => 'Service Desk', 'route' => route('client.itsm.service-desk'), 'key' => 'service-desk'],
            ['label' => 'Compliance Tracking', 'route' => route('client.itsm.compliance'), 'key' => 'compliance'],
            ['label' => 'Risk Management', 'route' => route('client.itsm.risk'), 'key' => 'risk'],
        ];
    @endphp

    <div class="flex min-h-screen flex-col">
        <x-itsm-header :home-route="route('client.itsm.employees')" active="access-control" :nav-items="$navItems" />

        <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-10">
            <section class="rounded-[2rem] bg-gradient-to-br from-[#132B52] via-[#1b467f] to-[#346DCB] p-8 shadow-xl md:p-10">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-blue-200">Client ITSM administration</p>
                <h1 class="mt-3 text-3xl font-bold md:text-5xl">Employee access control</h1>
                <p class="mt-4 max-w-3xl text-blue-100">Assign an ERP access role and the modules each employee can enter. HR job titles remain unchanged and are used only as a starting recommendation.</p>
            </section>

            @if (session('success'))
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 font-semibold text-red-800">{{ $errors->first() }}</div>
            @endif

            <section class="mt-8 rounded-[1.75rem] bg-white p-6 text-slate-950 shadow-sm ring-1 ring-slate-200 md:p-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-[#346DCB]">{{ $company->company_name }}</p>
                        <h2 class="mt-1 text-2xl font-bold">Roles and module access</h2>
                    </div>
                    <p class="max-w-xl text-sm text-slate-600">A Client System Admin account is managed separately. This page manages approved HR employee accounts belonging to this client only.</p>
                </div>

                <div class="mt-7 space-y-4">
                    @forelse ($employees as $employee)
                        <form method="POST" action="{{ route('client.itsm.access-control.update', $employee->id) }}" class="rounded-2xl border border-slate-200 p-5 transition hover:border-[#346DCB]/50 hover:shadow-sm">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-5 lg:grid-cols-[1.1fr_0.8fr_1.6fr_auto] lg:items-end">
                                <div>
                                    <p class="font-bold text-slate-950">{{ $employee->name ?: 'Employee' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $employee->position ?: 'No HR title' }} · {{ $employee->department ?: 'No department' }}</p>
                                    <p class="mt-1 text-xs font-semibold {{ $employee->status === 'Active' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $employee->status }}</p>
                                </div>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">ERP access role</span>
                                    <select name="access_role" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold focus:border-[#346DCB] focus:outline-none">
                                        @foreach ($roles as $value => $label)
                                            <option value="{{ $value }}" @selected($employee->access_role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <fieldset>
                                    <legend class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Enabled modules</legend>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 md:grid-cols-3">
                                        @foreach ($modules as $value => $label)
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="module_access[]" value="{{ $value }}" @checked(in_array($value, $employee->module_access ?? [], true)) class="h-4 w-4 rounded border-slate-300 accent-[#346DCB]">
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <button type="submit" class="h-11 rounded-full bg-[#346DCB] px-5 text-sm font-bold text-white transition hover:bg-[#2554a3]">Save access</button>
                            </div>
                        </form>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center text-slate-500">No HR employees are available for this client yet.</div>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
</body>
</html>
