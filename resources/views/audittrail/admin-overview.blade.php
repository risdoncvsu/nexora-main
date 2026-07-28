<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Audit Troubleshooting</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header
            :home-route="route('admin.itsm.registration')"
            active="audit-trail"
            :nav-items="[
                ['label' => 'Registration', 'route' => route('admin.itsm.registration'), 'key' => 'registration'],
                ['label' => 'Client Management', 'route' => route('admin.itsm.clients'), 'key' => 'clients'],
                ['label' => 'Service Desk', 'route' => route('admin.itsm.service-desk'), 'key' => 'service-desk'],
                ['label' => 'Audit Trail', 'route' => route('admin.itsm.audit-trail'), 'key' => 'audit-trail'],
            ]"
        />

        <main class="relative flex-1 overflow-hidden p-4 sm:p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <div class="relative z-10 space-y-6">
                <section class="rounded-[1.875rem] bg-white/90 px-5 py-5 text-slate-950 shadow-sm sm:px-8 sm:py-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-[#346DCB]">Root administration</p>
                    <h1 class="mt-1 text-3xl font-bold sm:text-4xl">Audit Trail</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Choose a client workspace to troubleshoot its activity. The global list below is deliberately limited to errors, keeping routine activity out of the root-admin view.</p>
                </section>

                <section class="rounded-[1.875rem] bg-white p-5 text-slate-950 shadow-xl sm:p-8">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                        <div><h2 class="text-xl font-bold">Client audit workspaces</h2><p class="mt-1 text-sm text-slate-500">Select a company to view its complete activity history.</p></div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $companyCards->count() }} clients</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @forelse ($companyCards as $company)
                            <a href="{{ route('admin.itsm.audit-trail', ['client_id' => $company->id]) }}" class="group rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#346DCB] hover:shadow-md">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><p class="truncate text-base font-bold text-slate-950">{{ $company->name }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">CL-{{ str_pad((string) $company->id, 5, '0', STR_PAD_LEFT) }} &middot; {{ $company->status ?: 'Active' }}</p></div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $company->error_count > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $company->error_count }} {{ $company->error_count === 1 ? 'error' : 'errors' }}</span>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                                    <div><p class="text-xs font-medium text-slate-500">Activity</p><p class="mt-1 text-xl font-extrabold text-[#132B52]">{{ number_format($company->activity_count) }}</p></div>
                                    <div><p class="text-xs font-medium text-slate-500">Latest record</p><p class="mt-1 truncate text-xs font-semibold text-slate-700">{{ $company->last_activity?->format('M j, H:i T') ?: 'No records' }}</p></div>
                                </div>
                                <p class="mt-4 text-sm font-bold text-[#346DCB]">Inspect trail &rarr;</p>
                            </a>
                        @empty
                            <p class="col-span-full py-10 text-center text-sm text-slate-500">No client companies are available yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-[1.875rem] bg-white p-5 text-slate-950 shadow-xl sm:p-8">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-red-600">All companies</p><h2 class="mt-1 text-xl font-bold">Important errors</h2><p class="mt-1 text-sm text-slate-500">403, 404, 500, and recorded failed actions across client systems.</p></div><span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ $errors->count() }} latest errors</span></div>
                    <div class="overflow-x-auto rounded-xl border border-slate-100"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Module</th><th class="px-4 py-3">Issue</th><th class="px-4 py-3">HTTP</th><th class="px-4 py-3">Time</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($errors as $error)<tr class="hover:bg-slate-50"><td class="px-4 py-4 font-semibold text-slate-900">{{ $error->company_name }}</td><td class="px-4 py-4">{{ $error->department }}</td><td class="px-4 py-4">{{ str_replace('.', ' ', $error->event) }}</td><td class="px-4 py-4 font-bold text-red-700">{{ $error->http_status ?? '—' }}</td><td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ $error->created_at?->format('M j, Y H:i T') }}</td><td class="px-4 py-4"><a href="{{ route('admin.itsm.audit-trail', ['client_id' => $error->client_id, 'category' => 'errors']) }}" class="font-bold text-[#346DCB] hover:underline">Inspect client</a></td></tr>@empty<tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No cross-client errors have been recorded.</td></tr>@endforelse</tbody></table></div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
