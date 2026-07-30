@extends('ecommerce::admin.layout', ['title' => 'Return & Cancel Requests', 'heading' => 'Return & Cancel Requests'])

@section('content')
<style>
    /* Stats Bar */
    .return-stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }

    .return-stat-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .return-stat-card .stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .return-stat-card .stat-icon.total { background: #eff6ff; color: #2563eb; }
    .return-stat-card .stat-icon.pending { background: #fffbeb; color: #d97706; }
    .return-stat-card .stat-icon.approved { background: #f0fdf4; color: #16a34a; }
    .return-stat-card .stat-icon.rejected { background: #fef2f2; color: #dc2626; }

    .return-stat-card .stat-info .stat-number {
        font-size: 22px;
        font-weight: 700;
        color: var(--c-text);
        line-height: 1.1;
    }

    .return-stat-card .stat-info .stat-label {
        font-size: 12px;
        color: var(--c-text-muted);
        font-weight: 500;
    }

    /* Returns Table */
    .returns-table {
        width: 100%;
        border-collapse: collapse;
    }

    .returns-table thead th {
        padding: 12px 16px;
        font-size: 12px;
        font-weight: 600;
        color: var(--c-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
        white-space: nowrap;
    }

    .returns-table tbody tr {
        transition: background 0.1s;
    }

    .returns-table tbody tr:hover {
        background: #f8fafc;
    }

    .returns-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 14px;
        color: var(--c-text);
        vertical-align: middle;
    }

    /* Type badges */
    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .type-badge.cancel { background: #fef2f2; color: #dc2626; }
    .type-badge.return { background: #fffbeb; color: #d97706; }

    /* Status badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .status-badge.pending { background: #fffbeb; color: #d97706; }
    .status-badge.pending .dot { background: #f59e0b; }
    .status-badge.approved { background: #f0fdf4; color: #16a34a; }
    .status-badge.approved .dot { background: #22c55e; }
    .status-badge.rejected { background: #fef2f2; color: #dc2626; }
    .status-badge.rejected .dot { background: #ef4444; }
    .status-badge.refunded { background: #f5f3ff; color: #7c3aed; }
    .status-badge.refunded .dot { background: #8b5cf6; }
    .status-badge.completed { background: #f0fdf4; color: #059669; }
    .status-badge.completed .dot { background: #059669; }

    /* Action buttons */
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
    }

    .action-btn.primary {
        background: #2563eb;
        color: #fff;
    }

    .action-btn.primary:hover { background: #1d4ed8; }

    .action-btn.success {
        background: #16a34a;
        color: #fff;
    }

    .action-btn.success:hover { background: #15803d; }

    .action-btn.danger {
        background: #dc2626;
        color: #fff;
    }

    .action-btn.danger:hover { background: #b91c1c; }

    .action-btn.secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .action-btn.secondary:hover { background: #e5e7eb; }

    /* Toolbar */
    .returns-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .returns-search-wrapper {
        position: relative;
        flex: 1;
        min-width: 200px;
        max-width: 320px;
    }

    .returns-search-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
        pointer-events: none;
    }

    .returns-search-wrapper input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: var(--c-text);
        background: #fff;
        transition: border-color 0.15s, box-shadow 0.15s;
        box-sizing: border-box;
    }

    .returns-search-wrapper input:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    .returns-filter-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .returns-filter-select {
        padding: 8px 30px 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 13px;
        color: var(--c-text);
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s;
        min-width: 130px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .returns-filter-wrapper::after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #6b7280;
        pointer-events: none;
    }

    /* Customer cell */
    .customer-cell {
        display: flex;
        flex-direction: column;
    }

    .customer-name {
        font-weight: 600;
        font-size: 14px;
        color: var(--c-text);
    }

    .customer-email {
        font-size: 12px;
        color: var(--c-text-muted);
    }

    .order-id-display {
        font-family: 'SF Mono', 'Menlo', monospace;
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
    }

    /* Empty state */
    .returns-empty-state {
        text-align: center;
        padding: 64px 24px;
    }

    .returns-empty-state .empty-icon {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
        display: block;
    }

    .returns-empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 8px;
    }

    .returns-empty-state p {
        font-size: 14px;
        color: var(--c-text-muted);
        margin-bottom: 24px;
        line-height: 1.5;
    }

    /* Pagination */
    .returns-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-top: 1px solid var(--c-border);
        background: #fafbfc;
    }

    .returns-pagination .pagination-info {
        font-size: 13px;
        color: var(--c-text-muted);
    }

    /* Modal styles */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .modal-backdrop.active {
        display: flex;
    }

    .modal-content {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        max-width: 520px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }

    .modal-content h3 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--c-text);
    }

    .modal-content label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text-muted);
        margin-bottom: 6px;
    }

    .modal-content textarea,
    .modal-content input[type="number"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: var(--c-text);
        transition: border-color 0.15s;
        box-sizing: border-box;
        margin-bottom: 12px;
    }

    .modal-content textarea:focus,
    .modal-content input[type="number"]:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    .modal-content textarea { min-height: 80px; resize: vertical; }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .modal-actions button {
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
    }
</style>

<!-- Stats Bar -->
<div class="return-stats-bar">
    <div class="return-stat-card">
        <div class="stat-icon total">
            <i class="ph ph-arrows-left-right"></i>
        </div>
        <div class="stat-info">
            <div class="stat-number">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Requests</div>
        </div>
    </div>
    <div class="return-stat-card">
        <div class="stat-icon pending">
            <i class="ph ph-clock"></i>
        </div>
        <div class="stat-info">
            <div class="stat-number">{{ $stats['pending'] }}</div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>
    <div class="return-stat-card">
        <div class="stat-icon approved">
            <i class="ph ph-check-circle"></i>
        </div>
        <div class="stat-info">
            <div class="stat-number">{{ $stats['approved'] }}</div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="return-stat-card">
        <div class="stat-icon rejected">
            <i class="ph ph-x-circle"></i>
        </div>
        <div class="stat-info">
            <div class="stat-number">{{ $stats['rejected'] }}</div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
</div>

<!-- Toolbar -->
<div class="returns-toolbar">
    <div class="returns-search-wrapper">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="returns-search-input" placeholder="Search by order ID, customer, or reason..." oninput="filterReturnsTable()">
    </div>
    <div class="returns-filter-wrapper">
        <select class="returns-filter-select" id="status-filter" onchange="filterReturnsTable()">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="refunded">Refunded</option>
            <option value="completed">Completed</option>
        </select>
    </div>
    <div class="returns-filter-wrapper">
        <select class="returns-filter-select" id="type-filter" onchange="filterReturnsTable()">
            <option value="">All Types</option>
            <option value="cancel">Cancel</option>
            <option value="return">Return</option>
        </select>
    </div>
</div>

<!-- Table -->
<div style="background: #fff; border: 1px solid var(--c-border); border-radius: 10px; overflow: hidden;">
    @if($requests->count() > 0)
    <table class="returns-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Items</th>
                <th>Condition</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $req)
            @php
                $itemsData = is_array($req->items_data) ? $req->items_data : [];
                $itemCount = count($itemsData);
                $firstItemName = $itemCount > 0 ? ($itemsData[0]['name'] ?? 'Item') : '—';
                $trackingNo = $req->order?->tracking_number ?? ('#' . substr($req->order_id, 0, 8));
            @endphp
            <tr class="returns-row" data-status="{{ $req->status }}" data-type="{{ $req->type }}">
                <td>
                    <span class="type-badge {{ $req->type === 'cancel' ? 'cancel' : 'return' }}">
                        <i class="ph-bold {{ $req->type === 'cancel' ? 'ph-x-circle' : 'ph-arrow-u-up-left' }}"></i>
                        {{ ucfirst($req->type) }}
                    </span>
                </td>
                <td>
                    <span class="order-id-display">{{ $trackingNo }}</span>
                </td>
                <td>
                    <div class="customer-cell">
                        <span class="customer-name">{{ $req->user?->name ?? '—' }}</span>
                        <span class="customer-email">{{ $req->user?->email ?? '—' }}</span>
                    </div>
                </td>
                <td>
                    <span title="{{ $req->reason ?? '—' }}" style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $req->reason ?? '—' }}
                    </span>
                </td>
                <td>
                    <span class="status-badge {{ $req->status }}">
                        <span class="dot"></span>
                        {{ ucfirst($req->status) }}
                    </span>
                </td>
                <td>
                    <span title="{{ $firstItemName }}">{{ $itemCount }} item(s)</span>
                </td>
                <td>
                    @php
                        $conditionLabels = ['like_new' => 'Like New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'];
                        $conditionLabel = $conditionLabels[$req->condition] ?? ($req->condition ? ucfirst($req->condition) : '—');
                    @endphp
                    <span>{{ $conditionLabel }}</span>
                </td>
                <td>
                    <div>
                        <div class="order-id-display" style="font-size: 12px;">{{ $req->created_at->format('M d, Y') }}</div>
                        <div style="font-size: 11px; color: var(--c-text-muted);">{{ $req->created_at->diffForHumans() }}</div>
                    </div>
                </td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="action-btn primary" onclick="viewReturn('{{ $req->id }}')">
                            <i class="ph ph-eye"></i> View
                        </button>
                        @if($req->status === 'pending')
                            <button type="button" class="action-btn success" onclick="openApproveModal('{{ $req->id }}', '{{ $req->type }}')">
                                <i class="ph ph-check"></i> Approve
                            </button>
                            <button type="button" class="action-btn danger" onclick="openRejectModal('{{ $req->id }}', '{{ $req->type }}')">
                                <i class="ph ph-x"></i> Reject
                            </button>
                        @elseif($req->status === 'approved')
                            <button type="button" class="action-btn primary" onclick="openRefundModal('{{ $req->id }}', '{{ $req->type }}')">
                                <i class="ph ph-currency-circle-dollar"></i> Process Refund
                            </button>
                        @elseif($req->status === 'refunded')
                            <span style="font-size: 12px; color: var(--c-text-muted); font-weight: 600;">
                                @if($req->refund_amount)
                                    ₱{{ number_format($req->refund_amount, 2) }}
                                @else
                                    Refunded
                                @endif
                            </span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="returns-pagination">
        <div class="pagination-info">
            Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }} requests
        </div>
        <div class="pagination-links" style="display: flex; gap: 4px;">
            {{ $requests->links() }}
        </div>
    </div>
    @else
    <div class="returns-empty-state">
        <i class="ph ph-arrows-left-right empty-icon"></i>
        <h3>No return or cancel requests yet</h3>
        <p>When customers submit return or cancellation requests, they will appear here for you to review and process.</p>
    </div>
    @endif
