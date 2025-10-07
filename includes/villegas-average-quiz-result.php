<?php
/**
 * Shortcode that renders quiz attempts table and exposes average percentage.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Villegas_Quiz_Attempts_Shortcode' ) ) {
    class Villegas_Quiz_Attempts_Shortcode {
        /**
         * Último promedio calculado para el shortcode actual.
         *
         * @var int
         */
        public static $last_average = 0;

        /**
         * Renderiza la tabla de intentos + promedio y almacena el promedio para
         * otros componentes que lo necesiten (como la plantilla del quiz).
         *
         * @param array $atts Shortcode attributes.
         * @return string
         */
        public static function render( $atts ) {
            global $wpdb;

            $GLOBALS['villegas_quiz_last_average'] = 0;
            self::$last_average                    = 0;

            $atts    = shortcode_atts( [ 'id' => 0 ], $atts, 'villegas_quiz_attempts' );
            $quiz_id = intval( $atts['id'] );

            if ( ! $quiz_id ) {
                return '<p style="color:#c00;">' . esc_html__( 'Quiz ID inválido.', 'villegas-courses' ) . '</p>';
            }

            $activity_table = $wpdb->prefix . 'learndash_user_activity';
            $meta_table     = $wpdb->prefix . 'learndash_user_activity_meta';

            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ua.activity_id, ua.user_id, ua.activity_completed
                       FROM {$activity_table} AS ua
                 INNER JOIN {$meta_table} AS uam
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
                return '<p>' . esc_html__( 'No hay intentos registrados para este quiz.', 'villegas-courses' ) . '</p>';
            }

            $sum   = 0;
            $count = 0;

            ob_start();
            ?>
            <table class="villegas-quiz-attempts" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="border:1px solid #ddd; padding:8px; text-align:left;">Activity ID</th>
                        <th style="border:1px solid #ddd; padding:8px; text-align:left;">Usuario</th>
                        <th style="border:1px solid #ddd; padding:8px; text-align:left;">Fecha y hora</th>
                        <th style="border:1px solid #ddd; padding:8px; text-align:left;">Puntaje %</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            foreach ( $rows as $row ) {
                $user      = get_userdata( $row->user_id );
                $user_name = $user ? esc_html( $user->display_name ) : esc_html( $row->user_id );

                $pct_val = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT activity_meta_value
                           FROM {$meta_table}
                          WHERE activity_id = %d
                            AND activity_meta_key = 'percentage'
                          LIMIT 1",
                        $row->activity_id
                    )
                );

                $pct_val = null !== $pct_val ? floatval( $pct_val ) : 0;

                $sum   += $pct_val;
                $count ++;

                $pct_fmt = round( $pct_val ) . '%';
                $date    = date_i18n(
                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                    strtotime( $row->activity_completed )
                );
                ?>
                <tr>
                    <td style="border:1px solid #ddd; padding:8px;"><?php echo esc_html( $row->activity_id ); ?></td>
                    <td style="border:1px solid #ddd; padding:8px;"><?php echo $user_name; ?></td>
                    <td style="border:1px solid #ddd; padding:8px;"><?php echo esc_html( $date ); ?></td>
                    <td style="border:1px solid #ddd; padding:8px;"><?php echo esc_html( $pct_fmt ); ?></td>
                </tr>
                <?php
            }

            $avg = $count > 0 ? round( $sum / $count ) : 0;

            $GLOBALS['villegas_quiz_last_average'] = $avg;
            self::$last_average                    = $avg;
            ?>
                <tr style="background:#f9f9f9;">
                    <th colspan="3" style="border:1px solid #ddd; padding:8px; text-align:right;">Promedio General</th>
                    <th style="border:1px solid #ddd; padding:8px; text-align:left;"><?php echo esc_html( $avg ); ?>%</th>
                </tr>
                </tbody>
            </table>
            <?php
            return ob_get_clean();
        }
    }

    add_action(
        'init',
        static function() {
            add_shortcode( 'villegas_quiz_attempts', [ 'Villegas_Quiz_Attempts_Shortcode', 'render' ] );
        }
    );
}
