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

        $nonce = wp_create_nonce('vcp_auth_nonce');

        ob_start();
        ?>
        <button class="vcp-auth-open"><?php echo esc_html($atts['label']); ?></button>

        <div class="vcp-auth-overlay" hidden></div>
        <div class="vcp-auth-modal" hidden role="dialog" aria-modal="true" aria-labelledby="vcp-auth-title">
            <div class="vcp-auth-panels">
                <button class="vcp-auth-close" aria-label="Cerrar">×</button>

                <div class="vcp-auth-tabs">
                    <button class="vcp-auth-tab is-active" data-target="#vcp-login">Iniciar sesión</button>
                    <button class="vcp-auth-tab" data-target="#vcp-register">Crear cuenta</button>
                </div>

                <form id="vcp-login" class="vcp-auth-panel is-active" method="post" novalidate>
                    <h3 id="vcp-auth-title">Iniciar sesión</h3>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="action" value="vcp_auth_login">
                    <div class="vcp-field">
                        <label>Correo electrónico o nombre de usuario</label>
                        <input type="text" name="log" id="vcp-login-user" required>
                        <small class="vcp-login-error">Este correo no está registrado</small>
                    </div>
                    <div class="vcp-field">
                        <label>Contraseña</label>
                        <input type="password" name="pwd" required>
                    </div>
                    <div class="vcp-captcha" data-type="login"></div>
                    <div class="vcp-actions">
                        <button type="submit">Entrar</button>
                    </div>
                    <p class="vcp-forgot">
                        <a href="#" id="vcp-forgot-toggle">¿Olvidaste tu contraseña?</a>
                    </p>
                    <div class="vcp-auth-error" aria-live="polite"></div>
                </form>

                <form id="vcp-register" class="vcp-auth-panel" method="post" novalidate>
                    <h3>Crear cuenta</h3>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="action" value="vcp_auth_register">
                    <div class="vcp-field"><label>Correo electrónico</label><input type="email" name="user_email" required></div>
                    <div class="vcp-field"><label>Nombre de usuario</label><input type="text" name="user_login" required></div>
                    <div class="vcp-field"><label>Contraseña</label><input type="password" name="user_pass" minlength="6" required></div>
                    <div class="vcp-captcha" data-type="register"></div>
                    <div class="vcp-actions">
                        <button type="submit">Crear cuenta</button>
                    </div>
                    <div class="vcp-auth-error" aria-live="polite"></div>
                </form>

                <form id="vcp-reset" class="vcp-auth-panel" method="post" novalidate>
                    <h3>Recuperar contraseña</h3>
                    <p>Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="action" value="vcp_reset_password">
                    <div class="vcp-field">
                        <label>Correo electrónico</label>
                        <input type="email" name="user_email" required>
                    </div>
                    <div class="vcp-actions">
                        <button type="submit">Enviar enlace</button>
                    </div>
                    <p class="vcp-back">
                        <a href="#" id="vcp-back-to-login">← Volver al inicio de sesión</a>
                    </p>
                    <div class="vcp-auth-error" aria-live="polite"></div>
                </form>
            </div>
        </div>
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

        $nonce = wp_create_nonce('vcp_auth_nonce');

        ob_start(); ?>
        <button class="vcp-auth-open"><?php echo esc_html($atts['label']); ?></button>

        <!-- Reutilizamos el mismo modal y overlay del shortcode principal -->
        <div class="vcp-auth-overlay" hidden></div>
        <div class="vcp-auth-modal" hidden role="dialog" aria-modal="true" aria-labelledby="vcp-auth-title">
          <button class="vcp-auth-close" aria-label="Cerrar">×</button>

          <div class="vcp-auth-tabs">
            <button class="vcp-auth-tab is-active" data-target="#vcp-login">Iniciar sesión</button>
            <button class="vcp-auth-tab" data-target="#vcp-register">Crear cuenta</button>
          </div>

          <div class="vcp-auth-panels">
            <form id="vcp-login" class="vcp-auth-panel is-active" novalidate>
              <h3 id="vcp-auth-title">Iniciar sesión</h3>
              <div class="vcp-field">
                <label>Correo electrónico o nombre de usuario</label>
                <input type="text" name="log" id="vcp-login-user" required>
                <small class="vcp-login-error">Este correo no está registrado</small>
              </div>
              <div class="vcp-field">
                <label>Contraseña</label>
                <input type="password" name="pwd" required>
              </div>
              <div class="vcp-actions">
                <button type="submit">Entrar</button>
              </div>
              <p class="vcp-forgot">
                <a href="#" id="vcp-forgot-toggle">¿Olvidaste tu contraseña?</a>
              </p>
              <input type="hidden" name="action" value="vcp_auth_login">
              <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
              <div class="vcp-auth-error" aria-live="polite"></div>
            </form>

            <form id="vcp-register" class="vcp-auth-panel" novalidate>
              <h3>Crear cuenta</h3>
              <div class="vcp-field">
                <label>Correo electrónico</label>
                <input type="email" name="user_email" required>
              </div>
              <div class="vcp-field">
                <label>Nombre de usuario</label>
                <input type="text" name="user_login" required>
              </div>
              <div class="vcp-field">
                <label>Contraseña</label>
                <input type="password" name="user_pass" minlength="6" required>
              </div>
              <div class="vcp-actions">
                <button type="submit">Crear cuenta</button>
              </div>
              <input type="hidden" name="action" value="vcp_auth_register">
              <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
              <div class="vcp-auth-error" aria-live="polite"></div>
            </form>

            <form id="vcp-reset" class="vcp-auth-panel" novalidate>
              <h3>Recuperar contraseña</h3>
              <p>Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
              <div class="vcp-field">
                <label>Correo electrónico</label>
                <input type="email" name="user_email" required>
              </div>
              <div class="vcp-actions">
                <button type="submit">Enviar enlace</button>
              </div>
              <p class="vcp-back">
                <a href="#" id="vcp-back-to-login">← Volver al inicio de sesión</a>
              </p>
              <input type="hidden" name="action" value="vcp_reset_password">
              <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
              <div class="vcp-auth-error" aria-live="polite"></div>
            </form>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
VCP_Auth_Button_Shortcode::init();

VCP_Auth_Shortcode::init();
