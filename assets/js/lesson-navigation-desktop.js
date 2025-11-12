(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const nav = document.getElementById('lesson-navigation');
    const sentinel = document.getElementById('lesson-nav-sentinel');
    const navScrollArea = nav ? nav.querySelector('.nav-scroll-area') : null;
    const navTitle = nav ? nav.querySelector('.nav-title') : null;
    const navColumn = sentinel ? sentinel.parentElement : null;

    if (typeof window.IntersectionObserver !== 'function') {
      return;
    }

    if (!nav || !sentinel || !navScrollArea || !navColumn) {
      return;
    }

    const desktopQuery = window.matchMedia('(min-width: 970px)');
    const state = {
      active: false,
      fixed: false,
      lockedTop: 0,
    };

    let touchStartY = null;

    const isDesktop = () => desktopQuery.matches;

    const clearPositioning = () => {
      nav.style.removeProperty('position');
      nav.style.removeProperty('top');
      nav.style.removeProperty('left');
      nav.style.removeProperty('width');
    };

    const updateListHeight = () => {
      if (!state.active) {
        navScrollArea.style.removeProperty('max-height');
        return;
      }

      const topOffset = state.fixed ? state.lockedTop : nav.getBoundingClientRect().top;
      const navStyles = window.getComputedStyle(nav);
      const paddingTop = parseFloat(navStyles.paddingTop) || 0;
      const paddingBottom = parseFloat(navStyles.paddingBottom) || 0;
      const borderTop = parseFloat(navStyles.borderTopWidth) || 0;
      const borderBottom = parseFloat(navStyles.borderBottomWidth) || 0;

      let extraSpace = paddingTop + paddingBottom + borderTop + borderBottom;

      if (navTitle) {
        const titleStyles = window.getComputedStyle(navTitle);
        extraSpace += navTitle.offsetHeight;
        extraSpace += parseFloat(titleStyles.marginTop) || 0;
        extraSpace += parseFloat(titleStyles.marginBottom) || 0;
      }

      const scrollStyles = window.getComputedStyle(navScrollArea);
      extraSpace += parseFloat(scrollStyles.marginTop) || 0;
      extraSpace += parseFloat(scrollStyles.marginBottom) || 0;

      const available = window.innerHeight - topOffset - extraSpace;
      const height = Math.max(0, Math.floor(available));
      navScrollArea.style.maxHeight = height > 0 ? height + 'px' : '0px';

      if (state.fixed) {
        sentinel.style.height = nav.offsetHeight + 'px';
      }
    };

    const releaseFixed = () => {
      if (!state.fixed) {
        return;
      }

      state.fixed = false;
      state.lockedTop = 0;
      nav.classList.remove('is-fixed');
      clearPositioning();
      sentinel.style.height = '0px';
      updateListHeight();
    };

    const applyFixed = () => {
      if (!state.active || state.fixed) {
        return;
      }

      const columnRect = navColumn.getBoundingClientRect();
      const navRect = nav.getBoundingClientRect();

      state.lockedTop = Math.max(navRect.top, 0);
      nav.style.position = 'fixed';
      nav.style.top = state.lockedTop + 'px';
      nav.style.left = columnRect.left + 'px';
      nav.style.width = columnRect.width + 'px';
      nav.classList.add('is-fixed');
      sentinel.style.height = navRect.height + 'px';
      state.fixed = true;
      updateListHeight();
    };

    const refreshFixedState = () => {
      if (!state.active) {
        return;
      }

      const sentinelRect = sentinel.getBoundingClientRect();
      if (sentinelRect.top < 0) {
        applyFixed();
      } else {
        releaseFixed();
      }
    };

    const observer = new IntersectionObserver((entries) => {
      if (!state.active) {
        return;
      }

      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          releaseFixed();
        } else {
          applyFixed();
        }
      });
    });

    const enable = () => {
      if (state.active) {
        return;
      }

      state.active = true;
      observer.observe(sentinel);
      updateListHeight();
      refreshFixedState();
    };

    const disable = () => {
      if (!state.active) {
        return;
      }

      observer.disconnect();
      state.active = false;
      releaseFixed();
      navScrollArea.style.removeProperty('max-height');
      sentinel.style.height = '0px';
    };

    const handleViewportChange = () => {
      if (isDesktop()) {
        enable();
      } else {
        disable();
      }
    };

    handleViewportChange();

    if (desktopQuery.addEventListener) {
      desktopQuery.addEventListener('change', handleViewportChange);
    } else if (desktopQuery.addListener) {
      desktopQuery.addListener(handleViewportChange);
    }

    window.addEventListener('resize', () => {
      if (!isDesktop()) {
        disable();
        return;
      }

      if (!state.active) {
        enable();
      }

      if (state.fixed) {
        const columnRect = navColumn.getBoundingClientRect();
        state.lockedTop = Math.max(navColumn.getBoundingClientRect().top, 0);
        nav.style.top = state.lockedTop + 'px';
        nav.style.left = columnRect.left + 'px';
        nav.style.width = columnRect.width + 'px';
        sentinel.style.height = nav.offsetHeight + 'px';
      }

      updateListHeight();
      refreshFixedState();
    });

    window.addEventListener('scroll', () => {
      if (!state.active || state.fixed) {
        return;
      }

      updateListHeight();
    });

    const canScrollFurther = (delta) => {
      const atTop = navScrollArea.scrollTop <= 0;
      const atBottom = navScrollArea.scrollTop + navScrollArea.clientHeight >= navScrollArea.scrollHeight - 1;

      if (delta < 0 && atTop) {
        return false;
      }

      if (delta > 0 && atBottom) {
        return false;
      }

      return true;
    };

    navScrollArea.addEventListener('wheel', (event) => {
      if (!state.active) {
        return;
      }

      const deltaY = event.deltaY;
      if (!canScrollFurther(deltaY)) {
        return;
      }

      event.preventDefault();
      navScrollArea.scrollTop += deltaY;
    }, { passive: false });

    navScrollArea.addEventListener('touchstart', (event) => {
      if (!state.active) {
        return;
      }

      const touch = event.touches[0];
      touchStartY = touch ? touch.clientY : null;
    }, { passive: true });

    navScrollArea.addEventListener('touchmove', (event) => {
      if (!state.active || touchStartY === null) {
        return;
      }

      const touch = event.touches[0];
      if (!touch) {
        return;
      }

      const delta = touchStartY - touch.clientY;
      if (!canScrollFurther(delta)) {
        touchStartY = touch.clientY;
        return;
      }

      navScrollArea.scrollTop += delta;
      touchStartY = touch.clientY;
      event.preventDefault();
    }, { passive: false });

    const resetTouch = () => {
      touchStartY = null;
    };

    navScrollArea.addEventListener('touchend', resetTouch, { passive: true });
    navScrollArea.addEventListener('touchcancel', resetTouch, { passive: true });
  });
})();
