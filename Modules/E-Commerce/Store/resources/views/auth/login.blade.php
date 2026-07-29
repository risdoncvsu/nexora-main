@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    if (!$storefrontCompany) {
        $storefrontCompany = auth('ecommerce_admin')->user()?->getCompany() ?? auth('ecommerce')->user()?->company ?? \App\Models\Company::first();
    }

    $isPreview = request()->boolean('preview') || auth('ecommerce_admin')->check();
    $publishedLayout = $storefrontCompany 
        ? ($isPreview ? \Modules\Ecommerce\Models\StorefrontLayout::editableFor($storefrontCompany) : \Modules\Ecommerce\Models\StorefrontLayout::publishedFor($storefrontCompany))
        : [];

    $layout = empty($layout) ? $publishedLayout : $layout;

    $storefrontName = $storefrontCompany?->company_name ?: ($layout['brand_name'] ?? 'Nexora Store');
    $store = $storefrontCompany?->ecommerce_slug ?: 'store';
    $logoUrl = $storefrontCompany?->logoUrl() ?: asset('ecommerce/Nexora_Logo.png');
    $storefrontVisitKey = 'storefront_visited_' . ($storefrontCompany?->ecommerce_slug ?: 'store');

    $primaryHex = $layout['primary_color'] ?? '#ff6b00';
    $primaryClean = ltrim($primaryHex, '#');
    if (strlen($primaryClean) === 3) $primaryClean = $primaryClean[0].$primaryClean[0].$primaryClean[1].$primaryClean[1].$primaryClean[2].$primaryClean[2];
    $primaryR = hexdec(substr($primaryClean, 0, 2));
    $primaryG = hexdec(substr($primaryClean, 2, 2));
    $primaryB = hexdec(substr($primaryClean, 4, 2));

    $accentHex = $layout['accent_color'] ?? '#f59e0b';
    $accentClean = ltrim($accentHex, '#');
    if (strlen($accentClean) === 3) $accentClean = $accentClean[0].$accentClean[0].$accentClean[1].$accentClean[1].$accentClean[2].$accentClean[2];
    $accentR = hexdec(substr($accentClean, 0, 2));
    $accentG = hexdec(substr($accentClean, 2, 2));
    $accentB = hexdec(substr($accentClean, 4, 2));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <title>{{ $layout['brand_name'] ?? $storefrontName }} | Sign In</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '{{ $primaryHex }}', hover: '{{ $primaryHex }}CC', glow: 'rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.5)' },
                        accent: '{{ $accentHex }}',
                        dark: { bg: '#050505', surface: '#121212' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #050505;
            color: #ffffff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Ambient Radial Light Blurs */
        .ambient-light-1 {
            position: fixed;
            top: -20%;
            left: -20%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0.35) 0%, rgba({{ $primaryR }}, {{ $primaryG }}, {{ $primaryB }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse1 20s ease-in-out infinite;
        }

        .ambient-light-2 {
            position: fixed;
            top: 35%;
            right: -20%;
            width: 80vw;
            height: 80vw;
            background: radial-gradient(circle, rgba({{ max(0, $primaryR - 100) }}, {{ max(0, $primaryG - 100) }}, {{ max(0, $primaryB - 100) }}, 0.4) 0%, rgba({{ max(0, $primaryR - 100) }}, {{ max(0, $primaryG - 100) }}, {{ max(0, $primaryB - 100) }}, 0) 65%);
            z-index: -1;
            pointer-events: none;
            animation: floatPulse2 25s ease-in-out infinite;
        }

        @keyframes floatPulse1 {
            0% { opacity: 0.3; transform: translate(0, 0) scale(0.8); }
            33% { opacity: 0.8; transform: translate(25vw, 15vh) scale(1.2); }
            66% { opacity: 0.4; transform: translate(-10vw, 30vh) scale(0.9); }
            100% { opacity: 0.3; transform: translate(0, 0) scale(0.8); }
        }

        @keyframes floatPulse2 {
            0% { opacity: 0.8; transform: translate(0, 0) scale(1.1); }
            33% { opacity: 0.3; transform: translate(-25vw, -15vh) scale(0.8); }
            66% { opacity: 0.7; transform: translate(15vw, -25vh) scale(1.3); }
            100% { opacity: 0.8; transform: translate(0, 0) scale(1.1); }
        }
        
        .text-gradient {
            background: linear-gradient(to right, #ffffff, #ffaa66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Preloader animation keyframes loaded from resources/css/preloader.css via @@vite() */
    </style>

    @vite('Modules/E-Commerce/Store/resources/css/preloader.css')
    @vite('Modules/E-Commerce/Store/resources/css/liquidglass.css')
</head>
<body class="relative antialiased selection:bg-primary selection:text-white flex items-center justify-center">

    <x-preloader
        :logoUrl="$logoUrl"
        :storefrontName="$storefrontName"
        :visitKey="$storefrontVisitKey"
    />

    <!-- Background Ambient Effects -->
    <div class="ambient-light-1"></div>
    <div class="ambient-light-2"></div>

    <div class="w-full max-w-md px-6 relative z-10 py-12">
        <!-- Back to Home -->
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors mb-8 group text-sm font-medium">
            <i class="ph ph-arrow-left group-hover:-translate-x-1 transition-transform"></i> Back to Store
        </a>

        <!-- Login Container -->
        <div class="liquid-glass rounded-[2rem] p-8 border border-white/10 shadow-2xl relative overflow-hidden">
            <!-- Subtle internal glow -->
            <div class="absolute inset-0 bg-gradient-to-b from-white/5 to-transparent opacity-50 pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Forms Wrapper -->
                <div id="forms-wrapper" class="relative overflow-hidden transition-all duration-500" style="height: auto;">
                    
                    <!-- Login Form -->
                    <div id="login-container" class="transition-all duration-500 w-full transform translate-x-0 opacity-100">
                        <div class="flex flex-col items-center mb-8">
                            <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center mb-5">
                                <i class="ph-fill ph-lock-simple-open text-2xl text-white"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-white mb-1">Welcome Back</h2>
                            <p class="text-sm text-gray-400 font-light">Sign in to continue to {{ $layout['brand_name'] ?? $storefrontName }}</p>
                        </div>
                        
                        @if (session('success'))
                            <div class="bg-green-500/10 border border-green-500/50 text-green-500 text-xs rounded-xl p-3 mb-4">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if ($errors->any())
                            <div class="bg-red-500/10 border border-red-500/50 text-red-500 text-xs rounded-xl p-3 mb-4">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('ecommerce.login.post') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Email</label>
                                <div class="relative group">
                                    <i class="ph ph-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors text-lg"></i>
                                    <input type="email" name="email" id="email" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm" placeholder="name@example.com" required>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5 ml-1 pr-1">
                                    <label for="password" class="block text-xs font-medium text-gray-300">Password</label>
                                    <a href="#" class="text-[10px] text-primary hover:text-primary-hover transition-colors font-medium">Forgot password?</a>
                                </div>
                                <div class="relative group">
                                    <i class="ph ph-lock-key absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors text-lg"></i>
                                    <input type="password" name="password" id="password" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm" placeholder="••••••••" required>
                                    <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                                        <i class="ph ph-eye text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <label for="remember" class="flex items-center gap-2 mt-2 ml-1 cursor-pointer group w-fit">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" name="remember" id="remember" class="peer appearance-none w-4 h-4 rounded border border-white/20 bg-white/5 checked:bg-primary checked:border-primary transition-all cursor-pointer shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)]">
                                    <i class="ph-bold ph-check absolute text-white text-[10px] opacity-0 peer-checked:opacity-100 pointer-events-none transition-all scale-50 peer-checked:scale-100 duration-200"></i>
                                </div>
                                <span class="text-xs text-gray-400 group-hover:text-gray-300 transition-colors select-none">Remember me for 30 days</span>
                            </label>

                            <button type="submit" class="w-full bg-primary hover:brightness-110 text-white py-4 rounded-xl font-black uppercase tracking-widest transition-all duration-300 shadow-[0_0_20px_rgba({{ $primaryR }},{{ $primaryG }},{{ $primaryB }},0.4)] hover:shadow-[0_0_30px_rgba({{ $primaryR }},{{ $primaryG }},{{ $primaryB }},0.6)] hover:-translate-y-1 mt-4 flex items-center justify-center gap-2 text-sm">
                                Sign In <i class="ph-bold ph-sign-in"></i>
                            </button>
                        </form>

                        <div class="mt-8 mb-6 flex items-center justify-center gap-4">
                            <div class="h-px bg-white/10 flex-1"></div>
                            <span class="text-xs font-medium text-gray-500">Or continue with</span>
                            <div class="h-px bg-white/10 flex-1"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('ecommerce.social.redirect', 'google') }}" class="flex items-center justify-center gap-2 bg-white/25 hover:bg-white/40 border border-white/30 hover:border-white/60 transition-all py-3 rounded-xl text-sm font-bold text-white group shadow-lg shadow-black/30">
                                <i class="ph-fill ph-google-logo text-lg text-gray-300 group-hover:text-white transition-colors"></i> Google
                            </a>
                            <a href="{{ route('ecommerce.social.redirect', 'github') }}" class="flex items-center justify-center gap-2 bg-white/25 hover:bg-white/40 border border-white/30 hover:border-white/60 transition-all py-3 rounded-xl text-sm font-bold text-white group shadow-lg shadow-black/30">
                                <i class="ph-fill ph-github-logo text-lg text-gray-300 group-hover:text-white transition-colors"></i> GitHub
                            </a>
                        </div>

                        <p class="text-center text-xs text-gray-400 mt-8">
                            Don't have an account? <button type="button" id="show-register" class="text-primary hover:text-white font-bold transition-colors">Create one now</button>
                        </p>
                    </div>

                    <!-- Register Form -->
                    <div id="register-container" class="absolute top-0 left-0 w-full transition-all duration-500 transform translate-x-full opacity-0 pointer-events-none">
                        <div class="flex flex-col items-center mb-8">
                            <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center mb-5">
                                <i class="ph-fill ph-user-plus text-2xl text-white"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-white mb-1">Create Account</h2>
                            <p class="text-sm text-gray-400 font-light">Join {{ $layout['brand_name'] ?? $storefrontName }} today</p>
                        </div>

                        <form action="{{ route('ecommerce.register.post') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="reg-email" class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Email</label>
                                <div class="relative group">
                                    <i class="ph ph-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors text-lg"></i>
                                    <input type="email" name="email" id="reg-email" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm" placeholder="name@example.com" required>
                                </div>
                            </div>
                            
                            <div>
                                <label for="reg-password" class="block text-xs font-medium text-gray-300 mb-1.5 ml-1">Password</label>
                                <div class="relative group">
                                    <i class="ph ph-lock-key absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors text-lg"></i>
                                    <input type="password" name="password" id="reg-password" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-white placeholder-gray-500 focus:outline-none focus:border-primary/50 focus:bg-white/10 transition-all text-sm" placeholder="••••••••" required>
                                    <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                                        <i class="ph ph-eye text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <label for="reg-remember" class="flex items-center gap-2 mt-2 ml-1 cursor-pointer group w-fit">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" name="remember" id="reg-remember" class="peer appearance-none w-4 h-4 rounded border border-white/20 bg-white/5 checked:bg-primary checked:border-primary transition-all cursor-pointer shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)]">
                                    <i class="ph-bold ph-check absolute text-white text-[10px] opacity-0 peer-checked:opacity-100 pointer-events-none transition-all scale-50 peer-checked:scale-100 duration-200"></i>
                                </div>
                                <span class="text-xs text-gray-400 group-hover:text-gray-300 transition-colors select-none">Remember me for 30 days</span>
                            </label>

                            <button type="submit" class="w-full bg-primary hover:brightness-110 text-white py-4 rounded-xl font-black uppercase tracking-widest transition-all duration-300 shadow-[0_0_20px_rgba({{ $primaryR }},{{ $primaryG }},{{ $primaryB }},0.4)] hover:shadow-[0_0_30px_rgba({{ $primaryR }},{{ $primaryG }},{{ $primaryB }},0.6)] hover:-translate-y-1 mt-4 flex items-center justify-center gap-2 text-sm">
                                Continue <i class="ph-bold ph-arrow-right"></i>
                            </button>
                        </form>

                        <div class="mt-8 mb-6 flex items-center justify-center gap-4">
                            <div class="h-px bg-white/10 flex-1"></div>
                            <span class="text-xs font-medium text-gray-500">Or sign up with</span>
                            <div class="h-px bg-white/10 flex-1"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('ecommerce.social.redirect', 'google') }}" class="flex items-center justify-center gap-2 bg-white/25 hover:bg-white/40 border border-white/30 hover:border-white/60 transition-all py-3 rounded-xl text-sm font-bold text-white group shadow-lg shadow-black/30">
                                <i class="ph-fill ph-google-logo text-lg text-gray-300 group-hover:text-white transition-colors"></i> Google
                            </a>
                            <a href="{{ route('ecommerce.social.redirect', 'github') }}" class="flex items-center justify-center gap-2 bg-white/25 hover:bg-white/40 border border-white/30 hover:border-white/60 transition-all py-3 rounded-xl text-sm font-bold text-white group shadow-lg shadow-black/30">
                                <i class="ph-fill ph-github-logo text-lg text-gray-300 group-hover:text-white transition-colors"></i> GitHub
                            </a>
                        </div>

                        <p class="text-center text-xs text-gray-400 mt-8">
                            Already have an account? <button type="button" id="show-login" class="text-primary hover:text-white font-bold transition-colors">Sign in</button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preloader & Interactivity Scripts -->
    @vite(['Modules/E-Commerce/Store/resources/js/Common/Preloader.js'])

    <script>
        // Toggle Login/Register Forms
        const loginContainer = document.getElementById('login-container');
        const registerContainer = document.getElementById('register-container');
        const showRegisterBtn = document.getElementById('show-register');
        const showLoginBtn = document.getElementById('show-login');
        const formsWrapper = document.getElementById('forms-wrapper');

        function updateWrapperHeight(targetContainer) {
            formsWrapper.style.height = targetContainer.scrollHeight + 'px';
        }
        
        // Initialize height
        setTimeout(() => updateWrapperHeight(loginContainer), 100);

        showRegisterBtn.addEventListener('click', () => {
            // Hide Login
            loginContainer.classList.remove('translate-x-0', 'opacity-100');
            loginContainer.classList.add('-translate-x-full', 'opacity-0', 'pointer-events-none');
            
            // Show Register
            registerContainer.classList.remove('translate-x-full', 'opacity-0', 'pointer-events-none');
            registerContainer.classList.add('translate-x-0', 'opacity-100');
            
            updateWrapperHeight(registerContainer);
        });

        showLoginBtn.addEventListener('click', () => {
            // Hide Register
            registerContainer.classList.remove('translate-x-0', 'opacity-100');
            registerContainer.classList.add('translate-x-full', 'opacity-0', 'pointer-events-none');
            
            // Show Login
            loginContainer.classList.remove('-translate-x-full', 'opacity-0', 'pointer-events-none');
            loginContainer.classList.add('translate-x-0', 'opacity-100');
            
            updateWrapperHeight(loginContainer);
        });

        // Handle URL parameters to show specific form
        if (window.location.search.includes('register=true')) {
            showRegisterBtn.click();
        }

        // Password Toggle Script
        const togglePasswordBtns = document.querySelectorAll('.toggle-password');
        togglePasswordBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                const icon = btn.querySelector('i');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                icon.className = type === 'password' ? 'ph ph-eye text-lg' : 'ph ph-eye-slash text-lg';
            });
        });
    </script>

</body>
</html>
