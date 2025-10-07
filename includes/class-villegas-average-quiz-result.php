<?php
/**
 * Shortcode for displaying Villegas quiz attempts and averages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Quiz_Attempts_Shortcode {
    public static $last_average = 0;

    public static function render( $atts ) {
        global $wpdb;

        $GLOBALS['villegas_quiz_last_average'] = 0;

        $atts    = shortcode_atts( [ 'id' => 0 ], $atts, 'villegas_quiz_attempts' );
        $quiz_id = intval( $atts['id'] );

        if ( ! $quiz_id ) {
            return '<p>Quiz ID inválido.</p>';
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ua.activity_id, ua.user_id, ua.activity_completed
                 FROM {$wpdb->prefix}learndash_user_activity AS ua
                 INNER JOIN {$wpdb->prefix}learndash_user_activity_meta AS uam
                   ON ua.activity_id = uam.activity_id
                 WHERE ua.activity_type = 'quiz'
                   AND ua.activity_completed IS NOT NULL
                   AND uam.activity_meta_key = 'quiz'
                   AND uam.activity_meta_value+0 = %d
                 ORDER BY ua.activity_completed DESC",
                $quiz_id
            )
        );

        if ( empty( $rows ) ) {
            return '<p>No hay intentos registrados.</p>';
        }

        $sum   = 0;
        $count = 0;

        $html  = '<table style="width:100%; border-collapse:collapse;">';
        $html .= '<thead><tr><th>Activity ID</th><th>Usuario</th><th>Fecha</th><th>%</th></tr></thead><tbody>';

        foreach ( $rows as $row ) {
            $pct = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT activity_meta_value FROM {$wpdb->prefix}learndash_user_activity_meta
                     WHERE activity_id=%d AND activity_meta_key='percentage' LIMIT 1",
                    $row->activity_id
                )
            );

            $user    = get_userdata( $row->user_id );
            $pct_val = $pct ? floatval( $pct ) : 0;

            $sum   += $pct_val;
            $count++;

            $html .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s%%</td></tr>',
                $row->activity_id,
                esc_html( $user ? $user->display_name : $row->user_id ),
                esc_html( date_i18n( 'Y-m-d H:i', strtotime( $row->activity_completed ) ) ),
                round( $pct_val )
            );
        }

        $avg = $count ? round( $sum / $count ) : 0;

        $GLOBALS['villegas_quiz_last_average'] = $avg;
        self::$last_average                     = $avg;

        $html .= sprintf(
            '<tr style="background:#f9f9f9;"><th colspan="3" style="text-align:right;">Promedio Villegas</th><th>%d%%</th></tr>',
            $avg
        );

        return $html . '</tbody></table>';
    }
}
add_action( 'init', function() {
    add_shortcode( 'villegas_quiz_attempts', [ 'Villegas_Quiz_Attempts_Shortcode', 'render' ] );
} );
