document.addEventListener("DOMContentLoaded", function () {
    const startQuizButton = document.querySelector('.wpProQuiz_button[name="startQuiz"]');
    if (startQuizButton && typeof quizData !== 'undefined' && !document.getElementById('quiz-start-message')) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'custom-quiz-message';
        messageDiv.id = 'quiz-start-message';
        const messageContent = `
            <div id="quiz-start-paragraph">
                ${quizData.description || '<p style="color:red;">(Falta la descripción del quiz)</p>'}
            </div>
        `;
        messageDiv.innerHTML = messageContent;
        startQuizButton.parentNode.insertBefore(messageDiv, startQuizButton);
    }
});

let villegasFirstQuizEmailSent = false;

jQuery(document).ready(function ($) {
    let finalQuizEmailSent = false;
    let finalQuizEmailAttempts = 0;
    const finalQuizEmailMaxAttempts = 10;
    const finalQuizRetryDelay = 500;

    function parsePercent(text) {
        if (!text) {
            return null;
        }

        const numeric = parseFloat(String(text).replace('%', '').trim());

        if (Number.isNaN(numeric)) {
            return null;
        }

        return numeric;
    }

    function sendFinalQuizEmail() {
        if (finalQuizEmailSent) {
            return;
        }

        if (typeof FinalQuizEmailData === 'undefined' || !FinalQuizEmailData.isFinalQuiz) {
            return;
        }

        const initialText = $('.villegas-donut-percent-initial').text();
        const finalText = $('.villegas-donut-percent-final').text();

        const initialScore = parsePercent(initialText);
        const finalScore = parsePercent(finalText);

        if (initialScore === null || finalScore === null) {
            finalQuizEmailAttempts += 1;

            if (finalQuizEmailAttempts <= finalQuizEmailMaxAttempts) {
                setTimeout(sendFinalQuizEmail, finalQuizRetryDelay);
            }

            return;
        }

        const ajaxUrl = FinalQuizEmailData.ajaxUrl || window.ajaxurl || '';

        if (!ajaxUrl) {
            console.error('[FinalQuizEmail] Missing AJAX URL.');
            return;
        }

        finalQuizEmailSent = true;

        $.post(ajaxUrl, {
            action: 'enviar_correo_final_quiz',
            nonce: FinalQuizEmailData.nonce,
            first_quiz_percentage: initialScore,
            final_quiz_percentage: finalScore,
            quiz_id: FinalQuizEmailData.quizId,
            course_id: FinalQuizEmailData.courseId,
            user_id: FinalQuizEmailData.userId
        })
            .done(function (response) {
                console.log('[FinalQuizEmail] Server response:', response);
            })
            .fail(function (error) {
                console.error('[FinalQuizEmail] AJAX error:', error);
                finalQuizEmailSent = false;
            });
    }

    $(document).on('learndash-quiz-finished', function () {
        setTimeout(sendFinalQuizEmail, 1500);
    });
});

(function ($) {
    $(document).on('learndash-quiz-finished', function () {
        if (villegasFirstQuizEmailSent) {
            return;
        }

        if (typeof quizData === 'undefined' || quizData.type !== 'first') {
            return;
        }

        const ajaxUrl = (window.villegasAjax && window.villegasAjax.ajaxUrl) || window.ajaxurl || '';
        if (!ajaxUrl) {
            console.error('[FirstQuizEmail] No AJAX URL available.');
            return;
        }

        const nonce = quizData.firstQuizNonce || '';
        if (!nonce) {
            console.error('[FirstQuizEmail] Missing nonce for rendered first quiz email.');
            return;
        }

        let attempts = 0;
        const maxAttempts = 20;
        const retryDelay = 500;

        function detectPointsLabels() {
            attempts++;

            const labels = document.querySelectorAll('.wpProQuiz_pointsChart__label');

            if (labels.length >= 2) {
                const userLabel = labels[0];
                const avgLabel = labels[1];

                const userScore = parseFloat(userLabel.textContent.replace('%', '').trim());
                const avgScore = parseFloat(avgLabel.textContent.replace('%', '').trim());

                if (!isNaN(userScore) && !isNaN(avgScore)) {
                    villegasFirstQuizEmailSent = true;
                    console.log(`[FirstQuizEmail] ✅ Found wpProQuiz_pointsChart__label values after ${attempts} attempt(s):`, {
                        userScore: userScore,
                        avgScore: avgScore
                    });

                    $.post(ajaxUrl, {
                        action: 'enviar_correo_first_quiz_rendered',
                        quiz_id: quizData.quizId,
                        user_id: quizData.userId,
                        user_score: userScore,
                        average_score: avgScore,
                        nonce: nonce
                    })
                        .done(function (res) {
                            console.info('[FirstQuizEmail] AJAX success:', res);
                        })
                        .fail(function (err) {
                            console.error('[FirstQuizEmail] AJAX failed:', err);
                            villegasFirstQuizEmailSent = false;
                        });

                    return;
                }
            }

            if (attempts < maxAttempts) {
                console.warn(`[FirstQuizEmail] Attempt ${attempts}: .wpProQuiz_pointsChart__label not ready. Retrying...`);
                setTimeout(detectPointsLabels, retryDelay);
            } else {
                console.error('[FirstQuizEmail] ❌ No se pudo obtener las etiquetas wpProQuiz_pointsChart__label después de varios intentos.');
            }
        }

        setTimeout(detectPointsLabels, 1000);
    });
})(jQuery);

