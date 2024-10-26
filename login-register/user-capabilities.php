<?php
// user-capabilities.php
add_action('init', 'restrict_non_confirmed_user_capabilities');

function restrict_non_confirmed_user_capabilities() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('pending', $current_user->roles)) {
            // Quitar capacidades
            $current_user->remove_cap('edit_posts');
            $current_user->remove_cap('comment'); // Si has habilitado capacidades de comentario
        }
    }
}
?>
