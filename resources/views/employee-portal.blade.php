<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Employee Portal</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7fb] font-sans text-slate-950">
    <header class="border-b border-white/10 bg-[#132B52] text-white shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-6 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('employee.portal') }}" class="flex items-center gap-3 no-underline">
                    <img src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora ERP" class="h-10 w-auto max-w-40 object-contain">
                    <div>
                        <p class="text-xs text-blue-200">Employee Portal</p>
                    </div>
                </a>
                <x-client-logo :size="40" />
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-semibold">{{ session('employee_name', 'Employee') }}</p>
                    <p class="text-xs text-blue-200">{{ $company?->company_name ?? 'Your organization' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-white/25 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/10">Log out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        <section class="rounded-[2rem] bg-gradient-to-br from-[#132B52] via-[#1b467f] to-[#346DCB] p-8 text-white shadow-xl md:p-12">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-blue-200">ITSM workspace</p>
            <div class="mt-4 flex flex-col justify-between gap-7 lg:flex-row lg:items-end">
                <div>
                    <h1 class="text-3xl font-bold md:text-5xl">Welcome back, {{ session('employee_name', 'Employee') }}.</h1>
                    <p class="mt-3 max-w-2xl text-base text-blue-100 md:text-lg">Enter your assigned department, view support updates, or access your HR self-service records from one secure portal.</p>
                </div>
                <a href="{{ $moduleUrl }}" role="button" tabindex="0"
                   onclick="window.location.assign(this.href); return false;"
                   class="relative z-20 inline-flex shrink-0 cursor-pointer pointer-events-auto items-center justify-center rounded-full bg-white px-6 py-3 text-center font-bold text-[#132B52] shadow-md transition hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-white/50">
                    Continue to {{ $department }} dashboard <span class="ml-2" aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 font-medium text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="mt-8 grid gap-8 {{ $showEmployeeSelfService ? 'lg:grid-cols-[1.45fr_0.85fr]' : '' }}">
            <section class="rounded-[1.75rem] bg-white p-7 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-[#346DCB]">Support notifications</p>
                        <h2 class="mt-1 text-2xl font-bold">Your support tickets</h2>
                    </div>
                    <button type="button" id="openTicketDialog" class="rounded-full bg-[#346DCB] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2554a3]">Create support ticket</button>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($tickets as $ticket)
                        @php
                            $statusClass = match (strtolower($ticket->status)) {
                                'resolved', 'closed' => 'bg-emerald-100 text-emerald-700',
                                'in progress', 'pending review' => 'bg-amber-100 text-amber-700',
                                default => 'bg-blue-100 text-blue-700',
                            };
                        @endphp
                        <article class="rounded-xl border border-slate-200 p-4 transition hover:border-[#346DCB]/50 hover:shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $ticket->ticket_no }} &middot; {{ $ticket->module ?? 'General ERP' }}</p>
                                    <h3 class="mt-1 font-bold text-slate-900">{{ $ticket->subject }}</h3>
                                    @if ($ticket->description)
                                        <p class="mt-1 text-sm text-slate-600">{{ $ticket->description }}</p>
                                    @endif
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $ticket->status }}</span>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">{{ $ticket->category }} &middot; {{ $ticket->priority }} priority &middot; Updated {{ $ticket->updated_at?->diffForHumans() }}</p>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center">
                            <p class="font-semibold text-slate-800">No support notifications yet.</p>
                            <p class="mt-1 text-sm text-slate-500">Create a ticket and updates from your client ITSM team will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($showEmployeeSelfService)
                <aside>
                    <section class="rounded-[1.75rem] bg-white p-7 shadow-sm ring-1 ring-slate-200">
                        <p class="text-sm font-bold uppercase tracking-wide text-[#346DCB]">Employee self-service</p>
                        <h2 class="mt-1 text-2xl font-bold">Your records</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">View your attendance and submit leave requests without leaving the Employee Portal.</p>
                        <a href="{{ $attendanceUrl }}" class="mt-5 flex items-center justify-between rounded-xl bg-[#132B52] px-4 py-4 font-semibold text-white transition hover:bg-[#0d2141]">
                            <span>My attendance</span><span aria-hidden="true">&rarr;</span>
                        </a>
                        <a href="{{ $leaveUrl }}" class="mt-3 flex items-center justify-between rounded-xl border border-[#346DCB]/30 px-4 py-4 font-semibold text-[#132B52] transition hover:bg-blue-50">
                            <span>Leave requests</span><span aria-hidden="true">&rarr;</span>
                        </a>
                    </section>
                </aside>
            @endif
        </div>
    </main>

    <div id="ticketDialog" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
        <div class="w-full max-w-2xl rounded-[1.75rem] bg-white p-7 shadow-2xl">
            <div class="flex items-start justify-between gap-5">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-[#346DCB]">Client ITSM Service Desk</p>
                    <h2 class="mt-1 text-2xl font-bold">Create support ticket</h2>
                </div>
                <button type="button" data-close-ticket class="text-2xl leading-none text-slate-500 hover:text-slate-950" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('employee.support-tickets.store') }}" class="mt-6 grid gap-5 md:grid-cols-2">
                @csrf
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold">Category</span>
                    <input name="category" value="{{ old('category') }}" required placeholder="Example: Access issue" class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-[#346DCB] focus:outline-none">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold">Priority</span>
                    <select name="priority" class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-[#346DCB] focus:outline-none">
                        @foreach (['Low', 'Medium', 'High', 'Critical'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-sm font-semibold">Subject</span>
                    <input name="subject" value="{{ old('subject') }}" required class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-[#346DCB] focus:outline-none">
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-sm font-semibold">Describe the issue</span>
                    <textarea name="description" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#346DCB] focus:outline-none">{{ old('description') }}</textarea>
                </label>
                <div class="flex justify-end gap-3 md:col-span-2">
                    <button type="button" data-close-ticket class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-full bg-[#346DCB] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2554a3]">Send ticket</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const dialog = document.getElementById('ticketDialog');
        const openDialog = () => { dialog.classList.remove('hidden'); dialog.classList.add('flex'); };
        const closeDialog = () => { dialog.classList.add('hidden'); dialog.classList.remove('flex'); };
        document.getElementById('openTicketDialog')?.addEventListener('click', openDialog);
        document.querySelectorAll('[data-close-ticket]').forEach(button => button.addEventListener('click', closeDialog));
        dialog?.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });
        @if ($errors->any())
            openDialog();
        @endif
    </script>
</body>
</html>
