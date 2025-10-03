<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
}

function politeia_get_latest_quiz_activity() {
    if ( ! check_ajax_referer( 'politeia_quiz_activity', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Solicitud no válida.', 'villegas-courses' ) ], 403 );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
        wp_send_json_error( [ 'message' => esc_html__( 'No autorizado.', 'villegas-courses' ) ], 403 );
    }

    $quiz_id        = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
    $requested_user = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
    $current_user   = get_current_user_id();

    if ( ! $quiz_id ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Faltan datos del cuestionario.', 'villegas-courses' ) ], 400 );
    }

    if ( $requested_user && $requested_user !== $current_user ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Usuario no autorizado.', 'villegas-courses' ) ], 403 );
    }

    $user_id = $requested_user ? $requested_user : $current_user;

    global $wpdb;

    $activity = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT activity_id, activity_completed
             FROM {$wpdb->prefix}learndash_user_activity
             WHERE user_id = %d
               AND post_id = %d
               AND activity_type = 'quiz'
             ORDER BY activity_id DESC
             LIMIT 1",
            $user_id,
            $quiz_id
        ),
        ARRAY_A
    );

    $default_retry_after = politeia_get_quiz_poll_retry_interval();

    if ( empty( $activity ) || empty( $activity['activity_id'] ) ) {
        wp_send_json_success(
            [
                'status'      => 'pending',
                'activity_id' => 0,
                'retry_after' => $default_retry_after,
                'message'     => esc_html__( 'Todavía no registramos tu intento.', 'villegas-courses' ),
            ]
        );
    }

    $activity_id        = intval( $activity['activity_id'] );
    $activity_timestamp = isset( $activity['activity_completed'] ) ? intval( $activity['activity_completed'] ) : 0;

    $meta = politeia_fetch_activity_meta_map( $activity_id );

    $quiz_meta_value = isset( $meta['quiz'] ) ? intval( $meta['quiz'] ) : 0;
    $has_quiz_meta   = $quiz_meta_value === $quiz_id;
    $percentage_raw  = isset( $meta['percentage'] ) ? $meta['percentage'] : null;
    $has_percentage  = ( '' !== $percentage_raw && null !== $percentage_raw && is_numeric( $percentage_raw ) );

    if ( ! $has_quiz_meta || ! $has_percentage ) {
        $pending_retry_after = max(
            1,
            intval( apply_filters( 'villegas_quiz_activity_pending_retry_after', 2, $quiz_id, $user_id, $activity_id ) )
        );

        $pending_payload = [
            'status'      => 'pending',
            'activity_id' => $activity_id,
            'retry_after' => $pending_retry_after,
            'message'     => esc_html__( 'Quiz meta pending...', 'villegas-courses' ),
        ];

        wp_send_json_success( $pending_payload );
    }

    $percentage         = round( floatval( $percentage_raw ), 2 );
    $percentage_rounded = intval( round( $percentage ) );
    $score              = ( isset( $meta['score'] ) && is_numeric( $meta['score'] ) ) ? 0 + $meta['score'] : null;
    $total_points       = ( isset( $meta['total_points'] ) && is_numeric( $meta['total_points'] ) ) ? 0 + $meta['total_points'] : null;

    if ( ! $activity_timestamp ) {
        $activity_timestamp = current_time( 'timestamp' );
    }

    $response = [
        'status'             => 'ready',
        'activity_id'        => $activity_id,
        'percentage'         => $percentage,
        'percentage_rounded' => $percentage_rounded,
        'score'              => $score,
        'total_points'       => $total_points,
        'timestamp'          => $activity_timestamp,
        'formatted_date'     => politeia_format_quiz_activity_date( $activity_timestamp ),
    ];

    $course_id      = 0;
    $first_quiz_id  = 0;
    $final_quiz_id  = 0;
    $is_first_quiz  = false;
    $is_final_quiz  = false;

    if ( class_exists( 'PoliteiaCourse' ) ) {
        $course_id = intval( PoliteiaCourse::getCourseFromQuiz( $quiz_id ) );

        if ( $course_id ) {
            $first_quiz_id = intval( PoliteiaCourse::getFirstQuizId( $course_id ) );
            $final_quiz_id = intval( PoliteiaCourse::getFinalQuizId( $course_id ) );

            $is_first_quiz = $first_quiz_id && $first_quiz_id === $quiz_id;
            $is_final_quiz = $final_quiz_id && $final_quiz_id === $quiz_id;

            if ( $is_final_quiz && $first_quiz_id ) {
                $first_summary = politeia_get_quiz_attempt_summary_for_comparison( $user_id, $first_quiz_id );

                $response['first_percentage'] = null;
                $response['final_percentage'] = $percentage_rounded;
                $response['progress_delta']   = null;
                $response['days_elapsed']     = null;

                if ( $first_summary ) {
                    $first_percentage = intval( round( $first_summary['percentage'] ) );

                    $response['first_percentage'] = $first_percentage;
                    $response['progress_delta']   = $percentage_rounded - $first_percentage;

                    if ( ! empty( $first_summary['timestamp'] ) && $activity_timestamp ) {
                        $delta_seconds            = max( 0, intval( $activity_timestamp ) - intval( $first_summary['timestamp'] ) );
                        $response['days_elapsed'] = intval( floor( $delta_seconds / DAY_IN_SECONDS ) );
                    }
                }
            }
        }
    }

    if ( $course_id ) {
        $response['course_id'] = $course_id;
    }

    $response['is_first_quiz'] = $is_first_quiz;
    $response['is_final_quiz'] = $is_final_quiz;

    wp_send_json_success( $response );
}
add_action( 'wp_ajax_get_latest_quiz_activity', 'politeia_get_latest_quiz_activity' );
add_action( 'wp_ajax_villegas_get_latest_quiz_result', 'politeia_get_latest_quiz_activity' );

