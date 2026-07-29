@php
    $navLink = 'text-white no-underline text-xl py-2.5 px-[18px] flex items-center gap-2 rounded-full transition-all duration-250 hover:text-[#66A6FF] hover:bg-[#1B3A6B] hover:-translate-y-px hover:font-bold active:scale-[.97]';
    $navActive = 'text-[#66A6FF] bg-[#1B3A6B] font-bold';
    $dropLink = 'block no-underline text-[#C9DAF8] py-[11px] px-3.5 rounded-[10px] text-[13px] font-medium transition-all duration-200 hover:bg-white/10 hover:backdrop-blur-md hover:text-[#66A6FF]';
    $dropActive = 'bg-white/10 backdrop-blur-md text-[#66A6FF]';
    $dropdownPanel = 'absolute top-[120%] left-1/2 -translate-x-1/2 translate-y-2.5 w-[220px] bg-white/[.06] backdrop-blur-xl border border-white/10 rounded-[18px] shadow-[0_20px_45px_rgba(0,0,0,.35),inset_0_1px_0_rgba(255,255,255,.05)] p-2.5 opacity-0 invisible transition-all duration-300 z-[999] group-hover:opacity-100 group-hover:visible group-hover:translate-y-0';

    $isDashboard = request()->routeIs('hr.dashboard');
    $isWorkforce = request()->routeIs('hr.employees.*') || request()->routeIs('hr.departments.*');
    $isEmployees = request()->routeIs('hr.employees.index') || request()->routeIs('hr.employees.show') || request()->routeIs('hr.employees.create');
    $isDepartments = request()->routeIs('hr.departments.*');
    $isOnboarding = request()->routeIs('hr.onboarding.*');
    $isReports = request()->routeIs('hr.reports-analytics.*');
    $isAttendance = request()->routeIs('hr.reports-analytics.attendance-overview') || request()->routeIs('hr.reports-analytics.employee-attendance');
    $isLeave = request()->routeIs('hr.reports-analytics.leave');

    $isEmployeeManagement = request()->routeIs('hr.leave-management.*');
    $isLeaveManagement = request()->routeIs('hr.leave-management.*');
@endphp

<header class="w-full h-[150px] bg-[#132B52] flex items-center justify-between pl-[1px] pr-[5px] border-b border-white/5 shadow-[0_1px_0_rgba(255,255,255,.03)_inset] sticky top-0 z-[1000]">
    <div class="flex items-center gap-3">
        <a href="{{ route('hr.dashboard') }}" class="block" aria-label="HR dashboard">
            <img src="{{ asset('images/Banner Transparent.png') }}" class="h-[86px] w-auto object-contain block" alt="Nexora ERP">
        </a>
        <x-client-logo :size="64" />
    </div>

    <div class="flex items-center gap-7">
        <nav class="flex items-center gap-px">
            <div class="relative group">
                <a href="{{ route('hr.dashboard') }}"
                   class="{{ $navLink }} {{ $isDashboard ? $navActive : '' }}">
                    Dashboard
                </a>
            </div>

            <div class="relative group">
                <a href="#"
                   class="{{ $navLink }} {{ $isWorkforce ? $navActive : '' }}">
                    Workforce
                    <svg class="w-3.5 h-3.5 opacity-80 transition-transform duration-300 origin-center group-hover:rotate-180 group-hover:opacity-100" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <div class="{{ $dropdownPanel }}">
                    <a href="{{ route('hr.employees.index') }}"
                       class="{{ $dropLink }} {{ $isEmployees ? $dropActive : '' }}">Employee List</a>
                    <a href="{{ route('hr.departments.index') }}"
                       class="{{ $dropLink }} {{ $isDepartments ? $dropActive : '' }}">Department List</a>
                </div>
            </div>

            <div class="relative group">
                <a href="{{ route('hr.onboarding.step1') }}"
                   class="{{ $navLink }} {{ $isOnboarding ? $navActive : '' }}">
                    Employee Onboarding
                </a>
            </div>

            <div class="relative group">
                <a href="#"
                   class="{{ $navLink }} {{ $isReports ? $navActive : '' }}">
                    Reports and Analytics
                    <svg class="w-3.5 h-3.5 opacity-80 transition-transform duration-300 origin-center group-hover:rotate-180 group-hover:opacity-100" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <div class="{{ $dropdownPanel }}">
                    <a href="{{ route('hr.reports-analytics.attendance-overview') }}"
                       class="{{ $dropLink }} {{ $isAttendance ? $dropActive : '' }}">Attendance Record</a>
                    <a href="{{ route('hr.reports-analytics.leave') }}"
                       class="{{ $dropLink }} {{ $isLeave ? $dropActive : '' }}">Leave Record</a>
                </div>
            </div>

            <div class="relative group">
                <a href="#"
                   class="{{ $navLink }}">
                    Employee Management
                    <svg class="w-3.5 h-3.5 opacity-80 transition-transform duration-300 origin-center group-hover:rotate-180 group-hover:opacity-100" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <div class="{{ $dropdownPanel }}">
                 <a href="{{ route('hr.leave-management.index') }}"
   class="{{ $dropLink }} {{ $isLeaveManagement ? $dropActive : '' }}">
    Leave Management
