document.addEventListener("DOMContentLoaded", function () {
    const startQuizButton = document.querySelector('.wpProQuiz_button[name="startQuiz"]');

    if (
        startQuizButton &&
        typeof quizData !== 'undefined' &&
        !document.getElementById('quiz-start-message')
    ) {
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

jQuery(document).on('learndash-quiz-finished', function () {
    if (typeof quizData === 'undefined') {
        return;
    }

    var ajaxConfig = window.villegasAjax || {};
    var ajaxUrl = ajaxConfig.ajaxUrl || window.ajaxurl || '';

    if (!ajaxUrl) {
        console.error('No se pudo determinar la URL de AJAX para enviar los correos de quiz.');
        return;
    }

    var correctAnswers = parseInt(jQuery('.wpProQuiz_correct_answer').text(), 10);
    var totalQuestions = parseInt(jQuery('.total-questions').text(), 10);

    if (isNaN(correctAnswers) || totalQuestions <= 0) {
        return;
    }

    var percentage = Math.round((correctAnswers / totalQuestions) * 100);

    if (quizData.type === 'first') {
        var firstQuizNonce = quizData.firstQuizNonce || '';

        if (!firstQuizNonce) {
            console.error('Falta el nonce para enviar el correo del First Quiz.');
            return;
        }

        jQuery.post(ajaxUrl, {
            action: 'villegas_send_first_quiz_email',
            quiz_id: quizData.quizId,
            user_id: quizData.userId || 0,
            quiz_percentage: percentage,
            nonce: firstQuizNonce
        }).fail(function (response) {
            console.error('Error al enviar correo First Quiz:', response);
        });
    }

    if (quizData.type === 'final') {
        var finalQuizNonce = quizData.finalQuizNonce || '';

        if (!finalQuizNonce) {
            console.error('Faltan datos para enviar el correo del Final Quiz. Abortando envío.');
            return;
        }

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
                if (response.success) {
                    return;
                } else if (response.data === 'Intento no encontrado') {
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

