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
        <div class="vcp-auth-modal" hidden role="dialog" aria-modal="true" aria-labelledby="vcp-auth-title">
            <button class="vcp-auth-close" aria-label="Close">×</button>

            <div class="vcp-auth-tabs">
                <button class="vcp-auth-tab is-active" data-target="#vcp-login">Login</button>
                <button class="vcp-auth-tab" data-target="#vcp-register">Register</button>
            </div>

            <div class="vcp-auth-panels">
                <form id="vcp-login" class="vcp-auth-panel is-active">
                    <h3 id="vcp-auth-title">Login</h3>
                    <div class="vcp-field"><label>Username</label><input type="text"></div>
                    <div class="vcp-field"><label>Password</label><input type="password"></div>
                    <button type="submit">Login</button>
                </form>

                <form id="vcp-register" class="vcp-auth-panel">
                    <h3>Register</h3>
                    <div class="vcp-field"><label>Email</label><input type="email"></div>
                    <div class="vcp-field"><label>Username</label><input type="text"></div>
                    <div class="vcp-field"><label>Password</label><input type="password"></div>
                    <button type="submit">Create account</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

VCP_Auth_Shortcode::init();
