<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function villegas_get_first_quiz_email_content( array $quiz_data, WP_User $user ): array {
    error_log( '--- [FirstQuizEmail] Building template ---' );
    error_log( '[FirstQuizEmail] Incoming quiz_data=' . print_r( $quiz_data, true ) );

    $debug = villegas_get_quiz_debug_data( $quiz_data, $user );
    error_log( '[FirstQuizEmail] Debug data (partial)=' . print_r( array_slice( $debug, 0, 3, true ), true ) );

    if ( empty( $debug['is_first_quiz'] ) ) {
        error_log( '[FirstQuizEmail] Not first quiz, aborting.' );
        return [ 'subject' => '', 'body' => '' ];
    }

    $quiz_id      = $debug['quiz_id'];
    $quiz_post_id = ! empty( $debug['quiz_post_id'] ) ? (int) $debug['quiz_post_id'] : 0;

    if ( ! $quiz_post_id && ! empty( $debug['quiz_id'] ) ) {
        $potential_post_id = (int) $debug['quiz_id'];

        if ( $potential_post_id && 'sfwd-quiz' === get_post_type( $potential_post_id ) ) {
            $quiz_post_id = $potential_post_id;
        }
    }
    $course_id    = $debug['course_id'];

    $default_background_image_url = 'https://elvillegas.cl/wp-content/uploads/2025/04/default-bg.jpg';
    $background_image_url         = '';

    if ( $quiz_post_id ) {
        $background_image_id = get_post_meta( $quiz_post_id, '_quiz_style_image', true );

        if ( $background_image_id ) {
            $background_image_url = wp_get_attachment_url( (int) $background_image_id );
        }
    }

    if ( ! $background_image_url && $quiz_id && (int) $quiz_id !== $quiz_post_id ) {
        $background_image_id = get_post_meta( (int) $quiz_id, '_quiz_style_image', true );

        if ( $background_image_id ) {
            $background_image_url = wp_get_attachment_url( (int) $background_image_id );
        }
    }

    $background_image_url = function_exists( 'villegas_normalize_email_asset_url' )
        ? villegas_normalize_email_asset_url( $background_image_url ?: '', $default_background_image_url )
        : ( $background_image_url ?: $default_background_image_url );

    $current_percentage = villegas_normalize_percentage_value( $quiz_data['percentage'] ?? null );
    error_log( '[FirstQuizEmail] Normalized percentage=' . var_export( $current_percentage, true ) );

    $user_score = null !== $current_percentage ? $current_percentage : 0.0;
    $user_score = max( 0.0, min( 100.0, $user_score ) );

    $stats_quiz_id = 0;

    if ( ! empty( $debug['quiz_post_id'] ) ) {
        $stats_quiz_id = (int) $debug['quiz_post_id'];
    } elseif ( ! empty( $debug['quiz_pro_id'] ) && function_exists( 'learndash_get_quiz_id_by_pro_quiz_id' ) ) {
        $stats_quiz_id = (int) learndash_get_quiz_id_by_pro_quiz_id( (int) $debug['quiz_pro_id'] );
    }

    if ( ! $stats_quiz_id ) {
        $stats_quiz_id = (int) $quiz_id;
    }

    if ( $stats_quiz_id && 'sfwd-quiz' !== get_post_type( $stats_quiz_id ) && function_exists( 'learndash_get_quiz_id_by_pro_quiz_id' ) ) {
        $resolved_stats_id = (int) learndash_get_quiz_id_by_pro_quiz_id( $stats_quiz_id );

        if ( $resolved_stats_id ) {
            $stats_quiz_id = $resolved_stats_id;
        }
    }

    if ( $quiz_post_id && 'sfwd-quiz' === get_post_type( $quiz_post_id ) ) {
        $stats_quiz_id = $quiz_post_id;
    }

    $incoming_average = array_key_exists( 'average', $quiz_data )
        ? villegas_normalize_percentage_value( $quiz_data['average'] )
        : null;

    if ( null !== $incoming_average ) {
        $average_score = $incoming_average;
    } else {
        $average_score = Villegas_Quiz_Stats::get_average_percentage( $stats_quiz_id );
    }

    $average_value = null !== $average_score ? max( 0.0, min( 100.0, (float) $average_score ) ) : 0.0;

    $user_display_percent    = Villegas_Quiz_Stats::format_percentage( $user_score );
    $average_display_percent = null !== $average_score ? Villegas_Quiz_Stats::format_percentage( (float) $average_score ) : 0;

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

    $user_chart_url    = villegas_generate_quickchart_url( $user_score, $user_display_percent );
    $average_chart_url = villegas_generate_quickchart_url( $average_value, $average_display_percent );

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

  table[id$="villegas-email-card"] {
    border-radius: 8px;
    overflow: hidden;
  }

  /* Desktop and large screens (1024px and above) */
  @media only screen and (min-width: 1024px) {
    #villegas-email-logo {
      width: 76% !important;
      height: 170px !important;
    }
  }

  /* Mobile and tablet (below 1024px) */
  @media only screen and (max-width: 1023px) {
    #villegas-email-logo {
      width: 100% !important;
      height: 140px !important;
    }
  }

  @media only screen and (max-width: 600px) {
    .villegas-circle-container,
    .villegas-circle-wrapper {
      margin-left: auto !important;
      margin-right: auto !important;
      text-align: center !important;
    }

    .villegas-first-circle {
      margin-bottom: 40px !important;
    }

    #villegas-final-title-row td,
    #villegas-final-title-row {
      padding-top: 40px !important;
    }
  }
