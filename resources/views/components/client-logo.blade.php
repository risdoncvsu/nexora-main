@props([
    'clientId' => null,
    'size' => 52,
])

@php
    // The main ITSM companies table is the single source of truth for client
    // branding. Prefer the authenticated ERP session and only fall back to
    // the authenticated client administrator's company.
    $resolvedClientId = (int) (
        $clientId
        ?: session('employee_client_id')
        ?: session('client_id')
        ?: request()->attributes->get('ecommerce_company')?->id
        ?: auth()->user()?->client_id
        ?: auth()->user()?->company_id
    );
    $client = $resolvedClientId > 0
        ? \App\Models\Company::query()->whereKey($resolvedClientId)->first()
        : null;
    $logoUrl = $client?->logoUrl();
@endphp

@if($logoUrl)
    <span class="client-brand-logo" title="{{ $client->company_name }}" aria-label="{{ $client->company_name }} logo"
       style="display:inline-flex;align-items:center;justify-content:center;width:{{ $size }}px;height:{{ $size }}px;flex:0 0 {{ $size }}px;overflow:hidden;">
        <img src="{{ $logoUrl }}" alt="{{ $client->company_name }} logo" style="width:100%;height:100%;display:block;object-fit:contain;" onerror="this.parentElement.style.display='none'">
    </span>
@endif
