/**
 * quiz-title-toggle.js
 * Purpose: Hide the quiz title during questions.
 * Restriction: Do not modify any other quiz logic or UI behavior.
 */

document.addEventListener('DOMContentLoaded', function () {
    const quizCard = document.getElementById('quiz-card');
    if (!quizCard) return;

    const quizTitle = quizCard.querySelector('h1, h2, h3, .quiz-title');
    if (!quizTitle) return;

    const nodesToToggle = [quizTitle];
    const originalDisplay = new Map();

    const cacheDisplay = (node) => {
        if (!node || originalDisplay.has(node)) return;
        const inlineDisplay = node.style.display;
        if (inlineDisplay && inlineDisplay !== 'none') {
            originalDisplay.set(node, inlineDisplay);
            return;
        }

        const computed = window.getComputedStyle(node).display;
        if (computed && computed !== 'none') {
            originalDisplay.set(node, computed);
        } else {
            originalDisplay.set(node, '');
        }
    };

    const showNodes = () => {
        nodesToToggle.forEach((node) => {
            if (!node) return;
            const displayValue = originalDisplay.get(node);
            if (displayValue) {
                node.style.display = displayValue;
            } else {
                node.style.removeProperty('display');
            }
        });
    };

    const hideNodes = () => {
        nodesToToggle.forEach((node) => {
            if (!node) return;
            node.style.display = 'none';
        });
    };

    const identifyDateElement = () => {
        // Attempt to capture the date that LearnDash prints immediately after the title
        // so it can be toggled together with the heading.
        let sibling = quizTitle.nextSibling;

        while (sibling) {
            if (sibling.nodeType === Node.TEXT_NODE) {
                const trimmed = sibling.textContent.trim();
                if (!trimmed) {
                    sibling = sibling.nextSibling;
                    continue;
                }

                const span = document.createElement('span');
                span.className = 'quiz-title-date';
                span.textContent = trimmed;
                span.dataset.quizTitleToggle = 'date';
                span.style.display = 'block';

                quizCard.insertBefore(span, sibling);
                quizCard.removeChild(sibling);
                return span;
            }

            if (sibling.nodeType === Node.ELEMENT_NODE) {
                const element = sibling;
                const textContent = element.textContent.trim();
                if (!textContent) {
                    sibling = sibling.nextSibling;
                    continue;
                }

                const isExplicitDateElement = element.matches('time, .quiz-date, .ld-quiz-date, .learndash-quiz-date');
                const looksLikeDate = /(\d{1,2}\s+de\s+[A-Za-zÁÉÍÓÚáéíóúÑñ]+\s+\d{4})/.test(textContent) || /(\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4})/.test(textContent);

                if (isExplicitDateElement || looksLikeDate) {
                    element.dataset.quizTitleToggle = 'date';
                    return element;
                }

                break;
            }

            sibling = sibling.nextSibling;
        }

        return null;
    };

    const dateElement = identifyDateElement();
    if (dateElement) {
        nodesToToggle.push(dateElement);
    }

    nodesToToggle.forEach(cacheDisplay);
    showNodes();

    document.addEventListener('learndash-quiz-started', () => {
        hideNodes();
    });

    document.addEventListener('learndash-quiz-finished', () => {
        setTimeout(() => {
            showNodes();
        }, 600);
    });

    const observer = new MutationObserver(() => {
        const questionVisible = document.querySelector('.wpProQuiz_questionList');
        if (questionVisible && questionVisible.offsetParent !== null) {
            hideNodes();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
