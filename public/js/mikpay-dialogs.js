/**
 * MikPay custom dialogs — ganti window.confirm / window.alert bawaan browser.
 */
(function () {
    'use strict';

    window.MikPay = window.MikPay || {};

    function $(id) {
        return document.getElementById(id);
    }

    function setIcon(variant) {
        const iconWrap = $('mikpayConfirmIconWrap');
        const icon = $('mikpayConfirmIcon');
        if (!iconWrap || !icon) return;

        if (variant === 'danger') {
            iconWrap.className = 'h-10 w-10 rounded-2xl flex items-center justify-center shrink-0 bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300';
            icon.className = 'fa-solid fa-trash-can text-lg';
        } else if (variant === 'warning') {
            iconWrap.className = 'h-10 w-10 rounded-2xl flex items-center justify-center shrink-0 bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300';
            icon.className = 'fa-solid fa-triangle-exclamation text-lg';
        } else if (variant === 'info') {
            iconWrap.className = 'h-10 w-10 rounded-2xl flex items-center justify-center shrink-0 bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300';
            icon.className = 'fa-solid fa-circle-info text-lg';
        } else {
            iconWrap.className = 'h-10 w-10 rounded-2xl flex items-center justify-center shrink-0 bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300';
            icon.className = 'fa-solid fa-circle-question text-lg';
        }
    }

    function setOkStyle(variant, alertMode) {
        const okBtn = $('mikpayConfirmOk');
        if (!okBtn) return;
        const base = 'flex-1 px-3 py-2.5 rounded-2xl text-xs font-bold shadow-md transition text-white ';
        if (alertMode) {
            okBtn.className = base + 'bg-violet-600 hover:bg-violet-500 shadow-violet-600/20';
            return;
        }
        if (variant === 'danger') {
            okBtn.className = base + 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/20';
        } else if (variant === 'warning') {
            okBtn.className = base + 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20';
        } else {
            okBtn.className = base + 'bg-violet-600 hover:bg-violet-500 shadow-violet-600/20';
        }
    }

    /**
     * @param {string|{title?:string,message?:string,confirmText?:string,cancelText?:string,variant?:string,alert?:boolean}} opts
     * @returns {Promise<boolean>}
     */
    MikPay.confirm = function (opts) {
        const options = typeof opts === 'string' ? { message: opts } : (opts || {});
        const modal = $('mikpayConfirmModal');
        const titleEl = $('mikpayConfirmTitle');
        const msgEl = $('mikpayConfirmMessage');
        const okBtn = $('mikpayConfirmOk');
        const cancelBtn = $('mikpayConfirmCancel');
        const backdrop = $('mikpayConfirmBackdrop');
        const alertMode = !!options.alert;

        if (!modal || !okBtn) {
            if (alertMode) {
                window.alert(options.message || '');
                return Promise.resolve(true);
            }
            return Promise.resolve(window.confirm(options.message || 'Lanjutkan?'));
        }

        const variant = options.variant || (alertMode ? 'info' : 'default');
        titleEl.textContent = options.title || (alertMode ? 'Pemberitahuan' : 'Konfirmasi');
        msgEl.textContent = options.message || (alertMode ? '' : 'Lanjutkan?');
        okBtn.textContent = options.confirmText || (alertMode ? 'Mengerti' : (variant === 'danger' ? 'Ya, hapus' : 'Ya, lanjut'));
        if (cancelBtn) {
            cancelBtn.textContent = options.cancelText || 'Batal';
            cancelBtn.classList.toggle('hidden', alertMode);
        }
        setIcon(variant);
        setOkStyle(variant, alertMode);

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        okBtn.focus();

        return new Promise((resolve) => {
            const close = (value) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                okBtn.onclick = null;
                if (cancelBtn) cancelBtn.onclick = null;
                if (backdrop) backdrop.onclick = null;
                document.removeEventListener('keydown', onKey);
                resolve(value);
            };
            const onKey = (e) => {
                if (e.key === 'Escape') close(alertMode ? true : false);
                if (e.key === 'Enter') close(true);
            };
            okBtn.onclick = () => close(true);
            if (cancelBtn) cancelBtn.onclick = () => close(false);
            if (backdrop) backdrop.onclick = () => close(alertMode ? true : false);
            document.addEventListener('keydown', onKey);
        });
    };

    MikPay.alert = function (opts) {
        const options = typeof opts === 'string' ? { message: opts } : (opts || {});
        options.alert = true;
        options.variant = options.variant || 'info';
        return MikPay.confirm(options);
    };

    MikPay.toast = function (message, ms) {
        const wrap = $('mikpayToast');
        if (!wrap) {
            return MikPay.alert(message);
        }
        const box = wrap.firstElementChild;
        if (box) box.textContent = message || '';
        wrap.classList.remove('hidden');
        clearTimeout(window.__mikpayToastTimer);
        window.__mikpayToastTimer = setTimeout(() => wrap.classList.add('hidden'), ms || 2200);
    };

    if (typeof MikPay.showToast !== 'function') {
        MikPay.showToast = MikPay.toast;
    }

    /**
     * onsubmit="return MikPay.confirmForm(event, { title, message, variant })"
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
    window.mikpayAlert = MikPay.alert;
    window.mikpayToast = MikPay.toast;
})();
