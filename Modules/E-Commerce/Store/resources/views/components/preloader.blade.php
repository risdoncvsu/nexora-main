@props([
    'logoUrl' => null,
    'storefrontName' => 'Store',
    'visitKey' => 'storefront_visited',
])

{{--
    Storefront first-visit brand reveal animation.

    CSS dependency: animation keyframes are defined in resources/css/preloader.css.
    The parent view MUST load this CSS via @vite() in its <head> section:
        @vite('Modules/E-Commerce/Store/resources/css/preloader.css')
--}}

@if (!request('preview'))
<div id="preloader" data-visit-key="{{ $visitKey }}" class="fixed inset-0 bg-[#050505] z-[100] flex items-center justify-center transition-opacity duration-1000 ease-in-out">
    <script>
        if (!sessionStorage.getItem(@json($visitKey))) {
            document.write(`
                <div class="relative flex flex-col items-center justify-center">
                    <div class="absolute inset-0 bg-primary/30 blur-3xl rounded-full animate-preloader-glow pointer-events-none"></div>
                    <div class="flex items-center relative z-10">
                        <img src="{{ $logoUrl }}" alt="{{ $storefrontName }} logo" class="h-24 w-auto object-contain animate-logo-reveal animate-logo-glide">
                        <span class="text-4xl md:text-5xl font-black text-white tracking-widest animate-name-reveal">{{ Str::upper($storefrontName) }}</span>
                    </div>
                    <div class="h-0.5 bg-gradient-to-r from-primary/80 via-primary to-primary/80 rounded-full mt-5 w-64 animate-underline-draw"></div>
                    <div class="absolute inset-0 bg-primary/10 blur-2xl rounded-full animate-hold-glow pointer-events-none"></div>
                </div>
            `);
        } else {
            document.write(`
                <div class="w-14 h-14 border-[3px] border-white/10 border-t-primary rounded-full animate-spin shadow-glow-sm"></div>
            `);
        }
    </script>
</div>
@endif
