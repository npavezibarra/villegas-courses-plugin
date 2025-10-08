<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Villegas_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-villegas-quiz-stats.php';
}

class Villegas_Quiz_Attempts_Shortcode {
    public static $last_average = 0.0;

    public static function render( $atts ): string {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'villegas_quiz_attempts'
        );

        $quiz_id = absint( $atts['id'] );

        if ( $quiz_id ) {
            $average = Villegas_Quiz_Stats::get_average_percentage( $quiz_id );

            if ( null !== $average ) {
                self::$last_average = round( (float) $average, 2 );
            }
        }

        return '';
    }
}

add_shortcode( 'villegas_quiz_attempts', [ 'Villegas_Quiz_Attempts_Shortcode', 'render' ] );
