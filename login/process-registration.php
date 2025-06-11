<?php
function handle_registration() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
        error_log("Registration form submitted");
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        error_log("First name: $first_name");
        error_log("Last name: $last_name");
        error_log("Email: $email");
        error_log("Password: " . (!empty($password) ? 'Received' : 'Missing'));
        
        // Generate a unique username based on first and last name
        $username = sanitize_user($first_name . $last_name);
        if (username_exists($username)) {
            $username = sanitize_user($first_name . '.' . $last_name . rand(1000, 9999));
        }

        $userdata = array(
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'subscriber'
        );

        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            $error_message = 'Error al registrar el usuario.';
            error_log("User creation failed: " . $user_id->get_error_message());
        } else {
            error_log("User created successfully with ID: $user_id");
        }

        if (!is_wp_error($user_id)) {
            // Generate confirmation code
            $confirmation_code = wp_generate_password(20, false);
            update_user_meta($user_id, 'confirmation_code', $confirmation_code);

            // Send confirmation email
            $to = $email;
            $subject = 'Confirma tu cuenta';
            $confirmation_link = home_url('/?confirm=' . $confirmation_code . '&user=' . $user_id);
            $message = '
            <html><body>
            <h2>¡Te has registrado en Tienda El Villegas!</h2>
            <p>Para verificar tu registro, haz clic en el botón de abajo:</p>
            <p><a href="' . esc_url($confirmation_link) . '">CONFIRMAR</a></p>
            </body></html>';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($to, $subject, $message, $headers);

            // Log in the user automatically
            $creds = array(
                'user_login' => $username,
                'user_password' => $password,
                'remember' => true,
            );
            $user = wp_signon($creds, false);

            if (!is_wp_error($user)) {
                wp_redirect(home_url('/la-republica-romana')); // Redirect to the course page
                exit;
            }
        } else {
            echo '<p style="color:red;">Error: ' . $user_id->get_error_message() . '</p>';
        }
    }
}

add_action('init', 'handle_registration');
