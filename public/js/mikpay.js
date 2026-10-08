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
        btn.classList.remove('bg-white', 'text-indigo-700', 'shadow-md');
        btn.classList.add('text-slate-600');
    });

    const target = document.getElementById('preview-' + tabName);
    if (target) {
        target.classList.remove('hidden');
    }

    const activeBtn = document.getElementById('btn-tab-' + tabName);
    if (activeBtn) {
        activeBtn.classList.add('bg-white', 'text-indigo-700', 'shadow-md');
        activeBtn.classList.remove('text-slate-600');
    }
};

MikPay.toggleFaq = function (id) {
    const faqEl = document.getElementById(id);
    if (faqEl) {
        faqEl.classList.toggle('hidden');
    }

    const icon = document.querySelector('[data-faq-icon="' + id + '"]');
    if (icon) {
        icon.classList.toggle('rotate-45');
    }
};

MikPay.initReveal = function () {
    const items = document.querySelectorAll('.reveal');
    if (!items.length) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    items.forEach((el) => observer.observe(el));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', MikPay.initReveal);
} else {
    MikPay.initReveal();
}

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

MikPay.togglePasswordVisibility = function (btn) {
    if (!btn) {
        return;
    }

    const input = document.getElementById(btn.getAttribute('data-password-toggle') || '');
    if (!input) {
        return;
    }

    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';

    const icon = btn.querySelector('i');
    if (icon) {
        icon.classList.toggle('fa-eye', reveal);
        icon.classList.toggle('fa-eye-slash', ! reveal);
    }

    btn.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
};

MikPay.initPasswordToggles = function () {
    if (MikPay._passwordToggleBound) {
        return;
    }
    MikPay._passwordToggleBound = true;

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-password-toggle]');
        if (!btn) {
            return;
        }

        event.preventDefault();
        MikPay.togglePasswordVisibility(btn);
    });
};

MikPay.initPasswordToggles();
