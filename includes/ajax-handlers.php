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
    $last_timestamp = isset( $_POST['last_timestamp'] ) ? intval( $_POST['last_timestamp'] ) : 0;

    if ( ! $quiz_id ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Faltan datos del cuestionario.', 'villegas-courses' ) ], 400 );
    }

    if ( $requested_user && $requested_user !== $current_user ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Usuario no autorizado.', 'villegas-courses' ) ], 403 );
    }

    $user_id = $requested_user ? $requested_user : $current_user;

    $cache_key = sprintf( 'villegas_quiz_activity_%d_%d', $user_id, $quiz_id );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        $cached_timestamp = isset( $cached['timestamp'] ) ? intval( $cached['timestamp'] ) : 0;

        if ( $last_timestamp && $cached_timestamp && $cached_timestamp <= $last_timestamp ) {
            $cached = false;
        }
    }

    if ( false !== $cached ) {
        wp_send_json_success( $cached );
    }

    $summary = politeia_extract_quiz_attempt_summary( $user_id, $quiz_id );

    $retry_seconds = politeia_get_quiz_poll_retry_interval();

    if ( ! $summary['has_attempt'] ) {
        $pending = [
            'status'      => 'pending',
            'retry_after' => $retry_seconds,
        ];

        set_transient( $cache_key, $pending, $retry_seconds );

        wp_send_json_success( $pending );
    }

    $current_timestamp = intval( $summary['timestamp'] );

    if ( $last_timestamp && $current_timestamp && $current_timestamp <= $last_timestamp ) {
        $pending = [
            'status'      => 'pending',
            'retry_after' => $retry_seconds,
        ];

        wp_send_json_success( $pending );
    }

    $course_id = 0;
    $is_first  = false;
    $is_final  = false;
    $first_quiz = 0;
    $final_quiz = 0;

    if ( class_exists( 'PoliteiaCourse' ) ) {
        $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_id );
        if ( $course_id ) {
            $first_quiz = intval( PoliteiaCourse::getFirstQuizId( $course_id ) );
            $final_quiz = intval( PoliteiaCourse::getFinalQuizId( $course_id ) );

            $is_first = $first_quiz && $first_quiz === $quiz_id;
            $is_final = $final_quiz && $final_quiz === $quiz_id;
        }
    }

    $response = [
        'status'             => 'ready',
        'percentage'         => is_null( $summary['percentage'] ) ? null : (float) $summary['percentage'],
        'percentage_rounded' => is_null( $summary['percentage'] ) ? null : intval( round( $summary['percentage'] ) ),
        'score'              => intval( $summary['score'] ),
        'timestamp'          => intval( $summary['timestamp'] ),
        'formatted_date'     => $summary['formatted_date'],
        'course_id'          => $course_id ? intval( $course_id ) : 0,
        'is_first_quiz'      => (bool) $is_first,
        'is_final_quiz'      => (bool) $is_final,
    ];

    if ( $is_final && $first_quiz ) {
        $first_summary = politeia_extract_quiz_attempt_summary( $user_id, $first_quiz );

        $response['first_percentage'] = is_null( $first_summary['percentage'] )
            ? null
            : intval( round( $first_summary['percentage'] ) );
        $response['final_percentage'] = is_null( $summary['percentage'] )
            ? null
            : intval( round( $summary['percentage'] ) );

        if ( ! is_null( $response['first_percentage'] ) && ! is_null( $response['final_percentage'] ) ) {
            $response['progress_delta'] = intval( $response['final_percentage'] - $response['first_percentage'] );
        }

        if ( $first_summary['timestamp'] && $summary['timestamp'] && $summary['timestamp'] >= $first_summary['timestamp'] ) {
            $response['days_elapsed'] = max(
                1,
                floor( ( intval( $summary['timestamp'] ) - intval( $first_summary['timestamp'] ) ) / DAY_IN_SECONDS )
            );
        }
    }

    $cache_ttl = apply_filters( 'villegas_quiz_activity_cache_ttl', 15, $quiz_id, $user_id );
    set_transient( $cache_key, $response, max( 1, intval( $cache_ttl ) ) );

    wp_send_json_success( $response );
}
add_action( 'wp_ajax_get_latest_quiz_activity', 'politeia_get_latest_quiz_activity' );

// Backwards compatibility: keep previous hook name but delegate to the new handler.
function get_latest_quiz_score() {
    politeia_get_latest_quiz_activity();
}
add_action( 'wp_ajax_get_latest_quiz_score', 'get_latest_quiz_score' );

