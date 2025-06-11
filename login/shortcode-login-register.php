<?php

/**
 * Shortcode de Login y Registro
 * Uso: [villegas_login_register course_id="123"]
 */

// Función para generar el shortcode
function villegas_login_register_shortcode($atts) {
    // Obtener atributos del shortcode
    $atts = shortcode_atts(
        array(
            'course_id' => null, // ID del curso de LearnDash (opcional)
        ),
        $atts,
        'villegas_login_register'
    );

    // Inicializar mensaje de error y éxito
    global $error_message, $success_message;
    $show_register_form = isset($error_message);

    // Si el usuario ya está logueado, redirigir al curso si se proporciona un ID
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (!empty($atts['course_id']) && is_numeric($atts['course_id'])) {
            $course_url = get_permalink($atts['course_id']);
            wp_safe_redirect($course_url);
            exit;
        }
        return '<p>Hola, ' . esc_html($current_user->display_name) . '! <a href="' . esc_url(wp_logout_url(get_permalink())) . '">Cerrar sesión</a></p>';
    }

    // Manejo del formulario de inicio de sesión
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];

        $creds = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true,
        );

        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            $error_message = 'Error: Nombre de usuario o contraseña incorrectos.';
        } else {
            // Redirigir al curso si el login es exitoso y el ID está definido
            if (!empty($atts['course_id']) && is_numeric($atts['course_id'])) {
                $course_url = get_permalink($atts['course_id']);
                wp_safe_redirect($course_url);
                exit;
            }

            // Si no hay curso asociado, redirigir a la página actual
            wp_safe_redirect(get_permalink());
            exit;
        }
    }

    // Si el usuario no está logueado, mostrar el formulario de login/registro
    ob_start();
    ?>
    <div id="login-register">
        <div id="form-container">
            <!-- Login Form -->
            <form method="POST" id="login-form" style="<?php echo $show_register_form ? 'display: none;' : ''; ?>">
                <h2 id="form-title">Iniciar Sesión</h2>
                <p id="form-subtitle">Si no tienes cuenta, <a href="#" id="toggle-form">regístrate aquí</a></p>
                
                <?php if (!empty($error_message)) : ?>
                    <div class="error-message" style="color: red; margin-bottom: 10px;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <input type="text" name="username" placeholder="Nombre de usuario o correo electrónico" required id="username">
                <input type="password" name="password" placeholder="Contraseña" required id="password">
                <input type="hidden" name="action" value="login">
                <input type="submit" value="Iniciar Sesión">
                <p id="forgot-pass"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Olvidé la contraseña</a></p>
            </form>

            <!-- Registration Form -->
            <form method="POST" id="register-form" style="display: none;">
                <h2 id="form-title-register">Registro</h2>
                <p id="form-subtitle-register">Si ya tienes cuenta, <a href="#" id="toggle-form-login">inicia sesión aquí</a></p>
                
                <?php if (!empty($error_message)) : ?>
                    <div class="error-message" style="color: red; margin-bottom: 10px;">
                        <?php echo $error_message; ?>
                    </div>
                <?php elseif (!empty($success_message)) : ?>
                    <div class="success-message" style="color: green; margin-bottom: 10px;">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <input type="text" name="first_name" placeholder="Nombre" required id="register-first-name">
                <input type="text" name="last_name" placeholder="Apellido" required id="register-last-name">
                <input type="email" name="email" placeholder="Correo electrónico" required id="register-email">
                <input type="password" name="password" placeholder="Contraseña" required id="register-password">
                <input type="hidden" name="action" value="register">
                <input type="submit" value="Registrarse">
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($show_register_form): ?>
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('register-form').style.display = 'block';
            <?php endif; ?>

            // Toggle form visibility
            document.getElementById('toggle-form').addEventListener('click', function(event) {
                event.preventDefault();
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('register-form').style.display = 'block';
            });
            
            document.getElementById('toggle-form-login').addEventListener('click', function(event) {
                event.preventDefault();
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('register-form').style.display = 'none';
            });
        });
    </script>
    <?php

    return ob_get_clean();
}

// Registrar el shortcode
add_shortcode('villegas_login_register', 'villegas_login_register_shortcode');
