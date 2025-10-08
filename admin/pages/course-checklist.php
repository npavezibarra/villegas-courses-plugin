<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../../classes/class-course-quiz-helper.php';
}

if ( ! function_exists( 'villegas_course_checklist_find_final_quiz_id' ) ) {
    /**
     * Recursively search LearnDash course steps for the first quiz ID.
     *
     * @param mixed $steps Course steps structure from ld_course_steps meta.
     *
     * @return int|null Quiz post ID if found.
     */
    function villegas_course_checklist_find_final_quiz_id( $steps ) {
        if ( empty( $steps ) || ! is_array( $steps ) ) {
            return null;
        }

        foreach ( $steps as $key => $value ) {
            // Some course step arrays store quiz IDs as the key, others in nested arrays.
            if ( is_numeric( $key ) ) {
                $quiz_id = absint( $key );
                if ( $quiz_id && 'sfwd-quiz' === get_post_type( $quiz_id ) ) {
                    return $quiz_id;
                }
            }

            if ( is_array( $value ) ) {
                $quiz_id = villegas_course_checklist_find_final_quiz_id( $value );
                if ( $quiz_id ) {
                    return $quiz_id;
                }
            }
        }

        return null;
    }
}

/**
 * Render the Course Checklist admin page.
 */
function villegas_render_course_checklist_page() {
    global $wpdb;

    $courses = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'sfwd-courses' AND post_status = 'publish' ORDER BY post_title ASC"
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Course Checklist', 'villegas-courses' ); ?></h1>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Course ID', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'Course Title', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'First Quiz', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'Final Quiz', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'Product', 'villegas-courses' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $courses ) ) : ?>
                    <?php foreach ( $courses as $course ) : ?>
                        <?php
                        $course_id = absint( $course->ID );
                        $course_title = isset( $course->post_title ) ? $course->post_title : '';

                        $first_quiz_id = 0;
                        $final_quiz_id = 0;
                        $product_id    = 0;

                        if ( class_exists( 'CourseQuizMetaHelper' ) ) {
                            $first_quiz_id = absint( CourseQuizMetaHelper::getFirstQuizId( $course_id ) );
                            $final_quiz_id = absint( CourseQuizMetaHelper::getFinalQuizId( $course_id ) );
                        }

                        if ( ! $first_quiz_id ) {
                            $first_quiz_id = absint( get_post_meta( $course_id, '_first_quiz_id', true ) );
                        }

                        if ( ! $final_quiz_id ) {
                            $ld_steps      = get_post_meta( $course_id, 'ld_course_steps', true );
                            $final_quiz_id = absint( villegas_course_checklist_find_final_quiz_id( $ld_steps ) );
                        }

                        if ( function_exists( 'villegas_get_course_product_id' ) ) {
                            $product_id = absint( villegas_get_course_product_id( $course_id ) );
                        }

                        if ( ! $product_id ) {
                            $product_id = absint( get_post_meta( $course_id, '_related_product', true ) );
                        }

                        if ( ! $product_id ) {
                            $product_id = absint( get_post_meta( $course_id, '_linked_woocommerce_product', true ) );
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( $course_id ); ?></td>
                            <td><?php echo esc_html( $course_title ); ?></td>
                            <td>
                                <?php if ( $first_quiz_id ) : ?>
                                    <?php echo esc_html( $first_quiz_id ); ?>
                                <?php else : ?>
                                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sfwd-quiz' ) ); ?>"><?php esc_html_e( 'CREATE', 'villegas-courses' ); ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $final_quiz_id ) : ?>
                                    <?php echo esc_html( $final_quiz_id ); ?>
                                <?php else : ?>
                                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sfwd-quiz' ) ); ?>"><?php esc_html_e( 'CREATE', 'villegas-courses' ); ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $product_id ) : ?>
                                    <?php echo esc_html( $product_id ); ?>
                                <?php else : ?>
                                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>"><?php esc_html_e( 'CREATE', 'villegas-courses' ); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e( 'No published courses found.', 'villegas-courses' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
