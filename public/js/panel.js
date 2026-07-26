window.MikPay = window.MikPay || {};

MikPay.toggleTheme = function () {
    const root = document.documentElement;
    const icon = document.getElementById('themeIcon');
    const isDark = root.classList.toggle('dark');

    localStorage.setItem('theme', isDark ? 'dark' : 'light');

    if (icon) {
        icon.className = isDark
            ? 'fa-solid fa-sun text-sm text-amber-400'
            : 'fa-solid fa-moon text-sm text-slate-700';
    }
};

MikPay.applySavedTheme = function () {
    const saved = localStorage.getItem('theme');
    const icon = document.getElementById('themeIcon');
    const preferDark = saved === 'dark';

    document.documentElement.classList.toggle('dark', preferDark);

    if (icon) {
        icon.className = preferDark
            ? 'fa-solid fa-sun text-sm text-amber-400'
            : 'fa-solid fa-moon text-sm text-slate-700';
    }
};

MikPay.copyText = function (text) {
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            MikPay.showToast('Alamat URL berhasil disalin: ' + text);
        });
        return;
    }

    const temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    MikPay.showToast('Alamat URL berhasil disalin: ' + text);
};

MikPay.copyVpnScript = function () {
    const el = document.getElementById('vpnScriptCode');
    if (el) {
        MikPay.copyText(el.value);
    }
};

MikPay.switchVpnScript = function () {
    const version = document.getElementById('vpnVersion')?.value || 'ros7';
    const textarea = document.getElementById('vpnScriptCode');

    if (!textarea) {
        return;
    }

    // Pakai script OpenVPN nyata dari server (window.__tunnelScripts), jangan template SSTP/WG palsu.
    if (window.__tunnelScripts && window.__tunnelScripts[version]) {
        textarea.value = window.__tunnelScripts[version];
        return;
    }

    textarea.value = '# Script OpenVPN belum tersedia. Refresh halaman Remote & OpenVPN.';
};

MikPay.openThermalModal = function (name, total, paket) {
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.innerText = val;
    };
    set('stName', name);
    set('stTotal', total);
    set('stPaket', paket);
    document.getElementById('thermalModal')?.classList.remove('hidden');
};

MikPay.closeThermalModal = function () {
    document.getElementById('thermalModal')?.classList.add('hidden');
};

MikPay.openOnuModal = function () {
    document.getElementById('onuModal')?.classList.remove('hidden');
};

MikPay.closeOnuModal = function () {
    document.getElementById('onuModal')?.classList.add('hidden');
};

MikPay.openOltWebview = function () {
    document.getElementById('oltWebviewModal')?.classList.remove('hidden');
};

MikPay.closeOltWebview = function () {
    document.getElementById('oltWebviewModal')?.classList.add('hidden');
};

MikPay.sendWaBlast = function () {
    MikPay.showToast('Mengirim 32 Pesan WhatsApp Tagihan via Gateway...');
};

MikPay.generateVouchers = function () {
    const qty = parseInt(document.getElementById('vQty')?.value, 10) || 6;
    const price = document.getElementById('vProfile')?.value || '5000';
    const container = document.getElementById('voucherContainer');
    if (!container) return;

    container.innerHTML = '';
    for (let i = 0; i < qty; i++) {
        const code = 'MP-' + Math.random().toString(36).substring(2, 7).toUpperCase();
        container.innerHTML += `
            <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1.5 text-center relative overflow-hidden shadow-sm">
                <div class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">MikPay Voucher</div>
                <div class="text-sm font-extrabold font-mono text-slate-900 dark:text-white tracking-wider bg-white dark:bg-slate-900 py-1 rounded border border-slate-200 dark:border-slate-800">${code}</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold">Rp ${parseInt(price, 10).toLocaleString('id-ID')}</div>
                <div class="w-12 h-12 bg-white p-1 mx-auto rounded border border-slate-200 dark:border-slate-800">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${code}" alt="QR" class="w-full h-full">
                </div>
            </div>`;
    }
    MikPay.showToast(`${qty} Voucher Berhasil Di-generate!`);
};

// Global aliases for inline onclick handlers from template
window.toggleTheme = MikPay.toggleTheme;
window.copyText = MikPay.copyText;
window.copyVpnScript = MikPay.copyVpnScript;
window.switchVpnScript = MikPay.switchVpnScript;
window.openThermalModal = MikPay.openThermalModal;
window.closeThermalModal = MikPay.closeThermalModal;
window.openOnuModal = MikPay.openOnuModal;
window.closeOnuModal = MikPay.closeOnuModal;
window.openOltWebview = MikPay.openOltWebview;
window.closeOltWebview = MikPay.closeOltWebview;
window.sendWaBlast = MikPay.sendWaBlast;
window.generateVouchers = MikPay.generateVouchers;

document.addEventListener('DOMContentLoaded', MikPay.applySavedTheme);