// Backwards compatibility: keep previous hook name but delegate to the new handler.
function politeia_get_latest_quiz_score_legacy() {
    politeia_get_latest_quiz_activity();
}
add_action( 'wp_ajax_get_latest_quiz_score', 'politeia_get_latest_quiz_score_legacy' );

/**
 * Retrieve the most recent completed quiz attempt (and its metadata) for a user.
 *
 * @param int $user_id Current user ID.
 * @param int $quiz_id Quiz post ID.
 *
 * @return array|null Attempt data or null when no completed attempt exists.
 */
function politeia_get_latest_completed_quiz_attempt( $user_id, $quiz_id ) {
    $user_id = intval( $user_id );
    $quiz_id = intval( $quiz_id );

    if ( ! $user_id || ! $quiz_id ) {
        return null;
    }

    global $wpdb;

    $attempt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT ua.activity_id, ua.activity_completed
             FROM {$wpdb->prefix}learndash_user_activity AS ua
             INNER JOIN {$wpdb->prefix}learndash_user_activity_meta AS uam
                ON ua.activity_id = uam.activity_id
             WHERE ua.user_id = %d
               AND ua.activity_type = 'quiz'
               AND uam.activity_meta_key = 'quiz'
               AND uam.activity_meta_value+0 = %d
               AND ua.activity_completed IS NOT NULL
             ORDER BY ua.activity_id DESC
             LIMIT 1",
            $user_id,
            $quiz_id
        ),
        ARRAY_A
    );

    if ( empty( $attempt ) || empty( $attempt['activity_id'] ) ) {
        return null;
    }

    $activity_id        = absint( $attempt['activity_id'] );
    $activity_completed = isset( $attempt['activity_completed'] ) ? intval( $attempt['activity_completed'] ) : 0;

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

    foreach ( (array) $meta_rows as $row ) {
        if ( empty( $row['activity_meta_key'] ) ) {
            continue;
        }

        $key = sanitize_key( $row['activity_meta_key'] );

        if ( '' === $key ) {
            continue;
        }

        $meta[ $key ] = $row['activity_meta_value'];
    }

    $percentage = null;
    if ( isset( $meta['percentage'] ) && is_numeric( $meta['percentage'] ) ) {
        $percentage = round( floatval( $meta['percentage'] ), 2 );
    }

    $score = null;
    if ( isset( $meta['score'] ) && is_numeric( $meta['score'] ) ) {
        $score = 0 + $meta['score'];
    }

    $total_points = null;
    if ( isset( $meta['total_points'] ) && is_numeric( $meta['total_points'] ) ) {
        $total_points = 0 + $meta['total_points'];
    }

    return [
        'activity_id'    => $activity_id,
        'timestamp'      => $activity_completed,
        'percentage'     => $percentage,
        'score'          => $score,
        'total_points'   => $total_points,
        'formatted_date' => $activity_completed ? date_i18n( 'j \d\e F \d\e Y', $activity_completed ) : '',
        'meta'           => $meta,
    ];
}

