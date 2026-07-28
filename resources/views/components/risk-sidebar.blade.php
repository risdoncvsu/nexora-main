@props(['section' => 'register'])

@php
    $links = [
        'register' => ['label' => 'Risk Register', 'route' => route('client.itsm.risk')],
        'mitigation' => ['label' => 'Mitigation Plans', 'route' => route('client.itsm.risk.mitigation')],
        'incident' => ['label' => 'Incident Report', 'route' => route('client.itsm.risk.incident')],
        'analytics' => ['label' => 'Risk Analytics', 'route' => route('client.itsm.risk.analytics')],
    ];
@endphp

<aside class="self-start min-h-[calc(100vh-10rem)] rounded-[1.875rem] bg-white p-5 text-slate-950 sm:p-8">
    <nav class="flex flex-wrap gap-x-6 gap-y-3 text-base sm:text-xl xl:block xl:space-y-6">
        @foreach ($links as $key => $link)
            <a href="{{ $link['route'] }}" class="block {{ $section === $key ? 'font-extrabold text-slate-950' : 'font-medium text-slate-700 hover:text-[#346DCB]' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
