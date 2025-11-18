<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function villegas_get_final_quiz_email_content( array $debug, WP_User $user ): array {
    error_log( '[FinalQuizEmail] villegas_get_final_quiz_email_content START' );

    if ( empty( $debug['is_final_quiz'] ) ) {
        error_log( '[FinalQuizEmail] Debug payload is not final quiz. Returning empty content.' );
        return [
            'subject'             => '',
            'body'                => '',
            'initial_percentage'  => null,
            'final_percentage'    => null,
        ];
    }

    $first_score = villegas_normalize_percentage_value( $debug['first_attempt']['percentage'] ?? null );
    $final_score = villegas_normalize_percentage_value( $debug['final_attempt']['percentage'] ?? null );

    $first_score = null !== $first_score ? round( max( 0, min( 100, $first_score ) ), 2 ) : null;
    $final_score = null !== $final_score ? round( max( 0, min( 100, $final_score ) ), 2 ) : null;

    error_log(
        sprintf(
            '[FinalQuizEmail] Content builder percentages: initial=%s final=%s',
            null === $first_score ? 'null' : $first_score,
            null === $final_score ? 'null' : $final_score
        )
    );

    $course_title = $debug['course_title'] ?: ( $debug['quiz_title'] ?? '' );
    $quiz_title   = $debug['quiz_title'] ?: $course_title;
    $quiz_id      = isset( $debug['quiz_id'] ) ? (int) $debug['quiz_id'] : 0;
    $quiz_post_id = ! empty( $debug['quiz_post_id'] ) ? (int) $debug['quiz_post_id'] : 0;
    $course_id    = ! empty( $debug['course_id'] ) ? (int) $debug['course_id'] : 0;

    $email_file = plugin_dir_path( __FILE__ ) . 'final-quiz-email.php';
    if ( file_exists( $email_file ) ) {
        $email_body = file_get_contents( $email_file );
    } else {
        $email_body = '<p>Has finalizado la Evaluación Final.</p>';
    }

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

    $background_color          = '#f6f6f6';
    $background_image_attr_url = $background_image_url ? esc_url( $background_image_url ) : '';

    $background_style_rules = [ 'background-color:' . $background_color . ';' ];

    if ( $background_image_attr_url ) {
        $background_style_rules[] = "background-image:url('{$background_image_attr_url}');";
        $background_style_rules[] = 'background-repeat:no-repeat;';
        $background_style_rules[] = 'background-position:center center;';
        $background_style_rules[] = 'background-size:cover;';
    }

    $wrapper_background_style = implode( '', $background_style_rules );
    $wrapper_div_style        = $wrapper_background_style . 'padding:32px 0;';

    $mso_background_block = '';

    if ( $background_image_attr_url ) {
        $mso_background_block  = '<!--[if gte mso 9]>'; 
        $mso_background_block .= '<v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">';
        $mso_background_block .= '<v:fill type="frame" src="' . $background_image_attr_url . '" color="' . esc_attr( $background_color ) . '" />';
        $mso_background_block .= '</v:background>';
        $mso_background_block .= '<![endif]-->';
    }

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

    $logo_image_html = '';

    if ( $logo_url ) {
        $logo_image_html = '<img src="' . esc_url( $logo_url ) . '" alt="Academia Villegas" style="width:76%;max-width:720px;height:162px;object-fit:cover;object-position:center;display:block;margin:0 auto;border-top-left-radius:8px;border-top-right-radius:8px;">';
    }

    $completion_timestamp = ! empty( $debug['final_attempt']['timestamp'] ) ? (int) $debug['final_attempt']['timestamp'] : current_time( 'timestamp' );
    $completion_date      = date_i18n( get_option( 'date_format' ), $completion_timestamp );

    $first_display = null !== $first_score ? Villegas_Quiz_Stats::format_percentage( (float) $first_score ) : null;
    $final_display = null !== $final_score ? Villegas_Quiz_Stats::format_percentage( (float) $final_score ) : null;

    $initial_value       = null !== $first_score ? (float) $first_score : 0.0;
    $initial_display_val = null !== $first_display ? (float) $first_display : null;
    $final_value         = null !== $final_score ? (float) $final_score : 0.0;
    $final_display_val   = null !== $final_display ? (float) $final_display : null;

    $initial_chart_url = function_exists( 'villegas_generate_quickchart_url' )
        ? villegas_generate_quickchart_url( $initial_value, $initial_display_val )
        : '';
    $final_chart_url   = function_exists( 'villegas_generate_quickchart_url' )
        ? villegas_generate_quickchart_url( $final_value, $final_display_val )
        : '';

    $initial_percentage_text = null !== $first_display
        ? sprintf( '%d%%', (int) $first_display )
        : __( 'Sin datos', 'villegas-courses' );
    $final_percentage_text   = null !== $final_display
        ? sprintf( '%d%%', (int) $final_display )
        : __( 'Sin datos', 'villegas-courses' );

    $cta_message = __( 'Sigue reforzando tus aprendizajes revisando el contenido del curso.', 'villegas-courses' );

    $course_url = $course_id ? get_permalink( $course_id ) : home_url( '/' );

    $course_price_type = $course_id && function_exists( 'learndash_get_setting' )
        ? learndash_get_setting( $course_id, 'course_price_type' )
        : '';

    $is_free_course = in_array( $course_price_type, [ 'free', 'open' ], true );
    $has_access     = $course_id ? Villegas_Course::user_has_access( $course_id, $user->ID ) : false;

    $button_label = __( 'Ver curso', 'villegas-courses' );
    $button_url   = $course_url;
    $button_note  = __( 'Revisa nuevamente las lecciones y recursos para consolidar tu aprendizaje.', 'villegas-courses' );

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
        $button_note  = __( 'Adquiere el curso para volver a revisar las lecciones y mejorar tus resultados.', 'villegas-courses' );
    }

    $replacements = [
        '{{background_color}}'        => esc_attr( $background_color ),
        '{{background_image_url}}'    => $background_image_attr_url,
        '{{wrapper_background_style}}'=> esc_attr( $wrapper_background_style ),
        '{{wrapper_div_style}}'       => esc_attr( $wrapper_div_style ),
        '{{mso_background_block}}'    => $mso_background_block,
        '{{logo_image}}'              => $logo_image_html,
        '{{completion_date}}'         => esc_html( $completion_date ),
        '{{user_name}}'               => esc_html( $user->display_name ),
        '{{quiz_name}}'               => esc_html( $quiz_title ),
        '{{initial_chart_url}}'       => esc_url( $initial_chart_url ),
        '{{final_chart_url}}'         => esc_url( $final_chart_url ),
        '{{initial_percentage}}'      => esc_html( $initial_percentage_text ),
        '{{final_percentage}}'        => esc_html( $final_percentage_text ),
        '{{cta_message}}'             => esc_html( $cta_message ),
        '{{button_url}}'              => esc_url( $button_url ),
        '{{button_label}}'            => esc_html( $button_label ),
        '{{button_note}}'             => esc_html( $button_note ),
    ];

    $body = strtr( $email_body, $replacements );

    if ( null !== $first_score && null !== $final_score ) {
        $variation      = round( $final_score - $first_score );
        error_log( '[FinalQuizEmail] Variation difference: ' . $variation );
        $variation_html = '';

        if ( $variation > 0 ) {
            $variation_html = "\n    <div id='villegas-progress-message' style='text-align:center; margin-top: 30px;'>\n        <h1 style='margin-bottom: 10px; line-height:1'>¡Gran progreso!</h1>\n        <p style='font-size:18px;'>Has mejorado tu desempeño en un <strong>{$variation}%</strong> respecto a tu evaluación inicial.</p>\n        <p style='font-size:18px;'>¡Excelente trabajo! Continúa avanzando con el mismo entusiasmo.</p>\n    </div>";
        } elseif ( $variation < 0 ) {
            $variation_html = "\n    <div id='villegas-progress-message' style='text-align:center; margin-top: 30px;'>\n        <h1 style='margin-bottom: 10px; line-height:1'>¡Gracias por completar la evaluación!</h1>\n        <p style='font-size:18px;'>Tu puntaje final fue un <strong>" . abs( $variation ) . "% menor</strong> que en tu evaluación inicial.</p>\n        <p style='font-size:18px;'>No te preocupes: repasar los contenidos y volver a intentarlo puede ayudarte a mejorar.</p>\n    </div>";
        } else {
            $variation_html = "\n    <div id='villegas-progress-message' style='text-align:center; margin-top: 30px;'>\n        <h1 style='margin-bottom: 10px; line-height:1'>¡Buen esfuerzo!</h1>\n        <p style='font-size:18px;'>Tu resultado es similar al de tu evaluación inicial.</p>\n        <p style='font-size:18px;'>Te animamos a seguir practicando para profundizar tus conocimientos.</p>\n    </div>";
        }

        error_log( '[FinalQuizEmail] Appending variation message: ' . strip_tags( $variation_html ) );

        $graphs_marker = 'id="villegas-email-graficas"';
        $inserted      = false;

        $marker_position = strpos( $body, $graphs_marker );

        if ( false !== $marker_position ) {
            $first_closing  = strpos( $body, '</table>', $marker_position );
            $second_closing = false !== $first_closing ? strpos( $body, '</table>', $first_closing + strlen( '</table>' ) ) : false;

            if ( false !== $second_closing ) {
                $body     = substr_replace( $body, $variation_html, $second_closing + strlen( '</table>' ), 0 );
                $inserted = true;
            }
        }

        if ( ! $inserted ) {
            $body .= $variation_html;
        }

        error_log( '[FinalQuizEmail] FINAL HTML content: ' . substr( strip_tags( $body ), 0, 200 ) );
    }

    $subject = sprintf(
        /* translators: %s: quiz or course title */
        __( 'Has finalizado la Evaluación Final: %s', 'villegas-courses' ),
        $course_title ?: $quiz_title
    );

    return [
        'subject'             => $subject,
        'body'                => $body,
        'initial_percentage'  => $first_score,
        'final_percentage'    => $final_score,
    ];
}
