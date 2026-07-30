@props([
    'homeRoute' => '#',
    'navItems' => [],
    'active' => null,
])

<header class="sticky top-0 z-40 flex min-h-16 flex-col items-center justify-center gap-3 border-b border-white/10 bg-[#0B1E3D] px-4 py-3 shadow-lg lg:h-[72px] lg:flex-row lg:justify-between lg:px-6 lg:py-0">
    <div class="flex shrink-0 items-center gap-2">
        <a href="{{ $homeRoute }}" class="block h-10 transition hover:scale-[1.02] sm:h-11">
            <img src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora Logo" class="h-full object-contain">
        </a>
        <span class="h-7 w-px bg-white/20"></span>
        <x-client-logo :size="36" />
    </div>

    <div class="flex w-full flex-wrap items-center justify-center gap-3 lg:w-auto lg:flex-nowrap lg:justify-end">
        <nav class="flex max-w-full flex-wrap items-center justify-center gap-1 text-xs font-semibold sm:text-sm lg:flex-nowrap">
            @foreach ($navItems as $item)
                @php
                    $isActive = $active === $item['key']
                        || ($item['key'] === 'employees' && request()->routeIs('client.itsm.employees'));
                @endphp
                <a href="{{ $item['route'] }}" @if ($isActive) aria-current="page" @endif class="rounded-md px-3 py-2 {{ $isActive ? 'bg-white/12 text-white shadow-sm' : 'text-white/70 transition hover:bg-white/8 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="relative" data-user-menu>
            <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-white/10 transition hover:bg-white/20" data-user-menu-button aria-label="Open user menu">
                <img src="{{ asset('images/icon.png') }}" alt="User" class="h-7 w-7 object-contain">
            </button>

            <div class="invisible absolute right-0 top-12 z-50 w-[200px] translate-y-[-10px] overflow-hidden rounded-lg bg-white opacity-0 shadow-2xl transition data-[open=true]:visible data-[open=true]:translate-y-0 data-[open=true]:opacity-100" data-user-menu-dropdown>
                <x-dark-mode-toggle />
                @if(session('employee_logged_in'))
                    <a href="{{ route('employee.portal') }}" class="block px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Employee Portal</a>
                @endif
                <a href="{{ route('login') }}" class="block px-5 py-4 text-sm font-semibold text-[#DC2626] transition hover:bg-slate-100">Log Out</a>
            </div>
        </div>
    </div>
</header>

<script>
    document.querySelectorAll('[data-user-menu]').forEach((menu) => {
        const button = menu.querySelector('[data-user-menu-button]');
        const dropdown = menu.querySelector('[data-user-menu-dropdown]');

        button?.addEventListener('click', (event) => {
            event.stopPropagation();
            dropdown.dataset.open = dropdown.dataset.open === 'true' ? 'false' : 'true';
        });

        window.addEventListener('click', () => {
            dropdown.dataset.open = 'false';
        });
    });
</script>
