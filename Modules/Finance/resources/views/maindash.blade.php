<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora Finance and Accounting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body{
    background:#091d42;
    color:#fff;
}

.container{
    display:flex;
    height:calc(100vh - 128px);
}
    /*==========================
            HEADER
    ===========================*/
        .header {
            height: 128px;
            background: #0B1E3D;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            width: 100%;
            border-bottom: 2px solid #1B3A6B;
        }

        /* LEFT LOGO */
        .nexora-logo {
            display: block;
            margin: 16px 0;

            height: 96px;
            transition: .3s ease;
        }
        .header-brand {
            display:flex;
            align-items:center;
            gap:12px;
            margin-left:16px;
            flex-shrink:0;
        }

        .nexora-logo:hover {
            transform: scale(1.02);
        }

        .nexora-logo img {
            height: 100%;
            object-fit: contain;
            transition: .3s ease;
        }

        .nexora-logo:hover img {
            filter: drop-shadow(0 8px 20px rgba(0,0,0,.25));
        }
        .profile-trigger{
            width:36px;
            height:36px;
            margin-right:40px;
            border:1px solid rgba(255,255,255,.24);
            border-radius:50%;
            background:rgba(74,158,232,.16);
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            transition:transform .2s ease, background .2s ease, border-color .2s ease;
        }
        .profile-trigger:hover,
        .profile-trigger:focus-visible{
            transform:scale(1.05);
            background:rgba(74,158,232,.32);
            border-color:#78b9f1;
            outline:none;
        }
        .profile-trigger svg{ width:25px; height:25px; }
        .profile-dropdown{
            position:absolute;
            top:88px;
            right:24px;
            width:min(320px, calc(100vw - 32px));
            padding:22px;
            border:1px solid rgba(74,158,232,.28);
            border-radius:18px;
            background:#eef5ff;
            box-shadow:0 16px 40px rgba(0,0,0,.36);
            color:#172033;
            opacity:0;
            visibility:hidden;
            transform:translateY(-8px);
            pointer-events:none;
            transition:opacity .2s ease, transform .2s ease, visibility .2s ease;
            z-index:1000;
        }
        .profile-dropdown.open{ opacity:1; visibility:visible; transform:translateY(0); pointer-events:auto; }
        .profile-dropdown-close{
            position:absolute; top:8px; right:12px; border:0; background:transparent;
            color:#64748b; font-size:24px; line-height:1; cursor:pointer;
        }
        .profile-dropdown-email{ margin:0 28px 4px; text-align:center; color:#1f2937; font-size:13px; font-weight:700; overflow-wrap:anywhere; }
        .profile-dropdown-name{ margin:0 28px 18px; text-align:center; color:#64748b; font-size:12px; }
        .profile-dropdown-avatar{
            width:72px; height:72px; margin:0 auto 10px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            background:#0b1e3d; color:#fff; border:4px solid #fff; box-shadow:0 4px 12px rgba(11,30,61,.18);
        }
        .profile-dropdown-avatar svg{ width:34px; height:34px; }
        .profile-logout-button{
            width:100%; display:flex; align-items:center; gap:10px; padding:12px 10px;
            border:0; border-radius:10px; background:transparent; color:#c62828;
            font:600 14px 'Inter',sans-serif; text-align:left; cursor:pointer;
        }
        .profile-logout-button:hover{ background:rgba(198,40,40,.08); }
        .profile-logout-button svg{ width:18px; height:18px; }



/*==========================
            SPLASH SCREEN
        ===========================*/
        #splash {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            z-index: 99999;
            transition: opacity .6s ease;
        }

        .circle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #0B1E3D;
            border-radius: 50%;
            animation: spread .5s ease-out forwards;
        }

        @keyframes spread {
            0% { transform: scale(0); }
            100% { transform: scale(350); }
        }

        .brand {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }

        .logo {
            width: 132px;
            height: 132px;
            opacity: 0;
            transform: scale(0) rotate(0deg);
            animation: logoIntro 0.5s ease forwards 0.8s, logoMove .8s ease forwards 2s;
        }

        @keyframes logoIntro {
            0% { opacity: 0; transform: scale(0) rotate(0deg); }
            100% { opacity: 1; transform: scale(1) rotate(360deg); }
        }

        @keyframes logoMove {
            from { transform: translateX(0); }
            to { transform: translateX(-170px); }
        }

        .banner {
            position: absolute;
            margin-left: 175px;
            width: 0;
            opacity: 0;
            transform: translateX(-80px);
            animation: bannerReveal .8s ease forwards 2.25s;
        }

        @keyframes bannerReveal {
            0% { width: 0; opacity: 0; transform: translateX(-150px); }
            100% { width: 420px; opacity: 1; transform: translateX(10px); }
        }

        /*==========================
            SIDEBAR
        ===========================*/
        .sidebar{
        width:355px;
        background:#0d2147;
        padding-LEFT:41px;
        padding-right:41px;
        border-right:1px solid rgba(255,255,255,.08);
        }
        .menu{
        list-style:none;
        margin-top:35px;
        }

        .menu li{
        display:flex;
        align-items:center;
        gap:10px;
        width:278px;
        height:61px;
        padding:10px 12px;
        border-radius:10px;
        cursor:pointer;
        color:#8A94A6;
        font-size:20px;
        font-family:'Inter', sans-serif;
        transition:.2s;
        }

        /* Hover */
        .menu li:hover{
            background:#4A9EE8;
            color:#fff;
        }

        /* Selected */
        .menu li.active{
            background:#4A9EE8;
            color:#fff;
        }

        .menu li.active .dash-icon,
        .menu li:hover .dash-icon{
            color:#fff;
        }
        .menu hr{
            margin:18px 0;
            border:.5px solid rgba(255,255,255,.08);
        }
        .main{
            flex:1;
            overflow:hidden;
            background:#0B1E3D;
        }

        #contentFrame{
            width:100%;
            height:100%;
            background:#0B1E3D;
        }

        .dash-icon{
            width: 30px !important;
            height: 30px !important;
            color: #1659A0;
            transition: 0.1s ease;
        }

     /*==========================
        MAIN PAGE LAYOUT
     ===========================*/
        .main-wrapper {
            opacity: 0;
            animation: showPage .8s ease forwards 4.1s;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        @keyframes showPage {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }


    </style>
</head>

<body>

    <!-- Splash Screen -->
    <div id="splash">
        <div class="circle"></div>
        <div class="brand">
            <img src="{{ asset('finance/images/Nexora_Logo_Transparent.png') }}" class="logo" alt="Logo">
            <img src="{{ asset('finance/images/Banner Name White.png') }}" class="banner" alt="Banner">
        </div>
    </div>

        <!-- Top Navigation -->
        <header class="header">
            <div class="header-brand">
                <a href="{{ route('finance.dashboard') }}" class="nexora-logo" id="headerLogoBtn">
                    <img src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora Logo">
                </a>
                <x-client-logo :size="64" />
            </div>

        <button type="button" class="profile-trigger" id="profileTrigger" aria-label="Open profile menu" aria-expanded="false">
            <x-heroicon-s-user-circle />
        </button>
        <div id="profileDropdown" class="profile-dropdown" role="dialog" aria-label="Profile menu">
            <button type="button" class="profile-dropdown-close" id="profileDropdownClose" aria-label="Close profile menu">&times;</button>
            <div class="profile-dropdown-email">{{ session('employee_email', 'Employee') }}</div>
            <div class="profile-dropdown-avatar"><x-heroicon-s-user-circle /></div>
            <p class="profile-dropdown-name">Hi, {{ session('employee_name', 'User') }}!</p>
            <x-dark-mode-toggle />
            <a href="{{ route('employee.portal') }}" class="profile-logout-button" style="display:flex;text-decoration:none;color:#0b1e3d;">Employee Portal</a>
            <form method="POST" action="{{ action([\App\Http\Controllers\AuthController::class, 'logout']) }}">
                @csrf
                <button type="submit" class="profile-logout-button"><x-heroicon-o-arrow-right-on-rectangle /> Log out</button>
            </form>
        </div>
</header>


<script>
    const SPLASH_DURATION = 4300;
    const splash = document.getElementById("splash");

    // 1. Hide splash screen after initial load
    setTimeout(() => {
        splash.style.opacity = "0";
        splash.style.pointerEvents = "none";
    }, SPLASH_DURATION);

    // 2. Smooth, fast fade-out for exiting the page
    function smoothExit(e, url) {
        e.preventDefault();

        // Create a quick white fade overlay
        const fader = document.createElement('div');
        fader.style.position = 'fixed';
        fader.style.inset = '0';
        fader.style.background = 'white';
        fader.style.opacity = '0';
        fader.style.transition = 'opacity 0.4s ease';
        fader.style.zIndex = '999999';
        document.body.appendChild(fader);

        // Trigger browser reflow to ensure the transition plays
        void fader.offsetWidth;
        fader.style.opacity = '1';

        // Redirect quickly after the screen goes white
        setTimeout(() => {
            window.location.href = url;
        }, 400);
    }

    // Attach the new smooth exit to your links
    // (Note: This checks if the buttons exist first, so it works safely on both pages)

    const profileTrigger = document.getElementById('profileTrigger');
    const profileDropdown = document.getElementById('profileDropdown');
    const profileDropdownClose = document.getElementById('profileDropdownClose');
    const closeProfileDropdown = () => {
        profileDropdown?.classList.remove('open');
        profileTrigger?.setAttribute('aria-expanded', 'false');
    };
    profileTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = profileDropdown?.classList.toggle('open') ?? false;
        profileTrigger.setAttribute('aria-expanded', String(open));
    });
    profileDropdownClose?.addEventListener('click', closeProfileDropdown);
    document.addEventListener('click', (event) => {
        if (!profileDropdown?.contains(event.target) && !profileTrigger?.contains(event.target)) {
            closeProfileDropdown();
        }
    });
