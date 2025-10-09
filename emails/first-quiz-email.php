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

    $latest_attempt = Villegas_Quiz_Stats::get_latest_attempt_percentage( $quiz_id, $user->ID );
    $user_score     = $latest_attempt ? (float) $latest_attempt : 0.0;

    $average_score = null;

    if ( $quiz_id ) {
        $average_score = Villegas_Quiz_Stats::get_average_percentage( $quiz_id );
    }

    $average_value = null !== $average_score ? (float) $average_score : 0.0;

    $subject = sprintf(
        __( '✔️ Primer quiz completado: %s', 'villegas-courses' ),
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

    $button_label = __( 'Ir al curso', 'villegas-courses' );
    $button_url   = $course_url;
    $button_note  = __( 'Continúa con las lecciones para prepararte para el Quiz Final.', 'villegas-courses' );

    if ( ! $is_free_course && ! $has_access ) {
        $product_id = $course_id ? Villegas_Course::get_related_product_id( $course_id ) : 0;

        if ( $product_id && function_exists( 'wc_get_checkout_url' ) ) {
            $button_url = add_query_arg( 'add-to-cart', $product_id, wc_get_checkout_url() );
        } elseif ( $product_id ) {
            $button_url = get_permalink( $product_id );
        } else {
            $button_url = home_url( '/courses/' );
        }

        $button_label = __( 'Comprar curso', 'villegas-courses' );
        $button_note  = __( 'Compra el curso para desbloquear todas las lecciones y el Quiz Final.', 'villegas-courses' );
    }

    // Prefer externally accessible HTTPS assets so email clients can always load the header image.
    $logo_candidates = [
        'https://elvillegas.cl/wp-content/plugins/villegas-courses-plugin/assets/jpg/academia-email-logo.jpeg',
        'https://raw.githubusercontent.com/npavezibarra/villegas-courses-plugin/main/assets/jpg/academia-email-logo.jpeg',
    ];

    $logo_url = '';

    if ( function_exists( 'wp_remote_head' ) ) {
        foreach ( $logo_candidates as $candidate_url ) {
            $response = wp_remote_head( $candidate_url, [ 'timeout' => 5 ] );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $status_code = (int) wp_remote_retrieve_response_code( $response );

            if ( $status_code >= 200 && $status_code < 400 ) {
                $logo_url = $candidate_url;
                break;
            }
        }
    }

    if ( ! $logo_url ) {
        $logo_url = reset( $logo_candidates );
    }

    if ( ! $logo_url ) {
        $logo_url = get_site_icon_url( 192 );
    }

    $user_chart_url    = villegas_generate_quickchart_url( $user_score );
    $average_chart_url = villegas_generate_quickchart_url( $average_value );

    $inline_styles  = '<style type="text/css">
  @media only screen and (max-width: 500px) {
    #villegas-email-graficas td {
      display: block !important;
      width: 90% !important;
      margin: 0 auto !important;
      text-align: center !important;
    }
    #villegas-email-graficas h2 {
      margin-top: 24px !important;
    }
  }
</style>';

    $body  = $inline_styles;
    $body .= '<div id="villegas-email-wrapper" style="background-color:#f6f6f6;padding:32px 0;">';
    $body .= '<div id="villegas-email-card" style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">';

    $body .= '<div id="villegas-email-encabezado" style="text-align:center;padding:0;">';
    if ( $logo_url ) {
        $body .= '<img src="' . esc_url( $logo_url ) . '" alt="Academia Villegas" style="width:100%;max-width:720px;height:200px;object-fit:cover;object-position:center;display:block;margin:0 auto;">';
    }
    $body .= '</div>';

    $body .= '<div id="villegas-email-presentacion" style="padding:20px 48px 32px;text-align:center;">';
    $body .= '<p style="margin:0;font-size:12px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completado el %s', 'villegas-courses' ), esc_html( $completion_date ) ) . '</p>';
    $body .= '<h1 style="margin:12px 0 8px;font-size:26px;color:#111111;">' . sprintf( esc_html__( '¡Gran trabajo, %s!', 'villegas-courses' ), esc_html( $debug['user_display_name'] ) ) . '</h1>';
    $body .= '<p style="margin:0;font-size:16px;line-height:1.5;">' . sprintf( esc_html__( 'Completaste el Primer Quiz de %s.', 'villegas-courses' ), esc_html( $debug['course_title'] ) ) . '</p>';
    $body .= '</div>';

    $body .= '
<table id="villegas-email-graficas" width="100%" border="0" cellspacing="0" cellpadding="0" 
style="border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;padding:32px 0;text-align:center;">
  <tr>
    <td align="center">
      <table border="0" cellspacing="0" cellpadding="0" role="presentation">
        <tr>
          <td style="padding:0 14px;text-align:center;">
            <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Tu puntaje', 'villegas-courses' ) . '</h2>
            <img src="' . esc_url( $user_chart_url ) . '" alt="' . esc_attr__( 'Tu puntaje', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">
          </td>
          <td style="padding:0 14px;text-align:center;">
            <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Promedio Villegas', 'villegas-courses' ) . '</h2>
            <img src="' . esc_url( $average_chart_url ) . '" alt="' . esc_attr__( 'Promedio Villegas', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>';

    $body .= '<div id="villegas-email-cta" style="padding:32px 48px;text-align:center;">';
    $body .= '<p style="margin:0 0 18px;font-size:15px;color:#333333;">' . esc_html__( 'Cada lección completada te acerca a comparar tu progreso en el Quiz Final.', 'villegas-courses' ) . '</p>';
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
