<?php
// Mostrar el campo de imagen en la sección "Editar cuenta"
add_action('woocommerce_edit_account_form_start', 'villegas_profile_picture_field', 5);
function villegas_profile_picture_field() {
    $user_id = get_current_user_id();
    $profile_picture = get_user_meta($user_id, 'profile_picture', true);
    $default_picture = plugin_dir_url(dirname(__FILE__)) . 'assets/profile-default.png';
    $current_picture = $profile_picture ?: $default_picture;
    ?>
    <div class="villegas-profile-upload-wrapper">
        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" style="display:none;">
        
        <div class="villegas-profile-avatar" onclick="document.getElementById('profile_picture').click();">
            <img id="villegas-profile-preview" src="<?php echo esc_url($current_picture); ?>" alt="Imagen de perfil" />
            <div class="upload-icon">+</div>
        </div>

        <?php if ($profile_picture): ?>
            <label class="remove-checkbox"><input type="checkbox" name="remove_profile_picture" value="1"> Eliminar imagen actual</label>
        <?php endif; ?>
    </div>
    <?php
}


// Guardar o eliminar la imagen de perfil al actualizar la cuenta
add_action('woocommerce_save_account_details', 'villegas_save_profile_picture');
function villegas_save_profile_picture($user_id) {
    $current_picture = get_user_meta($user_id, 'profile_picture', true);
    $is_removing = !empty($_POST['remove_profile_picture']);
    $is_uploading = isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK;

    // Bloquear nuevas subidas si ya hay imagen y no se marcó eliminar
    if ($current_picture && !$is_removing && $is_uploading) {
        wc_add_notice('⚠️ Ya tienes una imagen de perfil. Elimínala antes de subir una nueva.', 'error');
        return;
    }

    // Eliminar imagen actual si se marcó el checkbox
    if ($is_removing) {
        if ($current_picture) {
            global $wpdb;
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment'",
                $current_picture
            ));
            if ($attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
            delete_user_meta($user_id, 'profile_picture');
        }
        wc_add_notice('🗑️ Imagen de perfil eliminada.', 'notice');
    }

    // Subida de nueva imagen
    if (!$is_uploading) {
        return;
    }

    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types) || $file['size'] > 2 * 1024 * 1024) {
        wc_add_notice('Formato no válido o imagen demasiado grande (máx 2MB).', 'error');
        return;
    }

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $upload = wp_handle_upload($file, ['test_form' => false]);
    if (!$upload || isset($upload['error'])) {
        wc_add_notice('Error al subir la imagen: ' . ($upload['error'] ?? 'Desconocido'), 'error');
        return;
    }

    // Redimensionar
    $editor = wp_get_image_editor($upload['file']);
    if (!is_wp_error($editor)) {
        $editor->resize(200, 200, true);
        $editor->set_quality(90);
        $editor->save($upload['file']);
    }

    // Registrar como attachment
    $filename = basename($upload['file']);
    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = [
        'guid'           => $upload['url'],
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    update_user_meta($user_id, 'profile_picture', esc_url(wp_get_attachment_url($attach_id)));
    wc_add_notice('✅ Imagen de perfil actualizada.', 'success');
}
