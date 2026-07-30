@extends('inventory::layouts.dashboard')

@section('title', 'Dashboard')

@push('head')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
@endpush

@push('styles')
<style>
    .pbar { background: rgba(0,0,0,0.15); border-radius: 99px; height: 6px; overflow: hidden; }
    .pbar-inner { height: 100%; border-radius: 99px; }
    tr.trow { border-bottom: 1px solid rgba(255,255,255,0.06); cursor: default; }
    tr.trow:last-child { border-bottom: none; }
    tr.trow:hover { background: rgba(255,255,255,0.04); }
    .alert-restock-link { display:inline-flex; align-items:center; margin-top:10px; font-size:11px; font-weight:700; color:#1b6fc8; text-decoration:none; }
    .alert-restock-link:hover { text-decoration:underline; }

    /* Fade-in transition when landing on dashboard after login */
    .responsive-grid-dashboard {
        animation: dashboardFadeIn 0.6s ease-out;
    }
    @keyframes dashboardFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="inv-page">
@if(($pendingApprovalsCount ?? 0) > 0 || ($pendingDeliveriesCount ?? 0) > 0)
    <div style="background: rgba(19, 43, 82, 0.85); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 12px; padding: 12px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); display: flex; align-items: center; justify-content: center; color: #f59e0b; flex-shrink: 0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div>
                <h4 style="font-size: 14px; font-weight: 700; color: #ffffff; margin: 0; line-height: 1.2;">
                    {{ ($pendingApprovalsCount ?? 0) + ($pendingDeliveriesCount ?? 0) }} pending approvals
                </h4>
                <p style="font-size: 12px; color: #9bb0d1; margin: 2px 0 0 0;">
                    Waiting for review across Adjustments, Transfers, and Receiving
                </p>
            </div>
        </div>
        @if(($pendingDeliveriesCount ?? 0) > 0)
            <a href="{{ route('inventory.stock-receiving') }}" style="background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; border-radius: 20px; padding: 6px 14px; font-size: 12px; font-weight: 600; text-decoration: none; transition: all 0.15s ease;" onmouseover="this.style.background='rgba(245,158,11,0.3)'" onmouseout="this.style.background='rgba(245,158,11,0.2)'">
                {{ $pendingDeliveriesCount }} receivings
            </a>
        @elseif(($pendingApprovalsCount ?? 0) > 0)
            <a href="{{ route('inventory.requests') }}" style="background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; border-radius: 20px; padding: 6px 14px; font-size: 12px; font-weight: 600; text-decoration: none; transition: all 0.15s ease;" onmouseover="this.style.background='rgba(245,158,11,0.3)'" onmouseout="this.style.background='rgba(245,158,11,0.2)'">
                {{ $pendingApprovalsCount }} requests
            </a>
        @endif
    </div>
@endif
<div class="responsive-grid-dashboard">
    <!-- Row 1: 3 stat cards + Critical Alerts -->
    <div class="kpi-tile" style="--accent:#4a9ee8;align-self:start;">
        <div class="kpi-head">
            <span class="kpi-label">Total Items</span>
            <span class="kpi-icon" style="background:rgba(74,158,232,0.15);color:#4a9ee8;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05"/><path d="M12 22.08V12"/></svg>
            </span>
        </div>
        <p class="kpi-value">{{ number_format($totalItems) }}</p>
    </div>
    <div class="kpi-tile" style="--accent:#2dd4a8;align-self:start;">
        <div class="kpi-head">
            <span class="kpi-label">Total Stock Unit</span>
            <span class="kpi-icon" style="background:rgba(45,212,168,0.15);color:#2dd4a8;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l9 5-9 5-9-5 9-5z"/><path d="M3 12l9 5 9-5"/><path d="M3 17l9 5 9-5"/></svg>
            </span>
        </div>
        <p class="kpi-value">{{ number_format($totalStockUnits) }}</p>
    </div>
    <div class="kpi-tile" style="--accent:#f59e0b;align-self:start;">
        <div class="kpi-head">
            <span class="kpi-label">Low Stock Alerts</span>
            <span class="kpi-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4m0 4h.01"/></svg>
            </span>
        </div>
        <p class="kpi-value">{{ number_format($lowStockAlerts) }}</p>
    </div>
    <!-- Critical Alerts card: spans 2 rows -->
    <div class="stat-card" style="grid-row: span 2; display:flex; flex-direction:column; gap:12px; overflow-y:auto; height:100%;">
        <p style="font-size:15px; white-space: nowrap; margin-bottom:4px;">Critical Alerts</p>

        @forelse ($criticalAlerts as $alert)
            <div style="background:#ffffff;border-radius:16px;padding:16px 18px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                    <div style="min-width:0;flex:1;">
                        <p style="font-size:15px;font-weight:700;color:#0b1e3d;margin:0 0 3px 0;line-height:1.3;">{{ $alert['name'] }}</p>
                        <p style="font-size:12px;color:#64748b;margin:0;">{{ $alert['warehouse'] }}</p>
                    </div>
                    <span style="font-size:11px;font-weight:700;padding:3px 12px;border-radius:99px;flex-shrink:0;margin-left:10px;{{ $alert['type'] === 'out_of_stock' ? 'background:#fee2e2;color:#dc2626;' : 'background:#fef3c7;color:#d97706;' }}">{{ $alert['type'] === 'out_of_stock' ? 'OUT OF STOCK' : 'LOW STOCK' }}</span>
                </div>
                @php
                    $onHand = $alert['on_hand'];
                    $threshold = $alert['threshold'];
                    if ($alert['type'] === 'out_of_stock') {
                        $percentage = 0;
                    } elseif ($threshold > 0 && $onHand > 0) {
                        $percentage = min(100, ($onHand / ($threshold * 2)) * 100);
                    } else {
                        $percentage = 0;
                    }
                    $hue = min(120, round($percentage * 1.2));
                @endphp
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:5px;">
                    <span style="font-size:13px;font-weight:700;color:#0b1e3d;">{{ number_format($onHand) }}</span>
                    <span style="font-size:11px;color:#64748b;">units available</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="pbar" style="flex:1;"><div class="pbar-inner" style="width:{{ $percentage }}%;background:hsl({{ $hue }}, 80%, 45%);"></div></div>
                    <span style="font-size:11px;color:#64748b;white-space:nowrap;flex-shrink:0;">threshold {{ number_format($threshold) }}</span>
                </div>
                @if(!empty($alert['item_id']))
                    <a href="{{ route('inventory.requests', ['item' => $alert['item_id']]) }}" class="alert-restock-link">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Request stock
                    </a>
                @endif
            </div>
        @empty
            <p style="font-size:13px;color:#94a3b8;">No active alerts.</p>
        @endforelse
    </div>
    <!-- Row 2: Chart spanning 2 columns (beside Critical Alerts) -->
    <div class="stat-card span-2">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <p style="font-size:15px;font-weight:600;">Stock Movement Trend</p>
            <select id="trendPeriod" style="background: #1b3a6b; color: #e2e8f0; border: 1px solid #2d4a7a; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-family: 'Inter', sans-serif; cursor: pointer; outline: none;">
                <option value="this_week">This week</option>
                <option value="last_week">Last week</option>
                <option value="this_month">This month</option>
                <option value="last_month">Last month</option>
            </select>
        </div>
        <div style="display:flex;align-items:center;gap:18px;margin-bottom:14px;justify-content:flex-end;">
            <span id="legend-inbound" style="display:flex;align-items:center;gap:7px;font-size:12px;color:#e2e8f0;font-weight:500;cursor:pointer;transition:opacity 0.2s;">
                <span style="width:12px;height:12px;border-radius:50%;background:#22c55e;flex-shrink:0;"></span>Inbound
            </span>
            <span id="legend-outbound" style="display:flex;align-items:center;gap:7px;font-size:12px;color:#e2e8f0;font-weight:500;cursor:pointer;transition:opacity 0.2s;">
                <span style="width:12px;height:12px;border-radius:50%;background:#ef4444;flex-shrink:0;"></span>Outbound
            </span>
        </div>
        <div class="table-wrapper">
            <div class="chart-container">
                <canvas id="stockMovementChart"></canvas>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <p style="font-size:14px;font-weight:600;margin-bottom:15px; white-space: nowrap;">Warehouse Distribution</p>
        <div class="chart-container">
            <canvas id="warehouseChart"></canvas>
        </div>
    </div>

    <div class="content-card span-4">
        <p class="section-heading">Recent Stock Movement</p>
        <div class="table-wrapper">
            <table class="data-grid" style="width: 100%; table-layout: fixed;">
                <thead>
                    <tr>
                        <th>TYPE</th>
                        <th>ITEM NAME</th>
                        <th class="col-r">QUANTITY</th>
                        <th>WAREHOUSE</th>
                        <th>REFERENCE</th>
                        <th class="col-r">DATE &amp; TIME</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentMovements as $movement)
                        <tr class="trow">
                            <td style="text-align: center; padding: 12px 8px; color: #000000; font-size:13px;">
                                @if ($movement['type'] === 'inbound')
                                    <span style="display:inline-block;padding:4px 16px;border-radius:14px;background:#F0FFF5;color:#0CAE57;border:1px solid rgba(12,174,87,0.5);font-size:12px;font-weight:500;">Inbound</span>
                                @elseif ($movement['type'] === 'outbound')
                                    <span style="display:inline-block;padding:4px 16px;border-radius:14px;background:#FFF5F5;color:#DC2626;border:1px solid rgba(220,38,38,0.5);font-size:12px;font-weight:500;">Outbound</span>
                                @elseif ($movement['type'] === 'reservation')
                                    <span style="display:inline-block;padding:4px 16px;border-radius:14px;background:#F5F3FF;color:#7C3AED;border:1px solid rgba(124,58,237,0.5);font-size:12px;font-weight:500;">Reservation</span>
                                @else
                                    <span style="display:inline-block;padding:4px 16px;border-radius:14px;background:#E2E8F0;color:#64748B;border:1px solid rgba(100,116,139,0.5);font-size:12px;font-weight:500;">Other</span>
                                @endif
                            </td>
                            <td style="text-align: center; padding: 12px 8px; color: #000000; font-size:13px;">{{ $movement['item_name'] }}</td>
                            <td style="text-align: center; padding: 12px 8px; color: #000000; font-size:13px; font-weight:600;">
                                @if ($movement['type'] === 'inbound')
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#0CAE57;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
                                        {{ number_format($movement['quantity']) }}
                                    </span>
                                @elseif ($movement['type'] === 'outbound')
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#DC2626;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
                                        {{ number_format(abs($movement['quantity'])) }}
                                    </span>
                                @elseif ($movement['type'] === 'reservation')
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#7C3AED;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                        {{ number_format(abs($movement['quantity'])) }}
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;color:#64748B;">
                                        {{ number_format($movement['quantity']) }}
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center; padding: 12px 8px; color: #000000; font-size:13px;">{{ $movement['warehouse'] }}</td>
                            <td style="text-align: center; padding: 12px 8px; color: #000000; font-size:13px;">{{ $movement['reference'] }}</td>
                            <td style="text-align: center; padding: 12px 8px; color: #000000; font-size:13px;">{{ $movement['date'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #64748b; font-size:13px;">No recent stock movements.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    Chart.defaults.plugins.datalabels = { display: false };

    const ctx = document.getElementById('stockMovementChart').getContext('2d');
    let trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($trendData['labels']),
            datasets: [
                {
                    label: 'Inbound',
                    data: @json($trendData['inbound']),
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.12)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#22c55e',
                    pointBorderColor: '#22c55e',
                    pointRadius: 4,
                    pointHoverRadius: 9,
                    pointHoverBorderWidth: 3,
                    pointHoverBorderColor: '#ffffff',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Outbound',
                    data: @json($trendData['outbound']),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.12)',
                    borderWidth: 2.5,
                    borderDash: [6, 4],
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#ef4444',
                    pointRadius: 4,
                    pointHoverRadius: 9,
                    pointHoverBorderWidth: 3,
                    pointHoverBorderColor: '#ffffff',
                    fill: true,
                    tension: 0.4,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false
                },
                datalabels: { display: false },
                tooltip: {
                    backgroundColor: '#0f2447',
                    titleColor: '#cdd9ee',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    cornerRadius: 10,
                    padding: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                    caretSize: 6,
                }
            },
            scales: {
                x: {
                    ticks: { color: '#94a3b8' },
                    grid: { color: 'rgba(255,255,255,0.06)' }
                },
                y: {
                    beginAtZero: true,
                    min: 0,
                    title: {
                        display: true,
                        text: 'Units Moved',
                        color: '#e2e8f0',
                        font: { size: 12, weight: 600 }
                    },
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            if (Number.isInteger(value)) {
                                return value;
                            }
                        }
                    },
                    grid: { color: 'rgba(255,255,255,0.06)' }
                }
            }
        }
    });

    document.getElementById('trendPeriod').addEventListener('change', function() {
        fetch('{{ route("inventory.index.trend-data") }}?period=' + this.value)
            .then(r => r.json())
            .then(data => {
                trendChart.data.labels = data.labels;
                trendChart.data.datasets[0].data = data.inbound;
                trendChart.data.datasets[1].data = data.outbound;
                trendChart.update();
            });
    });

    function toggleDataset(index, legendId) {
        const meta = trendChart.getDatasetMeta(index);
        const other = trendChart.getDatasetMeta(index === 0 ? 1 : 0);
        if (!meta.hidden && other.hidden) return;
        meta.hidden = !meta.hidden;
        trendChart.update();
        const el = document.getElementById(legendId);
        el.style.opacity = meta.hidden ? '0.4' : '1';
    }

    document.getElementById('legend-inbound').addEventListener('click', function() {
        toggleDataset(0, 'legend-inbound');
    });
    document.getElementById('legend-outbound').addEventListener('click', function() {
        toggleDataset(1, 'legend-outbound');
    });

    const warehouseCtx = document.getElementById('warehouseChart').getContext('2d');
    new Chart(warehouseCtx, {
        type: 'pie',
        data: {
            labels: @json($warehouseDistribution->pluck('name')),
            datasets: [{
                data: @json($warehouseDistribution->pluck('total')),
                backgroundColor: ['#4a9ee8', '#2dd4a8', '#f0a93e', '#ef4444', '#8b5cf6', '#ec4899'],
                borderColor: '#0f2447',
                borderWidth: 3,
                hoverOffset: 12,
                hoverBorderColor: '#ffffff',
                hoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 12, bottom: 8, left: 8, right: 8 } },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#e2e8f0', font: { size: 12 }, padding: 12, usePointStyle: true, pointStyle: 'circle' }
                },
                datalabels: {
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] > 0;
                    },
                    color: '#ffffff',
                    font: { weight: 'bold', size: 11 },
                    anchor: 'end',
                    align: 'start',
                    offset: -6,
                    formatter: function(value, ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = ((value / total) * 100).toFixed(1);
                        return pct + '%';
                    }
                },
                tooltip: {
                    backgroundColor: '#0f2447',
                    titleColor: '#cdd9ee',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    cornerRadius: 10,
                    padding: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((context.parsed / total) * 100).toFixed(1);
                            return ' ' + context.label + ': ' + context.parsed + ' units (' + pct + '%)';
                        }
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
</script>
@endpush
