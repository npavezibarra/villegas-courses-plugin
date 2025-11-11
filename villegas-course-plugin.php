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
    if (!is_singular() || !isset($GLOBALS['post'])) {
        return;
    }

    if (has_shortcode($GLOBALS['post']->post_content, 'vcp_auth')) {
        wp_enqueue_style('vcp-auth-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap', [], null);
        wp_enqueue_style('vcp-auth-css', plugin_dir_url(__FILE__) . 'assets/css/vcp-auth.css', [], '1.1');
        wp_enqueue_script('vcp-auth-js', plugin_dir_url(__FILE__) . 'assets/js/vcp-auth.js', ['jquery'], '1.1', true);

        $recaptcha_site_key = (string) get_option('vcp_recaptcha_site_key', '');
        $google_client_id   = (string) get_option('vcp_google_client_id', '');

        if ($recaptcha_site_key) {
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($recaptcha_site_key), [], null, true);
        }

        wp_localize_script('vcp-auth-js', 'VCP_AUTH', [
            'ajax'        => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('vcp_auth_nonce'),
            'recaptcha_key' => $recaptcha_site_key,
            'google_id'  => $google_client_id,
            'google_url' => VCP_GOOGLE_REDIRECT_URI,
            'isUser'      => is_user_logged_in(),
        ]);
    }
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

add_action('admin_menu', function () {
    add_submenu_page(
        'villegaslms',
        __('Google Login Settings', 'villegas-course-plugin'),
        __('Google Login', 'villegas-course-plugin'),
        'manage_options',
        'villegaslms-google-login',
        'vcp_google_login_settings_page'
    );
});

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
