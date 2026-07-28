<x-service-desk-admin-layout
    title="Nexora Support Desk"
    subtitle="Support requests sent by company system administrators to the Nexora root-admin team."
    section="queue"
>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Open Tickets', 'value' => $tickets->where('status', 'Open')->count()],
            ['label' => 'In Progress', 'value' => $tickets->where('status', 'In Progress')->count()],
            ['label' => 'Pending Review', 'value' => $tickets->where('status', 'Pending Review')->count()],
            ['label' => 'Resolved', 'value' => $tickets->where('status', 'Resolved')->count()],
        ] as $stat)
            <div class="rounded-2xl bg-white p-6 text-slate-950 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-3 text-4xl font-bold">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-[1.875rem] bg-white p-6 text-slate-950 sm:p-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Recent Requests</h2>
                <p class="mt-1 text-sm text-slate-500">Claim a request before working on it, then update its status and resolution details.</p>
            </div>
            <input type="search" id="ticketSearch" placeholder="Search requests" class="h-10 w-full rounded border border-slate-300 px-3 text-sm sm:w-64">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm" id="ticketsTable">
                <thead>
                    <tr class="border-b-2 border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-3 pr-4">Ticket</th>
                        <th class="py-3 pr-4">Client</th>
                        <th class="py-3 pr-4">Module</th>
                        <th class="py-3 pr-4">Subject</th>
                        <th class="py-3 pr-4">Priority</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-b border-slate-200" data-ticket-row
                            data-id="{{ $ticket->id }}"
                            data-requester="{{ e($ticket->requester) }}"
                            data-module="{{ e($ticket->module) }}"
                            data-category="{{ e($ticket->category) }}"
                            data-priority="{{ e($ticket->priority) }}"
                            data-status="{{ e($ticket->status) }}"
                            data-subject="{{ e($ticket->subject) }}"
                            data-description="{{ e($ticket->description) }}">
                            <td class="py-4 pr-4 font-semibold">{{ $ticket->ticket_no }}</td>
                            <td class="py-4 pr-4">{{ $ticket->client_name ?? 'Internal' }}</td>
                            <td class="py-4 pr-4">{{ $ticket->module ?? 'Nexora Platform' }}</td>
                            <td class="py-4 pr-4">{{ $ticket->subject }}</td>
                            <td class="py-4 pr-4">{{ $ticket->priority }}</td>
                            <td class="py-4 pr-4">{{ $ticket->status }}</td>
                            <td class="py-4 text-right whitespace-nowrap">
                                @if (! in_array($ticket->status, ['Resolved', 'Closed'], true))
                                    @if ((int) $ticket->assigned_to === (int) auth()->id())
                                        <span class="mr-2 rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-[#346DCB]">Assigned to you</span>
                                    @elseif (! $ticket->assigned_to)
                                        <form method="POST" action="{{ route('admin.itsm.service-desk.claim', $ticket) }}" class="mr-2 inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-md bg-[#346DCB] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#2554a3]">Claim</button>
                                        </form>
                                    @else
                                        <span class="mr-2 text-xs font-semibold text-slate-500">Assigned</span>
                                    @endif
                                @endif
                                <button type="button" data-edit-ticket class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold hover:bg-slate-100">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-slate-500">No Nexora support requests are waiting.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="ticketModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 text-slate-950 shadow-2xl sm:p-8">
            <div class="mb-6 flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold">Update Support Request</h2>
                <button type="button" data-close-ticket-modal class="text-2xl font-bold text-slate-500 hover:text-slate-950" aria-label="Close">&times;</button>
            </div>
            <form id="ticketForm" method="POST" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @csrf
                @method('PATCH')
                <label class="block text-sm font-semibold">Requester<input id="ticket_requester" name="requester" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold">Category<input id="ticket_category" name="category" required class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold">Area<input id="ticket_module" name="module" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold">Priority<select id="ticket_priority" name="priority" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"><option>Low</option><option>Medium</option><option>High</option><option>Critical</option></select></label>
                <label class="block text-sm font-semibold">Status<select id="ticket_status" name="status" class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"><option>Open</option><option>In Progress</option><option>Pending Review</option><option>Resolved</option><option>Closed</option></select></label>
                <label class="block text-sm font-semibold md:col-span-2">Subject<input id="ticket_subject" name="subject" required class="mt-1.5 h-10 w-full rounded border border-slate-300 px-3 font-normal"></label>
                <label class="block text-sm font-semibold md:col-span-2">Description<textarea id="ticket_description" name="description" rows="5" class="mt-1.5 w-full rounded border border-slate-300 px-3 py-2 font-normal"></textarea></label>
                <div class="flex justify-end gap-3 pt-2 md:col-span-2"><button type="button" data-close-ticket-modal class="rounded-md border border-slate-300 px-5 py-2 font-semibold hover:bg-slate-100">Cancel</button><button type="submit" class="rounded-md bg-[#346DCB] px-5 py-2 font-semibold text-white hover:bg-[#2554a3]">Save changes</button></div>
            </form>
        </div>
    </div>

    <script>
        const ticketModal = document.getElementById('ticketModal');
        const ticketForm = document.getElementById('ticketForm');
        const updateTemplate = @json(route('admin.itsm.service-desk.update', ['ticket' => '__ID__']));
        const setField = (id, value) => { const field = document.getElementById(id); if (field) field.value = value || ''; };
        const closeTicketModal = () => { ticketModal.classList.add('hidden'); ticketModal.classList.remove('flex'); };

        document.querySelectorAll('[data-edit-ticket]').forEach((button) => button.addEventListener('click', () => {
            const row = button.closest('[data-ticket-row]');
            ticketForm.action = updateTemplate.replace('__ID__', row.dataset.id);
            ['requester', 'module', 'category', 'priority', 'status', 'subject', 'description'].forEach((field) => setField(`ticket_${field}`, row.dataset[field]));
            ticketModal.classList.remove('hidden'); ticketModal.classList.add('flex');
        }));
        document.querySelectorAll('[data-close-ticket-modal]').forEach((button) => button.addEventListener('click', closeTicketModal));
        ticketModal.addEventListener('click', (event) => { if (event.target === ticketModal) closeTicketModal(); });
        document.getElementById('ticketSearch').addEventListener('input', (event) => document.querySelectorAll('[data-ticket-row]').forEach((row) => row.classList.toggle('hidden', !row.textContent.toLowerCase().includes(event.target.value.toLowerCase()))));
    </script>
</x-service-desk-admin-layout>
