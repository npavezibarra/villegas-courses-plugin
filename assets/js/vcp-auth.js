(function($){
  const qs = (selector, ctx = document) => ctx.querySelector(selector);
  const qsa = (selector, ctx = document) => Array.from(ctx.querySelectorAll(selector));
  const overlay = qs('.vcp-auth-overlay');
  const modal = qs('.vcp-auth-modal');
  const config = typeof VCP_AUTH !== 'undefined' ? VCP_AUTH : null;
  const redirectConfig = typeof VCP_AUTH_REDIRECT !== 'undefined' ? VCP_AUTH_REDIRECT : null;
  let lastFocus = null;

  function showModal() {
    if (!overlay || !modal) {
      return;
    }

    lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    overlay.hidden = false;
    modal.hidden = false;

    window.setTimeout(() => {
      overlay.classList.add('is-visible');
      modal.classList.add('is-visible');

      const firstInput = modal.querySelector('input, button, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (firstInput && typeof firstInput.focus === 'function') {
        firstInput.focus();
      }
    }, 10);

    document.body.style.overflow = 'hidden';
  }

  function hideModal() {
    if (!overlay || !modal) {
      return;
    }

    overlay.classList.remove('is-visible');
    modal.classList.remove('is-visible');

    window.setTimeout(() => {
      overlay.hidden = true;
      modal.hidden = true;
      document.body.style.overflow = '';

      if (lastFocus && typeof lastFocus.focus === 'function' && document.contains(lastFocus)) {
        lastFocus.focus();
      }
    }, 250);
  }

  document.addEventListener('click', e => {
    const logoutBtn = e.target.closest('.vcp-auth-logout');
    if (logoutBtn) {
      e.preventDefault();

      if (!config) {
        window.location.reload();
        return;
      }

      fetch(config.ajax, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'vcp_auth_logout',
          nonce: config.nonce,
        }).toString(),
      })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            window.location.reload();
          }
        })
        .catch(() => window.alert('Logout failed.'));

      return;
    }

    const googleBtn = e.target.closest('.vcp-google-login');
    if (googleBtn) {
      e.preventDefault();

      if (config && config.google_url && config.google_id) {
        window.location.href = config.google_url;
      }

      return;
    }

    if (e.target.closest('.vcp-auth-open')) {
      showModal();
      return;
    }

    if (!overlay || !modal) {
      return;
    }

    if (e.target.closest('.vcp-auth-close') || e.target === overlay) {
      hideModal();
    }
  });

  document.addEventListener('keydown', e => {
    if (!modal) {
      return;
    }

    if (e.key === 'Escape' && !modal.hidden) {
      e.preventDefault();
      hideModal();
    }
  });

  document.addEventListener('keydown', e => {
    if (!modal || !modal.classList.contains('is-visible') || e.key !== 'Tab') {
      return;
    }

    const focusable = qsa('a[href], button, textarea, input, select, [tabindex]:not([tabindex="-1"])', modal)
      .filter(el => !el.disabled && el.tabIndex !== -1 && (el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length));

    if (focusable.length === 0) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      last.focus();
      e.preventDefault();
    } else if (!e.shiftKey && document.activeElement === last) {
      first.focus();
      e.preventDefault();
    }
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

  document.addEventListener('submit', async e => {
    const form = e.target.closest('#vcp-login, #vcp-register');
    if (!form) return;
    e.preventDefault();

    if (!config) {
      return;
    }

    let token = '';
    if (window.grecaptcha && config.recaptcha_key) {
      try {
        token = await grecaptcha.execute(config.recaptcha_key, { action: 'submit' });
      } catch (err) {
        console.error('Captcha failed', err);
      }
    }

    const data = new URLSearchParams(new FormData(form));
    const isLogin = form.id === 'vcp-login';
    data.delete('action');
    data.delete('nonce');
    data.append('action', isLogin ? 'vcp_auth_login' : 'vcp_auth_register');
    data.append('nonce', config.nonce);
    if (token) {
      data.append('captcha_token', token);
    }

    fetch(config.ajax, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data.toString(),
    })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          const target = redirectConfig && redirectConfig.redirect
            ? redirectConfig.redirect
            : window.location.href;
          window.location.href = target;
          return;
        } else {
          const msg = json.data || 'Authentication failed.';
          form.querySelector('.vcp-auth-error')?.remove();
          const err = document.createElement('div');
          err.className = 'vcp-auth-error';
          err.textContent = msg;
          form.appendChild(err);
        }
      })
      .catch(() => window.alert('Network error.'));
  });
})(jQuery);
