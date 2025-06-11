<?php
// Email Confirmation Handler
add_action('init', 'handle_email_confirmation');

function handle_email_confirmation() {
    if (isset($_GET['confirm']) && isset($_GET['user']) && isset($_GET['course_id'])) {
        $confirmation_code = sanitize_text_field($_GET['confirm']);
        $user_id = intval($_GET['user']);
        $course_id = intval($_GET['course_id']);

        // Retrieve stored confirmation code
        $stored_code = get_user_meta($user_id, 'confirmation_code', true);

        if ($confirmation_code === $stored_code) {
            // Update the user role or any other status
            wp_update_user(array(
                'ID' => $user_id,
                'role' => 'subscriber' // Set the confirmed role
            ));
        
            // Remove the confirmation code
            delete_user_meta($user_id, 'confirmation_code');
        
            // Log in the user automatically after confirmation
            wp_set_auth_cookie($user_id);
        
            // Success message and redirection
            echo '<div style="text-align: center; padding: 80px 0; style">';
            echo '<h2>Tu cuenta ha sido confirmada. ¡Bienvenido!</h2>';
            echo '<p>Serás redirigido automáticamente al curso en <span id="countdown">5</span> segundos.</p>';
            echo '<a href="' . esc_url(get_permalink($course_id)) . '" style="display: inline-block; padding: 10px 20px; background-color: #000; color: #fff; border-radius: 5px; text-decoration: none;">Ir al curso</a>';
            echo '</div>';
        
            // JavaScript for redirection after a delay
            echo '<script>
                let countdown = 5;
                const countdownElement = document.getElementById("countdown");
                const interval = setInterval(() => {
                    countdown--;
                    countdownElement.textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        window.location.href = "' . esc_url(get_permalink($course_id)) . '";
                    }
                }, 1000);
            </script>';
        
            exit;
        } else {
            // Error message for invalid code
            echo '<p style="color: red; text-align: center; padding: 80px 0;">Código de confirmación inválido o ya utilizado.</p>';
        }
    }
}
?>
