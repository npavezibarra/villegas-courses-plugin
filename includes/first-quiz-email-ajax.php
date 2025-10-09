<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_enviar_correo_first_quiz', 'villegas_enviar_correo_first_quiz_handler' );
add_action( 'wp_ajax_nopriv_enviar_correo_first_quiz', 'villegas_enviar_correo_first_quiz_handler' );

function villegas_enviar_correo_first_quiz_handler() {
    check_ajax_referer( 'villegas_send_first_quiz_email', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'No autorizado' );
    }

    $req             = wp_unslash( $_POST );
    $quiz_id         = isset( $req['quiz_id'] ) ? (int) $req['quiz_id'] : 0;
    $requested_user  = isset( $req['user_id'] ) ? (int) $req['user_id'] : 0;
    $quiz_percentage = isset( $req['quiz_percentage'] ) ? villegas_normalize_percentage_value( $req['quiz_percentage'] ) : null;
    $current_user_id = get_current_user_id();

    if ( ! $quiz_id || ! $requested_user ) {
        wp_send_json_error( 'Datos incompletos' );
    }

    if ( $requested_user !== $current_user_id ) {
        wp_send_json_error( 'Usuario no autorizado' );
    }

    $user = get_userdata( $requested_user );

    if ( ! $user ) {
        wp_send_json_error( 'Usuario no encontrado' );
    }

    if ( null === $quiz_percentage ) {
        $quiz_percentage = 0.0;
    }

    $quiz_data = [
        'quiz'       => $quiz_id,
        'quiz_id'    => $quiz_id,
        'percentage' => $quiz_percentage,
    ];

    $email = villegas_get_first_quiz_email_content( $quiz_data, $user );

    if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
        wp_send_json_error( 'Plantilla de correo no disponible' );
    }

    $sent = wp_mail(
        $user->user_email,
        $email['subject'],
        $email['body'],
        [ 'Content-Type: text/html; charset=UTF-8' ]
    );

    if ( $sent ) {
        wp_send_json_success( 'Correo enviado con puntaje real' );
    }

    wp_send_json_error( 'Error al enviar el correo' );
}
