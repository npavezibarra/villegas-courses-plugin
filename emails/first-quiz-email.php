<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function villegas_get_first_quiz_email_content( array $quiz_data, WP_User $user ): array {

    $debug = villegas_get_quiz_debug_data( $quiz_data, $user );

    if ( empty( $debug['is_first_quiz'] ) ) {
        return [ 'subject' => '', 'body' => '' ];
    }

    // SUBJECT
    $subject = sprintf(
        __( '✔️ First Quiz Completed: %s', 'villegas-courses' ),
        $debug['quiz_title']
    );

    // DATE
    $completion_timestamp = $debug['first_attempt']['timestamp'] ?? current_time('timestamp');
    $completion_date      = date_i18n( get_option('date_format'), $completion_timestamp );

    // SCORES
    $user_score     = villegas_normalize_percentage_value( $quiz_data['percentage'] ?? 0 );
    $average_value  = Villegas_Quiz_Stats::get_average_percentage( $debug['quiz_post_id'] ?? $debug['quiz_id'] ) ?? 0;

    $user_score_int    = villegas_round_half_up( $user_score );
    $average_score_int = villegas_round_half_up( $average_value );

    $user_display_percent    = $user_score_int . '%';
    $average_display_percent = $average_score_int . '%';

    // CHART URLS
    $user_chart_url    = villegas_generate_quickchart_url( $user_score_int, $user_score_int );
    $average_chart_url = villegas_generate_quickchart_url( $average_score_int, $average_score_int );

    // BACKGROUND
    $background_image_url = 'https://elvillegas.cl/wp-content/uploads/2025/04/default-bg.jpg';

    // LOGO
    $logo_url = 'https://elvillegas.cl/wp-content/plugins/villegas-courses-plugin/assets/jpg/academia-email-logo.jpeg';

    // MAKE VARIABLES AVAILABLE TO TEMPLATE
    extract([
        'debug'                  => $debug,
        'completion_date'        => $completion_date,
        'subject'                => $subject,
        'user_display_percent'   => $user_display_percent,
        'average_display_percent'=> $average_display_percent,
        'user_chart_url'         => $user_chart_url,
        'average_chart_url'      => $average_chart_url,
        'background_image_url'   => $background_image_url,
        'logo_url'               => $logo_url,
    ]);

    // BUILD EMAIL USING TEMPLATE
    ob_start();
    include __DIR__ . '/first-quiz-email-template.php';
    $body = ob_get_clean();

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}
