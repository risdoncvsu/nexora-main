// dashboard-charts.js
// Renders the weekly builds bar chart on the Dashboard.
function renderDashboardChart(payload) {
    const ctx = document.getElementById('dashWeekChart');
    if (!ctx || !window.Chart) return;

    const days = payload?.days || window.dashboardData?.days || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const weekCounts = payload?.weekCounts || window.dashboardData?.weekCounts || Array(7).fill(0);

    if (window.dashboardChartInstance) {
        window.dashboardChartInstance.data.labels = days;
        window.dashboardChartInstance.data.datasets[0].data = weekCounts;
        window.dashboardChartInstance.update();
        return;
    }

    window.dashboardChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                data: weekCounts,
                backgroundColor: '#1B6FC8',
                borderRadius: 4,
                borderSkipped: 'bottom',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => c.parsed.y + ' builds' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#5B7A9D', font: { size: 11 } }, border: { color: '#1B3A6B' } },
                y: { grid: { display: true, color: '#869FB1' }, ticks: { color: '#5B7A9D', font: { size: 11 }, stepSize: 2 }, border: { display: false }, min: 0 }
            }
        }
    });
}

function initDashboardChart() {
    if (!window.dashboardData || !window.Chart) return;

    renderDashboardChart(window.dashboardData);

    if (window.dashboardChartEndpoint) {
        fetch(window.dashboardChartEndpoint, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.ok ? response.json() : null)
            .then(payload => {
                if (payload?.days && Array.isArray(payload.weekCounts)) {
                    window.dashboardData = payload;
                    renderDashboardChart(payload);
                }
            })
            .catch(() => {});
    }
}

if (window.Chart) {
    initDashboardChart();
} else {
    window.addEventListener('load', initDashboardChart);
}