</a>
                    <div class="relative group/tooltip">
                        <span class="{{ $dropLink }} cursor-not-allowed opacity-70 block">
                            Resignation Management
                        </span>

                        <!-- Under Construction Tooltip -->
                        <div
                            class="absolute left-1/2 -translate-x-1/2 top-[115%]
                                   bg-[#132B52]/90 backdrop-blur-md text-[#C9DAF8]
                                   text-[12px] px-3 py-2 rounded-lg
                                   whitespace-nowrap shadow-lg border border-white/10
                                   opacity-0 invisible
                                   transition-all duration-300
                                   group-hover/tooltip:opacity-100
                                   group-hover/tooltip:visible
                                   z-[999]">
                            This page is under construction
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="relative group mr-[15px]">
            <div class="w-9 h-9 rounded-full grid place-items-center bg-white/[.06] shadow-[inset_0_0_0_1px_rgba(255,255,255,.06)] cursor-pointer" aria-label="Profile">
                <svg class="w-9 h-9" viewBox="0 0 36 36" fill="none">
                    <circle cx="18" cy="18" r="17" fill="white" opacity=".97"/>
                    <circle cx="18" cy="13" r="5.2" fill="#223B63"/>
                    <path d="M8.8 28.3C10.7 23.8 14.1 21.7 18 21.7C21.9 21.7 25.3 23.8 27.2 28.3" fill="#223B63"/>
                </svg>
            </div>

            <div class="absolute top-[120%] right-0 left-auto translate-y-2.5 w-[200px] bg-white/[.06] backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_20px_45px_rgba(0,0,0,.35),inset_0_1px_0_rgba(255,255,255,.05)] p-2 opacity-0 invisible transition-all duration-300 z-[999] group-hover:opacity-100 group-hover:visible group-hover:translate-y-0">
                <x-dark-mode-toggle />
                <a href="{{ route('employee.portal') }}"
                   class="flex items-center gap-2 no-underline text-[#C9DAF8] py-2.5 px-3 rounded-[10px] text-[13px] font-semibold transition-all duration-200 hover:bg-white/10 hover:backdrop-blur-md hover:text-[#66A6FF]">
                    <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none">
                        <path d="M3 12l9-8 9 8M5 10.5V20h5v-5h4v5h5v-9.5"
                              stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Employee Dashboard
                </a>
                <form method="POST" action="{{ route('hr.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left bg-none border-none cursor-pointer">
                        <span class="flex items-center gap-2 no-underline text-[#FFB4B4] py-2.5 px-3 rounded-[10px] text-[13px] font-semibold transition-all duration-200 hover:bg-red-500/10 hover:backdrop-blur-md hover:text-[#ff6b6b]">
                            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none">
                                <path d="M15 17l5-5-5-5M20 12H9M13 5H7a2 2 0 00-2 2v10a2 2 0 002 2h6"
                                      stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Logout
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
