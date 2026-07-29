<style>
    html[data-nexora-theme="dark"] { color-scheme: dark; }
    html[data-nexora-theme="dark"] body { background-color: #0f1923 !important; color: #e2e8f0 !important; }
    html[data-nexora-theme="dark"] .bg-white,
    html[data-nexora-theme="dark"] .bg-slate-50,
    html[data-nexora-theme="dark"] .bg-slate-100,
    html[data-nexora-theme="dark"] .profile-dropdown,
    html[data-nexora-theme="dark"] .user-dropdown { background-color: #1a2332 !important; color: #e2e8f0 !important; }
    html[data-nexora-theme="dark"] .border-slate-200,
    html[data-nexora-theme="dark"] .border-slate-300 { border-color: #334155 !important; }
    html[data-nexora-theme="dark"] .text-slate-900,
    html[data-nexora-theme="dark"] .text-slate-800,
    html[data-nexora-theme="dark"] .text-slate-700 { color: #e2e8f0 !important; }
    [data-nexora-theme-toggle] { display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;border:0;background:transparent;padding:10px 12px;border-radius:10px;cursor:pointer;font:inherit;color:inherit;text-align:left; }
    [data-nexora-theme-toggle]:hover { background:rgba(74,158,232,.12); }
    [data-nexora-theme-switch] { position:relative;width:34px;height:20px;border-radius:999px;background:#cbd5e1;transition:background .2s ease;flex:0 0 auto; }
    [data-nexora-theme-switch]::after { content:"";position:absolute;width:14px;height:14px;top:3px;left:3px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform .2s ease; }
    html[data-nexora-theme="dark"] [data-nexora-theme-switch] { background:#346dcb; }
    html[data-nexora-theme="dark"] [data-nexora-theme-switch]::after { transform:translateX(14px); }
</style>

<button type="button" data-nexora-theme-toggle aria-pressed="false">
    <span>Dark mode</span>
    <span data-nexora-theme-switch aria-hidden="true"></span>
</button>

<script>
    (() => {
        const storageKey = 'nexora-interface-theme';
        const root = document.documentElement;
        const apply = (theme) => {
            if (theme === 'dark') root.setAttribute('data-nexora-theme', 'dark');
            else root.removeAttribute('data-nexora-theme');
            document.querySelectorAll('[data-nexora-theme-toggle]').forEach((button) => {
                button.setAttribute('aria-pressed', String(theme === 'dark'));
            });
        };

        try { apply(localStorage.getItem(storageKey) === 'dark' ? 'dark' : 'light'); } catch (_) { apply('light'); }

        document.querySelectorAll('[data-nexora-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const next = root.getAttribute('data-nexora-theme') === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem(storageKey, next); } catch (_) {}
                apply(next);
            });
        });
    })();
</script>
