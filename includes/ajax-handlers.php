<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
}

/**
 * AJAX endpoint: get the most recent completed quiz attempt for the current user.
 *
 * Returns JSON with percentage, score, total points, activity_id, timestamp, etc.
 */
function villegas_get_latest_quiz_result() {
    global $wpdb;

    $user_id = get_current_user_id();
    $quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0;

    if ( ! $user_id || ! $quiz_id ) {
        wp_send_json_error( [ 'message' => 'Missing parameters' ] );
    }

    // Get the LAST attempt always, without requiring 'quiz' meta (which may be delayed)
    $latest_attempt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT activity_id, activity_completed
             FROM {$wpdb->prefix}learndash_user_activity AS ua
             WHERE ua.user_id = %d
               AND ua.post_id = %d
               AND ua.activity_type = 'quiz'
               AND ua.activity_completed IS NOT NULL
             ORDER BY ua.activity_id DESC
             LIMIT 1",
            $user_id,
            $quiz_id
        ),
        ARRAY_A
    );

    if ( ! $latest_attempt ) {
        wp_send_json_success( [
            'status'  => 'pending',
            'message' => 'Todavía no registramos tu intento.'
        ] );
    }

    $activity_id = intval( $latest_attempt['activity_id'] );

    // Get meta for that attempt
    $meta_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT activity_meta_key, activity_meta_value
             FROM {$wpdb->prefix}learndash_user_activity_meta
             WHERE activity_id = %d",
            $activity_id
        ),
        ARRAY_A
    );

    $meta = [];
    foreach ( $meta_rows as $row ) {
        $meta[ $row['activity_meta_key'] ] = $row['activity_meta_value'];
    }

    // If 'quiz' meta not yet populated or doesn't match, keep returning pending
    if ( empty( $meta['quiz'] ) || intval( $meta['quiz'] ) != $quiz_id ) {
        wp_send_json_success( [
            'status'      => 'pending',
            'activity_id' => $activity_id,
            'message'     => 'Quiz meta not ready yet, keep polling...'
        ] );
    }

    // If meta not yet populated, keep returning pending
    if ( empty( $meta['percentage'] ) || ! is_numeric( $meta['percentage'] ) ) {
        wp_send_json_success( [
            'status'      => 'pending',
            'activity_id' => $activity_id,
            'message'     => 'Meta data not ready yet, keep polling...'
        ] );
    }

    $percentage_value    = round( floatval( $meta['percentage'] ), 2 );
    $score_value         = isset( $meta['score'] ) ? intval( $meta['score'] ) : null;
    $total_points_value  = isset( $meta['total_points'] ) ? intval( $meta['total_points'] ) : null;
    $timestamp           = intval( $latest_attempt['activity_completed'] );
    $formatted_date      = $timestamp ? date_i18n( get_option( 'date_format' ) . ' H:i', $timestamp ) : '';

    wp_send_json_success( [
        'status'             => 'ready',
        'activity_id'        => $activity_id,
        'user_id'            => $user_id,
        'quiz_id'            => $quiz_id,
        'percentage'         => $percentage_value,
        'percentage_rounded' => round( $percentage_value ),
        'score'              => $score_value,
        'total_points'       => $total_points_value,
        'timestamp'          => $timestamp,
        'formatted_date'     => $formatted_date,
        'meta'               => $meta,
    ] );
}


// Register AJAX for logged-in and guest users
add_action( 'wp_ajax_villegas_get_latest_quiz_result', 'villegas_get_latest_quiz_result' );
add_action( 'wp_ajax_nopriv_villegas_get_latest_quiz_result', 'villegas_get_latest_quiz_result' );