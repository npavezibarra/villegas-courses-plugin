<?php
if (!defined('ABSPATH')) {
    exit;
}

final class VCP_Auth_Shortcode {
    public static function init() {
        add_shortcode('vcp_auth', [__CLASS__, 'render']);
    }

    public static function render($atts = []) {
        $has_redirect = array_key_exists('redirect', $atts);

        $atts = shortcode_atts([
            'label'    => 'Register / Login',
            'redirect' => home_url(),
        ], $atts, 'vcp_auth');

        $atts['redirect'] = $has_redirect ? $atts['redirect'] : '';

        $redirect = apply_filters('vcp_auth_redirect_url', $atts['redirect'], $atts);
        $redirect = is_string($redirect) ? esc_url_raw($redirect) : '';

        if (wp_script_is('vcp-auth-js', 'enqueued') || wp_script_is('vcp-auth-js', 'registered')) {
            wp_localize_script('vcp-auth-js', 'VCP_AUTH_REDIRECT', [
                'redirect' => esc_url($redirect),
            ]);
        }

        if (is_user_logged_in()) {
            $user = wp_get_current_user();

            ob_start();
            ?>
            <div class="vcp-auth-logged">
                <span class="vcp-auth-greet">Hello, <?php echo esc_html($user->display_name ?: $user->user_login); ?></span>
                <button type="button" class="vcp-auth-logout">Logout</button>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <button class="vcp-auth-open"><?php echo esc_html($atts['label']); ?></button>
        <?php
        return ob_get_clean();
    }
}

/**
 * Variante minimalista del shortcode [vcp_auth].
 * Muestra solo el botón "Ingresa" y no muestra nada si el usuario está logueado.
 */
final class VCP_Auth_Button_Shortcode {
    public static function init() {
        add_shortcode('vcp_auth_button', [__CLASS__, 'render']);
    }

    public static function render($atts = []) {
        // Si el usuario está logueado, no mostrar nada
        if (is_user_logged_in()) {
            return '';
        }

        // Atributos por defecto
        $atts = shortcode_atts([
            'label' => 'Ingresa'
        ], $atts, 'vcp_auth_button');

        ob_start(); ?>
        <button class="vcp-auth-open"><?php echo esc_html($atts['label']); ?></button>
        <?php
        return ob_get_clean();
    }
}
VCP_Auth_Button_Shortcode::init();

VCP_Auth_Shortcode::init();
