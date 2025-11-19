<?php
/**
 * Module: Villegas Course Plugin bootstrap
 * Description: Authentication UI, Google OAuth, and admin helpers for Villegas courses.
 * Note: Loaded by the main plugin entry point (my-ld-course-override.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-vcp-auth-shortcode.php';
require_once __DIR__ . '/includes/vcp-auth-ajax.php';

function vcp_register_new_users_submenu() {
    add_submenu_page(
        'villegas-lms',
        __('New Users', 'villegas-course-plugin'),
        __('New Users', 'villegas-course-plugin'),
        'manage_options',
        'villegaslms-new-users',
        'vcp_render_new_users_page'
    );
}
add_action('admin_menu', 'vcp_register_new_users_submenu', 20);

function vcp_render_new_users_page() {
    $since = strtotime('-7 days');
    $args  = [
        'orderby'    => 'registered',
        'order'      => 'DESC',
        'meta_query' => [],
        'date_query' => [
            [
                'after' => date('Y-m-d H:i:s', $since),
            ],
        ],
        'fields' => ['ID', 'user_login', 'user_email', 'user_registered'],
    ];

    $users = get_users($args);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('New Users (Last 7 Days)', 'villegas-course-plugin'); ?></h1>
        <?php if (empty($users)) : ?>
            <p><?php esc_html_e('No new users found in the last week.', 'villegas-course-plugin'); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Username', 'villegas-course-plugin'); ?></th>
                        <th><?php esc_html_e('Email', 'villegas-course-plugin'); ?></th>
                        <th><?php esc_html_e('Registered', 'villegas-course-plugin'); ?></th>
                        <th><?php esc_html_e('Actions', 'villegas-course-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr id="user-<?php echo esc_attr($user->ID); ?>">
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html($user->user_registered); ?></td>
                            <td>
                                <button
                                    class="button delete-user-btn"
                                    data-user="<?php echo esc_attr($user->ID); ?>"
                                    data-nonce="<?php echo esc_attr(wp_create_nonce('vcp_delete_user_' . $user->ID)); ?>"
                                >
                                    <?php esc_html_e('Delete', 'villegas-course-plugin'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

add_action('wp_ajax_vcp_delete_user', function () {
    if (!current_user_can('delete_users')) {
        wp_send_json_error(['message' => __('No permission', 'villegas-course-plugin')]);
    }

    $user_id = isset($_POST['user_id']) ? (int) wp_unslash($_POST['user_id']) : 0;
    $nonce   = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

    if (!$user_id || !wp_verify_nonce($nonce, 'vcp_delete_user_' . $user_id)) {
        wp_send_json_error(['message' => __('Invalid request', 'villegas-course-plugin')]);
    }

    if (get_current_user_id() === $user_id) {
        wp_send_json_error(['message' => __('You cannot delete yourself', 'villegas-course-plugin')]);
    }

    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($user_id);

    wp_send_json_success(['message' => __('User deleted', 'villegas-course-plugin')]);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'villegas-lms_page_villegaslms-new-users') {
        return;
    }

    wp_enqueue_script(
        'vcp-new-users',
        plugin_dir_url(__FILE__) . 'assets/js/vcp-new-users.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('vcp-new-users', 'VCP_USERS', [
        'ajax' => admin_url('admin-ajax.php'),
    ]);

    wp_register_style('vcp-new-users-admin', false);
    wp_enqueue_style('vcp-new-users-admin');
    wp_add_inline_style('vcp-new-users-admin', '.delete-user-btn{background:#cc0000!important;color:#fff!important;}.delete-user-btn:hover{background:#a30000!important;}');
});

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
        '1.4',
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
}, 99);

function vcp_render_learndash_newsreader_typography() {
    if (is_admin()) {
        return;
    }

    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap" rel="stylesheet">
    <style>
      /* Scope: ALL LearnDash tab panels (no post-specific IDs) */
      .ld-tabs-content [role="tabpanel"] {
        /* Local variables for this container only */
        --p-size: 22px;
        --h1-scale: 2.6;
        --h2-scale: 2.0;
        --h3-scale: 1.6;
        --h4-scale: 1.35;
      }

      /* Apply Newsreader only inside panels */
      .ld-tabs-content [role="tabpanel"] h1,
      .ld-tabs-content [role="tabpanel"] h2,
      .ld-tabs-content [role="tabpanel"] h3,
      .ld-tabs-content [role="tabpanel"] h4,
      .ld-tabs-content [role="tabpanel"] p {
        font-family: "Newsreader", serif;
        font-optical-sizing: auto;
        font-style: normal;
        margin: 0 0 .65em 0;
      }

      .ld-tabs-content [role="tabpanel"] h1,
      .ld-tabs-content [role="tabpanel"] h2,
      .ld-tabs-content [role="tabpanel"] h3,
      .ld-tabs-content [role="tabpanel"] h4 { text-align: left !important; }

      .ld-tabs-content [role="tabpanel"] h1 { font-weight: 800; font-size: calc(var(--p-size) * var(--h1-scale)); line-height: 1.1; }
      .ld-tabs-content [role="tabpanel"] h2 { font-weight: 700; font-size: calc(var(--p-size) * var(--h2-scale)); line-height: 1.2; }
      .ld-tabs-content [role="tabpanel"] h3 { font-weight: 600; font-size: calc(var(--p-size) * var(--h3-scale)); line-height: 1.25; }
      .ld-tabs-content [role="tabpanel"] h4 { font-weight: 500; font-size: calc(var(--p-size) * var(--h4-scale)); line-height: 1.3; }
      .ld-tabs-content [role="tabpanel"] p  { font-weight: 400; font-size: 22px !important; line-height: 1.5 !important; }
    </style>
    <?php
}
add_action('wp_head', 'vcp_render_learndash_newsreader_typography');

add_action('init', function () {
    if (isset($_GET['vcp_auth']) && $_GET['vcp_auth'] === 'google') {
        vcp_auth_handle_google();
        exit;
    }
});

/**
 * Load the custom author template that lives inside this plugin.
 */
add_filter('template_include', function ($template) {
    if (is_author()) {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/author.php';

        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }

    return $template;
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

add_action('wp_print_footer_scripts', 'vcp_render_auth_modal', 99);

function vcp_render_auth_modal() {
    if (is_user_logged_in()) {
        return;
    }

    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;

    $nonce = wp_create_nonce('vcp_auth_nonce');
    ?>
    <div id="vcp-auth-overlay" class="vcp-auth-overlay" hidden></div>
    <div
        id="vcp-auth-modal"
        class="vcp-auth-modal"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="vcp-auth-title"
    >
        <button class="vcp-auth-close" aria-label="<?php echo esc_attr__('Cerrar', 'villegas-course-plugin'); ?>">×</button>

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
                <div class="vcp-captcha" data-type="login"></div>
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
                <div class="vcp-captcha" data-type="register"></div>
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
}

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
