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

    function resolveFinalQuizConfig() {
        const localizedConfig = typeof FinalQuizEmailData !== 'undefined' ? FinalQuizEmailData : null;
        const hasFinalQuizType = typeof quizData !== 'undefined' && quizData && quizData.type === 'final';

        if (!localizedConfig && !hasFinalQuizType) {
            return null;
        }

        const ajaxUrl = localizedConfig && localizedConfig.ajaxUrl ? localizedConfig.ajaxUrl : '';
        const fallbackAjaxUrl = (window.villegasAjax && window.villegasAjax.ajaxUrl) || window.ajaxurl || '';

        const nonce = localizedConfig && localizedConfig.nonce
            ? localizedConfig.nonce
            : (typeof quizData !== 'undefined' ? quizData.finalQuizNonce : '');

        const quizId = localizedConfig && localizedConfig.quizId
            ? localizedConfig.quizId
            : (typeof quizData !== 'undefined' ? quizData.quizId : 0);

        const courseId = localizedConfig && typeof localizedConfig.courseId !== 'undefined'
            ? localizedConfig.courseId
            : (typeof quizData !== 'undefined' ? quizData.courseId : 0);

        const userId = localizedConfig && localizedConfig.userId
            ? localizedConfig.userId
            : (typeof quizData !== 'undefined' ? quizData.userId : 0);

        return {
            ajaxUrl: ajaxUrl || fallbackAjaxUrl,
            nonce: nonce || '',
            quizId: quizId || 0,
            courseId: courseId || 0,
            userId: userId || 0,
            isFinalQuiz: localizedConfig ? !!localizedConfig.isFinalQuiz : hasFinalQuizType,
        };
    }

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

        const config = resolveFinalQuizConfig();

        if (!config || !config.isFinalQuiz) {
            return;
        }

        if (!config.ajaxUrl || !config.nonce || !config.quizId || !config.userId) {
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

        finalQuizEmailSent = true;

        $.post(config.ajaxUrl, {
            action: 'enviar_correo_final_quiz',
            nonce: config.nonce,
            first_quiz_percentage: initialScore,
            final_quiz_percentage: finalScore,
            quiz_id: config.quizId,
            course_id: config.courseId,
            user_id: config.userId
        })
            .done(function () {
                finalQuizEmailSent = true;
            })
            .fail(function () {
                finalQuizEmailSent = false;

                finalQuizEmailAttempts += 1;

                if (finalQuizEmailAttempts <= finalQuizEmailMaxAttempts) {
                    setTimeout(sendFinalQuizEmail, finalQuizRetryDelay);
                }
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

