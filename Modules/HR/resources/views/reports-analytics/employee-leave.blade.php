<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leave Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#132C5B] font-sans text-white">
    <header class="border-b border-white/10 bg-[#132B52] px-5 py-4 shadow-lg">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" class="h-12 w-auto object-contain" alt="Nexora">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-sky-200">Employee self-service</p>
                    <h1 class="text-lg font-semibold">My Leave Requests</h1>
                </div>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('hr.employee.attendance') }}" class="rounded-xl border border-white/15 px-4 py-2 font-medium text-slate-100 no-underline transition hover:bg-white/10">My Attendance</a>
                <a href="{{ route('hr.employee.dashboard') }}" class="rounded-xl bg-[#2D7EFF] px-4 py-2 font-semibold text-white no-underline transition hover:bg-[#4D95FF]">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-300/25 bg-emerald-500/15 px-5 py-4 text-emerald-50">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-300/25 bg-rose-500/15 px-5 py-4 text-rose-50">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-300/25 bg-rose-500/15 px-5 py-4 text-rose-50">
                <p class="font-semibold">Please correct the following:</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
            <section class="rounded-3xl border border-white/10 bg-[#10233D] p-6 shadow-[0_20px_60px_rgba(0,0,0,.18)]">
                <div class="mb-6">
                    <p class="text-xs uppercase tracking-[0.28em] text-sky-200">Leave application</p>
                    <h2 class="mt-2 text-2xl font-bold">Submit a request</h2>
                    <p class="mt-2 text-sm text-slate-300">Your request will remain pending until it is reviewed by your HR manager.</p>
                </div>

                <form method="POST" action="{{ route('hr.employee.leave.submit') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <div>
                        <label for="type" class="mb-2 block text-sm font-medium text-slate-200">Leave type</label>
                        <select id="type" name="type" required class="w-full rounded-xl border border-white/10 bg-[#0B1E3D] px-4 py-3 text-white outline-none transition focus:border-sky-400">
                            <option value="" disabled @selected(! old('type'))>Select a leave type</option>
                            @foreach (['vacation' => 'Vacation', 'sick' => 'Sick', 'maternity' => 'Maternity', 'paternity' => 'Paternity', 'bereavement' => 'Bereavement', 'others' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }} leave</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="from_date" class="mb-2 block text-sm font-medium text-slate-200">Start date</label>
                            <input id="from_date" name="from_date" type="date" value="{{ old('from_date') }}" required class="w-full rounded-xl border border-white/10 bg-[#0B1E3D] px-4 py-3 text-white outline-none transition focus:border-sky-400">
                        </div>
                        <div>
                            <label for="to_date" class="mb-2 block text-sm font-medium text-slate-200">End date</label>
                            <input id="to_date" name="to_date" type="date" value="{{ old('to_date') }}" required class="w-full rounded-xl border border-white/10 bg-[#0B1E3D] px-4 py-3 text-white outline-none transition focus:border-sky-400">
                        </div>
                    </div>

                    <div>
                        <label for="attachments" class="mb-2 block text-sm font-medium text-slate-200">Supporting documents <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full rounded-xl border border-dashed border-white/20 bg-[#0B1E3D] px-4 py-3 text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-[#2D7EFF] file:px-3 file:py-2 file:font-medium file:text-white hover:file:bg-[#4D95FF]">
                        <p class="mt-2 text-xs text-slate-400">Up to five PDF, Word, JPG, or PNG files; 10 MB each.</p>
                    </div>

                    <div>
                        <label for="reason" class="mb-2 block text-sm font-medium text-slate-200">Reason <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="reason" name="reason" rows="5" maxlength="1000" class="w-full resize-y rounded-xl border border-white/10 bg-[#0B1E3D] px-4 py-3 text-white outline-none transition placeholder:text-slate-500 focus:border-sky-400" placeholder="Briefly describe your request...">{{ old('reason') }}</textarea>
                    </div>

                    <button type="submit" class="inline-flex items-center rounded-xl bg-[#2D7EFF] px-5 py-3 font-semibold text-white transition hover:bg-[#4D95FF]">Submit leave request</button>
                </form>
            </section>

            <aside class="rounded-3xl border border-white/10 bg-[#10233D] p-6 shadow-[0_20px_60px_rgba(0,0,0,.18)]">
                <p class="text-xs uppercase tracking-[0.28em] text-sky-200">Employee</p>
                <h2 class="mt-2 text-2xl font-bold">{{ trim($employee->first_name.' '.$employee->last_name) }}</h2>
                <p class="mt-1 text-sm text-slate-300">{{ $employee->department ?: 'Department not assigned' }}</p>

                <div class="mt-7 border-t border-white/10 pt-6">
                    <h3 class="font-semibold">Leave guidelines</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
                        <li>• Requests are reviewed by an HR manager.</li>
                        <li>• Vacation and sick leave require 5–15 calendar days.</li>
                        <li>• Maternity, paternity, and bereavement leave follow their configured limits.</li>
                        <li>• Attach supporting documents when they are relevant.</li>
                    </ul>
                </div>
            </aside>
        </div>

        <section class="mt-8 overflow-hidden rounded-3xl border border-white/10 bg-[#10233D] shadow-[0_20px_60px_rgba(0,0,0,.18)]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-6 py-5">
                <div>
                    <p class="text-xs uppercase tracking-[0.28em] text-sky-200">History</p>
                    <h2 class="mt-1 text-xl font-bold">My submitted requests</h2>
                </div>
                <span class="rounded-full bg-white/10 px-3 py-1 text-sm text-slate-200">{{ $leaveRequests->total() }} total</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-[#0B1E3D] text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-6 py-4">Reference</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Dates</th>
                            <th class="px-6 py-4">Days</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">HR remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 text-slate-200">
                        @forelse ($leaveRequests as $leaveRequest)
                            @php
                                $statusClass = match ($leaveRequest->status) {
                                    'approved' => 'bg-emerald-500/20 text-emerald-200',
                                    'rejected' => 'bg-rose-500/20 text-rose-200',
                                    default => 'bg-amber-500/20 text-amber-100',
                                };
                            @endphp
                            <tr class="hover:bg-white/[0.03]">
                                <td class="px-6 py-4 font-medium text-white">{{ $leaveRequest->reference_id ?: 'Pending reference' }}</td>
                                <td class="px-6 py-4">{{ ucfirst($leaveRequest->type) }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ $leaveRequest->from_date?->format('M d, Y') }} – {{ $leaveRequest->to_date?->format('M d, Y') }}</td>
                                <td class="px-6 py-4">{{ rtrim(rtrim((string) $leaveRequest->total_days, '0'), '.') }}</td>
                                <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ strtoupper($leaveRequest->status) }}</span></td>
                                <td class="max-w-xs px-6 py-4 text-slate-300">{{ $leaveRequest->status_note ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">You have not submitted any leave requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leaveRequests->hasPages())
                <div class="border-t border-white/10 px-6 py-4">{{ $leaveRequests->links() }}</div>
            @endif
        </section>
    </main>
</body>
</html>