</div>

<!-- Approve Modal -->
<div id="approve-modal" class="modal-backdrop">
    <div class="modal-content">
        <h3>Approve <span id="approve-type-label">Return</span> Request</h3>
        <form id="approve-form" onsubmit="return false;">
            <input type="hidden" id="approve-request-id">
            
            <label for="refund-amount" id="refund-amount-label">Refund Amount (optional)</label>
            <input type="number" id="refund-amount" step="0.01" min="0" placeholder="Leave blank for full refund...">

            <label for="approve-notes">Admin Notes (optional)</label>
            <textarea id="approve-notes" placeholder="Any notes about this approval..."></textarea>

            <p style="font-size: 13px; color: var(--c-text-muted); margin: 8px 0;" id="approve-notice">
                This will approve the request, update the order status, and notify the customer.
            </p>

            <div class="modal-actions">
                <button type="button" style="background: #f3f4f6; color: #374151;" onclick="closeModal('approve-modal')">Cancel</button>
                <button type="button" id="approve-submit-btn" style="background: #16a34a; color: #fff;" onclick="submitApprove()">
                    <i class="ph ph-check"></i> Confirm Approval
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="modal-backdrop">
    <div class="modal-content">
        <h3>Reject <span id="reject-type-label">Return</span> Request</h3>
        <form id="reject-form" onsubmit="return false;">
            <input type="hidden" id="reject-request-id">

            <label for="reject-notes">Reason for Rejection *</label>
            <textarea id="reject-notes" placeholder="Explain why this request is being rejected (required)..." required></textarea>

            <p style="font-size: 13px; color: var(--c-text-muted); margin: 8px 0;">
                This will reject the request, revert the order status, and notify the customer with this note.
            </p>

            <div class="modal-actions">
                <button type="button" style="background: #f3f4f6; color: #374151;" onclick="closeModal('reject-modal')">Cancel</button>
                <button type="button" id="reject-submit-btn" style="background: #dc2626; color: #fff;" onclick="submitReject()">
                    <i class="ph ph-x"></i> Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Refund Modal -->
