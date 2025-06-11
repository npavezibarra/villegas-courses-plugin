<?php

function get_latest_quiz_score() {
    global $wpdb;

    $quiz_id = intval($_GET['quiz_id']);
    $user_id = get_current_user_id();

    // Esperar a que se registre el intento más reciente
    for ($i = 0; $i < 5; $i++) {
        $latest_attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT activity_id, activity_completed FROM {$wpdb->prefix}learndash_user_activity 
            WHERE user_id = %d 
            AND post_id = %d 
            AND activity_type = 'quiz' 
            ORDER BY activity_completed DESC 
            LIMIT 1",
            $user_id,
            $quiz_id
        ));

        if ($latest_attempt && $latest_attempt->activity_completed > 0) {
            break;
        }
        sleep(2); // Esperar 2 segundos antes de reintentar
    }

    if ($latest_attempt) {
        $quiz_score = $wpdb->get_var($wpdb->prepare(
            "SELECT activity_meta_value FROM {$wpdb->prefix}learndash_user_activity_meta 
            WHERE activity_id = %d 
            AND activity_meta_key = 'percentage'",
            $latest_attempt->activity_id
        ));

        wp_send_json_success(['score' => floatval($quiz_score)]);
    } else {
        wp_send_json_error(['message' => 'No se encontró un intento reciente.']);
    }
}
add_action('wp_ajax_get_latest_quiz_score', 'get_latest_quiz_score');
add_action('wp_ajax_nopriv_get_latest_quiz_score', 'get_latest_quiz_score');
