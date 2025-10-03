<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build First Quiz email content.
 *
 * @param array  $quiz_data Payload from learndash_quiz_completed.
 * @param object $user      WP_User object.
 * @param array  $debug     Debug data from Villegas_Quiz_Emails::get_quiz_debug_data().
 * @return array {subject, body}
 */
function villegas_get_first_quiz_email_content( $quiz_data, $user, $debug ) {
    $subject = sprintf( __( 'You finished your First Quiz: %s', 'villegas-courses' ), $debug['quiz_title'] );

    ob_start();
    ?>
    <html>
    <body style="font-family: Arial, sans-serif; background:#f9f9f9; padding:20px;">
        <div style="max-width:600px; margin:auto; background:#fff; padding:20px; border-radius:8px;">
            <h2 style="color:#333;"><?php echo esc_html( $debug['user_display_name'] ); ?>,</h2>
            <p><?php printf( __( 'You just completed the First Quiz of %s.', 'villegas-courses' ), esc_html( $debug['course_title'] ) ); ?></p>

            <p><strong><?php esc_html_e( 'Your Score:', 'villegas-courses' ); ?></strong>
               <?php echo esc_html( $debug['first_quiz_attempt'] ); ?></p>
            <p><em><?php echo esc_html( $debug['first_quiz_date'] ); ?></em></p>

            <p><?php esc_html_e( 'Great job! Continue the course to reinforce your knowledge.', 'villegas-courses' ); ?></p>

            <div style="margin-top:20px; text-align:center;">
                <a href="<?php echo esc_url( get_permalink( $debug['course_id_detected'] ) ); ?>"
                   style="background:#000; color:#fff; padding:12px 20px; text-decoration:none; border-radius:6px;">
                   <?php esc_html_e( 'Go to Course', 'villegas-courses' ); ?>
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    $body = ob_get_clean();

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}
