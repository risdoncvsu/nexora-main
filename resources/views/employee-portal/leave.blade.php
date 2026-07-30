<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Leave Requests</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B3A6B] font-sans text-slate-200">
    @include('employee-portal.partials.header', ['active' => 'leave'])

    <main class="mx-auto max-w-7xl px-5 py-7">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-300">Employee self-service</p>
            <h1 class="mt-1 text-2xl font-bold text-white">Leave Requests</h1>
            <p class="mt-1 text-sm text-slate-300">Submit a request and follow the HR review status here.</p>
        </div>

        @if (session('success'))<div class="mb-4 rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-200">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-lg border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm font-semibold text-rose-200">{{ $errors->first() }}</div>@endif

        <div class="grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
            <section class="rounded-lg border border-white/10 bg-[#0B1E3D] p-5 shadow-xl">
                <h2 class="text-lg font-bold text-white">New leave request</h2>
                <form method="POST" action="{{ route('employee.portal.leave.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                    @csrf
                    <label><span class="mb-1 block text-xs font-semibold text-slate-300">Leave type</span><select name="type" required class="h-10 w-full rounded border border-white/15 bg-white/5 px-3 text-sm text-white"><option value="">Select type</option>@foreach (['vacation' => 'Vacation', 'sick' => 'Sick', 'maternity' => 'Maternity', 'paternity' => 'Paternity', 'bereavement' => 'Bereavement', 'others' => 'Others'] as $value => $label)<option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label><span class="mb-1 block text-xs font-semibold text-slate-300">From</span><input type="date" name="from_date" value="{{ old('from_date') }}" required class="h-10 w-full rounded border border-white/15 bg-white/5 px-3 text-sm text-white"></label>
                        <label><span class="mb-1 block text-xs font-semibold text-slate-300">To</span><input type="date" name="to_date" value="{{ old('to_date') }}" required class="h-10 w-full rounded border border-white/15 bg-white/5 px-3 text-sm text-white"></label>
                    </div>
                    <label><span class="mb-1 block text-xs font-semibold text-slate-300">Reason</span><textarea name="reason" rows="4" class="w-full rounded border border-white/15 bg-white/5 px-3 py-2 text-sm text-white">{{ old('reason') }}</textarea></label>
                    <label><span class="mb-1 block text-xs font-semibold text-slate-300">Attachments (optional)</span><input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full text-xs text-slate-300"></label>
                    <button type="submit" class="rounded-md bg-[#2D7EFF] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#4D95FF]">Submit request</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-lg border border-white/10 bg-[#0B1E3D] shadow-xl">
                <div class="border-b border-white/10 px-5 py-4"><h2 class="font-bold text-white">Request history</h2><p class="mt-1 text-xs text-slate-400">Only your own requests are shown.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-xs font-bold uppercase tracking-wide text-slate-400"><tr><th class="px-4 py-3">Type</th><th class="px-4 py-3">Dates</th><th class="px-4 py-3">Days</th><th class="px-4 py-3">Status</th></tr></thead>
                        <tbody class="divide-y divide-white/10 text-slate-200">
                            @forelse ($leaveRequests as $leave)
                                @php $statusClass = match ($leave->status) { 'approved' => 'bg-emerald-400/15 text-emerald-300', 'rejected' => 'bg-rose-400/15 text-rose-300', default => 'bg-amber-400/15 text-amber-200' }; @endphp
                                <tr class="hover:bg-white/[.03]"><td class="px-4 py-3 font-semibold text-white">{{ ucfirst($leave->type) }}</td><td class="px-4 py-3">{{ $leave->from_date->format('M d, Y') }} - {{ $leave->to_date->format('M d, Y') }}</td><td class="px-4 py-3">{{ $leave->total_days }}</td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ ucfirst($leave->status) }}</span></td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-400">You have not submitted a leave request yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-white/10 px-5 py-3 text-sm">{{ $leaveRequests->links() }}</div>
            </section>
        </div>
    </main>
</body>
</html>
