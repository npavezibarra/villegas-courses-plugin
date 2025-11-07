<?php
/**
 * Shortcode: [villegas-circle-progress]
 *
 * Outputs the Villegas triple column progress dashboard.
 */

if ( ! function_exists( 'villegas_circle_progress_shortcode' ) ) {
    /**
     * Render the Villegas circle progress shortcode.
     *
     * @return string
     */
    function villegas_circle_progress_shortcode() {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animated Triple Column Dashboard</title>
    <!-- Load Cardo Font from Google Fonts --><link href="https://fonts.googleapis.com/css2?family=Cardo:wght@400;700&display=swap" rel="stylesheet">
    <!-- Load Tailwind CSS --><script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Define the circumference and common color variables */
        :root {
            --circumference: 283; 
            --color-gold: #FFC300; 
            --transition-duration: 2s; 
            --lesson-duration: 0.4s; 
            --lesson-delay: 0.1s; 
        }

        /* Apply Cardo font to the main headers (h2) */
        .cardo-font {
            font-family: 'Cardo', serif;
            letter-spacing: 0; 
            font-weight: 400;
        }

        /* SVG Styles */
        .progress-circle-bg { stroke: #e5e7eb; }
        .progress-circle-fg {
            stroke-dasharray: var(--circumference);
            stroke-linecap: round;
            fill: none;
            stroke-dashoffset: var(--circumference); 
            transition: stroke-dashoffset var(--transition-duration) ease-out; 
        }

        /* Lesson Styles */
        .lesson-icon { color: #9ca3af; transition: color var(--lesson-duration) ease-in-out, transform var(--lesson-duration) ease-out; }
        .lesson-completed .lesson-icon { color: var(--color-gold); transform: scale(1.1); }
        .lesson-completed { font-weight: bold; color: #374151; }
        
        /* Utility class for the main content area of each column */
        .main-content-area {
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center the content vertically inside this area */
            align-items: center;
            flex-grow: 1; /* This is key: allows the element to grow and fill remaining space */
        }

        /* Fix for list alignment within the lesson column */
        .lesson-list-wrapper {
            /* We don't need padding-top/bottom anymore, flex-grow handles the space */
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen font-sans p-6">

    <!-- Outer Container (Encapsula los tres elementos) - items-stretch asegura que las columnas llenen la altura de la fila --><div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl w-full md:items-stretch">
        
        <!-- COLUMN 1: Evaluación Inicial (43%) - ID: ev-inicial --><div id="ev-inicial" class="p-8 text-center flex flex-col items-center h-full">
            
            <!-- DIV SUPERIOR (Contenido: Título) --><div class="w-full h-12 mb-6 flex justify-center items-center">
                <h2 class="cardo-font text-3xl text-gray-800">Evaluación Inicial</h2>
            </div>

            <!-- CONTENIDO PRINCIPAL: Círculo y Puntuación (Ahora con flex-grow) --><div class="w-full main-content-area">
                <!-- SVG Container: Ajuste de margen para centrado vertical -->
                <div class="w-40 h-40 mx-auto relative mt-4"> 
                    <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                        <circle cx="50" cy="50" r="45" stroke-width="10" class="progress-circle-bg" fill="none"/>
                        <circle cx="50" cy="50" r="45" stroke="var(--color-gold)" stroke-width="10" class="progress-circle-fg" id="initial-progress"/>
                    </svg>
                    <!-- Text Overlay --><div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-4xl font-bold text-gray-800" id="initial-percentage">0%</span>
                    </div>
                </div>
                <!-- Score text shown after animation: Ajuste de margen para centrado vertical -->
                <p id="initial-score-text" class="mt-6 text-lg font-medium text-gray-500 opacity-0 transition-opacity duration-500">26 respuestas correctas de 60.</p>
            </div>
            
            <!-- DIV INFERIOR (Contenido: Párrafo) --><div class="w-full h-24 bg-transparent mt-6 flex justify-center items-start text-center">
                <p class="text-sm text-gray-700">Cada curso tiene un <strong class="font-semibold">Evaluación Inicial</strong> para medir tus conocimientos antes de iniciar el curso. Esta evaluación es obligatoria para partir el curso y el resultado es solo una referencia por la que no es necesario prepararse para rendirla.</p>
            </div>
        </div>
        
        <!-- COLUMN 2: Completa Lecciones (5 Lessons) - ID: lecciones-short --><div id="lecciones-short" class="p-8 flex flex-col items-center h-full">
            
            <!-- DIV SUPERIOR (Contenido: Título) --><div class="w-full h-12 mb-6 flex justify-center items-center">
                <h2 class="cardo-font text-3xl text-gray-800">Completa Lecciones</h2>
            </div>
            
            <!-- CONTENIDO PRINCIPAL: Lista de Lecciones (Ahora con flex-grow y py-6 para equilibrar) -->
            <div class="w-full main-content-area lesson-list-wrapper py-6">
                <ul class="space-y-3"> 
                    <!-- Lesson items --><li id="lesson-1" class="flex items-center justify-center text-base text-gray-700"> 
                        <svg class="lesson-icon w-5 h-5 mr-3" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>Lección 1
                    </li>
                    <li id="lesson-2" class="flex items-center justify-center text-base text-gray-700">
                        <svg class="lesson-icon w-5 h-5 mr-3" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>Lección 2
                    </li>
                    <li id="lesson-3" class="flex items-center justify-center text-base text-gray-700">
                        <svg class="lesson-icon w-5 h-5 mr-3" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>Lección 3
                    </li>
                    <li id="lesson-4" class="flex items-center justify-center text-base text-gray-700">
                        <svg class="lesson-icon w-5 h-5 mr-3" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>Lección 4
                    </li>
                    <li id="lesson-5" class="flex items-center justify-center text-base text-gray-700">
                        <svg class="lesson-icon w-5 h-5 mr-3" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>Lección 5
                    </li>
                </ul>
            </div>
            
            <!-- Se ha ELIMINADO el div espaciador que causaba la desalineación -->
            
            <!-- DIV INFERIOR (Contenido: Párrafo) --><div class="w-full h-24 bg-transparent mt-6 flex justify-center items-start text-center">
                <p class="text-sm text-gray-700">Los cursos cuentan con diverso material de estudio, desde videos, audios y lecturas que deberás completar en el orden determinado por el curso. Toma notas y repasa los contenidos para que una vez finalizadas las lecciones hagas la evaluación final.</p>
            </div>
        </div>
        
        <!-- COLUMN 3: Evaluación Final (78%) - ID: ev-final --><div id="ev-final" class="p-8 text-center flex flex-col items-center h-full">
            
            <!-- DIV SUPERIOR (Contenido: Título) --><div class="w-full h-12 mb-6 flex justify-center items-center">
                <h2 class="cardo-font text-3xl text-gray-800">Evaluación Final</h2>
            </div>

            <!-- CONTENIDO PRINCIPAL: Círculo y Puntuación (Ahora con flex-grow) --><div class="w-full main-content-area">
                <!-- SVG Container: Ajuste de margen para centrado vertical -->
                <div class="w-40 h-40 mx-auto relative mt-4"> 
                    <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                        <circle cx="50" cy="50" r="45" stroke-width="10" class="progress-circle-bg" fill="none"/>
                        <circle cx="50" cy="50" r="45" stroke="var(--color-gold)" stroke-width="10" class="progress-circle-fg" id="final-progress"/>
                    </svg>
                    <!-- Text Overlay --><div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-4xl font-bold text-gray-800" id="final-percentage">0%</span>
                    </div>
                </div>
                <!-- Score text shown after animation: Ajuste de margen para centrado vertical -->
                <p id="final-score-text" class="mt-6 text-lg font-medium text-gray-500 opacity-0 transition-opacity duration-500">47 respuestas correctas de 60.</p>
            </div>
            
            <!-- DIV INFERIOR (Contenido: Párrafo) --><div class="w-full h-24 bg-transparent mt-6 flex justify-center items-start text-center">
                <!-- Modificación: Evaluación Final ahora en negrita -->
                <p class="text-sm text-gray-700">Completadas todas las lecciones tendrás acceso a realizar la <strong class="font-semibold">Evaluación Final</strong> que te permitirá conocer cuánto del contenido aprendiste con el curso. De esta formas podrás visualizar el progreso alcanzado.</p>
            </div>
        </div>

    </div>

    <script>
        // --- Configuration Constants (in ms) ---
        const CIRCUMFERENCE = 283;
        const CIRCLE_DURATION = 2000; 
        const LESSON_DURATION = 400;  
        const LESSON_DELAY = 100;     
        const LESSON_COUNT = 5; 
        const INITIAL_PERCENTAGE = 43;
        const FINAL_PERCENTAGE = 78;

        /**
         * Calculates the stroke-dashoffset for a given percentage.
         * @param {number} percentage 
         * @returns {number} The offset value.
         */
        function calculateOffset(percentage) {
            // Formula: (100 - percentage) / 100 * CIRCUMFERENCE
            return ((100 - percentage) / 100) * CIRCUMFERENCE;
        }

        /**
         * Animates an SVG circle's stroke-dashoffset AND updates the percentage number.
         * @param {string} elementId - ID of the circle element ('initial-progress' or 'final-progress').
         * @param {number} percentage - Target percentage to display.
         * @param {number} duration - Animation duration in ms.
         * @returns {Promise<void>} Resolves when the animation is complete.
         */
        function animateCircle(elementId, percentage, duration) {
            const circle = document.getElementById(elementId);
            const textId = elementId === 'initial-progress' ? 'initial-score-text' : 'final-score-text';
            const scoreText = document.getElementById(textId);
            const percentageElement = document.getElementById(elementId.replace('-progress', '-percentage'));

            if (!circle) return Promise.resolve();

            const targetOffset = calculateOffset(percentage);
            const startTime = performance.now();
            
            // Set the transition duration dynamically for the SVG stroke
            circle.style.transitionDuration = `${duration}ms`;

            // Trigger the transition for the stroke
            circle.style.strokeDashoffset = targetOffset;
            
            // Start the number counting animation
            const animateCount = (currentTime) => {
                const elapsedTime = currentTime - startTime;
                const progress = Math.min(1, elapsedTime / duration); // Progress from 0 to 1

                // Calculate the current percentage based on time elapsed
                const currentPercentage = Math.floor(progress * percentage);

                if (percentageElement) {
                    percentageElement.textContent = `${currentPercentage}%`;
                }

                // If not finished, request the next frame
                if (progress < 1) {
                    requestAnimationFrame(animateCount);
                } else {
                    // Animation finished, ensure it's exactly the target percentage
                    if (percentageElement) {
                        percentageElement.textContent = `${percentage}%`;
                    }
                    // Reveal the score text
                    if (scoreText) {
                        scoreText.classList.remove('opacity-0');
                        scoreText.classList.add('opacity-100');
                    }
                }
            };
            
            return new Promise(resolve => {
                // Use setTimeout to ensure the promise resolves after the full duration, 
                // regardless of the frame rate of requestAnimationFrame
                setTimeout(() => {
                    resolve();
                }, duration);

                // Start the high-resolution number counting
                requestAnimationFrame(animateCount);
            });
        }

        /**
         * Sequentially animates the lesson highlights.
         * @returns {Promise<void>} Resolves when all lessons are highlighted.
         */
        function animateLessons() {
            return new Promise(resolve => {
                let delayAccumulator = 0;
                
                // Loop up to the LESSON_COUNT (5)
                for (let i = 1; i <= LESSON_COUNT; i++) { 
                    const lessonElement = document.getElementById(`lesson-${i}`);
                    
                    // Stagger the start time for each lesson
                    setTimeout(() => {
                        if (lessonElement) {
                            // Apply the completion class to trigger the yellow color transition
                            lessonElement.classList.add('lesson-completed');
                        }
                    }, delayAccumulator);
                    
                    // Increment the accumulator for the next lesson's start time
                    delayAccumulator += LESSON_DURATION + LESSON_DELAY;
                }
                
                // Resolve the promise after the total time for all lessons has passed
                setTimeout(resolve, delayAccumulator);
            }
        )}

        /**
         * Main function to chain all animations in sequence.
         */
        async function startChainedAnimation() {
            // 1. Animate Initial Evaluation (43%) AND reveal its score text
            await animateCircle('initial-progress', INITIAL_PERCENTAGE, CIRCLE_DURATION);

            // 2. Animate Lessons Stack (Sequential yellow fill)
            await animateLessons();

            // 3. Animate Final Evaluation (78%) AND reveal its score text
            await animateCircle('final-progress', FINAL_PERCENTAGE, CIRCLE_DURATION);

            // console.log("All animations complete!");
        }

        // Run the chained animation when the page is fully loaded
        window.onload = startChainedAnimation;
    </script>

</body>
</html>
HTML;

        return $html;
    }

    add_shortcode( 'villegas-circle-progress', 'villegas_circle_progress_shortcode' );
}
