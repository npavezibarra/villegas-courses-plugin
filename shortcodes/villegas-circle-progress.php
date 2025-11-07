<?php
/**
 * Shortcode: [villegas-circle-progress]
 *
 * Outputs a dual circular progress component used in Villegas Courses.
 */

if (!function_exists('villegas_circle_progress_shortcode')) {
    /**
     * Render the Villegas circle progress shortcode.
     *
     * @return string
     */
    function villegas_circle_progress_shortcode()
    {
        static $style_printed = false;

        $cards = [
            [
                'title' => __('Evaluación Inicial', 'villegas-courses-plugin'),
                'percentage' => 43,
                'description' => __('26 respuestas correctas de 60.', 'villegas-courses-plugin'),
                'progress_class' => 'villegas-circle-progress__circle--43',
                'aria_label' => __('Evaluación inicial con un 43 por ciento de progreso', 'villegas-courses-plugin'),
            ],
            [
                'title' => __('Evaluación Final', 'villegas-courses-plugin'),
                'percentage' => 78,
                'description' => __('47 respuestas correctas de 60.', 'villegas-courses-plugin'),
                'progress_class' => 'villegas-circle-progress__circle--78',
                'aria_label' => __('Evaluación final con un 78 por ciento de progreso', 'villegas-courses-plugin'),
            ],
        ];

        ob_start();

        if (!$style_printed) {
            $style_printed = true;
            ?>
            <style>
                .villegas-circle-progress {
                    --villegas-circle-progress-circumference: 283;
                    --villegas-circle-progress-color-gold: #ffc300;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 2rem;
                    justify-content: center;
                    padding: 1.5rem;
                    font-family: "Inter", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #f9fafb;
                }

                .villegas-circle-progress__card {
                    background-color: #ffffff;
                    border-radius: 0.75rem;
                    box-shadow: 0 25px 50px -12px rgb(30 41 59 / 0.25);
                    flex: 1 1 280px;
                    max-width: 420px;
                    padding: 2rem;
                    text-align: center;
                    transition: box-shadow 0.3s ease, transform 0.3s ease;
                }

                .villegas-circle-progress__card:hover,
                .villegas-circle-progress__card:focus-within {
                    box-shadow: 0 35px 60px -15px rgb(30 41 59 / 0.3);
                    transform: scale(1.02);
                }

                .villegas-circle-progress__title {
                    font-size: 1.875rem;
                    font-weight: 800;
                    color: #1f2937;
                    margin-bottom: 2rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #f3f4f6;
                }

                .villegas-circle-progress__svg-wrapper {
                    position: relative;
                    width: 12rem;
                    height: 12rem;
                    margin: 0 auto;
                }

                .villegas-circle-progress__svg {
                    width: 100%;
                    height: 100%;
                    transform: rotate(-90deg);
                }

                .villegas-circle-progress__circle-bg {
                    stroke: #e5e7eb;
                    fill: none;
                }

                .villegas-circle-progress__circle {
                    stroke: var(--villegas-circle-progress-color-gold);
                    stroke-width: 10;
                    stroke-dasharray: var(--villegas-circle-progress-circumference);
                    stroke-linecap: round;
                    fill: none;
                    stroke-dashoffset: var(--villegas-circle-progress-circumference);
                    transition: stroke-dashoffset 0.1s ease;
                }

                .villegas-circle-progress__percentage {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 3rem;
                    font-weight: 700;
                    color: #1f2937;
                }

                .villegas-circle-progress__description {
                    margin-top: 1rem;
                    font-size: 1.125rem;
                    font-weight: 500;
                    color: #6b7280;
                }

                .villegas-circle-progress__circle--43 {
                    --villegas-circle-progress-offset-43: 161.3;
                    animation: villegas-circle-progress-draw-43 2s ease-out forwards;
                }

                .villegas-circle-progress__circle--78 {
                    --villegas-circle-progress-offset-78: 62.26;
                    animation: villegas-circle-progress-draw-78 2.5s ease-out 2s forwards;
                }

                @keyframes villegas-circle-progress-draw-43 {
                    to {
                        stroke-dashoffset: var(--villegas-circle-progress-offset-43);
                    }
                }

                @keyframes villegas-circle-progress-draw-78 {
                    to {
                        stroke-dashoffset: var(--villegas-circle-progress-offset-78);
                    }
                }
            </style>
            <?php
        }
        ?>
        <div class="villegas-circle-progress" role="group" aria-label="<?php echo esc_attr__('Comparación de progreso de evaluaciones', 'villegas-courses-plugin'); ?>">
            <?php foreach ($cards as $card) : ?>
                <article class="villegas-circle-progress__card" aria-label="<?php echo esc_attr($card['aria_label']); ?>">
                    <h2 class="villegas-circle-progress__title"><?php echo esc_html($card['title']); ?></h2>
                    <div class="villegas-circle-progress__svg-wrapper">
                        <svg viewBox="0 0 100 100" class="villegas-circle-progress__svg" role="img" aria-hidden="true" focusable="false">
                            <circle cx="50" cy="50" r="45" stroke-width="10" class="villegas-circle-progress__circle-bg"></circle>
                            <circle cx="50" cy="50" r="45" stroke-width="10" class="villegas-circle-progress__circle <?php echo esc_attr($card['progress_class']); ?>"></circle>
                        </svg>
                        <span class="villegas-circle-progress__percentage"><?php echo esc_html($card['percentage']); ?>%</span>
                    </div>
                    <p class="villegas-circle-progress__description"><?php echo esc_html($card['description']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    add_shortcode('villegas-circle-progress', 'villegas_circle_progress_shortcode');
}
