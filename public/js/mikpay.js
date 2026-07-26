window.MikPay = window.MikPay || {};

MikPay.toggleMobileMenu = function () {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
};

MikPay.switchPreviewTab = function (tabName) {
    document.querySelectorAll('.tab-preview-content').forEach((el) => el.classList.add('hidden'));

    document.querySelectorAll('.tab-preview-btn').forEach((btn) => {
        btn.classList.remove('bg-indigo-600', 'text-white', 'shadow-md');
        btn.classList.add('bg-slate-100', 'text-slate-700');
    });

    const target = document.getElementById('preview-' + tabName);
    if (target) {
        target.classList.remove('hidden');
    }

    const activeBtn = document.getElementById('btn-tab-' + tabName);
    if (activeBtn) {
        activeBtn.classList.add('bg-indigo-600', 'text-white', 'shadow-md');
        activeBtn.classList.remove('bg-slate-100', 'text-slate-700');
    }
};

MikPay.toggleFaq = function (id) {
    const faqEl = document.getElementById(id);
    if (faqEl) {
        faqEl.classList.toggle('hidden');
    }
};

MikPay.showToast = function (msg) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    if (!toast || !toastMsg) {
        return;
    }

    toastMsg.innerText = msg;
    toast.classList.remove('opacity-0', 'pointer-events-none');

    clearTimeout(MikPay._toastTimer);
    MikPay._toastTimer = setTimeout(() => {
        toast.classList.add('opacity-0', 'pointer-events-none');
    }, 3000);
};

// Backward-compatible global aliases used by landing markup
window.toggleMobileMenu = MikPay.toggleMobileMenu;
window.switchPreviewTab = MikPay.switchPreviewTab;
window.toggleFaq = MikPay.toggleFaq;
window.showToast = MikPay.showToast;
