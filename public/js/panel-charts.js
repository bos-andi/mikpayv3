document.addEventListener('DOMContentLoaded', () => {
    const bandwidthCanvas = document.getElementById('chartBandwidth');
    const billingCanvas = document.getElementById('chartBillingStatus');
    if (typeof Chart === 'undefined') {
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? '#1e293b' : '#e2e8f0';
    const textColor = isDark ? '#64748b' : '#94a3b8';

    if (bandwidthCanvas) {
        initLiveBandwidthChart(bandwidthCanvas, { gridColor, textColor });
    }

    if (billingCanvas) {
        const stats = window.__dashboardBilling || { paid: 0, unpaid: 0 };
        const paid = Math.max(0, Number(stats.paid) || 0);
        const unpaid = Math.max(0, Number(stats.unpaid) || 0);

        new Chart(billingCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Lunas', 'Belum Bayar'],
                datasets: [
                    {
                        data: [paid, unpaid],
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
    }
});

function initLiveBandwidthChart(canvas, theme) {
    const cfg = window.__dashboardTraffic || {};
    const url = cfg.url || '';
    const pollMs = Math.max(1500, Number(cfg.pollMs) || 2500);
    const maxPoints = Math.max(10, Number(cfg.maxPoints) || 40);
    const selectEl = document.getElementById('bandwidthInterface');
    const subtitleEl = document.getElementById('bandwidthSubtitle');
    const badgeEl = document.getElementById('bandwidthLiveBadge');
    const rxNowEl = document.getElementById('bandwidthRxNow');
    const txNowEl = document.getElementById('bandwidthTxNow');

    const storageKey = 'mikpay.bandwidth.interface';
    let selectedInterface = '';
    try {
        selectedInterface = localStorage.getItem(storageKey) || '';
    } catch (_) {
        selectedInterface = '';
    }

    const chart = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Download (Rx)',
                    data: [],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.12)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    tension: 0,
                    fill: true,
                },
                {
                    label: 'Upload (Tx)',
                    data: [],
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244, 63, 94, 0.08)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    tension: 0,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const label = ctx.dataset.label || '';
                            return `${label}: ${formatBitsPerSecond(ctx.parsed.y)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { color: theme.gridColor },
                    ticks: { color: theme.textColor, font: { size: 10 }, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: theme.gridColor },
                    ticks: {
                        color: theme.textColor,
                        font: { size: 10 },
                        callback(value) {
                            return formatBitsPerSecond(value, true);
                        },
                    },
                },
            },
        },
    });

    if (!cfg.hasRouter || !url) {
        setBadge(badgeEl, 'NO ROUTER', 'slate');
        if (subtitleEl) {
            subtitleEl.textContent = 'Hubungkan router MikroTik di Pengaturan untuk melihat traffic live.';
        }
        return;
    }

    let busy = false;
    let timer = null;
    let interfacesLoaded = false;

    if (selectEl) {
        selectEl.addEventListener('change', () => {
            selectedInterface = selectEl.value || '';
            try {
                if (selectedInterface) {
                    localStorage.setItem(storageKey, selectedInterface);
                } else {
                    localStorage.removeItem(storageKey);
                }
            } catch (_) {
                /* ignore */
            }
            chart.data.labels = [];
            chart.data.datasets[0].data = [];
            chart.data.datasets[1].data = [];
            chart.update('none');
            poll();
        });
    }

    async function poll() {
        if (busy) {
            return;
        }
        busy = true;

        try {
            const endpoint = new URL(url, window.location.origin);
            if (selectedInterface) {
                endpoint.searchParams.set('interface', selectedInterface);
            }

            const res = await fetch(endpoint.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (!interfacesLoaded && Array.isArray(data.interfaces)) {
                fillInterfaceOptions(selectEl, data.interfaces, data.interface, selectedInterface);
                interfacesLoaded = true;
                if (!selectedInterface && data.interface) {
                    selectedInterface = data.interface;
                }
            }

            if (!data.ok) {
                setBadge(badgeEl, 'OFFLINE', 'rose');
                if (subtitleEl) {
                    subtitleEl.textContent = data.message || 'Gagal membaca traffic dari MikroTik.';
                }
                return;
            }

            const iface = data.interface || selectedInterface || '—';
            selectedInterface = data.interface || selectedInterface;
            if (selectEl && data.interface && selectEl.value === '' && !localStorage.getItem(storageKey)) {
                // keep Auto selected visually, but show resolved name in subtitle
            }

            const rx = Number(data.rx_bps) || 0;
            const tx = Number(data.tx_bps) || 0;
            const label = formatClock(data.at ? new Date(data.at) : new Date());

            chart.data.labels.push(label);
            chart.data.datasets[0].data.push(rx);
            chart.data.datasets[1].data.push(tx);

            while (chart.data.labels.length > maxPoints) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
                chart.data.datasets[1].data.shift();
            }

            chart.update('none');

            if (rxNowEl) {
                rxNowEl.textContent = formatBitsPerSecond(rx);
            }
            if (txNowEl) {
                txNowEl.textContent = formatBitsPerSecond(tx);
            }
            if (subtitleEl) {
                const routerName = data.router ? ` · ${data.router}` : '';
                subtitleEl.textContent = `Interface ${iface}${routerName} — Rx/Tx realtime (sama sumber WinBox Traffic Monitor)`;
            }
            setBadge(badgeEl, 'LIVE', 'emerald');
        } catch (err) {
            setBadge(badgeEl, 'ERROR', 'rose');
            if (subtitleEl) {
                subtitleEl.textContent = 'Gagal memuat live traffic. Coba refresh halaman.';
            }
        } finally {
            busy = false;
        }
    }

    poll();
    timer = window.setInterval(poll, pollMs);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
            return;
        }
        if (!timer) {
            poll();
            timer = window.setInterval(poll, pollMs);
        }
    });
}

function fillInterfaceOptions(selectEl, interfaces, activeName, preferred) {
    if (!selectEl) {
        return;
    }

    const keep = preferred || '';
    selectEl.innerHTML = '';

    const autoOpt = document.createElement('option');
    autoOpt.value = '';
    autoOpt.textContent = activeName ? `Auto (${activeName})` : 'Auto (WAN)';
    selectEl.appendChild(autoOpt);

    interfaces.forEach((iface) => {
        const opt = document.createElement('option');
        opt.value = iface.name;
        const mark = iface.running ? '' : ' (down)';
        opt.textContent = `${iface.name}${mark}`;
        selectEl.appendChild(opt);
    });

    if (keep) {
        selectEl.value = keep;
        if (selectEl.value !== keep) {
            selectEl.value = '';
        }
    }
}

function setBadge(el, text, tone) {
    if (!el) {
        return;
    }
    el.textContent = text;
    const tones = {
        emerald: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
        rose: 'bg-rose-500/15 text-rose-700 dark:text-rose-400',
        slate: 'bg-slate-100 dark:bg-slate-800 text-slate-500',
    };
    el.className = `absolute top-4 right-4 text-[9px] font-bold px-1.5 py-0.5 rounded ${tones[tone] || tones.slate}`;
}

function formatClock(date) {
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    const ss = String(date.getSeconds()).padStart(2, '0');
    return `${hh}:${mm}:${ss}`;
}

function formatBitsPerSecond(bps, compact) {
    const n = Math.max(0, Number(bps) || 0);
    const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    let v = n;
    let i = 0;
    while (v >= 1000 && i < units.length - 1) {
        v /= 1000;
        i += 1;
    }
    const digits = i === 0 ? 0 : v >= 100 ? 0 : v >= 10 ? 1 : 2;
    const num = v.toFixed(digits);
    return compact ? `${num} ${units[i]}` : `${num} ${units[i]}`;
}
