<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Leave Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-[#18386d] font-sans text-white">
    @include('partials.navbar')

    @php
        $employee = $leaveRequest->employee;
        $statusClass = match ($leaveRequest->status) {
            'approved' => 'bg-emerald-500/20 text-emerald-100',
            'rejected' => 'bg-rose-500/20 text-rose-100',
            default => 'bg-amber-500/20 text-amber-100',
        };
    @endphp

    <main class="mx-auto max-w-5xl px-5 py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('hr.leave-management.index') }}" class="text-sm text-sky-200 no-underline hover:text-white"><i class="fa-solid fa-arrow-left mr-2"></i>Back to leave requests</a>
                <p class="mt-5 text-xs uppercase tracking-[0.3em] text-sky-200">Leave request</p>
                <h1 class="mt-2 text-3xl font-bold">{{ $leaveRequest->reference_id ?: 'Pending reference' }}</h1>
            </div>
            <span class="rounded-full px-4 py-2 text-sm font-bold {{ $statusClass }}">{{ strtoupper($leaveRequest->status) }}</span>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-300/25 bg-rose-500/15 px-5 py-4 text-rose-50">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-300/25 bg-rose-500/15 px-5 py-4 text-rose-50">
                <ul class="list-inside list-disc space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section class="rounded-3xl border border-white/10 bg-[#0B1E3D] p-6 shadow-lg">
                <h2 class="text-xl font-semibold">Request details</h2>

                <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Employee</dt>
                        <dd class="mt-1 font-semibold text-white">{{ $employee ? trim($employee->first_name.' '.$employee->last_name) : 'Former employee' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Department</dt>
                        <dd class="mt-1 text-slate-200">{{ $employee?->department ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Leave type</dt>
                        <dd class="mt-1 text-slate-200">{{ ucfirst($leaveRequest->type) }} leave</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Duration</dt>
                        <dd class="mt-1 text-slate-200">{{ rtrim(rtrim((string) $leaveRequest->total_days, '0'), '.') }} day(s)</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Leave dates</dt>
                        <dd class="mt-1 text-slate-200">{{ $leaveRequest->from_date?->format('F d, Y') }} to {{ $leaveRequest->to_date?->format('F d, Y') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Reason</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-2xl bg-white/[0.04] p-4 text-slate-200">{{ $leaveRequest->reason ?: 'No reason provided.' }}</dd>
                    </div>
                </dl>

                <div class="mt-7 border-t border-white/10 pt-6">
                    <h3 class="font-semibold">Supporting documents</h3>
                    @if (count($attachments))
                        <ul class="mt-3 space-y-2">
                            @foreach ($attachments as $attachment)
                                <li>
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-sky-200 no-underline hover:text-white">
                                        <i class="fa-solid fa-paperclip"></i>{{ basename($attachment) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-2 text-sm text-slate-400">No documents were attached.</p>
                    @endif
                </div>
            </section>

            <aside class="h-fit rounded-3xl border border-white/10 bg-[#0B1E3D] p-6 shadow-lg">
                @if ($leaveRequest->status === 'pending')
                    <h2 class="text-xl font-semibold">Review request</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Add an optional note for the employee, then approve or reject this request.</p>

                    <form method="POST" action="{{ route('hr.leave-requests.review', $leaveRequest) }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label for="remarks" class="mb-2 block text-sm font-medium text-slate-200">HR remarks</label>
                            <textarea id="remarks" name="remarks" rows="6" maxlength="1000" class="w-full resize-y rounded-xl border border-white/10 bg-[#132B52] px-4 py-3 text-white outline-none focus:border-sky-400" placeholder="Optional note to the employee...">{{ old('remarks') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" name="action" value="reject" class="rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-sm font-semibold text-rose-100 transition hover:bg-rose-500/25">Reject</button>
                            <button type="submit" name="action" value="approve" class="rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-400">Approve</button>
                        </div>
                    </form>
                @else
                    <h2 class="text-xl font-semibold">Review complete</h2>
                    <p class="mt-3 text-sm text-slate-300">This request was {{ $leaveRequest->status }} by {{ $leaveRequest->reviewed_by_name ?: 'HR' }}.</p>
                    @if ($leaveRequest->reviewed_by_position)
                        <p class="mt-1 text-sm text-slate-400">{{ $leaveRequest->reviewed_by_position }}</p>
                    @endif
                    @if ($leaveRequest->reviewed_at)
                        <p class="mt-4 text-xs text-slate-400">Reviewed {{ $leaveRequest->reviewed_at->format('M d, Y h:i A') }}</p>
                    @endif
                    @if ($leaveRequest->status_note)
                        <div class="mt-5 rounded-2xl bg-white/[0.04] p-4 text-sm text-slate-200">{{ $leaveRequest->status_note }}</div>
                    @endif
                @endif
            </aside>
        </div>
    </main>
</body>
</html>
