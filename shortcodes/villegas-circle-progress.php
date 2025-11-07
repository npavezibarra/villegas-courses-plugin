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
                'title'       => __( 'Evaluación Inicial', 'villegas-courses-plugin' ),
                'percentage'  => 43,
                'description' => __( '26 respuestas correctas de 60.', 'villegas-courses-plugin' ),
            ],
            'final'   => [
                'title'       => __( 'Evaluación Final', 'villegas-courses-plugin' ),
                'percentage'  => 78,
                'description' => __( '47 respuestas correctas de 60.', 'villegas-courses-plugin' ),
            ],
            'lessons' => [
                __( 'Lección 1', 'villegas-courses-plugin' ),
                __( 'Lección 2', 'villegas-courses-plugin' ),
                __( 'Lección 3', 'villegas-courses-plugin' ),
                __( 'Lección 4', 'villegas-courses-plugin' ),
                __( 'Lección 5', 'villegas-courses-plugin' ),
            ],
        ];

        ob_start();

        if ( ! $assets_printed ) {
            $assets_printed = true;
            ?>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cardo:wght@700&display=swap" media="all" />
            <style>
                .villegas-progress-dashboard {
                    --villegas-progress-circumference: 283;
                    --villegas-progress-color-gold: #ffc300;
                    --villegas-progress-transition-duration: 2s;
                    --villegas-progress-lesson-duration: 0.4s;
                    --villegas-progress-lesson-delay: 0.1s;
                    display: grid;
                    grid-template-columns: repeat(1, minmax(0, 1fr));
                    gap: 2rem;
                    max-width: 72rem;
                    width: 100%;
                    margin: 0 auto;
                    padding: 1.5rem;
                    background-color: #f9fafb;
                    font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                }

                @media (min-width: 768px) {
                    .villegas-progress-dashboard {
                        grid-template-columns: repeat(3, minmax(0, 1fr));
                    }
                }

                .villegas-progress-dashboard__column {
                    padding: 2rem;
                    text-align: center;
                }

                .villegas-progress-dashboard__title {
                    font-family: 'Cardo', serif;
                    font-size: 1.875rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin-bottom: 2rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #f3f4f6;
                    letter-spacing: 0;
                }

                .villegas-progress-dashboard__circle-wrapper {
                    position: relative;
                    width: 12rem;
                    height: 12rem;
                    margin: 0 auto;
                }

                .villegas-progress-dashboard__svg {
                    width: 100%;
                    height: 100%;
                    transform: rotate(-90deg);
                }

                .villegas-progress-dashboard__circle-bg {
                    stroke: #e5e7eb;
                    fill: none;
                }

                .villegas-progress-dashboard__circle {
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
                    font-size: 3rem;
                    font-weight: 700;
                    color: #1f2937;
                }

                .villegas-progress-dashboard__score {
                    margin-top: 1rem;
                    font-size: 1.125rem;
                    font-weight: 500;
                    color: #6b7280;
                    opacity: 0;
                    transition: opacity 0.5s ease;
                }

                .villegas-progress-dashboard__score.is-visible {
                    opacity: 1;
                }

                .villegas-progress-dashboard__lessons-list {
                    margin-top: 1.5rem;
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    align-items: center;
                    padding: 0;
                    list-style: none;
                }

                .villegas-progress-dashboard__lesson {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.125rem;
                    color: #374151;
                    gap: 0.75rem;
                    transition: color var(--villegas-progress-lesson-duration) ease-in-out, transform var(--villegas-progress-lesson-duration) ease-out;
                }

                .villegas-progress-dashboard__lesson-icon {
                    width: 1.5rem;
                    height: 1.5rem;
                    color: #9ca3af;
                    transition: color var(--villegas-progress-lesson-duration) ease-in-out, transform var(--villegas-progress-lesson-duration) ease-out;
                }

                .villegas-progress-dashboard__lesson.is-completed {
                    font-weight: 700;
                }

                .villegas-progress-dashboard__lesson.is-completed .villegas-progress-dashboard__lesson-icon {
                    color: var(--villegas-progress-color-gold);
                    transform: scale(1.1);
                }
            </style>
            <script>
                (function () {
                    if (window.villegasProgressDashboardLoaded) {
                        return;
                    }
                    window.villegasProgressDashboardLoaded = true;

                    const CONFIG = {
                        circumference: 283,
                        circleDuration: 2000,
                        lessonDuration: 400,
                        lessonDelay: 100,
                        lessonCount: 5,
                        initialPercentage: 43,
                        finalPercentage: 78,
                    };

                    function calculateOffset(percentage) {
                        return ((100 - percentage) / 100) * CONFIG.circumference;
                    }

                    function animateCircle(elementId, percentage, duration) {
                        const circle = document.getElementById(elementId);
                        if (!circle) {
                            return Promise.resolve();
                        }

                        const textId = elementId === 'villegas-progress-initial-circle'
                            ? 'villegas-progress-initial-score'
                            : 'villegas-progress-final-score';
                        const scoreText = document.getElementById(textId);
                        const targetOffset = calculateOffset(percentage);

                        circle.style.transitionDuration = duration + 'ms';

                        requestAnimationFrame(function () {
                            circle.style.strokeDashoffset = targetOffset;
                        });

                        return new Promise(function (resolve) {
                            setTimeout(function () {
                                if (scoreText) {
                                    scoreText.classList.add('is-visible');
                                }
                                resolve();
                            }, duration);
                        });
                    }

                    function animateLessons() {
                        return new Promise(function (resolve) {
                            var delayAccumulator = 0;

                            for (var i = 1; i <= CONFIG.lessonCount; i++) {
                                (function (index, delay) {
                                    setTimeout(function () {
                                        var lessonElement = document.getElementById('villegas-progress-lesson-' + index);
                                        if (lessonElement) {
                                            lessonElement.classList.add('is-completed');
                                        }
                                    }, delay);
                                })(i, delayAccumulator);

                                delayAccumulator += CONFIG.lessonDuration + CONFIG.lessonDelay;
                            }

                            setTimeout(resolve, delayAccumulator);
                        });
                    }

                    function startAnimationSequence() {
                        animateCircle('villegas-progress-initial-circle', CONFIG.initialPercentage, CONFIG.circleDuration)
                            .then(animateLessons)
                            .then(function () {
                                return animateCircle('villegas-progress-final-circle', CONFIG.finalPercentage, CONFIG.circleDuration);
                            });
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', startAnimationSequence);
                    } else {
                        startAnimationSequence();
                    }
                })();
            </script>
            <?php
        }
        ?>
        <div class="villegas-progress-dashboard" role="group" aria-label="<?php echo esc_attr__( 'Resumen de progreso del curso', 'villegas-courses-plugin' ); ?>">
            <div class="villegas-progress-dashboard__column" data-progress-section="initial">
                <h2 class="villegas-progress-dashboard__title"><?php echo esc_html( $data['initial']['title'] ); ?></h2>
                <div class="villegas-progress-dashboard__circle-wrapper">
                    <svg viewBox="0 0 100 100" class="villegas-progress-dashboard__svg" aria-hidden="true" focusable="false">
                        <circle cx="50" cy="50" r="45" stroke-width="10" class="villegas-progress-dashboard__circle-bg"></circle>
                        <circle
                            id="villegas-progress-initial-circle"
                            cx="50"
                            cy="50"
                            r="45"
                            stroke-width="10"
                            class="villegas-progress-dashboard__circle"
                        ></circle>
                    </svg>
                    <span class="villegas-progress-dashboard__percentage"><?php echo esc_html( $data['initial']['percentage'] ); ?>%</span>
                </div>
                <p id="villegas-progress-initial-score" class="villegas-progress-dashboard__score"><?php echo esc_html( $data['initial']['description'] ); ?></p>
            </div>
            <div class="villegas-progress-dashboard__column" data-progress-section="lessons">
                <h2 class="villegas-progress-dashboard__title"><?php esc_html_e( 'Lecciones', 'villegas-courses-plugin' ); ?></h2>
                <ul class="villegas-progress-dashboard__lessons-list">
                    <?php foreach ( $data['lessons'] as $index => $lesson_label ) : ?>
                        <li id="villegas-progress-lesson-<?php echo esc_attr( $index + 1 ); ?>" class="villegas-progress-dashboard__lesson">
                            <svg class="villegas-progress-dashboard__lesson-icon" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="5" cy="5" r="4" fill="currentColor"></circle>
                            </svg>
                            <span class="villegas-progress-dashboard__lesson-label"><?php echo esc_html( $lesson_label ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="villegas-progress-dashboard__column" data-progress-section="final">
                <h2 class="villegas-progress-dashboard__title"><?php echo esc_html( $data['final']['title'] ); ?></h2>
                <div class="villegas-progress-dashboard__circle-wrapper">
                    <svg viewBox="0 0 100 100" class="villegas-progress-dashboard__svg" aria-hidden="true" focusable="false">
                        <circle cx="50" cy="50" r="45" stroke-width="10" class="villegas-progress-dashboard__circle-bg"></circle>
                        <circle
                            id="villegas-progress-final-circle"
                            cx="50"
                            cy="50"
                            r="45"
                            stroke-width="10"
                            class="villegas-progress-dashboard__circle"
                        ></circle>
                    </svg>
                    <span class="villegas-progress-dashboard__percentage"><?php echo esc_html( $data['final']['percentage'] ); ?>%</span>
                </div>
                <p id="villegas-progress-final-score" class="villegas-progress-dashboard__score"><?php echo esc_html( $data['final']['description'] ); ?></p>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    add_shortcode( 'villegas-circle-progress', 'villegas_circle_progress_shortcode' );
}
