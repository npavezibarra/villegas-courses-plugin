<?php
/**
 * Helper utilities to resolve course/quiz relationships using metadata.
 */
class CourseQuizMetaHelper {
    /**
     * Retrieve the First Quiz ID for a course.
     *
     * @param int $course_id Course post ID.
     * @return int Quiz ID or 0 when not configured.
     */
    public static function getFirstQuizId( $course_id ) {
        $course_id = intval( $course_id );
        if ( ! $course_id ) {
            return 0;
        }

        $quiz_id = get_post_meta( $course_id, '_first_quiz_id', true );
        return $quiz_id ? intval( $quiz_id ) : 0;
    }

    /**
     * Retrieve the Final Quiz ID for a course.
     *
     * @param int $course_id Course post ID.
     * @return int Quiz ID or 0 when not configured.
     */
    public static function getFinalQuizId( $course_id ) {
        $course_id = intval( $course_id );
        if ( ! $course_id ) {
            return 0;
        }

        $quiz_id = get_post_meta( $course_id, '_final_quiz_id', true );
        return $quiz_id ? intval( $quiz_id ) : 0;
    }

    /**
     * Resolve the course ID that owns a quiz.
     *
     * @param int $quiz_id Quiz post ID.
     * @return int Course ID or 0 if none could be resolved.
     */
    public static function getCourseFromQuiz( $quiz_id ) {
        global $wpdb;

        $quiz_id = intval( $quiz_id );
        if ( ! $quiz_id ) {
            return 0;
        }

        // Look for a course that references the quiz via metadata.
        $course_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id
                   FROM {$wpdb->postmeta}
                  WHERE meta_key IN ('_first_quiz_id', '_final_quiz_id')
                    AND meta_value = %d
               ORDER BY post_id ASC
                  LIMIT 1",
                $quiz_id
            )
        );

        if ( $course_id ) {
            return intval( $course_id );
        }

        // Fallback to LearnDash helper if available.
        if ( function_exists( 'learndash_get_course_id' ) ) {
            $fallback = intval( learndash_get_course_id( $quiz_id ) );
            if ( $fallback ) {
                return $fallback;
            }
        }

        // Final fallback: scan ld_course_steps for backwards compatibility.
        $course_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id
                   FROM {$wpdb->postmeta}
                  WHERE meta_key = 'ld_course_steps'
                    AND meta_value LIKE %s
                  LIMIT 1",
                '%i:' . $quiz_id . ';%'
            )
        );

        return $course_id ? intval( $course_id ) : 0;
    }
}