//this part loads the content on the pain
function loadPage(page){
    document.getElementById("contentFrame").src = page;
}
function changePage(element, page) {

    // Remove active from every menu item
    document.querySelectorAll(".menu li").forEach(item => {
        item.classList.remove("active");
    });

    // Highlight the clicked one
    element.classList.add("active");

    // Load page into iframe (if using one)
    document.getElementById("contentFrame").src = page;
}

</script>

<div class="container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <ul class="menu">
            <li id="dashboardBtn" class="active" onclick="changePage(this, '{{ route('finance.overview') }}')"><x-heroicon-s-squares-2x2 class="dash-icon" /><span>Dashboard</span></li>
            <li  onclick="changePage(this, '{{ route('finance.invoicedash') }}')"><x-heroicon-s-chart-bar class="dash-icon" /><span>Invoice</span></li>
            <li  onclick="changePage(this, '{{ route('finance.expensesdash') }}')"><x-heroicon-s-credit-card class="dash-icon" /><span>Expenses</span></li>
            <li  onclick="changePage(this, '{{ route('finance.salesdash') }}')"><x-heroicon-s-shopping-cart class="dash-icon" /><span>Sales</span></li>
            <li  onclick="changePage(this, '{{ route('finance.cashflowdash') }}')"><x-heroicon-s-banknotes class="dash-icon" /><span>Cash Flow</span></li>
            <li  onclick="changePage(this, '{{ route('finance.accountsdash') }}')"><x-heroicon-s-scale class="dash-icon" /><span>Accounts</span></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main">


    <iframe
        id="contentFrame"
        name="contentFrame"
        src="{{ route('finance.overview') }}" >
    </iframe>



    </main>

</div>



</body>
</html>
