<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }

        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 500;
            background: rgba(255, 255, 255, .06);
        }
        .status-badge.approved { color: #34d399; }
        .status-badge.rejected { color: #fb7185; }
        .status-badge.pending  { color: #fbbf24; }

        .field-input::placeholder { color: #6b85b3; }
    </style>
</head>

<body class="bg-[#0d2549] text-white min-h-screen pb-6">

  @include('partials.employee-navbar')

    <div class="w-full px-6 py-8 flex flex-col gap-4">

        <!-- Form card: full width -->
        <form method="POST" action="{{ route('hr.employee.leave.submit') }}" enctype="multipart/form-data" class="w-full bg-[#122f5c] rounded-2xl p-10">
            @csrf

            <!-- Leave Type -->
            <div class="mb-5">
                <label class="block text-[12px] text-[#93abd3] mb-2">Leave Type</label>
                <div class="relative">
                    <i class="fa-regular fa-user absolute left-4 top-1/2 -translate-y-1/2 text-[#6b85b3] text-[13px]"></i>
                    <select name="type" class="field-input w-full appearance-none bg-[#0B1E3D] rounded-lg pl-11 pr-11 py-3 text-[13px] text-[#93abd3] focus:outline-none focus:ring-1 focus:ring-[#3b6fd4]">
                        <option value="" selected disabled>Select leave type</option>
                        <option value="vacation">Vacation Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="maternity">Maternity Leave</option>
                        <option value="paternity">Paternity Leave</option>
                        <option value="bereavement">Bereavement Leave</option>
                        <option value="others">Others</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-[#6b85b3] text-[11px] pointer-events-none"></i>
                </div>
            </div>

            <!-- Start / End / Documents -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                <div>
                    <label class="block text-[12px] text-[#93abd3] mb-2">Start Date</label>
                    <div class="relative">
                        <i class="fa-regular fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-[#6b85b3] text-[13px]"></i>
                        <input name="from_date" type="date" placeholder="Select start type"
                            class="field-input w-full bg-[#0B1E3D] rounded-lg pl-11 pr-4 py-3 text-[13px] text-[#93abd3] focus:outline-none focus:ring-1 focus:ring-[#3b6fd4]">
                    </div>
                </div>

                <div>
                    <label class="block text-[12px] text-[#93abd3] mb-2">End Date</label>
                    <div class="relative">
                        <i class="fa-regular fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-[#6b85b3] text-[13px]"></i>
                        <input name="to_date" id="to_date" type="date" placeholder="Select end type"
                            class="field-input w-full bg-[#0B1E3D] rounded-lg pl-11 pr-4 py-3 text-[13px] text-[#93abd3] focus:outline-none focus:ring-1 focus:ring-[#3b6fd4]">
                    </div>
                    <p id="date-help-text" class="text-[11px] text-[#93abd3] mt-2">Select leave type and start date to see allowed end date range.</p>
                </div>

                <div>
                    <label class="block text-[12px] text-[#93abd3] mb-2">Supporting Documents</label>
                        <label class="relative cursor-pointer block">
                        <i class="fa-solid fa-arrow-down-to-line absolute left-4 top-1/2 -translate-y-1/2 text-[#6b85b3] text-[13px]"></i>
                        <span class="field-input w-full block bg-[#0B1E3D] rounded-lg pl-11 pr-4 py-3 text-[13px] text-[#93abd3]">Click to upload files</span>
                        <input type="file" name="attachments[]" class="hidden" multiple>
                    </label>
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label class="block text-[12px] text-[#93abd3] mb-2">Reason</label>
                <div class="relative">
                    <textarea id="reason" name="reason" maxlength="500" rows="5" placeholder="Please provide the reason for your leave..."
                        class="field-input w-full bg-[#0B1E3D] rounded-lg px-4 py-3 text-[13px] text-[#93abd3] resize-none focus:outline-none focus:ring-1 focus:ring-[#3b6fd4]"
                        oninput="document.getElementById('charCount').textContent = this.value.length + ' / 500'"></textarea>
                    <input type="hidden" name="total_days" id="total_days" value="1">
                    <span id="charCount" class="absolute right-4 bottom-3 text-[11px] text-[#6b85b3]">0 / 500</span>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end mt-5">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-[#3b6fd4] hover:bg-[#4a7de0] transition-colors text-white text-[13px] font-medium px-6 py-3 rounded-lg">
                    <i class="fa-regular fa-paper-plane"></i>
                    Submit Request
                </button>
            </div>
        </form>

        @if(session('success'))
            <div class="w-full rounded-2xl bg-green-500/10 border border-green-500/20 text-green-100 px-6 py-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="w-full rounded-2xl bg-red-500/10 border border-red-500/20 text-red-100 px-6 py-4 mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="w-full rounded-2xl bg-red-500/10 border border-red-500/20 text-red-100 px-6 py-4 mb-4">
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- My Leave Request table: full width -->
        <div class="bg-[#122f5c] rounded-2xl p-6">
            <h3 class="text-[14px] font-semibold mb-4">My Leave Request</h3>

            <div class="rounded-lg overflow-hidden">
                <table class="w-full border-collapse text-[12.5px]">
                    <thead>
                        <tr class="bg-[#0B1E3D]">
                            <th class="text-center font-light text-white/90 px-4 py-3">Leave Type</th>
                            <th class="text-center font-light text-white/90 px-4 py-3">Date</th>
                            <th class="text-center font-light text-white/90 px-4 py-3">Duration</th>
                            <th class="text-center font-light text-white/90 px-4 py-3">Reason</th>
                            <th class="text-center font-light text-white/90 px-4 py-3">HR Remarks</th>
                            <th class="text-center font-light text-white/90 px-4 py-3">Status</th>
                            <th class="text-center font-light text-white/90 px-4 py-3">Filled On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveRequests as $leave)
                            <tr class="border-t border-white/[0.08] transition-colors duration-[250ms] hover:bg-[#1f3a67]">
                                <td class="text-center px-4 py-3 text-[#c9d8f2]">{{ ucfirst($leave->type) }} Leave</td>
                                <td class="text-center px-4 py-3 text-[#93abd3]">{{ \Carbon\Carbon::parse($leave->from_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($leave->to_date)->format('M d, Y') }}</td>
                                <td class="text-center px-4 py-3 text-[#93abd3]">{{ $leave->total_days }} Days</td>
                                <td class="text-center px-4 py-3 text-[#93abd3]">{{ \Illuminate\Support\Str::limit($leave->reason, 35) }}</td>
                                <td class="text-center px-4 py-3 text-[#93abd3]">{{ \Illuminate\Support\Str::limit($leave->status_note, 35) ?: '—' }}</td>
                                <td class="text-center px-4 py-3">
                                    @php
                                        $statusClass = match($leave->status) {
                                            'approved' => 'approved',
                                            'rejected' => 'rejected',
                                            default => 'pending',
                                        };
                                    @endphp
                                    <span class="status-badge {{ $statusClass }}">{{ strtoupper($leave->status) }}</span>
                                </td>
                                <td class="text-center px-4 py-3 text-[#93abd3]">{{ $leave->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center px-4 py-8 text-[#b9c8e8] text-sm">
                                    You have not submitted any leave requests yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const leaveTypeRules = {
            vacation: { min: 5, max: 15 },
            sick: { min: 5, max: 15 },
            maternity: { min: 105, max: 105 },
            paternity: { min: 7, max: 7 },
            bereavement: { min: 5, max: 5 },
            others: null,
        };

        const typeSelect = document.querySelector('select[name="type"]');
        const fromInput = document.querySelector('input[name="from_date"]');
        const toInput = document.getElementById('to_date');
        const totalDaysInput = document.getElementById('total_days');
        const dateHelpText = document.getElementById('date-help-text');

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        function updateToDateConstraints() {
            const type = typeSelect.value;
            const rule = leaveTypeRules[type];
            const fromValue = fromInput.value;

            if (!fromValue) {
                toInput.removeAttribute('min');
                toInput.removeAttribute('max');
                dateHelpText.textContent = 'Select leave type and start date to see allowed end date range.';
                updateTotalDays();
                return;
            }

            const fromDate = new Date(fromValue);
            if (Number.isNaN(fromDate.getTime())) {
                return;
            }

            if (!rule) {
                toInput.min = formatDate(fromDate);
                toInput.removeAttribute('max');
                dateHelpText.textContent = 'Others has no upper duration limit; select a valid end date.';
            } else {
                const maxDate = new Date(fromDate);
                maxDate.setDate(maxDate.getDate() + rule.max - 1);

                toInput.min = formatDate(fromDate);
                toInput.max = formatDate(maxDate);
                dateHelpText.textContent = `Select an end date between ${formatDate(fromDate)} and ${formatDate(maxDate)}.`;
            }

            if (toInput.value) {
                if (toInput.min && toInput.value < toInput.min) {
                    toInput.value = toInput.min;
                }
                if (toInput.max && toInput.value > toInput.max) {
                    toInput.value = toInput.max;
                }
            }

            updateTotalDays();
        }

        function updateTotalDays() {
            const fromValue = fromInput.value;
            const toValue = toInput.value;

            if (!fromValue || !toValue) {
                totalDaysInput.value = '1';
                return;
            }

            const fromDate = new Date(fromValue);
            const toDate = new Date(toValue);
            if (Number.isNaN(fromDate.getTime()) || Number.isNaN(toDate.getTime())) {
                totalDaysInput.value = '1';
                return;
            }

            const diff = Math.floor((toDate - fromDate) / (1000 * 60 * 60 * 24)) + 1;
            totalDaysInput.value = diff > 0 ? diff : 1;
        }

        typeSelect.addEventListener('change', updateToDateConstraints);
        fromInput.addEventListener('change', updateToDateConstraints);
        toInput.addEventListener('change', updateTotalDays);

        document.addEventListener('DOMContentLoaded', updateToDateConstraints);
    </script>
</body>

</html>
