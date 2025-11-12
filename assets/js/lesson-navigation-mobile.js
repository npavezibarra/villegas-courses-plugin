document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('vil-lesson-toggle');
  const backdrop = document.getElementById('vil-lesson-backdrop');
  const nav = document.getElementById('lesson-navigation');
  const navColumn = nav ? nav.closest('.lesson-navigation-column') : null;
  const sentinel = document.getElementById('lesson-nav-sentinel');
  const body = document.body;
  const mql = window.matchMedia('(max-width: 970px)');

  let headerHeight = 0;
  let isFixed = false;
  let lastSentinelVisible = true;

  function isMobile() {
    return mql.matches;
  }

  function disableSticky() {
    return isMobile() || body.classList.contains('vil-nav-open');
  }

  function updateHeaderHeight() {
    const header =
      document.querySelector('header.wp-block-template-part') ||
      document.querySelector('header.site-header') ||
      document.querySelector('header.wp-site-blocks > header') ||
      document.querySelector('header');

    if (!header) {
      headerHeight = 0;
      return;
    }

    const rect = header.getBoundingClientRect();
    if (rect.height > 0) {
      headerHeight = rect.height;
    }
  }

  function applyFixedStyles() {
    if (!nav || !navColumn) return;

    updateHeaderHeight();

    const columnRect = navColumn.getBoundingClientRect();
    const navHeight = nav.offsetHeight;

    if (navHeight > 0) {
      navColumn.style.minHeight = `${navHeight}px`;
    }

    nav.style.position = 'fixed';
    nav.style.top = `${Math.max(0, headerHeight)}px`;
    nav.style.left = `${columnRect.left}px`;
    nav.style.width = `${columnRect.width}px`;
    nav.style.maxHeight = `calc(100vh - ${Math.max(0, headerHeight)}px)`;
    nav.style.right = '';
    nav.style.bottom = '';
    nav.style.zIndex = '1000';
    nav.classList.add('lesson-navigation--fixed');

    const updatedHeight = nav.offsetHeight;
    if (updatedHeight > 0) {
      navColumn.style.minHeight = `${updatedHeight}px`;
    }

    isFixed = true;
  }

  function releaseFixedStyles() {
    if (!nav || !navColumn) return;

    navColumn.style.minHeight = '';

    if (!isFixed) {
      return;
    }

    nav.style.position = '';
    nav.style.top = '';
    nav.style.left = '';
    nav.style.width = '';
    nav.style.maxHeight = '';
    nav.style.right = '';
    nav.style.bottom = '';
    nav.style.zIndex = '';
    nav.classList.remove('lesson-navigation--fixed');

    isFixed = false;
  }

  function ensureStickyState() {
    if (!nav || !navColumn || !sentinel) return;

    if (disableSticky() || lastSentinelVisible) {
      releaseFixedStyles();
    } else {
      applyFixedStyles();
    }
  }

  function openNav() {
    if (!btn || !isMobile()) return;
    body.classList.add('vil-nav-open');
    btn.setAttribute('aria-expanded', 'true');
    ensureStickyState();
  }

  function closeNav() {
    body.classList.remove('vil-nav-open');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
    }
    ensureStickyState();
  }

  if (btn) {
    btn.addEventListener('click', () => {
      body.classList.contains('vil-nav-open') ? closeNav() : openNav();
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeNav);
  }

  if (nav) {
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', closeNav));
  }

  const handleBreakpointChange = e => {
    if (!e.matches) {
      closeNav();
    } else {
      ensureStickyState();
    }
  };

  if (typeof mql.addEventListener === 'function') {
    mql.addEventListener('change', handleBreakpointChange);
  } else if (typeof mql.addListener === 'function') {
    mql.addListener(handleBreakpointChange);
  }

  if (nav && navColumn && sentinel) {
    updateHeaderHeight();

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        lastSentinelVisible = entry.isIntersecting;

        if (entry.isIntersecting || disableSticky()) {
          releaseFixedStyles();
        } else {
          applyFixedStyles();
        }
      });
    });

    observer.observe(sentinel);

    window.addEventListener('resize', () => {
      updateHeaderHeight();

      if (disableSticky()) {
        releaseFixedStyles();
        return;
      }

      if (isFixed) {
        applyFixedStyles();
      } else {
        ensureStickyState();
      }
    });

    ensureStickyState();
  }
});
