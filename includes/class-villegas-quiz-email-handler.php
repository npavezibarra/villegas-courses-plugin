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

        $email_content = null;

        // Choose which template to load
        if ( $debug['is_first_quiz'] ) {
            require_once plugin_dir_path( __FILE__ ) . '../emails/first-quiz-email.php';
            $email_content = villegas_get_first_quiz_email_content( $quiz_data, $user, $debug );
        } elseif ( $debug['is_final_quiz'] ) {
            require_once plugin_dir_path( __FILE__ ) . '../emails/final-quiz-email.php';
            $email_content = villegas_get_final_quiz_email_content( $quiz_data, $user, $debug );
        }

        // Send the email
        if ( is_array( $email_content ) && ! empty( $email_content['subject'] ) && ! empty( $email_content['body'] ) ) {
            $to      = get_option( 'admin_email' ); // For now, send to admin. Can extend later.
            $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

            $sent = wp_mail( $to, $email_content['subject'], $email_content['body'], $headers );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Villegas Quiz Emails] Email sent? ' . ( $sent ? 'YES' : 'NO' ) );
                error_log( '[Villegas Quiz Emails] Recipient: ' . $to );
                error_log( '[Villegas Quiz Emails] Subject: ' . $email_content['subject'] );
                error_log( '[Villegas Quiz Emails] Debug Data: ' . print_r( $debug, true ) );
            }
        }
    }
}
