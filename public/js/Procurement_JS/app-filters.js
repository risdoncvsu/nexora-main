  /* ---------- Table search, sort, status tiles, and filter panels ----------
   * Every table interaction here is pure client-side DOM work (no reload).
   * A row's visibility is the AND of three independent filters, each tracked in
   * its own data flag so search / status-tile / panel filters never clobber one
   * another. */

  function isDataRow(row){
    // The empty-state row is a single full-width <td> — never filter/sort it.
    return row.querySelectorAll('td').length > 1;
  }

  function applyRowVisibility(row){
    const hidden = row.dataset.searchHidden === '1'
      || row.dataset.statusHidden === '1'
      || row.dataset.filterHidden === '1'
      || row.dataset.pageHidden === '1';
    row.style.display = hidden ? 'none' : '';
  }

  // A row the filters kept — i.e. eligible to appear on some page.
  function passesFilters(row){
    return row.dataset.searchHidden !== '1'
      && row.dataset.statusHidden !== '1'
      && row.dataset.filterHidden !== '1';
  }

  function dataRows(table){
    return [...table.querySelectorAll('tbody tr')].filter(isDataRow);
  }

  function fieldValue(id){
    const el = document.getElementById(id);
    return el ? String(el.value || '').trim() : '';
  }

  /* ---------- Pagination (8 rows per page, every table) ----------
   * Paginates only the rows that survived search / status / panel filters, so
   * paging and filtering compose instead of fighting. Pure DOM work: changing
   * page never reloads. */
  const PAGE_SIZE = 8;

  function tableFooterFor(table){
    const scope = table.closest('.panel') || table.parentElement;
    let footer = scope ? scope.querySelector('.table-footer') : null;
    if(!footer){
      footer = document.createElement('div');
      footer.className = 'table-footer';
      footer.innerHTML = '<div class="table-count"></div><div class="pager"></div>';
      table.insertAdjacentElement('afterend', footer);
    }
    if(!footer.querySelector('.pager')){
      const pager = document.createElement('div');
      pager.className = 'pager';
      footer.appendChild(pager);
    }
    return footer;
  }

  function renderCount(footer, from, to, total){
    // Existing footers start with a stray <br>, so pick the first non-pager
    // <div> rather than firstElementChild.
    const countEl = footer.querySelector('.table-count')
      || [...footer.children].find(el => el.tagName === 'DIV' && !el.classList.contains('pager'));
    if(!countEl) return;
    // Keep whatever noun the page already used ("purchase orders", etc.).
    if(!countEl.dataset.noun){
      const m = countEl.textContent.match(/Showing\s+\d+\s+(.+?)\s*$/i);
      countEl.dataset.noun = (m && m[1].trim()) || 'rows';
    }
    const noun = countEl.dataset.noun;
    countEl.innerHTML = total
      ? `Showing <b>${from}</b>–<b>${to}</b> of <b>${total}</b> ${noun}`
      : `No ${noun} to show`;
  }

  function renderPager(table, current, pages){
    const pager = tableFooterFor(table).querySelector('.pager');
    if(!pager) return;
    const btn = (label, page, opts = {}) => {
      const b = document.createElement('button');
      b.className = 'page-btn' + (opts.active ? ' active' : '');
      b.textContent = label;
      b.disabled = !!opts.disabled;
      if(!opts.disabled) b.addEventListener('click', () => paginateTable(table, page));
      return b;
    };
    pager.innerHTML = '';
    if(pages <= 1) return; // nothing to page through
    pager.appendChild(btn('‹', current - 1, { disabled: current === 1 }));
    for(let p = 1; p <= pages; p++) pager.appendChild(btn(String(p), p, { active: p === current }));
    pager.appendChild(btn('›', current + 1, { disabled: current === pages }));
  }

  function paginateTable(table, page){
    if(!table) return;
    const all = dataRows(table);
    const eligible = all.filter(passesFilters);
    const total = eligible.length;
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    let current = page == null ? Number(table.dataset.page || 1) : page;
    current = Math.min(Math.max(1, current || 1), pages);
    table.dataset.page = String(current);

    const start = (current - 1) * PAGE_SIZE;
    const visible = new Set(eligible.slice(start, start + PAGE_SIZE));
    all.forEach(row => {
      row.dataset.pageHidden = visible.has(row) ? '' : '1';
      applyRowVisibility(row);
    });

    renderCount(tableFooterFor(table), total ? start + 1 : 0, start + visible.size, total);
    renderPager(table, current, pages);
  }

  // Re-page a table by id, resetting to page 1 (used after filters change).
  function repaginate(table){ paginateTable(table, 1); }

  function initTablePagination(){
    document.querySelectorAll('table.data-table').forEach(table => {
      if(!table.querySelector('tbody') || table.__pagingBound) return;
      table.__pagingBound = true;
      paginateTable(table, 1);
      // Rows added/removed dynamically (new PO, logged delivery, delete, sort)
      // should re-page and re-count automatically — no reload, no caller
      // changes, nothing left showing a stale number.
      new MutationObserver(() => { paginateTable(table); updateStatusCounts(); }).observe(
        table.querySelector('tbody'), { childList: true }
      );
    });
  }

  /* ---------- Live search ----------
   * Two things this deliberately avoids:
   *  - row.innerText, which forces a layout pass per row per keystroke. On a
   *    few hundred rows that alone made typing visibly janky. textContent
   *    needs no layout.
   *  - re-reading the DOM on every keystroke: the searchable text is cached on
   *    the row and invalidated whenever the row's markup is rewritten. */
  function rowSearchText(row){
    if(row.dataset.searchBlob === undefined || row.dataset.searchDirty === '1'){
      row.dataset.searchBlob = (row.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
      row.dataset.searchDirty = '';
    }
    return row.dataset.searchBlob;
  }
  // Call after rewriting a row's cells so the cached text is rebuilt.
  function invalidateRowSearchText(row){ if(row) row.dataset.searchDirty = '1'; }

  let searchDebounce = null;
  function filterTable(tableId, query){
    const q = String(query || '').trim().toLowerCase();
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      const table = document.getElementById(tableId);
      if(!table) return;
      dataRows(table).forEach(row => {
        row.dataset.searchHidden = rowSearchText(row).includes(q) ? '' : '1';
      });
      repaginate(table);
    }, 120);
  }

  /* ---------- Status-chart tiles ----------
   * The tile numbers are rendered server-side, so before this they only
   * changed on a page reload: create a PO and the "Pending" tile still showed
   * the old count. Every table on these pages holds all of its rows (the 8-row
   * limit is pagination, not a query limit), so the counts can be recomputed
   * exactly from the DOM — no request, no staleness.
   *
   * Each chart declares which table it summarises via data-table. */
  function updateStatusCounts(){
    document.querySelectorAll('.status-chart[data-table]').forEach(chart => {
      const table = document.getElementById(chart.dataset.table);
      if(!table) return;

      const tally = new Map();
      dataRows(table).forEach(row => {
        const key = normalizeStatusKey(row.dataset.status);
        if(!key) return;
        tally.set(key, (tally.get(key) || 0) + 1);
      });

      chart.querySelectorAll('.status-chart-item[data-status]').forEach(tile => {
        const countEl = tile.querySelector('.status-count');
        if(!countEl) return;
        const next = tally.get(normalizeStatusKey(tile.dataset.status)) || 0;
        if(countEl.textContent.trim() !== String(next)) countEl.textContent = next;
      });
    });
  }

  /* ---------- Status-chart tile filter (click to toggle) ---------- */
  function filterByStatus(tableId, status, el){
    const table = document.getElementById(tableId);
    if(!table) return;
    const chart = el ? el.closest('.status-chart') : null;
    const alreadyActive = el && el.classList.contains('active');
    if(chart) chart.querySelectorAll('.status-chart-item').forEach(i => i.classList.remove('active'));
    // Compare on the collapsed key so "In Transit" matches an "intransit" tile.
    const active = alreadyActive ? null : normalizeStatusKey(status);
    if(el && active) el.classList.add('active');
    dataRows(table).forEach(row => {
      row.dataset.statusHidden = (!active || normalizeStatusKey(row.dataset.status) === active) ? '' : '1';
    });
    repaginate(table);
  }

  /* ---------- Sorting ---------- */
  function cellSortValue(row, idx){
    const cell = row.children[idx];
    return cell ? cell.innerText.trim() : '';
  }

  // The word test used to be applied to `a` only, which made the comparator
  // asymmetric: compare(a,b) could take the numeric branch while compare(b,a)
  // took the string branch. Array.prototype.sort with an inconsistent
  // comparator produces engine-defined ordering, not merely a slightly wrong
  // one. Both operands are now classified the same way.
  function looksNumeric(v){
    if(v === '') return false;
    if(!/\d/.test(v)) return false;
    if(/[a-z]{3,}/i.test(v)) return false;
    return !isNaN(Number(v.replace(/[^0-9.\-]/g, '')));
  }

  function compareValues(a, b){
    if(looksNumeric(a) && looksNumeric(b)){
      return Number(a.replace(/[^0-9.\-]/g, '')) - Number(b.replace(/[^0-9.\-]/g, ''));
    }
    const ad = Date.parse(a), bd = Date.parse(b);
    if(!isNaN(ad) && !isNaN(bd)) return ad - bd;
    return a.localeCompare(b, undefined, { numeric: true });
  }

  function sortByHeader(table, th){
    const headerRow = th.parentElement;
    const idx = [...headerRow.children].indexOf(th);
    const asc = !th.classList.contains('sort-asc');
    headerRow.querySelectorAll('th').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
    th.classList.add(asc ? 'sort-asc' : 'sort-desc');

    const tbody = table.querySelector('tbody');
    if(!tbody) return;
    const rows = dataRows(table);
    rows.sort((a, b) => {
      const cmp = compareValues(cellSortValue(a, idx), cellSortValue(b, idx));
      return asc ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
    repaginate(table);
  }

  function initSortableTables(){
    document.querySelectorAll('table.sortable-table').forEach(table => {
      table.querySelectorAll('th.sortable').forEach(th => {
        if(th.__sortBound) return;
        th.__sortBound = true;
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => sortByHeader(table, th));
      });
    });
  }

  /* ---------- Filter panels ---------- */
  function toggleFilterPanel(panelId, btn){
    const panel = document.getElementById(panelId);
    if(!panel) return;
    panel.classList.toggle('hidden');
    if(btn) btn.classList.toggle('active');
  }

  // Prefer an explicit data-date; otherwise read the given date column.
  function rowDateMs(row, dateIdx){
    const raw = row.dataset.date || (row.children[dateIdx] ? row.children[dateIdx].innerText.trim() : '');
    const ms = Date.parse(raw);
    return isNaN(ms) ? null : ms;
  }

  function withinRange(ms, from, to){
    if(ms === null) return !(from || to) ? true : false;
    if(from && ms < Date.parse(from)) return false;
    if(to && ms > Date.parse(to) + (86400000 - 1)) return false;
    return true;
  }

  function runPanelFilter(tableId, matchFn){
    const table = document.getElementById(tableId);
    if(!table) return;
    dataRows(table).forEach(row => {
      row.dataset.filterHidden = matchFn(row) ? '' : '1';
    });
    repaginate(table);
  }

  function clearPanelFilter(tableId, fieldIds){
    fieldIds.forEach(id => { const e = document.getElementById(id); if(e) e.value = ''; });
    const table = document.getElementById(tableId);
    if(!table) return;
    dataRows(table).forEach(row => { row.dataset.filterHidden = ''; });
    repaginate(table);
  }

  function amountInBucket(amount, bucket){
    if(!bucket) return true;
    if(bucket === '0-10000') return amount < 10000;
    if(bucket === '10000-50000') return amount >= 10000 && amount <= 50000;
    if(bucket === '50000+') return amount > 50000;
    return true;
  }

  // Purchase Orders (date column index 6 after the Unit Price column removal)
  function applyPOFilter(){
    const status = fieldValue('po-filter-status').toLowerCase();
    const from = fieldValue('po-filter-date-from');
    const to = fieldValue('po-filter-date-to');
    const amount = fieldValue('po-filter-amount');
    runPanelFilter('po-table', row => {
      if(status && String(row.dataset.status || '').toLowerCase() !== status) return false;
      if((from || to) && !withinRange(rowDateMs(row, 6), from, to)) return false;
      if(!amountInBucket(Number(row.dataset.amount || 0), amount)) return false;
      return true;
    });
  }
  function clearPOFilter(){
    clearPanelFilter('po-table', ['po-filter-status', 'po-filter-date-from', 'po-filter-date-to', 'po-filter-amount']);
  }

  // Deliveries (supplier in data-sup, date column index 6)
  function applyDelFilter(){
    const status = fieldValue('delivery-filter-status').toLowerCase();
    const from = fieldValue('delivery-filter-date-from');
    const to = fieldValue('delivery-filter-date-to');
    const supplier = fieldValue('delivery-filter-supplier').toLowerCase();
    runPanelFilter('deliveries-table', row => {
      if(status && String(row.dataset.status || '').toLowerCase() !== status) return false;
      if(supplier && String(row.dataset.sup || '').toLowerCase() !== supplier) return false;
      if((from || to) && !withinRange(rowDateMs(row, 6), from, to)) return false;
      return true;
    });
  }
  function clearDelFilter(){
    clearPanelFilter('deliveries-table', ['delivery-filter-status', 'delivery-filter-date-from', 'delivery-filter-date-to', 'delivery-filter-supplier']);
  }

  // Requisitions (priority pill in column 3, date in data-date / column 7)
  function applyReqFilter(){
    const status = fieldValue('req-filter-status').toLowerCase();
    const from = fieldValue('req-filter-date-from');
    const to = fieldValue('req-filter-date-to');
    const priority = fieldValue('req-filter-priority').toLowerCase();
    runPanelFilter('requisitions-table', row => {
      if(status && String(row.dataset.status || '').toLowerCase() !== status) return false;
      if(priority){
        const rowPriority = (row.children[3] ? row.children[3].innerText : '').toLowerCase();
        if(!rowPriority.includes(priority)) return false;
      }
      if((from || to) && !withinRange(rowDateMs(row, 7), from, to)) return false;
      return true;
    });
  }
  function clearReqFilter(){
    clearPanelFilter('requisitions-table', ['req-filter-status', 'req-filter-date-from', 'req-filter-date-to', 'req-filter-priority']);
  }

  /* ---------- Suppliers (cards, not a table) ----------
   * The Suppliers page renders one card per supplier, so search and filtering
   * work on cards. Visibility is still the AND of the two independent flags,
   * same as the tables, so search and the filter panel never clobber each
   * other. There is no Brand filter any more: the supplier-level Brand field
   * was removed, and category now belongs to each product. */
  function supplierCardEls(){
    return [...document.querySelectorAll('#suppliers-cards .supplier-card')];
  }

  function applySupplierCardVisibility(card){
    const hidden = card.dataset.searchHidden === '1'
      || card.dataset.filterHidden === '1'
      || card.dataset.pageHidden === '1';
    card.style.display = hidden ? 'none' : '';
  }

  // A card the filters kept — eligible to appear on some page.
  function supplierCardPassesFilters(card){
    return card.dataset.searchHidden !== '1' && card.dataset.filterHidden !== '1';
  }

  /* ---------- Supplier pagination (6 cards per page) ----------
   * Mirrors the table pager: only the cards that survived search and the
   * filter panel are paged, so paging and filtering compose instead of
   * fighting. Pure DOM work — changing page never reloads. */
  const SUPPLIER_PAGE_SIZE = 6;

  function paginateSupplierCards(page){
    const wrap = document.getElementById('suppliers-cards');
    if(!wrap) return;
    const all = supplierCardEls();
    const eligible = all.filter(supplierCardPassesFilters);
    const total = eligible.length;
    const pages = Math.max(1, Math.ceil(total / SUPPLIER_PAGE_SIZE));

    let current = page == null ? Number(wrap.dataset.page || 1) : page;
    current = Math.min(Math.max(1, current || 1), pages);
    wrap.dataset.page = String(current);

    const start = (current - 1) * SUPPLIER_PAGE_SIZE;
    const visible = new Set(eligible.slice(start, start + SUPPLIER_PAGE_SIZE));
    all.forEach(card => {
      card.dataset.pageHidden = visible.has(card) ? '' : '1';
      applySupplierCardVisibility(card);
    });

    const countEl = document.querySelector('#page-suppliers .table-count');
    if(countEl){
      countEl.innerHTML = total
        ? `Showing <b>${start + 1}</b>–<b>${start + visible.size}</b> of <b>${total}</b> ${total === 1 ? 'supplier' : 'suppliers'}`
        : 'No suppliers to show';
    }

    renderSupplierPager(current, pages);

    const empty = document.querySelector('#suppliers-cards .sc-empty-state');
    if(empty) empty.style.display = all.length ? 'none' : '';
  }

  function renderSupplierPager(current, pages){
    const pager = document.querySelector('#page-suppliers .pager');
    if(!pager) return;
    pager.innerHTML = '';
    if(pages <= 1) return; // nothing to page through
    const btn = (label, page, opts = {}) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'page-btn' + (opts.active ? ' active' : '');
      b.textContent = label;
      b.disabled = !!opts.disabled;
      if(!opts.disabled) b.addEventListener('click', () => paginateSupplierCards(page));
      return b;
    };
    pager.appendChild(btn('‹', current - 1, { disabled: current === 1 }));
    for(let p = 1; p <= pages; p++) pager.appendChild(btn(String(p), p, { active: p === current }));
    pager.appendChild(btn('›', current + 1, { disabled: current === pages }));
  }

  // Cached, layout-free searchable text for a card: its own fields plus every
  // product name/SKU/category, so searching for a product finds its supplier.
  function supplierCardSearchText(card){
    if(card.dataset.searchBlob === undefined || card.dataset.searchDirty === '1'){
      let products = [];
      try { products = JSON.parse(card.dataset.products || '[]'); } catch { products = []; }
      const productText = (Array.isArray(products) ? products : [])
        .map(p => [p.name, p.sku, p.category].filter(Boolean).join(' '))
        .join(' ');
      card.dataset.searchBlob = [
        card.dataset.name, card.dataset.contact, card.dataset.email,
        card.dataset.phone, card.dataset.address, card.dataset.category, productText
      ].filter(Boolean).join(' ').replace(/\s+/g, ' ').toLowerCase();
      card.dataset.searchDirty = '';
    }
    return card.dataset.searchBlob;
  }

  let supplierSearchDebounce = null;
  function filterSupplierCards(query){
    const q = String(query || '').trim().toLowerCase();
    clearTimeout(supplierSearchDebounce);
    supplierSearchDebounce = setTimeout(() => {
      supplierCardEls().forEach(card => {
        card.dataset.searchHidden = supplierCardSearchText(card).includes(q) ? '' : '1';
      });
      paginateSupplierCards(1);
    }, 120);
  }

  function supplierCardCategories(card){
    let products = [];
    try { products = JSON.parse(card.dataset.products || '[]'); } catch { products = []; }
    return (Array.isArray(products) ? products : [])
      .map(p => String(p.category || '').trim().toLowerCase())
      .filter(Boolean);
  }

  function applySupplierFilter(){
    const category = fieldValue('supplier-filter-category').toLowerCase();
    const status = fieldValue('supplier-filter-status').toLowerCase();
    supplierCardEls().forEach(card => {
      let keep = true;
      if(status && String(card.dataset.status || '').toLowerCase() !== status) keep = false;
      if(keep && category && !supplierCardCategories(card).includes(category)) keep = false;
      card.dataset.filterHidden = keep ? '' : '1';
    });
    paginateSupplierCards(1);
  }

  function clearSupplierFilter(){
    ['supplier-filter-category', 'supplier-filter-status'].forEach(id => {
      const el = document.getElementById(id);
      if(el) el.value = '';
    });
    supplierCardEls().forEach(card => { card.dataset.filterHidden = ''; });
    paginateSupplierCards(1);
  }

  // Build the category dropdown from the categories that actually exist on the
  // page's products, rather than a hardcoded sample list that matched nothing.
  function refreshSupplierCategoryOptions(){
    const select = document.getElementById('supplier-filter-category');
    if(!select) return;
    const selected = select.value || '';
    const seen = new Map();
    supplierCardEls().forEach(card => {
      let products = [];
      try { products = JSON.parse(card.dataset.products || '[]'); } catch { products = []; }
      (Array.isArray(products) ? products : []).forEach(p => {
        const label = String(p.category || '').trim();
        if(label) seen.set(label.toLowerCase(), label);
      });
    });
    const entries = [...seen.entries()].sort((a, b) => a[1].localeCompare(b[1]));
    select.innerHTML = '<option value="">All Categories</option>'
      + entries.map(([value, label]) => `<option value="${htmlEscape(value)}">${htmlEscape(label)}</option>`).join('');
    select.value = seen.has(selected) ? selected : '';
  }

  /* ---------- Date-range presets (Today / This Week / This Month) ---------- */
  function setDatePreset(prefix, preset, el){
    const fromEl = document.getElementById(prefix + '-filter-date-from');
    const toEl = document.getElementById(prefix + '-filter-date-to');
    if(!fromEl || !toEl) return;
    const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const now = new Date();
    let start = new Date(now), end = new Date(now);
    if(preset === 'week'){
      const mondayOffset = (now.getDay() + 6) % 7; // week starts Monday
      start = new Date(now); start.setDate(now.getDate() - mondayOffset);
      end = new Date(start); end.setDate(start.getDate() + 6);
    } else if(preset === 'month'){
      start = new Date(now.getFullYear(), now.getMonth(), 1);
      end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }
    fromEl.value = iso(start);
    toEl.value = iso(end);
    if(el){
      const group = el.parentElement;
      if(group) group.querySelectorAll('.date-preset').forEach(b => b.classList.remove('active'));
      el.classList.add('active');
    }
    ({ po: applyPOFilter, delivery: applyDelFilter, req: applyReqFilter })[prefix]?.();
  }

  // Wire sorting + 8-row pagination as soon as this script runs (it loads at
  // the end of <body>, so the tables are already in the DOM).
  initSortableTables();
  initTablePagination();
  refreshSupplierCategoryOptions();
  initSupplierPagination();
  updateStatusCounts();

  // Cards added (new supplier) or removed (delete) should re-page themselves,
  // the same way the tables do.
  function initSupplierPagination(){
    const wrap = document.getElementById('suppliers-cards');
    if(!wrap || wrap.__pagingBound) return;
    wrap.__pagingBound = true;
    paginateSupplierCards(1);
    new MutationObserver(() => paginateSupplierCards()).observe(wrap, { childList: true });
  }
