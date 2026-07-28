<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Contact Us</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            /* Allows natural vertical scrolling, prevents horizontal overflow */
            overflow-x: hidden; 
            min-height: 100vh;
        }

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

        /* FIXED 128PX HEADER */
        .header {
            height: 128px;
            background: #0B1E3D;
            display: flex;
            align-items: center;
            justify-content: space-between; 
            z-index: 100;
            width: 100%;
            position: sticky;
            top: 0;
        }

        /* LEFT LOGO */
        .nexora-logo {
            display: block;
            margin: 16px 0 16px 16px; 

            height: 96px; 
            transition: .3s ease;
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

        /* HEADER CONTACT BUTTON */
        .header-contact-btn {
            margin-right: 32px;
            padding: 12px 24px;
            background: #ffffff;
            color: #0B1E3D;
            border: 2px solid #ffffff;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            transition: .2s;
            cursor: pointer;
        }

        .header-contact-btn:hover {
            background: #E2E8F0;
            border-color: #E2E8F0;
        }

        /* IMAGE CONTAINER & LAYOUT */
        .page-container {
            position: relative;
            width: 100%;
            display: block;
            margin-top: -18vh;
        }

        /* Displays image seamlessly, allowing natural vertical scroll */
        .content-img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* REQUEST A DEMO BUTTON - Positioned absolutely over the image */
        .demo-btn {
            position: absolute;
            top: 89%; /* Centers button vertically under the achievement box */
            left: 74%; /* Centers button horizontally under the achievement box */
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 260px;
            height: 56px;
            background: #ffffff;
            color: #0B1E3D;
            font-size: 18px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 30px; 
            cursor: pointer;
            transition: .2s;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 10;
        }

        .demo-btn:hover {
            background: #E2E8F0;
            transform: translate(-50%, -53%);
            box-shadow: 0 12px 20px rgba(0,0,0,0.3);
        }

        .demo-section {
            scroll-margin-top: 152px;
            background: #f8fafc;
            padding: 72px 24px;
        }

        .demo-card {
            width: min(760px, 100%);
            margin: 0 auto;
            padding: 36px;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 20px 48px rgba(11, 30, 61, .14);
        }

        .demo-card h2 { color: #0B1E3D; font-size: clamp(1.75rem, 4vw, 2.4rem); }
        .demo-card p { margin-top: 10px; color: #475569; line-height: 1.6; }
        .demo-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 28px; }
        .demo-field { display: grid; gap: 7px; color: #0B1E3D; font-size: 14px; font-weight: 700; }
        .demo-field.full { grid-column: 1 / -1; }
        .demo-field input, .demo-field textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 14px; font: inherit; color: #0f172a; }
        .demo-field textarea { min-height: 120px; resize: vertical; }
        .demo-submit { margin-top: 22px; border: 0; border-radius: 999px; padding: 13px 24px; background: #0B1E3D; color: #fff; font: 800 15px 'Inter', sans-serif; cursor: pointer; }
        .demo-submit:hover { background: #1B365D; }
        .form-alert { margin-top: 18px; border-radius: 10px; padding: 12px 14px; font-size: 14px; }
        .form-alert.success { background: #dcfce7; color: #166534; }
        .form-alert.error { background: #fee2e2; color: #991b1b; }

        @media (max-width: 640px) {
            .header { height: 88px; }
            .nexora-logo { height: 58px; margin: 14px; }
            .header-contact-btn { margin-right: 14px; padding: 10px 16px; font-size: 13px; }
            .demo-grid { grid-template-columns: 1fr; }
            .demo-card { padding: 26px 20px; }
        }

    </style>
</head>

<body>

    <!-- Splash Screen -->
    <div id="splash">
        <div class="circle"></div>
        <div class="brand">
            <img src="{{ asset('images/Nexora_Logo_Transparent.png') }}" class="logo" alt="Nexora logo">
            <img src="{{ asset('images/Banner Name White.png') }}" class="banner" alt="Nexora">
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-wrapper">
        
        <!-- Top Navigation -->
        <header class="header">
            <a href="{{ route('login') }}" class="nexora-logo">
                <img src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora Logo">
            </a>
            
            <a href="#request-demo" class="header-contact-btn">Request a Demo</a>
        </header>
        
        <!-- Main Area (Image Container) -->
        <main class="page-container">
            <!-- Full Content Image -->
            <img src="{{ asset('images/contactus.png') }}" alt="Get Started With Our ERP" class="content-img">
            
            <!-- Request a Demo Button -->
            <button type="button" class="demo-btn" data-demo-scroll>Request a Demo</button>
        </main>

        <section id="request-demo" class="demo-section" aria-labelledby="demo-heading">
            <div class="demo-card">
                <h2 id="demo-heading">Request a Nexora ERP demo</h2>
                <p>Tell us a little about your company and the Nexora team will follow up with a demo schedule.</p>

                @if (session('success'))
                    <div class="form-alert success" role="status">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="form-alert error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="demo-grid">
                        <label class="demo-field">Name
                            <input name="name" value="{{ old('name') }}" required autocomplete="name">
                        </label>
                        <label class="demo-field">Work email
                            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                        </label>
                        <label class="demo-field">Company
                            <input name="company_name" value="{{ old('company_name') }}" autocomplete="organization">
                        </label>
                        <label class="demo-field">Phone number
                            <input name="phone" value="{{ old('phone') }}" autocomplete="tel">
                        </label>
                        <label class="demo-field full">What would you like to discuss?
                            <textarea name="message" placeholder="Modules, team size, or a preferred demo time">{{ old('message') }}</textarea>
                        </label>
                    </div>
                    <button class="demo-submit" type="submit">Send demo request</button>
                </form>
            </div>
        </section>
    </div>

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

    document.querySelectorAll('[data-demo-scroll]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('request-demo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

</script>

</body>
</html>
