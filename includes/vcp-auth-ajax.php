<?php
if (!defined('ABSPATH')) {
    exit;
}

// LOGIN
add_action('wp_ajax_nopriv_vcp_auth_login', 'vcp_auth_login');
function vcp_auth_login() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vcp_auth_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    $captcha = sanitize_text_field($_POST['captcha_token'] ?? '');
    if ($captcha) {
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => 'YOUR_SECRET_KEY',
                'response' => $captcha,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Captcha validation failed.');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['success'])) {
            wp_send_json_error('Captcha validation failed.');
        }
    }

    $login = sanitize_text_field($_POST['log'] ?? '');
    $pass  = $_POST['pwd'] ?? '';

    if (empty($login) || empty($pass)) {
        wp_send_json_error('Username and password required.');
    }

    $creds = [
        'user_login'    => $login,
        'user_password' => $pass,
        'remember'      => true,
    ];

    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        wp_send_json_error($user->get_error_message());
    }

    wp_send_json_success(true);
}

// REGISTER
add_action('wp_ajax_nopriv_vcp_auth_register', 'vcp_auth_register');
function vcp_auth_register() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vcp_auth_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    $captcha = sanitize_text_field($_POST['captcha_token'] ?? '');
    if ($captcha) {
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => 'YOUR_SECRET_KEY',
                'response' => $captcha,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Captcha validation failed.');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['success'])) {
            wp_send_json_error('Captcha validation failed.');
        }
    }

    $email = sanitize_email($_POST['user_email'] ?? '');
    $login = sanitize_user($_POST['user_login'] ?? '');
    $pass  = $_POST['user_pass'] ?? '';

    if (!is_email($email)) {
        wp_send_json_error('Invalid email.');
    }
    if (empty($login)) {
        wp_send_json_error('Username required.');
    }
    if (empty($pass)) {
        wp_send_json_error('Password required.');
    }
    if (username_exists($login)) {
        wp_send_json_error('Username already taken.');
    }
    if (email_exists($email)) {
        wp_send_json_error('Email already registered.');
    }

    $user_id = wp_create_user($login, $pass, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
    do_action('wp_login', $login, get_userdata($user_id));

    wp_send_json_success(true);
}

add_action('wp_ajax_vcp_auth_logout', 'vcp_auth_logout');
function vcp_auth_logout() {
    check_ajax_referer('vcp_auth_nonce', 'nonce');

    wp_logout();

    wp_send_json_success(true);
}