<div id="refund-modal" class="modal-backdrop">
    <div class="modal-content">
        <h3>Process Refund for <span id="refund-type-label">Return</span> Request</h3>
        <form id="refund-form" onsubmit="return false;">
            <input type="hidden" id="refund-request-id">
            
            <label for="refund-amount-display">Refund Amount *</label>
            <div style="position: relative;">
                <span style="position: absolute; left: 12px; top: 11px; font-size: 14px; font-weight: 600; color: #6b7280;">₱</span>
                <input type="number" id="refund-amount-display" step="0.01" min="0" placeholder="0.00" required
                    style="padding-left: 28px; width: 100%; padding: 10px 12px 10px 28px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: var(--c-text); transition: border-color 0.15s; box-sizing: border-box; margin-bottom: 12px;">
            </div>

            <label for="refund-notes">Admin Notes (optional)</label>
            <textarea id="refund-notes" placeholder="Record the refund transaction details..."></textarea>

            <p style="font-size: 13px; color: var(--c-text-muted); margin: 8px 0;" id="refund-notice">
                This will mark the refund as processed, update the request status to 'refunded', and notify the customer.
            </p>

            <div class="modal-actions">
                <button type="button" style="background: #f3f4f6; color: #374151;" onclick="closeModal('refund-modal')">Cancel</button>
                <button type="button" id="refund-submit-btn" style="background: #7c3aed; color: #fff;" onclick="submitRefund()">
                    <i class="ph ph-currency-circle-dollar"></i> Process Refund
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Detail Modal -->
<div id="detail-modal" class="modal-backdrop">
    <div class="modal-content" style="max-width: 640px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin-bottom: 0;">Request Details</h3>
            <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #9ca3af;" onclick="closeModal('detail-modal')">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div id="detail-content" style="font-size: 14px; color: var(--c-text); line-height: 1.6;">
            Loading...
        </div>
    </div>
