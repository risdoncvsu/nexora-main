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
<title>Nexora Orders</title>
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
    width: 46px;
    height: 50px;
    object-fit: contain;
  }

  .brand-text .title {
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 1px;
  }

  .brand-text .subtitle {
    font-size: 11px;
    color: #3B82F6;
    letter-spacing: 1px;
  }

  .nav-links {
    display: flex;
    gap: 36px;
  }

  .nav-links a {
    color: var(--header-muted);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
  }

  .nav-links a.active {
    color: var(--header-text);
    font-weight: 700;
  }

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

  .stat-card .label {
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
  }

  .stat-card .value {
    font-size: 32px;
    font-weight: 700;
  }

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
    /* Fixed frame: panel height never grows past this, queue scrolls inside it */
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
    max-height: 420px;
    overflow-y: auto;
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

  table {
    width: 100%;
    border-collapse: collapse;
    background: var(--bg-card);
  }

  thead th {
    text-align: left;
    padding: 12px 20px;
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--border-soft);
  }

  tbody tr {
    border-bottom: 1px solid var(--border-soft);
    transition: background 0.15s ease;
  }

  tbody tr:last-child {
    border-bottom: none;
  }

  tbody tr:nth-child(even) { background: var(--row-alt); }

  tbody tr:hover {
    background: var(--row-hover);
    cursor: pointer;
  }

  td {
    padding: 14px 20px;
    font-size: 14px;
    color: var(--text-light);
    vertical-align: middle;
  }

  td.order-id {
    color: var(--text-muted);
  }

  .th-qty, .qty-cell,
  .th-status, .status-cell,
  .th-priority, .priority-cell {
    text-align: center;
  }

  td.customer {
    color: var(--text-light);
    font-weight: 700;
  }

  .badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
    white-space: nowrap;
  }

  .badge.status {
    padding: 3px 10px;
    border-radius: 12px;
    background: var(--pill);
    color: var(--text-muted);
  }

  .badge.status.status-new { background: var(--pill); color: var(--text-muted); }
  .badge.status.status-packing { background: var(--warn-bg); color: var(--warn-text); border: 1px solid var(--warn-border); }
  .badge.status.status-transit { background: #1E3A6B; color: #93C5FD; }
  .badge.status.status-shipped { background: #1E5A6B; color: #7DD3E8; }
  .badge.status.status-delivered { background: #1E5A3A; color: #86EFAC; }
  .badge.status.status-complete { background: #1E5A3A; color: #86EFAC; }
  .badge.status.status-cancelled { background: #4A1E1E; color: #F3A9A9; }
  .badge.status.status-returned { background: #4A3A1E; color: #F3D3A9; }

  .badge.priority {
    background: #6B2B2B;
    color: #F3A9A9;
  }

  .badge.priority2 {
    background: #6B5A1E;
    color: #FBE38D;
  }

  .prepare-btn, .btn-prepare {
    background: var(--accent);
    color: #FFFFFF;
    border: none;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-block;
    box-shadow: 0 2px 6px rgba(59,130,246,0.35);
    transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
  }

  .prepare-btn:hover, .btn-prepare:hover {
    background: #2563EB;
    box-shadow: 0 4px 14px rgba(59,130,246,0.45);
    transform: translateY(-1px);
  }

  .prepare-btn:active, .btn-prepare:active {
    background: #1D4ED8;
    box-shadow: 0 2px 6px rgba(59,130,246,0.35);
    transform: translateY(0);
  }

  .empty-row td {
    height: 38px;
  }

  /* Recent Activity */
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

  .activity-icon {
    width: 18px;
    text-align: center;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .icon-cart { color: #5C9AE0; }
  .icon-truck { color: #5C9AE0; }
  .icon-warn { color: #E0735C; }

  .activity-empty {
    height: 50px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
  }

  /* Blur + modal mechanism */
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

  .overlay.active {
    display: flex;
  }

  .modal {
    width: 480px;
    max-width: 90vw;
    max-height: 85vh;
    background: var(--bg-card);
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    overflow-x: hidden;
    overflow-y: auto;
    box-shadow: var(--modal-shadow);
  }

  .modal-header {
    background: var(--bg-dark);
    padding: 20px 28px;
  }

  .modal-header h2 {
    margin: 0;
    color: var(--text-light);
    font-size: 18px;
  }

  .modal-header p {
    margin: 4px 0 0;
    color: var(--text-muted);
    font-size: 13px;
  }

  .modal-body-grid {
    padding: 20px 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 20px;
  }

  .modal-body-grid .field-label {
    margin: 0 0 6px;
    font-size: 12px;
    color: var(--text-muted);
  }

  .modal-body-grid .field-value {
    margin: 0;
    font-size: 15px;
    color: var(--text-light);
    font-weight: 600;
  }

  /* ===== Items section (order modal) ===== */
  .items-section {
    padding: 4px 28px 24px;
  }

  .items-heading {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-muted);
  }

  .items-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 14px;
    max-height: 260px;
    overflow-y: auto;
    padding-right: 6px;
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

  .item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-dark);
    border: 1px solid var(--border-soft);
    border-radius: 8px;
    padding: 10px 14px;
  }

  .item-row .item-name {
    margin: 0 0 3px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-light);
  }

  .item-row .item-meta {
    margin: 0;
    font-size: 12px;
    color: var(--text-muted);
  }

  .item-row .item-line-total {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-light);
    white-space: nowrap;
    padding-left: 12px;
  }

  .items-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid var(--border-soft);
    font-size: 14px;
  }

  .items-total .items-total-label {
    color: var(--text-muted);
    font-weight: 600;
  }

  .items-total .items-total-value {
    color: var(--text-light);
    font-weight: 700;
    font-size: 16px;
  }

  .modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 28px;
    border-top: 1px solid var(--border-soft);
  }

  .btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
  }

  .btn-close {
    background: var(--bg-dark);
    color: var(--text-light);
  }

  .btn-close:hover {
    background: var(--pill-border);
  }

  .btn-cancel {
    background: #7a2340;
    color: #f9c3d3;
  }

  .btn-cancel:hover {
    background: #8f2a4b;
  }
  /* Priority tags — colors standardized to match dashboard.blade.php's
     .tag-low / .tag-medium / .tag-high palette (based on order age). */
  .priority-high {
    background: #6B1E1E;
    color: #FB8D8D;
}

.priority-medium {
    background: #6B5A1E;
    color: #FBE38D;
}

.priority-low {
    background: #6B2B2B;
    color: #F3A9A9;
}

.priority-new {
    background: var(--pill);
    color: var(--text-muted);
}

/* ===== Cancel confirmation modal (stacked on top of order modal) ===== */
.cancel-modal-overlay {
    z-index: 200;
    background: rgba(5, 12, 28, 0.65);
}

.cancel-warning {
    text-align: center;
    padding: 4px 28px 18px;
}

.cancel-warning .warning-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: rgba(225,75,90,0.16);
    border: 1px solid rgba(225,75,90,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.cancel-warning .cancel-title {
    margin: 0 0 8px;
    font-size: 17px;
    font-weight: 700;
    color: var(--text-light);
}

.cancel-warning .cancel-desc {
    margin: 0 auto;
    max-width: 340px;
    font-size: 13.5px;
    line-height: 1.5;
    color: var(--text-muted);
}

.cancel-items-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0 28px 20px;
    padding: 12px 14px;
    background: var(--bg-dark);
    border: 1px solid var(--border-soft);
    border-radius: 10px;
}

.cancel-items-list .cancel-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13.5px;
}

.cancel-items-list .cancel-item-name {
    color: var(--text-light);
}

.cancel-items-list .cancel-item-amount {
    color: var(--text-light);
    font-weight: 700;
}

.cancel-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 0 28px 28px;
}

