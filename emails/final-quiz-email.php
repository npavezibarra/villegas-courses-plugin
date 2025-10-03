<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build Final Quiz email content.
 *
 * @param array  $quiz_data Payload from learndash_quiz_completed.
 * @param object $user      WP_User object.
 * @param array  $debug     Debug data from Villegas_Quiz_Emails::get_quiz_debug_data().
 * @return array {subject, body}
 */
function villegas_get_final_quiz_email_content( $quiz_data, $user, $debug ) {
    $subject = sprintf( __( 'Your Final Quiz results for %s', 'villegas-courses' ), $debug['course_title'] );

    $first_percentage = intval( $debug['first_quiz_attempt'] );
    $final_percentage = intval( $debug['final_quiz_attempt'] );
    $delta            = $final_percentage - $first_percentage;

    if ( $delta > 0 ) {
        $progress_msg = sprintf( __( 'Congratulations! You improved by %d%% compared to your First Quiz.', 'villegas-courses' ), $delta );
    } elseif ( $delta === 0 ) {
        $progress_msg = __( 'Your score remained the same as your First Quiz.', 'villegas-courses' );
    } else {
        $progress_msg = sprintf( __( 'Your score decreased by %d%% compared to your First Quiz.', 'villegas-courses' ), abs( $delta ) );
    }

    ob_start();
    ?>
    <html>
    <body style="font-family: Arial, sans-serif; background:#f9f9f9; padding:20px;">
        <div style="max-width:600px; margin:auto; background:#fff; padding:20px; border-radius:8px;">
            <h2 style="color:#333;"><?php echo esc_html( $debug['user_display_name'] ); ?>,</h2>
            <p><?php printf( __( 'You just completed the Final Quiz of %s.', 'villegas-courses' ), esc_html( $debug['course_title'] ) ); ?></p>

            <h3><?php esc_html_e( 'Your Results', 'villegas-courses' ); ?></h3>
            <p><strong><?php esc_html_e( 'First Quiz:', 'villegas-courses' ); ?></strong>
               <?php echo esc_html( $debug['first_quiz_attempt'] ); ?>
               (<?php echo esc_html( $debug['first_quiz_date'] ); ?>)</p>
            <p><strong><?php esc_html_e( 'Final Quiz:', 'villegas-courses' ); ?></strong>
               <?php echo esc_html( $debug['final_quiz_attempt'] ); ?>
               (<?php echo esc_html( $debug['final_quiz_date'] ); ?>)</p>

            <p style="margin-top:15px; font-size:16px; color:#006600;">
               <?php echo esc_html( $progress_msg ); ?>
            </p>

            <div style="margin-top:20px; text-align:center;">
                <a href="<?php echo esc_url( get_permalink( $debug['course_id_detected'] ) ); ?>"
                   style="background:#000; color:#fff; padding:12px 20px; text-decoration:none; border-radius:6px;">
                   <?php esc_html_e( 'Browse Courses', 'villegas-courses' ); ?>
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
