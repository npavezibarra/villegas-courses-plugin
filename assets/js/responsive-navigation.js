(function () {
  const CLASS_OPEN = 'is-menu-open';

  function resolveContainer(button) {
    const targetId = button.getAttribute('aria-controls');
    if (targetId) {
      const target = document.getElementById(targetId);
      if (target) {
        return target;
      }
    }

    const navigationRoot = button.closest('.wp-block-navigation');
    if (navigationRoot) {
      const inlineContainer = navigationRoot.querySelector('.wp-block-navigation__responsive-container');
      if (inlineContainer) {
        return inlineContainer;
      }
    }

    return document.querySelector('.wp-block-navigation__responsive-container');
  }

  function closeContainer(container) {
    container.classList.remove(CLASS_OPEN);
    container.setAttribute('aria-hidden', 'true');
  }

  function bindCloseButton(container) {
    const closeButton = container.querySelector('.wp-block-navigation__responsive-container-close');
    if (!closeButton || closeButton.dataset.vcpNavBound === 'true') {
      return;
    }

    closeButton.dataset.vcpNavBound = 'true';
    closeButton.addEventListener('click', function () {
      closeContainer(container);
    });
  }

  function openContainer(button) {
    const container = resolveContainer(button);
    if (!container) {
      return;
    }

    container.classList.add(CLASS_OPEN);
    container.setAttribute('aria-hidden', 'false');
    bindCloseButton(container);
  }

  function bindOpenButtons() {
    const openButtons = document.querySelectorAll('.wp-block-navigation__responsive-container-open');
    if (!openButtons.length) {
      return;
    }

    openButtons.forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        openContainer(button);
      });

      button.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          openContainer(button);
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', bindOpenButtons);
})();
