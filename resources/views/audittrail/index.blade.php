@php
    $isRoot = $portal === 'admin';
    $indexRoute = $isRoot ? 'admin.itsm.audit-trail' : 'client.itsm.audit-trail';
    $exportRoute = $isRoot ? 'admin.itsm.audit-trail.export' : 'client.itsm.audit-trail.export';
    $navItems = $isRoot
        ? [
            ['label' => 'Registration', 'route' => route('admin.itsm.registration'), 'key' => 'registration'],
            ['label' => 'Client Management', 'route' => route('admin.itsm.clients'), 'key' => 'clients'],
            ['label' => 'Service Desk', 'route' => route('admin.itsm.service-desk'), 'key' => 'service-desk'],
            ['label' => 'Audit Trail', 'route' => route($indexRoute), 'key' => 'audit-trail'],
        ]
        : [
            ['label' => 'Employee Management', 'route' => route('client.itsm.employees'), 'key' => 'employees'],
            ['label' => 'Service Desk', 'route' => route('client.itsm.service-desk'), 'key' => 'service-desk'],
            ['label' => 'Compliance Tracking', 'route' => route('client.itsm.compliance'), 'key' => 'compliance'],
            ['label' => 'Risk Management', 'route' => route('client.itsm.risk'), 'key' => 'risk'],
            ['label' => 'Audit Trail', 'route' => route($indexRoute), 'key' => 'audit-trail'],
        ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Audit Trail</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header :home-route="$isRoot ? route('admin.itsm.registration') : route('client.itsm.employees')" active="audit-trail" :nav-items="$navItems" />

        <main class="relative flex-1 overflow-hidden p-4 sm:p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">

            <div class="relative z-10 flex flex-col gap-6">
                <section class="flex flex-col gap-4 rounded-[2rem] bg-white/90 px-5 py-5 text-slate-950 sm:px-8 sm:py-7 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-[#346DCB]">{{ $isRoot ? 'Root administration' : 'Client administration' }}</p>
                        <h1 class="mt-1 text-3xl font-bold sm:text-4xl">Audit Trail</h1>
                        <p class="mt-1 text-sm text-slate-600">Search, inspect, and export recorded ERP activity.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <form method="GET" action="{{ route($indexRoute) }}" class="flex min-w-0 flex-1 items-center rounded-full bg-slate-200 px-4 py-3 sm:w-72 sm:flex-none">
                            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search actions or modules" aria-label="Search audit logs" class="min-w-0 flex-1 border-0 bg-transparent text-sm text-slate-900 outline-none">
                        </form>
                        <a href="{{ route($exportRoute, ['search' => request('search')]) }}" class="rounded-full bg-[#346DCB] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#2554a3]">Export CSV</a>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-5 text-slate-950 shadow-xl sm:p-8">
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold">System Logs &amp; Activities</h2>
                            <p class="text-sm text-slate-500">{{ $isRoot ? 'All client activity' : 'Activity for your company only' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $logs->total() }} records</span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="min-w-full border-collapse text-left">
                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-4">Log ID</th>
                                    @if ($isRoot)<th class="px-4 py-4">Client</th>@endif
                                    <th class="px-4 py-4">Actor</th>
                                    <th class="px-4 py-4">Department</th>
                                    <th class="px-4 py-4">Action</th>
                                    <th class="px-4 py-4">Date &amp; Time</th>
                                    <th class="px-4 py-4 text-center">Details</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-slate-700">
                                @forelse ($logs as $log)
                                    @php
                                        $detailPayload = json_encode([
                                            'id' => 'LOG-'.str_pad((string) $log->id, 6, '0', STR_PAD_LEFT),
                                            'actor' => $log->actor,
                                            'department' => $log->department,
                                            'event' => $log->event,
                                            'module' => $log->module,
                                            'timestamp' => $log->created_at?->format('M d, Y H:i'),
                                            'details' => $log->details,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                                    @endphp
                                    <tr class="border-t border-slate-100 transition hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-900">LOG-{{ str_pad((string) $log->id, 6, '0', STR_PAD_LEFT) }}</td>
                                        @if ($isRoot)<td class="px-4 py-4">CL-{{ str_pad((string) $log->client_id, 5, '0', STR_PAD_LEFT) }}</td>@endif
                                        <td class="px-4 py-4">{{ $log->actor }}</td>
                                        <td class="px-4 py-4">{{ $log->department }}</td>
                                        <td class="px-4 py-4"><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-[#346DCB]">{{ str_replace('.', ' ', $log->event) }}</span></td>
                                        <td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ $log->created_at?->format('M d, Y H:i') }}</td>
                                        <td class="px-4 py-4 text-center"><button type="button" class="audit-details rounded-full bg-[#EBF1FA] px-4 py-2 text-xs font-bold text-[#346DCB] transition hover:bg-[#346DCB] hover:text-white" data-log="{{ $detailPayload }}">Details</button></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $isRoot ? 7 : 6 }}" class="px-4 py-12 text-center text-slate-400">No audit records match this search.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">{{ $logs->links() }}</div>
                </section>
            </div>
        </main>
    </div>

    <div id="auditDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm">
        <div class="max-h-full w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 text-slate-950 shadow-2xl sm:p-8">
            <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4"><h3 class="text-xl font-bold">Audit Record Details</h3><button type="button" id="closeAuditDetails" class="text-2xl font-bold text-slate-400 hover:text-slate-950">&times;</button></div>
            <dl id="auditDetailList" class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2"></dl>
        </div>
    </div>

    <script>
        const auditModal = document.getElementById('auditDetailsModal');
        const auditDetailList = document.getElementById('auditDetailList');
        const escapeHtml = (value) => String(value || 'Not recorded').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
        const label = (name, value) => `<div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">${escapeHtml(name)}</dt><dd class="mt-1 break-words font-medium text-slate-800">${escapeHtml(value)}</dd></div>`;
        document.querySelectorAll('.audit-details').forEach((button) => button.addEventListener('click', () => {
            const log = JSON.parse(button.dataset.log || '{}');
            auditDetailList.innerHTML = label('Log ID', log.id) + label('Actor', log.actor) + label('Department', log.department) + label('Action', log.event) + label('Module', log.module) + label('Date & time', log.timestamp) + `<div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Request details</dt><dd class="mt-1 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-3 font-mono text-xs text-slate-700">${escapeHtml(JSON.stringify(log.details || {}, null, 2))}</dd></div>`;
            auditModal.classList.remove('hidden'); auditModal.classList.add('flex');
        }));
        const closeAuditDetails = () => { auditModal.classList.add('hidden'); auditModal.classList.remove('flex'); };
        document.getElementById('closeAuditDetails').addEventListener('click', closeAuditDetails);
        auditModal.addEventListener('click', (event) => { if (event.target === auditModal) closeAuditDetails(); });
    </script>
</body>
</html>
