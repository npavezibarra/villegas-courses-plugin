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

        $nonce = wp_create_nonce('vcp_auth_nonce');

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

            <div class="vcp-social-login">
                <button class="vcp-google-login" data-provider="google">
                    Continue with Google
                </button>
            </div>

            <div class="vcp-auth-panels">
                <form id="vcp-login" class="vcp-auth-panel is-active" method="post">
                    <h3 id="vcp-auth-title">Login</h3>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="action" value="">
                    <div class="vcp-field"><label>Username</label><input type="text" name="log"></div>
                    <div class="vcp-field"><label>Password</label><input type="password" name="pwd"></div>
                    <div class="vcp-captcha" data-type="login"></div>
                    <button type="submit">Login</button>
                </form>

                <form id="vcp-register" class="vcp-auth-panel" method="post">
                    <h3>Register</h3>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="action" value="">
                    <div class="vcp-field"><label>Email</label><input type="email" name="user_email"></div>
                    <div class="vcp-field"><label>Username</label><input type="text" name="user_login"></div>
                    <div class="vcp-field"><label>Password</label><input type="password" name="user_pass"></div>
                    <div class="vcp-captcha" data-type="register"></div>
                    <button type="submit">Create account</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

VCP_Auth_Shortcode::init();
