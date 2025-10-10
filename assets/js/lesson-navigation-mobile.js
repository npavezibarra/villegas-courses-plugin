document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('vil-lesson-toggle');
  const backdrop = document.getElementById('vil-lesson-backdrop');
  const body = document.body;
  const mql = window.matchMedia('(max-width: 970px)');

  if (!btn || !backdrop) return;

  const isMobile = () => mql.matches;

  const open = () => {
    if (!isMobile()) return;
    body.classList.add('vil-nav-open');
    btn.setAttribute('aria-expanded', 'true');
  };

  const close = () => {
    body.classList.remove('vil-nav-open');
    btn.setAttribute('aria-expanded', 'false');
  };

  btn.addEventListener('click', () => {
    body.classList.contains('vil-nav-open') ? close() : open();
  });

  backdrop.addEventListener('click', close);

  // Close when a lesson is chosen
  const nav = document.getElementById('lesson-navigation');
  if (nav) {
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
  }

  // If user resizes to desktop while open, close and restore scroll
  mql.addEventListener('change', e => { if (!e.matches) close(); });
});
