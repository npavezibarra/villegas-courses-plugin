<?php
if ( ! class_exists( 'Villegas_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-villegas-quiz-stats.php';
}

/**
 * Shortcode to render the average score pie chart for a quiz.
 *
 * Usage: [villegas_quiz_average_score quiz_id="123" decimals="0" title="Average Score"]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function villegas_quiz_average_score_shortcode( $atts ): string {
    $atts = shortcode_atts(
        [
            'quiz_id'  => 0,
            'decimals' => 0,
            'title'    => __( 'Puntaje Promedio', 'villegas-courses' ),
        ],
        $atts,
        'villegas_quiz_average_score'
    );

    $quiz_id  = intval( $atts['quiz_id'] );
    $decimals = max( 0, intval( $atts['decimals'] ) );
    $title    = sanitize_text_field( $atts['title'] );

    if ( ! $quiz_id ) {
        global $post;

        if ( $post instanceof WP_Post && 'sfwd-quiz' === $post->post_type ) {
            $quiz_id = (int) $post->ID;
        }
    }

    if ( ! $quiz_id ) {
        return '';
    }

    $average = Villegas_Quiz_Stats::get_average_percentage( $quiz_id );

    $has_data      = null !== $average;
    $percent_value = $has_data ? Villegas_Quiz_Stats::format_percentage( (float) $average ) : 0;

    $label = $has_data
        ? sprintf( '%s%%', number_format_i18n( $percent_value ) )
        : __( 'No data', 'villegas-courses' );

    $classes = [ 'wpProQuiz_pointsChart', 'wpProQuiz_pointsChart--average' ];

    if ( ! $has_data ) {
        $classes[] = 'wpProQuiz_pointsChart--empty';
    }

    $attributes = [
        'id'              => 'wpProQuiz_pointsChartAverage',
        'class'           => implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
        'aria-live'       => 'polite',
        'data-chart-id'   => 'average-score',
        'data-chart-title'=> $title,
        'style'           => 'display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em 0 1em 1em;',
    ];

    if ( $has_data ) {
        $attributes['data-static-percent'] = (string) $percent_value;
    }

    $attributes_markup = '';

    foreach ( $attributes as $attr => $value ) {
        $attributes_markup .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
    }

    ob_start();
    ?>
    <div<?php echo $attributes_markup; ?>>
        <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" style="width: 120px; height: 120px;">
            <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke="#E3E3E3" stroke-width="4"></circle>
            <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke="#f9c600" stroke-width="4" stroke-linecap="round" stroke-dasharray="0 100" stroke-dashoffset="25.12" transform="rotate(-90 18 18)"></circle>
        </svg>
        <div class="wpProQuiz_pointsChart__label" style="font-weight: 600;">
            <?php echo esc_html( $label ); ?>
        </div>
        <div class="wpProQuiz_pointsChart__caption" style="font-size: 14px;">
            <?php echo esc_html( $title ); ?>
        </div>
        <?php if ( ! $has_data ) : ?>
            <div class="wpProQuiz_pointsChart__empty-message" style="font-size: 12px; color: #666;">
                <?php esc_html_e( 'No attempts recorded yet.', 'villegas-courses' ); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return trim( ob_get_clean() );
}
add_shortcode( 'villegas_quiz_average_score', 'villegas_quiz_average_score_shortcode' );
