<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Politeia_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-quiz-stats.php';
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
}

function politeia_get_latest_quiz_activity() {
    check_ajax_referer( 'politeia_quiz_activity', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'No autorizado' );
    }

    $quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0;
    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    $current = get_current_user_id();

    if ( ! $quiz_id ) {
        wp_send_json_error( 'Faltan datos' );
    }

    if ( $user_id && $user_id !== $current ) {
        wp_send_json_error( 'Usuario no autorizado' );
    }

    $user_id = $user_id ?: $current;

    $stats    = new Politeia_Quiz_Stats( $quiz_id, $user_id );
    $summary  = $stats->get_quiz_summary( $quiz_id );
    $response = [
        'percentage'        => $summary['percentage'],
        'percentage_rounded'=> $summary['percentage_rounded'],
        'score'             => $summary['score'],
        'timestamp'         => $summary['timestamp'],
        'formatted_date'    => $summary['formatted_date'],
        'course_id'         => $stats->get_course_id(),
        'is_first_quiz'     => $stats->is_first_quiz(),
        'is_final_quiz'     => $stats->is_final_quiz(),
    ];

    if ( $stats->is_final_quiz() ) {
        $first_summary = $stats->get_first_quiz_summary();
        $final_summary = $stats->get_final_quiz_summary();

        $response['first_percentage'] = $first_summary['percentage_rounded'];
        $response['final_percentage'] = $final_summary['percentage_rounded'];
    }

    if ( ! $summary['has_attempt'] ) {
        wp_send_json_error( [ 'message' => 'Sin intentos' ] );
    }

    wp_send_json_success( $response );
}
add_action( 'wp_ajax_get_latest_quiz_activity', 'politeia_get_latest_quiz_activity' );
add_action( 'wp_ajax_nopriv_get_latest_quiz_activity', 'politeia_get_latest_quiz_activity' );

// Backwards compatibility: keep previous hook name but delegate to the new handler.
function get_latest_quiz_score() {
    politeia_get_latest_quiz_activity();
}
add_action( 'wp_ajax_get_latest_quiz_score', 'get_latest_quiz_score' );
add_action( 'wp_ajax_nopriv_get_latest_quiz_score', 'get_latest_quiz_score' );
