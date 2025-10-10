<?php
/**
 * First quiz email AJAX handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ----------------------------------------------------------
 * FIRST QUIZ – SEND EMAIL WITH REAL-TIME SCORE (AJAX)
 * ----------------------------------------------------------
 */
add_action( 'wp_ajax_enviar_correo_first_quiz', 'villegas_enviar_correo_first_quiz_handler' );
add_action( 'wp_ajax_nopriv_enviar_correo_first_quiz', 'villegas_enviar_correo_first_quiz_handler' );

function villegas_enviar_correo_first_quiz_handler() {
    error_log( '--- [FirstQuizEmail] Handler triggered ---' );

    check_ajax_referer( 'villegas_send_first_quiz_email', 'nonce' );

    if ( ! is_user_logged_in() ) {
        error_log( '[FirstQuizEmail] User not logged in.' );
        wp_send_json_error( 'No autorizado' );
    }

    $req             = wp_unslash( $_POST );
    $quiz_id         = isset( $req['quiz_id'] ) ? (int) $req['quiz_id'] : 0;
    $requested_user  = isset( $req['user_id'] ) ? (int) $req['user_id'] : 0;
    $quiz_percentage = isset( $req['quiz_percentage'] ) ? (float) $req['quiz_percentage'] : 0;
    $current_user_id = get_current_user_id();

    error_log( sprintf( '[FirstQuizEmail] Received via AJAX → quiz_id=%d, user_id=%d, percentage=%s', $quiz_id, $requested_user, $quiz_percentage ) );

    if ( ! $quiz_id || ! $requested_user ) {
        error_log( '[FirstQuizEmail] Missing quiz_id or user_id.' );
        wp_send_json_error( 'Datos incompletos' );
    }

    if ( $requested_user !== $current_user_id ) {
        error_log( sprintf( '[FirstQuizEmail] Unauthorized. current_user_id=%d', $current_user_id ) );
        wp_send_json_error( 'Usuario no autorizado' );
    }

    $user = get_userdata( $requested_user );

    if ( ! $user ) {
        error_log( '[FirstQuizEmail] User not found.' );
        wp_send_json_error( 'Usuario no encontrado' );
    }

    // 🔹 Build email with the real percentage sent by JS
    $quiz_data = [
        'percentage' => $quiz_percentage,
        'quiz_id'    => $quiz_id,
    ];

    error_log( '[FirstQuizEmail] Building email with quiz_data=' . print_r( $quiz_data, true ) );

    $email = villegas_get_first_quiz_email_content( $quiz_data, $user );

    if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
        error_log( '[FirstQuizEmail] Email template empty.' );
        wp_send_json_error( 'Plantilla de correo no disponible' );
    }

    $sent = wp_mail(
        $user->user_email,
        $email['subject'],
        $email['body'],
        [ 'Content-Type: text/html; charset=UTF-8' ]
    );

    if ( $sent ) {
        error_log( '[FirstQuizEmail] Email successfully sent to ' . $user->user_email );
        wp_send_json_success( 'Correo enviado con puntaje real' );
    }

    error_log( '[FirstQuizEmail] wp_mail() failed.' );
    wp_send_json_error( 'Error al enviar el correo' );
}
