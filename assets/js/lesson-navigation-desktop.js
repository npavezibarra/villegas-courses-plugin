(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const nav = document.getElementById('lesson-navigation');
    const sentinel = document.getElementById('lesson-nav-sentinel');
    const navScrollArea = nav ? nav.querySelector('.nav-scroll-area') : null;
    const navTitle = nav ? nav.querySelector('.nav-title') : null;
    const navColumn = nav ? nav.closest('.lesson-navigation-column') : null;
    const lessonContent = document.getElementById('lesson-content');

    let contentSentinel = null;

    const headerSelectors = [
      'header[role="banner"]',
      'header.wp-block-template-part',
      'header.site-header',
    ];

    const findHeaderElement = () => {
      for (let index = 0; index < headerSelectors.length; index += 1) {
        const candidate = document.querySelector(headerSelectors[index]);
        if (candidate) {
          return candidate;
        }
      }
      return null;
    };

    let headerElement = findHeaderElement();

    if (typeof window.IntersectionObserver !== 'function') {
      return;
    }

    if (!nav || !sentinel || !navScrollArea || !navColumn || !lessonContent) {
      return;
    }

    contentSentinel = lessonContent.querySelector('[data-lesson-content-sentinel]');

    if (!contentSentinel) {
      contentSentinel = document.createElement('div');
      contentSentinel.setAttribute('data-lesson-content-sentinel', 'true');
      contentSentinel.setAttribute('aria-hidden', 'true');
      lessonContent.insertBefore(contentSentinel, lessonContent.firstChild || null);
    }

    const desktopQuery = window.matchMedia('(min-width: 970px)');
    const state = {
      active: false,
      fixed: false,
      lockedTop: 0,
      lockedLeft: 0,
      lockedWidth: 0,
      adminOffset: 0,
      headerHeight: 0,
      observingHeader: false,
      observingContent: false,
      headerHidden: false,
      contentAtTop: true,
    };

    let observer = null;
    let observerRootMargin = null;

    let touchStartY = null;

    const isDesktop = () => desktopQuery.matches;

    const measureAdminOffset = () => {
      const adminBar = document.getElementById('wpadminbar');
      if (!adminBar) {
        return 0;
      }

      const adminStyles = window.getComputedStyle(adminBar);
      if (adminStyles.position === 'fixed') {
        return adminBar.offsetHeight;
      }

      return 0;
    };

    const ensureHeaderElement = () => {
      if (headerElement && document.body.contains(headerElement)) {
        return headerElement;
      }

      headerElement = findHeaderElement();
      return headerElement;
    };

    const measureHeaderHeight = () => {
      const candidate = ensureHeaderElement();
      if (!candidate) {
        return 0;
      }

      const rect = candidate.getBoundingClientRect();
      if (rect.height > 0) {
        return rect.height;
      }

      return candidate.offsetHeight || 0;
    };

    const refreshOffsets = () => {
      state.adminOffset = measureAdminOffset();
      state.headerHeight = measureHeaderHeight();
    };

    const computeFixedTop = () => {
      return state.adminOffset;
    };

    const teardownObserver = () => {
      if (observer) {
        observer.disconnect();
        observer = null;
      }

      state.observingHeader = false;
      state.observingContent = false;
      observerRootMargin = null;
    };

    const setupObserver = () => {
      const rootMargin = '-' + state.adminOffset + 'px 0px 0px 0px';

      if (observer && observerRootMargin === rootMargin) {
        return;
      }

      teardownObserver();

      observerRootMargin = rootMargin;

      observer = new IntersectionObserver((entries) => {
        if (!state.active) {
          return;
        }

        let changed = false;

        entries.forEach((entry) => {
          if (entry.target === sentinel) {
            const headerHidden = entry.boundingClientRect.top <= state.adminOffset;
            if (headerHidden !== state.headerHidden) {
              state.headerHidden = headerHidden;
              changed = true;
            }
            return;
          }

          if (entry.target === contentSentinel) {
            const contentAtTop = entry.boundingClientRect.top >= state.adminOffset + state.headerHeight;
            if (contentAtTop !== state.contentAtTop) {
              state.contentAtTop = contentAtTop;
              changed = true;
            }
          }
        });

        if (changed) {
          syncFixedState();
        }
      }, { rootMargin, threshold: [0, 1] });

      state.observingHeader = false;
      state.observingContent = false;
    };

    const startObserving = () => {
      if (!observer) {
        setupObserver();
      }

      if (!observer) {
        return;
      }

      if (!state.observingHeader) {
        observer.observe(sentinel);
        state.observingHeader = true;
      }

      if (!state.observingContent) {
        observer.observe(contentSentinel);
        state.observingContent = true;
      }
    };

    const stopObserving = () => {
      if (!observer) {
        return;
      }

      if (state.observingHeader) {
        observer.unobserve(sentinel);
        state.observingHeader = false;
      }

      if (state.observingContent) {
        observer.unobserve(contentSentinel);
        state.observingContent = false;
      }
    };

    const clearPositioning = () => {
      nav.style.removeProperty('position');
      nav.style.removeProperty('top');
      nav.style.removeProperty('left');
      nav.style.removeProperty('width');
    };

    const lockCurrentMetrics = () => {
      const columnRect = navColumn.getBoundingClientRect();

      refreshOffsets();

      state.lockedTop = computeFixedTop();
      state.lockedLeft = columnRect.left;
      state.lockedWidth = columnRect.width;
    };

    const applyLockedMetrics = () => {
      nav.style.position = 'fixed';
      nav.style.top = state.lockedTop + 'px';
      nav.style.left = state.lockedLeft + 'px';
      nav.style.width = state.lockedWidth + 'px';
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
    };

    const refreshSentinelStates = () => {
      const headerRect = sentinel.getBoundingClientRect();
      const contentRect = contentSentinel.getBoundingClientRect();

      const headerHidden = headerRect.top <= state.adminOffset;
      const contentAtTop = contentRect.top >= state.adminOffset + state.headerHeight;

      const changed = headerHidden !== state.headerHidden || contentAtTop !== state.contentAtTop;

      state.headerHidden = headerHidden;
      state.contentAtTop = contentAtTop;

      return changed;
    };

    const releaseFixed = () => {
      if (!state.fixed) {
        updateListHeight();
        return;
      }

      state.fixed = false;
      state.lockedTop = 0;
      state.lockedLeft = 0;
      state.lockedWidth = 0;
      nav.classList.remove('is-fixed');
      clearPositioning();
      sentinel.style.height = '0px';
      updateListHeight();
    };

    const applyFixed = () => {
      if (!state.active || state.fixed) {
        return;
      }

      const navHeight = nav.offsetHeight;

      lockCurrentMetrics();
      applyLockedMetrics();
      nav.classList.add('is-fixed');
      state.fixed = true;
      sentinel.style.height = navHeight + 'px';
      updateListHeight();
    };

    const syncFixedState = (options = {}) => {
      const { forceUpdate = false } = options;

      if (!state.active) {
        return;
      }

      const shouldBeFixed = state.headerHidden && !state.contentAtTop;

      if (shouldBeFixed) {
        if (!state.fixed) {
          applyFixed();
          return;
        }

        if (forceUpdate) {
          lockCurrentMetrics();
          applyLockedMetrics();
          updateListHeight();
        }

        return;
      }

      if (!state.fixed) {
        if (forceUpdate) {
          updateListHeight();
        }
        return;
      }

      releaseFixed();
    };

    const refreshFixedState = (options = {}) => {
      if (!state.active) {
        return;
      }

      const { forceUpdate = false } = options;
      const changed = refreshSentinelStates();

      syncFixedState({ forceUpdate: forceUpdate || (changed && state.fixed) });
    };

    const enable = () => {
      if (state.active) {
        return;
      }

      state.active = true;
      refreshOffsets();
      setupObserver();
      startObserving();
      sentinel.style.height = '0px';
      refreshFixedState({ forceUpdate: true });
      updateListHeight();
    };

    const disable = () => {
      if (!state.active) {
        return;
      }

      state.active = false;
      stopObserving();
      teardownObserver();
      releaseFixed();
      navScrollArea.style.removeProperty('max-height');
      sentinel.style.height = '0px';
      state.headerHidden = false;
      state.contentAtTop = true;
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
      refreshOffsets();
      setupObserver();

      if (!isDesktop()) {
        disable();
        return;
      }

      if (!state.active) {
        enable();
        return;
      }

      startObserving();
      refreshFixedState({ forceUpdate: true });
    });

    window.addEventListener('scroll', () => {
      if (!state.active) {
        return;
      }

      refreshFixedState();

      if (state.fixed) {
        const previousLeft = state.lockedLeft;
        const previousWidth = state.lockedWidth;

        lockCurrentMetrics();

        if (
          Math.abs(previousLeft - state.lockedLeft) > 0.5 ||
          Math.abs(previousWidth - state.lockedWidth) > 0.5
        ) {
          applyLockedMetrics();
        } else {
          state.lockedTop = computeFixedTop();
          nav.style.top = state.lockedTop + 'px';
        }

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
