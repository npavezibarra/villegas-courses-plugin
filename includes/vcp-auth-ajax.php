<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vcp_resend_confirmation', 'vcp_resend_confirmation_ajax');

function vcp_resend_confirmation_ajax()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para realizar esta acción.']);
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    if (!wp_verify_nonce($nonce, 'vcp_resend_nonce_' . $user_id)) {
        wp_send_json_error(['message' => 'Nonce inválido.']);
    }

    $user = get_userdata($user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'Usuario no encontrado.']);
    }

    // Generate a new token if needed, or retrieve existing one.
// For simplicity, we'll generate a new one to ensure it's fresh.
    $token = bin2hex(random_bytes(32));
    update_user_meta($user_id, 'vcp_confirmation_token', $token);

    if (vcp_send_confirmation_email($user_id, $token, true)) {
        update_user_meta($user_id, 'vcp_last_confirmation_sent', current_time('mysql'));
        wp_send_json_success(['message' => 'Correo de confirmación reenviado.']);
    } else {
        wp_send_json_error(['message' => 'Error al enviar el correo.']);
    }
}

// LOGIN
add_action('wp_ajax_nopriv_vcp_auth_login', 'vcp_auth_login');
function vcp_auth_login()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'vcp_auth_nonce')) {
        wp_send_json_error('Error de seguridad.');
    }

    $captcha = isset($_POST['captcha_token']) ? sanitize_text_field(wp_unslash($_POST['captcha_token'])) : '';
    if ($captcha) {
        $secret_key = defined('VCP_RECAPTCHA_SECRET_KEY') ? VCP_RECAPTCHA_SECRET_KEY : '';

        if (empty($secret_key)) {
            wp_send_json_error('Falló la validación del Captcha.');
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $secret_key,
                'response' => $captcha,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Falló la validación del Captcha.');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['success'])) {
            wp_send_json_error('Falló la validación del Captcha.');
        }
    }

    $login = isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '';
    $pass = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '';

    if (empty($login) || empty($pass)) {
        wp_send_json_error('Usuario y contraseña requeridos.');
    }

    $creds = [
        'user_login' => $login,
        'user_password' => $pass,
        'remember' => true,
    ];

    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        wp_send_json_error($user->get_error_message());
    }

    // Check if account is confirmed
    $status = get_user_meta($user->ID, 'vcp_account_status', true);
    if ($status === 'pending') {
        wp_logout();
        wp_send_json_error(['message' => 'Por favor confirma tu correo electrónico antes de iniciar sesión.']);
    }

    /**
     * Fires when a user logs in through the VCP auth modal.
     *
     * @param \WP_User $user Authenticated user object.
     */
    do_action('vcp_user_logged_in', $user);

    wp_send_json_success([
        'user_display_name' => $user->display_name ?: $user->user_login
    ]);
}

// REGISTER
add_action('wp_ajax_nopriv_vcp_auth_register', 'vcp_auth_register');
function vcp_auth_register()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'vcp_auth_nonce')) {
        wp_send_json_error('Error de seguridad.');
    }

    $captcha = isset($_POST['captcha_token']) ? sanitize_text_field(wp_unslash($_POST['captcha_token'])) : '';
    if ($captcha) {
        $secret_key = defined('VCP_RECAPTCHA_SECRET_KEY') ? VCP_RECAPTCHA_SECRET_KEY : '';

        if (empty($secret_key)) {
            wp_send_json_error('Falló la validación del Captcha.');
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $secret_key,
                'response' => $captcha,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Falló la validación del Captcha.');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['success'])) {
            wp_send_json_error('Falló la validación del Captcha.');
        }
    }

    $email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';
    $login = isset($_POST['user_login']) ? sanitize_user(wp_unslash($_POST['user_login'])) : '';
    $pass = isset($_POST['user_pass']) ? (string) wp_unslash($_POST['user_pass']) : '';

    if (!is_email($email)) {
        wp_send_json_error('Correo electrónico inválido.');
    }
    if (empty($login)) {
        wp_send_json_error('Nombre de usuario requerido.');
    }
    if (empty($pass)) {
        wp_send_json_error('Contraseña requerida.');
    }
    if (username_exists($login)) {
        wp_send_json_error('El nombre de usuario ya está en uso.');
    }
    if (email_exists($email)) {
        wp_send_json_error('El correo electrónico ya está registrado.');
    }

    $user_id = wp_create_user($login, $pass, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }

    // Set account as pending and generate token
    $token = wp_generate_password(32, false);
    update_user_meta($user_id, 'vcp_account_status', 'pending');
    update_user_meta($user_id, 'vcp_confirmation_token', $token);

    // Send confirmation email
    if (function_exists('vcp_send_confirmation_email')) {
        vcp_send_confirmation_email($user_id, $token);
    }

    /**
     * Fires after a new user registers through the VCP auth modal.
     *
     * @param int $user_id Newly created user ID.
     */
    do_action('vcp_user_registered', $user_id);

    wp_send_json_success(['confirmation_required' => true]);
}

add_action('wp_ajax_vcp_auth_logout', function () {
    $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';

    if (!wp_verify_nonce($nonce, 'vcp_auth_nonce')) {
        wp_logout();

        wp_send_json_success(['message' => 'Sesión cerrada (sin verificación de nonce)']);
    }

    wp_logout();

    wp_send_json_success(['message' => 'Sesión cerrada']);
});

add_action('wp_ajax_nopriv_vcp_auth_logout', function () {
    wp_send_json_error(['message' => 'No has iniciado sesión']);
});

/**
 * Validate if a username or email exists.
 */
$vcp_check_user_exists = function () {
    $input = isset($_POST['user_check']) ? sanitize_text_field(wp_unslash($_POST['user_check'])) : '';

    if ($input === '') {
        wp_send_json_error(['message' => 'Campo vacío']);
    }

    if (is_email($input)) {
        $user = get_user_by('email', $input);
    } else {
        $user = get_user_by('login', $input);
    }

    if ($user) {
        wp_send_json_success(['exists' => true]);
    }

    wp_send_json_error([
        'exists' => false,
        'message' => 'Este correo no está registrado',
    ]);
};

add_action('wp_ajax_nopriv_vcp_check_user_exists', $vcp_check_user_exists);
add_action('wp_ajax_vcp_check_user_exists', $vcp_check_user_exists);

/**
 * Handle password reset request.
 */
$vcp_reset_password = function () {
    $email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Correo no válido.']);
    }

    $user = get_user_by('email', $email);
    if (!$user) {
        wp_send_json_error(['message' => 'Este correo no está registrado.']);
    }

    $reset = retrieve_password($user->user_login);

    if ($reset instanceof WP_Error) {
        $message = $reset->get_error_message();
        wp_send_json_error(['message' => $message ? $message : 'No se pudo enviar el correo. Intenta más tarde.']);
    }

    if (!$reset) {
        wp_send_json_error(['message' => 'No se pudo enviar el correo. Intenta más tarde.']);
    }

    wp_send_json_success(['message' => 'Hemos enviado un enlace de restablecimiento de contraseña a tu correo.']);
};

add_action('wp_ajax_nopriv_vcp_reset_password', $vcp_reset_password);
add_action('wp_ajax_vcp_reset_password', $vcp_reset_password);