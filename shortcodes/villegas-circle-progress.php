<?php
/**
 * Shortcode: [villegas-circle-progress]
 *
 * Outputs a triple column progress dashboard used in Villegas Courses.
 */

if ( ! function_exists( 'villegas_circle_progress_shortcode' ) ) {
    /**
     * Render the Villegas circle progress shortcode.
     *
     * @return string
     */
    function villegas_circle_progress_shortcode() {
        static $assets_printed = false;

        $data = [
            'initial' => [
                'title'             => __( 'Evaluación Inicial', 'villegas-courses-plugin' ),
                'percentage'        => 43,
                'description'       => __( '26 respuestas correctas de 60.', 'villegas-courses-plugin' ),
                'footnote_template' => __( 'Cada curso tiene un %s para medir tus conocimientos antes de iniciar el curso. Esta evaluación es obligatoria para partir el curso y el resultado es solo una referencia por la que no es necesario prepararse para rendirla.', 'villegas-courses-plugin' ),
                'footnote_label'    => __( 'Evaluación Inicial', 'villegas-courses-plugin' ),
            ],
            'final'   => [
                'title'             => __( 'Evaluación Final', 'villegas-courses-plugin' ),
                'percentage'        => 78,
                'description'       => __( '47 respuestas correctas de 60.', 'villegas-courses-plugin' ),
                'footnote_template' => __( 'Completadas todas las lecciones tendrás acceso a realizar la %s que te permitirá conocer cuánto del contenido aprendiste con el curso. De esta forma podrás visualizar el progreso alcanzado.', 'villegas-courses-plugin' ),
                'footnote_label'    => __( 'Evaluación Final', 'villegas-courses-plugin' ),
            ],
            'lessons' => [
                __( 'Lección 1', 'villegas-courses-plugin' ),
                __( 'Lección 2', 'villegas-courses-plugin' ),
                __( 'Lección 3', 'villegas-courses-plugin' ),
                __( 'Lección 4', 'villegas-courses-plugin' ),
                __( 'Lección 5', 'villegas-courses-plugin' ),
            ],
            'lessons_footnote' => __( 'Los cursos cuentan con diverso material de estudio, desde videos, audios y lecturas que deberás completar en el orden determinado por el curso. Toma notas y repasa los contenidos para que una vez finalizadas las lecciones hagas la evaluación final.', 'villegas-courses-plugin' ),
        ];

        ob_start();

        if ( ! $assets_printed ) {
            $assets_printed = true;
            $config         = [
                'circumference'      => 283,
                'circleDuration'     => 2000,
                'lessonDuration'     => 400,
                'lessonDelay'        => 100,
                'lessonCount'        => count( $data['lessons'] ),
                'initialPercentage'  => (int) $data['initial']['percentage'],
                'finalPercentage'    => (int) $data['final']['percentage'],
            ];
            ?>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cardo:wght@400;700&amp;display=swap" media="all" />
            <style>
                .villegas-progress-dashboard-wrapper {
                    --villegas-progress-circumference: 283;
                    --villegas-progress-color-gold: #ffc300;
                    --villegas-progress-transition-duration: 2s;
                    --villegas-progress-lesson-duration: 0.4s;
                    --villegas-progress-lesson-delay: 0.1s;
                    background-color: #f9fafb;
                    padding: 1.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                }

                .villegas-progress-dashboard {
                    display: grid;
                    grid-template-columns: repeat(1, minmax(0, 1fr));
                    gap: 2rem;
                    max-width: 72rem;
                    width: 100%;
                }

                @media (min-width: 768px) {
                    .villegas-progress-dashboard {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                        align-items: stretch;
                    }
                }

                .villegas-progress-dashboard__column {
                    padding: 2rem;
                    text-align: center;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    height: 100%;
                    background-color: transparent;
                }

                .villegas-progress-dashboard__header {
                    width: 100%;
                    height: 3rem;
                    margin-bottom: 1.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .villegas-progress-dashboard__title {
                    font-family: 'Cardo', serif;
                    font-size: 1.875rem;
                    font-weight: 400;
                    color: #1f2937;
                    margin: 0;
                }

                .cardo-font {
                    font-family: 'Cardo', serif;
                    letter-spacing: 0;
                    font-weight: 400;
                }

                .villegas-progress-dashboard__content,
                .main-content-area {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    flex-grow: 1;
                }

                .villegas-progress-dashboard__circle-wrapper {
                    position: relative;
                    width: 10rem;
                    height: 10rem;
                    margin: 0 auto 0.5rem;
                }

                .villegas-progress-dashboard__svg {
                    width: 100%;
                    height: 100%;
                    transform: rotate(-90deg);
                }

                .progress-circle-bg {
                    stroke: #e5e7eb;
                    fill: none;
                }

                .progress-circle-fg {
                    stroke: var(--villegas-progress-color-gold);
                    stroke-width: 10;
                    stroke-dasharray: var(--villegas-progress-circumference);
                    stroke-linecap: round;
                    fill: none;
                    stroke-dashoffset: var(--villegas-progress-circumference);
                    transition: stroke-dashoffset var(--villegas-progress-transition-duration) ease-out;
                }

                .villegas-progress-dashboard__percentage {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 2.5rem;
                    font-weight: 700;
                    color: #1f2937;
                }

                .villegas-progress-dashboard__score-text {
                    margin-top: 1rem;
                    font-size: 1.125rem;
                    font-weight: 500;
                    color: #6b7280;
                }

                .opacity-0 {
                    opacity: 0;
                }

                .opacity-100 {
                    opacity: 1;
                }

                .transition-opacity {
                    transition-property: opacity;
                }

                .duration-500 {
                    transition-duration: 0.5s;
                }

                .villegas-progress-dashboard__footnote {
                    margin-top: 1.5rem;
                    font-size: 0.875rem;
                    line-height: 1.6;
                    color: #374151;
                }

                .villegas-progress-dashboard__footnote strong,
                .font-semibold {
                    font-weight: 600;
                }

                .villegas-progress-dashboard__lessons-wrapper {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    flex-grow: 1;
                }

                .villegas-progress-dashboard__lessons-list {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                    width: 100%;
                    align-items: center;
                }

                .villegas-progress-dashboard__lesson-item {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.75rem;
                    font-size: 1rem;
                    color: #374151;
                    transition: color var(--villegas-progress-lesson-duration) ease-in-out,
                        transform var(--villegas-progress-lesson-duration) ease-out;
                }

                .lesson-icon {
                    width: 1.25rem;
                    height: 1.25rem;
                    color: #9ca3af;
                    transition: color var(--villegas-progress-lesson-duration) ease-in-out,
                        transform var(--villegas-progress-lesson-duration) ease-out;
                }

                .lesson-completed {
                    font-weight: 700;
                    color: #1f2937;
                }

                .lesson-completed .lesson-icon {
                    color: var(--villegas-progress-color-gold);
                    transform: scale(1.1);
                }
            </style>
            <script>
                (function () {
                    if (window.villegasCircleProgressLoaded) {
                        return;
                    }
                    window.villegasCircleProgressLoaded = true;

                    const CONFIG = <?php echo wp_json_encode( $config ); ?>;

                    function calculateOffset(percentage) {
                        return ((100 - percentage) / 100) * CONFIG.circumference;
                    }

                    function animateCircle(elementId, percentage, duration) {
                        const circle = document.getElementById(elementId);
                        if (!circle) {
                            return Promise.resolve();
                        }

                        const textId = elementId === 'initial-progress' ? 'initial-score-text' : 'final-score-text';
                        const scoreText = document.getElementById(textId);
                        const percentageElement = document.getElementById(elementId.replace('-progress', '-percentage'));
                        const targetOffset = calculateOffset(percentage);
                        let finalized = false;
                        const startTime = performance.now();

                        circle.style.transitionDuration = duration + 'ms';

                        requestAnimationFrame(function () {
                            circle.style.strokeDashoffset = targetOffset;
                        });

                        function finalize() {
                            if (finalized) {
                                return;
                            }
                            finalized = true;

                            if (percentageElement) {
                                percentageElement.textContent = percentage + '%';
                            }

                            if (scoreText) {
                                scoreText.classList.remove('opacity-0');
                                scoreText.classList.add('opacity-100');
                            }
                        }

                        function animateCount(currentTime) {
                            const elapsed = currentTime - startTime;
                            const progress = Math.min(1, elapsed / duration);
                            const currentPercentage = Math.round(progress * percentage);

                            if (percentageElement) {
                                percentageElement.textContent = currentPercentage + '%';
                            }

                            if (progress < 1) {
                                requestAnimationFrame(animateCount);
                            } else {
                                finalize();
                            }
                        }

                        return new Promise(function (resolve) {
                            requestAnimationFrame(animateCount);

                            setTimeout(function () {
                                finalize();
                                resolve();
                            }, duration);
                        });
                    }

                    function animateLessons() {
                        return new Promise(function (resolve) {
                            let delayAccumulator = 0;

                            for (let i = 1; i <= CONFIG.lessonCount; i++) {
                                setTimeout(function () {
                                    const lessonElement = document.getElementById('lesson-' + i);
                                    if (lessonElement) {
                                        lessonElement.classList.add('lesson-completed');
                                    }
                                }, delayAccumulator);

                                delayAccumulator += CONFIG.lessonDuration + CONFIG.lessonDelay;
                            }

                            setTimeout(resolve, delayAccumulator);
                        });
                    }

                    async function startChainedAnimation() {
                        await animateCircle('initial-progress', CONFIG.initialPercentage, CONFIG.circleDuration);
                        await animateLessons();
                        await animateCircle('final-progress', CONFIG.finalPercentage, CONFIG.circleDuration);
                    }

                    if (document.readyState === 'complete') {
                        startChainedAnimation();
                    } else {
                        window.addEventListener('load', startChainedAnimation);
                    }
                })();
            </script>
            <?php
        }
        ?>
        <div class="villegas-progress-dashboard-wrapper">
            <div class="villegas-progress-dashboard" role="group" aria-label="<?php echo esc_attr__( 'Resumen de progreso del curso', 'villegas-courses-plugin' ); ?>">
                <div id="ev-inicial" class="villegas-progress-dashboard__column">
                    <div class="villegas-progress-dashboard__header">
                        <h2 class="villegas-progress-dashboard__title cardo-font"><?php echo esc_html( $data['initial']['title'] ); ?></h2>
                    </div>
                    <div class="villegas-progress-dashboard__content main-content-area">
                        <div class="villegas-progress-dashboard__circle-wrapper">
                            <svg viewBox="0 0 100 100" class="villegas-progress-dashboard__svg" aria-hidden="true" focusable="false">
                                <circle cx="50" cy="50" r="45" stroke-width="10" class="progress-circle-bg"></circle>
                                <circle id="initial-progress" cx="50" cy="50" r="45" stroke-width="10" class="progress-circle-fg"></circle>
                            </svg>
                            <div class="villegas-progress-dashboard__percentage" id="initial-percentage">0%</div>
                        </div>
                        <p id="initial-score-text" class="villegas-progress-dashboard__score-text opacity-0 transition-opacity duration-500"><?php echo esc_html( $data['initial']['description'] ); ?></p>
                    </div>
                    <div class="villegas-progress-dashboard__footnote">
                        <?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: highlighted label. */
                                $data['initial']['footnote_template'],
                                '<strong class="font-semibold">' . esc_html( $data['initial']['footnote_label'] ) . '</strong>'
                            ),
                            [
                                'strong' => [ 'class' => [] ],
                            ]
                        );
                        ?>
                    </div>
                </div>
                <div id="lecciones-short" class="villegas-progress-dashboard__column">
                    <div class="villegas-progress-dashboard__header">
                        <h2 class="villegas-progress-dashboard__title cardo-font"><?php esc_html_e( 'Completa Lecciones', 'villegas-courses-plugin' ); ?></h2>
                    </div>
                    <div class="villegas-progress-dashboard__lessons-wrapper main-content-area lesson-list-wrapper">
                        <ul class="villegas-progress-dashboard__lessons-list">
                            <?php foreach ( $data['lessons'] as $index => $lesson_label ) : ?>
                                <li id="lesson-<?php echo esc_attr( $index + 1 ); ?>" class="villegas-progress-dashboard__lesson-item">
                                    <svg class="lesson-icon" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <circle cx="5" cy="5" r="4" fill="currentColor"></circle>
                                    </svg>
                                    <?php echo esc_html( $lesson_label ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="villegas-progress-dashboard__footnote">
                        <?php echo esc_html( $data['lessons_footnote'] ); ?>
                    </div>
                </div>
                <div id="ev-final" class="villegas-progress-dashboard__column">
                    <div class="villegas-progress-dashboard__header">
                        <h2 class="villegas-progress-dashboard__title cardo-font"><?php echo esc_html( $data['final']['title'] ); ?></h2>
                    </div>
                    <div class="villegas-progress-dashboard__content main-content-area">
                        <div class="villegas-progress-dashboard__circle-wrapper">
                            <svg viewBox="0 0 100 100" class="villegas-progress-dashboard__svg" aria-hidden="true" focusable="false">
                                <circle cx="50" cy="50" r="45" stroke-width="10" class="progress-circle-bg"></circle>
                                <circle id="final-progress" cx="50" cy="50" r="45" stroke-width="10" class="progress-circle-fg"></circle>
                            </svg>
                            <div class="villegas-progress-dashboard__percentage" id="final-percentage">0%</div>
                        </div>
                        <p id="final-score-text" class="villegas-progress-dashboard__score-text opacity-0 transition-opacity duration-500"><?php echo esc_html( $data['final']['description'] ); ?></p>
                    </div>
                    <div class="villegas-progress-dashboard__footnote">
                        <?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: highlighted label. */
                                $data['final']['footnote_template'],
                                '<strong class="font-semibold">' . esc_html( $data['final']['footnote_label'] ) . '</strong>'
                            ),
                            [
                                'strong' => [ 'class' => [] ],
                            ]
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    add_shortcode( 'villegas-circle-progress', 'villegas_circle_progress_shortcode' );
}
