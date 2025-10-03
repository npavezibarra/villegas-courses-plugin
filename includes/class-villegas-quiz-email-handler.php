<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Quiz_Email_Handler {

    /**
     * Called when a quiz is completed in LearnDash.
     *
     * @param array  $quiz_data Payload provided by LearnDash.
     * @param object $user      WP_User object for the user who completed the quiz.
     */
    public static function on_quiz_completed( $quiz_data, $user ) {

        // Use our helper to collect debug data
        $debug = Villegas_Quiz_Emails::get_quiz_debug_data( $quiz_data, $user );

        // Bail if not First or Final quiz
        if ( empty( $debug['is_first_quiz'] ) && empty( $debug['is_final_quiz'] ) ) {
            return;
        }

        if ( 'None' === $debug['first_quiz_attempt'] && 'None' === $debug['final_quiz_attempt'] ) {
            $queue = get_option( 'villegas_quiz_email_queue', [] );

            if ( ! is_array( $queue ) ) {
                $queue = [];
            }

            $queue[] = [
                'user_id'   => $debug['user_id'],
                'quiz_id'   => $debug['quiz_id'],
                'course_id' => $debug['course_id_detected'],
                'created'   => time(),
                'retries'   => 0,
            ];

            update_option( 'villegas_quiz_email_queue', $queue, false );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Villegas Quiz Emails] Queued quiz attempt for later retry: ' . $debug['quiz_id'] );
            }

            return;
        }

        self::send_email( $quiz_data, $user, $debug );
    }

    public static function process_queue() {
        $queue = get_option( 'villegas_quiz_email_queue', [] );

        if ( ! is_array( $queue ) ) {
            $queue = [];
        }

        if ( empty( $queue ) ) {
            return;
        }

        $new_queue = [];

        foreach ( $queue as $entry ) {
            $user = get_user_by( 'id', $entry['user_id'] );

            if ( ! $user ) {
                continue;
            }

            $quiz_data = [
                'quiz'   => $entry['quiz_id'],
                'course' => $entry['course_id'],
            ];

            $debug = Villegas_Quiz_Emails::get_quiz_debug_data( $quiz_data, $user );

            if ( 'None' !== $debug['first_quiz_attempt'] || 'None' !== $debug['final_quiz_attempt'] ) {
                self::send_email( $quiz_data, $user, $debug );
                continue;
            }

            $entry['retries']++;

            if ( $entry['retries'] < 3 ) {
                $new_queue[] = $entry;

                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[Villegas Quiz Emails] Requeueing attempt #' . $entry['retries'] . ' for quiz: ' . $entry['quiz_id'] );
                }
            } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Villegas Quiz Emails] Dropping attempt after 3 retries: ' . $entry['quiz_id'] );
            }
        }

        update_option( 'villegas_quiz_email_queue', $new_queue, false );
    }

    private static function send_email( $quiz_data, $user, $debug ) {
        $default_first_body = "Hello {user_name},\n\nYou scored {first_score} in {quiz_title} for {course_title}.\nDate: {quiz_date}";
        $default_final_body = "Hello {user_name},\n\nYou scored {final_score} in {quiz_title}.\nYour First Quiz was {first_score}, so your progress is {progress_delta}%.\nDate: {quiz_date}";

        $subject = '';
        $body    = '';

        if ( ! empty( $debug['is_first_quiz'] ) ) {
            $subject = get_option( 'villegas_quiz_email_first_subject', 'Your First Quiz Results: {quiz_title}' );
            $body    = get_option( 'villegas_quiz_email_first_body', $default_first_body );
        } elseif ( ! empty( $debug['is_final_quiz'] ) ) {
            $subject = get_option( 'villegas_quiz_email_final_subject', 'Your Final Quiz Results: {quiz_title}' );
            $body    = get_option( 'villegas_quiz_email_final_body', $default_final_body );

            $first_percentage        = intval( $debug['first_quiz_attempt'] );
            $final_percentage        = intval( $debug['final_quiz_attempt'] );
            $debug['progress_delta'] = $final_percentage - $first_percentage;
        } else {
            return;
        }

        $subject = Villegas_Quiz_Emails::replace_placeholders( $subject, $debug );
        $body    = Villegas_Quiz_Emails::replace_placeholders( $body, $debug );

        if ( '' === trim( $subject ) || '' === trim( $body ) ) {
            return;
        }

        if ( strpos( $body, '<' ) === false ) {
            $body = nl2br( $body );
        }

        $send_to_student = get_option( 'villegas_quiz_email_send_to_student', 1 );
        $send_to_admin   = apply_filters( 'villegas_quiz_email_include_admin', get_option( 'villegas_quiz_email_send_to_admin', 1 ), $debug );
        $custom_prefix   = get_option( 'villegas_quiz_email_custom_subject', '' );

        $recipients = [];

        if ( $send_to_student && ! empty( $user->user_email ) ) {
            $recipients[] = $user->user_email;
        }

        if ( $send_to_admin ) {
            $admin_email = get_option( 'admin_email' );

            if ( $admin_email ) {
                $recipients[] = $admin_email;
            }
        }

        $recipients = apply_filters( 'villegas_quiz_email_recipients', $recipients, $debug );

        if ( empty( $recipients ) ) {
            return;
        }

        $subject = trim( ( $custom_prefix ? $custom_prefix . ' ' : '' ) . $subject );

        $subject = apply_filters( 'villegas_quiz_email_subject', $subject, $debug );
        $body    = apply_filters( 'villegas_quiz_email_body', $body, $debug );

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        $sent = wp_mail( $recipients, $subject, $body, $headers );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Villegas Quiz Emails] Sent? ' . ( $sent ? 'YES' : 'NO' ) );
            error_log( '[Villegas Quiz Emails] Recipients: ' . implode( ',', (array) $recipients ) );
            error_log( '[Villegas Quiz Emails] Subject: ' . $subject );
            error_log( '[Villegas Quiz Emails] Debug Data: ' . print_r( $debug, true ) );
        }
    }
}

add_action( 'villegas_process_quiz_email_queue', [ 'Villegas_Quiz_Email_Handler', 'process_queue' ] );
