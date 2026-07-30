<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<script>
  (function () {
    try {
      if (localStorage.getItem('nexora-theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
      }
    } catch (e) {}
  })();
</script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Nexora Returns</title>
<style>
   :root {
    --bg-header: #FFFFFF;
    --bg-dark: #EEF2FA;
    --bg-card: #FFFFFF;
    --text-light: #16233F;
    --text-muted: #5B6B85;
    --border-soft: rgba(15,23,42,0.10);
    --row-alt: rgba(15,23,42,0.025);
    --row-hover: rgba(15,23,42,0.045);
    --accent: #3B82F6;
    --pill: #EAF0FB;
    --pill-border: #C9D8F2;

    /* Header/profile menu stay fixed dark-navy in both light and dark mode */
    --bg-header-fixed: #0B1E3D;
    --header-text: #FFFFFF;
    --header-muted: #9FB3D1;
    --header-border: rgba(255,255,255,0.08);

    /* PACKING / READY FOR DELIVERY status color, kept in sync with the
       Shipping tab's palette so the same status looks the same everywhere. */
    --warn-bg: #FFF6E5;
    --warn-border: #F3D08A;
    --warn-text: #8A5A06;

    /* Cards/panels/modals need their own soft shadow in light mode for
       depth against the light page background. */
    --elev-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 10px 28px rgba(15,23,42,0.07);
    --modal-shadow: 0 20px 60px rgba(15,23,42,0.18);
  }

  html.dark-theme {
    --bg-header: #0B1E3D;
    --bg-dark: #1B3A6B;
    --bg-card: #0B1E3D;
    --text-light: #FFFFFF;
    --text-muted: #9FB3D1;
    --border-soft: rgba(255,255,255,0.08);
    --row-alt: rgba(255,255,255,0.02);
    --row-hover: rgba(255,255,255,0.04);
    --accent: #3B82F6;
    --pill: #16305c;
    --pill-border: #2c4373;

    --warn-bg: #6B4A1E;
    --warn-border: #6b5a24;
    --warn-text: #FBD38D;

    --elev-shadow: none;
    --modal-shadow: 0 20px 60px rgba(0,0,0,0.4);
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--bg-dark);
    color: var(--text-light);
  }

  /* ===== Navbar ===== */
  .navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 40px;
    background: var(--bg-header-fixed);
    border-bottom: 1px solid var(--header-border);
  }

.brand{
    display:flex;
    align-items:center;
    gap:14px;
}

.brand-logo{
    display:flex;
    align-items:center;
    gap:14px;
    text-decoration:none;
    color:inherit;
}

.brand-logo .title{
    color: var(--header-text);
}

