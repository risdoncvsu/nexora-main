<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-[#18386d] font-sans text-white">
    @include('partials.navbar')

    <main class="mx-auto w-[96%] max-w-[1800px] py-7">
        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-300/25 bg-emerald-500/15 px-5 py-4 text-emerald-50">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-2xl border border-rose-300/25 bg-rose-500/15 px-5 py-4 text-rose-50">{{ session('error') }}</div>
        @endif

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-sky-200">Employee management</p>
                <h1 class="mt-2 text-3xl font-bold">Leave Requests</h1>
                <p class="mt-2 text-sm text-slate-300">Review pending requests for your client only.</p>
            </div>
            <a href="{{ route('hr.reports-analytics.leave') }}" class="rounded-xl border border-white/15 px-4 py-2.5 text-sm font-semibold text-slate-100 no-underline transition hover:bg-white/10">Reviewed leave records</a>
        </div>

        <section class="mb-6 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-white/10 bg-[#0B1E3D] p-5 shadow-lg">
                <p class="text-sm text-slate-400">All submitted requests</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalSubmitted) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-300/15 bg-[#0B1E3D] p-5 shadow-lg">
                <p class="text-sm text-amber-100/80">Awaiting review</p>
                <p class="mt-2 text-3xl font-bold text-amber-200">{{ number_format($pendingCount) }}</p>
            </article>
            <article class="rounded-2xl border border-sky-300/15 bg-[#0B1E3D] p-5 shadow-lg">
                <p class="text-sm text-sky-100/80">Submitted today</p>
                <p class="mt-2 text-3xl font-bold text-sky-200">{{ number_format($submittedToday) }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#0B1E3D] shadow-lg">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
                <h2 class="text-lg font-semibold">Pending requests</h2>
                <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-100">{{ $pendingCount }} pending</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-[#132B52] text-xs uppercase tracking-wide text-slate-300">
                        <tr>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4">Leave type</th>
                            <th class="px-6 py-4">Leave dates</th>
                            <th class="px-6 py-4">Days</th>
                            <th class="px-6 py-4">Submitted</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 text-slate-200">
                        @forelse ($leaveRequests as $leaveRequest)
                            @php
                                $employee = $leaveRequest->employee;
                            @endphp
                            <tr class="transition hover:bg-white/[0.04]">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-white">{{ $employee ? trim($employee->first_name.' '.$employee->last_name) : 'Former employee' }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $leaveRequest->reference_id ?: 'Pending reference' }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-300">{{ $employee?->department ?: '—' }}</td>
                                <td class="px-6 py-4">{{ ucfirst($leaveRequest->type) }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ $leaveRequest->from_date?->format('M d, Y') }} – {{ $leaveRequest->to_date?->format('M d, Y') }}</td>
                                <td class="px-6 py-4">{{ rtrim(rtrim((string) $leaveRequest->total_days, '0'), '.') }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ $leaveRequest->created_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('hr.leave-requests.show', $leaveRequest) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#2D7EFF] px-4 py-2 text-xs font-semibold text-white no-underline transition hover:bg-[#4D95FF]">
                                        <i class="fa-solid fa-clipboard-check"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center text-slate-400">There are no leave requests waiting for review.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leaveRequests->hasPages())
                @include('partials.list-pagination', ['paginator' => $leaveRequests, 'label' => 'leave requests'])
            @endif
        </section>
    </main>
</body>
</html>
