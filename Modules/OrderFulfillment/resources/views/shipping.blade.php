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
<title>Nexora Shipping</title>
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

    /* "Ready for delivery" / dispatch-to-courier surfaces (banner, big
       action button). These need real light-mode colors of their own —
       the old dark-brown-on-dark-bg palette only worked against a dark
       background and looked like a stray dark box once the page itself
       went light. */
    --warn-bg: #FFF6E5;
    --warn-border: #F3D08A;
    --warn-text: #8A5A06;
    --warn-btn-bg: #F59E0B;
    --warn-btn-text: #FFFFFF;
    --warn-btn-hover: #DB8C0A;

    /* Cards/panels/modals sit flush against a very light background in
       light mode, so they need a soft shadow of their own for depth —
       in dark mode the panels already read clearly against the darker
       page background and don't need one. */
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

    --warn-bg: #3a3016;
    --warn-border: #6b5a24;
    --warn-text: #f3d98a;
    --warn-btn-bg: #6B4A1E;
    --warn-btn-text: #FBD38D;
    --warn-btn-hover: #7d5824;

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
    width: 46px;
    height: 50px;
    object-fit: contain;
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

  /* ===== Stats Row ===== */
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

  /* ---------- Main Content ---------- */
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

  .order-queue {
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
  .order-queue thead th {
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
    font-size: 14px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-soft);
  }

  tbody td { padding: 14px 24px; font-size: 14px; border-bottom: 1px solid var(--border-soft); }
  tbody tr:nth-child(even) { background: var(--row-alt); }

  .order-id, .product { color: var(--text-muted); }
  .customer { font-weight: 600; }

  .shipping-row { cursor: pointer; transition: background 0.15s ease; }
  .shipping-row:hover { background: rgba(255,255,255,0.04); }

  .status-tag {
    display: inline-block;
    font-weight: 700;
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 12px;
    white-space: nowrap;
  }

  .status-tag.tag-packing   { background: var(--warn-bg); color: var(--warn-text); border: 1px solid var(--warn-border); }
  .status-tag.tag-shipped   { background: #1E5A6B; color: #7DD3E8; }
  .status-tag.tag-transit   { background: #1E3A6B; color: #93C5FD; }
  .status-tag.tag-delivered { background: #1E5A3A; color: #86EFAC; }
  .status-tag.tag-complete { background: #1E5A3A; color: #86EFAC; }
  .status-tag.tag-cancelled { background: #4A1E1E; color: #F3A9A9; }

  .btn-prepare {
    display: inline-block;
    background: var(--accent);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 13px;
    padding: 7px 16px;
    border-radius: 20px;
    text-align: center;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(59,130,246,0.35);
    transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
  }

  .btn-prepare:hover {
    background: #2563EB;
    box-shadow: 0 4px 14px rgba(59,130,246,0.45);
    transform: translateY(-1px);
  }

  .btn-prepare:active {
    background: #1D4ED8;
    box-shadow: 0 2px 6px rgba(59,130,246,0.35);
    transform: translateY(0);
  }

  .empty-row td { height: 38px; }

  /* Delivery alerts */
  .activity-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
  }

  .activity-list::-webkit-scrollbar {
    width: 8px;
  }
  .activity-list::-webkit-scrollbar-track {
    background: transparent;
  }
  .activity-list::-webkit-scrollbar-thumb {
    background: var(--pill-border);
    border-radius: 8px;
  }
  .activity-list::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
  }

  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
  }

  .activity-item:last-child { border-bottom: none; }
  .activity-icon { width: 18px; text-align: center; flex-shrink: 0; margin-top: 2px; }

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
    width: 520px;
    max-width: 90vw;
    max-height: 85vh;
    background: var(--bg-card);
    border-radius: 14px;
    overflow-y: auto;
    box-shadow: var(--modal-shadow);
    border: 1px solid var(--border-soft);
    scrollbar-width: none;      /* Firefox */
    -ms-overflow-style: none;   /* old Edge/IE */
  }

  .modal::-webkit-scrollbar {
    display: none;              /* Chrome/Safari/new Edge */
  }

  .modal-header { background: var(--bg-dark); padding: 16px 24px; }
  .modal-header h2 { margin: 0; color: var(--text-light); font-size: 16px; }
  .modal-header p { margin: 3px 0 0; color: var(--text-muted); font-size: 12px; }

  .modal-body {
    padding: 18px 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 18px;
  }

  .modal-body .field-label { margin: 0 0 4px; font-size: 11px; color: var(--text-muted); }
  .modal-body .field-value { margin: 0; font-size: 14px; color: var(--text-light); font-weight: 600; }

  .modal-body .status-pill {
    display: inline-block;
    font-weight: 700;
    font-size: 13px;
    padding: 4px 12px;
    border-radius: 12px;
    background: #1E5A6B;
    color: #7DD3E8;
    white-space: nowrap;
  }

  .modal-body .status-pill.tag-packing   { background: var(--warn-bg); color: var(--warn-text); border: 1px solid var(--warn-border); }
  .modal-body .status-pill.tag-shipped   { background: #1E5A6B; color: #7DD3E8; }
  .modal-body .status-pill.tag-transit   { background: #1E3A6B; color: #93C5FD; }
  .modal-body .status-pill.tag-delivered { background: #1E5A3A; color: #86EFAC; }
  .modal-body .status-pill.tag-complete { background: #1E5A3A; color: #86EFAC; }
  .modal-body .status-pill.tag-cancelled { background: #4A1E1E; color: #F3A9A9; }

  .assign-banner {
    margin: 0 24px 16px;
    background: var(--warn-bg);
    border: 1px solid var(--warn-border);
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    color: var(--warn-text);
    font-size: 12.5px;
  }

  /* ===== Order items breakdown (order modal + assign-driver modal) ===== */
  .items-section {
    background: var(--bg-dark);
    border: 1px solid var(--pill-border);
    border-radius: 10px;
    padding: 12px 14px;
  }

  .items-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
  }

  .items-badge {
    background: var(--pill);
    border: 1px solid var(--pill-border);
    color: var(--text-light);
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
  }

  .items-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 190px;
    overflow-y: auto;
    padding-right: 4px;
  }

  .items-list::-webkit-scrollbar {
    width: 8px;
  }
  .items-list::-webkit-scrollbar-track {
    background: transparent;
  }
  .items-list::-webkit-scrollbar-thumb {
    background: var(--pill-border);
    border-radius: 8px;
  }
  .items-list::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
  }

  .items-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 10px;
    background: var(--row-alt);
    border-radius: 8px;
  }

  .items-row-name {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-light);
  }

  .items-row-qty {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
  }

  .items-row-amount {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-light);
    white-space: nowrap;
  }

  .items-total-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
    padding: 10px 12px;
    background: var(--pill, #1b3a6b);
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-light);
  }
  /* ===== end order items breakdown ===== */


  .assign-banner.hidden { display: none; }

  .btn-assign-driver {
    background: var(--warn-btn-bg);
    color: var(--warn-btn-text);
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
  }

  .btn-assign-driver:hover { background: var(--warn-btn-hover); }

  .modal-footer {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--border-soft);
  }

  .btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-size: 13.5px;
    cursor: pointer;
  }

  .btn-close { background: #2b4a7c; color: #dbe4f5; }
  .btn-close:hover { background: #345a94; }

  .btn-cancel { background: #7a2340; color: #f9c3d3; }
  .btn-cancel:hover { background: #8f2a4b; }

  /* Footer button swaps to this when the order is ready-to-ship / in the
     assign-driver flow, replacing "Cancel order" (see openShippingModal). */
  .btn-assign-driver-footer { background: var(--warn-btn-bg); color: var(--warn-btn-text); }
  .btn-assign-driver-footer:hover { background: var(--warn-btn-hover); }

  /* ===== Cancel confirmation modal ===== */
  .confirm-modal { width: 420px; }
  .confirm-modal .modal-body {
    display: block;
    padding: 22px 28px 6px;
  }
  .confirm-text {
    margin: 0 0 16px;
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.6;
  }
  .confirm-text strong { color: var(--text-light); }

  .assign-toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #22c55e;
    color: #08240f;
    font-weight: 700;
    font-size: 14px;
    padding: 12px 22px;
    border-radius: 8px;
    opacity: 0;
    transition: opacity 0.25s ease, transform 0.25s ease;
    z-index: 200;
    pointer-events: none;
  }

  .assign-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }

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
        <x-client-logo :size="50" />
      </div>
      <div class="nav-actions">
        <div class="nav-links">
          <a href="{{ route('order-fulfillment.dashboard') }}">Dashboard</a>
          <a href="{{ route('order-fulfillment.orders') }}">Orders</a>
          <a href="{{ route('order-fulfillment.packing') }}">Packing</a>
          <a href="{{ route('order-fulfillment.shipping') }}" class="active">Shipping</a>
          <a href="{{ route('order-fulfillment.return') }}">Returns</a>
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

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="label">In shipping</div>
        <div class="value">{{ $inShipping }}</div>
      </div>
      <div class="stat-card">
        <div class="label">In transit</div>
        <div class="value">{{ $inTransit }}</div>
      </div>
      <div class="stat-card">
        <div class="label">Delivery rate</div>
        <div class="value">{{ $onTimeRate }}%</div>
      </div>
      <div class="stat-card">
        <div class="label">Delayed shipment</div>
        <div class="value">{{ $delayed }}</div>
      </div>
    </div>

    <section class="content">

      <div class="panel order-queue">
        <div class="panel-header">
          <div class="title">📦 Shipment tracking</div>
          <div class="actions">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input type="text" id="shippingSearch" placeholder="Search..." autocomplete="off">
            </div>

            <button id="filterBtn" class="filter-btn">
              Filter <span class="caret">▾</span>
              <span id="filterBadge" class="filter-badge">1</span>
            </button>

            <div id="filterPanel" class="filter-panel">
              <div class="filter-title">Status</div>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="" class="status-check" checked>
                All
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="READY_TO_SHIP" class="status-check">
                READY FOR DELIVERY
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="SHIPPED" class="status-check">
                SHIPPED
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="OUT_FOR_DELIVERY" class="status-check">
                OUT FOR DELIVERY
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="DELIVERED" class="status-check">
                DELIVERED
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="COMPLETE" class="status-check">
                COMPLETE
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="DELAYED" class="status-check">
                DELAYED
              </label>
            </div>
          </div>
        </div>
        <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Shipment Id</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Tracking no.</th>
              <th class="th-status">Status</th>
              <th>Destination</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="shippingTableBody">
            @foreach($shipments as $shipment)
@php
    $statusRaw = strtoupper($shipment->status);
    $statusLabels = [
        'SHIPPED'           => 'SHIPPED',
        'READY_TO_SHIP'     => 'READY FOR DELIVERY',
        'OUT_FOR_DELIVERY'  => 'OUT FOR DELIVERY',
        'DELIVERED'         => 'DELIVERED',
        'COMPLETE'          => 'COMPLETE',
        'DELAYED'           => 'DELAYED',
    ];
    $statusLabel = $statusLabels[$statusRaw] ?? strtoupper(str_replace('_', ' ', $statusRaw));
    $statusClassMap = [
        'SHIPPED'           => 'tag-shipped',
        'READY_TO_SHIP'     => 'tag-packing',
        'OUT_FOR_DELIVERY'  => 'tag-transit',
        'DELIVERED'         => 'tag-delivered',
        'COMPLETE'          => 'tag-complete',
        'DELAYED'           => 'tag-cancelled',
    ];
    $statusClass = $statusClassMap[$statusRaw] ?? 'tag-shipped';
@endphp
<tr
    class="shipping-row"
    data-id="{{ $shipment->shipment_id }}"
    data-customer="{{ $shipment->customer_name }}"
    data-product="{{ collect($shipment->items ?? [])->pluck('product_name')->implode(', ') }}"
    data-tracking="{{ $shipment->tracking_number }}"
    data-status="{{ $statusRaw }}"
    data-destination="{{ $shipment->address }}"
    data-amount="{{ number_format($shipment->amount, 2) }}"
    onclick="openShippingModal('{{ $shipment->shipment_id }}')"
>

    <td class="order-id">{{ $shipment->shipment_id }}</td>
    <td class="customer">{{ $shipment->customer_name }}</td>
    <td class="product">{{ $shipment->items_count ?? 0 }} {{ ($shipment->items_count ?? 0) === 1 ? 'item' : 'items' }}</td>
    <td class="tracking">{{ $shipment->tracking_number }}</td>

    <td class="status-cell">
        <span class="status-tag {{ $statusClass }}">{{ $statusLabel }}</span>
    </td>

    <td>{{ $shipment->address }}</td>

    <td>
      @if($statusRaw === 'READY_TO_SHIP')
        <button
            class="btn-prepare"
            onclick="event.stopPropagation(); openShippingModal('{{ $shipment->shipment_id }}', true)">
            Dispatch to Courier
        </button>
        @endif
    </td>

</tr>
@endforeach

            <tr class="no-results-row" id="noResultsRow" style="display:none;">
              <td colspan="7">No shipments match your search or filter.</td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <div class="panel activity">
        <div class="panel-header">
          <div class="title">🔔 Delivery alerts</div>
        </div>
        <div class="activity-list" id="deliveryAlertsList">
          @forelse($deliveryAlerts as $alert)
          <div class="activity-item" data-alert-id="{{ $alert->id }}">
            <span class="activity-icon">{{ $alert->icon }}</span>
            <span class="activity-message">{{ $alert->message }}</span>
          </div>
          @empty
          <div class="activity-item">
            <span class="activity-message" style="color: var(--text-muted);">No recent activity.</span>
          </div>
          @endforelse
        </div>
      </div>

    </section>
  </div>

  <!-- ============================================
       Modals live OUTSIDE #pageContent so they never
       get blurred themselves.
       ============================================ -->

  <!-- Order detail modal -->
  <div class="overlay" id="packingOverlay">
    <div class="modal">
      <div class="modal-header">
        <h2 id="modalOrderId">—</h2>
        <p>Website order</p>
      </div>

      <div class="modal-body">
        <div>
          <p class="field-label">Customer</p>
          <p class="field-value" id="modalCustomer">—</p>
        </div>
        <div>
          <p class="field-label">Status</p>
          <span class="status-pill tag-packing" id="modalStatus">—</span>
        </div>
        <div>
          <p class="field-label">Tracing no.</p>
          <p class="field-value" id="modalTracking">—</p>
        </div>
        <div>
          <p class="field-label">Courier</p>
          <p class="field-value" id="modalCourier">—</p>
        </div>
        <div>
          <p class="field-label">Due date</p>
          <p class="field-value" id="modalDue">—</p>
        </div>
        <div>
          <p class="field-label">Delivery Address</p>
          <p class="field-value" id="modalAddress">—</p>
        </div>
        <div style="grid-column: 1 / -1;">
          <div class="items-section">
            <div class="items-section-header">
              <p class="field-label" style="margin:0;">Items in this order</p>
              <span class="items-badge" id="modalItemsBadge">0 items</span>
            </div>
            <div class="items-list" id="modalItemsList"></div>
            <div class="items-total-row">
              <span>Total amount</span>
              <span id="modalItemsTotal">—</span>
            </div>
          </div>
        </div>
      </div>

      <div class="assign-banner" id="assignBanner">
        <span>This order is ready for delivery. Hand it off to the selected courier partner.</span>
        <button class="btn-assign-driver" id="assignBannerBtn" onclick="dispatchShipment()">Dispatch to courier</button>
      </div>

      <div class="modal-footer">
        <button class="btn btn-close" onclick="closePackingModal()">Close</button>
        <button class="btn btn-cancel" id="modalActionBtn" onclick="requestCancelOrder()">Cancel order</button>
      </div>
    </div>
  </div>

  <!-- Cancel-order confirmation modal -->
  <div class="overlay" id="cancelConfirmOverlay">
    <div class="modal confirm-modal">
      <div class="modal-header">
        <h2>Cancel this order?</h2>
        <p>This can't be undone</p>
      </div>
      <div class="modal-body">
        <p class="confirm-text">
          Are you sure you want to cancel order <strong id="cancelConfirmOrderId">—</strong>?
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-close" onclick="closeCancelConfirm()">Keep order</button>
        <button class="btn btn-cancel" id="confirmCancelBtn" onclick="confirmCancelOrder()">Yes, cancel order</button>
      </div>
    </div>
  </div>

  <div class="filter-overlay" id="filterOverlay"></div>

  <div class="assign-toast" id="assignToast">Shipment dispatched successfully</div>

  <script>

    // Base URL for the shipping endpoints. Built from the *named* shipping
    // route (the same one the "Shipping" nav link above resolves through)
    // rather than a hardcoded "/shipping" path, so it works no matter where
    // this app is actually mounted/prefixed (e.g. served from a subfolder,
    // or behind a module prefix like /order-fulfillment/shipping rather
    // than the domain root). A hardcoded path silently drifted out of sync
    // with the real route and caused "Could not load drivers" 404s.
    const SHIPPING_BASE_URL = "{{ rtrim(route('order-fulfillment.shipping'), '/') }}";

    const orders = @json($shipments->keyBy('shipment_id'));
    const statusLabels = {
      'SHIPPED': 'SHIPPED',
      'READY_TO_SHIP': 'READY FOR DELIVERY',
      'OUT_FOR_DELIVERY': 'OUT FOR DELIVERY',
      'DELIVERED': 'DELIVERED',
      'COMPLETE': 'COMPLETE',
      'DELAYED': 'DELAYED',
    };
    const statusTagClasses = {
      'SHIPPED': 'tag-shipped',
      'READY_TO_SHIP': 'tag-packing',
      'OUT_FOR_DELIVERY': 'tag-transit',
      'DELIVERED': 'tag-delivered',
      'COMPLETE': 'tag-complete',
      'DELAYED': 'tag-cancelled',
    };
    const STATUS_TAG_CLASSES = ['tag-packing', 'tag-shipped', 'tag-transit', 'tag-delivered', 'tag-complete', 'tag-cancelled'];

    let currentOrderId = null;


    function formatCurrency(n) {
      const num = Number(n) || 0;
      return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Title-cases a status string like "OUT_FOR_DELIVERY"/"OUT FOR DELIVERY"
    // for use inline in a sentence (the all-caps pill labels only look right
    // as small badges, not sitting in "... is now OUT FOR DELIVERY").
    function toSentenceStatus(str) {
      return String(str)
        .replace(/_/g, ' ')
        .toLowerCase()
        .replace(/\b\w/g, c => c.toUpperCase());
    }

    // Renders an order's line items + total into the given list/badge/total
    // elements. Shared by the order-detail modal and the assign-driver modal
    // so both stay in sync with the same $shipment->items payload.
    function renderOrderItems(order, listElId, badgeElId, totalElId) {
      const listEl = document.getElementById(listElId);
      const badgeEl = document.getElementById(badgeElId);
      const totalEl = document.getElementById(totalElId);
      const items = (order && order.items) || [];

      listEl.innerHTML = '';

      if (!items.length) {
        listEl.innerHTML = '<p style="color: var(--text-muted); margin: 0; padding: 4px 0;">No item details available.</p>';
      } else {
        items.forEach(function (item) {
          const row = document.createElement('div');
          row.className = 'items-row';
          row.innerHTML = `
            <div>
              <div class="items-row-name">${item.product_name}</div>
              <div class="items-row-qty">Qty ${item.qty}</div>
            </div>
            <div class="items-row-amount">${formatCurrency(item.line_total)}</div>
          `;
          listEl.appendChild(row);
        });
      }

      badgeEl.textContent = items.length + (items.length === 1 ? ' item' : ' items');

      // Sum the line items rather than trusting order.amount — that column
      // on the shipments table isn't actually populated (defaults to 0), so
      // relying on it was showing ₱0.00 even when items had real amounts.
      const total = items.reduce((sum, it) => sum + (Number(it.line_total) || 0), 0);
      totalEl.textContent = formatCurrency(total);
    }

    function openShippingModal(orderId, showBanner) {
      const order = orders[orderId];
      if (order) {
        document.getElementById('modalOrderId').textContent = orderId;
        document.getElementById('modalCustomer').textContent = order.customer_name;
        document.getElementById('modalTracking').textContent = order.tracking_number;
        const modalStatusEl = document.getElementById('modalStatus');
        modalStatusEl.textContent = statusLabels[order.status] || order.status;
        modalStatusEl.classList.remove(...STATUS_TAG_CLASSES);
        modalStatusEl.classList.add(statusTagClasses[order.status] || 'tag-shipped');
        document.getElementById('modalCourier').textContent = order.courier;
        document.getElementById('modalDue').textContent = order.due_date;
        document.getElementById('modalAddress').textContent = order.address;

        renderOrderItems(order, 'modalItemsList', 'modalItemsBadge', 'modalItemsTotal');
      }

      // Only reveal the yellow "assign a driver" banner when the modal was
      // opened via the Assign Driver button — not from a plain row click.
      document.getElementById('assignBanner').classList.toggle('hidden', !showBanner);

      // Same condition drives the footer's action button. The banner
      // already has its own "Assign driver" button, so when it's showing,
      // just hide the footer action button instead of duplicating it.
      const actionBtn = document.getElementById('modalActionBtn');
      if (showBanner) {
        actionBtn.style.display = 'none';
      } else {
        actionBtn.style.display = '';
        actionBtn.textContent = 'Cancel order';
        actionBtn.className = 'btn btn-cancel';
        actionBtn.onclick = requestCancelOrder;
      }

      currentOrderId = orderId;
      document.getElementById('pageContent').classList.add('blurred');
      document.getElementById('packingOverlay').classList.add('active');
    }

    function closePackingModal() {
      document.getElementById('pageContent').classList.remove('blurred');
      document.getElementById('packingOverlay').classList.remove('active');
      currentOrderId = null;
    }

    // Dispatching to the selected courier partner is a single action that
    // moves the shipment straight to OUT_FOR_DELIVERY.
    async function dispatchShipment() {
      if (!currentOrderId) return;

      const btn = document.getElementById('assignBannerBtn');
      btn.disabled = true;
      btn.textContent = 'Dispatching…';

      try {
        const res = await fetch(`${SHIPPING_BASE_URL}/${currentOrderId}/dispatch`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          }
        });

        const data = await res.json();

        if (!res.ok) {
          showAssignToast(data.message || 'Could not dispatch the shipment.', true);
          return;
        }

        applyAssignmentToRow(currentOrderId, data.status);
        pushDeliveryAlert(currentOrderId, data.status);

        closePackingModal();
        showAssignToast(data.message);
      } catch (err) {
        showAssignToast('Network error — please try again.', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Dispatch to courier';
      }
    }

    // ===================== Cancel order flow =====================

    function requestCancelOrder() {
      if (!currentOrderId) return;

      document.getElementById('cancelConfirmOrderId').textContent = currentOrderId;
      document.getElementById('packingOverlay').classList.remove('active');
      document.getElementById('cancelConfirmOverlay').classList.add('active');
    }

    function closeCancelConfirm() {
      document.getElementById('cancelConfirmOverlay').classList.remove('active');
      document.getElementById('packingOverlay').classList.add('active');
    }

    async function confirmCancelOrder() {
      if (!currentOrderId) return;

      const btn = document.getElementById('confirmCancelBtn');
      btn.disabled = true;
      btn.textContent = 'Cancelling…';

      try {
        const res = await fetch(`${SHIPPING_BASE_URL}/${currentOrderId}/cancel`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          }
        });

        const data = await res.json();

        if (!res.ok) {
          showAssignToast(data.message || 'Could not cancel order.', true);
          return;
        }

        removeShippingRow(currentOrderId);
        pushDeliveryAlert(currentOrderId, 'CANCELLED', 'moved to Returns');

        document.getElementById('cancelConfirmOverlay').classList.remove('active');
        document.getElementById('pageContent').classList.remove('blurred');

        showAssignToast(data.message || 'Order cancelled and moved to Returns.');

        currentOrderId = null;
      } catch (err) {
        showAssignToast('Network error — please try again.', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Yes, cancel order';
      }
    }

    // Cancelled shipments no longer belong on the Shipping page at all
    // (they now live on the Returns page), so drop the row entirely
    // rather than just updating its status pill.
    function removeShippingRow(orderId) {
      delete orders[orderId];

      const row = document.querySelector('.shipping-row[data-id="' + orderId + '"]');
      if (row) row.remove();

      const idx = shippingRows.findIndex(r => r.dataset.id === orderId);
      if (idx !== -1) shippingRows.splice(idx, 1);

      applyShippingFilters();
    }

    // ===================== end cancel order flow =====================

    // Reflect the new status on the table row immediately, without a full
    // page reload: swap the status pill and drop the now-irrelevant
    // "Assign Driver" button.
    function applyAssignmentToRow(orderId, newStatus) {
      if (orders[orderId]) orders[orderId].status = newStatus;

      const row = document.querySelector('.shipping-row[data-id="' + orderId + '"]');
      if (!row) return;

      row.dataset.status = newStatus;

      const tag = row.querySelector('.status-cell .status-tag');
      if (tag) {
        tag.textContent = statusLabels[newStatus] || newStatus;
        tag.classList.remove(...STATUS_TAG_CLASSES);
        tag.classList.add(statusTagClasses[newStatus] || 'tag-shipped');
      }

      const actionCell = row.querySelector('td:last-child');
      if (actionCell) actionCell.innerHTML = '';
    }

    // Mirrors the message format ShippingController@index builds for
    // $deliveryAlerts, so a freshly-assigned shipment shows up immediately
    // instead of waiting for the next full page load.
    function pushDeliveryAlert(orderId, newStatus, customMessage) {
      const list = document.getElementById('deliveryAlertsList');
      if (!list) return;

      // Drop the "No recent activity." placeholder if it's the only thing there.
      const placeholder = list.querySelector('.activity-item:not([data-alert-id])');
      if (placeholder && list.children.length === 1) placeholder.remove();

      const message = customMessage
        ? `${orderId} ${customMessage}`
        : `${orderId} is now ${toSentenceStatus(statusLabels[newStatus] || newStatus)}`;

      const item = document.createElement('div');
      item.className = 'activity-item';
      item.dataset.alertId = orderId;
      item.innerHTML = `
        <span class="activity-icon">🔔</span>
        <span class="activity-message">${message}</span>
      `;

      list.prepend(item);

      // Keep it capped at 10, same as the controller's ->take(10).
      while (list.children.length > 10) {
        list.removeChild(list.lastElementChild);
      }
    }

    function showAssignToast(message, isError = false) {
      const toast = document.getElementById('assignToast');
      toast.textContent = message;
      toast.style.background = isError ? '#ef4444' : '#22c55e';
      toast.style.color = isError ? '#ffffff' : '#08240f';
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2600);
    }

    // Click outside either modal (on the dim backdrop) to close everything
    ['packingOverlay', 'cancelConfirmOverlay'].forEach(function (id) {
      document.getElementById(id).addEventListener('click', function (e) {
        if (e.target.id === id) {
          document.getElementById('packingOverlay').classList.remove('active');
          document.getElementById('cancelConfirmOverlay').classList.remove('active');
          document.getElementById('pageContent').classList.remove('blurred');
          currentOrderId = null;
        }
      });
    });

    /* ===================== Search + Filter (working) ===================== */
    const shippingRows   = Array.from(document.querySelectorAll('.shipping-row'));
    const searchInput    = document.getElementById('shippingSearch');
    const filterBtn      = document.getElementById('filterBtn');
    const filterPanel    = document.getElementById('filterPanel');
    const filterOverlay  = document.getElementById('filterOverlay');
    const filterBadge    = document.getElementById('filterBadge');
    const noResultsRow   = document.getElementById('noResultsRow');
    const statusChecks   = document.querySelectorAll('.status-check');

    function activeStatus() {
      const checked = Array.from(statusChecks).find(c => c.checked);
      return checked ? checked.value : '';
    }

    function applyShippingFilters() {
      const query = searchInput.value.trim().toLowerCase();
      const active = activeStatus();
      let visibleCount = 0;

      shippingRows.forEach(function (row) {
        const d = row.dataset;
        const haystack = [d.id, d.customer, d.product, d.tracking, d.status, d.destination]
          .join(' ')
          .toLowerCase();

        const matchesSearch = query === '' || haystack.includes(query);
        const matchesStatus = active === '' || d.status === active;
        const visible = matchesSearch && matchesStatus;

        row.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
      });

      noResultsRow.style.display = visibleCount === 0 ? '' : 'none';

      if (active !== '') {
        filterBtn.classList.add('active');
        filterBadge.style.display = 'inline-block';
        filterBadge.textContent = '1';
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
      c.addEventListener('change', applyShippingFilters);
    });

    searchInput.addEventListener('input', applyShippingFilters);
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
