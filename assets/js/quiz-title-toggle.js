/**
 * quiz-title-toggle.js
 * Purpose: Hide the quiz title during questions.
 * Restriction: Do not modify any other quiz logic or UI behavior.
 */

document.addEventListener('DOMContentLoaded', function () {
    const quizCard = document.getElementById('quiz-card');
    if (!quizCard) return;

    const quizTitle = quizCard.querySelector('h2, h3, .quiz-title');
    if (!quizTitle) return;

    // Ensure title is visible at load (pre-start screen)
    quizTitle.style.display = 'block';

    /**
     * Hide title when the quiz starts.
     * Triggered by LearnDash's native 'learndash-quiz-started' event.
     */
    document.addEventListener('learndash-quiz-started', function () {
        quizTitle.style.display = 'none';
    });

    /**
     * Show title again when quiz finishes (results page).
     * Triggered by LearnDash's native 'learndash-quiz-finished' event.
     */
    document.addEventListener('learndash-quiz-finished', function () {
        setTimeout(() => {
            quizTitle.style.display = 'block';
        }, 600);
    });

    /**
     * Fallback observer:
     * Detect when the question list is rendered (DOM mutation)
     * and hide the title only — no other modifications.
     */
    const observer = new MutationObserver(() => {
        const questionVisible = document.querySelector('.wpProQuiz_questionList');
        if (questionVisible && questionVisible.offsetParent !== null) {
            quizTitle.style.display = 'none';
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
