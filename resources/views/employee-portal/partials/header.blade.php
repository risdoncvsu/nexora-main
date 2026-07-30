@props(['active' => 'overview'])

@php
    $company = \App\Models\Company::find((int) session('employee_client_id'));
    $links = [
        'overview' => ['label' => 'Overview', 'route' => route('employee.portal')],
        'attendance' => ['label' => 'My Attendance', 'route' => route('employee.portal.attendance')],
        'leave' => ['label' => 'Leave Requests', 'route' => route('employee.portal.leave')],
    ];
@endphp

<header class="border-b border-white/10 bg-[#132B52] text-white shadow-lg">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee.portal') }}" class="flex items-center gap-3 no-underline">
                <img src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora ERP" class="h-9 w-auto max-w-36 object-contain">
                <span class="h-6 w-px bg-white/20"></span>
                <x-client-logo :size="32" />
            </a>
            <span class="text-xs font-semibold text-blue-200">Employee Portal</span>
        </div>

        <nav class="flex flex-wrap items-center gap-1 text-sm font-semibold">
            @foreach ($links as $key => $link)
                <a href="{{ $link['route'] }}" class="rounded-md px-3 py-2 {{ $active === $key ? 'bg-white/15 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold">{{ session('employee_name', 'Employee') }}</p>
                <p class="text-xs text-blue-200">{{ $company?->company_name ?? 'Your organization' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md border border-white/25 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/10">Log out</button>
            </form>
        </div>
    </div>
</header>
