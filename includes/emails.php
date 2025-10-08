<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Villegas_Course' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'class-villegas-course.php';
}

if ( ! class_exists( 'Villegas_Quiz_Attempts_Shortcode' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'class-villegas-quiz-attempts-shortcode.php';
}

if ( ! class_exists( 'Villegas_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-villegas-quiz-stats.php';
}

if ( ! function_exists( 'villegas_generate_quickchart_url' ) ) {
    function villegas_generate_quickchart_url( float $value ): string {
        $value = max( 0, min( 100, round( $value, 2 ) ) );

        $config = [
            'type'    => 'doughnut',
            'data'    => [
                'datasets' => [
                    [
                        'data'            => [ $value, 100 - $value ],
                        'backgroundColor' => [ '#f9c600', '#eeeeee' ],
                        'borderWidth'     => 0,
                    ],
                ],
            ],
            'options' => [
                'cutout'  => '10%',
                'plugins' => [
                    'legend'        => [ 'display' => false ],
                    'tooltip'       => [ 'enabled' => false ],
                    'datalabels'    => [ 'display' => false ],
                    'doughnutlabel' => [
                        'labels' => [
                            [
                                'text' => $value . '%',
                                'font' => [
                                    'size'   => 24,
                                    'weight' => 'bold',
                                ],
                                'color' => '#333333',
                            ],
                        ],
                    ],
                ],
            ],
            'plugins' => [ 'doughnutlabel' ],
        ];

        return 'https://quickchart.io/chart?c=' . urlencode( wp_json_encode( $config ) );
    }
}

if ( ! function_exists( 'villegas_get_latest_quiz_attempt' ) ) {
    function villegas_get_latest_quiz_attempt( int $user_id, int $quiz_id ): array {
        global $wpdb;

        $user_id = absint( $user_id );
        $quiz_id = absint( $quiz_id );

        if ( ! $user_id || ! $quiz_id ) {
            return [ 'percentage' => null, 'timestamp' => null ];
        }

        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        $meta_table     = $wpdb->prefix . 'learndash_user_activity_meta';

        $attempt = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ua.activity_id, ua.activity_completed
                 FROM {$activity_table} AS ua
                 INNER JOIN {$meta_table} AS quiz_meta
                    ON quiz_meta.activity_id = ua.activity_id
                   AND quiz_meta.activity_meta_key = 'quiz'
                   AND quiz_meta.activity_meta_value+0 = %d
                 WHERE ua.user_id = %d
                   AND ua.activity_type = 'quiz'
                   AND ua.activity_completed IS NOT NULL
                 ORDER BY ua.activity_completed DESC
                 LIMIT 1",
                $quiz_id,
                $user_id
            ),
            ARRAY_A
        );

        if ( empty( $attempt ) || empty( $attempt['activity_id'] ) ) {
            return [ 'percentage' => null, 'timestamp' => null ];
        }

        $percentage = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT activity_meta_value+0
                 FROM {$meta_table}
                 WHERE activity_id = %d
                   AND activity_meta_key = 'percentage'
                 LIMIT 1",
                (int) $attempt['activity_id']
            )
        );

        return [
            'percentage' => is_numeric( $percentage ) ? (float) $percentage : null,
            'timestamp'  => ! empty( $attempt['activity_completed'] ) ? (int) $attempt['activity_completed'] : null,
        ];
    }
}