</style>';

    $background_color          = '#f6f6f6';
    $background_image_attr_url = $background_image_url ? esc_url( $background_image_url ) : '';
    $wrapper_background_attr   = $background_image_attr_url ? ' background="' . $background_image_attr_url . '"' : '';
    $wrapper_bgcolor_attr      = ' bgcolor="' . esc_attr( $background_color ) . '"';

    $background_style_rules = [ 'background-color:' . $background_color . ';' ];

    if ( $background_image_attr_url ) {
        $background_style_rules[] = "background-image:url('{$background_image_attr_url}');";
        $background_style_rules[] = 'background-repeat:no-repeat;';
        $background_style_rules[] = 'background-position:center center;';
        $background_style_rules[] = 'background-size:cover;';
    }

    $wrapper_background_style = implode( '', $background_style_rules );
    $wrapper_div_style        = $wrapper_background_style . 'padding:32px 0;';

    $body  = $inline_styles;
    $body .= '<table id="villegas-email-wrapper" role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"' . $wrapper_background_attr . $wrapper_bgcolor_attr . ' style="' . $wrapper_background_style . '">';
    $body .= '<tr>';
    $body .= '<td align="center" valign="top"' . $wrapper_background_attr . $wrapper_bgcolor_attr . ' style="' . $wrapper_background_style . '">';

    if ( $background_image_attr_url ) {
        $body .= '<!--[if gte mso 9]>';
        $body .= '<v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">';
        $body .= '<v:fill type="frame" src="' . $background_image_attr_url . '" color="' . esc_attr( $background_color ) . '" />';
        $body .= '</v:background>';
        $body .= '<![endif]-->';
    }

    $body .= '<div style="' . $wrapper_div_style . '">';
    $body .= '<table id="villegas-email-card" role="presentation" width="720" border="0" cellspacing="0" cellpadding="0" style="width:100%;max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">';

    $body .= '<tr>';
    $body .= '<td id="villegas-email-encabezado" style="text-align:center;padding:0;background:black;border-radius:8px 8px 0px 0px;">';
    if ( $logo_url ) {
        $body .= '<img id="villegas-email-logo" src="' . esc_url( $logo_url ) . '" alt="Academia Villegas" style="width:100%;max-width:720px;height:170px;object-fit:cover;object-position:center;display:block;margin:0 auto;border-top-left-radius:8px;border-top-right-radius:8px;">';
    }
    $body .= '</td>';
    $body .= '</tr>';

    $body .= '<tr>';
    $body .= '<td id="villegas-email-presentacion" style="padding:20px 48px 32px;text-align:center;">';
    $body .= '<p style="margin:0;font-size:14px;color:#6d6d6d;">' . sprintf( esc_html__( 'Completado el %s', 'villegas-courses' ), esc_html( $completion_date ) ) . '</p>';
    $body .= '<h1 style="margin:12px 0 8px;font-size:26px;color:#111111;line-height:1;">' . sprintf( esc_html__( '¡Gran trabajo, %s!', 'villegas-courses' ), esc_html( $debug['user_display_name'] ) ) . '</h1>';
    $body .= '<div style="font-size:18px;line-height:1.6;">';
    $body .= '<p style="margin:0;color:#1c1c1c;">' . sprintf( esc_html__( 'Completaste el Primer Quiz de %s.', 'villegas-courses' ), esc_html( $debug['course_title'] ) ) . '</p>';
    $body .= '</div>';
    $body .= '</td>';
    $body .= '</tr>';

    $body .= '<tr>';
    $body .= '<td style="padding:0 32px;">';
    $body .= '<table id="villegas-email-graficas" role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;padding:32px 0;text-align:center;">';
    $body .= '<tr>';
    $body .= '<td align="center">';
    $body .= '<table class="villegas-circle-wrapper" border="0" cellspacing="0" cellpadding="0" role="presentation">';
    $body .= '<tr>';
    $body .= '<td class="villegas-circle-container villegas-first-circle" style="padding:0 14px;text-align:center;">';
    $body .= '<h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Tu puntaje', 'villegas-courses' ) . '</h2>';
    $body .= '<img src="' . esc_url( $user_chart_url ) . '" alt="' . esc_attr__( 'Tu puntaje', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">';
    $body .= '</td>';
    $body .= '<td id="villegas-final-title-row" class="villegas-circle-container" style="padding:0 14px;text-align:center;">';
    $body .= '<h2 style="font-size:16px;margin-bottom:12px;color:#111111;">' . esc_html__( 'Promedio Villegas', 'villegas-courses' ) . '</h2>';
    $body .= '<img src="' . esc_url( $average_chart_url ) . '" alt="' . esc_attr__( 'Promedio Villegas', 'villegas-courses' ) . '" style="max-width:240px;height:auto;">';
    $body .= '</td>';
    $body .= '</tr>';
    $body .= '</table>';
    $body .= '</td>';
    $body .= '</tr>';
    $body .= '</table>';
    $body .= '</td>';
    $body .= '</tr>';

    $body .= '<tr>';
    $body .= '<td id="villegas-email-cta" style="padding:32px 48px;text-align:center;">';
    $body .= '<div style="font-size:18px;line-height:1.6;color:#333333;">';
    $body .= '<p style="margin:0 0 18px;color:#1c1c1c;">' . esc_html__( 'Cada lección completada te acerca a comparar tu progreso en el Quiz Final.', 'villegas-courses' ) . '</p>';
    $body .= '</div>';
    $body .= '<a href="' . esc_url( $button_url ) . '" style="display:inline-block;background:#000000;color:#ffffff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;">' . esc_html( $button_label ) . '</a>';
    $body .= '<p style="margin-top:16px;font-size:13px;color:#666666;">' . esc_html( $button_note ) . '</p>';
    $body .= '</td>';
    $body .= '</tr>';

    $body .= '</table>';
    $body .= '</div><!-- /villegas-background -->';
    $body .= '</td>';
    $body .= '</tr>';
    $body .= '</table>';


    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}
