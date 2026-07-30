@props(['section' => 'register'])

@php
    $links = [
        'register' => ['label' => 'Risk Register', 'route' => route('client.itsm.risk')],
        'mitigation' => ['label' => 'Mitigation Plans', 'route' => route('client.itsm.risk.mitigation')],
        'incident' => ['label' => 'Incident Report', 'route' => route('client.itsm.risk.incident')],
        'analytics' => ['label' => 'Risk Analytics', 'route' => route('client.itsm.risk.analytics')],
    ];
@endphp

<aside class="itsm-density-panel self-start bg-white p-3 text-slate-950 xl:sticky xl:top-24">
    <nav class="flex flex-wrap gap-1 text-sm xl:block xl:space-y-1">
        @foreach ($links as $key => $link)
            <a href="{{ $link['route'] }}" class="block rounded-md px-3 py-2.5 {{ $section === $key ? 'bg-[#132B52] font-bold text-white' : 'font-medium text-slate-700 hover:bg-slate-100 hover:text-[#132B52]' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
