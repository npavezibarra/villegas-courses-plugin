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

            // Mensaje de éxito (podrías redirigir a una página específica en vez de esto)
            echo '<h2>Tu cuenta ha sido confirmada. ¡Bienvenido!</h2>';
            // Aquí podrías redirigir a la página principal o a la página de inicio de sesión
            exit; // Asegúrate de usar exit para detener la ejecución
        } else {
            echo '<h2 style="text-align: center;">Código de confirmación inválido o ya utilizado.</h2>';
        }
    }
}
