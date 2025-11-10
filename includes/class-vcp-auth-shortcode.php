<?php
if (!defined('ABSPATH')) {
    exit;
}

final class VCP_Auth_Shortcode {
    public static function init() {
        add_shortcode('vcp_auth', [__CLASS__, 'render']);
    }

    public static function render($atts = []) {
        $atts = shortcode_atts([
            'label' => 'Register / Login',
        ], $atts, 'vcp_auth');

        ob_start();
        ?>
        <button class="vcp-auth-open"><?php echo esc_html($atts['label']); ?></button>
        <div class="vcp-auth-overlay" hidden></div>
        <div class="vcp-auth-modal" hidden></div>
        <?php
        return ob_get_clean();
    }
}

VCP_Auth_Shortcode::init();
