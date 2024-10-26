<?php
// registration-login.php
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
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
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

    <?php
    return ob_get_clean();
}
add_shortcode('villegas_registration_login', 'villegas_registration_login_shortcode');
