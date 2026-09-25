import 'bootstrap';
import './customer-autocompletion.js';


const root = document.documentElement;
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');
const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');
const themeToggle = document.querySelector('[data-theme-toggle]');
const themeLabel = document.querySelector('[data-theme-label]');
const themeIcon = themeToggle?.querySelector('i');

const setSidebarState = (isOpen) => {
    document.body.classList.toggle('is-sidebar-open', isOpen);
    sidebarOpenButton?.setAttribute('aria-expanded', String(isOpen));
};

sidebarOpenButton?.addEventListener('click', () => setSidebarState(true));
sidebarCloseButtons.forEach((button) => button.addEventListener('click', () => setSidebarState(false)));

const syncSidebarControl = () => {
    const isCollapsed = root.dataset.sidebar === 'collapsed';
    sidebarCollapseButton?.setAttribute('aria-label', isCollapsed ? 'Déployer le menu latéral' : 'Réduire le menu latéral');
    sidebarCollapseButton?.setAttribute('aria-expanded', String(!isCollapsed));
};

sidebarCollapseButton?.addEventListener('click', () => {
    const sidebarState = root.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed';
    root.dataset.sidebar = sidebarState;
    localStorage.setItem('cite-etoile-sidebar', sidebarState);
    syncSidebarControl();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setSidebarState(false);
    }
});

const syncThemeControl = () => {
    const isDark = root.dataset.bsTheme === 'dark';
    themeToggle?.setAttribute('aria-label', isDark ? 'Activer le mode clair' : 'Activer le mode sombre');

    if (themeLabel) {
        themeLabel.textContent = isDark ? 'Mode clair' : 'Mode sombre';
    }

    if (themeIcon) {
        themeIcon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
};

themeToggle?.addEventListener('click', () => {
    const theme = root.dataset.bsTheme === 'dark' ? 'light' : 'dark';
    root.dataset.bsTheme = theme;
    root.dataset.theme = theme;
    localStorage.setItem('cite-etoile-theme', theme);
    syncThemeControl();
});

syncThemeControl();
syncSidebarControl();

document.querySelectorAll('[data-auto-submit]').forEach((control) => {
    control.addEventListener('change', () => control.form?.requestSubmit());
});

document.querySelectorAll('[data-plan-select]').forEach((select) => {
    const summary = document.querySelector('[data-plan-summary]');
    const summaryText = summary?.querySelector('[data-plan-summary-text]');

    const updatePlanSummary = () => {
        const option = select.options[select.selectedIndex];
        const duration = Number.parseInt(option.dataset.duration || '0', 10);
        const price = option.dataset.price;
        const monthly = option.dataset.monthly;

        if (!summary || !summaryText || !price) {
            summary?.classList.add('hidden');
            return;
        }

        summary.classList.remove('hidden');
        summaryText.textContent = duration > 0
            ? `Crédit sur ${duration} mois · Total ${price} USD · ${monthly} USD par mois`
            : `Paiement comptant · ${price} USD en une seule fois`;
    };

    select.addEventListener('change', updatePlanSummary);
    updatePlanSummary();
});

document.querySelectorAll('[data-suggested-amount]').forEach((button) => {
    button.addEventListener('click', () => {
        const amountInput = document.querySelector('#amount-input');

        if (amountInput) {
            amountInput.value = button.dataset.suggestedAmount;
            amountInput.focus();
        }
    });
});

const confirmationModal = document.querySelector('[data-confirm-modal]');
const confirmationMessage = confirmationModal?.querySelector('[data-confirm-message]');
const confirmationTitle = confirmationModal?.querySelector('[data-confirm-title]');
const confirmationAcceptButton = confirmationModal?.querySelector('[data-confirm-accept]');
const confirmationCancelButtons = confirmationModal?.querySelectorAll('[data-confirm-cancel]') ?? [];
let pendingConfirmationForm = null;
let confirmationTrigger = null;

const closeConfirmationModal = () => {
    confirmationModal?.close();
    pendingConfirmationForm = null;
    confirmationTrigger?.focus();
    confirmationTrigger = null;
};

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        pendingConfirmationForm = form;
        confirmationTrigger = event.submitter;

        const message = form.dataset.confirm ?? 'Confirmer cette opération ?';
        const isDestructive = /supprim|corbeille|irréversible/i.test(message);

        if (confirmationMessage) {
            confirmationMessage.textContent = message;
        }

        if (confirmationTitle) {
            confirmationTitle.textContent = isDestructive ? 'Confirmer la suppression' : 'Confirmer l’opération';
        }

        confirmationModal?.showModal();
    });
});

confirmationAcceptButton?.addEventListener('click', () => {
    const form = pendingConfirmationForm;
    pendingConfirmationForm = null;
    confirmationModal?.close();
    form?.submit();
});

confirmationCancelButtons.forEach((button) => button.addEventListener('click', closeConfirmationModal));
confirmationModal?.addEventListener('cancel', (event) => {
    event.preventDefault();
    closeConfirmationModal();
});
confirmationModal?.addEventListener('click', (event) => {
    if (event.target === confirmationModal) {
        closeConfirmationModal();
    }
});

document.querySelectorAll('[data-credentials-panel]').forEach((panel) => {
    const content = panel.querySelector('[data-credentials-content]');
    const copyButton = panel.querySelector('[data-copy-credentials]');
    const downloadButton = panel.querySelector('[data-download-credentials]');
    const markdown = content?.textContent.trim() ?? '';

    copyButton?.addEventListener('click', async () => {
        await navigator.clipboard.writeText(markdown);

        const label = copyButton.querySelector('span');
        const originalLabel = label?.textContent;

        if (label) {
            label.textContent = 'Copié';
            window.setTimeout(() => {
                label.textContent = originalLabel;
            }, 2000);
        }
    });

    downloadButton?.addEventListener('click', () => {
        const url = URL.createObjectURL(new Blob([markdown], { type: 'text/markdown;charset=utf-8' }));
        const link = document.createElement('a');

        link.href = url;
        link.download = downloadButton.dataset.filename;
        link.click();
        URL.revokeObjectURL(url);
    });
});

document.querySelectorAll('[data-app-toast]').forEach((toast) => {
    const dismiss = () => {
        toast.classList.add('is-leaving');
        window.setTimeout(() => toast.remove(), 180);
    };

    toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 5000);
});
