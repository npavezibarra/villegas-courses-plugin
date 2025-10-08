<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function villegas_get_first_quiz_email_content( array $quiz_data, WP_User $user ): array {
    $debug = villegas_get_quiz_debug_data( $quiz_data, $user );

    if ( empty( $debug['is_first_quiz'] ) ) {
        return [ 'subject' => '', 'body' => '' ];
    }

    $quiz_id   = $debug['quiz_id'];
    $course_id = $debug['course_id'];

    $user_score = isset( $debug['first_attempt']['percentage'] ) && null !== $debug['first_attempt']['percentage']
        ? (float) $debug['first_attempt']['percentage']
        : 0.0;

    $average_score = null;

    if ( $quiz_id ) {
        $average_score = Villegas_Quiz_Stats::get_average_percentage( $quiz_id );
    }

    $average_value = null !== $average_score ? (float) $average_score : 0.0;

    $subject = sprintf(
        /* translators: %s: quiz title. */
        __( '✔️ First Quiz completed: %s', 'villegas-courses' ),
        $debug['quiz_title']
    );

    $completion_timestamp = ! empty( $debug['first_attempt']['timestamp'] ) ? (int) $debug['first_attempt']['timestamp'] : current_time( 'timestamp' );
    $completion_date      = date_i18n( get_option( 'date_format' ), $completion_timestamp );

    $course_url = $course_id ? get_permalink( $course_id ) : home_url( '/' );

    $course_price_type = $course_id && function_exists( 'learndash_get_setting' )
        ? learndash_get_setting( $course_id, 'course_price_type' )
        : '';

    $is_free_course = in_array( $course_price_type, [ 'free', 'open' ], true );
    $has_access     = $course_id ? Villegas_Course::user_has_access( $course_id, $user->ID ) : false;

    $button_label = __( 'Go to Course', 'villegas-courses' );
    $button_url   = $course_url;
    $button_note  = __( 'Continue with the lessons to prepare for the Final Quiz.', 'villegas-courses' );

    if ( ! $is_free_course && ! $has_access ) {
        $product_id = $course_id ? Villegas_Course::get_related_product_id( $course_id ) : 0;

        if ( $product_id && function_exists( 'wc_get_checkout_url' ) ) {
            $button_url = add_query_arg( 'add-to-cart', $product_id, wc_get_checkout_url() );
        } elseif ( $product_id ) {
            $button_url = get_permalink( $product_id );
        } else {
            $button_url = home_url( '/courses/' );
        }

        $button_label = __( 'Buy Course', 'villegas-courses' );
        $button_note  = __( 'Purchase the course to unlock every lesson and the Final Quiz.', 'villegas-courses' );
    }

    $logo_url = '';

    if ( function_exists( 'get_theme_mod' ) ) {
        $logo_id = (int) get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            $logo_src = wp_get_attachment_image_src( $logo_id, 'full' );
            if ( $logo_src ) {
                $logo_url = $logo_src[0];
            }
        }
    }

    if ( ! $logo_url ) {
        $logo_url = get_site_icon_url( 192 );
    }

    $user_chart_url    = villegas_generate_quickchart_url( $user_score );
    $average_chart_url = villegas_generate_quickchart_url( $average_value );

    $average_caption = null !== $average_score
        ? sprintf( __( 'Villegas average: %s%%', 'villegas-courses' ), number_format_i18n( round( $average_value ) ) )
        : __( 'Villegas average: no attempts yet', 'villegas-courses' );

    $body  = '<div style="background-color:#f6f6f6;padding:32px 0;">';
    $body .= '<div style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">';

    if ( $logo_url ) {
        $body .= '<div style="text-align:center;padding:28px 24px 0;">';
        $body .= '<img src="' . esc_url( $logo_url ) . '" alt="Villegas" style="max-width:220px;height:auto;">';
        $body .= '</div>';
    }

    $body .= '<div style="padding:20px 48px 32px;text-align:center;">';
    $body .= '<p style="margin:0;font-size:12px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completed on %s', 'villegas-courses' ), esc_html( $completion_date ) ) . '</p>';
    $body .= '<h1 style="margin:12px 0 8px;font-size:26px;color:#111111;">' . sprintf( esc_html__( 'Great job, %s!', 'villegas-courses' ), esc_html( $debug['user_display_name'] ) ) . '</h1>';
    $body .= '<p style="margin:0;font-size:16px;line-height:1.5;">' . sprintf( esc_html__( 'You completed the First Quiz of %s.', 'villegas-courses' ), esc_html( $debug['course_title'] ) ) . '</p>';
    $body .= '</div>';

    $body .= '<div style="display:flex;flex-wrap:wrap;gap:32px;justify-content:center;padding:0 48px 32px;border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;">';

    $body .= '<div style="text-align:center;min-width:220px;">';
    $body .= '<h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Your Score', 'villegas-courses' ) . '</h2>';
    $body .= '<img src="' . esc_url( $user_chart_url ) . '" alt="' . esc_attr__( 'Your score', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">';
    $body .= '<p style="margin-top:12px;font-size:18px;font-weight:600;color:#111111;">' . esc_html( number_format_i18n( round( $user_score ) ) ) . '%</p>';
    $body .= '</div>';

    $body .= '<div style="text-align:center;min-width:220px;">';
    $body .= '<h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Villegas Average', 'villegas-courses' ) . '</h2>';
    $body .= '<img src="' . esc_url( $average_chart_url ) . '" alt="' . esc_attr__( 'Villegas average', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">';
    $body .= '<p style="margin-top:12px;font-size:15px;color:#444444;">' . esc_html( $average_caption ) . '</p>';
    $body .= '</div>';

    $body .= '</div>';

    $body .= '<div style="padding:32px 48px;text-align:center;">';
    $body .= '<p style="margin:0 0 18px;font-size:15px;color:#333333;">' . esc_html__( 'Every lesson you finish will bring you closer to comparing your progress in the Final Quiz.', 'villegas-courses' ) . '</p>';
    $body .= '<a href="' . esc_url( $button_url ) . '" style="display:inline-block;background:#000000;color:#ffffff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;">' . esc_html( $button_label ) . '</a>';
    $body .= '<p style="margin-top:16px;font-size:13px;color:#666666;">' . esc_html( $button_note ) . '</p>';
    $body .= '</div>';

    $body .= '</div>';
    $body .= '</div>';

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}
