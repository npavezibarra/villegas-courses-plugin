<?php
if ( ! function_exists( 'villegas_round_half_up' ) ) {
    /**
     * Round a numeric value using half-up strategy.
     *
     * @param float|int $value Numeric value to round.
     * @return int Rounded integer.
     */
    function villegas_round_half_up( $value ) {
        return (int) floor( (float) $value + 0.5 );
    }
}
