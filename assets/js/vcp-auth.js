(function($){
  const qs = s => document.querySelector(s);
  const qsa = s => Array.from(document.querySelectorAll(s));

  function toggleModal(show) {
    const overlay = qs('.vcp-auth-overlay');
    const modal = qs('.vcp-auth-modal');
    if (!overlay || !modal) return;
    overlay.hidden = !show;
    modal.hidden = !show;
    document.body.style.overflow = show ? 'hidden' : '';
  }

  document.addEventListener('click', e => {
    if (e.target.closest('.vcp-auth-open')) toggleModal(true);
    if (e.target.closest('.vcp-auth-close') || e.target.classList.contains('vcp-auth-overlay')) toggleModal(false);
  });

  qsa('.vcp-auth-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      qsa('.vcp-auth-tab').forEach(t => t.classList.remove('is-active'));
      qsa('.vcp-auth-panel').forEach(p => p.classList.remove('is-active'));
      tab.classList.add('is-active');
      const panel = qs(tab.dataset.target);
      if (panel) panel.classList.add('is-active');
    });
  });
})(jQuery);
