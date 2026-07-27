@if(isset($leaveRequests))
    <div class="w-full max-w-[1859px] mx-auto bg-[#0B1E3D] rounded-[10px] overflow-x-hidden">
        <table class="w-full table-fixed border-collapse">
            <colgroup>
                <col style="width:18%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:11%">
                <col style="width:15%">
                <col style="width:11%">
                <col style="width:14%">
                <col style="width:9%">
                <col style="width:8%">
            </colgroup>
            <tbody>
                @forelse($leaveRequests as $leave)
                    @php $employee = $leave->employee; @endphp
                    <tr class="border-t border-white/[0.18] transition-colors duration-[250ms] hover:bg-[#21457f]">
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight">
                            
                            {{ $employee->first_name }} {{ $employee->last_name }}
                            <span class="block text-[0.65rem] text-[#93abd3] font-light mt-0.5">{{ '2026' . str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight">{{ $employee->department }}</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">{{ ucfirst($leave->type) }}</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">{{ $leave->created_at->format('M d, Y') }}</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">{{ \Carbon\Carbon::parse($leave->from_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($leave->to_date)->format('M d, Y') }}</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">{{ $leave->reference_id }}</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight">
                            <div class="text-[#c9d8f2]">{{ $leave->reviewed_by_name ?? '—' }}</div>
                            <div class="text-[0.70rem] text-[#93abd3] mt-1">{{ $leave->reviewed_by_position ?? '—' }}</div>
                        </td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight">
                            <span class="status-badge {{ $leave->status }}">{{ strtoupper($leave->status) }}</span>
                        </td>
                        <td class="p-4 text-[0.84375rem] text-center font-extralight">
                            <a href="{{ route('hr.leave-requests.show', $leave->id) }}" class="inline-flex items-center justify-center bg-[#132B52] hover:bg-[#2e5ca3] text-white rounded-xl px-3 py-1.5 text-[0.6875rem] transition-all duration-[250ms]">
                                Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-[30px] text-center text-[#b9c8e8] text-sm">
                            No leave requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.list-pagination', ['paginator' => $leaveRequests, 'label' => 'leave requests'])
@else
    {{-- Fallback to employees view when no leaveRequests provided (legacy) --}}
    <div class="w-full max-w-[1859px] mx-auto bg-[#0B1E3D] rounded-[10px] overflow-x-hidden">
        <table class="w-full table-fixed border-collapse">
            <colgroup>
                <col style="width:18%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:11%">
                <col style="width:15%">
                <col style="width:11%">
                <col style="width:14%">
                <col style="width:9%">
                <col style="width:8%">
            </colgroup>
            <tbody>
                @forelse($employees as $employee)
                    <tr class="border-t border-white/[0.18] transition-colors duration-[250ms] hover:bg-[#21457f]">
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight">
                            @php
                                $genderClass = match(strtolower($employee->gender ?? '')) {
                                    'female' => 'text-[#ff8bd2]',
                                    'male' => 'text-[#6ea9ff]',
                                    default => 'text-white',
                                };
                            @endphp
                            <i class="fa-solid fa-circle-user text-2xl {{ $genderClass }} mr-2"></i>
                            {{ $employee->first_name }} {{ $employee->last_name }}
                            <span class="block text-[0.65rem] text-[#93abd3] font-light mt-0.5">{{ '2026' . str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight">{{ $employee->department }}</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">—</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">—</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">—</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">—</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">—</td>
                        <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-extralight text-[#93abd3]">—</td>
                        <td class="p-4 text-[0.84375rem] text-center font-extralight">
                            <span class="status-badge">—</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-[30px] text-center text-[#b9c8e8] text-sm">
                            No employees found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.list-pagination', ['paginator' => $employees, 'label' => 'employees'])
@endif
