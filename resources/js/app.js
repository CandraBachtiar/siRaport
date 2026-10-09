const publicMenuButton = document.querySelector('[data-public-menu-toggle]');
const publicMenu = document.querySelector('[data-public-menu]');

const setPublicMenuOpen = (isOpen, restoreFocus = false) => {
    publicMenuButton?.setAttribute('aria-expanded', String(isOpen));
    publicMenuButton?.setAttribute('aria-label', isOpen ? 'Tutup menu utama' : 'Buka menu utama');
    publicMenu?.classList.toggle('hidden', !isOpen);

    if (isOpen) {
        publicMenu?.querySelector('a')?.focus();
    } else if (restoreFocus) {
        publicMenuButton?.focus();
    }
};

publicMenuButton?.addEventListener('click', () => {
    setPublicMenuOpen(publicMenuButton.getAttribute('aria-expanded') !== 'true');
});

publicMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        setPublicMenuOpen(false);
    });
});

const dashboardSidebar = document.querySelector('[data-dashboard-sidebar]');
const dashboardBackdrop = document.querySelector('[data-dashboard-backdrop]');
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const sidebarCloseButton = document.querySelector('[data-sidebar-close]');
let sidebarPreviousFocus = null;

const setSidebarOpen = (isOpen, restoreFocus = false) => {
    if (isOpen && document.activeElement instanceof HTMLElement) {
        sidebarPreviousFocus = document.activeElement;
    }

    dashboardSidebar?.classList.toggle('hidden', !isOpen);
    dashboardSidebar?.classList.toggle('flex', isOpen);
    dashboardBackdrop?.classList.toggle('hidden', !isOpen);
    sidebarOpenButton?.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('overflow-hidden', isOpen);

    if (isOpen) {
        sidebarCloseButton?.focus();
    } else if (restoreFocus && sidebarPreviousFocus instanceof HTMLElement) {
        sidebarPreviousFocus.focus();
    }
};

sidebarOpenButton?.addEventListener('click', () => setSidebarOpen(true));
sidebarCloseButton?.addEventListener('click', () => setSidebarOpen(false, true));
dashboardBackdrop?.addEventListener('click', () => setSidebarOpen(false, true));
dashboardSidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 1023px)').matches) {
            setSidebarOpen(false);
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && publicMenuButton?.getAttribute('aria-expanded') === 'true') {
        setPublicMenuOpen(false, true);
    }

    if (event.key === 'Escape' && sidebarOpenButton?.getAttribute('aria-expanded') === 'true') {
        setSidebarOpen(false, true);
    }

    if (event.key !== 'Tab' || sidebarOpenButton?.getAttribute('aria-expanded') !== 'true' || !(dashboardSidebar instanceof HTMLElement)) {
        return;
    }

    const focusableElements = Array.from(dashboardSidebar.querySelectorAll('a, button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'))
        .filter((element) => element instanceof HTMLElement && !element.hasAttribute('hidden'));
    const firstElement = focusableElements[0];
    const lastElement = focusableElements.at(-1);

    if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement?.focus();
    } else if (! event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement?.focus();
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const inputId = button.getAttribute('aria-controls');
        const input = inputId ? document.getElementById(inputId) : null;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const shouldShow = input.type === 'password';
        input.type = shouldShow ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(shouldShow));
        button.setAttribute('aria-label', shouldShow ? 'Sembunyikan password' : 'Tampilkan password');
        button.querySelector('[data-password-label]')?.replaceChildren(shouldShow ? 'Sembunyikan' : 'Tampilkan');
    });
});

document.querySelectorAll('[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const submitButton = form.querySelector('button[type="submit"]');
        const submitLabel = submitButton?.querySelector('[data-submit-label]');

        if (!(submitButton instanceof HTMLButtonElement)) {
            return;
        }

        submitButton.disabled = true;
        submitButton.classList.add('cursor-wait', 'opacity-75');
        submitButton.setAttribute('aria-busy', 'true');
        submitLabel?.replaceChildren('Memproses...');
    });
});

const helpSearch = document.querySelector('[data-help-search]');
const helpItems = Array.from(document.querySelectorAll('[data-help-item]'));
const helpEmptyState = document.querySelector('[data-help-empty]');

helpSearch?.addEventListener('input', () => {
    const query = helpSearch.value.trim().toLocaleLowerCase('id');
    let visibleItems = 0;

    helpItems.forEach((item) => {
        const isVisible = item.textContent.toLocaleLowerCase('id').includes(query);
        item.classList.toggle('hidden', !isVisible);
        visibleItems += Number(isVisible);
    });

    helpEmptyState?.classList.toggle('hidden', visibleItems > 0);
});