.brand-logo .subtitle{
    color:#3B82F6;
}

  .logo {
    height: 64px;
    width: auto;
    max-width: 280px;
    object-fit: contain;
    margin-left: -14px;
  }

  .brand-text .title { font-size: 20px; font-weight: 700; letter-spacing: 1px; }
  .brand-text .subtitle { font-size: 11px; color: #3B82F6; letter-spacing: 1px; }

  .nav-links { display: flex; gap: 36px; }
  .nav-links a { color: var(--header-muted); text-decoration: none; font-size: 15px; font-weight: 500; }
  .nav-links a.active { color: var(--header-text); font-weight: 700; }

  .nav-links a:hover {
    color: var(--header-text);
    text-shadow: 0 0 0.4px currentColor, 0 0 0.4px currentColor;
  }
  
  /* ---------- Stats ---------- */
  .stats-row {
    display: flex;
    gap: 24px;
    padding: 32px 40px 10px;
    flex-wrap: wrap;
  }

  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-soft);
    border-radius: 12px;
    padding: 22px 28px;
    flex: 1;
    min-width: 200px;
    box-shadow: var(--elev-shadow);
  }

  .stat-card .label { color: var(--text-muted); font-size: 14px; font-weight: 600; margin-bottom: 10px; }
  .stat-card .value { font-size: 32px; font-weight: 700; }

  /* ---------- Main content ---------- */
  .content {
    display: flex;
    gap: 24px;
    padding: 28px 40px 60px 40px;
  }

  .panel {
    background: var(--bg-card);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--elev-shadow);
  }

  .returns-queue {
    flex: 2.5;
    display: flex;
    flex-direction: column;
    height: 560px;
  }
  .activity {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 560px;
  }

  /* Scrollable body under the fixed panel header */
  .table-scroll {
    flex: 1;
    overflow-y: auto;
  }

  .table-scroll::-webkit-scrollbar {
    width: 8px;
  }
  .table-scroll::-webkit-scrollbar-track {
    background: transparent;
  }
  .table-scroll::-webkit-scrollbar-thumb {
    background: var(--pill-border);
    border-radius: 8px;
  }
  .table-scroll::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
  }

  /* Keep column headers pinned while rows scroll */
  .returns-queue thead th {
    position: sticky;
    top: 0;
    background: var(--bg-card);
    z-index: 5;
  }

  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    position: relative;
    gap: 16px;
  }

  .panel-header .title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 16px;
    white-space: nowrap;
  }

  .panel-header .actions {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 14px;
  }

  /* ===== Search + Filter (working controls) ===== */
  .search-wrap {
    position: relative;
  }

  .search-wrap input {
    width: 170px;
    background: var(--pill);
    border: 1px solid var(--pill-border);
    border-radius: 20px;
    padding: 8px 14px 8px 32px;
    color: var(--text-light);
    font-size: 13px;
    outline: none;
    transition: width 0.15s ease, border-color 0.15s ease;
  }

  .search-wrap input:focus {
    width: 210px;
    border-color: var(--accent);
  }

  .search-wrap input::placeholder {
    color: var(--text-muted);
  }

  .search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    font-size: 12px;
  }

  .filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--pill);
    border: 1px solid var(--pill-border);
    border-radius: 20px;
    padding: 8px 14px;
    color: var(--text-light);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    position: relative;
  }

  .filter-btn:hover,
  .filter-btn.active {
    border-color: var(--accent);
  }

  .filter-btn .caret {
    font-size: 10px;
    color: var(--text-muted);
    transition: transform 0.15s ease;
  }

  .filter-btn.open .caret {
    transform: rotate(180deg);
  }

  .filter-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ff2f92;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
    line-height: 1.4;
    display: none;
  }

  .filter-panel {
    position: absolute;
    right: 24px;
    top: 56px;
    background: var(--bg-header);
    border: 1px solid var(--pill-border);
    border-radius: 12px;
    padding: 14px 16px;
    width: 200px;
    box-shadow: var(--modal-shadow);
    display: none;
    z-index: 30;
  }

  .filter-panel.show {
    display: block;
  }

  .filter-panel .filter-title {
    color: var(--text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
  }

  .filter-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    cursor: pointer;
    color: var(--text-light);
    font-size: 14px;
    font-weight: 600;
    user-select: none;
  }

  .filter-option input {
    width: 16px;
    height: 16px;
    accent-color: var(--accent);
    cursor: pointer;
  }

  .filter-overlay {
    position: fixed;
    inset: 0;
    z-index: 20;
    display: none;
  }

  .filter-overlay.show {
    display: block;
  }

  .no-results-row td {
    text-align: center;
    padding: 30px;
    color: var(--text-muted);
    font-size: 14px;
  }
  /* ===== end search + filter ===== */

  table { width: 100%; border-collapse: collapse; }

  thead th {
    text-align: left;
    padding: 14px 24px;
    font-size: 13px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-soft);
  }

  tbody td { padding: 14px 24px; font-size: 14px; border-bottom: 1px solid var(--border-soft); }
  tbody tr.return-row { cursor: pointer; }
  tbody tr.return-row:hover { background: var(--row-hover); }

  .order-id, .product { color: var(--text-muted); }
  .customer { font-weight: 600; }

  .status-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
    white-space: nowrap;
  }

  /* Same color language as order.blade.php's .badge.status.status-* —
     pending reads like a fresh/awaiting-review order (gray pill), in
     transit reads like an order that's out for delivery (blue),
     inspecting reads like an order being worked on (amber/warn, same
     as PACKING), refunded gets its own distinct teal (matches Orders/
     Dashboard's REFUNDED), completed reads like a finished order
     (green, same as DELIVERED/COMPLETE), and declined reads like a
     cancelled one (red). */
  .status-badge.status-pending    { background: var(--pill); color: var(--text-muted); }
  .status-badge.status-transit    { background: #1E3A6B; color: #93C5FD; }
  .status-badge.status-inspecting { background: var(--warn-bg); color: var(--warn-text); border: 1px solid var(--warn-border); }
  .status-badge.status-refunded   { background: #134E4A; color: #5EEAD4; }
  .status-badge.status-completed  { background: #1E5A3A; color: #86EFAC; }
  .status-badge.status-declined   { background: #4A1E1E; color: #F3A9A9; }

  .resolution-not-resellable { color: #f28b82; font-weight: 600; }

  .empty-row td { height: 38px; }

 
  table {
    width: 100%;
    border-collapse: collapse;
  }
 
  thead th {
    text-align: left;
    padding: 14px 24px;
    font-size: 14px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-soft);
  }
 
  tbody td {
    padding: 14px 24px;
    font-size: 14px;
    border-bottom: 1px solid var(--border-soft);
  }
 
  tbody tr:nth-child(even) {
    background: var(--row-alt);
  }
 
  .order-id, .product {
    color: var(--text-muted);
  }
 
  .customer {
    font-weight: 600;
  }
 
  .priority-low {
    background: #5A3A4A;
    color: #E8B8C8;
    padding: 3px 12px;
    border-radius: 5px;
    font-size: 11px;
    display: inline-block;
  }
 
  .priority-med {
    background: #6B4A1E;
    color: #FBD38D;
    padding: 3px 12px;
    border-radius: 5px;
    font-size: 11px;
    display: inline-block;
  }
 
  .priority-high {
    background: #7F1D2E;
    color: #FCA5B1;
    padding: 3px 12px;
    border-radius: 5px;
    font-size: 11px;
    display: inline-block;
  }
 
  .btn-prepare {
    display: inline-block;
    background: var(--bg-dark);
    color: var(--text-light);
    font-weight: 700;
    font-size: 13px;
    padding: 6px 14px;
    border-radius: 20px;
    text-align: center;
    border: none;
    cursor: pointer;
  }
 
  .btn-prepare:hover {
    background: #244a80;
  }
 
  .empty-row td {
    height: 38px;
  }
 
  .activity-list {
    padding: 8px 0;
  }
 
  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
  }
 
  .activity-item:last-child {
    border-bottom: none;
  }
 
  .activity-icon {
    width: 18px;
    text-align: center;
    flex-shrink: 0;
    margin-top: 2px;
  }

  /* Refund activity list */
  .refund-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
  }

  .refund-list::-webkit-scrollbar {
    width: 8px;
  }
  .refund-list::-webkit-scrollbar-track {
    background: transparent;
  }
  .refund-list::-webkit-scrollbar-thumb {
    background: var(--pill-border);
    border-radius: 8px;
  }
  .refund-list::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
  }

  .refund-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
  }

  .refund-item:last-child { border-bottom: none; }
  .refund-icon { width: 18px; text-align: center; flex-shrink: 0; color: #4ade80; }

  /* ============================================
     Blur + modal mechanism
     ============================================ */
  #pageContent {
    transition: filter 0.25s ease;
  }

  #pageContent.blurred {
    filter: blur(4px);
  }

  .overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5, 12, 28, 0.45);
    align-items: center;
    justify-content: center;
    z-index: 100;
  }

  .overlay.active { display: flex; }

  .modal {
    width: 620px;
    max-width: 90vw;
    background: var(--bg-card);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--modal-shadow);
    border: 1px solid var(--border-soft);
  }

  .modal-header { background: var(--bg-dark); padding: 20px 28px; }
  .modal-header h2 { margin: 0; color: var(--text-light); font-size: 18px; }
  .modal-header h2 span { color: var(--text-muted); font-weight: 400; }
  .modal-header p { margin: 4px 0 0; color: var(--text-muted); font-size: 13px; }

  .modal-tags {
    display: flex;
    gap: 10px;
    padding: 18px 28px 0;
  }

  .modal-body { padding: 20px 28px 0; }
  .modal-body .field-label { margin: 0 0 6px; font-size: 12px; color: var(--text-muted); }
  .modal-body .field-label span { color: var(--text-muted); font-weight: 400; }
  .modal-body .reason-title { margin: 0 0 10px; font-size: 16px; font-weight: 700; color: var(--text-light); }
  .modal-body .reason-desc { margin: 0 0 20px; font-size: 14px; color: var(--text-muted); line-height: 1.5; }

  .items-list {
    list-style: none;
    margin: 0 0 20px;
    padding: 0;
    max-height: 160px;
    overflow-y: auto;
    border: 1px solid var(--border-soft);
    border-radius: 8px;
  }

  .items-list li {
    padding: 10px 14px;
    font-size: 14px;
    color: var(--text-light);
    border-bottom: 1px solid var(--border-soft);
  }

  .items-list li:last-child { border-bottom: none; }

  .items-list::-webkit-scrollbar { width: 8px; }
  .items-list::-webkit-scrollbar-track { background: transparent; }
  .items-list::-webkit-scrollbar-thumb { background: var(--pill-border); border-radius: 8px; }
  .items-list::-webkit-scrollbar-thumb:hover { background: var(--accent); }

  .proof-row { display: flex; gap: 12px; margin-bottom: 20px; }

  .proof-thumb {
    width: 80px;
    height: 70px;
    background: #1c3766;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #6f89c2;
  }

  .meta-row {
    display: flex;
    gap: 40px;
    padding: 18px 0;
    border-top: 1px solid var(--border-soft);
    margin-top: 4px;
  }

  .meta-row .field-value { margin: 0; font-size: 15px; color: var(--text-light); font-weight: 700; }

  .modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 28px;
    border-top: 1px solid var(--border-soft);
  }

  .btn {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
  }

  .btn-close { background: var(--pill); color: var(--text-light); border: 1px solid var(--pill-border); }
  .btn-close:hover { background: #1c3766; }

  .btn-accept { background: #16a34a; color: #eafff0; }
  .btn-accept:hover { background: #1bbf58; }

  .btn-reject { background: #dc2626; color: #fff0f0; }
  .btn-reject:hover { background: #e5433a; }

  .btn:disabled,
  .btn:disabled:hover { opacity: 0.5; cursor: not-allowed; }

  /* ===== Nav actions (links + profile grouped on the right) ===== */
  .nav-actions {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .nav-divider {
    width: 1px;
    height: 22px;
    background: var(--header-border);
  }

  /* ===== Profile menu ===== */
  .profile-menu {
    position: relative;
  }

  .profile-trigger {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid var(--header-border);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--accent, #3B82F6);
    padding: 0;
  }

  .avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 16px;
    font-family: inherit;
    line-height: 1;
  }

  .avatar-initial-lg {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 50%;
    font-size: 18px;
  }

  .profile-trigger:hover {
    border-color: var(--accent, #3B82F6);
  }

  .profile-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    background: var(--bg-header-fixed);
    border: 1px solid var(--header-border);
    border-radius: 12px;
    min-width: 250px;
    padding: 14px;
    display: none;
    flex-direction: column;
    box-shadow: 0 12px 28px rgba(0,0,0,0.35);
    z-index: 100;
  }

  .profile-dropdown.open {
    display: flex;
  }

  .profile-summary {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 2px 2px 12px;
  }

  .profile-summary-text {
    min-width: 0;
  }

  .profile-name {
    color: var(--header-text);
    font-size: 15px;
    font-weight: 700;
  }

  .profile-email {
    color: var(--header-muted);
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .profile-role-badge {
    display: inline-block;
    align-self: flex-start;
    background: var(--pill, rgba(59,130,246,0.18));
    border: 1px solid var(--pill-border, rgba(59,130,246,0.35));
    color: #3B82F6;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.03em;
    padding: 3px 10px;
    border-radius: 12px;
    margin: 0 0 12px;
  }

  .profile-dropdown .divider {
    height: 1px;
    background: var(--header-border);
    margin: 4px 0 10px;
  }

  .profile-dropdown-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 2px 12px;
  }

  .profile-dropdown-row .dark-mode-label {
    color: var(--header-text);
    font-size: 14px;
    font-weight: 500;
  }

  .theme-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
    flex-shrink: 0;
  }

  .theme-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .theme-switch-slider {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.18);
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.15s ease;
  }

  .theme-switch-slider::before {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    left: 3px;
    top: 3px;
    background: #FFFFFF;
    border-radius: 50%;
    transition: transform 0.15s ease;
  }

  .theme-switch input:checked + .theme-switch-slider {
    background: #3B82F6;
  }

  .theme-switch input:checked + .theme-switch-slider::before {
    transform: translateX(18px);
  }

  .profile-dropdown .logout-btn {
    display: block;
    width: 100%;
    text-align: center;
    background: none;
    border: none;
    color: #F87171;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
  }

  .profile-dropdown .logout-btn:hover {
    background: rgba(248,113,113,0.12);
  }
</style>
</head>
<body>
<div class="top-strip"></div>

  <!-- ============================================
       Everything the user should see BLURRED while
       the modal is open goes inside #pageContent.
       ============================================ -->
  <div id="pageContent">

    <!-- Navbar -->
    <div class="navbar">
      <div class="brand brand-logo">
        <a href="{{ route('order-fulfillment.dashboard') }}" aria-label="Order Fulfillment dashboard"><img class="logo" src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora Logo"></a>
        <x-client-logo :size="64" />
      </div>
      <div class="nav-actions">
        <div class="nav-links">
          <a href="{{ route('order-fulfillment.dashboard') }}">Dashboard</a>
          <a href="{{ route('order-fulfillment.orders') }}">Orders</a>
          <a href="{{ route('order-fulfillment.packing') }}">Packing</a>
          <a href="{{ route('order-fulfillment.shipping') }}">Shipping</a>
          <a href="{{ route('order-fulfillment.return') }}" class="active">Returns</a>
        </div>
        <div class="nav-divider"></div>
        <div class="profile-menu" id="profileMenu">
          <button type="button" class="profile-trigger" id="profileTrigger" aria-label="Account menu">
            <span class="avatar-initial">{{ strtoupper(substr(session('employee_name', 'Employee'), 0, 1)) }}</span>
          </button>
          <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-summary">
              <span class="avatar-initial avatar-initial-lg">{{ strtoupper(substr(session('employee_name', 'Employee'), 0, 1)) }}</span>
              <div class="profile-summary-text">
                <div class="profile-name">{{ session('employee_name', 'Employee') }}</div>
                <div class="profile-email">{{ session('employee_email', '') }}</div>
              </div>
          </div>
          <div class="divider"></div>
          <a href="{{ route('employee.portal') }}" class="profile-dropdown-row" style="text-decoration:none;">Employee Portal</a>
          <div class="profile-dropdown-row">
              <span class="dark-mode-label">🌙 Dark Mode</span>
              <label class="theme-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="theme-switch-slider"></span>
              </label>
            </div>
            <div class="divider"></div>
            <form method="POST" action="{{ route('order-fulfillment.logout') }}" style="margin:0;">
              @csrf
              <button type="submit" class="logout-btn">⏻ Logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>

<div class="stats-row">

  <div class="stat-card">
    <div class="label">Return requests pending</div>
    <div class="value">{{ $pendingReturns }}</div>
  </div>

  <div class="stat-card">
    <div class="label">In transit back</div>
    <div class="value">0</div>
  </div>

  <div class="stat-card">
    <div class="label">Refunds processed today</div>
    <div class="value">{{ $refundedToday }}</div>
  </div>

  <div class="stat-card">
    <div class="label">Return rate</div>
    <div class="value">0%</div>
  </div>

</div>

    <div class="content">

      <div class="panel returns-queue">
        <div class="panel-header">
          <div class="title">📋 Return requests</div>
          <div class="actions">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input type="text" id="returnSearch" placeholder="Search..." autocomplete="off">
            </div>

            <button id="filterBtn" class="filter-btn">
              Filter <span class="caret">▾</span>
              <span id="filterBadge" class="filter-badge">1</span>
            </button>

            @php
              // Built from whatever's actually on these returns, rather
              // than a hardcoded list, so the filter always matches real
              // status values.
              $returnStatusOptions = $returns->pluck('status')->filter()->unique()->sort()->values();
            @endphp
            <div id="filterPanel" class="filter-panel">
              <div class="filter-title">Status</div>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="" class="status-check" checked>
                All
              </label>
              @foreach ($returnStatusOptions as $statusOption)
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="{{ $statusOption }}" class="status-check">
                {{ $statusOption }}
              </label>
              @endforeach
            </div>
          </div>
        </div>
        <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Order Id</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Reason</th>
              <th>Status</th>
            </tr>
          </thead>
<tbody id="returnsTableBody">

@foreach($returns as $return)
@php
  // Mirrors order.blade.php's per-row status label/class approach so
  // the Returns tab's badges look and read the same way the Orders
  // tab's do — same pill shape, same uppercase text, same color
  // language (pending=gray, transit=blue, inspecting=amber/warn,
  // refunded=teal, completed=green, declined=red).
  $returnStatusLabels = [
      'Pending'                 => 'PENDING',
      'In Transit to Warehouse' => 'IN TRANSIT TO WAREHOUSE',
      'Inspecting'              => 'INSPECTING',
      'Refunded'                => 'REFUNDED',
      'Completed'               => 'COMPLETED',
      'Declined'                => 'DECLINED',
  ];
  $returnStatusLabel = $returnStatusLabels[$return->status] ?? strtoupper($return->status);
  $returnStatusClassMap = [
      'Pending'                 => 'status-pending',
      'In Transit to Warehouse' => 'status-transit',
      'Inspecting'              => 'status-inspecting',
      'Refunded'                => 'status-refunded',
      'Completed'               => 'status-completed',
      'Declined'                => 'status-declined',
  ];
  $returnStatusClass = $returnStatusClassMap[$return->status] ?? 'status-pending';
@endphp
<tr class="return-row"
    onclick="openReturnModal(this)"
    data-return-id="{{ $return->id }}"
    data-order-id="{{ $return->order_id }}"
    data-customer="{{ $return->customer_name }}"
    data-product="{{ $return->product_name }}"
    data-reason="{{ $return->reason }}"
    data-status="{{ $return->status }}"
    data-resolution="{{ $return->resolution }}"
>
    <td class="order-id">{{ $return->order_id }}</td>
    <td class="customer">{{ $return->customer_name }}</td>
    <td class="product">
        @php $itemCount = count(array_filter(array_map('trim', explode(',', $return->product_name)))); @endphp
        {{ $itemCount }} {{ Str::plural('item', $itemCount) }}
    </td>
    <td>{{ $return->reason }}</td>

    <td>
        <span class="status-badge {{ $returnStatusClass }}">
            {{ $returnStatusLabel }}
        </span>
    </td>
</tr>
@endforeach

<tr class="no-results-row" id="noResultsRow" style="display:none;">
    <td colspan="5">No returns match your search or filter.</td>
</tr>

</tbody>
        </table>
        </div>
      </div>

      <div class="panel activity">
        <div class="panel-header">
          <div class="title">📈 Refund activity</div>
        </div>
        <div class="refund-list">
        </div>
      </div>

    </div>
  </div>

  <!-- ============================================
       Modal lives OUTSIDE #pageContent so it never
       gets blurred itself.
       ============================================ -->
  <div class="overlay" id="returnOverlay">
    <div class="modal">
      <div class="modal-header">
        <h2>Return request <span id="modalOrderId">#ORD-4821</span></h2>
        <p id="modalCustomerProduct">Maria Santos</p>
      </div>

      <div class="modal-tags">
        <span class="status-badge" id="modalPriority">High priority</span>
      </div>

      <div class="modal-body">
        <p class="field-label">Items <span id="modalItemCount"></span></p>
        <ul class="items-list" id="modalItemsList"></ul>

        <p class="field-label">Reason for return</p>
        <p class="reason-title" id="modalReasonTitle">Defective - item stopped working after 2 days</p>
        <p class="reason-desc" id="modalReasonDesc">Customer reports the left earcup lost audio and the device won't hold a charge. No visible external damage.</p>

        <div class="meta-row">
          <div>
            <p class="field-label">Order value</p>
            <p class="field-value" id="modalValue">₱67.67</p>
          </div>
          <div>
            <p class="field-label">Requested on</p>
            <p class="field-value" id="modalRequestedOn">July 2, 2026</p>
          </div>
          <div>
            <p class="field-label">In transit</p>
            <p class="field-value" id="modalInTransit">Yes</p>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-close" onclick="closeReturnModal()">Close</button>
        <button class="btn btn-reject" id="modalRejectBtn">Reject return</button>
        <button class="btn btn-accept" id="modalAcceptBtn">Accept return</button>
      </div>
    </div>
  </div>

  <div class="filter-overlay" id="filterOverlay"></div>

  <script>
    // Demo data keyed by return id. Swap this for a fetch() call to your
    // backend if you want live data instead of hardcoded values.


// Returns created by the admin cancelling an order (rather than a customer
// requesting a return) have nothing to accept/reject — they're just moving
// stock back to the warehouse — so the modal shows Close only, no Accept.
const ADMIN_CANCEL_REASONS = ['Cancelled while shipping', 'Cancelled before shipping'];

const acceptUrlTemplate = @json(route('order-fulfillment.returns.accept', ['id' => '__ID__']));
const rejectUrlTemplate = @json(route('order-fulfillment.returns.decline', ['id' => '__ID__']));
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Mirrors order.blade.php's STATUS_LABELS/statusToClass/setStatusBadge
// helpers, just for return statuses instead of order statuses, so a
// status update (Accept/Reject here, or the returns:progress-lifecycle
// auto-promotion picked up on next page load) renders with the same
// uppercase text + pill color as the initial page render's PHP-side
// $returnStatusLabels/$returnStatusClassMap above.
const RETURN_STATUS_CLASSES = ['status-pending', 'status-transit', 'status-inspecting', 'status-refunded', 'status-completed', 'status-declined'];

const RETURN_STATUS_LABELS = {
  'Pending': 'PENDING',
  'In Transit to Warehouse': 'IN TRANSIT TO WAREHOUSE',
  'Inspecting': 'INSPECTING',
  'Refunded': 'REFUNDED',
  'Completed': 'COMPLETED',
  'Declined': 'DECLINED',
};

function returnStatusToLabel(status) {
  return RETURN_STATUS_LABELS[status] || String(status).toUpperCase();
}

function returnStatusToClass(status) {
  const map = {
    'Pending': 'status-pending',
    'In Transit to Warehouse': 'status-transit',
    'Inspecting': 'status-inspecting',
    'Refunded': 'status-refunded',
    'Completed': 'status-completed',
    'Declined': 'status-declined',
  };
  return map[status] || 'status-pending';
}

function setReturnStatusBadge(el, status) {
  if (!el) return;
  el.textContent = returnStatusToLabel(status);
  el.classList.remove(...RETURN_STATUS_CLASSES);
  el.classList.add(returnStatusToClass(status));
}

let currentReturnRow = null;

function openReturnModal(row)
{
    currentReturnRow = row;

    document.getElementById('modalOrderId').textContent =
        row.dataset.orderId;

    document.getElementById('modalCustomerProduct').textContent =
        row.dataset.customer;

    const items = row.dataset.product
        .split(',')
        .map(function (item) { return item.trim(); })
        .filter(Boolean);

    document.getElementById('modalItemCount').textContent =
        '(' + items.length + (items.length === 1 ? ' item)' : ' items)');

    const itemsList = document.getElementById('modalItemsList');
    itemsList.innerHTML = '';
    items.forEach(function (item) {
        const li = document.createElement('li');
        li.textContent = item;
        itemsList.appendChild(li);
    });

    setReturnStatusBadge(document.getElementById('modalPriority'), row.dataset.status);

    document.getElementById('modalReasonTitle').textContent =
        row.dataset.reason;

    const isAdminCancellation = ADMIN_CANCEL_REASONS.includes(row.dataset.reason);
    const isPending = row.dataset.status === 'Pending';
    const acceptBtn = document.getElementById('modalAcceptBtn');
    const rejectBtn = document.getElementById('modalRejectBtn');
    const showActions = !isAdminCancellation && isPending;
    acceptBtn.style.display = showActions ? '' : 'none';
    rejectBtn.style.display = showActions ? '' : 'none';
    acceptBtn.disabled = !isPending;
    rejectBtn.disabled = !isPending;
    acceptBtn.textContent = 'Accept return';

    document.getElementById('pageContent')
        .classList.add('blurred');

    document.getElementById('returnOverlay')
        .classList.add('active');
}

    function closeReturnModal() {
      currentReturnRow = null;
      document.getElementById('pageContent').classList.remove('blurred');
      document.getElementById('returnOverlay').classList.remove('active');
    }

    document.getElementById('modalAcceptBtn').addEventListener('click', function () {
      if (!currentReturnRow) return;

      const row = currentReturnRow;
      const btn = this;
      btn.disabled = true;
      btn.textContent = 'Accepting...';

      const url = acceptUrlTemplate.replace('__ID__', encodeURIComponent(row.dataset.returnId));

      fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data.success) {
            throw new Error(result.data.message || 'Could not accept this return.');
          }

          row.dataset.status = result.data.status;
          row.dataset.resolution = result.data.resolution;

          setReturnStatusBadge(row.querySelector('.status-badge'), result.data.status);

          closeReturnModal();
        })
        .catch(function (err) {
          alert(err.message);
          btn.disabled = false;
          btn.textContent = 'Accept return';
        });
    });

    document.getElementById('modalRejectBtn').addEventListener('click', function () {
      if (!currentReturnRow) return;

      const row = currentReturnRow;
      const btn = this;
      btn.disabled = true;
      btn.textContent = 'Rejecting...';

      fetch(rejectUrlTemplate.replace('__ID__', encodeURIComponent(row.dataset.returnId)), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok || !result.data.success) throw new Error(result.data.message || 'Could not reject this return.');
          row.dataset.status = result.data.status;
          row.dataset.resolution = result.data.resolution;
          setReturnStatusBadge(row.querySelector('.status-badge'), result.data.status);
          closeReturnModal();
        })
        .catch(function (err) {
          alert(err.message);
          btn.disabled = false;
          btn.textContent = 'Reject return';
        });
    });

    /* ===================== Search + Filter (working) ===================== */
    const returnRows     = Array.from(document.querySelectorAll('.return-row'));
    const searchInput    = document.getElementById('returnSearch');
    const filterBtn      = document.getElementById('filterBtn');
    const filterPanel    = document.getElementById('filterPanel');
    const filterOverlay  = document.getElementById('filterOverlay');
    const filterBadge    = document.getElementById('filterBadge');
    const noResultsRow   = document.getElementById('noResultsRow');
    const statusChecks = document.querySelectorAll('.status-check');

    function activeStatus() {
      const checked = Array.from(statusChecks).find(c => c.checked);
      return checked ? checked.value : '';
    }

    function applyReturnFilters() {
      const query = searchInput.value.trim().toLowerCase();
      const activeSt = activeStatus();
      let visibleCount = 0;

      returnRows.forEach(function (row) {
        const d = row.dataset;
        const haystack = [d.orderId, d.customer, d.product, d.reason, d.status]
          .join(' ')
          .toLowerCase();

        const matchesSearch = query === '' || haystack.includes(query);
        const matchesStatus = activeSt === '' || d.status === activeSt;
        const visible = matchesSearch && matchesStatus;

        row.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
      });

      noResultsRow.style.display = visibleCount === 0 ? '' : 'none';

      const activeFilterCount = activeSt !== '' ? 1 : 0;

      if (activeFilterCount > 0) {
        filterBtn.classList.add('active');
        filterBadge.style.display = 'inline-block';
        filterBadge.textContent = String(activeFilterCount);
      } else {
        filterBtn.classList.remove('active');
        filterBadge.style.display = 'none';
      }
    }

    function openFilterPanel() {
      filterPanel.classList.add('show');
      filterOverlay.classList.add('show');
      filterBtn.classList.add('open');
    }

    function closeFilterPanel() {
      filterPanel.classList.remove('show');
      filterOverlay.classList.remove('show');
      filterBtn.classList.remove('open');
    }

    filterBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      filterPanel.classList.contains('show') ? closeFilterPanel() : openFilterPanel();
    });

    filterOverlay.addEventListener('click', closeFilterPanel);

    statusChecks.forEach(function (c) {
      c.addEventListener('change', applyReturnFilters);
    });

    searchInput.addEventListener('input', applyReturnFilters);
    /* =================== end Search + Filter =================== */
  </script>

  <script>
    (function () {
      const menu = document.getElementById('profileMenu');
      const trigger = document.getElementById('profileTrigger');
      const dropdown = document.getElementById('profileDropdown');

      const darkModeToggle = document.getElementById('darkModeToggle');
      if (darkModeToggle) {
        darkModeToggle.checked = document.documentElement.classList.contains('dark-theme');
        darkModeToggle.addEventListener('change', function () {
          document.documentElement.classList.toggle('dark-theme', this.checked);
          try {
            localStorage.setItem('nexora-theme', this.checked ? 'dark' : 'light');
          } catch (e) {}
        });
      }

      if (!menu || !trigger || !dropdown) return;

      trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
      });

      document.addEventListener('click', function (e) {
        if (!menu.contains(e.target)) {
          dropdown.classList.remove('open');
        }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.classList.remove('open');
      });
    })();
  </script>

</body>
</html>
