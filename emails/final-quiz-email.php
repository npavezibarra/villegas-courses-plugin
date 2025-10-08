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

    $first_date = $first_timestamp ? date_i18n( get_option( 'date_format' ), $first_timestamp ) : __( 'Primer quiz pendiente', 'villegas-courses' );
    $final_date = date_i18n( get_option( 'date_format' ), $final_timestamp );

    $difference = round( $final_score ) - round( $first_score );

    if ( $difference > 0 ) {
        $progress_message = sprintf(
            __( '¡Maravilloso! Tu conocimiento creció %d puntos entre el primer y el quiz final.', 'villegas-courses' ),
            $difference
        );
        $progress_color = '#1b873c';
    } elseif ( 0 === $difference ) {
        $progress_message = __( 'Tus resultados se mantienen consistentes: ¡conocimiento sólido!', 'villegas-courses' );
        $progress_color   = '#444444';
    } else {
        $progress_message = sprintf(
            __( 'Tu puntaje disminuyó %d puntos. Repasa las lecciones y vuelve a intentarlo cuando estés listo.', 'villegas-courses' ),
            abs( $difference )
        );
        $progress_color = '#b42323';
    }

    $subject = sprintf(
        __( '✔️ Quiz final completado: %s', 'villegas-courses' ),
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

    $inline_styles  = '<style type="text/css">@media only screen and (max-width:480px){#villegas-final-graficas td{display:block !important;width:100% !important;}}</style>';

    $body  = $inline_styles;
    $body .= '<div id="villegas-final-wrapper" style="background-color:#f6f6f6;padding:32px 0;">';
    $body .= '<div id="villegas-final-card" style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">';

    $body .= '<div id="villegas-final-encabezado" style="text-align:center;padding:28px 24px 0;">';
    if ( $logo_url ) {
        $body .= '<img src="' . esc_url( $logo_url ) . '" alt="Villegas" style="max-width:220px;height:auto;">';
    }
    $body .= '</div>';

    $body .= '<div id="villegas-final-resumen" style="padding:20px 48px 24px;text-align:center;">';
    $body .= '<h1 style="margin:12px 0 8px;font-size:26px;color:#111111;">' . esc_html__( '¡Felicitaciones!', 'villegas-courses' ) . '</h1>';
    $body .= '<p style="margin:0;font-size:16px;line-height:1.5;">' . sprintf( esc_html__( 'Terminaste el curso %s.', 'villegas-courses' ), esc_html( $course_title ) ) . '</p>';
    $body .= '<p style="margin:16px 0 0;font-size:15px;color:' . esc_attr( $progress_color ) . ';font-weight:600;">' . esc_html( $progress_message ) . '</p>';
    $body .= '</div>';

    $body .= '
<table id="villegas-final-graficas" width="100%" border="0" cellspacing="0" cellpadding="0" 
style="border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;padding:32px 0;text-align:center;">
  <tr>
    <td align="center">
      <table border="0" cellspacing="0" cellpadding="0" role="presentation">
        <tr>
          <td style="padding:0 18px;text-align:center;">
            <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Resultado Quiz Final', 'villegas-courses' ) . '</h2>
            <img src="' . esc_url( $final_chart_url ) . '" alt="' . esc_attr__( 'Puntaje del Quiz Final', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">
            <p style="margin:12px 0 4px;font-size:18px;font-weight:600;color:#111111;">' . esc_html( number_format_i18n( round( $final_score ) ) ) . '%</p>
            <p style="margin:0;font-size:13px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completado el %s', 'villegas-courses' ), esc_html( $final_date ) ) . '</p>
          </td>
          <td style="padding:0 18px;text-align:center;">
            <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Resultado Primer Quiz', 'villegas-courses' ) . '</h2>
            <img src="' . esc_url( $first_chart_url ) . '" alt="' . esc_attr__( 'Puntaje del Primer Quiz', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">
            <p style="margin:12px 0 4px;font-size:18px;font-weight:600;color:#111111;">' . esc_html( number_format_i18n( round( $first_score ) ) ) . '%</p>
            <p style="margin:0;font-size:13px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completado el %s', 'villegas-courses' ), esc_html( $first_date ) ) . '</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>';

    $body .= '<div id="villegas-final-cta" style="padding:32px 48px;text-align:center;">';
    $body .= '<p style="margin:0 0 18px;font-size:15px;color:#333333;">' . esc_html__( 'Mantén el impulso: explora más cursos de Villegas para seguir aprendiendo.', 'villegas-courses' ) . '</p>';
    $body .= '<a href="' . esc_url( $courses_url ) . '" style="display:inline-block;background:#000000;color:#ffffff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;">' . esc_html__( 'Ver cursos', 'villegas-courses' ) . '</a>';
    $body .= '</div>';

    $body .= '</div>';
    $body .= '</div>';

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}
