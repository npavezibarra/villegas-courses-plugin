<?php
/**
 * Helper utilities for retrieving quiz statistics for Villegas Courses.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Quiz_Stats {

    public static function get_latest_attempt_id( int $user_id, int $quiz_id ): ?int {
        global $wpdb;

        $ua_table  = $wpdb->prefix . 'learndash_user_activity';
        $uam_table = $wpdb->prefix . 'learndash_user_activity_meta';

        $activity_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ua.activity_id FROM {$ua_table} ua
                 INNER JOIN {$uam_table} m ON m.activity_id = ua.activity_id
                 WHERE m.activity_meta_key = 'quiz' AND m.activity_meta_value = %d
                   AND ua.user_id = %d AND ua.activity_type = 'quiz'
                 ORDER BY ua.activity_completed DESC LIMIT 1",
                $quiz_id,
                $user_id
            )
        );

        return $activity_id ? intval( $activity_id ) : null;
    }

    public static function get_score_and_pct_by_activity( int $activity_id ): ?object {
        global $wpdb;

        $uam_table = $wpdb->prefix . 'learndash_user_activity_meta';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    MAX(CASE WHEN activity_meta_key = 'percentage' THEN activity_meta_value END) AS percentage,
                    MAX(CASE WHEN activity_meta_key = 'points' THEN activity_meta_value END) AS points
                 FROM {$uam_table}
                 WHERE activity_id = %d",
                $activity_id
            )
        );

        return $row ?: null;
    }

    public static function get_all_attempts_data( int $user_id, int $quiz_id ): array {
        global $wpdb;

        $ua_table  = $wpdb->prefix . 'learndash_user_activity';
        $uam_table = $wpdb->prefix . 'learndash_user_activity_meta';

        $activity_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ua.activity_id FROM {$ua_table} ua
                 INNER JOIN {$uam_table} m ON m.activity_id = ua.activity_id
                 WHERE m.activity_meta_key = 'quiz' AND m.activity_meta_value = %d
                   AND ua.user_id = %d AND ua.activity_type = 'quiz'
                 ORDER BY ua.activity_completed DESC",
                $quiz_id,
                $user_id
            )
        );

        if ( empty( $activity_ids ) ) {
            return [];
        }

        $attempts = [];

        foreach ( $activity_ids as $activity_id ) {
            $meta = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        MAX(CASE WHEN activity_meta_key = 'percentage' THEN activity_meta_value END) AS pct,
                        MAX(CASE WHEN activity_meta_key = 'points' THEN activity_meta_value END) AS pts
                     FROM {$uam_table}
                     WHERE activity_id = %d",
                    $activity_id
                )
            );

            $attempts[] = [
                'activity_id' => (int) $activity_id,
                'percentage'  => $meta && $meta->pct !== null ? round( floatval( $meta->pct ), 2 ) . '%' : '–',
                'points'      => $meta && $meta->pts !== null ? (int) $meta->pts : 0,
            ];
        }

        return $attempts;
    }
}
