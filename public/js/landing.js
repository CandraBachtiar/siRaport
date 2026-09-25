const menuToggle = document.querySelector('.menu-toggle');
const mainNavigation = document.querySelector('#main-nav');

menuToggle?.addEventListener('click', () => {
    const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';

    menuToggle.setAttribute('aria-expanded', String(!isExpanded));
    menuToggle.setAttribute('aria-label', isExpanded ? 'Buka menu' : 'Tutup menu');
    mainNavigation?.classList.toggle('is-open', !isExpanded);
});

mainNavigation?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Buka menu');
        mainNavigation.classList.remove('is-open');
    });
});
