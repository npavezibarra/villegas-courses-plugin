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
        var correctAnswers = parseInt(jQuery('.wpProQuiz_correct_answer').text(), 10);
        var totalQuestions = parseInt(jQuery('.total-questions').text(), 10);
        if (isNaN(correctAnswers) || totalQuestions <= 0) return;

        var percentage = Math.round((correctAnswers / totalQuestions) * 100);
        console.log('Intentando enviar correo Final Quiz con porcentaje:', percentage);

        // Función que revisa si el handler PHP ya encontrará el intento.
        function intentarEnviar(reintentoCount) {
            if (reintentoCount > 5) {
                console.warn('No se encontró intento tras varios reintentos. Abortando envío.');
                return;
            }

            jQuery.post(ajax_object.ajaxurl, {
                action: 'enviar_correo_final_quiz',
                user_id: quizData.userId,
                quiz_id: quizData.quizId,
                quiz_percentage: percentage
            }, function (response) {
                if (response.success) {
                    console.log('📩 FINAL QUIZ EMAIL response:', response);
                } else if (response.data === 'Intento no encontrado') {
                    console.log('Intento no encontrado aún. Reintentando en 500 ms…');
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

