<?php
/**
 * Helper utilities that resolve course and commerce metadata for quizzes.
 */
class PoliteiaCourse {
    /**
     * Ensure legacy helper is available for backwards compatibility.
     */
    protected static function ensure_helper() {
        if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-course-quiz-helper.php';
        }
    }

    /**
     * Retrieve the course ID that owns a quiz using metadata.
     *
     * @param int $quiz_id Quiz post ID.
     * @return int Course ID or 0 when it cannot be resolved.
     */
    public static function getCourseFromQuiz( $quiz_id ) {
        self::ensure_helper();

        return CourseQuizMetaHelper::getCourseFromQuiz( $quiz_id );
    }

    /**
     * Retrieve the first quiz configured for a course.
     *
     * @param int $course_id Course post ID.
     * @return int Quiz ID or 0 when none is configured.
     */
    public static function getFirstQuizId( $course_id ) {
        self::ensure_helper();

        return CourseQuizMetaHelper::getFirstQuizId( $course_id );
    }

    /**
     * Retrieve the final quiz configured for a course.
     *
     * @param int $course_id Course post ID.
     * @return int Quiz ID or 0 when none is configured.
     */
    public static function getFinalQuizId( $course_id ) {
        self::ensure_helper();

        return CourseQuizMetaHelper::getFinalQuizId( $course_id );
    }

    /**
     * Resolve the WooCommerce product related to a course.
     *
     * @param int $course_id Course post ID.
     * @return int Product ID or 0 when none is linked.
     */
    public static function getRelatedProductId( $course_id ) {
        $course_id = intval( $course_id );

        if ( ! $course_id ) {
            return 0;
        }

        $product_id = get_post_meta( $course_id, '_linked_woocommerce_product', true );

        if ( $product_id ) {
            return intval( $product_id );
        }

        $products = get_posts(
            [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'meta_query'     => [
                    [
                        'key'     => '_related_course',
                        'value'   => $course_id,
                        'compare' => 'LIKE',
                    ],
                ],
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]
        );

        return $products ? intval( $products[0] ) : 0;
    }

    /**
     * Determine whether the user has access to a course.
     *
     * @param int $course_id Course post ID.
     * @param int $user_id   User ID.
     * @return bool
     */
    public static function userHasAccess( $course_id, $user_id ) {
        $course_id = intval( $course_id );
        $user_id   = intval( $user_id );

        if ( ! $course_id || ! $user_id ) {
            return false;
        }

        return (bool) sfwd_lms_has_access( $course_id, $user_id );
    }
}