.cancel-actions button {
    width: 100%;
    border: none;
    border-radius: 9px;
    padding: 13px 0;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}

.btn-yes-full { background: #b3374a; color: #fff; }
.btn-yes-full:hover { background: #c23f54; }

.btn-no-full {
    background: transparent;
    border: 1px solid var(--border-soft) !important;
    color: var(--text-light);
}
.btn-no-full:hover { background: var(--bg-dark); }

.btn-cancel.disabled { opacity: .4; cursor: not-allowed; }

.priority-cancelled {
    background: #4A1E1E;
    color: #F3A9A9;
}

/* Success toast — same look used on the Shipping and Packing pages. */
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
      <div class="brand brand-logo" title="Nexora">
    <a href="{{ route('order-fulfillment.dashboard') }}" aria-label="Order Fulfillment dashboard"><img class="logo" src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora Logo"></a>
    <x-client-logo :size="50" />
</div>
      <div class="nav-actions">
        <div class="nav-links">
          <a href="{{ route('order-fulfillment.dashboard') }}">Dashboard</a>
          <a href="{{ route('order-fulfillment.orders') }}" class="active">Orders</a>
          <a href="{{ route('order-fulfillment.packing') }}">Packing</a>
          <a href="{{ route('order-fulfillment.shipping') }}">Shipping</a>
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
        <div class="label">Total orders</div>
        <div class="value">{{ $totalOrders }}</div>
      </div>
      <div class="stat-card">
        <div class="label">In progress</div>
        <div class="value">{{ $inPacking }}</div>
      </div>
      <div class="stat-card">
        <div class="label">In shipping</div>
        <div class="value">{{ $inShipping }}</div>
      </div>
      <div class="stat-card">
        <div class="label">Total fulfilled</div>
        <div class="value">{{ $totalFulfilled }}</div>
      </div>
    </div>

    <section class="content">

      <div class="panel order-queue">
        <div class="panel-header">
          <div class="title">📦 Order queue</div>
          <div class="actions">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input type="text" id="orderSearch" placeholder="Search..." autocomplete="off">
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
                <input type="radio" name="statusFilter" value="NEW" class="status-check">
                New
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="PACKING" class="status-check">
                Packing
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="READY_TO_SHIP" class="status-check">
                Ready for delivery
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="OUT_FOR_DELIVERY" class="status-check">
                Out for delivery
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="SHIPPED" class="status-check">
                Shipped
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="DELIVERED" class="status-check">
                Delivered
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="COMPLETE" class="status-check">
                Complete
              </label>
              <label class="filter-option">
                <input type="radio" name="statusFilter" value="CANCELLED" class="status-check">
                Cancelled
              </label>

              <div class="filter-title" style="margin-top:14px;">Priority</div>
              <label class="filter-option">
                <input type="radio" name="priorityFilter" value="" class="priority-check" checked>
                All
              </label>
              <label class="filter-option">
                <input type="radio" name="priorityFilter" value="LOW" class="priority-check">
                Low
              </label>
              <label class="filter-option">
                <input type="radio" name="priorityFilter" value="MEDIUM" class="priority-check">
                Medium
              </label>
              <label class="filter-option">
                <input type="radio" name="priorityFilter" value="HIGH" class="priority-check">
                High
              </label>
            </div>
          </div>
        </div>
        <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Order Id</th>
              <th>Customer</th>
              <th class="th-qty">Items</th>
              <th>Amount</th>
              <th class="th-status">Status</th>
              <th class="th-priority">Priority</th>
              <th>Due</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="ordersTableBody">
            @forelse($orders as $order)
            @php
            $priority = \Modules\OrderFulfillment\Helpers\OrderPriority::order($order->created_at);
            $statusRaw = strtoupper($order->status);
            $statusLabels = [
                'NEW'               => 'NEW',
                'PACKING'           => 'PACKING',
                'READY_TO_SHIP'     => 'READY FOR DELIVERY',
                'OUT_FOR_DELIVERY'  => 'OUT FOR DELIVERY',
                'SHIPPED'           => 'SHIPPED',
                'DELIVERED'         => 'DELIVERED',
                'COMPLETE'          => 'COMPLETE',
                'CANCELLED'         => 'CANCELLED',
                'RETURNED'          => 'RETURNED',
            ];
            $statusLabel = $statusLabels[$statusRaw] ?? strtoupper(str_replace('_', ' ', $statusRaw));
            $statusClassMap = [
                'NEW'               => 'status-new',
                'PACKING'           => 'status-packing',
                'READY_TO_SHIP'     => 'status-packing',
                'OUT_FOR_DELIVERY'  => 'status-transit',
                'SHIPPED'           => 'status-shipped',
                'DELIVERED'         => 'status-delivered',
                'COMPLETE'          => 'status-complete',
                'CANCELLED'         => 'status-cancelled',
                'RETURNED'          => 'status-returned',
            ];
            $statusClass = $statusClassMap[$statusRaw] ?? 'status-new';
            $orderQty = $order->items->count();
            $orderTotal = $order->items->sum(fn($item) => $item->qty * $item->product_amount);
            @endphp
            <tr class="order-row"
                style="cursor: pointer;"
                data-id="{{ $order->id }}"
                data-customer="{{ $order->customer_name }}"
                data-qty="{{ $orderQty }}"
                data-amount="{{ $orderTotal }}"
                data-status="{{ $statusRaw }}"
                data-priority="{{ $priority['label'] }}"
                data-priority-class="{{ $priority['class'] }}"
                data-due="{{ \Carbon\Carbon::parse($order->due_date)->format('M d') }}"
                data-tracking="{{ $order->shipment_tracking_number }}"
                data-courier="{{ $order->shipment_courier }}"
                data-address="{{ $order->shipment_address }}"
                {{-- Itemized breakdown for the order modal, sourced from the
                     order_items table (product_name, qty, product_amount). --}}
                data-items="{{ $order->items->map(fn($item) => [
                    'name'  => $item->product_name,
                    'qty'   => $item->qty,
                    'price' => $item->product_amount,
                ])->toJson() }}">
              <td class="order-id">{{ $order->id }}</td>
              <td class="customer">{{ $order->customer_name }}</td>
              <td class="qty-cell">{{ $orderQty }}</td>
              <td class="amount-cell">₱{{ number_format($orderTotal, 2) }}</td>
              <td class="status-cell"><span class="badge status {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td class="priority-cell">
              @if (!in_array($statusRaw, ['CANCELLED', 'RETURNED', 'DELIVERED', 'COMPLETE']))
              <span class="badge {{ $priority['class'] }}">
              {{ $priority['label'] }}
              </span>
              @endif
              </td>
              <td>{{ \Carbon\Carbon::parse($order->due_date)->format('M d') }}</td>
              <td>
                @if ($statusRaw === 'NEW')
                  <button type="button"
                          class="btn-prepare"
                          data-order-id="{{ $order->id }}"
                          onclick="event.stopPropagation(); prepareOrder('{{ $order->id }}', this)">
                    Process
                  </button>
                @endif
              </td>
            </tr>
            @empty
            <tr class="empty-row">
              <td colspan="8" style="text-align:center; padding:24px; color:#8b94b8;">No orders yet.</td>
            </tr>
            @endforelse

            {{-- Shown by JS when search/filter produce zero matches --}}
            <tr class="no-results-row" id="noResultsRow" style="display:none;">
              <td colspan="8">No orders match your search or filter.</td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <div class="panel activity">
        <div class="panel-header">
          <div class="title">📈 Recent activity</div>
        </div>
        <div class="activity-list">
          @forelse ($activity as $order)
            <div class="activity-item">
              <span class="activity-icon">{{ $order->activity_icon }}</span>
              <span>{{ $order->activity_message }}</span>
            </div>
          @empty
            <div class="activity-empty" style="display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:13px;">
              No recent activity.
            </div>
          @endforelse
        </div>
      </div>
    </section>

  </div>

  <div class="overlay" id="orderOverlay">
    <div class="modal">
      <div class="modal-header">
        <h2 id="modalOrderId">#ORD-4821</h2>
        <p id="modalSubtitle">Website order · 2 items</p>
      </div>
      <div class="modal-body-grid">
        <div>
          <p class="field-label">Customer</p>
          <p class="field-value" id="modalCustomer">Maria Santos</p>
        </div>
        <div>
          <p class="field-label">Status</p>
          <span class="badge status status-new" id="modalStatus">NEW</span>
        </div>
        <div id="modalTrackingField" style="display:none;">
          <p class="field-label">Tracing no.</p>
          <p class="field-value" id="modalTracking">—</p>
        </div>
        <div id="modalCourierField" style="display:none;">
          <p class="field-label">Courier</p>
          <p class="field-value" id="modalCourier">—</p>
        </div>
        <div>
          <p class="field-label">Due date</p>
          <p class="field-value" id="modalDue">Jun 25</p>
        </div>
        <div id="modalPriorityField">
          <p class="field-label">Priority</p>
          <span class="badge priority" id="modalPriority">Low</span>
        </div>
        <div id="modalAddressField" style="display:none;">
          <p class="field-label">Delivery Address</p>
          <p class="field-value" id="modalAddress">—</p>
        </div>
      </div>

      <div class="items-section">
        <p class="items-heading">Items in this order</p>
        <div class="items-list" id="modalItemsList">
          <!-- populated by JS -->
        </div>
        <div class="items-total">
          <span class="items-total-label" id="modalItemsTotalLabel">Total (0 items)</span>
          <span class="items-total-value" id="modalAmount">₱0.00</span>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-close" onclick="closeOrderModal()">Close</button>
        <button class="btn btn-cancel" id="cancelOrderBtn">Cancel order</button>
      </div>
    </div>
  </div>

  <div class="overlay cancel-modal-overlay" id="cancelOverlay">
    <div class="modal">
      <div class="modal-header">
        <h2 id="cancelOrderId">#ORD-4821</h2>
        <p id="cancelSubtitle">Website order · 2 items</p>
      </div>
      <div class="modal-body-grid">
        <div>
          <p class="field-label">Customer</p>
          <p class="field-value" id="cancelCustomer">Maria Santos</p>
        </div>
        <div>
          <p class="field-label">Status</p>
          <span class="badge status status-new" id="cancelStatus">NEW</span>
        </div>
        <div>
          <p class="field-label">Amount</p>
          <p class="field-value" id="cancelAmount">₱0.00</p>
        </div>
        <div>
          <p class="field-label">Due date</p>
          <p class="field-value" id="cancelDue">Jun 25</p>
        </div>
      </div>

      <div class="cancel-warning">
        <div class="warning-icon">⚠️</div>
        <p class="cancel-title">Cancel this order?</p>
        <p class="cancel-desc">
          This will cancel all <span id="cancelItemCount">0</span> items below and notify
          <span id="cancelCustomerName">this customer</span>. This action can't be undone.
        </p>
      </div>

      <div class="cancel-items-list" id="cancelItemsList">
        <!-- populated by JS -->
      </div>

      <div class="cancel-actions">
        <button class="btn-yes-full" id="yesCancelBtn">Yes, cancel order</button>
        <button class="btn-no-full" id="noKeepBtn">No, keep order</button>
      </div>
    </div>
  </div>

  <div class="filter-overlay" id="filterOverlay"></div>

  <div class="assign-toast" id="orderToast">Order moved to packing</div>

  <script>
    const STATUS_CLASSES = ['status-new', 'status-packing', 'status-transit', 'status-shipped', 'status-delivered', 'status-complete', 'status-cancelled', 'status-returned'];

    const STATUS_LABELS = {
      NEW: 'NEW',
      PACKING: 'PACKING',
      READY_TO_SHIP: 'READY FOR DELIVERY',
      OUT_FOR_DELIVERY: 'OUT FOR DELIVERY',
      SHIPPED: 'SHIPPED',
      DELIVERED: 'DELIVERED',
      COMPLETE: 'COMPLETE',
      CANCELLED: 'CANCELLED',
      RETURNED: 'RETURNED',
    };

    function statusToLabel(status) {
      return STATUS_LABELS[status] || String(status).replace(/_/g, ' ');
    }

    function statusToClass(status) {
      const map = {
        NEW: 'status-new',
        PACKING: 'status-packing',
        READY_TO_SHIP: 'status-packing',
        OUT_FOR_DELIVERY: 'status-transit',
        SHIPPED: 'status-shipped',
        DELIVERED: 'status-delivered',
        COMPLETE: 'status-complete',
        CANCELLED: 'status-cancelled',
        RETURNED: 'status-returned',
      };
      return map[status] || 'status-new';
    }

    function setStatusBadge(el, status) {
      if (!el) return;
      el.textContent = statusToLabel(status);
      el.classList.remove(...STATUS_CLASSES);
      el.classList.add(statusToClass(status));
    }

    const orderRows = Array.from(document.querySelectorAll('.order-row'));
    let currentOrderRow = null;

    orderRows.forEach(function (row) {
      row.addEventListener('click', function (e) {
        // If the click started on (or inside) a button — e.g. "Prepare" —
        // don't open the order modal, let the button's own handler run.
        if (e.target.closest('button')) return;
        openOrderModal(this.dataset, this);
      });
    });

    function renderItemRows(items) {
      return items.map(function (item) {
        const qty = Number(item.qty) || 0;
        const price = Number(item.price) || 0;
        const lineTotal = qty * price;
        return (
          '<div class="item-row">' +
            '<div>' +
              '<p class="item-name">' + item.name + '</p>' +
              '<p class="item-meta">Qty ' + qty + ' · ₱' + price.toFixed(2) + ' each</p>' +
            '</div>' +
            '<div class="item-line-total">₱' + lineTotal.toFixed(2) + '</div>' +
          '</div>'
        );
      }).join('');
    }

    function computeItemsTotal(items) {
      return items.reduce(function (sum, item) {
        return sum + ((Number(item.qty) || 0) * (Number(item.price) || 0));
      }, 0);
    }

    function itemLabel(count) {
      return count + (count === 1 ? ' item' : ' items');
    }

    function openOrderModal(data, rowEl) {
      currentOrderRow = rowEl;

      let items = [];
      try {
        items = data.items ? JSON.parse(data.items) : [];
      } catch (e) {
        console.error('Could not parse order items:', e);
      }

      document.getElementById('modalOrderId').textContent = data.id;
      document.getElementById('modalSubtitle').textContent = 'Website order · ' + itemLabel(items.length);
      document.getElementById('modalCustomer').textContent = data.customer;
      document.getElementById('modalDue').textContent = data.due;
      setStatusBadge(document.getElementById('modalStatus'), data.status);

      const priorityEl = document.getElementById('modalPriority');
      priorityEl.textContent = data.priority;
      priorityEl.className = 'badge ' + data.priorityClass;

      // Once an order has left packing and picked up a shipment
      // (READY_TO_SHIP through COMPLETE), show the same shipping details
      // as the Shipping tab's modal — tracing no., courier, and delivery
      // address — in place of the Priority field, which no longer applies
      // to an order that's already shipping.
      const shippingStatuses = ['READY_TO_SHIP', 'OUT_FOR_DELIVERY', 'SHIPPED', 'DELAYED', 'DELIVERED', 'COMPLETE'];
      const hasShipment = shippingStatuses.includes(data.status);

      document.getElementById('modalPriorityField').style.display = hasShipment ? 'none' : '';
      document.getElementById('modalTrackingField').style.display = hasShipment ? '' : 'none';
      document.getElementById('modalCourierField').style.display = hasShipment ? '' : 'none';
      document.getElementById('modalAddressField').style.display = hasShipment ? '' : 'none';

      if (hasShipment) {
        document.getElementById('modalTracking').textContent = data.tracking || '—';
        document.getElementById('modalCourier').textContent = data.courier || '—';
        document.getElementById('modalAddress').textContent = data.address || '—';
      }

      document.getElementById('modalItemsList').innerHTML = renderItemRows(items);
      document.getElementById('modalItemsTotalLabel').textContent = 'Total (' + itemLabel(items.length) + ')';
      document.getElementById('modalAmount').textContent = '₱' + computeItemsTotal(items).toFixed(2);

      const cancelBtn = document.getElementById('cancelOrderBtn');
      const alreadyCancelled = data.status === 'CANCELLED';
      cancelBtn.classList.toggle('disabled', alreadyCancelled);
      document.getElementById('cancelOverlay').classList.remove('active');

      document.getElementById('pageContent').classList.add('blurred');
      document.getElementById('orderOverlay').classList.add('active');
    }

    function closeOrderModal() {
      document.getElementById('pageContent').classList.remove('blurred');
      document.getElementById('orderOverlay').classList.remove('active');
      document.getElementById('cancelOverlay').classList.remove('active');
    }

    const prepareUrlTemplate = @json(route('order-fulfillment.orders.prepare', ['id' => '__ID__']));

    function showOrderToast(message, isError = false) {
      const toast = document.getElementById('orderToast');
      toast.textContent = message;
      toast.style.background = isError ? '#ef4444' : '#22c55e';
      toast.style.color = isError ? '#ffffff' : '#08240f';
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2600);
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

    if (!csrfMeta) {
      console.error('CSRF meta tag not found. Add <meta name="csrf-token" content="{{ csrf_token() }}"> inside <head>. The Prepare button will not work without it.');
    }

    function prepareOrder(orderId, btn) {
      console.log('prepareOrder called for order', orderId);

      if (btn.disabled) return;

      if (!csrfToken) {
        alert('Missing CSRF token on this page — check the browser console for details.');
        return;
      }

      btn.disabled = true;
      const originalText = btn.textContent;
      btn.textContent = 'Moving...';

      const url = prepareUrlTemplate.replace('__ID__', encodeURIComponent(orderId));
      console.log('prepareOrder POSTing to', url);

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
      })
        .then(function (res) {
          console.log('prepareOrder response status:', res.status);
          if (!res.ok) {
            return res.json().catch(function () { return {}; }).then(function (body) {
              throw new Error(body.message || ('Request failed with status ' + res.status));
            });
          }
          return res.json();
        })
        .then(function (data) {
          if (!data.success) throw new Error(data.message || 'Update failed');

          const row = btn.closest('.order-row');
          row.dataset.status = 'PACKING';

          setStatusBadge(row.querySelector('.badge.status'), 'PACKING');

          // Order has moved past NEW — no action button needed anymore.
          btn.remove();

          // If the floating window is currently open for this same order,
          // keep it in sync with the new status.
          if (currentOrderRow === row) {
            setStatusBadge(document.getElementById('modalStatus'), 'PACKING');
          }

          showOrderToast(`Order ${orderId} moved to packing`);
        })
        .catch(function (err) {
          console.error('prepareOrder failed:', err);
          alert('Could not move this order to packing: ' + err.message);
          showOrderToast(`Could not move order ${orderId} to packing`, true);
          btn.disabled = false;
          btn.textContent = originalText;
        });
    }
    /* =================== end Prepare -> Packing =================== */
    document.getElementById('cancelOrderBtn').addEventListener('click', function () {
      if (this.classList.contains('disabled') || !currentOrderRow) return;

      const data = currentOrderRow.dataset;
      let items = [];
      try {
        items = data.items ? JSON.parse(data.items) : [];
      } catch (e) {
        console.error('Could not parse order items:', e);
      }

      document.getElementById('cancelOrderId').textContent = data.id;
      document.getElementById('cancelSubtitle').textContent = 'Website order · ' + itemLabel(items.length);
      document.getElementById('cancelCustomer').textContent = data.customer;
      document.getElementById('cancelAmount').textContent = '₱' + computeItemsTotal(items).toFixed(2);
      document.getElementById('cancelDue').textContent = data.due;
      setStatusBadge(document.getElementById('cancelStatus'), data.status);

      document.getElementById('cancelItemCount').textContent = items.length;
      document.getElementById('cancelCustomerName').textContent = data.customer;

      document.getElementById('cancelItemsList').innerHTML = items.map(function (item) {
        const qty = Number(item.qty) || 0;
        const price = Number(item.price) || 0;
        const lineTotal = qty * price;
        return (
          '<div class="cancel-item-row">' +
            '<span class="cancel-item-name">' + item.name + ' × ' + qty + '</span>' +
            '<span class="cancel-item-amount">₱' + lineTotal.toFixed(2) + '</span>' +
          '</div>'
        );
      }).join('');

      document.getElementById('cancelOverlay').classList.add('active');
    });

    document.getElementById('noKeepBtn').addEventListener('click', function () {
      document.getElementById('cancelOverlay').classList.remove('active');
    });

    const cancelUrlTemplate = @json(route('order-fulfillment.orders.cancel', ['id' => '__ID__']));

    document.getElementById('yesCancelBtn').addEventListener('click', function () {
      if (!currentOrderRow) return;

      const yesBtn  = this;
      const orderId = currentOrderRow.dataset.id;

      if (!csrfToken) {
        alert('Missing CSRF token on this page — check the browser console for details.');
        return;
      }

      yesBtn.disabled = true;
      const originalText = yesBtn.textContent;
      yesBtn.textContent = 'Cancelling...';

      const url = cancelUrlTemplate.replace('__ID__', encodeURIComponent(orderId));

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
      })
        .then(function (res) {
          if (!res.ok) {
            return res.json().catch(function () { return {}; }).then(function (body) {
              throw new Error(body.message || ('Request failed with status ' + res.status));
            });
          }
          return res.json();
        })
        .then(function (data) {
          if (!data.success) throw new Error(data.message || 'Cancel failed');

          // ---- Update order modal ----
          setStatusBadge(document.getElementById('modalStatus'), 'CANCELLED');
          const priorityEl = document.getElementById('modalPriority');
          priorityEl.textContent = '—';
          priorityEl.className = 'badge';
          document.getElementById('cancelOrderBtn').classList.add('disabled');
          document.getElementById('cancelOverlay').classList.remove('active');

          // ---- Update the row ----
          currentOrderRow.dataset.status = 'CANCELLED';
          currentOrderRow.dataset.priority = 'CANCELLED';

          setStatusBadge(currentOrderRow.querySelector('.badge.status'), 'CANCELLED');

          // Priority badge disappears entirely for cancelled orders.
          const rowPriorityBadge = currentOrderRow.querySelector('td .badge:not(.status)');
          if (rowPriorityBadge) rowPriorityBadge.remove();

          // Prepare button (if the order was still NEW) disappears too.
          const prepareBtn = currentOrderRow.querySelector('.btn-prepare');
          if (prepareBtn) prepareBtn.remove();

          yesBtn.disabled = false;
          yesBtn.textContent = originalText;

          showOrderToast(`Order ${orderId} has been cancelled`);

          setTimeout(closeOrderModal, 500);
        })
        .catch(function (err) {
          console.error('cancelOrder failed:', err);
          alert('Could not cancel this order: ' + err.message);
          showOrderToast(`Could not cancel order ${orderId}`, true);
          yesBtn.disabled = false;
          yesBtn.textContent = originalText;
        });
    });

    /* ===================== Search + Filter (working) ===================== */
    const searchInput   = document.getElementById('orderSearch');
    const filterBtn      = document.getElementById('filterBtn');
    const filterPanel    = document.getElementById('filterPanel');
    const filterOverlay  = document.getElementById('filterOverlay');
    const filterBadge    = document.getElementById('filterBadge');
    const noResultsRow   = document.getElementById('noResultsRow');
    const priorityChecks = document.querySelectorAll('.priority-check');
    const statusChecks   = document.querySelectorAll('.status-check');

    function activePriority() {
      const checked = Array.from(priorityChecks).find(c => c.checked);
      return checked ? checked.value : '';
    }

    function activeStatus() {
      const checked = Array.from(statusChecks).find(c => c.checked);
      return checked ? checked.value : '';
    }

    function applyOrderFilters() {
      const query = searchInput.value.trim().toLowerCase();
      const activePr = activePriority();
      const activeSt = activeStatus();
      let visibleCount = 0;

      orderRows.forEach(function (row) {
        const d = row.dataset;
        const haystack = [d.id, d.customer, d.product, d.status, d.due]
          .join(' ')
          .toLowerCase();

        const matchesSearch = query === '' || haystack.includes(query);
        const matchesPriority = activePr === '' || d.priority === activePr;
        const matchesStatus = activeSt === '' || (d.status || '').toUpperCase() === activeSt;
        const visible = matchesSearch && matchesPriority && matchesStatus;

        row.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
      });

      noResultsRow.style.display = visibleCount === 0 ? '' : 'none';

      const activeFilterCount = (activePr !== '' ? 1 : 0) + (activeSt !== '' ? 1 : 0);

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
    priorityChecks.forEach(c => c.addEventListener('change', applyOrderFilters));
    statusChecks.forEach(c => c.addEventListener('change', applyOrderFilters));
    searchInput.addEventListener('input', applyOrderFilters);
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
