@props(['title', 'subtitle', 'section' => 'queue'])

@php
    $links = [
        'queue' => ['label' => 'Nexora Support Queue', 'route' => route('admin.itsm.service-desk')],
        'assigned' => ['label' => 'Assigned Requests', 'route' => route('admin.itsm.service-desk.assigned')],
        'knowledge' => ['label' => 'Knowledge Base', 'route' => route('admin.itsm.service-desk.knowledge-base')],
        'sla' => ['label' => 'SLA Review', 'route' => route('admin.itsm.service-desk.sla-review')],
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
<body class="min-h-screen bg-[#1B365D] font-sans text-white">
    <div class="flex min-h-screen flex-col">
        <x-itsm-header :home-route="route('admin.itsm.registration')" active="service-desk" :nav-items="[
            ['label' => 'Registration', 'route' => route('admin.itsm.registration'), 'key' => 'registration'],
            ['label' => 'Client Management', 'route' => route('admin.itsm.clients'), 'key' => 'clients'],
            ['label' => 'Service Desk', 'route' => route('admin.itsm.service-desk'), 'key' => 'service-desk'],
            ['label' => 'Audit Trail', 'route' => route('admin.itsm.audit-trail'), 'key' => 'audit-trail'],
        ]" />
        <main class="relative flex-1 overflow-hidden p-4 sm:p-6">
            <img src="{{ asset('images/nexora-icon.png') }}" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[64rem] -translate-x-1/2 -translate-y-1/2 opacity-10 blur-sm">
            <section class="relative z-10 grid min-h-[calc(100vh-10rem)] grid-cols-1 gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <aside class="self-start min-h-[calc(100vh-10rem)] rounded-[1.875rem] bg-white p-5 text-slate-950 sm:p-8"><nav class="flex flex-wrap gap-x-6 gap-y-3 text-base sm:text-xl xl:block xl:space-y-6">@foreach ($links as $key => $link)<a href="{{ $link['route'] }}" class="block {{ $section === $key ? 'font-extrabold text-slate-950' : 'font-medium text-slate-700 hover:text-[#346DCB]' }}">{{ $link['label'] }}</a>@endforeach</nav></aside>
                <div class="space-y-6">
                    <div class="rounded-[1.875rem] bg-white/90 px-10 py-8 text-slate-950"><p class="text-sm font-semibold uppercase tracking-wide text-[#346DCB]">Nexora admin portal</p><h1 class="mt-2 text-5xl font-bold">{{ $title }}</h1><p class="mt-3 text-lg text-slate-600">{{ $subtitle }}</p></div>
                    @if ($errors->any())<div class="rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif
                    @if (session('success'))<div class="rounded-md bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>@endif
                    {{ $slot }}
                </div>
            </section>
        </main>
    </div>
</body>
</html>
