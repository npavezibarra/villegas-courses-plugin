<?php
if (!defined('ABSPATH')) {
    exit;
}

// LOGIN
add_action('wp_ajax_nopriv_vcp_auth_login', 'vcp_auth_login');
function vcp_auth_login() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'vcp_auth_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    $captcha = isset($_POST['captcha_token']) ? sanitize_text_field(wp_unslash($_POST['captcha_token'])) : '';
    if ($captcha) {
        $secret_key = defined('VCP_RECAPTCHA_SECRET_KEY') ? VCP_RECAPTCHA_SECRET_KEY : '';

        if (empty($secret_key)) {
            wp_send_json_error('Captcha validation failed.');
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $secret_key,
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

    $login = isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '';
    $pass  = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '';

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

    /**
     * Fires when a user logs in through the VCP auth modal.
     *
     * @param \WP_User $user Authenticated user object.
     */
    do_action('vcp_user_logged_in', $user);

    wp_send_json_success(true);
}

// REGISTER
add_action('wp_ajax_nopriv_vcp_auth_register', 'vcp_auth_register');
function vcp_auth_register() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'vcp_auth_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    $captcha = isset($_POST['captcha_token']) ? sanitize_text_field(wp_unslash($_POST['captcha_token'])) : '';
    if ($captcha) {
        $secret_key = defined('VCP_RECAPTCHA_SECRET_KEY') ? VCP_RECAPTCHA_SECRET_KEY : '';

        if (empty($secret_key)) {
            wp_send_json_error('Captcha validation failed.');
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $secret_key,
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

    $email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';
    $login = isset($_POST['user_login']) ? sanitize_user(wp_unslash($_POST['user_login'])) : '';
    $pass  = isset($_POST['user_pass']) ? (string) wp_unslash($_POST['user_pass']) : '';

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

    $user = get_userdata($user_id);
    do_action('wp_login', $login, $user);

    /**
     * Fires after a new user registers through the VCP auth modal.
     *
     * @param int $user_id Newly created user ID.
     */
    do_action('vcp_user_registered', $user_id);

    if ($user instanceof WP_User) {
        do_action('vcp_user_logged_in', $user);
    }

    wp_send_json_success(true);
}

add_action('wp_ajax_vcp_auth_logout', function () {
    $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';

    if (!wp_verify_nonce($nonce, 'vcp_auth_nonce')) {
        wp_logout();

        wp_send_json_success(['message' => 'Logged out (no nonce check)']);
    }

    wp_logout();

    wp_send_json_success(['message' => 'Logged out']);
});

add_action('wp_ajax_nopriv_vcp_auth_logout', function () {
    wp_send_json_error(['message' => 'Not logged in']);
});
