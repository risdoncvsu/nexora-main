<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Record</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Google Font: Inter -->
    
    <style type="text/css">@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/latin-ext/opsz/normal.woff2);unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF;font-display:swap;}@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/cyrillic/opsz/normal.woff2);unicode-range:U+0301,U+0400-045F,U+0490-0491,U+04B0-04B1,U+2116;font-display:swap;}@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/greek-ext/opsz/normal.woff2);unicode-range:U+1F00-1FFF;font-display:swap;}@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/vietnamese/opsz/normal.woff2);unicode-range:U+0102-0103,U+0110-0111,U+0128-0129,U+0168-0169,U+01A0-01A1,U+01AF-01B0,U+0300-0301,U+0303-0304,U+0308-0309,U+0323,U+0329,U+1EA0-1EF9,U+20AB;font-display:swap;}@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/greek/opsz/normal.woff2);unicode-range:U+0370-0377,U+037A-037F,U+0384-038A,U+038C,U+038E-03A1,U+03A3-03FF;font-display:swap;}@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/latin/opsz/normal.woff2);unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD;font-display:swap;}@font-face {font-family:Inter;font-style:normal;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/cyrillic-ext/opsz/normal.woff2);unicode-range:U+0460-052F,U+1C80-1C8A,U+20B4,U+2DE0-2DFF,U+A640-A69F,U+FE2E-FE2F;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/latin-ext/opsz/italic.woff2);unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/greek-ext/opsz/italic.woff2);unicode-range:U+1F00-1FFF;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/cyrillic-ext/opsz/italic.woff2);unicode-range:U+0460-052F,U+1C80-1C8A,U+20B4,U+2DE0-2DFF,U+A640-A69F,U+FE2E-FE2F;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/latin/opsz/italic.woff2);unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/vietnamese/opsz/italic.woff2);unicode-range:U+0102-0103,U+0110-0111,U+0128-0129,U+0168-0169,U+01A0-01A1,U+01AF-01B0,U+0300-0301,U+0303-0304,U+0308-0309,U+0323,U+0329,U+1EA0-1EF9,U+20AB;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/greek/opsz/italic.woff2);unicode-range:U+0370-0377,U+037A-037F,U+0384-038A,U+038C,U+038E-03A1,U+03A3-03FF;font-display:swap;}@font-face {font-family:Inter;font-style:italic;font-weight:100 900;src:url(/cf-fonts/v/inter/5.2.8/cyrillic/opsz/italic.woff2);unicode-range:U+0301,U+0400-045F,U+0490-0491,U+04B0-04B1,U+2116;font-display:swap;}</style>

    <!-- Tailwind CSS -->
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

    <!-- The handful of things Tailwind utilities genuinely can't express
         (webkit autofill pseudo-state, custom select caret) stay as raw CSS -->
    <style>
        .search-box input:-webkit-autofill,
        .search-box input:-webkit-autofill:hover,
        .search-box input:-webkit-autofill:focus,
        .search-box input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #0B1E3D inset !important;
            -webkit-text-fill-color: #fff !important;
            transition: background-color 9999s ease-in-out 0s;
            color: #fff !important;
            font-size: 11px !important;
        }

        .filter-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 500;
            background: rgba(255,255,255,.06);
            color: #93abd3;
        }

        /* Pagination links rendered by Laravel's paginator (Tailwind view) */
        .pagination-wrap nav > div:first-child {
            display: none;
        }
    </style>
</head>

