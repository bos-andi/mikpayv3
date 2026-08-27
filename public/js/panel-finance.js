document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('chartFinanceTrend');
    const trend = window.__financeTrend || [];
    if (!canvas || typeof Chart === 'undefined' || !trend.length) {
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const grid = isDark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.25)';
    const text = isDark ? '#94a3b8' : '#64748b';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: trend.map((r) => r.label),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: trend.map((r) => r.income),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderRadius: 6,
                    maxBarThickness: 18,
                },
                {
                    label: 'Pengeluaran',
                    data: trend.map((r) => r.expense),
                    backgroundColor: 'rgba(244, 63, 94, 0.7)',
                    borderRadius: 6,
                    maxBarThickness: 18,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: text, boxWidth: 12, font: { size: 11 } },
                },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const n = Number(ctx.raw || 0);
                            return `${ctx.dataset.label}: Rp ${n.toLocaleString('id-ID')}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: text, maxRotation: 45, minRotation: 0, font: { size: 10 } },
                    grid: { display: false },
                },
                y: {
                    ticks: {
                        color: text,
                        font: { size: 10 },
                        callback(v) {
                            if (v >= 1e6) return (v / 1e6).toFixed(1) + 'jt';
                            if (v >= 1e3) return (v / 1e3).toFixed(0) + 'rb';
                            return v;
                        },
                    },
                    grid: { color: grid },
                },
            },
        },
    });
});
