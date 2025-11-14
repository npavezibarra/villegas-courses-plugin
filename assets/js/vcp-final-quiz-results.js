document.addEventListener('DOMContentLoaded', function () {
    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function drawChart(chartContainer, percent) {
        const svg = chartContainer.querySelector('.wpProQuiz_pointsChart__svg');
        const labelEl = chartContainer.querySelector('.wpProQuiz_pointsChart__label');
        const progressCircle = svg ? svg.querySelector('.wpProQuiz_pointsChart__progress') : null;

        if (!progressCircle) {
            return;
        }

        const percentValue = clamp(parseFloat(percent) || 0, 0, 100);
        const radius = 16;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference * (1 - percentValue / 100);

        progressCircle.setAttribute('stroke-dasharray', circumference + ' ' + circumference);
        progressCircle.setAttribute('stroke-dashoffset', offset.toString());

        if (labelEl) {
            labelEl.textContent = percentValue.toFixed(0) + '%';
        }
    }

    function initializeCharts() {
        const charts = document.querySelectorAll('.villegas-final-quiz-result .wpProQuiz_pointsChart');

        charts.forEach(function (chart) {
            const percentAttr = chart.getAttribute('data-static-percent');
            if (percentAttr !== null && !chart.dataset.chartInitialized) {
                drawChart(chart, percentAttr);
                chart.dataset.chartInitialized = 'true';
            }
        });
    }

    function updateVariationMessage() {
        const wrapper = document.querySelector('.villegas-final-quiz-result');
        if (!wrapper) {
            return;
        }

        const initialChart = wrapper.querySelector('.villegas-donut--initial');
        const finalChart = wrapper.querySelector('.villegas-donut--final');
        const variationDiv = document.getElementById('variacion-evaluacion');

        if (!initialChart || !finalChart || !variationDiv) {
            return;
        }

        const initialPercent = parseFloat(initialChart.getAttribute('data-static-percent'));
        const finalPercent = parseFloat(finalChart.getAttribute('data-static-percent'));

        if (isNaN(initialPercent) || isNaN(finalPercent)) {
            variationDiv.innerHTML = '<p class="quiz-variation-message quiz-variation-message--negative">Error al cargar puntajes.</p>';
            return;
        }

        const variation = finalPercent - initialPercent;
        const absVariation = Math.abs(variation).toFixed(0);
        const courseUrl = variationDiv.getAttribute('data-course-url') || '#';
        const certificateUrl = variationDiv.getAttribute('data-certificate-url') || courseUrl;

        let messageClass = 'quiz-variation-message--negative';
        let percentClass = 'quiz-variation-percent--negative';
        let buttonClass = 'quiz-variation-button--course';
        let buttonText = 'VER CURSO';
        let buttonHref = courseUrl;
        let messageText = `¡Bien hecho por completar el curso! La variación fue de <span class="${percentClass}"><strong>${absVariation}%</strong></span>. Te aconsejamos retomar el curso para consolidar tus conocimientos.`;

        if (variation > 0) {
            messageClass = 'quiz-variation-message--positive';
            percentClass = 'quiz-variation-percent--positive';
            buttonClass = 'quiz-variation-button--certificate';
            buttonText = 'VER CERTIFICADO';
            buttonHref = certificateUrl;
            messageText = `¡Felicidades! Obtuviste una variación positiva de <span class="${percentClass}"><strong>${absVariation}%</strong></span>. Has progresado en el curso.`;
        }

        variationDiv.innerHTML = `
            <p class="quiz-variation-message ${messageClass}">
                ${messageText}
            </p>
            <a href="${buttonHref}" class="quiz-variation-button ${buttonClass}">
                ${buttonText}
            </a>
        `;
    }

    initializeCharts();
    updateVariationMessage();
});
