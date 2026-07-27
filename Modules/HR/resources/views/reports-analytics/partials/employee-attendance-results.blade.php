<!-- Table Header -->
<div class="w-full mx-auto mb-3 bg-[#0B1E3D] border border-white/[0.15] rounded-[10px] overflow-hidden">
  <table class="w-full table-fixed border-collapse">
    <colgroup>
      <col style="width:13%">
      <col style="width:11%">
      <col style="width:11%">
      <col style="width:11%">
      <col style="width:11%">
      <col style="width:13%">
      <col style="width:15%">
    </colgroup>
    <thead>
      <tr>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white border-r border-white/[0.15]">Date</th>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white border-r border-white/[0.15]">Time In</th>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white border-r border-white/[0.15]">In Image</th>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white border-r border-white/[0.15]">Time Out</th>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white border-r border-white/[0.15]">Out Image</th>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white border-r border-white/[0.15]">Work Hours</th>
        <th class="px-[10px] py-[15px] text-center text-[11.9px] font-normal uppercase tracking-wide text-white">Status</th>
      </tr>
    </thead>
  </table>
</div>

<!-- Table Body -->
<div class="w-full mx-auto bg-[#0B1E3D] border border-white/[0.15] rounded-[10px] overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full table-fixed border-collapse">
      <colgroup>
        <col style="width:13%">
        <col style="width:11%">
        <col style="width:11%">
        <col style="width:11%">
        <col style="width:11%">
        <col style="width:13%">
        <col style="width:15%">
      </colgroup>
      <tbody>
        @forelse ($attendances as $i => $row)
          @php
            $status = $row->displayStatus();
            $statusClasses = $status === 'Present'
                ? 'bg-emerald-500/15 text-emerald-400'
                : 'bg-rose-500/15 text-rose-400';
            $showLateIn = $row->showsLateTimeInWarning();
            $showShortOut = $row->showsShortTimeOutWarning();
            $hoursTitle = 'Did not meet the required work hours';
            $inImage = $row->timeInImageUrl();
            $outImage = $row->timeOutImageUrl();
          @endphp
          <tr class="border-t border-white/[0.18] transition-colors duration-[250ms] hover:bg-[#21457f]">
            <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-normal whitespace-nowrap">
              {{ \Carbon\Carbon::parse($row->attendance_date)->format('d M Y') }}
            </td>
            <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-normal whitespace-nowrap">
              @if ($showLateIn)
                <span class="cursor-help font-medium text-red-500" title="{{ $hoursTitle }}">
                  {{ $row->formattedTimeIn() }}
                </span>
              @else
                {{ $row->formattedTimeIn() }}
              @endif
            </td>
            <td class="p-4 text-center border-r border-white/[0.12]">
              @if ($inImage)
                <button
                  type="button"
                  class="attendance-photo-thumb inline-flex h-9 w-9 overflow-hidden rounded-md border border-white/10 bg-black/20 p-0"
                  data-photo-src="{{ $inImage }}"
                  data-photo-label="Time In — {{ \Carbon\Carbon::parse($row->attendance_date)->format('d M Y') }}"
                  title="View In Image"
                >
                  <img src="{{ $inImage }}" alt="Time in photo" class="h-full w-full object-cover">
                </button>
              @else
                <span class="text-[#93abd3]">—</span>
              @endif
            </td>
            <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-normal whitespace-nowrap">
              @if ($showShortOut)
                <span class="cursor-help font-medium text-red-500" title="{{ $hoursTitle }}">
                  {{ $row->formattedTimeOut() }}
                </span>
              @else
                {{ $row->formattedTimeOut() }}
              @endif
            </td>
            <td class="p-4 text-center border-r border-white/[0.12]">
              @if ($outImage)
                <button
                  type="button"
                  class="attendance-photo-thumb inline-flex h-9 w-9 overflow-hidden rounded-md border border-white/10 bg-black/20 p-0"
                  data-photo-src="{{ $outImage }}"
                  data-photo-label="Time Out — {{ \Carbon\Carbon::parse($row->attendance_date)->format('d M Y') }}"
                  title="View Out Image"
                >
                  <img src="{{ $outImage }}" alt="Time out photo" class="h-full w-full object-cover">
                </button>
              @else
                <span class="text-[#93abd3]">—</span>
              @endif
            </td>
            <td class="p-4 text-[0.84375rem] text-center border-r border-white/[0.12] font-normal whitespace-nowrap">{{ $row->formattedWorkHours() }}</td>
            <td class="p-4 text-center font-normal">
              <span class="status-badge {{ $statusClasses }}">
                {{ $status }}
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="p-[30px] text-center text-[#b9c8e8] text-sm">
              No attendance records for this employee.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@include('partials.list-pagination', ['paginator' => $attendances, 'label' => 'records'])
