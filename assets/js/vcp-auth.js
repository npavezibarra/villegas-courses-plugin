(function ($) {
  const qs = (selector, ctx = document) => ctx.querySelector(selector);
  const qsa = (selector, ctx = document) => Array.from(ctx.querySelectorAll(selector));
  const getModalElements = () => ({
    modal: qs('.vcp-auth-modal'),
    overlay: qs('.vcp-auth-overlay'),
  });
  const config = typeof VCP_AUTH !== 'undefined' ? VCP_AUTH : null;
  const redirectConfig = typeof VCP_AUTH_REDIRECT !== 'undefined' ? VCP_AUTH_REDIRECT : null;
  let lastFocus = null;
  const loginValidationTimers = new WeakMap();

  function showModal() {
    let { modal, overlay } = getModalElements();

    if (!modal || !overlay) {
      // Retry in case the DOM was updated asynchronously.
      const fresh = getModalElements();
      modal = fresh.modal;
      overlay = fresh.overlay;

      if (!modal || !overlay) {
        console.warn('Auth modal not found in DOM.');
        return;
      }
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
    const { modal, overlay } = getModalElements();

    if (!modal || !overlay) {
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
      authButton.disabled = false;

      if (config) {
        config.isLoggedIn = loggedIn;
        config.isUser = loggedIn;
      }

      authButton.textContent = loggedIn ? 'Salir' : 'Ingresar';
      authButton.classList.toggle('is-logged-in', loggedIn);
      authButton.classList.toggle('is-logged-out', !loggedIn);
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
            }),
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
          authButton.textContent = 'Salir';
        }

        return;
      }

      if (typeof showModal === 'function') {
        showModal();
      } else {
        const { modal: modalEl, overlay: overlayEl } = getModalElements();

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

  const initModalDelegates = () => {
    if (initModalDelegates.initialized) {
      return;
    }

    initModalDelegates.initialized = true;

    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('#vcp-auth-button, .vcp-auth-open');
      if (!trigger) {
        return;
      }

      if (trigger.id === 'vcp-auth-button' && config && (config.isLoggedIn || config.isUser)) {
        return;
      }

      e.preventDefault();

      let { modal, overlay } = getModalElements();

      if (!modal || !overlay) {
        const fresh = getModalElements();
        modal = fresh.modal;
        overlay = fresh.overlay;
      }

      if (modal && overlay) {
        showModal();
      } else {
        console.warn('Auth modal not found in DOM.');
      }
    });

    document.addEventListener('click', (e) => {
      if (!e.target.matches('.vcp-auth-close, .vcp-auth-overlay')) {
        return;
      }

      hideModal();
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModalDelegates);
  } else {
    initModalDelegates();
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
        .catch(() => window.alert('No se pudo cerrar sesión.'));

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

    const forgotLink = e.target.closest('#vcp-forgot-toggle');
    if (forgotLink) {
      e.preventDefault();
      const modalRoot = forgotLink.closest('.vcp-auth-modal');
      if (modalRoot) {
        const loginPanel = modalRoot.querySelector('#vcp-login');
        const resetPanel = modalRoot.querySelector('#vcp-reset');
        if (loginPanel && resetPanel) {
          loginPanel.classList.remove('is-active');
          resetPanel.classList.add('is-active');
        }
        const tabs = modalRoot.querySelectorAll('.vcp-auth-tab');
        tabs.forEach((tab, index) => {
          if (index === 0) {
            tab.classList.add('is-active');
          } else {
            tab.classList.remove('is-active');
          }
        });
      }
      return;
    }

    const backToLogin = e.target.closest('#vcp-back-to-login');
    if (backToLogin) {
      e.preventDefault();
      const modalRoot = backToLogin.closest('.vcp-auth-modal');
      if (modalRoot) {
        const loginPanel = modalRoot.querySelector('#vcp-login');
        const resetPanel = modalRoot.querySelector('#vcp-reset');
        if (loginPanel && resetPanel) {
          resetPanel.classList.remove('is-active');
          loginPanel.classList.add('is-active');
        }
        const tabs = modalRoot.querySelectorAll('.vcp-auth-tab');
        tabs.forEach((tab, index) => {
          if (index === 0) {
            tab.classList.add('is-active');
          } else {
            tab.classList.remove('is-active');
          }
        });
      }
      return;
    }

  });

  document.addEventListener('keydown', e => {
    const modal = qs('.vcp-auth-modal');
    if (!modal) {
      return;
    }

    if (e.key === 'Escape' && !modal.hidden) {
      e.preventDefault();
      hideModal();
    }
  });

  document.addEventListener('keydown', e => {
    const modal = qs('.vcp-auth-modal');
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

  document.addEventListener('click', (e) => {
    const tab = e.target.closest('.vcp-auth-tab');
    if (!tab) return;

    e.preventDefault();
    const modal = tab.closest('.vcp-auth-modal');
    if (!modal) return;

    // Deactivate all tabs and panels in this modal
    modal.querySelectorAll('.vcp-auth-tab').forEach(t => t.classList.remove('is-active'));
    modal.querySelectorAll('.vcp-auth-panel').forEach(p => p.classList.remove('is-active'));

    // Activate clicked tab
    tab.classList.add('is-active');

    // Activate target panel
    const targetSelector = tab.dataset.target;
    if (targetSelector) {
      const panel = modal.querySelector(targetSelector);
      if (panel) panel.classList.add('is-active');
    }
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
        console.error('Error al verificar el captcha', err);
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
    const messageBox = form.querySelector('.vcp-auth-error');
    if (messageBox) {
      messageBox.style.display = 'none';
      messageBox.textContent = '';
      messageBox.style.color = '#c62828';
    }

    const loginFieldMessage = form.querySelector('.vcp-login-error');
    if (loginFieldMessage) {
      loginFieldMessage.style.display = 'none';
    }

    try {
      const response = await fetch(config.ajax, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data.toString(),
      });
      const json = await response.json();

      if (json.success) {
        if (json.data && json.data.confirmation_required) {
          if (messageBox) {
            messageBox.style.display = 'block';
            messageBox.style.color = '#0a8f08';
            messageBox.textContent = 'Cuenta creada. Por favor revisa tu correo para confirmar tu cuenta.';
          } else {
            window.alert('Cuenta creada. Por favor revisa tu correo para confirmar tu cuenta.');
          }
          form.reset();
          return;
        }

        if (messageBox) {
          messageBox.style.display = 'none';
          messageBox.textContent = '';
        }

        document.dispatchEvent(new CustomEvent('vcp-login-success', { detail: json }));

        hideModal();

        const target = redirectConfig && redirectConfig.redirect
          ? redirectConfig.redirect
          : '';

        if (target) {
          window.location.href = target;
        }

        return;
      }

      const message = typeof json.data === 'string'
        ? json.data
        : (json.data && json.data.message) ? json.data.message : 'Autenticación fallida.';

      if (messageBox) {
        messageBox.textContent = message;
        messageBox.style.display = 'block';
        messageBox.style.color = '#c62828';
      } else {
        window.alert(message);
      }
    } catch (error) {
      console.error('Error en la autenticación', error);
      window.alert('Error de red.');
    }
  });

  document.addEventListener('input', e => {
    if (!e.target.matches('#vcp-login-user')) {
      return;
    }

    if (!config) {
      return;
    }

    const input = e.target;
    const field = input.closest('.vcp-field');
    const errorMsg = field ? field.querySelector('.vcp-login-error') : null;
    if (!errorMsg) {
      return;
    }

    const existing = loginValidationTimers.get(input);
    if (existing) {
      clearTimeout(existing);
    }

    errorMsg.style.display = 'none';

    const value = input.value.trim();
    if (!value) {
      return;
    }

    const timer = window.setTimeout(async () => {
      try {
        const resp = await fetch(config.ajax, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'vcp_check_user_exists',
            user_check: value,
          }),
        });
        const json = await resp.json();
        if (!json.success && json.data && json.data.message) {
          errorMsg.textContent = json.data.message;
          errorMsg.style.display = 'block';
        } else {
          errorMsg.style.display = 'none';
        }
      } catch (err) {
        console.error('Error al validar el usuario', err);
      }
    }, 500);

    loginValidationTimers.set(input, timer);
  });

  document.addEventListener('submit', async e => {
    const form = e.target;
    if (!form || !form.matches('#vcp-reset')) {
      return;
    }

    e.preventDefault();

    if (!config) {
      return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton ? submitButton.textContent : '';
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Enviando...';
    }

    const messageBox = form.querySelector('.vcp-auth-error');
    if (messageBox) {
      messageBox.style.display = 'none';
      messageBox.textContent = '';
    }

    const data = new URLSearchParams(new FormData(form));
    data.set('action', 'vcp_reset_password');
    if (config.nonce) {
      data.set('nonce', config.nonce);
    }

    try {
      const resp = await fetch(config.ajax, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data.toString(),
      });
      const json = await resp.json();

      if (messageBox) {
        messageBox.style.display = 'block';
        messageBox.style.color = json.success ? '#0a8f08' : '#c62828';
        const text = json.data && json.data.message
          ? json.data.message
          : (json.success ? '' : 'No se pudo enviar el correo. Intenta más tarde.');
        messageBox.textContent = text;
      }

      if (json.success) {
        form.reset();
      }
    } catch (err) {
      console.error('Error al solicitar el restablecimiento', err);
      if (messageBox) {
        messageBox.style.display = 'block';
        messageBox.style.color = '#c62828';
        messageBox.textContent = 'No se pudo enviar el correo. Intenta más tarde.';
      } else {
        window.alert('No se pudo enviar el correo. Intenta más tarde.');
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalText || 'Enviar enlace';
      }
    }
  });
})(jQuery);
