<?php
/**
 * Shortcode: [academia_villegas]
 * Description: Displays the Academia Villegas banner.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function villegas_academia_shortcode() {
    ob_start();
    ?>
    <div class="villegas-academia-banner">
        <h1>Academia Villegas</h1>
    </div>
    <style>
        .villegas-academia-banner {
            width: 100%;
            height: 300px;
            background-color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .villegas-academia-banner h1 {
            color: #fff;
            font-size: 2.5rem;
            text-align: center;
            margin: 0;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode( 'academia_villegas', 'villegas_academia_shortcode' );
