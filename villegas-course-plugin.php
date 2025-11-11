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

add_action('wp_enqueue_scripts', function () {
    if (!is_singular() || !isset($GLOBALS['post'])) {
        return;
    }

    if (has_shortcode($GLOBALS['post']->post_content, 'vcp_auth')) {
        wp_enqueue_style('vcp-auth-css', plugin_dir_url(__FILE__) . 'assets/css/vcp-auth.css', [], '1.1');
        wp_enqueue_script('vcp-auth-js', plugin_dir_url(__FILE__) . 'assets/js/vcp-auth.js', ['jquery'], '1.1', true);

        // Optional: comment this line until you add your real site key
        // wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY', [], null, true);

        wp_localize_script('vcp-auth-js', 'VCP_AUTH', [
            'ajax'        => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('vcp_auth_nonce'),
            'captcha_key' => 'YOUR_SITE_KEY',
            'google_url'  => home_url('/?vcp_auth=google'),
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
        if (!defined('VCP_GOOGLE_CLIENT_ID') || !defined('VCP_GOOGLE_CLIENT_SECRET') || !defined('VCP_GOOGLE_REDIRECT_URI')) {
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