function politeia_extract_quiz_attempt_summary( $user_id, $quiz_id ) {
    $user_id = intval( $user_id );
    $quiz_id = intval( $quiz_id );

    $empty = [
        'has_attempt'    => false,
        'percentage'     => null,
        'score'          => 0,
        'timestamp'      => 0,
        'formatted_date' => '',
    ];

    if ( ! $user_id || ! $quiz_id ) {
        return $empty;
    }

    static $user_attempts = [];

    if ( ! isset( $user_attempts[ $user_id ] ) ) {
        $raw_attempts = get_user_meta( $user_id, '_sfwd-quizzes', true );
        $attempts     = maybe_unserialize( $raw_attempts );
        $user_attempts[ $user_id ] = is_array( $attempts ) ? $attempts : [];
    }

    $latest_attempt = null;

    foreach ( $user_attempts[ $user_id ] as $attempt ) {
        if ( ! is_array( $attempt ) || ! isset( $attempt['quiz'] ) ) {
            continue;
        }

        if ( intval( $attempt['quiz'] ) !== $quiz_id ) {
            continue;
        }

        $attempt_time = isset( $attempt['time'] ) ? intval( $attempt['time'] ) : 0;

        if ( null === $latest_attempt || $attempt_time > intval( $latest_attempt['time'] ?? 0 ) ) {
            $latest_attempt = $attempt;
        }
    }

    if ( null === $latest_attempt ) {
        return $empty;
    }

    $percentage = isset( $latest_attempt['percentage'] ) && is_numeric( $latest_attempt['percentage'] )
        ? floatval( $latest_attempt['percentage'] )
        : null;

    $timestamp = isset( $latest_attempt['time'] ) ? intval( $latest_attempt['time'] ) : 0;

    return [
        'has_attempt'    => $timestamp > 0,
        'percentage'     => $percentage,
        'score'          => isset( $latest_attempt['score'] ) ? intval( $latest_attempt['score'] ) : 0,
        'timestamp'      => $timestamp,
        'formatted_date' => $timestamp ? esc_html( date_i18n( 'j \d\e F \d\e Y', $timestamp ) ) : '',
    ];
}

function politeia_get_quiz_poll_retry_interval() {
    $default = 5;

    return max( 1, intval( apply_filters( 'villegas_quiz_activity_retry_after', $default ) ) );
}

function villegas_get_latest_quiz_result() {
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
        wp_send_json_error( [
            'message' => esc_html__( 'No autorizado.', 'villegas-courses' ),
            'code'    => 'not_authorized',
        ], 403 );
    }

    $quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;

    if ( ! $quiz_id ) {
        wp_send_json_error( [
            'message' => esc_html__( 'Faltan datos del cuestionario.', 'villegas-courses' ),
            'code'    => 'invalid_parameters',
        ], 400 );
    }

    global $wpdb;

    $user_id = get_current_user_id();

    $latest_attempt = $wpdb->get_row(
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

    if ( empty( $latest_attempt ) ) {
        wp_send_json(
            [
                'success' => true,
                'status'  => 'pending',
                'message' => esc_html__( 'Todavía no registramos tu intento.', 'villegas-courses' ),
            ]
        );
    }

    $activity_id        = isset( $latest_attempt['activity_id'] ) ? absint( $latest_attempt['activity_id'] ) : 0;
    $activity_completed = isset( $latest_attempt['activity_completed'] ) ? intval( $latest_attempt['activity_completed'] ) : 0;

    if ( ! $activity_id ) {
        wp_send_json(
            [
                'success' => true,
                'status'  => 'pending',
                'message' => esc_html__( 'Todavía no registramos tu intento.', 'villegas-courses' ),
            ]
        );
    }

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

        $meta_key   = sanitize_key( $row['activity_meta_key'] );
        $meta_value = isset( $row['activity_meta_value'] ) ? $row['activity_meta_value'] : '';

        $meta[ $meta_key ] = $meta_value;
    }

    $percentage_value = null;

    if ( isset( $meta['percentage'] ) && '' !== $meta['percentage'] && is_numeric( $meta['percentage'] ) ) {
        $percentage_value = round( floatval( $meta['percentage'] ), 2 );
    }

    if ( null === $percentage_value ) {
        wp_send_json(
            [
                'success'      => true,
                'status'       => 'pending',
                'activity_id'  => $activity_id,
                'timestamp'    => $activity_completed,
                'message'      => esc_html__( 'El porcentaje aún no está disponible.', 'villegas-courses' ),
            ]
        );
    }

    $score_value = null;

    if ( isset( $meta['score'] ) && is_numeric( $meta['score'] ) ) {
        $score_value = 0 + $meta['score'];
    }

    $total_points_value = null;

    if ( isset( $meta['total_points'] ) && is_numeric( $meta['total_points'] ) ) {
        $total_points_value = 0 + $meta['total_points'];
    }

    $response = [
        'success'            => true,
        'status'             => 'ready',
        'percentage'         => $percentage_value,
        'score'              => $score_value,
        'total_points'       => $total_points_value,
        'timestamp'          => $activity_completed,
        'formatted_date'     => $activity_completed ? esc_html( date_i18n( 'j \d\e F \d\e Y', $activity_completed ) ) : '',
        'activity_id'        => $activity_id,
        'percentage_rounded' => intval( round( $percentage_value ) ),
    ];

    wp_send_json( $response );
}
add_action( 'wp_ajax_villegas_get_latest_quiz_result', 'villegas_get_latest_quiz_result' );
add_action( 'wp_ajax_nopriv_villegas_get_latest_quiz_result', 'villegas_get_latest_quiz_result' );
