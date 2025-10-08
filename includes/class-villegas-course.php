<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Course {
    protected static function ensure_helper(): void {
        if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../classes/class-course-quiz-helper.php';
        }
    }

    public static function get_course_from_quiz( int $quiz_id ): int {
        self::ensure_helper();

        $quiz_id = absint( $quiz_id );

        if ( ! $quiz_id ) {
            return 0;
        }

        return (int) CourseQuizMetaHelper::getCourseFromQuiz( $quiz_id );
    }

    public static function get_first_quiz_id( int $course_id ): int {
        self::ensure_helper();

        $course_id = absint( $course_id );

        if ( ! $course_id ) {
            return 0;
        }

        return (int) CourseQuizMetaHelper::getFirstQuizId( $course_id );
    }

    public static function get_final_quiz_id( int $course_id ): int {
        self::ensure_helper();

        $course_id = absint( $course_id );

        if ( ! $course_id ) {
            return 0;
        }

        return (int) CourseQuizMetaHelper::getFinalQuizId( $course_id );
    }

    public static function get_related_product_id( int $course_id ): int {
        $course_id = absint( $course_id );

        if ( ! $course_id ) {
            return 0;
        }

        $product_id = get_post_meta( $course_id, '_linked_woocommerce_product', true );

        if ( $product_id ) {
            return (int) $product_id;
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

        return $products ? (int) $products[0] : 0;
    }

    public static function user_has_access( int $course_id, int $user_id ): bool {
        $course_id = absint( $course_id );
        $user_id   = absint( $user_id );

        if ( ! $course_id || ! $user_id ) {
            return false;
        }

        if ( function_exists( 'learndash_is_user_enrolled' ) ) {
            return (bool) learndash_is_user_enrolled( $course_id, $user_id );
        }

        if ( function_exists( 'sfwd_lms_has_access' ) ) {
            return (bool) sfwd_lms_has_access( $course_id, $user_id );
        }

        return false;
    }
}
