<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send confirmation email to the user.
 *
 * @param int    $user_id User ID.
 * @param string $token   Confirmation token.
 * @return bool True on success, false on failure.
 */
function vcp_send_confirmation_email($user_id, $token)
{
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $confirmation_link = home_url('/confirmar-cuenta/' . $token);
    $subject = 'Confirma tu cuenta en El Villegas';
    $logo_url = get_option('vcp_email_logo');
    if (empty($logo_url)) {
        $logo_url = 'http://devvillegas.local/wp-content/uploads/2025/11/villegaslogowhite2.png';
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bienvenido a El Villegas</title>
        <style>
            /* Reset de estilos básicos para clientes de correo */
            body,
            table,
            td,
            a {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }

            table,
            td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }

            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }

            table {
                border-collapse: collapse !important;
            }

            body {
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                background-color: #f4f6f8;
                color: #333333;
            }

            /* Estilos del contenedor principal */
            .email-wrapper {
                width: 100%;
                background-color: #f4f6f8;
                padding: 40px 0;
            }

            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            }

            /* Cabecera */
            .header {
                background-color: #000000;
                /* Color oscuro elegante */
                padding: 30px 20px;
                text-align: center;
            }

            .header img {
                max-width: 200px;
            }

            /* Cuerpo del mensaje */
            .body-content {
                padding: 40px 30px;
                text-align: center;
            }

            .welcome-icon {
                font-size: 48px;
                margin-bottom: 20px;
                display: block;
            }

            .headline {
                font-size: 22px;
                font-weight: 600;
                margin-bottom: 15px;
                color: #1a202c;
            }

            .text {
                font-size: 16px;
                line-height: 1.6;
                color: #555555;
                margin-bottom: 30px;
            }

            /* Botón de acción */
            .btn-container {
                margin: 30px 0;
            }

            .btn {
                background-color: #d32f2f;
                /* Un rojo elegante o el color de marca */
                color: #ffffff !important;
                padding: 14px 30px;
                font-size: 16px;
                font-weight: bold;
                text-decoration: none;
                border-radius: 6px;
                display: inline-block;
                transition: background-color 0.3s ease;
                box-shadow: 0 4px 6px rgba(211, 47, 47, 0.2);
            }

            .btn:hover {
                background-color: #b71c1c;
            }

            /* Footer */
            .footer {
                background-color: #f9fafb;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #eeeeee;
            }

            .footer-text {
                font-size: 12px;
                color: #888888;
                line-height: 1.5;
            }

            .footer-link {
                color: #888888;
                text-decoration: underline;
            }

            /* Media Queries para móviles */
            @media screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                    border-radius: 0 !important;
                }

                .body-content {
                    padding: 30px 20px !important;
                }
            }
        </style>
    </head>

    <body>

        <div class="email-wrapper">
            <!-- Inicio del Contenedor del Email -->
            <table class="email-container" role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">

                <!-- Cabecera -->
                <tr>
                    <td class="header">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="El Villegas">
                    </td>
                </tr>

                <!-- Contenido Principal -->
                <tr>
                    <td class="body-content">
                        <span class="welcome-icon">👋</span>
                        <h2 class="headline">¡Te damos la bienvenida!</h2>

                        <p class="text">
                            Hola, acabas de dar el primer paso para unirte a nuestra comunidad en
                            <strong>elvillegas.cl</strong>. Estamos muy contentos de que hayas decidido sumarte.
                        </p>

                        <p class="text">
                            Para asegurarnos de que eres tú y garantizarte la mejor experiencia en nuestra plataforma,
                            necesitamos confirmar esta dirección de correo electrónico. Es solo un clic.
                        </p>

                        <div class="btn-container">
                            <a href="<?php echo esc_url($confirmation_link); ?>" target="_blank" class="btn">Confirmar mi
                                cuenta</a>
                        </div>

                        <p class="text" style="font-size: 14px; margin-top: 30px; color: #888;">
                            Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:<br>
                            <a href="<?php echo esc_url($confirmation_link); ?>"
                                style="color: #d32f2f; word-break: break-all;"><?php echo esc_html($confirmation_link); ?></a>
                        </p>
                    </td>
                </tr>

                <!-- Pie de página -->
                <tr>
                    <td class="footer">
                        <p class="footer-text">
                            Si no creaste esta cuenta en elvillegas.cl, puedes ignorar este mensaje con tranquilidad.
                            <br><br>
                            &copy; <?php echo date('Y'); ?> El Villegas. Todos los derechos reservados.<br>
                            Santiago, Chile.
                        </p>
                    </td>
                </tr>

            </table>
            <!-- Fin del Contenedor del Email -->
        </div>

    </body>

    </html>
    <?php
    $message = ob_get_clean();

    return wp_mail($user->user_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
}

/**
 * Add rewrite rule for confirmation URL.
 */
function vcp_handle_confirmation_rewrite()
{
    add_rewrite_rule('^confirmar-cuenta/([^/]+)/?$', 'index.php?vcp_confirm_token=$matches[1]', 'top');
}
add_action('init', 'vcp_handle_confirmation_rewrite');

/**
 * Register query var.
 */
function vcp_register_confirmation_query_var($vars)
{
    $vars[] = 'vcp_confirm_token';
    return $vars;
}
add_filter('query_vars', 'vcp_register_confirmation_query_var');

/**
 * Process confirmation token.
 */
function vcp_process_confirmation()
{
    $token = get_query_var('vcp_confirm_token');
    if (!$token) {
        return;
    }

    $args = [
        'meta_key' => 'vcp_confirmation_token',
        'meta_value' => $token,
        'number' => 1,
        'count_total' => false
    ];
    $users = get_users($args);

    if (empty($users)) {
        wp_redirect(home_url('/?vcp_confirmed=error'));
        exit;
    }

    $user = $users[0];

    // Activate user
    delete_user_meta($user->ID, 'vcp_confirmation_token');
    update_user_meta($user->ID, 'vcp_account_status', 'active');

    // Auto login after confirmation (optional, but good UX)
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);

    wp_redirect(home_url('/mi-cuenta?vcp_confirmed=success'));
    exit;
}
add_action('template_redirect', 'vcp_process_confirmation');
