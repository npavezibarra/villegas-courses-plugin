<?php
/**
 * Provides helpers to aggregate quiz performance across all users.
 */
class Villegas_Quiz_Stats {
    /**
     * Format a percentage value consistently across all displays.
     *
     * @param float $value Raw percentage value.
     * @return int Rounded integer representation of the percentage.
     */
    public static function format_percentage( float $value ): int {
        return villegas_round_half_up( $value );
    }

    /**
     * Fetch all attempts recorded for a LearnDash quiz across every user.
     *
     * @param int $quiz_id LearnDash quiz post ID.
     * @return array[] List of attempts with user, activity and score data.
     */
    public static function get_all_attempts_for_quiz( int $quiz_id ): array {
        global $wpdb;

        $quiz_id = intval( $quiz_id );

        if ( ! $quiz_id ) {
            return [];
        }

        $ua  = $wpdb->prefix . 'learndash_user_activity';
        $uam = $wpdb->prefix . 'learndash_user_activity_meta';

        $sql = $wpdb->prepare(
            "SELECT
                ua.user_id,
                ua.activity_id,
                MAX( CASE WHEN meta.activity_meta_key = 'percentage' THEN meta.activity_meta_value END ) AS percentage,
                MAX( CASE WHEN meta.activity_meta_key = 'points' THEN meta.activity_meta_value END )      AS points
            FROM {$ua} ua
            INNER JOIN {$uam} quiz_meta
                ON quiz_meta.activity_id = ua.activity_id
               AND quiz_meta.activity_meta_key = 'quiz'
               AND quiz_meta.activity_meta_value = %d
            LEFT JOIN {$uam} meta
                ON meta.activity_id = ua.activity_id
            WHERE ua.activity_type = 'quiz'
            GROUP BY ua.activity_id, ua.user_id",
            $quiz_id
        );

        $rows = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $rows ) ) {
            return [];
        }

        $attempts = [];

        foreach ( $rows as $row ) {
            $percentage_raw = $row['percentage'];
            $points_raw     = $row['points'];

            $attempts[] = [
                'user_id'     => intval( $row['user_id'] ),
                'activity_id' => intval( $row['activity_id'] ),
                'percentage'  => is_numeric( $percentage_raw ) ? floatval( $percentage_raw ) : null,
                'points'      => is_numeric( $points_raw ) ? floatval( $points_raw ) : null,
            ];
        }

        return $attempts;
    }

    /**
     * Calculate the average percentage achieved for a quiz across all attempts.
     *
     * @param int $quiz_id LearnDash quiz post ID.
     * @return float|null Average percentage or null when no data is available.
     */
    public static function get_average_percentage( int $quiz_id ): ?float {
        $attempts = self::get_all_attempts_for_quiz( $quiz_id );

        if ( empty( $attempts ) ) {
            return null;
        }

        $valid_percentages = array_filter(
            wp_list_pluck( $attempts, 'percentage' ),
            static function( $value ) {
                return null !== $value;
            }
        );

        if ( empty( $valid_percentages ) ) {
            return null;
        }

        $total = array_sum( $valid_percentages );
        $count = count( $valid_percentages );

        return $count > 0 ? ( $total / $count ) : null;
    }

    /**
     * Fetch the latest attempt percentage for a specific user and quiz.
     *
     * @param int $quiz_id LearnDash quiz post ID.
     * @param int $user_id WP user ID.
     * @return float|null Percentage of the latest attempt or null if not found.
     */
    public static function get_latest_attempt_percentage( $quiz_id, $user_id ) {
        $quiz_id = absint( $quiz_id );
        $user_id = absint( $user_id );

        if ( ! $quiz_id || ! $user_id ) {
            return null;
        }

        if ( function_exists( 'villegas_get_latest_quiz_attempt' ) ) {
            $attempt = villegas_get_latest_quiz_attempt( $user_id, $quiz_id );

            if ( is_array( $attempt ) && isset( $attempt['percentage'] ) && null !== $attempt['percentage'] ) {
                return is_numeric( $attempt['percentage'] ) ? (float) $attempt['percentage'] : null;
            }
        }

        global $wpdb;

        $table = "{$wpdb->prefix}learndash_user_activity";

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT activity_meta
                 FROM {$table}
                 WHERE user_id = %d AND activity_type = 'quiz' AND post_id = %d
                 ORDER BY activity_updated DESC LIMIT 1",
                $user_id,
                $quiz_id
            )
        );

        if ( $result ) {
            $meta = maybe_unserialize( $result );

            if ( isset( $meta['percentage'] ) && is_numeric( $meta['percentage'] ) ) {
                return (float) $meta['percentage'];
            }
        }

        return null;
    }
}
