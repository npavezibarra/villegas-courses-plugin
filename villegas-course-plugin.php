<?php
/**
 * Plugin Name: Villegas Course Plugin
 * Description: Custom functionality for Villegas courses.
 * Version: 1.0.0
 * Author: Villegas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-vcp-auth-shortcode.php';
require_once __DIR__ . '/includes/vcp-auth-ajax.php';

add_action('plugins_loaded', function () {
    if (!defined('VCP_RECAPTCHA_SITE_KEY')) {
        define('VCP_RECAPTCHA_SITE_KEY', (string) get_option('vcp_recaptcha_site_key', ''));
    }

    if (!defined('VCP_RECAPTCHA_SECRET_KEY')) {
        define('VCP_RECAPTCHA_SECRET_KEY', (string) get_option('vcp_recaptcha_secret_key', ''));
    }

    if (!defined('VCP_GOOGLE_CLIENT_ID')) {
        define('VCP_GOOGLE_CLIENT_ID', (string) get_option('vcp_google_client_id', ''));
    }

    if (!defined('VCP_GOOGLE_CLIENT_SECRET')) {
        define('VCP_GOOGLE_CLIENT_SECRET', (string) get_option('vcp_google_client_secret', ''));
    }

    if (!defined('VCP_GOOGLE_REDIRECT_URI')) {
        define('VCP_GOOGLE_REDIRECT_URI', home_url('/?vcp_auth=google'));
    }
});

add_action(
    'init',
    function () {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (shortcode_exists('vcp_auth')) {
                error_log('[vcp_auth] shortcode loaded successfully');
            } else {
                error_log('[vcp_auth] shortcode not loaded');
            }
        }

        if (!has_filter('the_content', 'do_shortcode')) {
            add_filter('the_content', 'do_shortcode', 11);
        }
    },
    5
);

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }

    wp_enqueue_style(
        'vcp-auth-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'vcp-auth-css',
        plugin_dir_url(__FILE__) . 'assets/css/vcp-auth.css',
        [],
        '1.3'
    );

    wp_enqueue_script(
        'vcp-auth-js',
        plugin_dir_url(__FILE__) . 'assets/js/vcp-auth.js',
        ['jquery'],
        '1.3',
        true
    );

    $recaptcha_site_key = (string) get_option('vcp_recaptcha_site_key', '');
    $google_client_id   = (string) get_option('vcp_google_client_id', '');

    if ($recaptcha_site_key) {
        wp_enqueue_script(
            'google-recaptcha',
            'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($recaptcha_site_key),
            [],
            null,
            true
        );
    }

    wp_localize_script('vcp-auth-js', 'VCP_AUTH', [
        'ajax'           => admin_url('admin-ajax.php'),
        'nonce'          => wp_create_nonce('vcp_auth_nonce'),
        'recaptcha_key'  => $recaptcha_site_key,
        'google_id'      => $google_client_id,
        'google_url'     => VCP_GOOGLE_REDIRECT_URI,
        'isUser'         => is_user_logged_in(),
        'isLoggedIn'     => is_user_logged_in(),
        'logoutRedirect' => home_url(),
    ]);
});

add_action('init', function () {
    if (isset($_GET['vcp_auth']) && $_GET['vcp_auth'] === 'google') {
        vcp_auth_handle_google();
        exit;
    }
});

if (!function_exists('vcp_auth_handle_google')) {
    function vcp_auth_handle_google() {
        if (empty(VCP_GOOGLE_CLIENT_ID) || empty(VCP_GOOGLE_CLIENT_SECRET) || empty(VCP_GOOGLE_REDIRECT_URI)) {
            wp_die(__('Google OAuth is not configured.', 'villegas-course-plugin'));
        }

        if (isset($_GET['code'])) {
            $code = sanitize_text_field(wp_unslash($_GET['code']));

            $token_response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'body' => [
                    'code'          => $code,
                    'client_id'     => VCP_GOOGLE_CLIENT_ID,
                    'client_secret' => VCP_GOOGLE_CLIENT_SECRET,
                    'redirect_uri'  => VCP_GOOGLE_REDIRECT_URI,
                    'grant_type'    => 'authorization_code',
                ],
            ]);

            if (is_wp_error($token_response)) {
                wp_die(__('Unable to contact Google for authentication.', 'villegas-course-plugin'));
            }

            $token_body = json_decode(wp_remote_retrieve_body($token_response), true);

            if (empty($token_body['access_token'])) {
                wp_die(__('Google authentication failed.', 'villegas-course-plugin'));
            }

            $user_info = wp_remote_get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . sanitize_text_field($token_body['access_token']),
                ],
            ]);

            if (is_wp_error($user_info)) {
                wp_die(__('Failed to fetch Google user info.', 'villegas-course-plugin'));
            }

            $info = json_decode(wp_remote_retrieve_body($user_info), true);

            if (empty($info['email'])) {
                wp_die(__('Failed to fetch Google user info.', 'villegas-course-plugin'));
            }

            $email = sanitize_email($info['email']);
            if (!is_email($email)) {
                wp_die(__('Invalid email address received from Google.', 'villegas-course-plugin'));
            }

            $user = get_user_by('email', $email);

            if (!$user) {
                $login = sanitize_user(current(explode('@', $email)), true);
                if (empty($login)) {
                    $login = 'google_user';
                }

                $base_login = $login;
                $i          = 1;
                while (username_exists($login)) {
                    $login = $base_login . $i;
                    $i++;
                }

                $password = wp_generate_password(20);
                $user_id  = wp_create_user($login, $password, $email);

                if (is_wp_error($user_id)) {
                    wp_die(__('Failed to create user.', 'villegas-course-plugin'));
                }

                if (!empty($info['name'])) {
                    wp_update_user([
                        'ID'           => $user_id,
                        'display_name' => sanitize_text_field($info['name']),
                    ]);
                }

                if (!empty($info['id'])) {
                    update_user_meta($user_id, 'vcp_google_id', sanitize_text_field($info['id']));
                }

                $user = get_user_by('id', $user_id);
            }

            if (!$user) {
                wp_die(__('Unable to locate user for Google authentication.', 'villegas-course-plugin'));
            }

            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            do_action('wp_login', $user->user_login, $user);

            $redirect = wp_get_referer();
            if (!$redirect || strpos($redirect, home_url()) !== 0) {
                $redirect = home_url();
            }

            wp_safe_redirect($redirect);
            exit;
        }

        $params = [
            'client_id'     => VCP_GOOGLE_CLIENT_ID,
            'redirect_uri'  => VCP_GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];

        wp_redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        exit;
    }
}

add_action('wp_footer', function () {
    if (is_user_logged_in()) {
        return;
    }

    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    $nonce = wp_create_nonce('vcp_auth_nonce');
    ?>
    <div class="vcp-auth-overlay" hidden></div>
    <div class="vcp-auth-modal" hidden role="dialog" aria-modal="true" aria-labelledby="vcp-auth-title">
        <button class="vcp-auth-close" aria-label="<?php echo esc_attr__('Cerrar', 'villegas-course-plugin'); ?>">×</button>

        <div class="vcp-auth-panels">
            <div class="vcp-auth-tabs">
                <button class="vcp-auth-tab is-active" data-target="#vcp-login">Iniciar sesión</button>
                <button class="vcp-auth-tab" data-target="#vcp-register">Crear cuenta</button>
            </div>

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
});

if (!function_exists('vcp_add_google_login_submenu')) {
    function vcp_add_google_login_submenu() {
        add_submenu_page(
            'villegas-lms',
            __('Google Login Settings', 'villegas-course-plugin'),
            __('Google Login', 'villegas-course-plugin'),
            'manage_options',
            'villegaslms-google-login',
            'vcp_google_login_settings_page'
        );
    }
}

add_action('admin_menu', 'vcp_add_google_login_submenu', 20);

if (!function_exists('vcp_google_login_settings_page')) {
    function vcp_google_login_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['vcp_save_google_settings'])) {
            check_admin_referer('vcp_google_settings_save');

            update_option('vcp_recaptcha_site_key', sanitize_text_field(wp_unslash($_POST['vcp_recaptcha_site_key'] ?? '')));
            update_option('vcp_recaptcha_secret_key', sanitize_text_field(wp_unslash($_POST['vcp_recaptcha_secret_key'] ?? '')));
            update_option('vcp_google_client_id', sanitize_text_field(wp_unslash($_POST['vcp_google_client_id'] ?? '')));
            update_option('vcp_google_client_secret', sanitize_text_field(wp_unslash($_POST['vcp_google_client_secret'] ?? '')));

            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'villegas-course-plugin') . '</p></div>';
        }

        $site_key      = get_option('vcp_recaptcha_site_key', '');
        $secret_key    = get_option('vcp_recaptcha_secret_key', '');
        $client_id     = get_option('vcp_google_client_id', '');
        $client_secret = get_option('vcp_google_client_secret', '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Google Login & reCAPTCHA Settings', 'villegas-course-plugin'); ?></h1>
            <p><?php esc_html_e('Enter the keys obtained from your Google Cloud Console. These are used for reCAPTCHA and OAuth login.', 'villegas-course-plugin'); ?></p>

            <form method="post">
                <?php wp_nonce_field('vcp_google_settings_save'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="vcp_recaptcha_site_key"><?php esc_html_e('reCAPTCHA Site Key', 'villegas-course-plugin'); ?></label></th>
                        <td><input name="vcp_recaptcha_site_key" id="vcp_recaptcha_site_key" type="text" value="<?php echo esc_attr($site_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vcp_recaptcha_secret_key"><?php esc_html_e('reCAPTCHA Secret Key', 'villegas-course-plugin'); ?></label></th>
                        <td><input name="vcp_recaptcha_secret_key" id="vcp_recaptcha_secret_key" type="text" value="<?php echo esc_attr($secret_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vcp_google_client_id"><?php esc_html_e('Google Client ID', 'villegas-course-plugin'); ?></label></th>
                        <td><input name="vcp_google_client_id" id="vcp_google_client_id" type="text" value="<?php echo esc_attr($client_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vcp_google_client_secret"><?php esc_html_e('Google Client Secret', 'villegas-course-plugin'); ?></label></th>
                        <td><input name="vcp_google_client_secret" id="vcp_google_client_secret" type="text" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="vcp_save_google_settings" class="button-primary"><?php esc_html_e('Save Settings', 'villegas-course-plugin'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }
}
