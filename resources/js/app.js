import 'bootstrap';

const root = document.documentElement;
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');
const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');
const themeToggle = document.querySelector('[data-theme-toggle]');
const themeLabel = document.querySelector('[data-theme-label]');

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
};

themeToggle?.addEventListener('click', () => {
    const theme = root.dataset.bsTheme === 'dark' ? 'light' : 'dark';
    root.dataset.bsTheme = theme;
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
