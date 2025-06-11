<?php
// Agregar campo Título Personal en perfil de usuario (Admin)
function villegas_agregar_titulo_personal_usuario($user) {
    ?>
    <h2><?php _e("Título Personal", "el-villegas-plugin"); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="titulo_personal"><?php _e("Título Personal"); ?></label></th>
            <td>
                <input type="text" name="titulo_personal" id="titulo_personal"
                    value="<?php echo esc_attr(get_user_meta($user->ID, 'titulo_personal', true)); ?>"
                    class="regular-text" maxlength="150" /><br />
                <span class="description"><?php _e("Introduce un título que describa tu ocupación o rol (máx 150 caracteres)."); ?></span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'villegas_agregar_titulo_personal_usuario');
add_action('edit_user_profile', 'villegas_agregar_titulo_personal_usuario');

// Guardar campo desde perfil admin
function villegas_guardar_titulo_personal_usuario($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['titulo_personal'])) {
        update_user_meta($user_id, 'titulo_personal', sanitize_text_field($_POST['titulo_personal']));
        // Clear user cache after updating meta
        clean_user_cache($user_id);
    }
}
add_action('personal_options_update', 'villegas_guardar_titulo_personal_usuario');
add_action('edit_user_profile_update', 'villegas_guardar_titulo_personal_usuario');

// === CAMPO EN MI CUENTA (WooCommerce) === //
add_action('woocommerce_edit_account_form', function () {
    $user_id = get_current_user_id();
    $titulo = get_user_meta($user_id, 'titulo_personal', true);
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const showNameField = document.querySelector('#account_display_name');
        if (showNameField) {
            const tituloHTML = `
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="titulo_personal">Título Personal</label>
                    <input type="text"
                           class="woocommerce-Input woocommerce-Input--text input-text"
                           name="titulo_personal"
                           id="titulo_personal"
                           value="<?php echo esc_attr($titulo); ?>"
                           maxlength="150"
                           placeholder="Tu profesión o actividad" />
                </p>
            `;
            showNameField.closest('.form-row').insertAdjacentHTML('afterend', tituloHTML);
        }
    });
    </script>
    <?php
});


// Guardar campo al actualizar Mi Cuenta
add_action('woocommerce_save_account_details', function ($user_id) {
    if (!empty($_POST['titulo_personal'])) {
        update_user_meta($user_id, 'titulo_personal', sanitize_text_field($_POST['titulo_personal']));
        // Clear user cache after updating meta
        clean_user_cache($user_id);
    }
});