const guideTabs = Array.from(document.querySelectorAll('[data-guide-tab]'));
const guidePanels = document.querySelectorAll('[data-guide-panel]');

const selectGuideTab = (selectedTab, moveFocus = false) => {
    const selectedPanel = selectedTab.getAttribute('data-guide-tab');

    guideTabs.forEach((item) => {
        const isSelected = item === selectedTab;
        item.setAttribute('aria-selected', String(isSelected));
        item.setAttribute('tabindex', isSelected ? '0' : '-1');
    });

    guidePanels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-guide-panel') !== selectedPanel);
    });

    if (moveFocus) {
        selectedTab.focus();
    }
};

guideTabs.forEach((tab, tabIndex) => {
    tab.addEventListener('click', () => selectGuideTab(tab));
    tab.addEventListener('keydown', (event) => {
        const lastTabIndex = guideTabs.length - 1;
        const targetIndex = event.key === 'ArrowRight'
            ? (tabIndex === lastTabIndex ? 0 : tabIndex + 1)
            : (event.key === 'ArrowLeft'
                ? (tabIndex === 0 ? lastTabIndex : tabIndex - 1)
                : (event.key === 'Home' ? 0 : (event.key === 'End' ? lastTabIndex : null)));

        if (targetIndex !== null) {
            event.preventDefault();
            selectGuideTab(guideTabs[targetIndex], true);
        }
    });
});

document.querySelectorAll('[data-flash-close]').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('[data-flash-message]')?.remove();
    });
});

document.querySelectorAll('[data-auto-submit]').forEach((field) => {
    field.addEventListener('change', () => {
        const form = field.closest('form');
        const filterOrder = ['year', 'subject', 'class', 'assessment', 'student'];
        const currentLevel = field.getAttribute('data-score-filter');
        const currentIndex = currentLevel ? filterOrder.indexOf(currentLevel) : -1;

        if (form instanceof HTMLFormElement && currentIndex >= 0) {
            filterOrder.slice(currentIndex + 1).forEach((level) => {
                const dependentField = form.querySelector(`[data-score-filter="${level}"]`);

                if (dependentField instanceof HTMLSelectElement) {
                    dependentField.value = '';
                }
            });
        }

        form?.requestSubmit();
    });
});

document.querySelectorAll('[data-wali-class-filter]').forEach((field) => {
    field.addEventListener('change', () => {
        const form = field.closest('form');
        const schoolYearField = form?.querySelector('[data-wali-year-filter]');

        if (schoolYearField instanceof HTMLSelectElement) {
            schoolYearField.value = '';
        }

        form?.requestSubmit();
    });
});

const deleteAssessmentDialog = document.querySelector('[data-delete-assessment-dialog]');
const deleteAssessmentForm = deleteAssessmentDialog?.querySelector('[data-delete-assessment-form]');
const deleteAssessmentName = deleteAssessmentDialog?.querySelector('[data-delete-assessment-name]');

document.querySelectorAll('[data-delete-assessment]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!(deleteAssessmentDialog instanceof HTMLDialogElement) || !(deleteAssessmentForm instanceof HTMLFormElement)) {
            return;
        }

        deleteAssessmentForm.action = button.dataset.deleteAction ?? '';
        deleteAssessmentName?.replaceChildren(button.dataset.deleteName ?? 'penilaian ini');
        deleteAssessmentDialog.showModal();
    });
});

deleteAssessmentDialog?.querySelector('[data-dialog-cancel]')?.addEventListener('click', () => {
    deleteAssessmentDialog.close();
});

document.querySelectorAll('[data-score-input]').forEach((input) => {
    input.addEventListener('input', () => {
        const status = document.querySelector(`[data-score-status="${input.dataset.scoreInput}"]`);
        const minimumScore = Number(input.dataset.kkm);
        const score = Number(input.value);

        if (!(status instanceof HTMLElement)) {
            return;
        }

        if (input.value === '') {
            status.textContent = 'Belum diisi';
            status.className = 'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600';
            return;
        }

        const meetsMinimum = Number.isFinite(score) && score >= minimumScore;
        status.textContent = meetsMinimum ? 'Mencapai KKM' : 'Di bawah KKM';
        status.className = meetsMinimum
            ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700'
            : 'inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700';
    });
});

document.querySelector('[data-print-page]')?.addEventListener('click', () => {
    window.print();
});

document.querySelectorAll('[data-history-back]').forEach((button) => {
    button.addEventListener('click', () => {
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        window.location.assign(button.dataset.fallbackUrl ?? '/');
    });
});
