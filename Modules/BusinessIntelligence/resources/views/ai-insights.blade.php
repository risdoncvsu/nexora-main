@extends('bi::layouts.app')

@section('content')
    <div id="ai-insights-view" class="tab-content active-tab" style="display:block;">
        <div class="subheader-bar">
            <div class="subheader-title">
                <h3>AI Insights Center</h3>
                <p>AI-generated business insights, recommendations, and alerts.</p>
            </div>
            <div class="subheader-controls">
                <div class="control-date-selector">
                    <i data-lucide="calendar" class="control-icon-sm"></i>
                    {{ now()->format('M d') }} - {{ now()->addDays(7)->format('M d, Y') }}
                </div>
            </div>
        </div>
        <div class="content-container">

            <div class="ui-card">
                <div class="card-header">
                    <div class="card-title">Executive KPI Overview <span class="info-dot" data-tooltip="High-level metrics derived from all connected ERP modules.">i</span></div>
                </div>
                <div class="bi-kpi-overview-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));">
                    @foreach($kpiOverview as $kpi)
                        <div class="kpi-card" style="flex-direction:column;align-items:flex-start;gap:.5rem;">
                            <div class="kpi-icon-container"><i data-lucide="{{ $kpi['icon'] }}" class="kpi-icon"></i></div>
                            <div class="kpi-details" style="flex:none;width:100%;">
                                <div class="kpi-label">{{ $kpi['label'] }}</div>
                                <div class="kpi-value" style="font-size:20px;margin:4px 0 2px;">{{ $kpi['value'] }}</div>
                                <div class="kpi-change {{ $kpi['change_class'] }}" style="font-size:10px;">{{ $kpi['change'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ai-insights-grid">
                {{-- Executive Summary --}}
                <div class="insight-card">
                    <h3>Executive Summary <span class="info-dot"
                            data-tooltip="Overview of the most critical business metrics and performance indicators across all modules.">i</span>
                    </h3>
                    <div class="card-subtitle">{{ empty($executiveSummary) ? 'No data available' : 'Metric-driven analysis' }}
                    </div>
                    <div class="insight-list">
                        @forelse($executiveSummary as $item)
                            <div class="insight-item">
                                <div class="insight-icon-circle bg-icon-{{ $item['color'] }}">
                                    <i data-lucide="{{ $item['icon'] }}" class="insight-icon-sm"></i>
                                </div>
                                <div class="insight-text-wrapper">
                                    <p>{{ $item['text'] }}</p>
                                    @if(!empty($item['sub_text']))
                                        <div class="sub-text">{{ $item['sub_text'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="insight-item">
                                <div class="insight-text-wrapper">
                                    <p style="color: var(--slate-500);">Insights will appear here once connected to data
                                        sources.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Top Recommendations --}}
                <div class="insight-card">
                    <h3>Top Recommendations <span class="info-dot"
                            data-tooltip="Prioritized actionable recommendations generated from your live metrics.">i</span></h3>
                    <div class="card-subtitle">&nbsp;</div>
                    <div class="insight-list">
                        @forelse($recommendations as $index => $rec)
                            <div class="insight-item">
                                <div class="insight-icon-circle bg-icon-num">{{ $index + 1 }}</div>
                                <div class="insight-text-wrapper">
                                    <p><strong>{{ $rec['title'] }}</strong></p>
                                    <div class="sub-text">{{ $rec['description'] }}</div>
                                </div>
                                <span class="mock-badge mb-{{ strtolower($rec['impact']) }}-impact">{{ $rec['impact'] }}
                                    Impact</span>
                            </div>
                        @empty
                            <div class="insight-item">
                                <div class="insight-text-wrapper">
                                    <p style="color: var(--slate-500);">No recommendations right now — all tracked metrics look
                                        healthy.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Risk Detection --}}
                <div class="insight-card">
                    <h3>Risk Detection <span class="info-dot"
                            data-tooltip="Automated risk monitoring across supply chain, operations, and financial domains.">i</span>
                    </h3>
                    <div class="card-subtitle">&nbsp;</div>
                    <div class="insight-list">
                        @forelse($risks as $risk)
                            <div class="insight-item">
                                <div class="insight-icon-circle bg-icon-{{ $risk['color'] }}">
                                    <i data-lucide="{{ $risk['icon'] }}" class="insight-icon-sm"></i>
                                </div>
                                <div class="insight-text-wrapper">
                                    <p><strong>{{ $risk['title'] }}</strong></p>
                                    <div class="sub-text">{{ $risk['description'] }}</div>
                                </div>
                                <span class="mock-badge mb-{{ strtolower($risk['level']) }}">{{ $risk['level'] }}</span>
                            </div>
                        @empty
                            <div class="insight-item">
                                <div class="insight-text-wrapper">
                                    <p style="color: var(--slate-500);">No risks detected across the tracked metrics.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="insight-card">
                    <h3>Business Health Score <span class="info-dot" data-tooltip="Overall business performance score computed from live data across all ERP modules.">i</span></h3>
                    <div class="card-subtitle">Live cross-module assessment</div>
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                        <div class="op-donut op-donut-lg">
                            <svg viewBox="0 0 36 36" class="op-donut-svg" aria-label="Business health {{ $businessHealth['score'] }} percent">
                                <path class="op-donut-track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="op-donut-fill {{ $businessHealth['score'] >= 80 ? 'health-green' : ($businessHealth['score'] >= 60 ? 'health-yellow' : ($businessHealth['score'] >= 40 ? 'health-orange' : 'health-red')) }}" stroke-dasharray="{{ $businessHealth['score'] }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="op-donut-text op-donut-text-lg">{{ $businessHealth['score'] }}%</span>
                        </div>
                        <p style="font-size:11px;color:var(--slate-500);line-height:1.5;">{{ $businessHealth['explanation'] }}</p>
                    </div>
                    <div class="insight-list">
                        @foreach($businessHealth['factors'] as $factor)
                            <div class="insight-item">
                                <div class="insight-icon-circle bg-icon-{{ $factor['status'] === 'positive' ? 'green' : ($factor['status'] === 'warning' ? 'orange' : 'red') }}"><i data-lucide="{{ $factor['status'] === 'positive' ? 'trending-up' : 'trending-down' }}" class="insight-icon-sm"></i></div>
                                <div class="insight-text-wrapper"><p><strong>{{ $factor['label'] }}</strong></p><div class="sub-text">{{ $factor['detail'] }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
