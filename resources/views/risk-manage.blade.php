<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Manage Risk</title>
    <link rel="icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header
            :home-route="route('client.itsm.employees')"
            active="risk"
            :nav-items="[
                ['label' => 'Employee Management', 'route' => route('client.itsm.employees'), 'key' => 'employees'],
                ['label' => 'Service Desk', 'route' => route('client.itsm.service-desk'), 'key' => 'service-desk'],
                ['label' => 'Compliance Tracking', 'route' => route('client.itsm.compliance'), 'key' => 'compliance'],
                ['label' => 'Risk Management', 'route' => route('client.itsm.risk'), 'key' => 'risk'],
                ['label' => 'Audit Trail', 'route' => route('client.itsm.audit-trail'), 'key' => 'audit-trail'],
            ]"
        />

        <main class="relative flex-1 overflow-hidden p-4 sm:p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <div class="relative z-10 grid min-h-[calc(100vh-10rem)] grid-cols-1 gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <x-risk-sidebar section="register" />

                <section class="min-w-0 space-y-6">
                    <div class="rounded-[1.875rem] bg-white/90 px-5 py-5 text-slate-950 shadow-sm sm:px-8 sm:py-6"><p class="text-xs font-bold uppercase tracking-wider text-[#346DCB]">Risk Management</p><h1 class="mt-1 text-3xl font-bold sm:text-4xl">Manage Risk</h1><p class="mt-2 text-sm text-slate-600">Update the owner, mitigation progress, and review schedule for this record.</p></div>
                    <section class="rounded-[1.875rem] bg-white p-5 text-slate-950 shadow-xl sm:p-8">
                        <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5"><div><h2 class="text-2xl font-bold">{{ $risk->title }}</h2><p class="mt-1 text-sm font-semibold text-slate-500">{{ $risk->category ?: 'Uncategorised' }}</p></div><a href="{{ route('client.itsm.risk') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">&larr; Risk Register</a></div>
                        @if ($errors->any())<div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif
                        <form method="POST" action="{{ route('client.itsm.risk.update', $risk) }}" class="grid gap-5 lg:grid-cols-2">@csrf @method('PATCH')
                            <label class="text-sm font-semibold">Status<select name="status" class="mt-1.5 h-11 w-full rounded border border-slate-300 px-3 font-normal"><option value="Unmitigated" @selected($risk->status === 'Unmitigated')>Unmitigated</option><option value="In Progress" @selected($risk->status === 'In Progress')>In Progress</option><option value="Mitigated" @selected($risk->status === 'Mitigated')>Mitigated</option></select></label>
                            <label class="text-sm font-semibold">Risk owner<input name="owner" value="{{ old('owner', $risk->owner) }}" class="mt-1.5 h-11 w-full rounded border border-slate-300 px-3 font-normal"></label>
                            <label class="text-sm font-semibold lg:col-span-2">Review date<input type="date" name="review_date" value="{{ old('review_date', optional($risk->review_date)->format('Y-m-d')) }}" class="mt-1.5 h-11 w-full rounded border border-slate-300 px-3 font-normal"></label>
                            <label class="text-sm font-semibold lg:col-span-2">Mitigation plan<textarea name="mitigation_plan" rows="7" class="mt-1.5 w-full rounded border border-slate-300 px-3 py-2 font-normal">{{ old('mitigation_plan', $risk->mitigation_plan) }}</textarea></label>
                            <div class="lg:col-span-2"><button class="rounded-full bg-[#346DCB] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#2554a3]">Save changes</button></div>
                        </form>
                    </section>
                </section>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
