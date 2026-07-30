@props(['title', 'subtitle', 'section' => 'queue'])

@php
    $links = [
        'queue' => ['label' => 'Nexora Support Queue', 'route' => route('admin.itsm.service-desk')],
        'assigned' => ['label' => 'Assigned Requests', 'route' => route('admin.itsm.service-desk.assigned')],
        'knowledge' => ['label' => 'Knowledge Base', 'route' => route('admin.itsm.service-desk.knowledge-base')],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexora | {{ $title }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="itsm-workspace min-h-screen font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header :home-route="route('admin.itsm.registration')" active="service-desk" :nav-items="[
            ['label' => 'Registration', 'route' => route('admin.itsm.registration'), 'key' => 'registration'],
            ['label' => 'Client Management', 'route' => route('admin.itsm.clients'), 'key' => 'clients'],
            ['label' => 'Service Desk', 'route' => route('admin.itsm.service-desk'), 'key' => 'service-desk'],
            ['label' => 'Audit Trail', 'route' => route('admin.itsm.audit-trail'), 'key' => 'audit-trail'],
        ]" />
        <main class="relative flex-1 p-4 lg:p-5">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="itsm-watermark pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <section class="relative z-10 mx-auto grid max-w-[1600px] grid-cols-1 gap-4 xl:grid-cols-[13.5rem_minmax(0,1fr)]">
                <aside class="itsm-density-panel self-start bg-white p-3 text-slate-950 xl:sticky xl:top-24"><nav class="flex flex-wrap gap-1 text-sm xl:block xl:space-y-1">@foreach ($links as $key => $link)<a href="{{ $link['route'] }}" class="block rounded-md px-3 py-2.5 {{ $section === $key ? 'bg-[#132B52] font-bold text-white' : 'font-medium text-slate-700 hover:bg-slate-100 hover:text-[#132B52]' }}">{{ $link['label'] }}</a>@endforeach</nav></aside>
                <div class="space-y-4">
                    <div class="itsm-density-panel bg-white px-5 py-4 text-slate-950"><p class="text-xs font-bold uppercase tracking-wide text-[#346DCB]">Nexora admin portal</p><h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $title }}</h1><p class="mt-2 text-sm text-slate-600">{{ $subtitle }}</p></div>
                    @if ($errors->any())<div class="rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif
                    @if (session('success'))<div class="rounded-md bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>@endif
                    {{ $slot }}
                </div>
            </section>
        </main>
    </div>
</body>
</html>
