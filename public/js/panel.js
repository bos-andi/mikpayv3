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

/**
 * Custom confirm modal (mengganti window.confirm).
 * @param {string|{title?:string,message?:string,confirmText?:string,cancelText?:string,variant?:'default'|'danger'|'warning'}} opts
 * @returns {Promise<boolean>}
 */
MikPay.confirm = function (opts) {
    const options = typeof opts === 'string' ? { message: opts } : (opts || {});
    const modal = document.getElementById('mikpayConfirmModal');
    const titleEl = document.getElementById('mikpayConfirmTitle');
    const msgEl = document.getElementById('mikpayConfirmMessage');
    const okBtn = document.getElementById('mikpayConfirmOk');
    const cancelBtn = document.getElementById('mikpayConfirmCancel');
    const backdrop = document.getElementById('mikpayConfirmBackdrop');
    const iconWrap = document.getElementById('mikpayConfirmIconWrap');
    const icon = document.getElementById('mikpayConfirmIcon');

    if (!modal || !okBtn || !cancelBtn) {
        return Promise.resolve(window.confirm(options.message || 'Lanjutkan?'));
    }

    const variant = options.variant || 'default';
    titleEl.textContent = options.title || 'Konfirmasi';
    msgEl.textContent = options.message || 'Lanjutkan?';
    okBtn.textContent = options.confirmText || (variant === 'danger' ? 'Ya, hapus' : 'Ya, lanjut');
    cancelBtn.textContent = options.cancelText || 'Batal';

    okBtn.className = 'flex-1 px-3 py-2.5 rounded-2xl text-white text-xs font-bold shadow-md transition ' + (
        variant === 'danger'
            ? 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/20'
            : variant === 'warning'
                ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20'
                : 'bg-violet-600 hover:bg-violet-500 shadow-violet-600/20'
    );

    if (iconWrap && icon) {
        iconWrap.className = 'h-10 w-10 rounded-2xl flex items-center justify-center shrink-0 ' + (
            variant === 'danger'
                ? 'bg-rose-100 text-rose-600'
                : variant === 'warning'
                    ? 'bg-amber-100 text-amber-600'
                    : 'bg-violet-100 text-violet-600'
        );
        icon.className = 'fa-solid text-lg ' + (
            variant === 'danger'
                ? 'fa-trash-can'
                : variant === 'warning'
                    ? 'fa-triangle-exclamation'
                    : 'fa-circle-question'
        );
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    okBtn.focus();

    return new Promise((resolve) => {
        const close = (value) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            okBtn.onclick = null;
            cancelBtn.onclick = null;
            if (backdrop) backdrop.onclick = null;
            document.removeEventListener('keydown', onKey);
            resolve(value);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') close(false);
            if (e.key === 'Enter') close(true);
        };
        okBtn.onclick = () => close(true);
        cancelBtn.onclick = () => close(false);
        if (backdrop) backdrop.onclick = () => close(false);
        document.addEventListener('keydown', onKey);
    });
};

/**
 * Untuk onsubmit form: onsubmit="return MikPay.confirmForm(event, 'Pesan?', { variant: 'danger' })"
 */
MikPay.confirmForm = function (event, message, options) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return false;

    if (form.dataset.mikpayConfirmed === '1') {
        delete form.dataset.mikpayConfirmed;
        return true;
    }

    event.preventDefault();
    const opts = typeof message === 'object' && message !== null
        ? message
        : Object.assign({ message: message || 'Lanjutkan?' }, options || {});

    MikPay.confirm(opts).then((ok) => {
        if (!ok) return;
        form.dataset.mikpayConfirmed = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            HTMLFormElement.prototype.submit.call(form);
        }
    });

    return false;
};

window.mikpayConfirm = MikPay.confirm;
window.mikpayConfirmForm = MikPay.confirmForm;

MikPay.initSidebar = function () {
    const sidebar = document.getElementById('panelSidebar');
    const backdrop = document.getElementById('panelSidebarBackdrop');
    const openBtn = document.getElementById('panelSidebarOpen');
    const closeBtn = document.getElementById('panelSidebarClose');
    if (!sidebar) return;

    const mq = window.matchMedia('(min-width: 1024px)');

    const setOpen = (open) => {
        if (mq.matches) {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            backdrop?.classList.add('hidden');
            openBtn?.setAttribute('aria-expanded', 'false');
            return;
        }

        sidebar.classList.toggle('translate-x-0', open);
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
        openBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    openBtn?.addEventListener('click', () => setOpen(true));
    closeBtn?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));

    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            if (!mq.matches) setOpen(false);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });

    const onViewportChange = () => setOpen(false);
    if (typeof mq.addEventListener === 'function') {
        mq.addEventListener('change', onViewportChange);
    } else if (typeof mq.addListener === 'function') {
        mq.addListener(onViewportChange);
    }
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

document.addEventListener('DOMContentLoaded', () => {
    MikPay.applySavedTheme();
    MikPay.initSidebar();
});
