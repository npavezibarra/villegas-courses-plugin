document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('lesson-menu-toggle');
    const nav = document.getElementById('lesson-navigation');
    const backdrop = document.getElementById('menu-backdrop');
    const icon = document.getElementById('menu-icon');

    if (!button || !nav || !backdrop) return;

    const openMenu = () => {
        nav.classList.remove('invisible', 'opacity-0', '-translate-y-96');
        nav.classList.add('visible', 'opacity-100', 'translate-y-0');
        backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        icon.classList.add('rotate-90');
    };

    const closeMenu = () => {
        nav.classList.add('invisible', 'opacity-0', '-translate-y-96');
        nav.classList.remove('visible', 'opacity-100', 'translate-y-0');
        backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        icon.classList.remove('rotate-90');
    };

    button.addEventListener('click', () => {
        nav.classList.contains('visible') ? closeMenu() : openMenu();
    });

    backdrop.addEventListener('click', closeMenu);

    nav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });
});
