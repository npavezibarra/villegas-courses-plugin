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

  const initGlobalAuthButton = () => {
    const authButton = document.getElementById('vcp-auth-button');
    if (!authButton) {
      return;
    }

    const applyState = (loggedIn) => {
      if (authButton.disabled) {
        authButton.disabled = false;
      }

      if (config) {
        config.isLoggedIn = loggedIn;
        config.isUser = loggedIn;
      }

      if (loggedIn) {
        authButton.textContent = 'Salir';
        authButton.classList.remove('ingresar');
        authButton.classList.add('salir');
      } else {
        authButton.textContent = 'Ingresar';
        authButton.classList.remove('salir');
        authButton.classList.add('ingresar');
      }
    };

    applyState(Boolean(config && (config.isLoggedIn || config.isUser)));

    authButton.addEventListener('click', async (e) => {
      e.preventDefault();

      if (config && (config.isLoggedIn || config.isUser)) {
        authButton.disabled = true;
        authButton.textContent = 'Saliendo...';

        try {
          const resp = await fetch(config.ajax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'vcp_auth_logout',
              nonce: config.nonce,
            }).toString(),
          });

          const json = await resp.json();

          if (json.success) {
            window.location.href = config.logoutRedirect || window.location.href;
            return;
          }

          window.alert('Error al cerrar sesión.');
          applyState(true);
        } catch (err) {
          console.error(err);
          applyState(true);
        } finally {
          authButton.disabled = false;
        }

        return;
      }

      if (typeof showModal === 'function') {
        showModal();
      } else {
        const modalEl = document.querySelector('.vcp-auth-modal');
        const overlayEl = document.querySelector('.vcp-auth-overlay');

        if (modalEl && overlayEl) {
          modalEl.hidden = false;
          overlayEl.hidden = false;
          modalEl.classList.add('is-visible');
          overlayEl.classList.add('is-visible');
        } else {
          console.warn('Modal de autenticación no encontrado en el DOM.');
        }
      }
    });

    document.addEventListener('vcp-login-success', () => {
      applyState(true);
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGlobalAuthButton);
  } else {
    initGlobalAuthButton();
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
          document.dispatchEvent(new CustomEvent('vcp-login-success', { detail: json }));

          hideModal();

          const target = redirectConfig && redirectConfig.redirect
            ? redirectConfig.redirect
            : '';

          if (target) {
            window.location.href = target;
          }

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
