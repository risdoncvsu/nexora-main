@extends('inventory::layouts.dashboard')

@section('title', 'Transfers')

{{-- Status badge styles moved to the shared inventory.css (single source). --}}

@section('content')
<div class="inv-page">

    <!-- KPI tiles -->
    <div class="kpi-row cols-3">
        <div class="kpi-tile" style="--accent:#4a9ee8;">
            <div class="kpi-head">
                <span class="kpi-label">Total Transfers</span>
                <span class="kpi-icon" style="background:rgba(74,158,232,0.15);color:#4a9ee8;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8l4 4-4 4"/><path d="M7 16l-4-4 4-4"/></svg>
                </span>
            </div>
            <p class="kpi-value">{{ number_format($totalCount) }}</p>
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
        <div class="kpi-tile" style="--accent:#22c55e;">
            <div class="kpi-head">
                <span class="kpi-label">Approved</span>
                <span class="kpi-icon" style="background:rgba(34,197,94,0.15);color:#22c55e;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                </span>
            </div>
            <p class="kpi-value">{{ number_format($approvedCount) }}</p>
        </div>
    </div>

    <!-- Data panel -->
    <div class="data-panel">
        <div class="panel-head">
            <span class="panel-title">Transfer History</span>
            <span class="panel-count">{{ number_format($transfers->total()) }} records</span>
            <div class="panel-head-actions">
                <button type="button" onclick="openTransferModal()" class="inv-btn inv-btn-primary inv-btn-sm">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Transfer
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.stock-transfers') }}" class="data-toolbar">
            <div class="tb-search">
                <svg width="16" height="16" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by Name...">
            </div>
            <select name="status" class="tb-select" onchange="this.form.submit()">
                <option value="">Status</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <select name="from_warehouse" class="tb-select" onchange="this.form.submit()">
                <option value="">From Warehouse</option>
                @foreach ($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ ($filters['from_warehouse'] ?? '') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
            <select name="to_warehouse" class="tb-select" onchange="this.form.submit()">
                <option value="">To Warehouse</option>
                @foreach ($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ ($filters['to_warehouse'] ?? '') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
            </select>
            @if(array_filter($filters ?? []))
                <a href="{{ route('inventory.stock-transfers') }}" class="tb-clear" title="Clear all filters">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </a>
            @endif
        </form>

        <div class="responsive-table" style="min-width:0;">
            <table class="data-grid">
                <thead>
                    <tr>
                        <th>TRF.ID</th>
                        <th>ITEM NAME</th>
                        <th>SKU</th>
                        <th>FROM</th>
                        <th>TO</th>
                        <th class="col-r">QUANTITY</th>
                        <th>STATUS</th>
                        <th>APPROVED BY</th>
                        <th class="col-r">DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td class="cell-muted">{{ $transfer->reference }}</td>
                            <td class="cell-strong">{{ $transfer->item?->name ?? 'Deleted' }}</td>
                            <td class="cell-muted">{{ $transfer->item?->sku ?? '—' }}</td>
                            <td>{{ $transfer->fromWarehouse?->name ?? 'Deleted' }}</td>
                            <td>{{ $transfer->toWarehouse?->name ?? 'Deleted' }}</td>
                            <td class="col-r cell-strong">{{ $transfer->quantity }}</td>
                            <td>
                                <span class="status-badge status-{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span>
                            </td>
                            <td class="cell-muted">{{ trim(($transfer->approver?->first_name ?? '') . ' ' . ($transfer->approver?->last_name ?? '')) ?: '—' }}</td>
                            <td class="col-r cell-muted">{{ $transfer->created_at?->format('M d, Y') ?? '—' }}</td>
                            <td>
                                @error("trf_action_{$transfer->id}")
                                    <p style="color:#ef4444;font-size:11px;margin:0 0 6px 0;">{{ $message }}</p>
                                @enderror
                                @if($transfer->status === 'pending')
                                    <form method="POST" action="{{ route('inventory.stock-transfers.approve', $transfer) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="inv-btn inv-btn-success inv-btn-xs" onclick="nexoraConfirm({title:'Approve Transfer',message:'Approve this transfer? Stock will be moved between warehouses on approval.',confirmText:'Approve',variant:'success',onConfirm:()=>this.closest('form').submit()})">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.stock-transfers.reject', $transfer) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="inv-btn inv-btn-danger inv-btn-xs" onclick="nexoraConfirm({title:'Reject Transfer',message:'Reject this transfer request? This cannot be undone.',confirmText:'Reject',variant:'danger',onConfirm:()=>this.closest('form').submit()})">Reject</button>
                                    </form>
                                    <form method="POST" action="{{ route('inventory.stock-transfers.cancel', $transfer) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="inv-btn inv-btn-neutral inv-btn-xs" onclick="nexoraConfirm({title:'Cancel Transfer',message:'Cancel this transfer request? This cannot be undone.',confirmText:'Cancel Transfer',cancelText:'Keep',variant:'warning',onConfirm:()=>this.closest('form').submit()})">Cancel</button>
                                    </form>
                                @else
                                    <span style="color:#94a3b8;font-size:12px;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="10">No stock transfers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-foot">
            {{ $transfers->links() }}
        </div>
    </div>
</div>

    <div id="transferModal" class="nexora-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="newTransferTitle">
        <div class="nexora-modal nexora-modal-md">
            <div class="nexora-modal-logo"></div>
            <div class="nexora-modal-header">
                <div class="nexora-modal-heading">
                    <span class="nexora-modal-icon nexora-modal-icon-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3"/></svg>
                    </span>
                    <h2 id="newTransferTitle" class="nexora-modal-title">New Stock Transfer</h2>
                </div>
                <button type="button" onclick="closeTransferModal()" class="nexora-modal-close" aria-label="Close">&times;</button>
            </div>

            <form method="POST" action="{{ route('inventory.stock-transfers.store') }}" id="transferForm" novalidate>
                @csrf
                <input type="hidden" name="from_warehouse_id" id="from_warehouse_id" value="{{ old('from_warehouse_id') }}">
                <input type="hidden" name="to_warehouse_id" id="to_warehouse_id" value="{{ old('to_warehouse_id') }}">
                <input type="hidden" name="item_id" id="transfer_item_id" value="{{ old('item_id') }}">

                <div class="nexora-modal-form">
                    <div>
                        <label class="nexora-modal-label">From Warehouse</label>
                        <div class="list-select-search">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" id="fromWhSearch" placeholder="Search source..." oninput="renderFromList()" autocomplete="off">
                        </div>
                        {{-- il-compact keeps this stacked selector modal within the viewport (no scroll when content fits) --}}
                        <div class="item-list il-compact" id="fromWhList"></div>
                        @error('from_warehouse_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">To Warehouse</label>
                        <div class="list-select-search">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" id="toWhSearch" placeholder="Search destination..." oninput="renderToList()" autocomplete="off">
                        </div>
                        <div class="item-list il-compact" id="toWhList"></div>
                        @error('to_warehouse_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="nexora-modal-form-full">
                        <label class="nexora-modal-label">Item</label>
                        <div class="list-select-search">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" id="transferItemSearch" placeholder="Search item or SKU..." oninput="renderTransferItemList()" autocomplete="off">
                        </div>
                        <div class="item-list il-compact" id="transferItemList"></div>
                        @error('item_id')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Quantity</label>
                        <input type="number" name="quantity" id="transfer_quantity" value="{{ old('quantity') }}" min="1" class="nexora-modal-input" placeholder="e.g. 50">
                        <span id="transfer_stock_indicator" style="font-size:11px;color:#90c8ff;display:none;margin-top:4px;"></span>
                        @error('quantity')<p class="nexora-modal-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="nexora-modal-label">Notes (optional)</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="nexora-modal-input" placeholder="Additional details...">
                    </div>
                </div>

                <div class="nexora-modal-actions">
                    <button type="button" onclick="closeTransferModal()" class="nexora-modal-btn-secondary">Cancel</button>
                    <button type="submit" class="nexora-modal-btn-primary">Submit Transfer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const transferModal = document.getElementById('transferModal');
    function openTransferModal() { transferModal.classList.add('open'); }
    function closeTransferModal() { transferModal.classList.remove('open'); }
    transferModal.addEventListener('click', function(e) { if (e.target === this) closeTransferModal(); });

    const warehouses = @json($warehouses);
    const itemsByWarehouse = @json($itemsByWarehouse);
    const stockMap = @json($stockMap ?? []);

    const fromHidden = document.getElementById('from_warehouse_id');
    const toHidden = document.getElementById('to_warehouse_id');
    const itemHidden = document.getElementById('transfer_item_id');
    const fromListEl = document.getElementById('fromWhList');
    const toListEl = document.getElementById('toWhList');
    const itemListEl = document.getElementById('transferItemList');
    const fromSearch = document.getElementById('fromWhSearch');
    const toSearch = document.getElementById('toWhSearch');
    const itemSearch = document.getElementById('transferItemSearch');
    const transferQuantity = document.getElementById('transfer_quantity');
    const transferStockIndicator = document.getElementById('transfer_stock_indicator');

    const CHECK = '<span class="il-check"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str == null ? '' : str));
        return div.innerHTML;
    }

    function warehouseRow(w, selectedId, handler) {
        var sel = String(w.id) === String(selectedId) ? ' selected' : '';
        return '<div class="il-item' + sel + '" data-id="' + w.id + '" onclick="' + handler + '(' + w.id + ')">'
            + '<div><span class="il-name">' + escapeHtml(w.name) + '</span></div>' + CHECK + '</div>';
    }

    function renderFromList() {
        var q = (fromSearch.value || '').toLowerCase();
        var rows = warehouses.filter(function(w){ return w.name.toLowerCase().indexOf(q) !== -1; });
        fromListEl.innerHTML = rows.length
            ? rows.map(function(w){ return warehouseRow(w, fromHidden.value, 'selectFromWarehouse'); }).join('')
            : '<div class="il-empty">No warehouses found</div>';
    }

    function renderToList() {
        var q = (toSearch.value || '').toLowerCase();
        var rows = warehouses.filter(function(w){
            return String(w.id) !== String(fromHidden.value) && w.name.toLowerCase().indexOf(q) !== -1;
        });
        toListEl.innerHTML = rows.length
            ? rows.map(function(w){ return warehouseRow(w, toHidden.value, 'selectToWarehouse'); }).join('')
            : '<div class="il-empty">' + (fromHidden.value ? 'No other warehouses found' : 'Select a source warehouse first') + '</div>';
    }

    function renderTransferItemList() {
        var from = fromHidden.value;
        var q = (itemSearch.value || '').toLowerCase();
        if (!from) { itemListEl.innerHTML = '<div class="il-empty">Select a source warehouse first</div>'; return; }
        var list = (itemsByWarehouse[from] || []).filter(function(i){
            return (i.name || '').toLowerCase().indexOf(q) !== -1 || (i.sku || '').toLowerCase().indexOf(q) !== -1;
        });
        if (!list.length) { itemListEl.innerHTML = '<div class="il-empty">No stock available in this warehouse</div>'; return; }
        itemListEl.innerHTML = list.map(function(i){
            var avail = stockMap[from + '-' + i.id];
            if (avail == null) avail = 0;
            var badgeClass = avail <= 0 ? 'il-out' : 'il-in';
            var sel = String(i.id) === String(itemHidden.value) ? ' selected' : '';
            return '<div class="il-item' + sel + '" data-id="' + i.id + '" onclick="selectTransferItem(' + i.id + ')">'
                + '<div><span class="il-name">' + escapeHtml(i.name) + '</span><span class="il-sku">' + escapeHtml(i.sku) + '</span></div>'
                + '<span class="il-stock ' + badgeClass + '">' + avail + ' avail.</span></div>';
        }).join('');
    }

    function selectFromWarehouse(id) {
        fromHidden.value = id;
        itemHidden.value = '';
        if (String(toHidden.value) === String(id)) toHidden.value = '';
        renderFromList();
        renderToList();
        renderTransferItemList();
        updateTransferIndicator();
    }

    function selectToWarehouse(id) {
        toHidden.value = id;
        renderToList();
    }

    function selectTransferItem(id) {
        itemHidden.value = id;
        renderTransferItemList();
        updateTransferIndicator();
    }

    function getTransferStock() {
        var wh = fromHidden.value, item = itemHidden.value;
        if (wh && item) { var v = stockMap[wh + '-' + item]; return v == null ? null : v; }
        return null;
    }

    function clampTransferQuantity() {
        var stock = getTransferStock();
        if (stock !== null) {
            var val = parseInt(transferQuantity.value);
            if (!isNaN(val) && val > stock) transferQuantity.value = stock;
        }
    }

    function updateTransferIndicator() {
        var stock = getTransferStock();
        if (stock !== null) {
            transferStockIndicator.textContent = 'Stock available: ' + stock;
            transferStockIndicator.style.display = 'block';
        } else {
            transferStockIndicator.style.display = 'none';
        }
        clampTransferQuantity();
    }

    transferQuantity.addEventListener('input', clampTransferQuantity);
    transferQuantity.addEventListener('change', clampTransferQuantity);

    // Initial render also restores any old() selection after a validation error.
    renderFromList();
    renderToList();
    renderTransferItemList();
    updateTransferIndicator();

    document.getElementById('transferForm').addEventListener('submit', function(e){
        if (!fromHidden.value || !itemHidden.value || !toHidden.value) {
            e.preventDefault();
            showToast('Please choose a source warehouse, an item, and a destination.', 'error');
        }
    });

    @if($errors->any())
        openTransferModal();
    @endif
</script>
@endpush
