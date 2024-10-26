<?php
function villegas_registration_login_shortcode() {
    // Encolar el archivo CSS
    wp_enqueue_style('ingresa-roma-css', plugins_url('assets/ingresa-roma.css', __FILE__));
    
    // Encolar el archivo JavaScript
    wp_enqueue_script('form-toggle-js', plugins_url('login-register/form-toggle.js', __FILE__), array('jquery'), null, true);
    
    ob_start();

    // Comprobamos si el usuario está conectado
    if (is_user_logged_in()) {
        // Redirigir al curso si el usuario ya ha iniciado sesión
        wp_redirect(home_url('/la-republica-romana')); // Cambia esto a la URL de tu curso
        exit;
    }

    // Variable para verificar si el registro fue exitoso
    $registration_successful = false;

    // Manejo del registro
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $email = sanitize_email($_POST['email']);
        
        // Registro de usuario
        $userdata = array(
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'role' => 'pending' // Cambia a 'pending' para los no confirmados
        );
        $user_id = wp_insert_user($userdata);
        if (!is_wp_error($user_id)) {
            // Generar un código de confirmación
            $confirmation_code = wp_generate_password(20, false);
            update_user_meta($user_id, 'confirmation_code', $confirmation_code);
        
            // Enviar el correo de confirmación
            $to = $email;
            $subject = 'Confirma tu cuenta';
            $message = 'Por favor, confirma tu cuenta haciendo clic en el siguiente enlace: ' . 
                       home_url('/?confirm=' . $confirmation_code . '&user=' . $user_id);
            wp_mail($to, $subject, $message);
            
            // Iniciar sesión automáticamente
            $creds = array();
            $creds['user_login'] = $username;
            $creds['user_password'] = $password;
            $creds['remember'] = true;

            $user = wp_signon($creds, false);
            if (!is_wp_error($user)) {
                // Registro exitoso y usuario conectado
                $registration_successful = true; // Cambiar la bandera a verdadero
            }
        }
    }

    // Si el registro fue exitoso, solo mostrar el mensaje
    if ($registration_successful) {
        echo '<div id="mensaje-registro" style="text-align: center; padding: 80px 0px;">';
        echo '<h3>Registro exitoso.</h3>';
        echo '<p>Por favor revisa tu correo para confirmar tu cuenta.</p>';
        echo '<a href="' . esc_url(home_url('/la-republica-romana')) . '" class="button">Ir al curso</a>'; // Cambia esto a la URL de tu curso
        echo '</div>';
    } else {
        // Mostrar los formularios si no se registró exitosamente
        ?>
        <div id="ingresa-roma">
            <div id="form-container">
                <form method="POST" id="auth-form">
                    <h2 id="form-title">Iniciar Sesión</h2>
                    <p id="form-subtitle">Si no tienes cuenta, <a href="#" id="toggle-form">regístrate aquí</a></p>
                    
                    <input type="text" name="username" placeholder="Nombre de usuario" required id="username">
                    <input type="password" name="password" placeholder="Contraseña" required id="password">
                    <input type="hidden" name="action" value="login" id="form-action">
                    
                    <input type="submit" value="Iniciar Sesión">
                    <p id="forgot-pass"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Olvidé la contraseña</a></p>
                </form>

                <form method="POST" id="registration-form" style="display:none;">
                    <h2 id="form-title-register">Registro</h2>
                    <p id="form-subtitle-register">Si ya tienes cuenta, <a href="#" id="toggle-form-login">inicia sesión aquí</a></p>
                    <input type="text" name="username" placeholder="Nombre de usuario" required id="register-username">
                    <input type="email" name="email" placeholder="Correo electrónico" required id="register-email">
                    <input type="password" name="password" placeholder="Contraseña" required id="register-password">
                    <input type="hidden" name="action" value="register">
                    
                    <input type="submit" value="Registrarse">
                </form>
            </div>
        </div>
        <?php
    }

    return ob_get_clean();
}
add_shortcode('villegas_registration_login', 'villegas_registration_login_shortcode');
