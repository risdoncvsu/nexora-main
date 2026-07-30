@extends('procurement::layouts.app')

@section('title', 'Nexora ERP — Suppliers')

@section('content')
<section id="page-suppliers">
      <div class="page-head">
        <h1>Suppliers</h1>
        <p>{{ isset($suppliers) ? count($suppliers) : 0 }} registered {{ (isset($suppliers) && count($suppliers) === 1) ? 'supplier' : 'suppliers' }}</p>
      </div>

      <div class="panel">
        <div class="table-toolbar">
          <h2>Supplier Directory</h2>
          <div class="search-box">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <input placeholder="Search suppliers, contacts, or products..." oninput="filterSupplierCards(this.value)">
          </div>
           <button class="toolbar-btn" onclick="toggleFilterPanel('supplier-filter-panel', this)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M3 5h18l-7 8v6l-4 2v-8L3 5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            Filter
          </button>
          <button class="toolbar-btn primary" onclick="openAddModal('supplier')">+ Add Supplier</button>
        </div>
        {{-- The Brand filter was removed with the supplier-level Brand field.
             Category now lives on each product, so the panel filters by status
             and by product category instead. --}}
        <div class="filter-panel hidden" id="supplier-filter-panel">
          <div class="filter-group">
            <label>Product category</label>
            <select id="supplier-filter-category" onchange="applySupplierFilter()">
              <option value="">All Categories</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select id="supplier-filter-status" onchange="applySupplierFilter()">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="blacklisted">Blacklisted</option>
            </select>
          </div>
          <div class="filter-actions">
            <button class="btn-text" onclick="clearSupplierFilter()">Clear</button>
            <button class="btn-primary" onclick="applySupplierFilter()">Apply</button>
          </div>
        </div>

        <div class="supplier-cards" id="suppliers-cards">
          @forelse($suppliers ?? [] as $s)
            @php
              $parts = preg_split('/\s+/', trim((string) $s->name)) ?: [];
              $initials = '';
              if($parts){
                $initials = strtoupper(substr($parts[0], 0, 1));
                if(count($parts) > 1) $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
              }
              $colors = ['#2f6fed','#22c55e','#f2994a','#7a5af0','#eb5757','#0ea5e9','#1fa971','#e0338c'];
              $h = 0;
              foreach(str_split((string) ($s->name ?? '')) as $ch) $h = ($h * 31 + ord($ch)) & 0xffff;
              $badgeColor = $colors[$h % count($colors)];

              $products = $s->product_items ? (json_decode($s->product_items, true) ?: []) : [];
              if(! is_array($products)) $products = [];

              // Supplier-level Brand is gone; the card subtitle is the most
              // common category among this supplier's own products.
              $categories = array_values(array_filter(array_map(
                fn($p) => trim((string) ($p['category'] ?? '')),
                $products
              )));
              $subtitle = '';
              if($categories){
                $tally = array_count_values($categories);
                arsort($tally);
                $subtitle = (string) array_key_first($tally);
              }

              $statusRaw = trim((string) ($s->status ?? 'active'));
              $statusKey = strtolower($statusRaw) ?: 'active';
              $poCount = (int) ($poCounts[$s->id] ?? 0);
            @endphp
            <article class="supplier-card"
                     data-id="{{ $s->id }}"
                     data-sid="{{ $s->sid ?? '' }}"
                     data-name="{{ $s->name }}"
                     data-contact="{{ $s->contact_person ?? '' }}"
                     data-email="{{ $s->email ?? '' }}"
                     data-phone="{{ $s->phone ?? '' }}"
                     data-address="{{ $s->address ?? '' }}"
                     data-status="{{ $statusKey }}"
                     data-status-label="{{ $statusRaw ?: 'Active' }}"
                     data-category="{{ $subtitle }}"
                     data-warehouse-id="{{ $s->warehouse_id ?? '' }}"
                     data-po-count="{{ $poCount }}"
                     data-products='@json($products)'>
              <header class="sc-head">
                <span class="sc-avatar" style="background: {{ $badgeColor }}">{{ $initials ?: 'NA' }}</span>
                <div class="sc-ident">
                  <h3>{{ $s->name }}</h3>
                  <p>{{ $subtitle !== '' ? $subtitle : 'No category yet' }}</p>
                </div>
                <span class="sc-status {{ $statusKey }}"><i></i>{{ ucfirst($statusRaw ?: 'Active') }}</span>
              </header>

              <ul class="sc-contact">
                <li>
                  <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M4.5 20c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  <span>{{ $s->contact_person ?: '—' }}</span>
                </li>
                <li>
                  <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3.5 7l8.5 6 8.5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  <span>{{ $s->email ?: '—' }}</span>
                </li>
                <li>
                  <svg viewBox="0 0 24 24" fill="none"><path d="M5 4h3.5l1.8 4.4-2.2 1.6a12 12 0 0 0 5.9 5.9l1.6-2.2L20 15.5V19a1 1 0 0 1-1.1 1A15.9 15.9 0 0 1 4 5.1 1 1 0 0 1 5 4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                  <span>{{ $s->phone ?: '—' }}</span>
                </li>
                <li>
                  <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                  <span>{{ $s->address ?: '—' }}</span>
                </li>
              </ul>

              <div class="sc-products">
                <h4>
                  <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7V5.5A1.5 1.5 0 0 1 9.5 4h5A1.5 1.5 0 0 1 16 5.5V7" stroke="currentColor" stroke-width="2"/></svg>
                  Products ({{ count($products) }})
                </h4>
                {{-- Only the first product is visible; the rest are hidden
                     behind the "+N more" toggle so a long catalog doesn't turn
                     the card into a scrolling column. --}}
                @if(count($products))
                  <ul class="sc-product-list">
                    @foreach($products as $i => $p)
                      <li @class(['sc-product-extra' => $i > 0])>
                        <div class="sc-product-info">
                          <span class="sc-product-name">{{ $p['name'] ?? 'Product' }}</span>
                          <span class="sc-product-meta">
                            {{ $p['sku'] ?? 'No SKU' }}@if(!empty($p['category'])) · {{ $p['category'] }}@endif
                          </span>
                        </div>
                        <span class="sc-product-price">&#8369;{{ number_format((float) ($p['price'] ?? 0), 2) }}</span>
                      </li>
                    @endforeach
                  </ul>
                  @if(count($products) > 1)
                    <button type="button" class="sc-product-more"
                            data-hidden="{{ count($products) - 1 }}"
                            aria-expanded="false"
                            onclick="toggleSupplierProducts(this, event)">+{{ count($products) - 1 }} more</button>
                  @endif
                @else
                  <div class="sc-product-empty">No products added yet.</div>
                @endif
              </div>

              <footer class="sc-foot">
                <span class="sc-po-count">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M7 3h7l4 4v14H7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                  {{ $poCount }} {{ $poCount === 1 ? 'PO' : 'POs' }} placed
                </span>
                <button type="button" class="sc-add-product" onclick="openSupplierProductModal(this)">
                  <span aria-hidden="true">+</span> Product
                </button>
              </footer>
            </article>
          @empty
            <div class="sc-empty-state">No suppliers yet. Use <b>+ Add Supplier</b> to register one.</div>
          @endforelse
        </div>

        <div class="table-footer">
          <div class="table-count">Showing <b>{{ isset($suppliers) ? count($suppliers) : 0 }}</b> suppliers</div>
          <div class="pager"></div>
        </div>
      </div>
    </section>
@endsection