<body class="font-sans bg-[#18386d] text-white m-0 p-0">

    <!-- =====================================================
            TOP NAVBAR
        ====================================================== -->
    @include('partials.navbar')

    <div class="w-[96.82%] max-w-[1859px] mx-auto" data-ajax-list>
        
     <div class="w-full min-h-[60px] bg-[none] rounded-[14px] px-0 py-5 mb-4 flex items-center justify-between gap-4 flex-wrap">

    <form method="GET" action="{{ route('hr.reports-analytics.leave') }}" class="flex items-center gap-3 flex-wrap" id="filterForm">
        @if (request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif
        <div class="search-box w-[487px] h-[45px] bg-[#0B1E3D] rounded-lg flex items-center px-3 opacity-70">
                <i class="fa-solid fa-magnifying-glass text-[#9db5db] mr-2 text-[0.6875rem]"></i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search employees by ID or name"
                    class="w-full h-full bg-transparent border-none outline-none text-white text-[0.6875rem] placeholder:text-[#93abd3]"
                    autocomplete="off">
            </div>

            <div class="relative w-[220px] flex-none">
                <select
                    name="department"
                    class="filter-select w-[220px] h-[45px] bg-[#0B1E3D] opacity-70 text-[#93abd3] border-none outline-none rounded-lg pl-3.5 pr-8 text-[0.6875rem] cursor-pointer">

                    <option value="">All Departments</option>
                    <option value="Business Intelligence" {{ request('department') == 'Business Intelligence' ? 'selected' : '' }}>Business Intelligence</option>
                    <option value="E-commerce" {{ request('department') == 'E-commerce' ? 'selected' : '' }}>E-commerce</option>
                    <option value="Finance" {{ request('department') == 'Finance' ? 'selected' : '' }}>Finance</option>
                    <option value="Human Resources" {{ request('department') == 'Human Resources' ? 'selected' : '' }}>Human Resources</option>
                    <option value="IT Service Management" {{ request('department') == 'IT Service Management' ? 'selected' : '' }}>IT Service Management</option>
                    <option value="Inventory Management" {{ request('department') == 'Inventory Management' ? 'selected' : '' }}>Inventory Management</option>
                    <option value="Order Management" {{ request('department') == 'Order Management' ? 'selected' : '' }}>Order Management</option>
                    <option value="Procurement Management" {{ request('department') == 'Procurement Management' ? 'selected' : '' }}>Procurement Management</option>
                    <option value="Production Management" {{ request('department') == 'Production Management' ? 'selected' : '' }}>Production Management</option>
                </select>
            </div>

     
    </form>

        @php
            $paginator = isset($leaveRequests) ? $leaveRequests : ($employees ?? null);
        @endphp
        @include('partials.per-page-filter', ['perPage' => $paginator ? $paginator->perPage() : 10])
</div>


<!-- Leave stats -->
<div class="grid grid-cols-3 gap-4 mt-6 mb-6">

    <!-- Total Submitted Request -->
    <div class="w-full h-[142px] bg-[#0B1E3D] rounded-[20px] border border-white/[0.08] px-6 flex items-center gap-4 transition-colors duration-200 hover:border-white/[0.15]">
        <div class="w-[64px] h-[64px] rounded-2xl bg-[#3B82F6]/15 flex items-center justify-center flex-none">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
                <path d="M9 12L11 14L15 10" stroke="#60A5FA" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 4H14L18 8V19C18 20.1046 17.1046 21 16 21H7C5.89543 21 5 20.1046 5 19V6C5 4.89543 5.89543 4 7 4Z" stroke="#60A5FA" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="flex flex-col justify-center">
            <div class="text-[14px] font-medium text-[#93ABD3] tracking-wide uppercase mb-1">Total Submitted Request</div>
            <div class="text-[32px] font-bold leading-none text-white employee-counter" data-target="{{ $totalSubmitted ?? 0 }}">0</div>
        </div>
    </div>

    <!-- Pending -->
    <div class="w-full h-[142px] bg-[#0B1E3D] rounded-[20px] border border-white/[0.08] px-6 flex items-center gap-4 transition-colors duration-200 hover:border-white/[0.15]">
        <div class="w-[64px] h-[64px] rounded-2xl bg-[#F59E0B]/15 flex items-center justify-center flex-none">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="1.8">
                <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 7V12L15 14" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="flex flex-col justify-center">
            <div class="text-[14px] font-medium text-[#93ABD3] tracking-wide uppercase mb-1">Pending</div>
            <div class="text-[32px] font-bold leading-none text-white employee-counter" data-target="{{ $pendingCount ?? 0 }}">0</div>
        </div>
    </div>

    <!-- Submitted Today -->
    <div class="w-full h-[142px] bg-[#0B1E3D] rounded-[20px] border border-white/[0.08] px-6 flex items-center gap-4 transition-colors duration-200 hover:border-white/[0.15]">
        <div class="w-[64px] h-[64px] rounded-2xl bg-[#22C55E]/15 flex items-center justify-center flex-none">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="1.8">
                <path d="M8 7V3M16 7V3M4 11H20M6 5H18C19.1046 5 20 5.89543 20 7V19C20 20.1046 19.1046 20 18 20H6C4.89543 20 4 20.1046 4 19V7C4 5.89543 4.89543 5 6 5Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="flex flex-col justify-center">
            <div class="text-[14px] font-medium text-[#93ABD3] tracking-wide uppercase mb-1">Submitted Today</div>
            <div class="text-[32px] font-bold leading-none text-white employee-counter" data-target="{{ $submittedToday ?? 0 }}">0</div>
        </div>
    </div>

</div>

    

        
        

        

        <!-- =========================
            TABLE
        ========================== -->

       <!-- Header -->
<div class="w-full h-[47px] mx-auto mb-3 grid grid-cols-[24%_16%_14%_16%_18%_12%] bg-[#0B1E3D] border border-white/[0.15] rounded-[10px] overflow-hidden">

    <div class="px-[10px] py-[15px] text-center text-[11.9px] font-light uppercase tracking-wide text-white border-r border-white/[0.15]">Employee</div>

    <div class="px-[10px] py-[15px] text-center text-[11.9px] font-light uppercase tracking-wide text-white border-r border-white/[0.15]">Department</div>

    <div class="px-[10px] py-[15px] text-center text-[11.9px] font-light uppercase tracking-wide text-white border-r border-white/[0.15]">File Date</div>

    <div class="px-[10px] py-[15px] text-center text-[11.9px] font-light uppercase tracking-wide text-white border-r border-white/[0.15]">Reason</div>

    <div class="px-[10px] py-[15px] text-center text-[11.9px] font-light uppercase tracking-wide text-white border-r border-white/[0.15]">Status</div>

    <div class="px-[10px] py-[15px] text-center text-[11.9px] font-light uppercase tracking-wide text-white">Action</div>

</div>

        <div data-ajax-list-results class="transition-opacity duration-200">
            @if(isset($leaveRequests))
                @include('employee-management.partials.leave-requests-results')
            @else
                @include('reports-analytics.partials.leave-results')
            @endif
        </div>

</div>

    <script>
        const employeeCounters = document.querySelectorAll('.employee-counter');

        function animateEmployeeCounter(el) {
            const target = parseInt(el.dataset.target, 10) || 0;
            const duration = 1450;
            const start = performance.now();

            function update(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(target * eased);
                el.textContent = current.toLocaleString();
                if (progress < 1) requestAnimationFrame(update);
            }

            requestAnimationFrame(update);
        }

        employeeCounters.forEach((counter) => animateEmployeeCounter(counter));
    </script>

    <script src="{{ asset('js/ajax-list.js') }}" defer></script>

</body>

</html>