</div>

<script>
    function filterReturnsTable() {
        const search = document.getElementById('returns-search-input').value.toLowerCase();
        const statusFilter = document.getElementById('status-filter').value;
        const typeFilter = document.getElementById('type-filter').value;
        const rows = document.querySelectorAll('.returns-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const status = row.getAttribute('data-status');
            const type = row.getAttribute('data-type');

            const matchesSearch = !search || text.includes(search);
            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesType = !typeFilter || type === typeFilter;

            row.style.display = (matchesSearch && matchesStatus && matchesType) ? '' : 'none';
        });
    }

    function openApproveModal(id, type) {
        document.getElementById('approve-request-id').value = id;
        document.getElementById('refund-amount').value = '';
        document.getElementById('approve-notes').value = '';
        document.getElementById('approve-type-label').textContent = type === 'cancel' ? 'Cancellation' : 'Return';
        document.getElementById('approve-modal').classList.add('active');

        // Update notice text based on type
        var notice = document.getElementById('approve-notice');
        if (notice) {
            if (type === 'cancel') {
                notice.textContent = 'This will approve the cancellation, cancel the order, and notify the customer.';
            } else {
                notice.textContent = 'This will approve the return, update the order status, and notify the customer. You can process the refund afterwards.';
            }
        }

        // For returns: fetch order total to pre-fill refund amount
        if (type === 'return') {
            var detailUrl = '{{ route('ecommerce.admin.returns.show', ['id' => 'REQ_ID']) }}';
            detailUrl = detailUrl.replace('REQ_ID', id);
            fetch(detailUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var total = data.order?.total || 0;
                document.getElementById('refund-amount').value = total > 0 ? total : '';
                var refundLabel = document.getElementById('refund-amount-label');
                if (refundLabel) {
                    refundLabel.textContent = 'Refund Amount (₱' + Number(total).toLocaleString() + ' total)';
                }
            })
            .catch(function() {});
        } else {
            document.getElementById('refund-amount').value = '';
            var refundLabel = document.getElementById('refund-amount-label');
            if (refundLabel) {
                refundLabel.textContent = 'Refund Amount (optional)';
            }
        }
    }

    function openRejectModal(id, type) {
        document.getElementById('reject-request-id').value = id;
        document.getElementById('reject-notes').value = '';
        document.getElementById('reject-type-label').textContent = type === 'cancel' ? 'Cancellation' : 'Return';
        document.getElementById('reject-modal').classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function submitApprove() {
        const id = document.getElementById('approve-request-id').value;
        const refundAmount = document.getElementById('refund-amount').value;
        const notes = document.getElementById('approve-notes').value;
        const btn = document.getElementById('approve-submit-btn');
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Processing...';

        var approveUrl = '{{ route('ecommerce.admin.returns.approve', ['id' => 'REQ_ID']) }}';
        approveUrl = approveUrl.replace('REQ_ID', id);
        fetch(approveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                refund_amount: refundAmount || null,
                admin_notes: notes || null
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('approve-modal');
                showToast(data.message || 'Request approved!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert(data.error || 'Something went wrong.');
            }
        })
        .catch(err => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Network error. Please try again.');
        });
    }

    function submitReject() {
        const id = document.getElementById('reject-request-id').value;
        const notes = document.getElementById('reject-notes').value.trim();
        const btn = document.getElementById('reject-submit-btn');
        const originalHtml = btn.innerHTML;

        if (!notes) {
            alert('Please provide a reason for rejection.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Processing...';

        var rejectUrl = '{{ route('ecommerce.admin.returns.reject', ['id' => 'REQ_ID']) }}';
        rejectUrl = rejectUrl.replace('REQ_ID', id);
        fetch(rejectUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ admin_notes: notes })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('reject-modal');
                showToast(data.message || 'Request rejected.', 'error');
                setTimeout(() => location.reload(), 1500);
            } else {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert(data.error || 'Something went wrong.');
            }
        })                .catch(err => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Network error. Please try again.');
        });
    }

    function openRefundModal(id, type) {
        document.getElementById('refund-request-id').value = id;
        document.getElementById('refund-amount-display').value = '';
        document.getElementById('refund-notes').value = '';
        document.getElementById('refund-type-label').textContent = type === 'cancel' ? 'Cancellation' : 'Return';
        document.getElementById('refund-modal').classList.add('active');

        // Fetch order total to pre-fill refund amount
        var detailUrl = '{{ route('ecommerce.admin.returns.show', ['id' => 'REQ_ID']) }}';
        detailUrl = detailUrl.replace('REQ_ID', id);
        fetch(detailUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var total = data.order?.total || 0;
            document.getElementById('refund-amount-display').value = total > 0 ? total : '';
            var existingRefund = data.refund_amount || 0;
            if (existingRefund > 0) {
                document.getElementById('refund-amount-display').value = existingRefund;
            }
        })
        .catch(function() {});
    }

    function submitRefund() {
        const id = document.getElementById('refund-request-id').value;
        const refundAmount = document.getElementById('refund-amount-display').value;
        const notes = document.getElementById('refund-notes').value;
        const btn = document.getElementById('refund-submit-btn');
        const originalHtml = btn.innerHTML;

        if (!refundAmount || parseFloat(refundAmount) <= 0) {
            alert('Please enter a valid refund amount.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Processing refund...';

        var refundUrl = '{{ route('ecommerce.admin.returns.refund', ['id' => 'REQ_ID']) }}';
        refundUrl = refundUrl.replace('REQ_ID', id);
        fetch(refundUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                refund_amount: refundAmount,
                admin_notes: notes || null
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('refund-modal');
                showToast(data.message || 'Refund processed!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert(data.error || 'Something went wrong.');
            }
        })
        .catch(err => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Network error. Please try again.');
        });
    }

    function viewReturn(id) {
        const modal = document.getElementById('detail-modal');
        const content = document.getElementById('detail-content');
        content.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Loading...';
        modal.classList.add('active');

        var detailUrl = '{{ route('ecommerce.admin.returns.show', ['id' => 'REQ_ID']) }}';
        detailUrl = detailUrl.replace('REQ_ID', id);
        fetch(detailUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            let itemsHtml = '';
            const items = data.items_data || [];
            if (items.length > 0) {
                itemsHtml = '<div style="margin-top: 12px;"><strong>Items:</strong><ul style="margin: 6px 0 0 16px;">';
                items.forEach(item => {
                    itemsHtml += '<li>' + (item.name || 'Item') + ' × ' + (item.quantity || 1) + ' — ₱' + Number(item.price || 0).toLocaleString() + '</li>';
                });
                itemsHtml += '</ul></div>';
            }

            const type = data.type === 'cancel' ? 'Cancellation' : 'Return';
            const statusLabel = data.status.charAt(0).toUpperCase() + data.status.slice(1);
            const refundAmount = data.refund_amount ? '₱' + Number(data.refund_amount).toLocaleString() : '—';
            const orderId = data.order_id || '—';
            const userName = data.user?.name || '—';
            const userEmail = data.user?.email || '—';

            content.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <strong>Type:</strong> ${type}
                    </div>
                    <div>
                        <strong>Status:</strong> <span class="status-badge ${data.status}"><span class="dot"></span> ${statusLabel}</span>
                    </div>
                    <div>
                        <strong>Order:</strong> ${orderId}
                    </div>
                    <div>
                        <strong>Refund Amount:</strong> ${refundAmount}
                    </div>
                    <div>
                        <strong>Customer:</strong> ${userName}
                    </div>
                    <div>
                        <strong>Email:</strong> ${userEmail}
                    </div>
                    <div style="grid-column: span 2;">
                        <strong>Reason:</strong> ${data.reason || '—'}
                    </div>
                    ${data.condition ? `<div style="grid-column: span 2;"><strong>Condition:</strong> ${({'like_new':'Like New','good':'Good','fair':'Fair','poor':'Poor'}[data.condition] || data.condition)}</div>` : ''}
                    ${data.admin_notes ? `<div style="grid-column: span 2;"><strong>Admin Notes:</strong> ${data.admin_notes}</div>` : ''}
                    ${itemsHtml}
                    <div>
                        <strong>Submitted:</strong> ${data.created_at ? new Date(data.created_at).toLocaleString() : '—'}
                    </div>
                    <div>
                        <strong>Resolved:</strong> ${data.resolved_at ? new Date(data.resolved_at).toLocaleString() : '—'}
                    </div>
                    ${data.refunded_at ? `<div><strong>Refunded:</strong> ${new Date(data.refunded_at).toLocaleString()}</div>` : ''}
                </div>
            `;
        })
        .catch(err => {
            content.innerHTML = '<p style="color: #dc2626;">Failed to load request details.</p>';
        });
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            padding: 12px 24px; border-radius: 8px; color: #fff; font-weight: 600;
            font-size: 14px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            background: ${type === 'success' ? '#16a34a' : '#dc2626'};
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.active').forEach(m => m.classList.remove('active'));
        }
    });
</script>
@endsection
