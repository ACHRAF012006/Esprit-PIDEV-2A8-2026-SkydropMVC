/*
 * S1 - Admin overview charts (Chart.js, already loaded by the back-office layout).
 * The data comes from SQL through the controller: data-labels and data-values (JSON) on each <canvas>. No inline script.
 */
(function () {
    if (typeof window.Chart === 'undefined') {
        return;
    }
    function read(canvas) {
        try {
            return { labels: JSON.parse(canvas.dataset.labels || '[]'), values: JSON.parse(canvas.dataset.values || '[]') };
        } catch (e) {
            return { labels: [], values: [] };
        }
    }
    function colors() {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark');
        return { navy: dark ? '#8fb3d9' : '#03213d', muted: dark ? '#a8a29e' : '#57534e', grid: dark ? 'rgba(255,255,255,0.08)' : '#e7e5e4', grey: dark ? '#44403c' : '#d6d3d1' };
    }
    var charts = [];
    function draw() {
        charts.forEach(function (c) { c.destroy(); });
        charts = [];
        var c = colors();
        var weeks = document.getElementById('chartNewAccounts');
        if (weeks) {
            var w = read(weeks);
            charts.push(new window.Chart(weeks, {
                type: 'bar',
                data: { labels: w.labels, datasets: [{ label: 'New accounts', data: w.values, backgroundColor: c.navy, borderRadius: 4, maxBarThickness: 32 }] },
                options: {
                    maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.muted } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: c.muted }, grid: { color: c.grid } }
                    }
                }
            }));
        }
        var status = document.getElementById('chartStatus');
        if (status) {
            var s = read(status);
            charts.push(new window.Chart(status, {
                type: 'doughnut',
                data: { labels: s.labels, datasets: [{ data: s.values, backgroundColor: [c.navy, c.grey], borderWidth: 0 }] },
                options: { maintainAspectRatio: false, animation: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: c.muted } } } }
            }));
        }
    }
    draw();
    // Redraw with the right colours when the theme changes.
    new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class'] });
}());
