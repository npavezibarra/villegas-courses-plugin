(function ($) {
  $(function () {
    const $header = $('.quiz-page-header');
    if (!$header.length) return;

    const $container = $('.wpProQuiz_content');
    if (!$container.length) return;

    const show = () => $header.stop(true, true).fadeIn(120);
    const hide = () => $header.stop(true, true).fadeOut(120);

    function applyRule() {
      const hasResults = $container.find('.wpProQuiz_results:visible').length > 0;
      const hasIntro = $container.find('.wpProQuiz_text:visible').length > 0;
      const hasQuestions =
        $container.find('.wpProQuiz_questionList:visible').length > 0 ||
        $container.find('.wpProQuiz_question:visible').length > 0;

      if (hasResults || hasIntro) {
        show();
      } else if (hasQuestions) {
        hide();
      } else {
        hide();
      }
    }

    applyRule();

    $(document)
      .on('learndash-quiz-started', function () {
        hide();
      })
      .on('learndash-quiz-finished', function () {
        show();
        if (observer) observer.disconnect();
      });

    const observer = new MutationObserver(() => {
      applyRule();

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
