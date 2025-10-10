<?php
/**
 * First quiz email handler that uses rendered donut data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_enviar_correo_first_quiz_rendered', 'villegas_enviar_correo_first_quiz_rendered' );
add_action( 'wp_ajax_nopriv_enviar_correo_first_quiz_rendered', 'villegas_enviar_correo_first_quiz_rendered' );

function villegas_enviar_correo_first_quiz_rendered() {
    check_ajax_referer( 'villegas_send_first_quiz_email', 'nonce' );

    $req = wp_unslash( $_POST );

    $quiz_id       = isset( $req['quiz_id'] ) ? (int) $req['quiz_id'] : 0;
    $user_id       = isset( $req['user_id'] ) ? (int) $req['user_id'] : 0;
    $user_score    = isset( $req['user_score'] ) ? (float) $req['user_score'] : 0;
    $average_score = isset( $req['average_score'] ) ? (float) $req['average_score'] : 0;

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'No autorizado' );
    }

    $current_user_id = get_current_user_id();
    if ( $current_user_id !== $user_id ) {
        wp_send_json_error( 'Usuario no autorizado' );
    }

    if ( ! $quiz_id || ! $user_id ) {
        wp_send_json_error( 'Missing quiz or user.' );
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        wp_send_json_error( 'User not found.' );
    }

    error_log( sprintf( '[EmailSync] Sending rendered-data email. User: %d | Quiz: %d | Scores: %s/%s', $user_id, $quiz_id, $user_score, $average_score ) );

    $quiz_data = [
        'percentage' => $user_score,
        'average'    => $average_score,
        'quiz_id'    => $quiz_id,
    ];

    $email = villegas_get_first_quiz_email_content( $quiz_data, $user );

    if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
        wp_send_json_error( 'Email template unavailable' );
    }

    $sent = wp_mail(
        $user->user_email,
        $email['subject'],
        $email['body'],
        [ 'Content-Type: text/html; charset=UTF-8' ]
    );

    if ( $sent ) {
        wp_send_json_success( 'Rendered-data email sent' );
    }

    wp_send_json_error( 'Email failed' );
}
