/* ---------- Add Modals (PO / Supplier / Delivery) ----------
   * The Requisition and Invoice entries were removed: neither #add-req-modal
   * nor #add-invoice-modal exists in any Procurement view, so openAddModal()
   * returned early for both and their submit handlers were unreachable.
   * Requisitions are raised in Inventory / Order Fulfillment. */
  const ADD_MODAL_MAP = {
    po: 'add-po-modal',
    supplier: 'add-supplier-modal',
    delivery: 'add-delivery-modal'
  };

  // Fallback sequence seeds, used only until the server-supplied
  // window.nextPoSeq / window.nextShipmentSeq arrive.
  const NEXT_ID = { po: 420, dr: 232, sup: 20 };
  const ID_COUNTS = { po: 419, dr: 231 };
  const procurementUrl = window.procurementUrl || ((path = '') => `/procurement/${String(path).replace(/^\/+/, '')}`);

  function pad(n, len){ return String(n).padStart(len, '0'); }
  // Local calendar date, not UTC. toISOString() returns the UTC day, so in
  // Manila (UTC+8) every date this produced between midnight and 08:00 was a
  // day behind — wrong default order/delivery dates, and shipments wrongly
  // flagged Delayed against their expected date.
  function todayISO(d = new Date()){
    return `${d.getFullYear()}-${pad(d.getMonth() + 1, 2)}-${pad(d.getDate(), 2)}`;
  }
  function fmtDate(iso){
    if(!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
  }
  function initials(name){
    return name.split(/\s+/).filter(Boolean).slice(0,2).map(w=>w[0].toUpperCase()).join('');
  }
  function randomColor(seed){
    const colors = ['#2f6fed','#22c55e','#f2994a','#7a5af0','#eb5757','#0ea5e9','#1fa971','#e0338c'];
    let h = 0; for(const c of seed) h = (h*31 + c.charCodeAt(0)) & 0xffff;
    return colors[h % colors.length];
  }
  function priorityBadge(label = 'Normal'){
    const raw = String(label || 'Normal').trim();
    const normalized = raw.toUpperCase();
    const type = ['URGENT','HIGH','LOW'].includes(normalized) ? normalized.toLowerCase() : 'normal';
    return `<span class="priority-pill ${type}">${htmlEscape(normalized)}</span>`;
  }

  function refreshDeliverySupplierOptions(){
    const select = document.getElementById('delivery-filter-supplier');
    if(!select) return;
    const selected = select.value || '';
    const supplierNames = supplierCards().map(card => card.dataset.name || '').filter(Boolean);
    const uniqueNames = [...new Set(supplierNames)];
    select.innerHTML = '<option value="">All Suppliers</option>' + uniqueNames.map(name => `<option value="${htmlEscape(name)}">${htmlEscape(name)}</option>`).join('');
    select.value = uniqueNames.includes(selected) ? selected : '';
  }

  function setModalFieldValue(modal, name, value){
    const field = modal.querySelector(`[name="${name}"]`);
    if(field) field.value = value;
  }

  // "Created By" must always reflect whoever is actually logged in â€” the
  // topnav profile dropdown already renders that name server-side, so we
  // read it from there instead of leaving the field hardcoded/editable.
  function getCurrentEmployeeName(){
    const el = document.querySelector('.profile-dropdown .profile-id strong');
    return el ? el.textContent.trim() : '';
  }

  function refreshPoSupplierOptions(modal){
    const form = modal?.querySelector('#add-po-form');
    if(!form) return Promise.resolve();
    const supplierField = form.querySelector('[name="supplier"]');
    // Always a promise. This path used to return undefined, so the caller's
    // .then() threw a TypeError and aborted the whole modal-open flow.
    if(!supplierField) return Promise.resolve();
    const selectedSupplier = supplierField.value || '';
    const supplierRows = supplierCards()
      .map(card => {
        const supplierName = card.dataset.name || '';
        if(!supplierName) return null;
        return { name: supplierName, brand: supplierName, warehouseId: card.dataset.warehouseId || '', products: getSupplierProducts(card) };
      })
      .filter(Boolean);
    if(supplierRows.length > 0){
      supplierField.innerHTML = '<option value="">Select supplier...</option>' + supplierRows.map(s => `<option value="${htmlEscape(s.name)}">${htmlEscape(s.name)}</option>`).join('');
      supplierField.value = supplierRows.some(s => s.name === selectedSupplier) ? selectedSupplier : '';
      // populate client-side catalog from rows (with each product's category,
      // used by the PO modal's supplier -> category -> item cascade)
      window.SUPPLIER_CATALOG = window.SUPPLIER_CATALOG || {};
      supplierRows.forEach(s => {
        window.SUPPLIER_CATALOG[s.name] = {
          brand: s.brand || s.name,
          warehouseId: s.warehouseId || '',
          products: s.products.map(p => ({ name: p.name, unitPrice: Number(p.price || p.unitPrice || 0), category: p.category || '' }))
        };
      });
      syncPoItemRowsWithSupplier(modal);
      refreshDeliverySupplierOptions();
      return Promise.resolve();
    }
    // No supplier rows on this page â€” fetch suppliers JSON from server
    return fetch(procurementUrl('suppliers'), { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        const list = (json?.data || []);
        supplierField.innerHTML = '<option value="">Select supplier...</option>' + list.map(s => `<option value="${htmlEscape(s.name)}">${htmlEscape(s.name)}</option>`).join('');
        supplierField.value = list.some(s => s.name === selectedSupplier) ? selectedSupplier : '';
        window.SUPPLIER_CATALOG = window.SUPPLIER_CATALOG || {};
        list.forEach(s => {
          window.SUPPLIER_CATALOG[s.name] = { brand: s.brand || s.name, warehouseId: s.warehouse_id || '', products: (s.products || []).map(p => ({ name: p.name, unitPrice: Number(p.price || p.unitPrice || 0), category: p.category || '' })) };
        });
        syncPoItemRowsWithSupplier(modal);
        refreshDeliverySupplierOptions();
      }).catch(() => {
        // ignore errors; leave supplierField as-is
      });
  }

  function addSupplierOptionToPoForm(name){
    if(!name) return;
    const modal = document.getElementById('add-po-modal');
    if(!modal) return;
    const supplierField = modal.querySelector('[name="supplier"]');
    if(!supplierField) return;
    const exists = [...supplierField.options].some(opt => opt.value === name);
    if(exists) return;
    const option = document.createElement('option');
    option.value = name;
    option.textContent = name;
    supplierField.appendChild(option);
  }

  /* ---------- PO multi-item rows: Category -> Item cascading ---------- */
  // Categories come from the currently-selected supplier's products. A
  // product with no category is bucketed as "Uncategorized" so older catalog
  // entries (saved before the category field existed) stay selectable.
  function supplierCategoriesFor(entry){
    const cats = (entry?.products || []).map(p => String(p.category || '').trim() || 'Uncategorized');
    return [...new Set(cats)];
  }
  function itemsForCategory(entry, category){
    const target = String(category || '').trim().toLowerCase();
    return (entry?.products || []).filter(p => (String(p.category || '').trim() || 'Uncategorized').toLowerCase() === target);
  }
  function currentPoSupplierEntry(modal){
    const supplierField = modal?.querySelector('#add-po-form [name="supplier"]');
    return getSupplierCatalogEntry((supplierField?.value || '').trim());
  }

  function populatePoRowCategorySelect(row, entry){
    const catField = row.querySelector('.po-item-category');
    if(!catField) return;
    const categories = supplierCategoriesFor(entry);
    catField.innerHTML = '<option value="">Select category...</option>' + categories.map(c => `<option value="${htmlEscape(c)}">${htmlEscape(c)}</option>`).join('');
    catField.value = '';
    populatePoRowItemSelect(row, entry, '');
  }
  function populatePoRowItemSelect(row, entry, category){
    const itemField = row.querySelector('.po-item-name');
    const priceField = row.querySelector('.po-item-price');
    if(!itemField) return;
    const items = category ? itemsForCategory(entry, category) : [];
    itemField.innerHTML = '<option value="">Select item...</option>' + items.map(p => {
      const name = String(p.name || '').trim();
      const price = Number(p.unitPrice || p.price || 0);
      return `<option value="${htmlEscape(name)}" data-unit-price="${price}">${htmlEscape(name)}</option>`;
    }).join('');
    itemField.disabled = !category;
    itemField.value = '';
    if(priceField) priceField.value = '';
    recomputePoRowAmount(row);
  }

  function recomputePoRowAmount(row){
    const qty = Number(row.querySelector('.po-item-qty')?.value || 0);
    const price = Number(row.querySelector('.po-item-price')?.value || 0);
    const amountField = row.querySelector('.po-item-amount');
    if(amountField) amountField.value = money(qty * price);
  }

  function recomputePoTotals(modal){
    const form = modal?.querySelector('#add-po-form');
    if(!form) return;
    let totalAmount = 0;
    form.querySelectorAll('.po-item-row').forEach(row => {
      const qty = Number(row.querySelector('.po-item-qty')?.value || 0);
      const price = Number(row.querySelector('.po-item-price')?.value || 0);
      totalAmount += qty * price;
    });
    const amountField = form.querySelector('[name="amount"]');
    if(amountField) amountField.value = totalAmount.toFixed(2);
  }

  function bindPoItemRow(modal, row){
    const catField = row.querySelector('.po-item-category');
    const itemField = row.querySelector('.po-item-name');
    const qtyField = row.querySelector('.po-item-qty');
    catField?.addEventListener('change', () => {
      populatePoRowItemSelect(row, currentPoSupplierEntry(modal), catField.value);
      recomputePoTotals(modal);
    });
    itemField?.addEventListener('change', () => {
      const priceField = row.querySelector('.po-item-price');
      const selected = itemField.selectedOptions?.[0];
      if(priceField) priceField.value = selected?.dataset?.unitPrice || '';
      recomputePoRowAmount(row);
      recomputePoTotals(modal);
    });
    qtyField?.addEventListener('input', () => { recomputePoRowAmount(row); recomputePoTotals(modal); });
    qtyField?.addEventListener('change', () => { recomputePoRowAmount(row); recomputePoTotals(modal); });
  }

  function addPoItemRow(modal){
    const container = modal?.querySelector('#po-items-rows');
    const template = document.getElementById('po-item-row-template');
    if(!container || !template) return null;
    const row = template.content.firstElementChild.cloneNode(true);
    container.appendChild(row);
    bindPoItemRow(modal, row);
    populatePoRowCategorySelect(row, currentPoSupplierEntry(modal));
    updatePoItemRemoveButtons(modal);
    recomputePoTotals(modal);
    return row;
  }
  function removePoItemRow(btn){
    const modal = btn.closest('.modal-overlay');
    const container = modal?.querySelector('#po-items-rows');
    if(!container || container.children.length <= 1) return; // keep at least one row
    btn.closest('.po-item-row')?.remove();
    updatePoItemRemoveButtons(modal);
    recomputePoTotals(modal);
  }
  function updatePoItemRemoveButtons(modal){
    const rows = [...(modal?.querySelectorAll('.po-item-row') || [])];
    rows.forEach(r => {
      const btn = r.querySelector('.po-item-remove');
      if(btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
  }
  // Reset the item rows back to exactly one empty row (called when the PO
  // modal opens/closes so a previous session's extra rows don't linger).
  function resetPoItemRows(modal){
    const container = modal?.querySelector('#po-items-rows');
    if(!container) return;
    container.innerHTML = '';
    addPoItemRow(modal);
  }
  // Supplier changed: every row shares the same supplier, so repopulate every
  // row's category (and clear its item/price, since the old item may not
  // belong to the new supplier) rather than just the row being edited.
  function refreshAllPoItemRowsForSupplier(modal){
    const entry = currentPoSupplierEntry(modal);
    modal?.querySelectorAll('.po-item-row').forEach(row => populatePoRowCategorySelect(row, entry));
    recomputePoTotals(modal);
  }
  // Reads the item rows back out for submission: {category, name, qty, unitPrice, amount}.
  function collectPoItemRows(modal){
    return [...(modal?.querySelectorAll('.po-item-row') || [])].map(row => {
      const category = row.querySelector('.po-item-category')?.value || '';
      const name = row.querySelector('.po-item-name')?.value || '';
      const qty = Number(row.querySelector('.po-item-qty')?.value || 0);
      const unitPrice = Number(row.querySelector('.po-item-price')?.value || 0);
      return { category, name, qty, unitPrice, amount: qty * unitPrice };
    }).filter(r => r.name && r.qty > 0);
  }

  function lockField(el, locked){
    if(!el) return;
    el.classList.toggle('field-locked', !!locked);
    el.tabIndex = locked ? -1 : 0;
  }

  // Normalizes a Requisition's requested item(s) into a flat list of
  // {name, qty}. Most requisitions carry a single item/qty pair; if a
  // requisition ever carries multiple item lines (reqData.items), one row is
  // generated per line so the PO always mirrors exactly what was requested.
  function buildRequestedItemsList(reqData){
    if(Array.isArray(reqData?.items) && reqData.items.length){
      return reqData.items
        .map(it => ({ name: it.name || it.item || '', qty: Number(it.qty || 0) || 1 }))
        .filter(it => it.name);
    }
    if(reqData?.item){
      return [{ name: reqData.item, qty: Number(reqData.qty || 0) || 1 }];
    }
    return [];
  }

  // Toggles between "request" (Requisition-generated PO â€” Qty fixed,
  // "+ Add Item" hidden) and "manual" (the original "+ New PO" experience).
  // The third mode, "auto", belonged to the defect -> PO conversion, which no
  // button reaches any more; it was removed with the rest of that flow.
  function setPoModalMode(modal, mode){
    if(!modal) return;
    const addBtn = modal.querySelector('#po-add-item-btn');
    const hint = modal.querySelector('#po-items-hint');
    const supplierHint = modal.querySelector('#po-supplier-hint');
    const supplierField = modal.querySelector('#add-po-form [name="supplier"]');
    modal.dataset.poMode = mode;
    if(mode === 'request'){
      if(addBtn) addBtn.style.display = 'none';
      if(hint) hint.textContent = 'Quantity is fixed from the requisition. Select the Supplier, then the Category and Item for each row.';
      if(supplierHint) supplierHint.textContent = 'Select the supplier for this requisition\u2019s item(s).';
      lockField(supplierField, false);
    } else {
      if(addBtn) addBtn.style.display = '';
      if(hint) hint.textContent = 'Select a supplier first to load its categories and items.';
      if(supplierHint) supplierHint.textContent = 'Select a supplier first to load its categories and items.';
      lockField(supplierField, false);
    }
  }

  // Picks the right per-row refresh routine for whatever mode the PO modal is
  // currently in: 'request' rows have a Category select to repopulate, and
  // 'manual' rows use the original Category -> Item cascade.
  function syncPoItemRowsWithSupplier(modal){
    if(modal?.dataset.poMode === 'request') refreshRequestPoItemRowsForSupplier(modal);
    else refreshAllPoItemRowsForSupplier(modal);
  }

  /* ---------- PO modal: requisition-generated rows, manual Supplier/Category
     /Item ---------- Only Qty comes straight from the requisition; Supplier,
     Category and Item are all picked manually, reusing the same Category ->
     Item cascade (populatePoRowCategorySelect / bindPoItemRow) as the fully
     manual "+ New PO" flow. Each row keeps a reminder label of which
     requisitioned item it corresponds to. */
  function renderRequestPoItemRow(container, { reqNum, name, qty }){
    const template = document.getElementById('po-item-row-request-template');
    if(!container || !template) return null;
    const row = template.content.firstElementChild.cloneNode(true);
    const labelField = row.querySelector('.po-item-request-label');
    const qtyField = row.querySelector('.po-item-qty');
    if(labelField) labelField.textContent = `Requisition • ${reqNum || ''} (${name || ''})`;
    if(qtyField) qtyField.value = qty || 0;
    container.appendChild(row);
    recomputePoRowAmount(row);
    return row;
  }

  // Generates one row per requested item (Qty fixed; Supplier/Category/Item
  // all left for the user to pick manually).
  function populateRequestPoItemRows(modal, reqData){
    const container = modal?.querySelector('#po-items-rows');
    const supplierField = modal?.querySelector('#add-po-form [name="supplier"]');
    if(!container) return;
    container.innerHTML = '';
    const requested = buildRequestedItemsList(reqData);
    requested.forEach(entry => {
      const row = renderRequestPoItemRow(container, { reqNum: reqData?.reqNum, name: entry.name, qty: entry.qty });
      if(row){
        bindPoItemRow(modal, row);
        populatePoRowCategorySelect(row, currentPoSupplierEntry(modal));
      }
    });
    if(supplierField) supplierField.value = '';
    setPoModalMode(modal, 'request');
    recomputePoTotals(modal);
  }

  // Supplier changed while in request mode: repopulate every row's Category
  // (and reset its Item/price, same as the manual flow) from the newly
  // selected supplier.
  function refreshRequestPoItemRowsForSupplier(modal){
    const entry = currentPoSupplierEntry(modal);
    modal?.querySelectorAll('.po-item-row-request').forEach(row => populatePoRowCategorySelect(row, entry));
    recomputePoTotals(modal);
  }

  /* ---------- PO modal: recommended items ----------
   * The server ranks a supplier's items by how often they have actually been
   * ordered before (GET purchase-orders/suggestions). Picking one fills an
   * item row with the usual quantity and the last price paid, so a repeat
   * order takes one click instead of three selects and two number fields.
   *
   * The map is fetched once per page and reused for every modal open. */
  let poSuggestionsCache = null;
  let poSuggestionsRequest = null;

  function loadPoSuggestions(){
    if(poSuggestionsCache) return Promise.resolve(poSuggestionsCache);
    if(poSuggestionsRequest) return poSuggestionsRequest;
    poSuggestionsRequest = fetch(procurementUrl('purchase-orders/suggestions'), {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(res => res.ok ? res.json() : {})
      .then(json => {
        poSuggestionsCache = (json && typeof json === 'object') ? json : {};
        return poSuggestionsCache;
      })
      .catch(() => { poSuggestionsCache = {}; return poSuggestionsCache; })
      .finally(() => { poSuggestionsRequest = null; });
    return poSuggestionsRequest;
  }

  function togglePoSuggestions(){
    const list = document.getElementById('po-suggest-list');
    const toggle = document.getElementById('po-suggest-toggle');
    if(!list || !toggle) return;
    const hidden = list.classList.toggle('collapsed');
    toggle.textContent = hidden ? 'Show' : 'Hide';
  }

  // Renders the suggestion chips for whichever supplier is selected. Hides the
  // whole block when there is no history for that supplier, so a first-time
  // supplier shows nothing rather than an empty panel.
  function renderPoSuggestions(modal){
    const block = document.getElementById('po-suggest-block');
    const list = document.getElementById('po-suggest-list');
    if(!block || !list) return;

    // Only the manual "+ New PO" flow benefits; requisition-generated rows
    // already carry their items.
    if(modal?.dataset.poMode !== 'manual'){ block.style.display = 'none'; return; }

    const supplier = (modal.querySelector('#add-po-form [name="supplier"]')?.value || '').trim();
    const items = (poSuggestionsCache && poSuggestionsCache[supplier]) || [];
    if(!supplier || !items.length){ block.style.display = 'none'; return; }

    block.style.display = '';
    list.classList.remove('collapsed');
    const toggle = document.getElementById('po-suggest-toggle');
    if(toggle) toggle.textContent = 'Hide';

    list.innerHTML = items.map((it, idx) => `
      <button type="button" class="po-suggest-item" data-index="${idx}">
        <span class="po-suggest-main">
          <span class="po-suggest-name">${htmlEscape(it.name || '')}</span>
          <span class="po-suggest-meta">${htmlEscape(it.category || 'Uncategorized')} &middot; usually ${Number(it.qty || 1)} pc &middot; &#8369;${Number(it.unitPrice || 0).toFixed(2)}</span>
        </span>
        <span class="po-suggest-count">${Number(it.times || 0)}&times;</span>
      </button>
    `).join('');

    list.querySelectorAll('.po-suggest-item').forEach(btn => {
      btn.addEventListener('click', () => applyPoSuggestion(modal, items[Number(btn.dataset.index)]));
    });
  }

  // Fills the first empty item row (or appends one) with the suggestion.
  function applyPoSuggestion(modal, suggestion){
    if(!modal || !suggestion) return;
    const container = modal.querySelector('#po-items-rows');
    if(!container) return;

    let row = [...container.querySelectorAll('.po-item-row')]
      .find(r => !(r.querySelector('.po-item-name')?.value || '').trim());
    if(!row) row = addPoItemRow(modal);
    if(!row) return;

    const entry = currentPoSupplierEntry(modal);
    const category = suggestion.category || 'Uncategorized';

    const catField = row.querySelector('.po-item-category');
    if(catField){
      // The category select is built from the supplier catalog; if this
      // historical category is no longer in the catalog, add it back so the
      // suggestion is still selectable.
      if(![...catField.options].some(o => o.value === category)){
        const opt = document.createElement('option');
        opt.value = category;
        opt.textContent = category;
        catField.appendChild(opt);
      }
      catField.value = category;
    }
    populatePoRowItemSelect(row, entry, category);

    const itemField = row.querySelector('.po-item-name');
    if(itemField){
      if(![...itemField.options].some(o => o.value === suggestion.name)){
        const opt = document.createElement('option');
        opt.value = suggestion.name;
        opt.textContent = suggestion.name;
        opt.dataset.unitPrice = Number(suggestion.unitPrice || 0);
        itemField.appendChild(opt);
      }
      itemField.disabled = false;
      itemField.value = suggestion.name;
    }

    const priceField = row.querySelector('.po-item-price');
    if(priceField) priceField.value = Number(suggestion.unitPrice || 0).toFixed(2);
    const qtyField = row.querySelector('.po-item-qty');
    if(qtyField) qtyField.value = Number(suggestion.qty || 1);

    recomputePoRowAmount(row);
    recomputePoTotals(modal);
    showToast(`${suggestion.name} added from your order history`, 'ok');
  }

  function bindPoFormAutofill(modal){
    const form = modal?.querySelector('#add-po-form');
    if(!form || form.__poAutofillBound) return;
    form.__poAutofillBound = true;
    const supplierField = form.querySelector('[name="supplier"]');
    // Selecting a supplier loads that supplier's categories/items into every
    // item row (request or manual mode) â€” it must never touch a row's unit
    // price on its own; auto-mode rows are already fully locked.
    supplierField?.addEventListener('change', () => {
      syncPoItemRowsWithSupplier(modal);
      renderPoSuggestions(modal);
    });
  }

  function refreshDeliveryPoOptions(){
    const modal = document.getElementById('add-delivery-modal');
    const poField = modal?.querySelector('[name="po"]');
    if(!poField) return;

    const currentValue = poField.value || '';
    const approvedRows = [...document.querySelectorAll('#po-table tbody tr')].filter(row => {
      const status = String(row.dataset.status || textFrom(row.children[5]) || '').toLowerCase().trim();
      return status === 'approved';
    });
    const noApprovedText = 'No approved purchase orders available';

    if(approvedRows.length){
      poField.innerHTML = '<option value="">Select PO...</option>' + approvedRows.map(row => {
        const poNumber = htmlEscape(textFrom(row.children[0]));
        return `<option value="${poNumber}"${poNumber === currentValue ? ' selected' : ''}>${poNumber}</option>`;
      }).join('');
      window.APPROVED_PO_CACHE = approvedRows.reduce((acc,row) => {
        const poNumber = textFrom(row.children[0]);
        acc[poNumber] = {
          po: poNumber,
          supplier: supplierNameFromCell(row.children[1]),
          item: row.dataset.item || textFrom(row.children[2]) || '',
          items: getPoItems(row),
          qty: row.dataset.qty || '',
          unitPrice: row.dataset.unitPrice || '',
          amount: row.dataset.amount || '',
          status: String(row.dataset.status || textFrom(row.children[5]) || '').toLowerCase().trim(),
          expected: row.dataset.expected || ''
        };
        return acc;
      }, {});
      return;
    }

    poField.innerHTML = `<option value="">${noApprovedText}</option>`;
    fetch(procurementUrl('purchase-orders/approved'), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => res.ok ? res.json() : Promise.reject()).then(data => {
      if(!Array.isArray(data)) throw new Error('Invalid response');
      if(!data.length){
        poField.innerHTML = `<option value="">${noApprovedText}</option>`;
        window.APPROVED_PO_CACHE = {};
        return;
      }
      window.APPROVED_PO_CACHE = data.reduce((acc, item) => {
        if(item.po_number){
          acc[item.po_number] = {
            po: item.po_number,
            supplier: item.supplier_name || 'â€”',
            item: item.item || '',
            items: Array.isArray(item.items) ? item.items : [],
            qty: item.qty || '',
            unitPrice: item.unit_price || '',
            amount: item.amount || '',
            status: String(item.status || 'approved').toLowerCase().trim(),
            expected: item.expected_delivery_date || ''
          };
        }
        return acc;
      }, {});
      poField.innerHTML = '<option value="">Select PO...</option>' + data.map(po => {
        const poNumber = htmlEscape(po.po_number || '');
        return `<option value="${poNumber}"${poNumber === currentValue ? ' selected' : ''}>${poNumber}</option>`;
      }).join('');
    }).catch(() => {
      poField.innerHTML = `<option value="">${noApprovedText}</option>`;
    });
  }

  function getPoInfo(poNumber){
    const trimmed = String(poNumber || '').trim();
    if(!trimmed) return null;
    const domRow = findPoRowByNumber(trimmed);
    if(domRow){
      return {
        po: trimmed,
        supplier: resolveSupplierByPO(trimmed) || '',
        item: domRow.dataset.item || textFrom(domRow.children[2]) || '',
        items: getPoItems(domRow),
        qty: domRow.dataset.qty || '',
        unitPrice: domRow.dataset.unitPrice || '',
        amount: domRow.dataset.amount || '',
        status: String(domRow.dataset.status || textFrom(domRow.children[5]) || '').toLowerCase().trim(),
        expected: domRow.dataset.expected || ''
      };
    }
    return window.APPROVED_PO_CACHE?.[trimmed] || null;
  }

  /* ---------- Delivery: items-purchased chips (replaces the manual Item field) ---------- */
  function renderDeliveryItemChips(poInfo){
    const wrap = document.getElementById('delivery-items-chips');
    const hidden = document.getElementById('delivery-items-value');
    if(!wrap) return;
    const items = Array.isArray(poInfo?.items) && poInfo.items.length
      ? poInfo.items
      : (poInfo?.item ? [{ name: poInfo.item, qty: poInfo.qty, unitPrice: poInfo.unitPrice }] : []);
    if(!items.length){
      wrap.innerHTML = '<div class="product-list-empty">No items found for this purchase order.</div>';
      if(hidden) hidden.value = '';
      return;
    }
    wrap.innerHTML = items.map(it => `<span class="product-chip"><span>${htmlEscape(it.name || 'Item')}</span><span class="meta">Qty ${Number(it.qty || 0)}${it.unitPrice ? ' &middot; &#8369;' + Number(it.unitPrice).toFixed(2) : ''}</span></span>`).join('');
    if(hidden) hidden.value = items.map(it => it.name).filter(Boolean).join(', ');
  }
  function resetDeliveryItemChips(){
    const wrap = document.getElementById('delivery-items-chips');
    const hidden = document.getElementById('delivery-items-value');
    if(wrap) wrap.innerHTML = '<div class="product-list-empty">Select a PO to load its items.</div>';
    if(hidden) hidden.value = '';
  }

  function bindDeliveryPoAutofill(modal){
    const form = modal?.querySelector('#add-delivery-form');
    if(!form || form.__deliveryPoBound) return;
    form.__deliveryPoBound = true;
    const poField = form.querySelector('[name="po"]');
    const supplierField = form.querySelector('[name="supplier"]');
    const amountField = form.querySelector('[name="amount"]');
    const update = () => {
      const poNumber = (poField?.value || '').trim();
      if(!poNumber){ resetDeliveryItemChips(); return; }
      const poInfo = getPoInfo(poNumber);
      if(!poInfo) return;
      if(supplierField){
        supplierField.value = poInfo.supplier || '';
      }
      renderDeliveryItemChips(poInfo);
      // Total Amount mirrors the total amount that was ordered on the PO.
      if(amountField){
        const poAmount = Number(poInfo.amount || 0);
        if(poAmount) amountField.value = poAmount.toFixed(2);
      }
    };
    // 'change' only. A <select> fires both 'change' and 'input' for the same
    // interaction, so binding both ran this whole routine twice per pick.
    poField?.addEventListener('change', update);
  }

  function openAddModal(kind, reqData = null){
    const modal = document.getElementById(ADD_MODAL_MAP[kind]);
    if(!modal) return;
    
    // Handle PO modal title for requisition conversion
    const poTitle = modal.querySelector('#add-po-modal h3');
    if(poTitle && kind === 'po'){
      if(reqData && reqData.reqNum){
        poTitle.textContent = `Create New Purchase - REQ ${reqData.reqNum}`;
      } else {
        poTitle.textContent = 'Create New Purchase Order';
      }
    }

    if(kind==='supplier'){
      setSupplierProductEditor('supplier-products-list', 'supplier-products-json');
      resetSupplierProductDraft();
    }

    // Pre-fill auto-generated IDs & default dates
    const yr = new Date().getFullYear();
    if(kind==='po'){
      bindPoFormAutofill(modal);
      // Prefer the server-provided real next sequence (window.nextPoSeq) so the
      // number doesn't collide with existing POs; fall back to the local counter.
      const poNum = Number(window.nextPoSeq) > 0 ? Number(window.nextPoSeq) : (ID_COUNTS.po + 1);
      setModalFieldValue(modal, 'po', `PO-${yr}-${pad(poNum,4)}`);
      const exp = new Date(); exp.setDate(exp.getDate()+7);
      setModalFieldValue(modal, 'expected', todayISO(exp));
      const createdByField = modal.querySelector('[name="createdBy"]');
      if(createdByField){
        createdByField.value = getCurrentEmployeeName() || createdByField.value;
        createdByField.setAttribute('readonly', 'readonly');
      }

      // Requisition conversions fix Item and Qty from the requisition â€”
      // Supplier and Category are picked manually by the user. The "+ New PO"
      // toolbar button (no reqData at all) keeps the original fully-manual
      // entry experience.
      const isRequestFlow = !!(reqData && reqData.reqNum);

      // Always set reqRef/priority explicitly (both directions) so a PO created
      // straight from this page doesn't inherit a leftover requisition
      // reference/priority from a previous conversion â€” form.reset() can't
      // clear these because their defaultValue gets mutated once assigned.
      if(reqData && reqData.reqNum){
        setModalFieldValue(modal, 'reqRef', reqData.reqNum || '');
        setModalFieldValue(modal, 'priority', normalizePriorityLabel(reqData.priority));
      } else {
        setModalFieldValue(modal, 'reqRef', '');
        setModalFieldValue(modal, 'priority', 'Normal');
      }

      if(isRequestFlow){
        setPoModalMode(modal, 'request');
      } else {
        setPoModalMode(modal, 'manual');
        resetPoItemRows(modal);
      }

      // One load of the supplier catalog, then generate rows from it. This
      // previously called refreshPoSupplierOptions() immediately and *again*
      // inside a 60ms timeout: two /suppliers requests per modal open and two
      // racing innerHTML writes into the same <select>.
      // Warm the recommendation cache alongside the supplier catalog.
      loadPoSuggestions().then(() => renderPoSuggestions(modal));

      Promise.resolve(refreshPoSupplierOptions(modal)).then(() => {
        if(isRequestFlow){
          // Requisition -> PO: one row per requested item (Item/Qty fixed,
          // shown as a label); Supplier and Category are left for the user
          // to pick manually.
          populateRequestPoItemRows(modal, reqData);
        }
        recomputePoTotals(modal);
        renderPoSuggestions(modal);
      });
    } else if(kind==='delivery'){
      const shpNum = Number(window.nextShipmentSeq) > 0 ? Number(window.nextShipmentSeq) : (ID_COUNTS.dr + 1);
      setModalFieldValue(modal, 'dr', `SHP-${yr}-${pad(shpNum,4)}`);
      setModalFieldValue(modal, 'delDate', todayISO());
      resetDeliveryItemChips();
      refreshDeliveryPoOptions();
      bindDeliveryPoAutofill(modal);
    } else if(kind==='supplier'){
      setModalFieldValue(modal, 'sid', `SUP-${pad(NEXT_ID.sup,4)}`);
    }

    modal.classList.add('open');
    // focus first editable input
    setTimeout(()=>{
      const focusable = modal.querySelector('input:not([readonly]), select, textarea');
      if(focusable) focusable.focus();
    }, 60);
  }

  /* ---------- Approved PO -> Log Delivery ----------
   * Opens the Log Delivery modal already pointed at this purchase order, so
   * the PO number, supplier, items and amount are filled in rather than
   * re-selected by hand.
   *
   * refreshDeliveryPoOptions() populates the PO <select> from the page's own
   * rows when it can and falls back to a fetch when it can't, so the selection
   * is retried for a short while instead of assuming the options are ready. */
  function openLogDeliveryForPO(row){
    // PO rows carry the number in their first cell, not a data attribute.
    const poNumber = textFrom(row?.children?.[0]).trim();
    closeViewModal();
    openAddModal('delivery');
    if(!poNumber) return;

    const modal = document.getElementById('add-delivery-modal');
    const poField = modal?.querySelector('[name="po"]');
    if(!poField) return;

    let attempts = 0;
    const select = () => {
      const option = [...poField.options].find(o => o.value === poNumber);
      if(option){
        poField.value = poNumber;
        // Fire the same event a manual pick would, so bindDeliveryPoAutofill()
        // fills supplier / item chips / amount.
        poField.dispatchEvent(new Event('change', { bubbles: true }));
        return;
      }
      if(++attempts < 20){ setTimeout(select, 100); return; }
      showToast(`${poNumber} is not available for delivery logging right now.`, 'no');
    };
    select();
  }

  function closeAddModal(kind){
    const modal = document.getElementById(ADD_MODAL_MAP[kind]);
    if(modal){
      modal.classList.remove('open');
      const form = modal.querySelector('form');
      if(form) form.reset();
      
      // Reset PO modal title and mode when closing
      if(kind === 'po'){
        const poTitle = modal.querySelector('h3');
        if(poTitle) poTitle.textContent = 'Create New Purchase Order';
        setPoModalMode(modal, 'manual');
        resetPoItemRows(modal);
        const suggestBlock = document.getElementById('po-suggest-block');
        if(suggestBlock) suggestBlock.style.display = 'none';
      }
    }
    if(kind==='supplier'){
      resetSupplierProductDraft();
    }
    if(kind==='delivery'){
      resetDeliveryItemChips();
    }
  }

  // Normalize any priority string (e.g. "URGENT", "high") to the Title-case
  // label stored on the PO (Urgent / High / Normal / Low).
  function normalizePriorityLabel(value){
    const key = String(value || '').trim().toLowerCase();
    const map = { urgent: 'Urgent', high: 'High', normal: 'Normal', low: 'Low' };
    return map[key] || 'Normal';
  }

  // Convert Requisition to PO â€” carry the requisition's priority through so the
  // new PO keeps the requested priority instead of defaulting to Normal.
  function convertReqToPO(reqNum, item, qty, priority, items){
    const reqData = { reqNum, item, qty, priority, items: Array.isArray(items) ? items : null };
    openAddModal('po', reqData);
  }

  let cancelPOData = null;
  function createPOFromView(){
    const modal = document.getElementById('view-modal');
    const row = modal.__row;
    if(!row) return;
    const record = buildRecord(row);
    if(record.type === 'req'){
      convertReqToPO(record.ref, record.item, record.qty, record.priority);
      closeViewModal();
    }
  }
  /* ---------- Supplier products ----------
   * Products are edited as inline blocks inside the supplier form (Add and
   * Edit both use the same markup), instead of the old "open a second modal to
   * add one chip at a time" flow. The blocks are the source of truth; the
   * hidden productsJson input is rebuilt from them on every keystroke, which is
   * what actually gets posted.
   *
   * The standalone Add Product modal survives for the "+ Product" button on a
   * supplier card, where there is no open supplier form to type into. */
  let supplierProductCounter = 1;
  let supplierProductEditor = { listId: 'supplier-products-list', hiddenId: 'supplier-products-json' };

  function setSupplierProductEditor(listId, hiddenId){
    supplierProductEditor = { listId, hiddenId };
  }

  function supplierProductListEl(){
    return document.getElementById(supplierProductEditor.listId);
  }

  // Read every inline block back out into the shape the server stores in
  // suppliers.product_items: {name, sku, price, category}.
  // Blocks with no name are skipped rather than saved as blank products.
  function collectSupplierProductRows(){
    const list = supplierProductListEl();
    if(!list) return [];
    return [...list.querySelectorAll('.product-row')].map(row => ({
      name: (row.querySelector('.sp-name')?.value || '').trim(),
      sku: (row.querySelector('.sp-sku')?.value || '').trim(),
      price: Number(row.querySelector('.sp-price')?.value || 0),
      category: (row.querySelector('.sp-category')?.value || '').trim()
    })).filter(p => p.name);
  }

  // Mirror the blocks into the hidden field. Deliberately does NOT re-render:
  // rewriting the markup on each keystroke would blur the input being typed in.
  function syncSupplierProductRows(){
    const hidden = document.getElementById(supplierProductEditor.hiddenId);
    if(hidden) hidden.value = JSON.stringify(collectSupplierProductRows());
    updateSupplierProductRemoveButtons();
  }

  function updateSupplierProductRemoveButtons(){
    const list = supplierProductListEl();
    if(!list) return;
    const rows = [...list.querySelectorAll('.product-row')];
    rows.forEach(r => {
      const btn = r.querySelector('.product-row-remove');
      // Keep at least one block on screen so the section is never empty.
      if(btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
  }

  function addSupplierProductRow(values){
    const list = supplierProductListEl();
    const template = document.getElementById('supplier-product-row-template');
    if(!list || !template) return null;
    const row = template.content.firstElementChild.cloneNode(true);
    if(values){
      const set = (sel, v) => { const el = row.querySelector(sel); if(el) el.value = v ?? ''; };
      set('.sp-name', values.name);
      set('.sp-sku', values.sku);
      set('.sp-price', values.price != null ? Number(values.price) : '');
      set('.sp-category', values.category);
    }
    list.appendChild(row);
    syncSupplierProductRows();
    return row;
  }

  function removeSupplierProductRow(btn){
    const list = supplierProductListEl();
    if(!list || list.querySelectorAll('.product-row').length <= 1) return;
    btn.closest('.product-row')?.remove();
    syncSupplierProductRows();
  }

  // Rebuild the whole editor from a saved product array (Edit Supplier), or
  // from nothing but one blank block (Add Supplier).
  function renderSupplierProductList(products){
    const list = supplierProductListEl();
    if(!list) return;
    list.innerHTML = '';
    const items = Array.isArray(products) ? products.filter(p => p && p.name) : [];
    if(items.length){
      items.forEach(p => addSupplierProductRow(p));
    } else {
      addSupplierProductRow();
    }
    syncSupplierProductRows();
  }

  function resetSupplierProductDraft(){
    supplierProductCounter = 1;
    renderSupplierProductList([]);
  }

  /* ---------- Standalone "+ Product" modal (from a supplier card) ---------- */
  let supplierProductTargetCard = null;

  function openSupplierProductModal(trigger){
    const modal = document.getElementById('add-supplier-product-modal');
    const form = document.getElementById('add-supplier-product-form');
    if(!modal || !form) return;

    // The button lives inside a supplier card; remember which one so the new
    // product is appended to that supplier's existing catalog.
    supplierProductTargetCard = trigger?.closest?.('.supplier-card') || null;
    const label = document.getElementById('supplier-product-modal-target');
    if(label){
      const name = supplierProductTargetCard?.dataset.name || '';
      label.textContent = name ? ` — ${name}` : '';
    }

    form.reset();
    const skuInput = form.querySelector('[name="productSku"]');
    if(skuInput) skuInput.value = generateSupplierProductSku('');
    modal.classList.add('open');
    setTimeout(() => form.querySelector('[name="productName"]')?.focus(), 60);
  }

  function closeSupplierProductModal(){
    const modal = document.getElementById('add-supplier-product-modal');
    if(modal){
      modal.classList.remove('open');
      document.getElementById('add-supplier-product-form')?.reset();
    }
    supplierProductTargetCard = null;
  }

  // SKU is suggested from the product name but stays editable — the old
  // auto/manual dropdown was an extra control for no benefit.
  function syncSupplierProductSku(nameInput){
    const form = document.getElementById('add-supplier-product-form');
    const skuInput = form?.querySelector('[name="productSku"]');
    if(!skuInput || skuInput.dataset.touched === '1') return;
    skuInput.value = generateSupplierProductSku(nameInput.value);
  }

  function generateSupplierProductSku(name){
    const trimmed = (name || '').trim();
    const base = trimmed ? trimmed.toUpperCase().replace(/[^A-Z0-9]+/g, '').slice(0, 8) : 'PRD';
    return `${base}${String(supplierProductCounter).padStart(3, '0')}`;
  }

  function submitSupplierProduct(e){
    e.preventDefault();
    const form = e.target;
    const d = Object.fromEntries(new FormData(form).entries());
    const name = (d.productName || '').trim();
    if(!name) return;

    const product = {
      name,
      sku: (d.productSku || '').trim() || generateSupplierProductSku(name),
      price: Number(d.productPrice || 0),
      category: (d.productCategory || '').trim()
    };
    supplierProductCounter += 1;

    // Opened from a supplier card: append to that supplier and persist now.
    if(supplierProductTargetCard){
      saveProductToSupplierCard(supplierProductTargetCard, product);
      closeSupplierProductModal();
      return;
    }

    // Opened while a supplier form is on screen: just add another block.
    addSupplierProductRow(product);
    closeSupplierProductModal();
  }

  // Appends one product to an existing supplier and PUTs the whole catalog
  // back, then repaints the card so the change is visible without a reload.
  function saveProductToSupplierCard(card, product){
    const id = card.dataset.id;
    if(!id){
      showToast('This supplier has no id yet — reload the page and try again.', 'no');
      return;
    }
    const products = getSupplierProducts(card);
    products.push(product);

    fetch(procurementUrl(`suppliers/${id}`), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: new URLSearchParams({ productsJson: JSON.stringify(products) }).toString()
    }).then(async res => {
      const json = await res.json().catch(() => ({}));
      if(!res.ok) throw new Error(json?.message || 'Unable to save the product.');
      card.dataset.products = JSON.stringify(products);
      renderSupplierCardProducts(card);
      refreshSupplierCatalogFromCards();
      showToast(`${product.name} added to ${card.dataset.name || 'supplier'}`, 'ok');
    }).catch(err => showToast(err?.message || 'Unable to save the product.', 'no'));
  }

  // Cancelling is reached from the PO's View Details modal, which passes the
  // row directly. The button-based openCancelModal(btn) variant had no caller.
  function openCancelModalFromRow(row){
    const poNum = row?.cells[0]?.textContent?.trim() || 'this PO';
    cancelPOData = { row, poNum };
    document.getElementById('cancel-po-number').textContent = poNum;
    document.getElementById('cancel-po-modal').classList.add('open');
    closeViewModal();
  }
  function closeCancelModal(){
    document.getElementById('cancel-po-modal').classList.remove('open');
    cancelPOData = null;
  }
  function confirmCancelPO(){
    if(cancelPOData && cancelPOData.row){
      const row = cancelPOData.row;
      row.classList.add('cancelled-row');
      // cells[6] is the DATE column — this used to stamp the status pill over
      // the order date and leave the real Status cell reading "Pending".
      // updateRowStatus() knows the right index (5) for every table.
      updateRowStatus(row, 'Cancelled');
      findDefectRowsByPO(cancelPOData.poNum || '').forEach(r => updateDefectStatus(r, 'Cancelled'));
      // Previously this only updated the DOM â€” the cancellation was never sent
      // to the server, so refreshing the page silently reverted the PO (and any
      // requisition derived from it) back to its old status.
      if(typeof persistPurchaseOrderStatus === 'function'){
        persistPurchaseOrderStatus(row, 'Cancelled').then(() => {
          if(typeof syncRelatedRequisitionStatusForPO === 'function') syncRelatedRequisitionStatusForPO(row, 'Cancelled');
        }).catch(() => {});
      }
      if(typeof pollLiveStats === 'function') pollLiveStats();
      showToast(`PO ${cancelPOData.poNum} cancelled`, 'no');
    }
    closeCancelModal();
  }

  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      Object.keys(ADD_MODAL_MAP).forEach(closeAddModal);
      closeCancelModal();
    }
  });

  /* ---------- Submit handlers ---------- */

  function submitAddPO(e){
    e.preventDefault();
    const form = e.target;
    const modal = form.closest('.modal-overlay');
    const d = Object.fromEntries(new FormData(form).entries());
    const items = collectPoItemRows(modal);
    if(!items.length){
      showToast('Add at least one item (category, item, and quantity) before submitting.', 'no');
      return;
    }
    const qtyNum = items.reduce((sum, it) => sum + it.qty, 0);
    const amountNum = items.reduce((sum, it) => sum + it.amount, 0);
    const primaryCategory = items[0].category || '';
    const itemSummary = items.map(it => it.name).join(', ');
    const poDate = todayISO();
    const priorityLabel = d.priority || 'Normal';

    fetch(procurementUrl('purchase-orders'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        po: d.po,
        supplier: d.supplier,
        category: primaryCategory,
        items: JSON.stringify(items),
        priority: priorityLabel,
        expected: d.expected || '',
        createdBy: d.createdBy || '',
        remarks: d.remarks || '',
        reqRef: d.reqRef || ''
      }).toString()
    }).then(res => res.json().then(json => ({ ok: res.ok, json }))).then(({ ok, json }) => {
      if(!ok){
        showToast(json?.message || 'Unable to save purchase order right now.', 'no');
        return;
      }
      if(json && json.po_number) d.po = json.po_number;
      const table = document.querySelector('#po-table tbody');
      if(table){
        const tr = document.createElement('tr');
        tr.dataset.status = 'pending';
        tr.dataset.date = poDate;
        tr.dataset.amount = amountNum;
        tr.dataset.item = itemSummary;
        tr.dataset.items = JSON.stringify(items.map(it => ({ name: it.name, qty: it.qty, unitPrice: it.unitPrice })));
        tr.dataset.expected = d.expected || '';
        tr.dataset.remarks = d.remarks || '';
        tr.dataset.requestedBy = d.createdBy || 'Procurement Team';
        tr.dataset.supplier = d.supplier || '';
        tr.dataset.brand = primaryCategory;
        tr.dataset.qty = qtyNum;
        tr.dataset.unitPrice = items[0].unitPrice || 0;
        tr.dataset.reqRef = d.reqRef || '';
        tr.dataset.priority = priorityLabel;
        tr.dataset.delivery = 'Pending';
        tr.innerHTML = `
          <td><a class="po-link">${htmlEscape(d.po)}</a></td>
          <td>${supplierPill(d.supplier || 'Unknown Supplier')}</td>
          <td style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="${htmlEscape(itemSummary)}">${htmlEscape(items[0].name || 'â€”')}${items.length > 1 ? `<span class="item-more">+${items.length - 1} more</span>` : ''}</td>
          <td><b>${money(amountNum)}</b></td>
          <td>${priorityBadge(priorityLabel)}</td>
          <td>${statusPill('Pending')}</td>
          <td>${fmtDate(poDate)}</td>
          <td><span class="row-actions"><button title="View"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button><button title="Edit"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button><button class="del" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button></span></td>`;
        if(json && (json.id || (json.data && json.data.id))) tr.dataset.id = json.id || json.data.id;
        table.prepend(tr);
      }
      NEXT_ID.po++;
      ID_COUNTS.po++;
      if(d.reqRef){
        const reqRow = findReqRowByRef(d.reqRef);
        if(reqRow){
          // Creating the PO already moved the requisition to Processing
          // server-side (and across every row sharing its req_id), so just
          // repaint the badge here â€” no second write.
          reqRow.dataset.po = d.po;
          updateRowStatus(reqRow, json?.requisition_status || 'Processing');
        }
      }
      initRowActionButtons();
      poSuggestionsCache = null; // this PO changes the recommendations
      showToast(`Purchase Order ${d.po} created`, 'ok');
      closeAddModal('po');
      if(typeof pollLiveStats === 'function') pollLiveStats();
    }).catch(() => {
      showToast('Unable to save purchase order right now.', 'no');
    });
  }

  function submitAddSupplier(e){
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.target).entries());
    // Products are read straight from the inline blocks so a half-typed last
    // row is still captured.
    let products = [];
    try { products = JSON.parse(d.productsJson || '[]'); } catch { products = []; }
    if(!Array.isArray(products)) products = [];
    if(!products.length) products = collectSupplierProductRows();

    fetch(procurementUrl('suppliers'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        sid: d.sid || '',
        name: d.name || '',
        contact: d.contact || '',
        email: d.email || '',
        phone: d.phone || '',
        address: d.address || '',
        status: d.status || 'active',
        warehouse_id: d.warehouse_id || '',
        productsJson: JSON.stringify(products)
      }).toString()
    }).then(async res => {
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json?.message || 'Unable to save supplier right now.');
      return json;
    }).then(json => {
      // The Suppliers page renders cards. Build one so a newly saved supplier
      // shows up without a reload, matching the server-rendered markup.
      const cardWrap = document.getElementById('suppliers-cards');
      if(cardWrap){
        cardWrap.querySelector('.sc-empty-state')?.remove();
        const categories = products.map(p => String(p.category || '').trim()).filter(Boolean);
        const subtitle = categories.length
          ? categories.sort((a, b) =>
              categories.filter(c => c === b).length - categories.filter(c => c === a).length)[0]
          : '';
        const statusLabel = (d.status || 'active').replace(/^./, m => m.toUpperCase());
        const card = document.createElement('article');
        card.className = 'supplier-card';
        card.dataset.id = (json && (json.id || json.data?.id)) || '';
        card.dataset.sid = d.sid || '';
        card.dataset.name = d.name || '';
        card.dataset.contact = d.contact || '';
        card.dataset.email = d.email || '';
        card.dataset.phone = d.phone || '';
        card.dataset.address = d.address || '';
        card.dataset.status = (d.status || 'active').toLowerCase();
        card.dataset.statusLabel = statusLabel;
        card.dataset.category = subtitle;
        card.dataset.warehouseId = d.warehouse_id || '';
        card.dataset.poCount = '0';
        card.dataset.products = JSON.stringify(products);
        card.innerHTML = `
          <header class="sc-head">
            <span class="sc-avatar" style="background:${supplierBadgeColor(d.name)}">${htmlEscape(initials(d.name || 'NA'))}</span>
            <div class="sc-ident">
              <h3>${htmlEscape(d.name || '')}</h3>
              <p>${htmlEscape(subtitle || 'No category yet')}</p>
            </div>
            <span class="sc-status ${(d.status || 'active').toLowerCase()}"><i></i>${htmlEscape(statusLabel)}</span>
          </header>
          <ul class="sc-contact">
            <li><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M4.5 20c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><span>${htmlEscape(d.contact || '—')}</span></li>
            <li><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3.5 7l8.5 6 8.5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><span>${htmlEscape(d.email || '—')}</span></li>
            <li><svg viewBox="0 0 24 24" fill="none"><path d="M5 4h3.5l1.8 4.4-2.2 1.6a12 12 0 0 0 5.9 5.9l1.6-2.2L20 15.5V19a1 1 0 0 1-1.1 1A15.9 15.9 0 0 1 4 5.1 1 1 0 0 1 5 4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg><span>${htmlEscape(d.phone || '—')}</span></li>
            <li><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg><span>${htmlEscape(d.address || '—')}</span></li>
          </ul>
          <div class="sc-products">
            <h4><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7V5.5A1.5 1.5 0 0 1 9.5 4h5A1.5 1.5 0 0 1 16 5.5V7" stroke="currentColor" stroke-width="2"/></svg> Products (${products.length})</h4>
          </div>
          <footer class="sc-foot">
            <span class="sc-po-count"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3h7l4 4v14H7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg> 0 POs placed</span>
            <button type="button" class="sc-add-product" onclick="openSupplierProductModal(this)"><span aria-hidden="true">+</span> Product</button>
          </footer>`;
        cardWrap.prepend(card);
        renderSupplierCardProducts(card);
      }
      NEXT_ID.sup++;
      // supplier IDs are NEXT_ID-based; no change to ID_COUNTS for suppliers
      initRowActionButtons();
      addSupplierOptionToPoForm(d.name);
      refreshPoSupplierOptions(document.getElementById('add-po-modal'));
      refreshDeliverySupplierOptions();
      refreshSupplierCatalogFromCards();
      if(typeof refreshSupplierCategoryOptions === 'function') refreshSupplierCategoryOptions();
      if(typeof pollLiveStats === 'function') pollLiveStats();
      showToast(`Supplier ${d.name} added successfully`, 'ok');
      closeAddModal('supplier');
    }).catch(error => {
      showToast(error.message || 'Unable to save supplier right now.', 'no');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    refreshDeliverySupplierOptions();
  });

  function submitAddDelivery(e){
    e.preventDefault();
    const d = Object.fromEntries(new FormData(e.target).entries());
    const poInfo = getPoInfo(d.po || '');
    if(!poInfo || poInfo.status !== 'approved'){
      showToast('Only approved purchase orders can be logged in deliveries.', 'info');
      return;
    }
    if(!(d.items || '').trim()){
      showToast('Select a purchase order so its items can be loaded before logging the delivery.', 'no');
      return;
    }
    // The delivery form has no quantity input, so `d.qty` was always undefined
    // and every shipment was stored as qty 1 / qty_expected 1 regardless of
    // what the PO actually ordered. Take the real total from the PO's items.
    const poItems = Array.isArray(poInfo.items) ? poInfo.items : [];
    const deliveryQty = poItems.length
      ? poItems.reduce((sum, it) => sum + (Number(it.qty) || 0), 0)
      : (Number(poInfo.qty) || 0);
    if(!(deliveryQty > 0)){
      showToast('This purchase order has no quantity to deliver.', 'no');
      return;
    }
    const expectedDate = poInfo.expected || '';
    const isDelayed = Boolean(expectedDate && new Date(expectedDate) < new Date(todayISO()));
    const statusLabel = isDelayed ? 'Delayed' : 'intransit';
    const stage = isDelayed ? '1' : '2';

    fetch(procurementUrl('deliveries'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: new URLSearchParams({
        dr: d.dr,
        po: d.po,
        supplier: d.supplier,
        items: d.items || '',
        qty: String(deliveryQty),
        delDate: d.delDate || '',
        status: statusLabel,
        remarks: d.remarks || '',
        carrier: d.carrier || '',
        warehouse_id: d.warehouse_id || ''
      }).toString()
    }).then(res => res.json().then(json => ({ ok: res.ok, json }))).then(({ ok, json }) => {
      if(!ok){
        showToast(json?.message || 'Only approved purchase orders can be logged in deliveries.', 'info');
        return;
      }
      const shipmentNumber = json?.shipment_number || json?.data?.shipment_number || d.dr;
      const table = document.querySelector('#deliveries-table tbody');
      const poRow = findPoRowByNumber(d.po || '');
      if(table){
        const tr = document.createElement('tr');
        tr.dataset.status = isDelayed ? 'delayed' : 'intransit';
        tr.dataset.date = d.delDate;
        tr.dataset.ship = shipmentNumber;
        tr.dataset.po = d.po;
        tr.dataset.sup = d.supplier;
        tr.dataset.items = d.items || '';
        tr.dataset.stage = stage;
        tr.dataset.note = d.remarks || `${d.items} • Qty ${deliveryQty}`;
        tr.dataset.qty = String(deliveryQty);
        tr.dataset.carrier = d.carrier || 'Assigned carrier';
        tr.dataset.expected = expectedDate;
        tr.dataset.warehouse = (document.querySelector('#delivery-warehouse-select')?.selectedOptions?.[0]?.textContent || '').trim();
        // Only the first item is shown in the table; the rest live in the
        // tracking details modal (data-items keeps the full list).
        const delItemsList = String(d.items || '').split(',').map(s => s.trim()).filter(Boolean);
        const delItemsCell = `${htmlEscape(delItemsList[0] || 'â€”')}${delItemsList.length > 1 ? `<span class="item-more">+${delItemsList.length - 1} more</span>` : ''}`;
        tr.innerHTML = `
          <td><a class="po-link">${htmlEscape(shipmentNumber)}</a></td>
          <td><a class="po-link">${htmlEscape(d.po)}</a></td>
          <td>${supplierPill(d.supplier)}</td>
          <td title="${htmlEscape(d.items || '')}">${delItemsCell}</td>
          <td>${fmtDate(expectedDate)}</td>
          <td>${statusPill(statusLabel)}</td>
          <td>${fmtDate(d.delDate)}</td>
          <td><span class="row-actions"><button title="Track" onclick="openTrackModal(this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button></span></td>`;
        if(json && (json.id || (json.data && json.data.id))) tr.dataset.id = json.id || json.data.id;
        table.prepend(tr);
      }
      if(poRow){
        poRow.dataset.status = 'processing';
        poRow.children[5].innerHTML = statusPill('Processing');
      }
      if(!isDelayed){
        findDefectRowsByPO(d.po || '').forEach(r => updateDefectStatus(r, 'Intransit'));
      }
      const reqRow = findReqRowByRef(d.po);
      if(reqRow){
        // "In Transit" is the canonical label the server's status writer
        // accepts; "intransit" was normalised to "Intransit", rejected with a
        // 422, and — because nothing caught the rejection — the badge showed a
        // status the database never received.
        const prevCellHtml = reqRow.children[6] ? reqRow.children[6].innerHTML : '';
        const prevStatus = reqRow.dataset.status || '';
        updateRowStatus(reqRow, 'In Transit');
        persistRequisitionStatus(reqRow, 'In Transit').catch(err => {
          reqRow.dataset.status = prevStatus;
          if(reqRow.children[6]) reqRow.children[6].innerHTML = prevCellHtml;
          showToast(err?.message || 'Delivery saved, but the requisition status could not be updated.', 'no');
        });
      }
      const shipmentSequence = Number(String(shipmentNumber).match(/(\d+)$/)?.[1] || 0);
      ID_COUNTS.dr = Math.max(ID_COUNTS.dr, shipmentSequence);
      NEXT_ID.dr = ID_COUNTS.dr + 1;
      initRowActionButtons();
      showToast(`Delivery ${shipmentNumber} logged`, 'ok');
      closeAddModal('delivery');
      if(typeof pollLiveStats === 'function') pollLiveStats();

      // Logging from anywhere other than the Deliveries page (e.g. an approved
      // PO's View modal) lands the user on Deliveries so they can see the
      // shipment they just created. On the Deliveries page itself the row was
      // just prepended, so navigating would only throw that away.
      if(!document.getElementById('deliveries-table')){
        setTimeout(() => { window.location.href = procurementUrl('deliveries'); }, 600);
      }
    }).catch(() => {
      showToast('Unable to save delivery right now.', 'no');
    });
  }
