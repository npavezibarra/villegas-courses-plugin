jQuery(document).ready(function ($) {
    const titleBox = $('.quiz-result-header');

    if (!titleBox.length) {
        return;
    }

    // Hide titles by default.
    titleBox.hide();

    // Hide during quiz start.
    $(document).on('learndash-quiz-started', function () {
        titleBox.hide();
    });

    // Show only when the quiz is finished.
    $(document).on('learndash-quiz-finished', function () {
        titleBox.fadeIn();
    });

    // Optional: stop MutationObserver once results are visible.
    const observer = new MutationObserver(() => {
        if ($('.wpProQuiz_results').is(':visible')) {
            observer.disconnect();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
