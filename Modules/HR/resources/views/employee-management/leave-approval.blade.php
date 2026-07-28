<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Leave Request Details</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  html, body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
</style>
</head>
<body class="bg-[#1B3A6B]">

 @include('partials.navbar')

<div class="relative min-h-screen bg-[#1B3A6B] flex items-center justify-center px-6 py-6">

  <!-- Outer bordered panel -->
  <div class="w-full max-w-[1800px] min-h-[calc(100vh-3rem)] border-[0px] border-[#1c2f5e] box-border p-10 flex flex-col">

    <!-- Back button -->
    <div class="mb-6">
      <a href="{{ route('hr.leave-management.index') }}" class="inline-flex items-center gap-2 bg-[#0061FF20] hover:bg-[#0061FF40] text-white text-[13px] font-semibold tracking-wide px-5 py-2.5 rounded-full border border-[#2a3f72] transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        LEAVE REQUEST DETAILS
      </a>
    </div>

    <!-- Main Card -->
    <div class="flex-1 bg-[#132B52] rounded-2xl border border-[#22335f] shadow-2xl px-14 py-10 flex flex-col gap-8 overflow-hidden">

      <!-- Title -->
      <div>
        <h1 class="text-white text-2xl font-400 tracking-wide">
          LEAVE REQUEST ID: <span class="text-[#5b8def] font-bold">#{{ $leaveRequest->reference_id }}</span>
        </h1>
      </div>

     <!-- PROFILE SECTION -->
      <div class="rounded-xl border border-[#22335f] overflow-hidden">
        <div class="flex items-center gap-2 bg-[#132a58] px-6 py-3.5 border-b border-[#22335f]">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span class="text-slate-200 text-[13px] font-400 tracking-widest">PROFILE</span>
        </div>
        <div class="grid grid-cols-4 divide-x divide-[#22335f] bg-[#0B1E3D]">
          <div class="px-8 py-6">
            <p class="text-slate-400 text-[13px] font-semibold mb-2">Employee ID</p>
            <p class="text-white text-[15px]">{{ $employee->employee_id }}</p>
          </div>
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-bold mb-2">Name</p>
            <p class="text-white text-[15px]">{{ $employee->first_name }} {{ $employee->last_name }}</p>
          </div>
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-bold mb-2">Department</p>
            <p class="text-white text-[15px]">{{ $employee->department }}</p>
          </div>
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-bold mb-2">Position</p>
            <p class="text-white text-[15px]">{{ $employee->position }}</p>
          </div>
        </div>
      </div>

      <!-- LEAVE INFORMATION SECTION -->
      <div class="rounded-xl border border-[#22335f] overflow-hidden">
        <div class="flex items-center gap-2 bg-[#132a58] px-6 py-3.5 border-b border-[#22335f]">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="text-slate-200 text-[13px] font-400 tracking-widest">LEAVE INFORMATION</span>
        </div>
        <div class="grid grid-cols-4 divide-x divide-[#22335f] bg-[#0B1E3D]">
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-400 mb-2">Leave Type</p>
            <p class="text-white text-[15px]">{{ ucfirst($leaveRequest->type) }}</p>
          </div>
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-400 mb-2">From</p>
            <div class="flex items-center gap-2">
              <p class="text-white text-[15px] font-semibold">{{ 
                  \Carbon\Carbon::parse($leaveRequest->from_date)->format('F d, Y')
                }}</p>
            </div>
            <p class="text-slate-400 text-[12px] mt-1 ml-5">({{ \Carbon\Carbon::parse($leaveRequest->from_date)->format('l') }})</p>
          </div>
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-400 mb-2">To</p>
            <div class="flex items-center gap-2">
              <p class="text-white text-[15px] font-semibold">{{ \Carbon\Carbon::parse($leaveRequest->to_date)->format('F d, Y') }}</p>
            </div>
            <p class="text-slate-400 text-[12px] mt-1 ml-5">({{ \Carbon\Carbon::parse($leaveRequest->to_date)->format('l') }})</p>
          </div>
          <div class="px-8 py-6">
            <p class="text-white text-[13px] font-400 mb-2">Total</p>
            <p class="text-[#5b8def] text-2xl font-bold">{{ \Carbon\Carbon::parse($leaveRequest->from_date)->diffInDays($leaveRequest->to_date) + 1 }} Days</p>
          </div>
        </div>
        <div class="bg-[#0B1E3D] px-8 py-6 border-t border-white/10">
          <p class="text-white text-[13px] font-400 mb-2">Employee Remarks</p>
          <p class="text-slate-200 text-[15px]">{{ $leaveRequest->reason ?? 'No remarks provided.' }}</p>
        </div>
      </div>

      <!-- ATTACHMENTS + APPROVAL STATUS -->
      <div class="grid grid-cols-2 gap-8">

        <!-- Attachments -->
        <div class="rounded-xl border border-[#22335f] overflow-hidden flex flex-col">
          <div class="flex items-center gap-2 bg-[#132a58] px-6 py-3.5 border-b border-[#22335f]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.1-1.1m-.1-8.556a4 4 0 015.656 0l4 4a4 4 0 11-5.656 5.656l-1.1-1.1" />
            </svg>
            <span class="text-slate-200 text-[13px] font-400 tracking-widest">ATTACHMENTS</span>
          </div>
          <div class="bg-[#0B1E3D] px-6 py-5 flex flex-col gap-3 flex-1">
            @forelse(($attachments ?? []) as $file)
              @php
                $attachmentPath = is_string($file) ? $file : ($file->path ?? $file->url ?? null);
                $attachmentName = is_string($file) ? basename($file) : ($file->name ?? basename((string) $attachmentPath));
                $attachmentUrl = $attachmentPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($attachmentPath) : '#';
              @endphp
              <div class="flex items-center justify-between bg-[#132a58]/60 hover:bg-[#16305f] transition-colors rounded-lg px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded bg-[#3b6fe0] flex items-center justify-center text-white text-[10px] font-bold">FILE</div>
                  <div>
                    <p class="text-white text-[14px] font-medium">{{ $attachmentName }}</p>
                  </div>
                </div>
                <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="text-slate-300">Download</a>
              </div>
            @empty
              <div class="p-4 text-slate-400">No attachments</div>
            @endforelse
          </div>
        </div>

        <!-- Approval Status -->
        <div class="rounded-xl border border-[#22335f] overflow-hidden flex flex-col">
          <div class="flex items-center gap-2 bg-[#132a58] px-6 py-3.5 border-b border-[#22335f]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-slate-200 text-[13px] font-400 tracking-widest">APPROVAL STATUS</span>
          </div>
          <div class="bg-[#0B1E3D] px-6 py-5 flex-1 flex items-center">
            @php
              $s = match($leaveRequest->status) {
                'approved' => ['label' => 'APPROVED', 'color' => 'text-green-400', 'note' => $leaveRequest->status_note ?? 'Your request has been approved.'],
                'rejected' => ['label' => 'REJECTED', 'color' => 'text-red-400', 'note' => $leaveRequest->status_note ?? 'Your request has been rejected.'],
                default => ['label' => 'PENDING APPROVAL', 'color' => 'text-amber-400', 'note' => 'Your request is awaiting approval.'],
              };
            @endphp
            <div class="w-full bg-[#132a58]/60 rounded-lg px-5 py-4 flex items-center gap-4">
              <div class="w-9 h-9 rounded-full {{ $s['color'] }}/15 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $s['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" />
                </svg>
              </div>
              <div>
                <p class="text-{{ str_replace('text-','',$s['color']) }} text-[14px] font-bold tracking-wide">{{ $s['label'] }}</p>
                <p class="text-slate-400 text-[13px] mt-0.5">{{ $s['note'] }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- HR REMARKS -->
      <form method="POST" action="{{ route('hr.leave-requests.review', $leaveRequest->id) }}">
        @csrf
        <div class="rounded-xl border border-[#22335f] overflow-hidden">
          <div class="flex items-center gap-2 bg-[#132a58] px-6 py-3.5 border-b border-[#22335f]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="text-slate-200 text-[13px] font-400 tracking-widest">HR REMARKS</span>
          </div>
          <div class="bg-[#0B1E3D] px-6 py-5 flex items-center gap-4">
            <textarea name="remarks" rows="1" placeholder="Enter remarks here..." class="flex-1 bg-[#0a1633] border border-[#22335f] rounded-lg px-4 py-3 text-[14px] text-slate-200 placeholder-slate-500 focus:outline-none focus:border-[#5b8def]">{{ old('remarks') }}</textarea>

            <button type="submit" name="action" value="approve" class="inline-flex items-center gap-2 bg-[#1f7a4d] hover:bg-[#228a56] text-white text-[14px] font-semibold px-5 py-3 rounded-lg transition-colors whitespace-nowrap">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              Accept
            </button>

            <button type="submit" name="action" value="reject" class="inline-flex items-center gap-2 bg-[#b23b46] hover:bg-[#c2444f] text-white text-[14px] font-semibold px-5 py-3 rounded-lg transition-colors whitespace-nowrap">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Reject
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>
</body>
</html>
