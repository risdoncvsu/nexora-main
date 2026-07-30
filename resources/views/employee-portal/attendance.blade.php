<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | My Attendance</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1B3A6B] font-sans text-slate-200">
    @include('employee-portal.partials.header', ['active' => 'attendance'])

    <main class="mx-auto max-w-7xl px-5 py-7">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-sky-300">Employee self-service</p>
                <h1 class="mt-1 text-2xl font-bold text-white">My Attendance</h1>
                <p class="mt-1 text-sm text-slate-300">Your attendance record from HR.</p>
            </div>
            <a href="{{ route('employee.portal') }}" class="rounded-md border border-white/20 px-3 py-2 text-sm font-semibold text-white hover:bg-white/10">Back to portal</a>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Present days', 'value' => $stats['present'], 'tone' => 'text-emerald-300'],
                ['label' => 'Absent days', 'value' => $stats['absent'], 'tone' => 'text-rose-300'],
                ['label' => 'Leave days', 'value' => $stats['leave'], 'tone' => 'text-amber-300'],
                ['label' => 'Attendance records', 'value' => $stats['total'], 'tone' => 'text-sky-300'],
            ] as $stat)
                <article class="rounded-lg border border-white/10 bg-[#0B1E3D] p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $stat['tone'] }}">{{ $stat['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-5 overflow-hidden rounded-lg border border-white/10 bg-[#0B1E3D] shadow-xl">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                <div>
                    <h2 class="font-bold text-white">Attendance history</h2>
                    <p class="mt-1 text-xs text-slate-400">{{ $employee->employee_id }} · {{ $employee->department }} · {{ $employee->position }}</p>
                </div>
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-300">{{ $attendances->total() }} records</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-xs font-bold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Time in</th>
                            <th class="px-5 py-3">Time out</th>
                            <th class="px-5 py-3">Work hours</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 text-slate-200">
                        @forelse ($attendances as $attendance)
                            @php
                                $status = $attendance->displayStatus();
                                $statusClass = $status === 'Present' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300';
                            @endphp
                            <tr class="hover:bg-white/[.03]">
                                <td class="px-5 py-3 font-semibold text-white">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}</td>
                                <td class="px-5 py-3">{{ $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') : 'Not recorded' }}</td>
                                <td class="px-5 py-3">{{ $attendance->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('h:i A') : 'Not recorded' }}</td>
                                <td class="px-5 py-3">{{ $attendance->formattedWorkHours() }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No attendance records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-white/10 px-5 py-3 text-sm">{{ $attendances->links() }}</div>
        </section>
    </main>
</body>
</html>
