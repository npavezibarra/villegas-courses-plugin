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
        // --- FIRST QUIZ ---
        if (quizData.type === 'first') {
            const firstQuizNonce = quizData.firstQuizNonce || '';
            if (!firstQuizNonce) {
                console.error('Falta el nonce para enviar el correo del First Quiz.');
                return;
            }
            jQuery.post(ajaxUrl, {
                action: 'enviar_correo_first_quiz',
                quiz_id: quizData.quizId,
                user_id: quizData.userId || 0,
                quiz_percentage: percentage,
                nonce: firstQuizNonce
            }).fail(function (response) {
                console.error('Error al enviar correo First Quiz:', response);
            });
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

