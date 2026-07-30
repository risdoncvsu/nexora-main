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
    $companyName = trim((string) ($client?->company_name ?: 'Company'));
    $words = preg_split('/\s+/', $companyName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $fallbackMark = strtoupper(substr($words[0] ?? 'C', 0, 1) . substr($words[1] ?? '', 0, 1));
@endphp

@if($client)
    <span class="client-brand-logo" title="{{ $companyName }}" aria-label="{{ $companyName }} logo"
       style="display:inline-flex;align-items:center;justify-content:center;width:{{ $size }}px;height:{{ $size }}px;flex:0 0 {{ $size }}px;overflow:hidden;">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $companyName }} logo" style="width:100%;height:100%;display:block;object-fit:contain;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        @endif
        <span aria-hidden="true" style="{{ $logoUrl ? 'display:none;' : 'display:flex;' }}width:100%;height:100%;align-items:center;justify-content:center;background:#1659A0;color:#fff;font-family:Arial,sans-serif;font-size:{{ max(12, (int) floor($size * 0.36)) }}px;font-weight:700;letter-spacing:0;">
            {{ $fallbackMark }}
        </span>
    </span>
@endif
