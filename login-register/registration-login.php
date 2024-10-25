<?php
// registration-login.php
function villegas_registration_login_shortcode() {
    // Encolar el archivo CSS
    wp_enqueue_style('ingresa-roma-css', plugins_url('assets/ingresa-roma.css', __FILE__));
    
    // Encolar el archivo JavaScript
    wp_enqueue_script('ingresa-roma-js', plugins_url('assets/ingresa-roma.js', __FILE__), array('jquery'), null, true);
    
    ob_start();

    // Comprobamos si el usuario está conectado
    if (is_user_logged_in()) {
        // Redirigir al curso si el usuario ya ha iniciado sesión
        wp_redirect(home_url('/la-republica-romana')); // Cambia esto a la URL de tu curso
        exit;
    }

    // Manejo del registro
    if ($_POST['action'] == 'register') {
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $email = sanitize_email($_POST['email']);
        
        // Registro de usuario
        $userdata = array(
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'role' => 'subscriber' // o el rol que necesites
        );
        $user_id = wp_insert_user($userdata);
        if (!is_wp_error($user_id)) {
            echo '<p>Registro exitoso. Por favor inicia sesión.</p>';
            
            // Iniciar sesión automáticamente
            $creds = array();
            $creds['user_login'] = $username;
            $creds['user_password'] = $password;
            $creds['remember'] = true;

            $user = wp_signon($creds, false); // Iniciar sesión
            if (is_wp_error($user)) {
                echo '<p>Error al iniciar sesión: ' . $user->get_error_message() . '</p>';
            } else {
                // Redirigir al curso después del login
                wp_redirect(home_url('/la-republica-romana'));
                exit;
            }
        } else {
            echo '<p>Error: ' . $user_id->get_error_message() . '</p>';
        }
    }

    // Manejo del login
    if ($_POST['action'] == 'login') {
        $creds = array();
        $creds['user_login'] = $_POST['username'];
        $creds['user_password'] = $_POST['password'];
        $creds['remember'] = true;

        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            echo '<p>Error: ' . $user->get_error_message() . '</p>';
        } else {
            // Redirigir al curso después del login
            wp_redirect(home_url('/la-republica-romana'));
            exit;
        }
    }
    ?>
    
    <form method="POST" id="registration-form">
        <h2>Registro</h2>
        <p>Si ya tienes cuenta haz login aquí</p>
        <input type="text" name="username" placeholder="Nombre de usuario" required id="username">
        <input type="email" name="email" placeholder="Correo electrónico" required id="email">
        <input type="password" name="password" placeholder="Contraseña" required id="password">
        <input type="hidden" name="action" value="register">
        <input type="submit" value="Registrarse">
    </form>

    <form method="POST" id="login-form">
        <h2>Iniciar Sesión</h2>
        <p>Si no tienes cuenta regístrate aquí</p>
        <input type="text" name="username" placeholder="Nombre de usuario" required id="login-username">
        <input type="password" name="password" placeholder="Contraseña" required id="login-password">
        <input type="hidden" name="action" value="login">
        <input type="submit" value="Iniciar Sesión">
        <p><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Olvidé la contraseña</a></p> <!-- Enlace de Olvidé la contraseña -->
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode('villegas_registration_login', 'villegas_registration_login_shortcode');
