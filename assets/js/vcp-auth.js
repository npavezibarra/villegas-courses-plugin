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
    const logoutBtn = e.target.closest('.vcp-auth-logout');
    if (logoutBtn) {
      e.preventDefault();

      if (typeof VCP_AUTH === 'undefined') {
        window.location.reload();
        return;
      }

      fetch(VCP_AUTH.ajax, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'vcp_auth_logout',
          nonce: VCP_AUTH.nonce
        }).toString()
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
      if (typeof VCP_AUTH !== 'undefined' && VCP_AUTH.google_url) {
        window.location.href = VCP_AUTH.google_url;
      }
      return;
    }

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

  document.addEventListener('submit', async e => {
    const form = e.target.closest('#vcp-login, #vcp-register');
    if (!form) return;
    e.preventDefault();

    if (typeof VCP_AUTH === 'undefined') {
      return;
    }

    let token = '';
    if (window.grecaptcha && VCP_AUTH.captcha_key !== 'YOUR_SITE_KEY') {
      try {
        token = await grecaptcha.execute(VCP_AUTH.captcha_key, { action: 'submit' });
      } catch (err) {
        console.error('Captcha failed', err);
      }
    }

    const data = new URLSearchParams(new FormData(form));
    const isLogin = form.id === 'vcp-login';
    data.delete('action');
    data.delete('nonce');
    data.append('action', isLogin ? 'vcp_auth_login' : 'vcp_auth_register');
    data.append('nonce', VCP_AUTH.nonce);
    if (token) {
      data.append('captcha_token', token);
    }

    fetch(VCP_AUTH.ajax, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data.toString(),
    })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          window.location.reload();
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
