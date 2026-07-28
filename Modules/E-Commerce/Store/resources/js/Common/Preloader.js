window.addEventListener('load', () => {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        const visitKey = preloader.dataset.visitKey || 'storefront_visited';
        if (!sessionStorage.getItem(visitKey)) {
            sessionStorage.setItem(visitKey, 'true');
            // Wait for the full cinematic animation to play before fading out
            setTimeout(() => {
                preloader.classList.add('opacity-0');
                setTimeout(() => preloader.style.display = 'none', 1000);
            }, 3200);
        } else {
            preloader.classList.add('opacity-0');
            setTimeout(() => preloader.style.display = 'none', 1000);
        }
    }
});
