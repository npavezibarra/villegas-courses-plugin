document.addEventListener('DOMContentLoaded', function () {
    function aplicarEstilosBotones() {
        const botones = document.querySelectorAll('.wpProQuiz_QuestionButton');
        const preguntas = Array.from(document.querySelectorAll('.wpProQuiz_listItem'));

        // Identificar la pregunta actualmente visible usando offsetParent
        const indexPreguntaVisible = preguntas.findIndex(p => p.offsetParent !== null);

        botones.forEach(btn => {
            const tipo = btn.getAttribute('name') || '';

            // Limpiar estilos inline previos para evitar conflictos
            btn.removeAttribute('style');

            // Estilos comunes
            btn.style.setProperty('background-color', 'black', 'important');
            btn.style.setProperty('color', 'white', 'important');
            btn.style.setProperty('border', 'none', 'important');
            btn.style.setProperty('padding', '10px 20px', 'important');
            btn.style.setProperty('font-size', '15px', 'important');
            btn.style.setProperty('border-radius', '6px', 'important');
            btn.style.setProperty('font-family', 'Arial, sans-serif', 'important');
            btn.style.setProperty('width', 'fit-content', 'important');
            btn.style.setProperty('margin', '10px', 'important');
            btn.style.setProperty('position', 'relative', 'important');
            btn.style.setProperty('cursor', 'pointer', 'important');

            // Lógica para el botón "Atrás"
            if (tipo === 'back') {
                if (indexPreguntaVisible === 0) {
                    btn.style.setProperty('display', 'none', 'important');
                } else {
                    btn.style.setProperty('display', 'inline-block', 'important');
                    btn.style.setProperty('float', 'left', 'important');
                }
            }

            // Lógica para el botón "Próximo" o "Terminar Evaluación"
            if (tipo === 'next' || tipo === 'questionFinish') {
                btn.style.setProperty('display', 'inline-block', 'important');
                btn.style.setProperty('float', 'right', 'important');
                btn.style.setProperty('text-align', 'right', 'important');
            }

            // Ocultar permanentemente el botón "Controlar"
            if (tipo === 'check') {
                btn.style.setProperty('display', 'none', 'important');
            }
        });
    }

    // Ejecutar estilos en el próximo ciclo de renderizado
    function ejecutarEstilos() {
        requestAnimationFrame(aplicarEstilosBotones);
    }

    // Ejecutar al cargar la página
    ejecutarEstilos();

    // Observar cambios en el DOM (childList, subtree y attributes)
    const observer = new MutationObserver(ejecutarEstilos);
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style'] // Solo observar cambios en el atributo style
    });
});