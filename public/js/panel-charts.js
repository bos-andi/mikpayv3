document.addEventListener('DOMContentLoaded', () => {
    const bandwidthCanvas = document.getElementById('chartBandwidth');
    const billingCanvas = document.getElementById('chartBillingStatus');
    if (!bandwidthCanvas || !billingCanvas || typeof Chart === 'undefined') {
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? '#1e293b' : '#e2e8f0';
    const textColor = isDark ? '#64748b' : '#94a3b8';

    new Chart(bandwidthCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: ['10:00', '10:05', '10:10', '10:15', '10:20', '10:25', '10:30'],
            datasets: [
                {
                    label: 'Download (Mbps)',
                    data: [210, 320, 280, 410, 390, 450, 480],
                    borderColor: '#10b981',
                    tension: 0.4,
                    fill: false,
                },
                {
                    label: 'Upload (Mbps)',
                    data: [80, 110, 95, 140, 160, 180, 195],
                    borderColor: '#6366f1',
                    tension: 0.4,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
            },
        },
    });

    new Chart(billingCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Lunas', 'Menunggak / Belum Bayar'],
            datasets: [
                {
                    data: [152, 32],
                    backgroundColor: ['#10b981', '#f43f5e'],
                    borderWidth: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '75%',
        },
    });
});
