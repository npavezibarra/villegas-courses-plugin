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

    $quiz_id    = (int) ( $req['quiz_id'] ?? 0 );
    $user_id    = (int) ( $req['user_id'] ?? 0 );
    $user_score = (float) ( $req['user_score'] ?? 0 );
    $avg_score  = (float) ( $req['average_score'] ?? 0 );

    if ( ! $quiz_id || ! $user_id ) {
        error_log( '[EmailSync] Missing quiz_id or user_id.' );
        wp_send_json_error( 'Missing quiz or user.' );
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        error_log( '[EmailSync] User not found: ' . $user_id );
        wp_send_json_error( 'User not found.' );
    }

    $quiz_data = [
        'percentage' => $user_score,
        'average'    => $avg_score,
        'quiz_id'    => $quiz_id,
    ];

    error_log( "[EmailSync] Sending rendered-data email | user={$user_id}, quiz={$quiz_id}, user_score={$user_score}, avg_score={$avg_score}" );

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

    error_log( '[EmailSync] wp_mail result=' . var_export( $sent, true ) );

    if ( $sent ) {
        wp_send_json_success( 'Rendered-data email sent' );
    }

    wp_send_json_error( 'Email failed' );
}
