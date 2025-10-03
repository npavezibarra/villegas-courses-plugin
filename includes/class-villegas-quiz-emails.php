<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Quiz_Emails {

    /**
     * Fetch the last completed attempt for a given quiz and user.
     *
     * @param int $user_id
     * @param int $quiz_id
     * @return array {
     *   @type string $percentage   e.g. "85%"
     *   @type string $date         formatted date or '—'
     *   @type int    $activity_id  ID of the attempt, or 0 if none
     * }
     */
    public static function get_last_attempt_data( $user_id, $quiz_id ) {
        global $wpdb;

        if ( empty( $quiz_id ) || empty( $user_id ) ) {
            return [ 'percentage' => 'None', 'date' => '—', 'activity_id' => 0 ];
        }

        $activity = $wpdb->get_row( $wpdb->prepare(
            "SELECT ua.activity_id, ua.activity_completed
             FROM {$wpdb->prefix}learndash_user_activity AS ua
             INNER JOIN {$wpdb->prefix}learndash_user_activity_meta AS uam
                ON ua.activity_id = uam.activity_id
             WHERE ua.user_id = %d
               AND uam.activity_meta_key = 'quiz'
               AND uam.activity_meta_value+0 = %d
               AND ua.activity_type = 'quiz'
               AND ua.activity_completed IS NOT NULL
             ORDER BY ua.activity_id DESC
             LIMIT 1",
            $user_id,
            $quiz_id
        ) );

        if ( ! $activity ) {
            return [ 'percentage' => 'None', 'date' => '—', 'activity_id' => 0 ];
        }

        $percentage = $wpdb->get_var( $wpdb->prepare(
            "SELECT activity_meta_value+0
             FROM {$wpdb->prefix}learndash_user_activity_meta
             WHERE activity_id = %d
               AND activity_meta_key = 'percentage'
             LIMIT 1",
            $activity->activity_id
        ) );

        return [
            'percentage'  => $percentage !== null ? intval( $percentage ) . '%' : 'None',
            'date'        => date_i18n( get_option( 'date_format' ) . ' H:i', $activity->activity_completed ),
            'activity_id' => intval( $activity->activity_id ),
        ];
    }

    /**
     * Collect debug data for a completed quiz attempt.
     * Similar to pqc_get_quiz_debug_data() but adapted for Villegas.
     *
     * @param array  $quiz_data  Payload from learndash_quiz_completed.
     * @param object $user       WP_User object.
     * @return array
     */
    public static function get_quiz_debug_data( $quiz_data, $user ) {
        global $wpdb;
        $user_id = $user->ID;
        $quiz_id = is_object( $quiz_data['quiz'] ) ? $quiz_data['quiz']->ID : $quiz_data['quiz'];

        // Find which course this quiz belongs to
        $course_id_first = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_first_quiz_id' AND meta_value = %d
             LIMIT 1",
            $quiz_id
        ) );

        $course_id_final = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_final_quiz_id' AND meta_value = %d
             LIMIT 1",
            $quiz_id
        ) );

        $course_id = $course_id_first ?: $course_id_final;

        $first_quiz_id = $course_id ? get_post_meta( $course_id, '_first_quiz_id', true ) : null;
        $final_quiz_id = $course_id ? get_post_meta( $course_id, '_final_quiz_id', true ) : null;

        $first_data = $first_quiz_id
            ? self::get_last_attempt_data( $user_id, $first_quiz_id )
            : [ 'percentage' => 'None', 'date' => '—', 'activity_id' => 0 ];

        $final_data = $final_quiz_id
            ? self::get_last_attempt_data( $user_id, $final_quiz_id )
            : [ 'percentage' => 'None', 'date' => '—', 'activity_id' => 0 ];

        $progress_data = $course_id
            ? learndash_course_progress([ 'user_id' => $user_id, 'course_id' => $course_id, 'array' => true ])
            : [ 'percentage' => 0 ];

        return [
            'user_id'            => $user_id,
            'user_display_name'  => $user->display_name ?: 'N/A',
            'user_email'         => $user->user_email ?: 'N/A',
            'quiz_id'            => $quiz_id,
            'quiz_title'         => get_the_title( $quiz_id ),
            'is_first_quiz'      => (int) $quiz_id === (int) $first_quiz_id,
            'is_final_quiz'      => (int) $quiz_id === (int) $final_quiz_id,
            'course_id_detected' => $course_id ?: 'N/A',
            'course_title'       => $course_id ? get_the_title( $course_id ) : 'N/A',
            'first_quiz_id'      => $first_quiz_id ?: 'N/A',
            'first_quiz_attempt' => $first_data['percentage'],
            'first_quiz_date'    => $first_data['date'],
            'final_quiz_id'      => $final_quiz_id ?: 'N/A',
            'final_quiz_attempt' => $final_data['percentage'],
            'final_quiz_date'    => $final_data['date'],
            'lessons_completed'  => isset( $progress_data['percentage'] ) ? $progress_data['percentage'] . '%' : '0%',
            'ld_course_id_hook'  => $quiz_data['course'],
            'ld_percentage_hook' => $quiz_data['percentage'],
        ];
    }
}
