/* ---------- Record modals (view / edit / delete) ---------- */
  function htmlEscape(v){
    return String(v ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }
  function textFrom(node){ return (node?.textContent || '').trim(); }
  // One canonical form for every status string in the module.
  //
  // There used to be three: updateRowStatus wrote "in-transit", the delivery
  // edit path wrote "intransit", and the status-chart tiles compared against
  // "intransit". A row moved to In Transit therefore stopped matching its own
  // filter tile and silently vanished from the table.
  function normalizeStatusKey(status){
    return String(status ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');
  }
  function parseMoney(v){ return Number(String(v || '').replace(/[^0-9.]/g,'')) || 0; }
  function money(v){ return 'PHP ' + Number(v || 0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:2}); }
  function supplierNameFromCell(cell){
    if(!cell) return '';
    const pill = cell.querySelector('.supplier-pill');
    if(!pill) return textFrom(cell);
    const clone = pill.cloneNode(true);
    const badge = clone.querySelector('.supplier-badge');
    if(badge) badge.remove();
    return textFrom(clone);
  }
  function supplierBadgeColor(name){
    const map = {'GigaCore Components':'#22c55e','Global Tech Supply':'#0ea5e9','MegaStar Trading':'#f2994a','Primo Electronics':'#22c55e','Quantum Motherboards':'#7a5af0','Silverline PSU Ltd':'#eb5757','Silverline PSU Ltd.':'#eb5757','TechWholesale PH':'#2f6fed','Trident RAM Supply':'#0ea5e9'};
    return map[name] || randomColor(name || 'supplier');
  }
  function supplierPill(name){ return `<span class="supplier-pill"><span class="supplier-badge" style="background:${supplierBadgeColor(name)}">${initials(name || 'NA')}</span>${htmlEscape(name || 'Unknown Supplier')}</span>`; }
  function statusPill(status){
    const raw = String(status || 'Pending');
    const clsMap = {'Approved':'approved','Pending':'pending','Processing':'processing','Rejected':'rejected','Completed':'completed','Paid':'paid','Unpaid':'unpaid','intransit':'intransit','Delivered':'delivered','Delayed':'delayed','Scheduled':'scheduled','Active':'approved','Inactive':'pending','Blacklisted':'rejected'};
    const cls = clsMap[raw] || raw.toLowerCase().replace(/\s+/g,'');
    return `<span class="status-pill ${cls}">${htmlEscape(raw)}</span>`;
  }
  function getTableType(row){
    // Suppliers render as cards, so they never sit inside a <table>.
    if(row?.classList?.contains('supplier-card')) return 'supplier';
    // Defects now share the Requisitions work queue. Preserve their distinct
    // workflow even though they no longer need a separate tab/table.
    if(row?.dataset?.recordType === 'defect') return 'defect';
    const id = row?.closest('table')?.id;
    return ({'po-table':'po','suppliers-table':'supplier','requisitions-table':'req','deliveries-table':'delivery','defect-items-table':'defect'})[id] || '';
  }
  function resolveSupplierByPO(po){
    const found = [...document.querySelectorAll('#po-table tbody tr')].find(r => textFrom(r.children[0]) === po);
    return found ? supplierNameFromCell(found.children[1]) : 'â€”';
  }
  function normalizePoStatus(status){
    const map = {
      'approved': 'approved',
      'pending': 'pending',
      'rejected': 'rejected',
      'cancelled': 'cancelled',
      'processing': 'processing',
      'completed': 'completed'
    };
    const key = String(status || '').trim().toLowerCase();
    return map[key] || key;
  }

  function persistPurchaseOrderStatus(row, status){
    if(!row || !row.dataset.id) return Promise.resolve();
    const id = row.dataset.id;
    const normalizedStatus = normalizePoStatus(status);
    return fetch(procurementUrl(`purchase-orders/${id}`), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: new URLSearchParams({ status: normalizedStatus }).toString()
    }).then(async response => {
      if (!response.ok) {
        throw new Error(`PO status update failed (${response.status})`);
      }

      return response.json();
    });
  }
  function persistRequisitionStatus(row, status){
    if(!row || !row.dataset.id) return Promise.resolve();
    const id = row.dataset.id;
    // The requisition reference (REQ #) is unique across the external sources;
    // the numeric id is NOT (Inventory and Order Fulfillment can share ids), so
    // send the reference to disambiguate which database to write.
    const ref = textFrom(row.children[0]);
    return fetch(procurementUrl(`requisitions/${id}`), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: new URLSearchParams({ status: status, ref: ref }).toString()
    }).then(async response => {
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        // Surface the server's reason (e.g. an illegal status transition).
        throw new Error(payload?.message || `Requisition status update failed (${response.status})`);
      }

      return payload;
    });
  }
  // NOTE: setRequisitionDecision() lived here. It drove the Approve / Reject
  // buttons on a requisition's View modal; there is no approval step any more
  // (raising the purchase order is the approval), so it had no callers left.

  function syncRelatedRequisitionStatusForPO(row, poStatus){
    if(!row) return;
    const lookupRef = row.dataset.reqRef || textFrom(row.children[0]);
    const reqRow = findReqRowByRef(lookupRef);
    if(!reqRow) return;
    const reqStatus = poStatus === 'Approved' ? 'Processing' : (poStatus === 'Rejected' ? 'Pending' : (poStatus === 'Completed' ? 'Completed' : poStatus));
    updateRequisitionStatus(lookupRef, reqStatus);
    persistRequisitionStatus(reqRow, reqStatus);
  }
  function getSupplierProducts(row){
    const raw = row?.dataset?.products || '';
    if(!raw) return [];
    try { return JSON.parse(raw); } catch { return []; }
  }

  // Item rows attached to a PO row (data-items, JSON) â€” same "products as
  // chips" pattern as getSupplierProducts, used for the View PO chips and the
  // Log Delivery modal's item chips.
  function getPoItems(row){
    const raw = row?.dataset?.items || '';
    if(!raw) return [];
    try { const parsed = JSON.parse(raw); return Array.isArray(parsed) ? parsed : []; } catch { return []; }
  }

  // The Suppliers page renders cards, not a table. Everything that used to
  // scan "#suppliers-table tbody tr" goes through this instead, so there is a
  // single place that knows where supplier data lives.
  function supplierCards(){
    return [...document.querySelectorAll('#suppliers-cards .supplier-card')];
  }
  function findSupplierCardByName(name){
    const key = String(name || '').trim().toLowerCase();
    if(!key) return null;
    return supplierCards().find(c => (c.dataset.name || '').trim().toLowerCase() === key) || null;
  }

  // One product row is enough to identify a supplier at a glance; the rest sit
  // behind a "+N more" toggle so a supplier with twenty products does not turn
  // its card into a scrolling column.
  function supplierProductItemMarkup(p){
    const meta = [p.sku || 'No SKU', p.category].filter(Boolean).join(' · ');
    return `<li><div class="sc-product-info"><span class="sc-product-name">${htmlEscape(p.name || 'Product')}</span><span class="sc-product-meta">${htmlEscape(meta)}</span></div><span class="sc-product-price">&#8369;${Number(p.price || 0).toFixed(2)}</span></li>`;
  }

  // Expand / collapse a card's product list. Stops the click from bubbling to
  // the card, which would otherwise open the supplier's View modal.
  function toggleSupplierProducts(btn, event){
    event?.stopPropagation();
    const wrap = btn?.closest('.sc-products');
    const list = wrap?.querySelector('.sc-product-list');
    if(!wrap || !list) return;
    const expanded = wrap.classList.toggle('expanded');
    const hidden = Number(btn.dataset.hidden || 0);
    btn.textContent = expanded ? 'Show less' : `+${hidden} more`;
    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  }

  // Repaint a card's product list from its data-products after an edit.
  function renderSupplierCardProducts(card){
    if(!card) return;
    const products = getSupplierProducts(card);
    const wrap = card.querySelector('.sc-products');
    if(!wrap) return;

    const heading = wrap.querySelector('h4');
    if(heading){
      const label = heading.lastChild;
      if(label && label.nodeType === Node.TEXT_NODE) label.textContent = ` Products (${products.length})`;
    }

    wrap.classList.remove('expanded');
    wrap.querySelectorAll('.sc-product-list, .sc-product-empty, .sc-product-more').forEach(el => el.remove());

    if(!products.length){
      const empty = document.createElement('div');
      empty.className = 'sc-product-empty';
      empty.textContent = 'No products added yet.';
      wrap.appendChild(empty);
      if(typeof invalidateRowSearchText === 'function') invalidateRowSearchText(card);
      return;
    }

    const list = document.createElement('ul');
    list.className = 'sc-product-list';
    list.innerHTML = products.map((p, i) =>
      supplierProductItemMarkup(p).replace('<li>', i === 0 ? '<li>' : '<li class="sc-product-extra">')
    ).join('');
    wrap.appendChild(list);

    if(products.length > 1){
      const more = document.createElement('button');
      more.type = 'button';
      more.className = 'sc-product-more';
      more.dataset.hidden = String(products.length - 1);
      more.setAttribute('aria-expanded', 'false');
      more.textContent = `+${products.length - 1} more`;
      more.addEventListener('click', e => toggleSupplierProducts(more, e));
      wrap.appendChild(more);
    }

    if(typeof invalidateRowSearchText === 'function') invalidateRowSearchText(card);
  }

  // Rebuild the in-memory catalog the PO modal reads, after a card changes.
  function refreshSupplierCatalogFromCards(){
    window.SUPPLIER_CATALOG = window.SUPPLIER_CATALOG || {};
    supplierCards().forEach(card => {
      const name = card.dataset.name || '';
      if(!name) return;
      window.SUPPLIER_CATALOG[name] = {
        brand: name,
        warehouseId: card.dataset.warehouseId || '',
        products: getSupplierProducts(card).map(p => ({
          name: p.name,
          unitPrice: Number(p.price || p.unitPrice || 0),
          category: p.category || ''
        }))
      };
    });
  }

  function getSupplierCatalogEntry(name){
    const key = String(name || '').trim();
    if(!key) return null;
    // First check client-side cached catalog
    if(window.SUPPLIER_CATALOG && window.SUPPLIER_CATALOG[key]){
      return window.SUPPLIER_CATALOG[key];
    }
    // Fallback to reading the supplier cards currently on the page
    const supplierRow = findSupplierCardByName(key);
    if(supplierRow){
      const products = getSupplierProducts(supplierRow);
      const entry = { brand: key, warehouseId: supplierRow.dataset.warehouseId || '', products: products.map(p => ({ name: p.name, unitPrice: Number(p.price || p.unitPrice || 0), category: p.category || '' })) };
      // cache for later
      window.SUPPLIER_CATALOG = window.SUPPLIER_CATALOG || {};
      window.SUPPLIER_CATALOG[key] = entry;
      return entry;
    }
    return null;
  }

  function findPoRowByNumber(poNumber){
    return [...document.querySelectorAll('#po-table tbody tr')].find(row => textFrom(row.children[0]) === poNumber);
  }

  function findReqRowByRef(ref){
    return [...document.querySelectorAll('#requisitions-table tbody tr')].find(row => textFrom(row.children[0]) === ref || row.dataset.reqRef === ref || row.dataset.po === ref);
  }

  // Kept for the unrelated Requisition -> Purchase Order conversion flow
  // (app-forms.js / app-deliveries.js still call these when a PO tied to a
  // defect-linked row changes stage). No-ops for the real defect rows below
  // since those never carry a data-po attribute.
  function findDefectRowsByPO(poNumber){
    if(!poNumber) return [];
    return [...document.querySelectorAll('#defect-items-table tbody tr, #requisitions-table tbody tr[data-record-type="defect"]')]
      .filter(row => row.dataset.po === poNumber);
  }
  function updateDefectStatus(row, status){
    if(!row) return;
    row.dataset.status = normalizeStatusKey(status);
    row.dataset.statusLabel = String(status || '');
    const cell = row.closest('table')?.id === 'requisitions-table'
      ? row.children[6]
      : (row.children[5] || row.children[3]);
    if(cell) cell.innerHTML = statusPill(status);
    if(typeof updateStatusCounts === 'function') updateStatusCounts();
  }

  // Defect Items table â€” static return workflow (no supplier portal, no PO).
  // Procurement only flips the defect's status; Inventory owns detection
  // (creating the defect) and final processing (marking it Completed).
  //
  //   Open -> Rejected
  //   Open -> Returned to Supplier
  //   Returned to Supplier -> Replacement In Transit
  //   Replacement In Transit -> Replacement Received
  //       (server creates the Inventory stock_receivings row)
  //
  // The table has no server-rendered rows â€” it's populated entirely from
  // GET /procurement/requisitions/defects.
  // Sends one of the static return actions to the server and refreshes the
  // row + open modal (if any) with the resulting status.
  function defectAction(row, action){
    if(!row) return;
    const id = row.dataset.id;
    if(!id) return;
    fetch(procurementUrl(`requisitions/defects/${id}`), {
      method: 'PUT',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({ action }).toString()
    })
      .then(async r => {
        const payload = await r.json().catch(() => null);
        if(!r.ok) throw new Error(payload?.message || `Defect update failed (${r.status})`);
        return payload;
      })
      .then(payload => {
        const status = payload.defect_status;
        row.dataset.status = normalizeStatusKey(status);
        if(row.children[5]) row.children[5].innerHTML = statusPill(status);
        if(typeof updateStatusCounts === 'function') updateStatusCounts();
        closeViewModal();
        showToast(
          payload.receiving_created
            ? `${row.dataset.defectNo} marked Replacement Received â€” receiving record created for Inventory.`
            : `${row.dataset.defectNo} updated to ${status}.`,
          'ok'
        );
      })
      .catch(err => showToast(err?.message || 'Unable to update this defect item.', 'no'));
  }

  function updateRequisitionStatus(ref, status){
    const reqRow = findReqRowByRef(ref);
    if(!reqRow) return;
    updateRowStatus(reqRow, status);
    if(reqRow.children[6]) reqRow.children[6].innerHTML = statusPill(status);
  }

  function buildRecord(row){
    const type = getTableType(row);
    if(type === 'po'){
      const po = textFrom(row.children[0]);
      const supplier = supplierNameFromCell(row.children[1]);
      const item = row.dataset.item || textFrom(row.children[2]) || 'Procurement item';
      const qty = Number(row.dataset.qty || 0) || 0;
      // Columns after removing "Unit price": 0 PO, 1 supplier, 2 item,
      // 3 total amount, 4 priority, 5 status, 6 date.
      const amount = Number(row.dataset.amount || parseMoney(textFrom(row.children[3])) || 0);
      const unitPrice = Number(row.dataset.unitPrice || (qty ? amount / qty : 0)) || 0;
      const priority = row.dataset.priority || 'Normal';
      const delivery = row.dataset.delivery || 'Scheduled';
      const status = textFrom(row.children[5]);
      const date = textFrom(row.children[6]);
      const category = row.dataset.brand || row.dataset.category || 'General Procurement';
      const items = getPoItems(row);
      return {type, key:po, title:`Purchase Order Â· ${po}`, po, supplier, category, item, items, qty, amount, unitPrice:unitPrice ? money(unitPrice) : 'â€”', delivery, priority, status, date, time:row.dataset.time || '09:00 AM', expected:row.dataset.expected || 'â€”', requestedBy:row.dataset.requestedBy || 'Procurement Team', remarks:row.dataset.remarks || 'Standard purchase order workflow.'};
    }
    if(type === 'supplier'){
      // Every value comes from the card's data-* attributes; there are no
      // fixed cell positions to read any more. The supplier-level Brand field
      // was removed — category is a property of each product.
      const name = row.dataset.name || '';
      const products = getSupplierProducts(row);
      return {type, key:name, title:`Supplier · ${name}`, id:row.dataset.id || '', sid:row.dataset.sid || '', name,
        contact:row.dataset.contact || '', email:row.dataset.email || '', phone:row.dataset.phone || '',
        address:row.dataset.address || '', category:row.dataset.category || '',
        status:row.dataset.statusLabel || row.dataset.status || 'Active',
        terms:row.dataset.terms || 'Net 30', lastActivity:row.dataset.lastActivity || 'Recent PO activity',
        poCount:Number(row.dataset.poCount || 0), products};
    }
    if(type === 'req'){
      const ref = textFrom(row.children[0]);
      // items: a multi-line requisition can expose its lines via data-items
      // (JSON [{name?,qty}]); single-line requisitions fall back to item/qty.
      return {type, key:ref, title:`Requisition Â· ${ref}`, ref, item:textFrom(row.children[1]), qty:Number(textFrom(row.children[2])) || 0, items:getPoItems(row), priority:row.dataset.priority || textFrom(row.children[3]) || 'Normal', delivery:textFrom(row.children[3]), dept:textFrom(row.children[4]), requester:textFrom(row.children[5]), status:textFrom(row.children[6]), date:textFrom(row.children[7]), time:row.dataset.time || '10:30 AM', uom:row.dataset.uom || 'pcs', notes:row.dataset.notes || `Requested for ${textFrom(row.children[4])} operations.`, po:textFrom(row.dataset.po || ''), hasPO: row.dataset.hasPo === '1', source: row.dataset.source || ''};
    }
    if(type === 'defect'){
      const defectNo = row.dataset.defectNo || textFrom(row.children[0]);
      const part = row.dataset.part || textFrom(row.children[1]);
      const qty = Number(row.dataset.qty || textFrom(row.children[2])) || 1;
      const inSharedQueue = row.closest('table')?.id === 'requisitions-table';
      return {type, key:defectNo, id:row.dataset.id || '', title:`Defect Â· ${defectNo}`, defectNo, part, qty, description:row.dataset.description || row.dataset.notes || textFrom(row.children[3]), reportedBy:row.dataset.reportedBy || textFrom(row.children[inSharedQueue ? 5 : 4]), status:row.dataset.statusLabel || textFrom(row.children[inSharedQueue ? 6 : 5]), source:row.dataset.source || 'Inventory', date:row.dataset.date || textFrom(row.children[inSharedQueue ? 7 : 6]), hasPO: row.dataset.hasPo === '1', po: row.dataset.po || ''};
    }
    if(type === 'delivery'){
      // Columns: 0 ship, 1 PO, 2 supplier, 3 item, 4 expected delivery,
      // 5 status, 6 date. This read 4 for status and 5 for date, i.e. it
      // reported the expected-delivery date as the shipment's status and the
      // status pill's text as its date.
      const ship = textFrom(row.children[0]);
      return {type, key:ship, title:`Shipment Â· ${ship}`, ship, po:textFrom(row.children[1]), supplier:supplierNameFromCell(row.children[2]), stage:row.dataset.stage || '0', expected:textFrom(row.children[4]), status:textFrom(row.children[5]), date:textFrom(row.children[6]), note:row.dataset.note || 'Shipment tracking entry.', carrier:row.dataset.carrier || 'Assigned carrier'};
    }
    return {type:'', key:'', title:'Record'};
  }
  function setViewActions(left, right, po){
    const rejectBtn = document.getElementById('modal-reject-btn');
    const approveBtn = document.getElementById('modal-approve-btn');
    const poBtn = document.getElementById('modal-po-btn');
    const bind = (btn, cfg, fallbackClass) => {
      if(!cfg){ btn.style.display = 'none'; btn.onclick = null; return; }
      btn.style.display = '';
      btn.textContent = cfg.label;
      btn.className = `btn ${cfg.className || fallbackClass}`;
      btn.onclick = cfg.onClick;
    };
    bind(rejectBtn, left, 'btn-view');
    bind(approveBtn, right, 'btn-approve');
    if(poBtn){
      if(!po){ poBtn.style.display = 'none'; poBtn.onclick = null; }
      else{ poBtn.style.display = ''; poBtn.textContent = po.label || 'Create Purchase Order'; poBtn.className = `btn ${po.className || 'btn-primary'}`; poBtn.onclick = po.onClick; }
    }
  }

  function setViewModalHeader(row, record){
    const modal = document.getElementById('view-modal');
    const editBtn = document.getElementById('modal-header-edit-btn');
    const deleteBtn = document.getElementById('modal-header-delete-btn');
    const isSupplier = record?.type === 'supplier';
    modal.__row = row || null;
    editBtn.style.display = isSupplier ? '' : 'none';
    deleteBtn.style.display = isSupplier ? '' : 'none';
    if(isSupplier && row){
      editBtn.onclick = () => openEditModal(row);
      deleteBtn.onclick = () => openDeleteModal(row);
    } else {
      editBtn.onclick = null;
      deleteBtn.onclick = null;
    }
  }
  function updateRowStatus(row, status){
    const type = getTableType(row);
    row.dataset.status = normalizeStatusKey(status);
    const pillCell = (type === 'delivery' || type === 'po') ? row.children[5] : row.children[6];
    if(pillCell) pillCell.innerHTML = statusPill(status);
    if(typeof invalidateRowSearchText === 'function') invalidateRowSearchText(row);
    // A status change moves the row between status tiles and changes the live
    // cards/badges, so both are refreshed here rather than on the next reload.
    if(typeof updateStatusCounts === 'function') updateStatusCounts();
    if(typeof pollLiveStats === 'function') pollLiveStats();
  }
  function renderViewRecord(row){
    const record = buildRecord(row);
    document.getElementById('modal-title').textContent = record.title;
    setViewModalHeader(row, record);
    let body = '';
    if(record.type === 'po'){
      const poItemsMarkup = record.items?.length ? `<div class="supplier-product-inline">${record.items.map(it => `<span class="supplier-product-tag">${htmlEscape(it.name || 'Item')} &middot; Qty ${Number(it.qty||0)}${it.unitPrice ? ' &middot; &#8369;'+Number(it.unitPrice).toFixed(2) : ''}</span>`).join('')}</div>` : `<div class="modal-helper">${htmlEscape(record.item)}</div>`;
      body = `<div class="detail-grid"><div class="detail-card"><h4>Order overview</h4><div class="modal-row"><span>PO number</span><span>${htmlEscape(record.po)}</span></div><div class="modal-row"><span>Supplier</span><span>${htmlEscape(record.supplier)}</span></div><div class="modal-row"><span>Category</span><span>${htmlEscape(record.category)}</span></div><div class="modal-row"><span>Total quantity</span><span>${record.qty || 'â€”'}</span></div></div><div class="detail-card"><h4>Commercial details</h4><div class="modal-row"><span>Total amount</span><span>${money(record.amount)}</span></div><div class="modal-row"><span>Unit price</span><span>${record.unitPrice}</span></div><div class="modal-row"><span>Priority</span><span>${priorityBadge(record.priority || 'Normal')}</span></div><div class="modal-row"><span>Delivery status</span><span>${htmlEscape(record.delivery)}</span></div><div class="modal-row"><span>Status</span><span>${htmlEscape(record.status)}</span></div><div class="modal-row"><span>Date & time</span><span>${htmlEscape(record.date)} Â· ${htmlEscape(record.time)}</span></div></div><div class="detail-card full"><h4>Items</h4>${poItemsMarkup}</div><div class="detail-card full"><h4>Workflow</h4><div class="modal-row"><span>Requested by</span><span>${htmlEscape(record.requestedBy)}</span></div><div class="modal-row"><span>Expected delivery</span><span>${htmlEscape(record.expected)}</span></div></div></div><div class="detail-note"><b>Remarks</b><br>${htmlEscape(record.remarks)}</div>`;
      // Approve / Reject are no longer done from the PO view modal (approval
      // happens elsewhere). Pending POs can still be cancelled; everything else
      // is view-only.
      const statusKey = normalizeStatusKey(record.status);
      if(statusKey === 'pending'){
        setViewActions(
          {label:'Close', className:'btn-view', onClick:closeViewModal},
          {label:'Cancel PO', className:'btn-danger', onClick:()=> openCancelModalFromRow(row)}
        );
      } else if(statusKey === 'approved'){
        // An approved PO is the only thing a delivery can be logged against,
        // so offer that here instead of making the user go to Deliveries and
        // hunt for the PO number in a dropdown.
        setViewActions(
          {label:'Close', className:'btn-view', onClick:closeViewModal},
          {label:'Log Delivery', className:'btn-approve', onClick:()=> openLogDeliveryForPO(row)}
        );
      } else {
        setViewActions(
          {label:'Close', className:'btn-view', onClick:closeViewModal},
          null
        );
      }
    } else if(record.type === 'supplier'){
      const productsMarkup = record.products?.length ? `<div class="supplier-product-inline">${record.products.map(p => `<span class="supplier-product-tag">${htmlEscape(p.name || 'Product')}${p.category ? ' &middot; '+htmlEscape(p.category) : ''} &middot; ${htmlEscape(p.sku || 'SKU')} &middot; &#8369;${Number(p.price || 0).toFixed(2)}</span>`).join('')}</div>` : '<div class="modal-helper">No products added.</div>';
      body = `<div class="detail-grid"><div class="detail-card"><h4>Supplier profile</h4><div class="modal-row"><span>Name</span><span>${htmlEscape(record.name)}</span></div><div class="modal-row"><span>Contact</span><span>${htmlEscape(record.contact)}</span></div><div class="modal-row"><span>Email</span><span>${htmlEscape(record.email)}</span></div><div class="modal-row"><span>Phone</span><span>${htmlEscape(record.phone)}</span></div></div><div class="detail-card"><h4>Commercial profile</h4><div class="modal-row"><span>Products</span><span>${record.products?.length || 0}</span></div><div class="modal-row"><span>POs placed</span><span>${record.poCount || 0}</span></div><div class="modal-row"><span>Status</span><span>${htmlEscape(record.status)}</span></div><div class="modal-row"><span>Payment terms</span><span>${htmlEscape(record.terms)}</span></div><div class="modal-row"><span>Last activity</span><span>${htmlEscape(record.lastActivity)}</span></div></div><div class="detail-card full"><h4>Products</h4>${productsMarkup}</div><div class="detail-card full"><h4>Address</h4><div style="font-size:13px; line-height:1.55;">${htmlEscape(record.address)}</div></div></div>`;
      setViewActions({label:'Close', className:'btn-view', onClick:closeViewModal}, null);
    } else if(record.type === 'req'){
      body = `<div class="detail-grid"><div class="detail-card"><h4>Request details</h4><div class="modal-row"><span>Requisition no.</span><span>${htmlEscape(record.ref)}</span></div><div class="modal-row"><span>Item</span><span>${htmlEscape(record.item)}</span></div><div class="modal-row"><span>Quantity</span><span>${record.qty} ${htmlEscape(record.uom)}</span></div><div class="modal-row"><span>Delivery status</span><span>${htmlEscape(record.delivery)}</span></div></div><div class="detail-card"><h4>Request workflow</h4><div class="modal-row"><span>Department</span><span>${htmlEscape(record.dept)}</span></div><div class="modal-row"><span>Requested by</span><span>${htmlEscape(record.requester)}</span></div><div class="modal-row"><span>Status</span><span>${htmlEscape(record.status)}</span></div><div class="modal-row"><span>Date & time</span><span>${htmlEscape(record.date)} Â· ${htmlEscape(record.time)}</span></div></div></div><div class="detail-note"><b>Justification</b><br>${htmlEscape(record.notes)}</div>`;
      // There is no Approve step: raising the purchase order IS the approval,
      // so Inventory and Order Fulfillment requests behave identically. A
      // request that has already been rejected or fulfilled cannot be
      // converted, and one that already has a PO cannot get a second.
      const reqStatus = normalizeStatusKey(record.status);
      const convertible = !record.hasPO
        && !['rejected', 'cancelled', 'completed'].includes(reqStatus);
      const createPoBtn = {label:'Create Purchase Order', className:'btn-primary', onClick:()=>{ convertReqToPO(record.ref, record.item, record.qty, record.priority, record.items); closeViewModal(); }};

      setViewActions(
        {label:'Close', className:'btn-view', onClick:closeViewModal},
        null,
        convertible ? createPoBtn : null
      );
    } else if(record.type === 'defect'){
      body = `<div class="detail-grid"><div class="detail-card"><h4>Defect details</h4><div class="modal-row"><span>Defect #</span><span>${htmlEscape(record.defectNo)}</span></div><div class="modal-row"><span>Part name</span><span>${htmlEscape(record.part)}</span></div><div class="modal-row"><span>Quantity</span><span>${record.qty}</span></div><div class="modal-row"><span>Status</span><span>${htmlEscape(record.status)}</span></div></div><div class="detail-card"><h4>Report info</h4><div class="modal-row"><span>Source</span><span>${htmlEscape(record.source)}</span></div><div class="modal-row"><span>Reported by</span><span>${htmlEscape(record.reportedBy)}</span></div><div class="modal-row"><span>Date</span><span>${htmlEscape(record.date)}</span></div></div></div><div class="detail-note"><b>Description</b><br>${htmlEscape(record.description)}</div>`;
      // A defect replacement follows exactly the same path as any other
      // request once Procurement has returned it to the supplier:
      //   Pending -> Rejected
      //   Pending -> Returned to Supplier -> (raise a PO) -> Processing
      //     -> In Transit (logged in Deliveries) -> Delivered -> Completed
      // Only the first decision is made here; everything after it is driven by
      // the purchase order and its shipment, so no manual buttons for those.
      const defectStatus = normalizeStatusKey(record.status || 'Pending');
      const createDefectPoBtn = {
        label: 'Create Purchase Order',
        className: 'btn-primary',
        onClick: () => { convertReqToPO(record.defectNo, record.part, record.qty, 'Normal', null); closeViewModal(); }
      };

      if(defectStatus === 'pending' || defectStatus === 'open'){
        setViewActions(
          {label:'Reject', className:'btn-reject', onClick:()=> defectAction(row, 'reject')},
          {label:'Return to Supplier', className:'btn-approve', onClick:()=> defectAction(row, 'return')}
        );
      } else if(defectStatus === 'returnedtosupplier'){
        // The replacement is now a purchase the supplier has to fulfil.
        setViewActions(
          {label:'Close', className:'btn-view', onClick:closeViewModal},
          null,
          record.hasPO ? null : createDefectPoBtn
        );
      } else {
        // Processing / In Transit / Delivered move with the PO and its
        // delivery; Rejected and Completed are terminal.
        setViewActions({label:'Close', className:'btn-view', onClick:closeViewModal}, null);
      }
    }
    document.getElementById('modal-body').innerHTML = body;
    document.getElementById('view-modal').classList.add('open');
  }
  function openViewModal(btn){
    setViewModalHeader(null, null);
    const row = btn.closest('tr');
    if(row) renderViewRecord(row);
  }
  function closeViewModal(){
    document.getElementById('view-modal').classList.remove('open');
  }

  function buildEditFields(record){
    if(record.type === 'po') return `
      <div class="form-field"><label>PO number</label><input name="po" value="${htmlEscape(record.po)}" readonly></div>
      <div class="form-field"><label>Supplier</label><input name="supplier" value="${htmlEscape(record.supplier)}"></div>
      <div class="form-field"><label>Category</label><input name="category" value="${htmlEscape(record.category)}"></div>
      <div class="form-field"><label>Item</label><input name="item" value="${htmlEscape(record.item)}"></div>
      <div class="form-field"><label>Quantity</label><input type="number" min="0" name="qty" value="${record.qty}"></div>
      <div class="form-field"><label>Total amount</label><input type="number" min="0" step="0.01" name="amount" value="${record.amount}"></div>
      <div class="form-field"><label>Priority</label><select name="priority"><option ${record.priority==='Urgent'?'selected':''}>Urgent</option><option ${record.priority==='High'?'selected':''}>High</option><option ${record.priority==='Normal' || !record.priority?'selected':''}>Normal</option><option ${record.priority==='Low'?'selected':''}>Low</option></select></div>
      <div class="form-field"><label>Delivery status</label><select name="delivery"><option ${record.delivery==='Scheduled'?'selected':''}>Scheduled</option><option ${record.delivery==='intransit'?'selected':''}>intransit</option><option ${record.delivery==='Delivered'?'selected':''}>Delivered</option><option ${record.delivery==='Delayed'?'selected':''}>Delayed</option></select></div>
      <div class="form-field"><label>Status</label><select name="status"><option ${record.status==='Pending'?'selected':''}>Pending</option><option ${record.status==='Approved'?'selected':''}>Approved</option><option ${record.status==='Rejected'?'selected':''}>Rejected</option><option ${record.status==='Completed'?'selected':''}>Completed</option></select></div>
      <div class="form-field"><label>Date</label><input name="date" value="${htmlEscape(record.date)}"></div>
      <div class="form-field"><label>Time</label><input name="time" value="${htmlEscape(record.time)}"></div>
      <div class="form-field full"><label>Remarks</label><textarea name="remarks">${htmlEscape(record.remarks)}</textarea></div>`;
    if(record.type === 'supplier') return `
      <div class="form-field"><label>Supplier ID</label><input name="sid" value="${htmlEscape(record.sid)}" readonly></div>
      <div class="form-field"><label>Supplier Name <span class="req">*</span></label><input name="name" value="${htmlEscape(record.name)}" required></div>
      <div class="form-field"><label>Contact Person <span class="req">*</span></label><input name="contact" value="${htmlEscape(record.contact)}" required></div>
      <div class="form-field"><label>Email <span class="req">*</span></label><input type="email" name="email" value="${htmlEscape(record.email)}" required></div>
      <div class="form-field"><label>Phone Number <span class="req">*</span></label><input name="phone" value="${htmlEscape(record.phone)}" required></div>
      <div class="form-field"><label>Status</label><select name="status"><option value="active" ${normalizeStatusKey(record.status)==='active'?'selected':''}>Active</option><option value="inactive" ${normalizeStatusKey(record.status)==='inactive'?'selected':''}>Inactive</option><option value="blacklisted" ${normalizeStatusKey(record.status)==='blacklisted'?'selected':''}>Blacklisted</option></select></div>
      <div class="form-field full"><label>Address <span class="req">*</span></label><textarea name="address" required>${htmlEscape(record.address)}</textarea></div>
      <div class="form-field full"><h4 class="form-section-title">Products of this supplier</h4><div id="edit-supplier-products-list" class="product-row-list"></div><input type="hidden" name="productsJson" id="edit-supplier-products-json" value="[]"><button type="button" class="btn-add-row" onclick="addSupplierProductRow()">+ Add Another Product</button></div>`;
    if(record.type === 'req') return `
      <div class="form-field"><label>Requisition no.</label><input name="ref" value="${htmlEscape(record.ref)}" readonly></div>
      <div class="form-field"><label>Requested by</label><input name="requester" value="${htmlEscape(record.requester)}"></div>
      <div class="form-field"><label>Department</label><input name="dept" value="${htmlEscape(record.dept)}"></div>
      <div class="form-field"><label>Item</label><input name="item" value="${htmlEscape(record.item)}"></div>
      <div class="form-field"><label>Quantity</label><input type="number" min="0" name="qty" value="${record.qty}"></div>
      <div class="form-field"><label>Unit</label><input name="uom" value="${htmlEscape(record.uom)}"></div>
      <div class="form-field"><label>Priority</label><select name="priority"><option ${record.priority==='Urgent'?'selected':''}>Urgent</option><option ${record.priority==='High'?'selected':''}>High</option><option ${record.priority==='Normal' || !record.priority?'selected':''}>Normal</option><option ${record.priority==='Low'?'selected':''}>Low</option></select></div>
      <div class="form-field"><label>Status</label><select name="status"><option ${record.status==='Pending'?'selected':''}>Pending</option><option ${record.status==='Approved'?'selected':''}>Approved</option><option ${record.status==='Rejected'?'selected':''}>Rejected</option></select></div>
      <div class="form-field"><label>Date</label><input name="date" value="${htmlEscape(record.date)}"></div>
      <div class="form-field"><label>Time</label><input name="time" value="${htmlEscape(record.time)}"></div>
      <div class="form-field full"><label>Justification</label><textarea name="notes">${htmlEscape(record.notes)}</textarea></div>`;
    if(record.type === 'delivery') return `
      <div class="form-field"><label>Shipment no.</label><input name="ship" value="${htmlEscape(record.ship)}" readonly></div>
      <div class="form-field"><label>PO number</label><input name="po" value="${htmlEscape(record.po)}"></div>
      <div class="form-field"><label>Supplier</label><input name="supplier" value="${htmlEscape(record.supplier)}"></div>
      <div class="form-field"><label>Carrier</label><input name="carrier" value="${htmlEscape(record.carrier)}"></div>
      <div class="form-field"><label>Status</label><select name="status"><option ${record.status==='Scheduled'?'selected':''}>Scheduled</option><option ${record.status==='intransit'?'selected':''}>intransit</option><option ${record.status==='Delayed'?'selected':''}>Delayed</option><option ${record.status==='Delivered'?'selected':''}>Delivered</option></select></div>
      <div class="form-field"><label>Date</label><input name="date" value="${htmlEscape(record.date)}"></div>
      <div class="form-field full"><label>Tracking note</label><textarea name="note">${htmlEscape(record.note)}</textarea></div>`;
    return '';
  }
  function openEditModal(row){
    const record = buildRecord(row);
    const modal = document.getElementById('edit-modal');
    modal.__row = row;
    document.getElementById('edit-modal-title').textContent = `Edit ${record.title}`;
    document.getElementById('edit-modal-body').innerHTML = buildEditFields(record);
    if(record.type === 'supplier'){
      setSupplierProductEditor('edit-supplier-products-list', 'edit-supplier-products-json');
      renderSupplierProductList(getSupplierProducts(row));
    }
    document.getElementById('edit-record-form').dataset.type = record.type;
    modal.classList.add('open');
  }
  function closeEditModal(){ document.getElementById('edit-modal').classList.remove('open'); }
  function saveEditRecord(e){
    e.preventDefault();
    const modal = document.getElementById('edit-modal');
    const row = modal.__row;
    if(!row) return;
    const type = e.target.dataset.type;
    const d = Object.fromEntries(new FormData(e.target).entries());
    if(type === 'po'){
      const qty = Number(d.qty || 0);
      const amount = Number(d.amount || 0);
      const unitPrice = qty ? amount / qty : 0;
      row.dataset.item = d.item || '';
      row.dataset.time = d.time || '';
      row.dataset.remarks = d.remarks || '';
      row.dataset.brand = d.category || '';
      row.dataset.qty = qty;
      row.dataset.unitPrice = unitPrice;
      row.dataset.amount = amount;
      row.dataset.priority = d.priority || 'Normal';
      row.dataset.delivery = d.delivery || 'Scheduled';
      row.children[1].innerHTML = supplierPill(d.supplier);
      row.children[2].textContent = d.item || 'â€”';
      // Unit price column was removed: 3 total amount, 4 priority, 5 status, 6 date.
      row.children[3].innerHTML = `<b>${money(amount)}</b>`;
      row.children[4].innerHTML = priorityBadge(d.priority || 'Normal');
      row.children[5].innerHTML = statusPill(d.status);
      row.children[6].textContent = d.date;
      row.dataset.status = normalizeStatusKey(d.status);
      // Persist PO update to backend if we have an id
      const id = row.dataset.id;
      if(id){
        fetch(procurementUrl(`purchase-orders/${id}`), {
          method: 'PUT',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
          body: new URLSearchParams({ status: d.status || '', amount: String(amount || 0), remarks: d.remarks || '' }).toString()
        }).then(r => r.json()).then(() => {}).catch(() => showToast('Unable to save PO changes to server.', 'no'));
      }
    } else if(type === 'supplier'){
      let products = [];
      try { products = JSON.parse(d.productsJson || '[]'); } catch { products = []; }
      if(!Array.isArray(products)) products = [];

      // The card's data-* attributes are the record; repaint the visible parts
      // from them. Brand is gone — the subtitle is the most common product
      // category instead.
      const categories = products.map(p => String(p.category || '').trim()).filter(Boolean);
      const subtitle = categories.length
        ? categories.sort((a, b) =>
            categories.filter(c => c === b).length - categories.filter(c => c === a).length)[0]
        : '';

      row.dataset.sid = d.sid || '';
      row.dataset.name = d.name || '';
      row.dataset.contact = d.contact || '';
      row.dataset.email = d.email || '';
      row.dataset.phone = d.phone || '';
      row.dataset.address = d.address || '';
      row.dataset.category = subtitle;
      row.dataset.status = normalizeStatusKey(d.status || 'active');
      row.dataset.statusLabel = d.status || 'Active';
      row.dataset.products = JSON.stringify(products);

      const nameEl = row.querySelector('.sc-ident h3');
      if(nameEl) nameEl.textContent = d.name || '';
      const subEl = row.querySelector('.sc-ident p');
      if(subEl) subEl.textContent = subtitle || 'No category yet';
      const statusEl = row.querySelector('.sc-status');
      if(statusEl){
        statusEl.className = 'sc-status ' + normalizeStatusKey(d.status || 'active');
        statusEl.innerHTML = '<i></i>' + htmlEscape(d.status || 'Active');
      }
      const contactCells = row.querySelectorAll('.sc-contact li span');
      const contactValues = [d.contact, d.email, d.phone, d.address];
      contactCells.forEach((cell, idx) => { cell.textContent = contactValues[idx] || '—'; });
      renderSupplierCardProducts(row);

      const supId = row.dataset.id;
      if(supId){
        fetch(procurementUrl(`suppliers/${supId}`), {
          method: 'PUT',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
          body: new URLSearchParams({ name: d.name || '', contact: d.contact || '', email: d.email || '', phone: d.phone || '', address: d.address || '', status: d.status || 'active', productsJson: JSON.stringify(products) }).toString()
        }).then(async r => {
          const json = await r.json().catch(() => ({}));
          if(!r.ok) throw new Error(json?.message || 'Unable to save supplier changes.');
        }).catch(err => showToast(err?.message || 'Unable to save supplier changes to server.', 'no'));
      }
      refreshSupplierCatalogFromCards();
      if(typeof refreshSupplierCategoryOptions === 'function') refreshSupplierCategoryOptions();
    } else if(type === 'req'){
      row.dataset.uom = d.uom || '';
      row.dataset.notes = d.notes || '';
      row.dataset.time = d.time || '';
      row.children[1].textContent = d.item;
      row.children[2].textContent = d.qty || '0';
      // Column 3 is PRIORITY. This rendered a delivery badge here, which
      // disagreed with every server-rendered row and with applyReqFilter().
      row.dataset.priority = d.priority || 'Normal';
      row.children[3].innerHTML = priorityBadge(d.priority || 'Normal');
      row.children[4].textContent = d.dept;
      row.children[5].textContent = d.requester;
      row.children[6].innerHTML = statusPill(d.status);
      row.children[7].textContent = d.date;
      row.dataset.status = normalizeStatusKey(d.status);
      // Persist requisition update to backend if id exists
      const reqId = row.dataset.id;
      if(reqId){
        fetch(procurementUrl(`requisitions/${reqId}`), {
          method: 'PUT',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
          body: new URLSearchParams({ status: d.status || '', notes: d.notes || '' }).toString()
        }).then(r => r.json()).then(() => {}).catch(() => showToast('Unable to save requisition changes to server.', 'no'));
      }
    } else if(type === 'delivery'){
      row.dataset.po = d.po || '';
      row.dataset.sup = d.supplier || '';
      row.dataset.carrier = d.carrier || '';
      row.dataset.note = d.note || '';
      row.dataset.date = d.date || '';
      row.dataset.stage = ({'Scheduled':'0','intransit':'2','Delayed':'1','Delivered':'4'})[d.status] || '0';
      row.children[1].innerHTML = `<a class="po-link">${htmlEscape(d.po)}</a>`;
      row.children[2].innerHTML = supplierPill(d.supplier);
      // 5 is Status and 6 is Date — writing to 4/5 overwrote the
      // Expected Delivery column with a status pill and the Status column
      // with a date.
      row.children[5].innerHTML = statusPill(d.status);
      row.children[6].textContent = d.date;
      row.dataset.status = normalizeStatusKey(d.status);
      const defectStatusMap = { intransit:'Intransit', delivered:'Delivered' };
      const defectStatus = defectStatusMap[String(d.status || '').toLowerCase().trim()];
      if(defectStatus){ findDefectRowsByPO(row.dataset.po || '').forEach(r => updateDefectStatus(r, defectStatus)); }
      const delId = row.dataset.id;
      if(delId){
        fetch(procurementUrl(`deliveries/${delId}`), {
          method: 'PUT',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
          body: new URLSearchParams({ status: d.status || '', remarks: d.note || '' }).toString()
        }).then(r => r.json()).then(() => {}).catch(() => showToast('Unable to save delivery changes to server.', 'no'));
      }
    }
    // The row's cells were rewritten — drop the cached search text so live
    // search sees the new values.
    if(typeof invalidateRowSearchText === 'function') invalidateRowSearchText(row);
    if(typeof updateStatusCounts === 'function') updateStatusCounts();
    if(typeof pollLiveStats === 'function') pollLiveStats();
    closeEditModal();
    showToast('Record updated successfully', 'ok');
  }

  function openDeleteModal(row){
    const record = buildRecord(row);
    const modal = document.getElementById('delete-modal');
    modal.__row = row;
    document.getElementById('delete-modal-title').textContent = `Delete ${record.title}`;
    document.getElementById('delete-modal-target').textContent = record.key || record.title;
    document.getElementById('delete-confirm-input').value = '';
    document.getElementById('delete-continue-btn').disabled = true;
    document.getElementById('delete-final-confirm').style.display = 'none';
    document.getElementById('delete-confirm-btn').style.display = 'none';
    modal.classList.add('open');
  }
  function closeDeleteModal(){ document.getElementById('delete-modal').classList.remove('open'); }
  function handleDeletePhrase(v){ document.getElementById('delete-continue-btn').disabled = String(v || '').trim().toLowerCase() !== 'delete'; }
  function continueDeleteFlow(){
    document.getElementById('delete-final-confirm').style.display = 'block';
    document.getElementById('delete-confirm-btn').style.display = '';
  }
  function confirmDeleteRecord(){
    const modal = document.getElementById('delete-modal');
    const row = modal.__row;
    if(row){
      const type = getTableType(row);
      const id = row.dataset.id;
      if(id){
        const urlMap = { 'po': 'purchase-orders/', 'supplier': 'suppliers/', 'req': 'requisitions/', 'delivery': 'deliveries/' };
        const base = urlMap[type];
        if(base){
          fetch(procurementUrl(`${base}${id}`), { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } }).then(response => {
            if (!response.ok) throw new Error('Delete failed');
            row.remove();
            if(typeof updateStatusCounts === 'function') updateStatusCounts();
            if(typeof pollLiveStats === 'function') pollLiveStats();
            showToast('Record deleted', 'no');
          }).catch(()=>{ showToast('Unable to delete record on server.', 'no'); });
          closeDeleteModal();
          return;
        }
      }
      row.remove();
    }
    closeDeleteModal();
    if(typeof updateStatusCounts === 'function') updateStatusCounts();
    if(typeof pollLiveStats === 'function') pollLiveStats();
    showToast('Record deleted', 'no');
  }

  function initRowActionButtons(){
    const viewBtn = `<button title="View" onclick="openViewModal(this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button>`;
    const trackBtn = `<button title="Track" onclick="openTrackModal(this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2C7 2 4 6 4 10c0 5.5 8 12 8 12s8-6.5 8-12c0-4-3-8-8-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg></button>`;
    document.querySelectorAll('table .row-actions').forEach(wrap => {
      const tableId = wrap.closest('table')?.id;
      if(tableId === 'deliveries-table') {
        wrap.innerHTML = trackBtn;
        return;
      }
      // Both tables expose a single View action; approving/rejecting a
      // requisition happens inside its View Details modal.
      if(tableId === 'po-table' || tableId === 'requisitions-table') {
        wrap.innerHTML = viewBtn;
        return;
      }
      [...wrap.children].forEach((btn, idx) => {
        btn.title = idx === 0 ? 'View' : (idx === 1 ? 'Edit' : 'Delete');
      });
    });
  }
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('table .row-actions button');
    if(!btn) return;
    const row = btn.closest('tr');
    const tableId = row?.closest('table')?.id;
    if(tableId === 'deliveries-table') {
      openTrackModal(btn);
      return;
    }
    if(tableId === 'po-table' || tableId === 'requisitions-table') {
      openViewModal(btn);
      return;
    }
    const idx = [...btn.parentElement.children].indexOf(btn);
    if(idx === 0) openViewModal(btn);
    else if(idx === 1) openEditModal(row);
    else if(idx === 2) openDeleteModal(row);
  });
  // Clicking anywhere on a table row (not just the eye/track button) opens that
  // record's details. Clicks on the action buttons or on interactive controls
  // are ignored so their own handlers still run.
  document.addEventListener('click', (e)=>{
    if(e.target.closest('.row-actions')) return;
    if(e.target.closest('button, input, select, textarea, label, a[href]')) return;
    const card = e.target.closest('.supplier-card');
    if(card){ renderViewRecord(card); return; }
    const row = e.target.closest('table tbody tr');
    if(!row || !row.querySelector('.row-actions')) return;
    const tableId = row.closest('table')?.id;
    if(tableId === 'deliveries-table') openTrackModal(row);
    else openViewModal(row);
  });
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape'){ closeViewModal(); closeEditModal(); closeDeleteModal(); } });
