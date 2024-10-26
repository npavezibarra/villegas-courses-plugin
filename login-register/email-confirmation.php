<?php
add_action('init', 'handle_email_confirmation');

function handle_email_confirmation() {
    if (isset($_GET['confirm']) && isset($_GET['user'])) {
        $confirmation_code = sanitize_text_field($_GET['confirm']);
        $user_id = intval($_GET['user']);

        // Verificar el código de confirmación
        $stored_code = get_user_meta($user_id, 'confirmation_code', true);

        if ($confirmation_code === $stored_code) {
            // Actualizar el rol del usuario a 'subscriber'
            wp_update_user(array(
                'ID' => $user_id,
                'role' => 'subscriber'
            ));
            // Eliminar el código de confirmación después de usarlo
            delete_user_meta($user_id, 'confirmation_code');

            // Mensaje de éxito
            echo '<div style="text-align: center; padding: 80px 0;">';
            echo '<h2>Tu cuenta ha sido confirmada. ¡Bienvenido!</h2>';
            echo '<p>Por favor, haz clic en el botón para ir al curso.</p>';
            echo '<a href="' . esc_url(home_url('/la-republica-romana')) . '" style="display: inline-block; padding: 10px 20px; background-color: #000; color: #fff; border-radius: 5px; text-decoration: none;">Ir al curso</a>';
            echo '<p>O serás redirigido en <span id="countdown">5</span> segundos.</p>';
            echo '</div>';

            // JavaScript para redireccionar después de 5 segundos
            echo '<script>
                let countdown = 5;
                const countdownElement = document.getElementById("countdown");
                const interval = setInterval(() => {
                    countdown--;
                    countdownElement.textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        window.location.href = "' . esc_url(home_url('/la-republica-romana')) . '";
                    }
                }, 1000);
            </script>';

            exit; // Asegúrate de usar exit para detener la ejecución
        } else {
            echo 'Código de confirmación inválido o ya utilizado.';
        }
    }
}

