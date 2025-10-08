<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function villegas_get_final_quiz_email_content( array $quiz_data, WP_User $user ): array {
    $debug = villegas_get_quiz_debug_data( $quiz_data, $user );

    if ( empty( $debug['is_final_quiz'] ) ) {
        return [ 'subject' => '', 'body' => '' ];
    }

    $course_title = $debug['course_title'];

    $first_score = isset( $debug['first_attempt']['percentage'] ) && null !== $debug['first_attempt']['percentage']
        ? (float) $debug['first_attempt']['percentage']
        : 0.0;

    $final_score = isset( $debug['final_attempt']['percentage'] ) && null !== $debug['final_attempt']['percentage']
        ? (float) $debug['final_attempt']['percentage']
        : 0.0;

    $first_timestamp = ! empty( $debug['first_attempt']['timestamp'] ) ? (int) $debug['first_attempt']['timestamp'] : null;
    $final_timestamp = ! empty( $debug['final_attempt']['timestamp'] ) ? (int) $debug['final_attempt']['timestamp'] : current_time( 'timestamp' );

    $first_date = $first_timestamp ? date_i18n( get_option( 'date_format' ), $first_timestamp ) : __( 'First Quiz pending', 'villegas-courses' );
    $final_date = date_i18n( get_option( 'date_format' ), $final_timestamp );

    $difference = round( $final_score ) - round( $first_score );

    if ( $difference > 0 ) {
        $progress_message = sprintf(
            /* translators: %d: improvement in points. */
            __( 'Wonderful! Your knowledge grew by %d points between quizzes.', 'villegas-courses' ),
            $difference
        );
        $progress_color = '#1b873c';
    } elseif ( 0 === $difference ) {
        $progress_message = __( 'Your results are consistent — solid knowledge retained!', 'villegas-courses' );
        $progress_color   = '#444444';
    } else {
        $progress_message = sprintf(
            /* translators: %d: decrease in points. */
            __( 'Your score decreased by %d points. Review the lessons and retake the quiz when you are ready.', 'villegas-courses' ),
            abs( $difference )
        );
        $progress_color = '#b42323';
    }

    $subject = sprintf(
        /* translators: %s: course title. */
        __( '✔️ Final Quiz completed: %s', 'villegas-courses' ),
        $course_title
    );

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

    $final_chart_url = villegas_generate_quickchart_url( $final_score );
    $first_chart_url = villegas_generate_quickchart_url( $first_score );

    $courses_url = home_url( '/courses/' );

    $body  = '<div style="background-color:#f6f6f6;padding:32px 0;">';
    $body .= '<div style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">';

    if ( $logo_url ) {
        $body .= '<div style="text-align:center;padding:28px 24px 0;">';
        $body .= '<img src="' . esc_url( $logo_url ) . '" alt="Villegas" style="max-width:220px;height:auto;">';
        $body .= '</div>';
    }

    $body .= '<div style="padding:20px 48px 24px;text-align:center;">';
    $body .= '<h1 style="margin:12px 0 8px;font-size:26px;color:#111111;">' . esc_html__( 'Congratulations!', 'villegas-courses' ) . '</h1>';
    $body .= '<p style="margin:0;font-size:16px;line-height:1.5;">' . sprintf( esc_html__( 'You finished the course %s.', 'villegas-courses' ), esc_html( $course_title ) ) . '</p>';
    $body .= '</div>';

    $body .= '<div style="padding:0 48px 24px;text-align:center;">';
    $body .= '<p style="margin:0;font-size:15px;color:' . esc_attr( $progress_color ) . ';font-weight:600;">' . esc_html( $progress_message ) . '</p>';
    $body .= '</div>';

    $body .= '<div style="display:flex;flex-wrap:wrap;gap:32px;justify-content:center;padding:0 48px 32px;border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;">';

    $body .= '<div style="text-align:center;min-width:220px;">';
    $body .= '<h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Final Quiz Result', 'villegas-courses' ) . '</h2>';
    $body .= '<img src="' . esc_url( $final_chart_url ) . '" alt="' . esc_attr__( 'Final Quiz score', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">';
    $body .= '<p style="margin:12px 0 4px;font-size:18px;font-weight:600;color:#111111;">' . esc_html( number_format_i18n( round( $final_score ) ) ) . '%</p>';
    $body .= '<p style="margin:0;font-size:13px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completed on %s', 'villegas-courses' ), esc_html( $final_date ) ) . '</p>';
    $body .= '</div>';

    $body .= '<div style="text-align:center;min-width:220px;">';
    $body .= '<h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'First Quiz Result', 'villegas-courses' ) . '</h2>';
    $body .= '<img src="' . esc_url( $first_chart_url ) . '" alt="' . esc_attr__( 'First Quiz score', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">';
    $body .= '<p style="margin:12px 0 4px;font-size:18px;font-weight:600;color:#111111;">' . esc_html( number_format_i18n( round( $first_score ) ) ) . '%</p>';
    $body .= '<p style="margin:0;font-size:13px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completed on %s', 'villegas-courses' ), esc_html( $first_date ) ) . '</p>';
    $body .= '</div>';

    $body .= '</div>';

    $body .= '<div style="padding:32px 48px;text-align:center;">';
    $body .= '<p style="margin:0 0 18px;font-size:15px;color:#333333;">' . esc_html__( 'Keep your momentum! Explore more Villegas courses to continue learning.', 'villegas-courses' ) . '</p>';
    $body .= '<a href="' . esc_url( $courses_url ) . '" style="display:inline-block;background:#000000;color:#ffffff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;">' . esc_html__( 'Browse Courses', 'villegas-courses' ) . '</a>';
    $body .= '</div>';

    $body .= '</div>';
    $body .= '</div>';

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}
