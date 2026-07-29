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
        ?: request()->attributes->get('ecommerce_company')?->id
        ?: auth()->user()?->company_id
    );
    $client = $resolvedClientId > 0
        ? \App\Models\Company::query()->whereKey($resolvedClientId)->first()
        : null;
    $logoUrl = $client?->logoUrl();
@endphp

@if($logoUrl)
    <span class="client-brand-logo" title="{{ $client->company_name }}" aria-label="{{ $client->company_name }} logo"
       style="display:inline-flex;align-items:center;justify-content:center;width:{{ $size }}px;height:{{ $size }}px;flex:0 0 {{ $size }}px;overflow:hidden;border-radius:10px;background:#fff;border:1px solid rgba(255,255,255,.35);box-shadow:0 2px 10px rgba(0,0,0,.16);">
        <img src="{{ $logoUrl }}" alt="{{ $client->company_name }} logo" style="width:100%;height:100%;display:block;object-fit:contain;padding:5px;" onerror="this.parentElement.style.display='none'">
    </span>
@endif