function politeia_get_quiz_poll_retry_interval() {
    $default = 5;

    return max( 1, intval( apply_filters( 'villegas_quiz_activity_retry_after', $default ) ) );
}

/**
 * Retrieve and normalize all metadata for a given LearnDash activity ID.
 *
 * @param int $activity_id Activity identifier.
 *
 * @return array
 */
function politeia_fetch_activity_meta_map( $activity_id ) {
    $activity_id = intval( $activity_id );

    if ( ! $activity_id ) {
        return [];
    }

    global $wpdb;

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT activity_meta_key, activity_meta_value
             FROM {$wpdb->prefix}learndash_user_activity_meta
             WHERE activity_id = %d",
            $activity_id
        ),
        ARRAY_A
    );

    $meta = [];

    foreach ( (array) $rows as $row ) {
        if ( empty( $row['activity_meta_key'] ) ) {
            continue;
        }

        $key = sanitize_key( $row['activity_meta_key'] );

        if ( '' === $key ) {
            continue;
        }

        $meta[ $key ] = isset( $row['activity_meta_value'] ) ? $row['activity_meta_value'] : '';
    }

    return $meta;
}

/**
 * Format the timestamp associated with a quiz attempt using the site locale.
 *
 * @param int $timestamp Unix timestamp.
 *
 * @return string
 */
function politeia_format_quiz_activity_date( $timestamp ) {
    $timestamp = intval( $timestamp );

    if ( $timestamp <= 0 ) {
        return '';
    }

    return date_i18n( 'j \d\e F \d\e Y H:i', $timestamp );
}

/**
 * Fetch the latest completed attempt summary for comparison charts.
 *
 * @param int $user_id User identifier.
 * @param int $quiz_id Quiz identifier.
 *
 * @return array|null
 */
function politeia_get_quiz_attempt_summary_for_comparison( $user_id, $quiz_id ) {
    $user_id = intval( $user_id );
    $quiz_id = intval( $quiz_id );

    if ( ! $user_id || ! $quiz_id ) {
        return null;
    }

    global $wpdb;

    $limit = max( 1, intval( apply_filters( 'villegas_quiz_activity_comparison_attempt_limit', 10, $quiz_id, $user_id ) ) );

    $attempts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT activity_id, activity_completed
             FROM {$wpdb->prefix}learndash_user_activity
             WHERE user_id = %d
               AND post_id = %d
               AND activity_type = 'quiz'
             ORDER BY activity_id DESC
             LIMIT %d",
            $user_id,
            $quiz_id,
            $limit
        ),
        ARRAY_A
    );

    foreach ( (array) $attempts as $attempt ) {
        if ( empty( $attempt['activity_id'] ) ) {
            continue;
        }

        $activity_id = intval( $attempt['activity_id'] );
        $meta        = politeia_fetch_activity_meta_map( $activity_id );

        $quiz_meta_value = isset( $meta['quiz'] ) ? intval( $meta['quiz'] ) : 0;

        if ( $quiz_meta_value && $quiz_meta_value !== $quiz_id ) {
            continue;
        }

        if ( ! isset( $meta['percentage'] ) || '' === $meta['percentage'] || ! is_numeric( $meta['percentage'] ) ) {
            continue;
        }

        $percentage = round( floatval( $meta['percentage'] ), 2 );
        $score      = ( isset( $meta['score'] ) && is_numeric( $meta['score'] ) ) ? 0 + $meta['score'] : null;
        $total      = ( isset( $meta['total_points'] ) && is_numeric( $meta['total_points'] ) ) ? 0 + $meta['total_points'] : null;
        $timestamp  = isset( $attempt['activity_completed'] ) ? intval( $attempt['activity_completed'] ) : 0;

        return [
            'activity_id'    => $activity_id,
            'percentage'     => $percentage,
            'score'          => $score,
            'total_points'   => $total,
            'timestamp'      => $timestamp,
            'formatted_date' => politeia_format_quiz_activity_date( $timestamp ),
        ];
    }

    return null;
}



