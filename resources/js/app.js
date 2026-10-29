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
const reportDataNav = document.querySelector('[data-report-data-nav]');
const reportReadyNav = document.querySelector('[data-report-ready-nav]');
let sidebarPreviousFocus = null;

const setReportNavActive = (link, isActive) => {
    if (!(link instanceof HTMLAnchorElement)) {
        return;
    }

    const activeClasses = link.dataset.navActive?.split(/\s+/).filter(Boolean) ?? [];
    const inactiveClasses = link.dataset.navInactive?.split(/\s+/).filter(Boolean) ?? [];
    link.classList.remove(...activeClasses, ...inactiveClasses);
    link.classList.add(...(isActive ? activeClasses : inactiveClasses));

    if (isActive) {
        link.setAttribute('aria-current', 'page');
    } else {
        link.removeAttribute('aria-current');
    }
};

const syncReportNav = () => {
    if (!(reportReadyNav instanceof HTMLAnchorElement) || !(reportDataNav instanceof HTMLAnchorElement)) {
        return;
    }

    const readyUrl = new URL(reportReadyNav.href);
    const isReadySection = window.location.pathname === readyUrl.pathname
        && window.location.hash === readyUrl.hash;

    setReportNavActive(reportReadyNav, isReadySection || reportReadyNav.dataset.navDefaultActive === 'true');
    setReportNavActive(reportDataNav, !isReadySection && reportDataNav.dataset.navDefaultActive === 'true');
};

syncReportNav();
window.addEventListener('hashchange', syncReportNav);

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

document.querySelectorAll('[data-line-chart]').forEach((chart) => {
    const tooltip = chart.querySelector('[data-chart-tooltip]');

    if (!(tooltip instanceof HTMLElement)) {
        return;
    }

    let activePoint = null;
    let pinnedPoint = null;
    let hideTimer = null;

    const positionTooltip = (x, y) => {
        const edge = 10;
        const gap = 12;
        const bounds = tooltip.getBoundingClientRect();
        let left = x + gap;
        let top = y + gap;

        if (left + bounds.width > window.innerWidth - edge) {
            left = x - bounds.width - gap;
        }

        if (top + bounds.height > window.innerHeight - edge) {
            top = y - bounds.height - gap;
        }

        left = Math.min(Math.max(edge, left), window.innerWidth - bounds.width - edge);
        top = Math.min(Math.max(edge, top), window.innerHeight - bounds.height - edge);
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    };

    const showTooltip = (point, x, y, pin = false) => {
        window.clearTimeout(hideTimer);
        tooltip.querySelector('[data-tooltip-subject-value]')?.replaceChildren(point.dataset.tooltipSubject || 'Tidak tersedia');
        tooltip.querySelector('[data-tooltip-assessment-value]')?.replaceChildren(point.dataset.tooltipAssessment || 'Tidak tersedia');
        tooltip.querySelector('[data-tooltip-date-value]')?.replaceChildren(point.dataset.tooltipDate || 'Tidak tersedia');
        tooltip.querySelector('[data-tooltip-value-label]')?.replaceChildren(point.dataset.tooltipValueLabel || 'Nilai Siswa');

        const score = Number(point.dataset.tooltipScore);
        tooltip.querySelector('[data-tooltip-score-value]')?.replaceChildren(
            Number.isFinite(score) ? score.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'Belum tersedia',
        );

        const countLabel = tooltip.querySelector('[data-tooltip-count-label]');
        const countValue = tooltip.querySelector('[data-tooltip-count-value]');
        const hasCount = point.dataset.tooltipCount !== '' && point.dataset.tooltipTotal !== '';
        countLabel?.classList.toggle('hidden', !hasCount);
        countValue?.classList.toggle('hidden', !hasCount);
        countLabel?.replaceChildren('Siswa dinilai');
        countValue?.replaceChildren(`${point.dataset.tooltipCount}/${point.dataset.tooltipTotal}`);

        tooltip.classList.remove('hidden');
        tooltip.setAttribute('aria-hidden', 'false');
        activePoint = point;
        pinnedPoint = pin ? point : null;
        positionTooltip(x, y);
    };

    const showPointAtCenter = (point, pin = false) => {
        const bounds = point.getBoundingClientRect();
        showTooltip(point, bounds.left + (bounds.width / 2), bounds.top + (bounds.height / 2), pin);
    };

    const hideTooltip = () => {
        window.clearTimeout(hideTimer);
        tooltip.classList.add('hidden');
        tooltip.setAttribute('aria-hidden', 'true');
        activePoint = null;
        pinnedPoint = null;
    };

    chart.addEventListener('pointerover', (event) => {
        if (event.pointerType === 'touch') {
            return;
        }

        const point = event.target.closest('[data-chart-point]');

        if (point instanceof SVGElement) {
            showTooltip(point, event.clientX, event.clientY);
        }
    });

    chart.addEventListener('pointermove', (event) => {
        const point = event.target.closest('[data-chart-point]');

        if (event.pointerType !== 'touch' && point === activePoint && pinnedPoint !== point) {
            positionTooltip(event.clientX, event.clientY);
        }
    });

    chart.addEventListener('pointerout', (event) => {
        const point = event.target.closest('[data-chart-point]');
        const nextPoint = event.relatedTarget instanceof Element ? event.relatedTarget.closest('[data-chart-point]') : null;

        if (point === activePoint && nextPoint !== point && pinnedPoint !== point) {
            hideTimer = window.setTimeout(hideTooltip, 100);
        }
    });

    chart.addEventListener('focusin', (event) => {
        const point = event.target.closest('[data-chart-point]');

        if (point instanceof SVGElement) {
            showPointAtCenter(point);
        }
    });

    chart.addEventListener('focusout', (event) => {
        if (event.target === activePoint && pinnedPoint !== activePoint) {
            hideTooltip();
        }
    });

    chart.addEventListener('click', (event) => {
        const point = event.target.closest('[data-chart-point]');
        const isTouchInput = event.pointerType === 'touch' || window.matchMedia('(pointer: coarse)').matches;

        if (!(point instanceof SVGElement) || !isTouchInput || point instanceof SVGAnchorElement) {
            return;
        }

        if (pinnedPoint === point) {
            hideTooltip();
        } else {
            showPointAtCenter(point, true);
        }
    });

    document.addEventListener('pointerdown', (event) => {
        if (pinnedPoint !== null && !chart.contains(event.target)) {
            hideTooltip();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activePoint !== null) {
            hideTooltip();
        }
    });

    chart.addEventListener('keydown', (event) => {
        if (event.key !== ' ' || !(event.target instanceof SVGElement)) {
            return;
        }

        const point = event.target.closest('[data-chart-point]');
        const link = point instanceof SVGAnchorElement ? point : point?.closest('a[href]');

        if (link instanceof SVGAnchorElement) {
            event.preventDefault();
            link.click();
        }
    });

    window.addEventListener('resize', () => {
        if (activePoint !== null && pinnedPoint !== null) {
            showPointAtCenter(pinnedPoint, true);
        }
    });
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
