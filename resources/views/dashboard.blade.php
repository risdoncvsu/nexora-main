@php
    $clientLocales = $clientLocales ?? config('client_locales.countries', []);
    $clientTimezones = collect($clientLocales)->pluck('timezone')->filter()->unique()->values();
    $selectedCountry = old('country_code', 'PH');
    $selectedTimezone = old('timezone', $clientLocales[$selectedCountry]['timezone'] ?? 'UTC');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | Registration</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="itsm-workspace min-h-screen font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header
            :home-route="route('admin.itsm.registration')"
            active="registration"
            :nav-items="[
                ['label' => 'Registration', 'route' => route('admin.itsm.registration'), 'key' => 'registration'],
                ['label' => 'Client Management', 'route' => route('admin.itsm.clients'), 'key' => 'clients'],
                ['label' => 'Service Desk', 'route' => route('admin.itsm.service-desk'), 'key' => 'service-desk'],
                ['label' => 'Audit Trail', 'route' => route('admin.itsm.audit-trail'), 'key' => 'audit-trail'],
            ]"
        />

        <main class="relative flex flex-1 items-start justify-center px-4 py-6 lg:px-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="itsm-watermark pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">

            <section class="itsm-density-panel relative z-10 grid w-full max-w-6xl gap-6 bg-white p-5 text-slate-950 lg:grid-cols-[.72fr_1.28fr] lg:p-7">
                <div class="border-b border-slate-200 pb-5 lg:border-b-0 lg:border-r lg:pb-0 lg:pr-7">
                    <p class="text-xs font-bold uppercase tracking-wider text-[#346DCB]">Root administration</p>
                    <h1 class="mt-2 text-3xl font-bold">Register a new company</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Create the client account, set its locale, and generate its system-admin access in one workspace.</p>
                </div>

                <div>

                @if ($errors->any())
                    <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('admin.itsm.registration.store') }}" method="POST" class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2">
                    @csrf

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Company Name</span>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Type here.." class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Industry</span>
                        <select name="industry" class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none">
                            <option value="" disabled selected hidden>Please Select</option>
                            <option value="tech" @selected(old('industry') === 'tech')>Technology</option>
                            <option value="finance" @selected(old('industry') === 'finance')>Finance</option>
                            <option value="retail" @selected(old('industry') === 'retail')>Retail</option>
                            <option value="manufacturing" @selected(old('industry') === 'manufacturing')>Manufacturing</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Company E-mail</span>
                        <input type="email" name="company_email" value="{{ old('company_email') }}" placeholder="sample@company.com" class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Phone No.</span>
                        <input type="text" name="phone_no" id="phone_no" value="{{ old('phone_no') }}" placeholder="Select a country to prefill its dial code" class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Country / Region</span>
                        <select name="country_code" id="country_code" required class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none">
                            @foreach ($clientLocales as $code => $locale)
                                <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $locale['name'] }} ({{ $locale['dial_code'] ?: 'no default prefix' }})</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Client Time Zone</span>
                        <select name="timezone" id="timezone" required class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none">
                            @foreach ($clientTimezones as $timezone)
                                <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-xs font-semibold text-slate-700">Admin Name</span>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" placeholder="Type here.." class="h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400">
                    </label>

                    <div class="flex justify-end pt-2 md:col-span-2">
                        <button type="submit" class="rounded-md bg-[#132B52] px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#0B1E3D]">Register company</button>
                    </div>
                </form>
                </div>
            </section>
        </main>
    </div>
    <script>
        const clientLocales = @json($clientLocales);
        const countryField = document.getElementById('country_code');
        const phoneField = document.getElementById('phone_no');
        const timezoneField = document.getElementById('timezone');
        let autoPhonePrefix = '';

        const applyCountryLocale = () => {
            const locale = clientLocales[countryField.value] || {};
            const prefix = locale.dial_code || '';
            if (!phoneField.value.trim() || phoneField.value.trim() === autoPhonePrefix) {
                phoneField.value = prefix;
                autoPhonePrefix = prefix;
            }
            if (locale.timezone) timezoneField.value = locale.timezone;
        };
        countryField?.addEventListener('change', applyCountryLocale);
        if (!phoneField.value.trim()) applyCountryLocale();
    </script>
</body>
</html>
