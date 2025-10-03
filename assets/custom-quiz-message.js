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
    if (typeof quizData !== 'undefined' && quizData.type === 'final') {
        var finalQuizNonce = quizData.finalQuizNonce || '';
        var ajaxConfig = window.villegasAjax || {};
        var ajaxUrl = ajaxConfig.ajaxUrl || '';

        if (!finalQuizNonce || !ajaxUrl) {
            console.error('Faltan datos para enviar el correo del Final Quiz. Abortando envío.');
            return;
        }

        var correctAnswers = parseInt(jQuery('.wpProQuiz_correct_answer').text(), 10);
        var totalQuestions = parseInt(jQuery('.total-questions').text(), 10);
        if (isNaN(correctAnswers) || totalQuestions <= 0) return;

        var percentage = Math.round((correctAnswers / totalQuestions) * 100);

        // Función que revisa si el handler PHP ya encontrará el intento.
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
                    setTimeout(function() {
                        intentarEnviar(reintentoCount + 1);
                    }, 500);
                } else {
                    // Otro tipo de error (p.ej. plantilla no encontrada, usuario no existe, etc.)
                    console.error('Error al enviar correo Final Quiz:', response);
                }
            });
        }

        // Iniciamos el primer intento (reintentoCount = 0)
        intentarEnviar(0);
    }
});

