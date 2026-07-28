@extends('inventory::layouts.dashboard')

@section('title', 'Adjustments')

{{-- Status badge styles moved to the shared inventory.css (single source). --}}

@section('content')
<div class="inv-page">

    <!-- KPI tiles -->
    <div class="kpi-row cols-3">
        <div class="kpi-tile" style="--accent:#4a9ee8;">
            <div class="kpi-head">
                <span class="kpi-label">Total Adjustments</span>
                <span class="kpi-icon" style="background:rgba(74,158,232,0.15);color:#4a9ee8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                </span>
            </div>
            <p class="kpi-value">{{ number_format($totalCount) }}</p>
        </div>
        <div class="kpi-tile" style="--accent:{{ $netAdjustedUnits >= 0 ? '#22c55e' : '#ef4444' }};">
            <div class="kpi-head">
                <span class="kpi-label">Net Adjusted Units</span>
                <span class="kpi-icon" style="background:{{ $netAdjustedUnits >= 0 ? 'rgba(34,197,94,0.15);color:#22c55e' : 'rgba(239,68,68,0.15);color:#ef4444' }};">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
                </span>
            </div>
            <p class="kpi-value" style="color:{{ $netAdjustedUnits >= 0 ? '#4ade80' : '#f87171' }};">{{ ($netAdjustedUnits >= 0 ? '+' : '') . number_format($netAdjustedUnits) }}</p>
        </div>
        <div class="kpi-tile" style="--accent:#f59e0b;">
            <div class="kpi-head">
                <span class="kpi-label">Pending Approval</span>
                <span class="kpi-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
            </div>
            <p class="kpi-value">{{ number_format($pendingCount) }}</p>
        </div>
    </div>

    <!-- Data panel -->
    <div class="data-panel">
        <div class="panel-head">
            <span class="panel-title">Adjustment History</span>
            <span class="panel-count">{{ number_format($adjustments->total()) }} records</span>
            <div class="panel-head-actions">
                <button type="button" onclick="openAdjustmentModal()" class="inv-btn inv-btn-primary inv-btn-sm">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Adjustment
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.stock-adjustments') }}" class="data-toolbar">
            <div class="tb-search">
                <svg width="16" height="16" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by Name...">
            </div>
            <select name="type" class="tb-select" onchange="this.form.submit()">
                <option value="">Type</option>
                <option value="increase" {{ ($filters['type'] ?? '') === 'increase' ? 'selected' : '' }}>Increase</option>
                <option value="decrease" {{ ($filters['type'] ?? '') === 'decrease' ? 'selected' : '' }}>Decrease</option>
            </select>
            <select name="reason" class="tb-select" onchange="this.form.submit()">
                <option value="">Reason</option>
                <option value="damage" {{ ($filters['reason'] ?? '') === 'damage' ? 'selected' : '' }}>Damage</option>
                <option value="expired" {{ ($filters['reason'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="recount" {{ ($filters['reason'] ?? '') === 'recount' ? 'selected' : '' }}>Recount</option>
                <option value="theft" {{ ($filters['reason'] ?? '') === 'theft' ? 'selected' : '' }}>Theft</option>
                <option value="correction" {{ ($filters['reason'] ?? '') === 'correction' ? 'selected' : '' }}>Correction</option>
            </select>
            <select name="warehouse" class="tb-select" onchange="this.form.submit()">
                <option value="">Warehouse</option>
                @foreach ($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ ($filters['warehouse'] ?? '') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
            <select name="status" class="tb-select" onchange="this.form.submit()">
                <option value="">Status</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            @if(array_filter($filters ?? []))
                <a href="{{ route('inventory.stock-adjustments') }}" class="tb-clear" title="Clear all filters">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </a>
            @endif
        </form>

        <div class="responsive-table" style="min-width:0;">
            <table class="data-grid">
                <thead>
                    <tr>
                        <th class="col-r">ADJ.ID</th>
                        <th>ITEM NAME</th>
                        <th>SKU</th>
                        <th>WAREHOUSE</th>
                        <th class="col-r">QUANTITY</th>
                        <th>TYPE</th>
                        <th>REASON</th>
                        <th>STATUS</th>
                        <th>APPROVED BY</th>
                        <th class="col-r">DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr>
                            <td class="col-r cell-muted">{{ $adjustment->id }}</td>
                            <td class="cell-strong">{{ $adjustment->item?->name ?? 'Deleted' }}</td>
                            <td class="cell-muted">{{ $adjustment->item?->sku ?? '—' }}</td>
                            <td>{{ $adjustment->warehouse?->name ?? 'Deleted' }}</td>
                            <td class="col-r cell-strong">{{ $adjustment->quantity }}</td>
                            <td>{{ ucfirst($adjustment->type) }}</td>
                            <td>{{ ucfirst($adjustment->reason) }}</td>
                            <td>
                                <span class="status-badge status-{{ $adjustment->status }}">{{ ucfirst($adjustment->status) }}</span>
                            </td>
                            <td class="cell-muted">{{ trim(($adjustment->approver?->first_name ?? '') . ' ' . ($adjustment->approver?->last_name ?? '')) ?: '—' }}</td>
                            <td class="col-r cell-muted">{{ $adjustment->created_at?->format('M d, Y') ?? '—' }}</td>
                            <td>
                                @error("adj_action_{$adjustment->id}")
                                    <p style="color:#ef4444;font-size:11px;margin:0 0 6px 0;">{{ $message }}</p>
                                @enderror
                                @if($adjustment->status === 'pending')
                                    <form method="POST" action="{{ route('inventory.stock-adjustments.approve', $adjustment) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="inv-btn inv-btn-success inv-btn-xs" onclick="nexoraConfirm({title:'Approve Adjustment',message:'Approve this adjustment? Stock levels will be updated on approval.',confirmText:'Approve',variant:'success',onConfirm:()=>this.closest('form').submit()})">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.stock-adjustments.reject', $adjustment) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="inv-btn inv-btn-danger inv-btn-xs" onclick="nexoraConfirm({title:'Reject Adjustment',message:'Reject this adjustment request? This cannot be undone.',confirmText:'Reject',variant:'danger',onConfirm:()=>this.closest('form').submit()})">Reject</button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.stock-adjustments.cancel', $adjustment) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="inv-btn inv-btn-neutral inv-btn-xs" onclick="nexoraConfirm({title:'Cancel Adjustment',message:'Cancel this adjustment request? This cannot be undone.',confirmText:'Cancel Adjustment',cancelText:'Keep',variant:'warning',onConfirm:()=>this.closest('form').submit()})">Cancel</button>
                                    </form>
                                @else
                                    <span style="color:#94a3b8;font-size:12px;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="11">No stock adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-foot">
            {{ $adjustments->links() }}
        </div>
    </div>
</div>
    <div id="adjustmentModal" class="nexora-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="newAdjustmentTitle">
        <div class="nexora-modal nexora-modal-md">
            <div class="nexora-modal-logo"></div>
            <div class="nexora-modal-header">
                <div class="nexora-modal-heading">
                    <span class="nexora-modal-icon nexora-modal-icon-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>
                    </span>
                    <h2 id="newAdjustmentTitle" class="nexora-modal-title">New Stock Adjustment</h2>
                </div>
                <button type="button" onclick="closeAdjustmentModal()" class="nexora-modal-close" aria-label="Close">&times;</button>
            </div>

            <form method="POST" action="{{ route('inventory.stock-adjustments.store') }}" id="adjustmentForm" novalidate>
                @csrf
                <input type="hidden" name="warehouse_id" id="adj_warehouse_id" value="{{ old('warehouse_id') }}">
                <input type="hidden" name="item_id" id="adj_item_id" value="{{ old('item_id') }}">
                <input type="hidden" name="type" id="adj_type" value="{{ old('type') }}">
                <input type="hidden" name="reason" id="adj_reason" value="{{ old('reason') }}">

                <div class="nexora-modal-form">
                    {{-- Warehouse + Item share a 2-col row (like Transfer's From/To) so this
                         modal stays short enough not to scroll when its content is fully visible.
                         il-compact caps each list; it still scrolls internally when overflowing. --}}
                    <div>
                        <label class="nexora-modal-label">Warehouse</label>
                        <div class="list-select-search">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" id="adjWhSearch" placeholder="Search warehouse..." oninput="renderAdjWarehouseList()" autocomplete="off">
                        </div>
                        <div class="item-list il-compact" id="adjWhList"></div>
                        @error('warehouse_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Item</label>
                        <div class="list-select-search">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" id="adjItemSearch" placeholder="Search item or SKU..." oninput="renderAdjItemList()" autocomplete="off">
                        </div>
                        <div class="item-list il-compact" id="adjItemList"></div>
                        @error('item_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="nexora-modal-form-full">
                        <label class="nexora-modal-label">Type</label>
                        <div class="type-toggle">
                            <button type="button" id="typeIncreaseBtn" onclick="setAdjType('increase')">Increase</button>
                            <button type="button" id="typeDecreaseBtn" onclick="setAdjType('decrease')">Decrease</button>
                        </div>
                        @error('type')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="nexora-modal-form-full">
                        <label class="nexora-modal-label">Reason</label>
                        <div class="item-list" id="adjReasonList"></div>
                        @error('reason')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Quantity</label>
                        <input type="number" name="quantity" id="adjustment_quantity" value="{{ old('quantity') }}" min="1" class="nexora-modal-input" placeholder="e.g. 50">
                        <span id="stock_indicator" style="font-size:11px;color:#90c8ff;display:none;margin-top:4px;"></span>
                        @error('quantity')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Notes (optional)</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="nexora-modal-input" placeholder="Additional details...">
                    </div>
                </div>

                <div class="nexora-modal-actions">
                    <button type="button" onclick="closeAdjustmentModal()" class="nexora-modal-btn-secondary">Cancel</button>
                    <button type="submit" class="nexora-modal-btn-primary">Submit Adjustment</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const adjModal = document.getElementById('adjustmentModal');
    window.openAdjustmentModal = function() { adjModal.classList.add('open'); };
    window.closeAdjustmentModal = function() { adjModal.classList.remove('open'); };
    if (adjModal) adjModal.addEventListener('click', function(e) { if (e.target === this) window.closeAdjustmentModal(); });

    const warehouses = @json($warehouses);
    const itemsByWarehouse = @json($itemsByWarehouse);
    const stockMap = @json($stockMap);
    const REASONS = [
        { value: 'damage', label: 'Damage' },
        { value: 'expired', label: 'Expired' },
        { value: 'recount', label: 'Recount' },
        { value: 'theft', label: 'Theft' },
        { value: 'correction', label: 'Correction' }
    ];

    const whHidden = document.getElementById('adj_warehouse_id');
    const itemHidden = document.getElementById('adj_item_id');
    const typeHidden = document.getElementById('adj_type');
    const reasonHidden = document.getElementById('adj_reason');
    const whListEl = document.getElementById('adjWhList');
    const itemListEl = document.getElementById('adjItemList');
    const reasonListEl = document.getElementById('adjReasonList');
    const whSearch = document.getElementById('adjWhSearch');
    const itemSearch = document.getElementById('adjItemSearch');
    const typeIncreaseBtn = document.getElementById('typeIncreaseBtn');
    const typeDecreaseBtn = document.getElementById('typeDecreaseBtn');
    const quantityInput = document.getElementById('adjustment_quantity');
    const stockIndicator = document.getElementById('stock_indicator');

    const CHECK = '<span class="il-check"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str == null ? '' : str));
        return div.innerHTML;
    }

    function renderAdjWarehouseList() {
        var q = (whSearch.value || '').toLowerCase();
        var rows = warehouses.filter(function(w){ return w.name.toLowerCase().indexOf(q) !== -1; });
        whListEl.innerHTML = rows.length ? rows.map(function(w){
            var sel = String(w.id) === String(whHidden.value) ? ' selected' : '';
            return '<div class="il-item' + sel + '" data-id="' + w.id + '" onclick="selectAdjWarehouse(' + w.id + ')">'
                + '<div><span class="il-name">' + escapeHtml(w.name) + '</span></div>' + CHECK + '</div>';
        }).join('') : '<div class="il-empty">No warehouses found</div>';
    }

    function renderAdjItemList() {
        var wh = whHidden.value;
        var q = (itemSearch.value || '').toLowerCase();
        if (!wh) { itemListEl.innerHTML = '<div class="il-empty">Select a warehouse first</div>'; return; }
        var list = (itemsByWarehouse[wh] || []).filter(function(i){
            return (i.name || '').toLowerCase().indexOf(q) !== -1 || (i.sku || '').toLowerCase().indexOf(q) !== -1;
        });
        if (!list.length) { itemListEl.innerHTML = '<div class="il-empty">No items in this warehouse</div>'; return; }
        itemListEl.innerHTML = list.map(function(i){
            var avail = stockMap[wh + '-' + i.id];
            if (avail == null) avail = 0;
            var badgeClass = avail <= 0 ? 'il-out' : 'il-in';
            var sel = String(i.id) === String(itemHidden.value) ? ' selected' : '';
            return '<div class="il-item' + sel + '" data-id="' + i.id + '" onclick="selectAdjItem(' + i.id + ')">'
                + '<div><span class="il-name">' + escapeHtml(i.name) + '</span><span class="il-sku">' + escapeHtml(i.sku) + '</span></div>'
                + '<span class="il-stock ' + badgeClass + '">' + avail + ' avail.</span></div>';
        }).join('');
    }

    function renderAdjReasonList() {
        reasonListEl.innerHTML = REASONS.map(function(r){
            var sel = r.value === reasonHidden.value ? ' selected' : '';
            return '<div class="il-item' + sel + '" data-value="' + r.value + '" onclick="selectAdjReason(\'' + r.value + '\')">'
                + '<div><span class="il-name">' + r.label + '</span></div>' + CHECK + '</div>';
        }).join('');
    }

    function selectAdjWarehouse(id) {
        whHidden.value = id;
        itemHidden.value = '';
        renderAdjWarehouseList();
        renderAdjItemList();
        updateIndicator();
    }
    function selectAdjItem(id) {
        itemHidden.value = id;
        renderAdjItemList();
        updateIndicator();
    }
    function selectAdjReason(value) {
        reasonHidden.value = value;
        renderAdjReasonList();
    }
    function setAdjType(type) {
        typeHidden.value = type;
        typeIncreaseBtn.classList.toggle('active', type === 'increase');
        typeDecreaseBtn.classList.toggle('active', type === 'decrease');
        updateIndicator();
    }

    function getCurrentStock() {
        var wh = whHidden.value, item = itemHidden.value;
        if (wh && item) { var v = stockMap[wh + '-' + item]; return v == null ? null : v; }
        return null;
    }
    function clamp() {
        var stock = getCurrentStock();
        if (stock !== null && typeHidden.value === 'decrease') {
            var val = parseInt(quantityInput.value);
            if (!isNaN(val) && val > stock) quantityInput.value = stock;
        }
    }
    function updateIndicator() {
        var stock = getCurrentStock();
        stockIndicator.textContent = stock !== null ? 'Stock available: ' + stock : '';
        stockIndicator.style.display = stock !== null ? 'block' : 'none';
        clamp();
    }

    quantityInput.addEventListener('input', clamp);
    quantityInput.addEventListener('change', clamp);

    // Initial render also restores any old() selection after a validation error.
    if (typeHidden.value) setAdjType(typeHidden.value);
    renderAdjWarehouseList();
    renderAdjItemList();
    renderAdjReasonList();
    updateIndicator();

    document.getElementById('adjustmentForm').addEventListener('submit', function(e){
        if (!whHidden.value || !itemHidden.value || !typeHidden.value || !reasonHidden.value) {
            e.preventDefault();
            showToast('Please choose a warehouse, item, type, and reason.', 'error');
        }
    });

    @if($errors->any())
        window.openAdjustmentModal();
    @endif
</script>
@endpush
