<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Quiz_Email_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_menu_page() {
        add_options_page(
            __( 'Quiz Email Settings', 'villegas-courses' ),
            __( 'Quiz Emails', 'villegas-courses' ),
            'manage_options',
            'villegas-quiz-emails',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_send_to_student' );
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_send_to_admin' );
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_custom_subject' );
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_first_subject' );
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_first_body' );
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_final_subject' );
        register_setting( 'villegas_quiz_email_group', 'villegas_quiz_email_final_body' );
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Quiz Email Settings', 'villegas-courses' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'villegas_quiz_email_group' ); ?>
                <?php do_settings_sections( 'villegas_quiz_email_group' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Send to Student', 'villegas-courses' ); ?></th>
                        <td>
                            <input type="checkbox" name="villegas_quiz_email_send_to_student" value="1" 
                                <?php checked( 1, get_option( 'villegas_quiz_email_send_to_student', 1 ) ); ?> />
                            <label><?php esc_html_e( 'Send quiz results to the student who took the quiz', 'villegas-courses' ); ?></label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Send to Admin', 'villegas-courses' ); ?></th>
                        <td>
                            <input type="checkbox" name="villegas_quiz_email_send_to_admin" value="1" 
                                <?php checked( 1, get_option( 'villegas_quiz_email_send_to_admin', 1 ) ); ?> />
                            <label><?php esc_html_e( 'Send quiz results to the site admin email', 'villegas-courses' ); ?></label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Custom Subject Prefix', 'villegas-courses' ); ?></th>
                        <td>
                            <input type="text" name="villegas_quiz_email_custom_subject"
                                value="<?php echo esc_attr( get_option( 'villegas_quiz_email_custom_subject', '' ) ); ?>"
                                class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Optional prefix to add before the email subject', 'villegas-courses' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'First Quiz Email – Subject', 'villegas-courses' ); ?></th>
                        <td>
                            <input type="text" name="villegas_quiz_email_first_subject"
                                   value="<?php echo esc_attr( get_option( 'villegas_quiz_email_first_subject', 'Your First Quiz Results: {quiz_title}' ) ); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'First Quiz Email – Body', 'villegas-courses' ); ?></th>
                        <td>
                            <textarea name="villegas_quiz_email_first_body" rows="6" cols="60"><?php
                                echo esc_textarea( get_option(
                                    'villegas_quiz_email_first_body',
                                    "Hello {user_name},\n\nYou scored {first_score} in {quiz_title} for {course_title}.\nDate: {quiz_date}"
                                ) );
                            ?></textarea>
                            <p class="description"><?php esc_html_e( 'Use placeholders like {user_name}, {quiz_title}, {course_title}, {first_score}, {quiz_date}', 'villegas-courses' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Final Quiz Email – Subject', 'villegas-courses' ); ?></th>
                        <td>
                            <input type="text" name="villegas_quiz_email_final_subject"
                                   value="<?php echo esc_attr( get_option( 'villegas_quiz_email_final_subject', 'Your Final Quiz Results: {quiz_title}' ) ); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Final Quiz Email – Body', 'villegas-courses' ); ?></th>
                        <td>
                            <textarea name="villegas_quiz_email_final_body" rows="6" cols="60"><?php
                                echo esc_textarea( get_option(
                                    'villegas_quiz_email_final_body',
                                    "Hello {user_name},\n\nYou scored {final_score} in {quiz_title}.\nYour First Quiz was {first_score}, so your progress is {progress_delta}%.\nDate: {quiz_date}"
                                ) );
                            ?></textarea>
                            <p class="description"><?php esc_html_e( 'Use placeholders like {user_name}, {quiz_title}, {course_title}, {first_score}, {final_score}, {progress_delta}, {quiz_date}', 'villegas-courses' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
