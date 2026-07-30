<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Test Panel</title>
</head>
<body>

<h1>Test Panel</h1>

<p>
  <a href="{{ route('order-fulfillment.dashboard') }}">Dashboard</a> |
  <a href="{{ route('order-fulfillment.orders') }}">Orders</a> |
  <a href="{{ route('order-fulfillment.packing') }}">Packing</a> |
  <a href="{{ route('order-fulfillment.shipping') }}">Shipping</a> |
  <a href="{{ route('order-fulfillment.return') }}">Returns</a>
</p>

<p>
  <strong>Note:</strong> this page force-sets statuses directly, skipping normal business rules
  (cancellation windows, NEW-only accept/decline guards, etc). Demo/testing only.
</p>

<p id="toast"></p>

<h2>Orders ({{ $orders->count() }})</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>Order ID</th>
      <th>Customer</th>
      <th>Current Status</th>
      <th>Set Status</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @forelse($orders as $order)
    <tr data-kind="order" data-id="{{ $order->id }}">
      <td>{{ $order->id }}</td>
      <td>{{ $order->customer_name ?? '' }}</td>
      <td class="current-status">{{ $order->status }}</td>
      <td>
        <select class="status-select">
          @foreach($orderStatuses as $s)
            <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
          @endforeach
        </select>
      </td>
      <td><button onclick="applyStatus(this)">Apply</button></td>
      <td><button onclick="deleteRow(this)">Delete</button></td>
    </tr>
    @empty
    <tr><td colspan="6">No orders yet.</td></tr>
    @endforelse
  </tbody>
</table>

<h2>Shipments ({{ $shipments->count() }})</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>Shipment ID</th>
      <th>Order ID</th>
      <th>Current Status</th>
      <th>Set Status</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @forelse($shipments as $shipment)
    <tr data-kind="shipment" data-id="{{ $shipment->shipment_id }}">
      <td>{{ $shipment->shipment_id }}</td>
      <td>{{ $shipment->order_id }}</td>
      <td class="current-status">{{ $shipment->status }}</td>
      <td>
        <select class="status-select">
          @foreach($shipmentStatuses as $s)
            <option value="{{ $s }}" @selected($shipment->status === $s)>{{ $s }}</option>
          @endforeach
        </select>
      </td>
      <td><button onclick="applyStatus(this)">Apply</button></td>
      <td><button onclick="deleteRow(this)">Delete</button></td>
    </tr>
    @empty
    <tr><td colspan="6">No shipments yet.</td></tr>
    @endforelse
  </tbody>
</table>

<h2>Returns ({{ $returns->count() }})</h2>
<p>
  If Reason is "Cancelled while shipping" or "Cancelled before shipping", Accept/Decline stay hidden
  on the real Returns page even at status Pending — that's intentional (it means "admin cancellation",
  not a customer return). Pick a different reason below to test Accept/Decline.
</p>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>Return ID</th>
      <th>Order ID</th>
      <th>Current Status</th>
      <th>Reason</th>
      <th>Set Status</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @forelse($returns as $return)
    <tr data-kind="return" data-id="{{ $return->id }}">
      <td>{{ Str::limit($return->id, 8, '') }}</td>
      <td>{{ $return->order_id }}</td>
      <td class="current-status">{{ $return->status }}</td>
      <td>
        <select class="reason-select">
          <option value="Wrong item received" @selected(!in_array($return->reason, $adminCancelReasons))>Wrong item received (customer)</option>
          <option value="Item damaged">Item damaged (customer)</option>
          <option value="Changed my mind">Changed my mind (customer)</option>
          <option value="Cancelled while shipping" @selected($return->reason === 'Cancelled while shipping')>Cancelled while shipping (admin)</option>
          <option value="Cancelled before shipping" @selected($return->reason === 'Cancelled before shipping')>Cancelled before shipping (admin)</option>
        </select>
      </td>
      <td>
        <select class="status-select">
          @foreach($returnStatuses as $s)
            <option value="{{ $s }}" @selected($return->status === $s)>{{ $s }}</option>
          @endforeach
        </select>
      </td>
      <td><button onclick="applyStatus(this)">Apply</button></td>
      <td><button onclick="deleteRow(this)">Delete</button></td>
    </tr>
    @empty
    <tr><td colspan="7">No returns yet.</td></tr>
    @endforelse
  </tbody>
</table>

<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  const urlTemplates = {
    order:    @json(route('order-fulfillment.test-panel.orders.status', ['id' => '__ID__'])),
    shipment: @json(route('order-fulfillment.test-panel.shipments.status', ['shipmentId' => '__ID__'])),
    return:   @json(route('order-fulfillment.test-panel.returns.status', ['id' => '__ID__'])),
  };

  const deleteUrlTemplates = {
    order:    @json(route('order-fulfillment.test-panel.orders.delete', ['id' => '__ID__'])),
    shipment: @json(route('order-fulfillment.test-panel.shipments.delete', ['shipmentId' => '__ID__'])),
    return:   @json(route('order-fulfillment.test-panel.returns.delete', ['id' => '__ID__'])),
  };

  function showToast(message) {
    document.getElementById('toast').textContent = message;
  }

  function applyStatus(btn) {
    const row = btn.closest('tr');
    const kind = row.dataset.kind;
    const id = row.dataset.id;
    const status = row.querySelector('.status-select').value;
    const url = urlTemplates[kind].replace('__ID__', encodeURIComponent(id));

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Applying...';

    const returnResolutions = {
      'Pending': null,
      'In Transit to Warehouse': 'Pending',
      'Inspecting': 'In Review',
      'Refunded': 'Refund Issued',
      'Completed': 'Returned to Inventory',
      'Declined': 'Declined by fulfillment',
    };

    const body = { status: status };
    if (kind === 'return') {
      if (returnResolutions[status]) {
        body.resolution = returnResolutions[status];
      }
      const reasonSelect = row.querySelector('.reason-select');
      if (reasonSelect) {
        body.reason = reasonSelect.value;
      }
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body),
    })
      .then(res => res.json().then(data => ({ ok: res.ok, data })))
      .then(({ ok, data }) => {
        if (!ok || !data.success) {
          throw new Error(data.message || 'Update failed.');
        }
        row.querySelector('.current-status').textContent = data.status;
        showToast(kind + ' ' + id + ' -> ' + data.status);
      })
      .catch(err => showToast('Error: ' + err.message))
      .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
      });
  }

  function deleteRow(btn) {
    const row = btn.closest('tr');
    const kind = row.dataset.kind;
    const id = row.dataset.id;

    const confirmMessage = kind === 'order'
      ? 'Delete this order? This also deletes its order items, shipment, return, and packing-error rows. This cannot be undone.'
      : 'Delete this ' + kind + '? This cannot be undone.';

    if (!confirm(confirmMessage)) {
      return;
    }

    const url = deleteUrlTemplates[kind].replace('__ID__', encodeURIComponent(id));

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Deleting...';

    fetch(url, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    })
      .then(res => res.json().then(data => ({ ok: res.ok, data })))
      .then(({ ok, data }) => {
        if (!ok || !data.success) {
          throw new Error(data.message || 'Delete failed.');
        }
        row.remove();
        showToast(kind + ' ' + id + ' deleted.');
      })
      .catch(err => {
        showToast('Error: ' + err.message);
        btn.disabled = false;
        btn.textContent = originalText;
      });
  }
</script>

</body>
</html>
