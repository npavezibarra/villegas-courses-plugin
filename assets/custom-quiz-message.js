document.addEventListener("DOMContentLoaded", function () {
    const startQuizButton = document.querySelector('.wpProQuiz_button[name="startQuiz"]');
    if (startQuizButton && typeof quizData !== 'undefined' && !document.getElementById('quiz-start-message')) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'custom-quiz-message';
        messageDiv.id = 'quiz-start-message';
        const messageContent = `
            <a id="back-to-course-link" href="${document.referrer}" class="back-to-course-link">Volver al curso</a>
            <div id="quiz-start-paragraph">
                ${quizData.description || '<p style="color:red;">(Falta la descripción del quiz)</p>'}
            </div>
        `;
        messageDiv.innerHTML = messageContent;
        startQuizButton.parentNode.insertBefore(messageDiv, startQuizButton);
    }
});

/**
 * Espera hasta que LearnDash haya actualizado el DOM con las respuestas correctas.
 */
function getFinalPercentage(callback, attempts = 0) {
    const correct = parseInt(jQuery('.wpProQuiz_correct_answer').text(), 10);
    const total = parseInt(jQuery('.total-questions').text(), 10);

    if (!isNaN(correct) && total > 0) {
        const pct = Math.round((correct / total) * 100);
        callback(pct);
    } else if (attempts < 10) {
        // Retry every 200 ms (total up to ~2 s)
        setTimeout(() => getFinalPercentage(callback, attempts + 1), 200);
    } else {
        console.warn('No se pudo obtener el puntaje final después de varios intentos.');
        callback(0);
    }
}

jQuery(document).on('learndash-quiz-finished', function () {
    if (typeof quizData === 'undefined') return;

    const ajaxConfig = window.villegasAjax || {};
    const ajaxUrl = ajaxConfig.ajaxUrl || window.ajaxurl || '';
    if (!ajaxUrl) {
        console.error('No se pudo determinar la URL de AJAX para enviar los correos de quiz.');
        return;
    }

    // Wait for LearnDash DOM update before sending the email
    getFinalPercentage(function (percentage) {
        console.log('[FirstQuizEmail] Final computed percentage:', percentage);
        // --- FIRST QUIZ ---
        if (quizData.type === 'first') {
            const firstQuizNonce = quizData.firstQuizNonce || '';
            const activityNonce = quizData.activityNonce || '';

            if (!firstQuizNonce || !activityNonce) {
                console.error('[FirstQuizEmail] Missing ajaxUrl/nonce(s).', {
                    ajaxUrl: !!ajaxUrl,
                    firstQuizNonce: !!firstQuizNonce,
                    activityNonce: !!activityNonce
                });
                return;
            }

            let tries = 0;
            const maxTries = 15;

            function poll() {
                tries++;

                jQuery.post(ajaxUrl, {
                    action: 'villegas_get_latest_quiz_result',
                    quiz_id: quizData.quizId,
                    user_id: quizData.userId || 0,
                    nonce: activityNonce
                }).done(function (res) {
                    console.log('[FirstQuizEmail][poll] try=', tries, res);

                    if (!res || !res.success || !res.data) {
                        if (tries < maxTries) {
                            return setTimeout(poll, 1000);
                        }

                        console.warn('[FirstQuizEmail][poll] bad response; giving up');
                        return;
                    }

                    const data = res.data;

                    if (
                        data.status === 'ready' &&
                        typeof data.percentage !== 'undefined' &&
                        data.percentage !== null
                    ) {
                        const pctRounded = Math.round(Number(data.percentage));
                        console.log('[FirstQuizEmail] READY. percentage=', data.percentage, 'rounded=', pctRounded);

                        jQuery.post(ajaxUrl, {
                            action: 'enviar_correo_first_quiz',
                            quiz_id: quizData.quizId,
                            user_id: quizData.userId || 0,
                            quiz_percentage: pctRounded,
                            nonce: firstQuizNonce
                        }).done(function (emailRes) {
                            console.log('[FirstQuizEmail] Email AJAX response:', emailRes);
                        }).fail(function (err) {
                            console.error('[FirstQuizEmail] Email AJAX failed:', err);
                        });
                    } else {
                        const wait = data.retry_after ? data.retry_after * 1000 : 1500;

                        if (tries < maxTries) {
                            return setTimeout(poll, wait);
                        }

                        console.warn('[FirstQuizEmail][poll] not ready after max tries; last=', data);
                    }
                }).fail(function (err) {
                    console.error('[FirstQuizEmail][poll] AJAX error:', err);

                    if (tries < maxTries) {
                        return setTimeout(poll, 1500);
                    }
                });
            }

            poll();
        }

        // --- FINAL QUIZ (keep original logic) ---
        if (quizData.type === 'final') {
            const finalQuizNonce = quizData.finalQuizNonce || '';
            if (!finalQuizNonce) return;

            function intentarEnviar(reintentoCount) {
                if (reintentoCount > 5) {
                    console.error('No se encontró intento tras varios reintentos. Abortando envío.');
                    return;
                }

                jQuery.post(ajaxUrl, {
                    action: 'enviar_correo_final_quiz',
                    quiz_id: quizData.quizId,
                    quiz_percentage: percentage,
                    nonce: finalQuizNonce
                }, function (response) {
                    if (response.success) return;
                    if (response.data === 'Intento no encontrado') {
                        setTimeout(function () {
                            intentarEnviar(reintentoCount + 1);
                        }, 500);
                    } else {
                        console.error('Error al enviar correo Final Quiz:', response);
                    }
                }).fail(function (response) {
                    console.error('Error al comunicarse con AJAX del Final Quiz:', response);
                });
            }

            intentarEnviar(0);
        }
    });
});

