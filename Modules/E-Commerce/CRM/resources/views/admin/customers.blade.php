@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $search = request('search');
    $selectedSegment = request('segment_id');
    $selectedSource = request('source');
    $selectedTier = request('tier');

    $tierColors = [
        'bronze' => '#CD7F32',
        'silver' => '#A0AEC0',
        'gold' => '#F59E0B',
        'platinum' => '#718096',
    ];

    $tierIcons = [
        'bronze' => 'ph-medal',
        'silver' => 'ph-medal',
        'gold' => 'ph-medal',
        'platinum' => 'ph-crown',
    ];
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Customers — CRM — ' . $companyName)

@section('content')
<div style="max-width:1200px; margin:0 auto;">
    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Customers</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">{{ number_format($customers->total()) }} total</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:24px; align-items:end;">
        <div style="flex:1; min-width:220px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Search</label>
            <input type="text" name="search" placeholder="Name, email or phone..." value="{{ $search }}" style="margin-top:2px;">
        </div>
        <div style="min-width:160px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Segment</label>
            <select name="segment_id" style="margin-top:2px;">
                <option value="">All Segments</option>
                @foreach($segments as $seg)
                    <option value="{{ $seg->id }}" {{ $selectedSegment == $seg->id ? 'selected' : '' }}>{{ $seg->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:140px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Source</label>
            <select name="source" style="margin-top:2px;">
                <option value="">All Sources</option>
                @foreach(['direct','social','referral','lead','organic'] as $src)
                    <option value="{{ $src }}" {{ $selectedSource == $src ? 'selected' : '' }}>{{ ucfirst($src) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:130px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Tier</label>
            <select name="tier" style="margin-top:2px;">
                <option value="">All Tiers</option>
                <option value="none" {{ $selectedTier === 'none' ? 'selected' : '' }}>No Tier</option>
                <option value="bronze" {{ $selectedTier === 'bronze' ? 'selected' : '' }}>Bronze</option>
                <option value="silver" {{ $selectedTier === 'silver' ? 'selected' : '' }}>Silver</option>
                <option value="gold" {{ $selectedTier === 'gold' ? 'selected' : '' }}>Gold</option>
                <option value="platinum" {{ $selectedTier === 'platinum' ? 'selected' : '' }}>Platinum</option>
            </select>
        </div>
        <button type="submit" class="button" style="margin-top:18px;">Filter</button>
        @if($search || $selectedSegment || $selectedSource || $selectedTier)
            <a href="{{ route('ecommerce.admin.crm.customers') }}" class="button alt" style="margin-top:18px;">Clear</a>
        @endif
    </form>

    <!-- Customers Table -->
    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Spent</th>
                    <th>Orders</th>
                    <th>AOV</th>
                    <th>Last Purchase</th>
                    <th>Tier</th>
                    <th>Tags</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td style="font-weight:600;">
                            <a href="{{ route('ecommerce.admin.crm.customers.show', $customer->id) }}" style="color:var(--c-primary); text-decoration:none;">
                                {{ $customer->full_name }}
                            </a>
                        </td>
                        <td style="color:var(--c-text-muted); font-size:13px;">{{ $customer->email ?? '—' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($customer->total_spent, 0) }}</td>
                        <td>{{ $customer->order_count }}</td>
                        <td>₱{{ number_format($customer->average_order_value, 0) }}</td>
                        <td style="font-size:13px; color:var(--c-text-muted);">
                            @if($customer->last_purchase_at)
                                {{ $customer->last_purchase_at->diffForHumans() }}
                            @else
                                <span style="color:#aaa;">Never</span>
                            @endif
                        </td>
                        <td>
                            @if($customer->tier && $customer->tier !== 'none')
                                @php
                                    $tc = $tierColors[$customer->tier] ?? '#6B7280';
                                    $icon = $tierIcons[$customer->tier] ?? 'ph-medal';
                                @endphp
                                <span style="display:inline-flex; align-items:center; gap:4px; background:{{ $tc }}15; color:{{ $tc }}; border:1px solid {{ $tc }}30; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.3px;">
                                    <i class="ph-fill {{ $icon }}" style="font-size:12px;"></i>
                                    {{ $customer->tier }}
                                </span>
                            @else
                                <span style="color:#bbb; font-size:12px;">—</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                @forelse($customer->tags->take(2) as $tag)
                                    <span style="display:inline-block; background:{{ $tag->color }}22; color:{{ $tag->color }}; border:1px solid {{ $tag->color }}44; border-radius:4px; padding:1px 8px; font-size:11px; font-weight:600;">
                                        {{ $tag->name }}
                                    </span>
                                @empty
                                    <span style="color:#bbb; font-size:12px;">—</span>
                                @endforelse
                                @if($customer->tags->count() > 2)
                                    <span style="font-size:11px; color:#888;">+{{ $customer->tags->count() - 2 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('ecommerce.admin.crm.customers.show', $customer->id) }}" class="button alt" style="padding:4px 12px; font-size:12px;">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:40px; color:#aaa;">
                            <i class="ph ph-users" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.4;"></i>
                            No customers yet. Customers are created automatically when orders are placed.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top:20px;">
        {{ $customers->links() }}
    </div>
</div>
@endsection
