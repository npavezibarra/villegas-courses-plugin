(function ($) {
  $(function () {
    const $header = $('.quiz-intro-header');
    if (!$header.length) return;

    const $container = $('.wpProQuiz_content');
    if (!$container.length) return;

    const show = () => $header.stop(true, true).fadeIn(120);
    const hide = () => $header.stop(true, true).fadeOut(120);

    // Core rule-set: decide visibility based on what's currently shown.
    function applyRule() {
      const hasResults = $container.find('.wpProQuiz_results:visible').length > 0;
      const hasIntro = $container.find('.wpProQuiz_text:visible').length > 0; // intro/description
      const hasQuestions =
        $container.find('.wpProQuiz_questionList:visible').length > 0 ||
        $container.find('.wpProQuiz_question:visible').length > 0;

      if (hasResults || hasIntro) {
        show();
      } else if (hasQuestions) {
        hide();
      } else {
        // Fallback: when in doubt (e.g., loading/spinner), keep hidden to avoid flashes.
        hide();
      }
    }

    // Initial state on page load (intro is visible → header should be visible).
    applyRule();

    // React to LearnDash events.
    $(document)
      .on('learndash-quiz-started', function () {
        // User clicked "Start" → enter question flow.
        hide();
      })
      .on('learndash-quiz-finished', function () {
        // Results rendered → show again and stop observing further DOM swaps.
        show();
        if (observer) observer.disconnect();
      });

    // Observe AJAX-driven DOM swaps inside the quiz (next/prev question, results load, etc.).
    const observer = new MutationObserver(() => {
      applyRule();

      // If results are visible, we can stop listening (prevents re-hiding after results).
      if ($container.find('.wpProQuiz_results:visible').length > 0) {
        observer.disconnect();
      }
    });

    observer.observe($container.get(0), {
      childList: true,
      attributes: true,
      subtree: true,
    });
  });
})(jQuery);
