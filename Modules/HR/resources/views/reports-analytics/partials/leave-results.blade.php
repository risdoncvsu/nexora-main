<div class="w-full max-w-[1859px] mx-auto overflow-x-auto rounded-[10px] bg-[#0B1E3D]">
    <table class="w-full min-w-[1050px] table-fixed border-collapse">
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
            @forelse ($leaveRequests as $leaveRequest)
                @php
                    $employee = $leaveRequest->employee;
                    $statusClass = $leaveRequest->status === 'approved'
                        ? 'bg-emerald-500/20 text-emerald-100'
                        : 'bg-rose-500/20 text-rose-100';
                @endphp
                <tr class="border-t border-white/[0.18] transition-colors duration-[250ms] hover:bg-[#21457f]">
                    <td class="p-4 text-center text-[0.84375rem] font-extralight border-r border-white/[0.12]">
                        <div>{{ $employee ? trim($employee->first_name.' '.$employee->last_name) : 'Former employee' }}</div>
                        <span class="mt-0.5 block text-[0.65rem] font-light text-[#93abd3]">{{ $employee?->employee_id ?: '—' }}</span>
                    </td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight border-r border-white/[0.12]">{{ $employee?->department ?: '—' }}</td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight text-[#93abd3] border-r border-white/[0.12]">{{ ucfirst($leaveRequest->type) }}</td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight text-[#93abd3] border-r border-white/[0.12]">{{ $leaveRequest->created_at?->format('M d, Y') }}</td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight text-[#93abd3] border-r border-white/[0.12]">{{ $leaveRequest->from_date?->format('M d, Y') }} – {{ $leaveRequest->to_date?->format('M d, Y') }}</td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight text-[#93abd3] border-r border-white/[0.12]">{{ $leaveRequest->reference_id ?: '—' }}</td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight border-r border-white/[0.12]">
                        <div class="text-[#c9d8f2]">{{ $leaveRequest->reviewed_by_name ?: '—' }}</div>
                        <div class="mt-1 text-[0.7rem] text-[#93abd3]">{{ $leaveRequest->reviewed_by_position ?: '—' }}</div>
                    </td>
                    <td class="p-4 text-center text-[0.84375rem] font-extralight border-r border-white/[0.12]">
                        <span class="inline-flex rounded-full px-3 py-1 text-[0.65rem] font-semibold {{ $statusClass }}">{{ strtoupper($leaveRequest->status) }}</span>
                    </td>
                    <td class="p-4 text-center font-extralight">
                        <a href="{{ route('hr.leave-requests.show', $leaveRequest) }}" class="inline-flex items-center justify-center rounded-xl bg-[#132B52] px-3 py-1.5 text-[0.6875rem] text-white transition-all duration-[250ms] hover:bg-[#2e5ca3]">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="p-[30px] text-center text-sm text-[#b9c8e8]">No reviewed leave requests found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.list-pagination', ['paginator' => $leaveRequests, 'label' => 'leave requests'])