if ( ! function_exists( 'villegas_get_quiz_debug_data' ) ) {
    function villegas_get_quiz_debug_data( array $quiz_data, WP_User $user ): array {
        $quiz_id = isset( $quiz_data['quiz'] ) ? $quiz_data['quiz'] : 0;

        if ( $quiz_id instanceof WP_Post ) {
            $quiz_id = $quiz_id->ID;
        }

        $quiz_id = absint( $quiz_id );
        $user_id = absint( $user->ID );

        $course_id = 0;

        if ( $quiz_id ) {
            $course_id = Villegas_Course::get_course_from_quiz( $quiz_id );

            if ( ! $course_id && function_exists( 'learndash_get_course_id' ) ) {
                $course_id = (int) learndash_get_course_id( $quiz_id );
            }
        }

        $first_quiz_id = $course_id ? Villegas_Course::get_first_quiz_id( $course_id ) : 0;
        $final_quiz_id = $course_id ? Villegas_Course::get_final_quiz_id( $course_id ) : 0;

        $is_first_quiz = $quiz_id && $first_quiz_id && (int) $quiz_id === (int) $first_quiz_id;
        $is_final_quiz = $quiz_id && $final_quiz_id && (int) $quiz_id === (int) $final_quiz_id;

        $first_attempt = $first_quiz_id ? villegas_get_latest_quiz_attempt( $user_id, $first_quiz_id ) : [ 'percentage' => null, 'timestamp' => null ];
        $final_attempt = $final_quiz_id ? villegas_get_latest_quiz_attempt( $user_id, $final_quiz_id ) : [ 'percentage' => null, 'timestamp' => null ];

        $current_percentage = isset( $quiz_data['percentage'] ) && is_numeric( $quiz_data['percentage'] )
            ? (float) $quiz_data['percentage']
            : null;

        if ( $is_first_quiz && null !== $current_percentage ) {
            $first_attempt['percentage'] = $current_percentage;
            $first_attempt['timestamp']  = $first_attempt['timestamp'] ?: time();
        }

        if ( $is_final_quiz && null !== $current_percentage ) {
            $final_attempt['percentage'] = $current_percentage;
            $final_attempt['timestamp']  = $final_attempt['timestamp'] ?: time();
        }

        return [
            'quiz_id'             => $quiz_id,
            'quiz_title'          => $quiz_id ? get_the_title( $quiz_id ) : '',
            'course_id'           => $course_id,
            'course_title'        => $course_id ? get_the_title( $course_id ) : '',
            'first_quiz_id'       => $first_quiz_id,
            'final_quiz_id'       => $final_quiz_id,
            'is_first_quiz'       => $is_first_quiz,
            'is_final_quiz'       => $is_final_quiz,
            'first_attempt'       => $first_attempt,
            'final_attempt'       => $final_attempt,
            'user_id'             => $user_id,
            'user_display_name'   => $user->display_name,
            'user_email'          => $user->user_email,
            'current_percentage'  => $current_percentage,
        ];
    }
}

require_once plugin_dir_path( __FILE__ ) . '../emails/first-quiz-email.php';
require_once plugin_dir_path( __FILE__ ) . '../emails/final-quiz-email.php';

if ( ! function_exists( 'villegas_quiz_completed_handler' ) ) {
    function villegas_quiz_completed_handler( $quiz_data, $user ) {
        if ( ! ( $user instanceof WP_User ) ) {
            return;
        }

        $debug = villegas_get_quiz_debug_data( $quiz_data, $user );

        if ( $debug['is_first_quiz'] ) {
            $email = villegas_get_first_quiz_email_content( $quiz_data, $user );
        } elseif ( $debug['is_final_quiz'] ) {
            $email = villegas_get_final_quiz_email_content( $quiz_data, $user );
        } else {
            return;
        }

        if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );

        if ( $admin_email && ! empty( $user->user_email ) && 0 === strcasecmp( $admin_email, $user->user_email ) ) {
            $admin_email = '';
        }

        if ( ! $admin_email ) {
            return;
        }

        wp_mail(
            $admin_email,
            $email['subject'],
            $email['body'],
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );
    }
}
add_action( 'learndash_quiz_completed', 'villegas_quiz_completed_handler', 10, 2 );

if ( ! function_exists( 'villegas_send_first_quiz_email_handler' ) ) {
    function villegas_send_first_quiz_email_handler() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'villegas_send_first_quiz_email' ) ) {
            wp_send_json_error( 'invalid_nonce', 403 );
        }

        $quiz_id      = isset( $_POST['quiz_id'] ) ? (int) $_POST['quiz_id'] : 0;
        $user_id      = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
        $percentage   = isset( $_POST['quiz_percentage'] ) ? (float) $_POST['quiz_percentage'] : null;
        $current_user = get_current_user_id();

        if ( ! $quiz_id || ! $user_id ) {
            wp_send_json_error( 'missing_parameters', 400 );
        }

        if ( $current_user && $current_user !== $user_id && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'forbidden', 403 );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'not_logged_in', 403 );
        }

        $user = get_userdata( $user_id );

        if ( ! $user ) {
            wp_send_json_error( 'user_not_found', 404 );
        }

        $quiz_data = [
            'quiz'       => $quiz_id,
            'percentage' => $percentage,
        ];

        $email = villegas_get_first_quiz_email_content( $quiz_data, $user );

        if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
            wp_send_json_error( 'empty_email', 500 );
        }

        $sent = wp_mail(
            $user->user_email,
            $email['subject'],
            $email['body'],
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );

        if ( $sent ) {
            wp_send_json_success( 'email_sent' );
        }

        wp_send_json_error( 'mail_failed', 500 );
    }
}
add_action( 'wp_ajax_villegas_send_first_quiz_email', 'villegas_send_first_quiz_email_handler' );
add_action( 'wp_ajax_nopriv_villegas_send_first_quiz_email', 'villegas_send_first_quiz_email_handler' );
