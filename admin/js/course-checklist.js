(function ($) {
  'use strict';

  const localized = typeof window.villegas_checklist !== 'undefined' ? window.villegas_checklist : {};
  const ajaxUrl = localized.ajaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '');
  const messages = localized.i18n || {};

  function closeMenus() {
    $('.villegas-dropdown .dropdown-menu').removeClass('show');
    $('.create-dropdown').attr('aria-expanded', 'false');
  }

  $(document).on('click', '.create-dropdown', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const $menu = $(this).siblings('.dropdown-menu');
    const isVisible = $menu.hasClass('show');
    closeMenus();
    if (!isVisible) {
      $menu.addClass('show');
      $(this).attr('aria-expanded', 'true');
    } else {
      $(this).attr('aria-expanded', 'false');
    }
  });

  $(document).on('click', function () {
    closeMenus();
  });

  $(document).on('click', '.villegas-dropdown .dropdown-menu', function (event) {
    event.stopPropagation();
  });

  function handleAjaxError(response) {
    let message = messages.ajaxError || 'An unexpected error occurred. Please try again.';

    if (response && response.data) {
      if (typeof response.data === 'string') {
        message = response.data;
      } else if (response.data.message) {
        message = response.data.message;
      }
    }

    window.alert(message);
  }

  $(document).on('click', '.action-create-quiz', function (event) {
    event.preventDefault();
    closeMenus();

    if (!ajaxUrl) {
      return;
    }

    $.post(
      ajaxUrl,
      {
        action: 'villegas_create_quiz',
        _ajax_nonce: localized.nonce,
      }
    )
      .done(function (response) {
        if (response && response.success && response.data && response.data.redirect) {
          window.location.href = response.data.redirect;
          return;
        }

        handleAjaxError(response);
      })
      .fail(function (jqXHR) {
        handleAjaxError(jqXHR.responseJSON);
      });
  });

  $(document).on('click', '.action-clone-quiz', function (event) {
    event.preventDefault();
    closeMenus();

    if (!ajaxUrl) {
      return;
    }

    const $button = $(this);
    const courseId = $button.data('course-id');
    const quizType = $button.data('quiz-type');

    $.post(
      ajaxUrl,
      {
        action: 'villegas_clone_opposite_quiz',
        course_id: courseId,
        type: quizType,
        _ajax_nonce: localized.nonce,
      }
    )
      .done(function (response) {
        if (response && response.success) {
          const message = response.data && response.data.message ? response.data.message : 'Quiz cloned successfully.';
          window.alert(message);
          window.location.reload();
          return;
        }

        handleAjaxError(response);
      })
      .fail(function (jqXHR) {
        handleAjaxError(jqXHR.responseJSON);
      });
  });

  $(document).on('submit', '.villegas-product-form', function (event) {
    event.preventDefault();

    if (!ajaxUrl) {
      return;
    }

    const $form = $(this);
    const $submit = $form.find('[type="submit"]');
    const $priceField = $form.find('input[name="price"]');
    const courseId = $form.data('course-id');
    const cleaned = String($priceField.val()).trim();

    if (!/^\d+$/.test(cleaned)) {
      window.alert(messages.priceError || 'Please enter a valid integer price greater than zero.');
      $priceField.trigger('focus');
      return;
    }

    const price = parseInt(cleaned, 10);

    if (!price) {
      window.alert(messages.priceError || 'Please enter a valid integer price greater than zero.');
      $priceField.trigger('focus');
      return;
    }

    $submit.prop('disabled', true);

    $.post(
      ajaxUrl,
      {
        action: 'villegas_create_product',
        course_id: courseId,
        price: price,
        _ajax_nonce: localized.nonce,
      }
    )
      .done(function (response) {
        if (response && response.success) {
          const message = response.data && response.data.message ? response.data.message : 'Product created successfully.';
          window.alert(message);
          window.location.reload();
          return;
        }

        handleAjaxError(response);
      })
      .fail(function (jqXHR) {
        handleAjaxError(jqXHR.responseJSON);
      })
      .always(function () {
        $submit.prop('disabled', false);
      });
  });
})(jQuery);
