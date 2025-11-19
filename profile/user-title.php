<?php
// Add field to User Profile (user edits own profile) and Admin Edit User
add_action('show_user_profile', 'villegas_user_title_field');
add_action('edit_user_profile', 'villegas_user_title_field');

function villegas_user_title_field($user) {
    $value = get_user_meta($user->ID, 'user_title', true);
    ?>
    <h2>Título del Usuario</h2>
    <table class="form-table">
        <tr>
            <th><label for="user_title">User Title</label></th>
            <td>
                <input type="text"
                       name="user_title"
                       id="user_title"
                       class="regular-text"
                       value="<?php echo esc_attr($value); ?>"
                       placeholder="President, Engineer, Writer, Owner, etc.">
                <p class="description">Professional or public title for this user.</p>
            </td>
        </tr>
    </table>
    <?php
}

// Save to user_meta
add_action('personal_options_update', 'villegas_save_user_title_field');
add_action('edit_user_profile_update', 'villegas_save_user_title_field');

function villegas_save_user_title_field($user_id) {

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['user_title'])) {
        update_user_meta($user_id, 'user_title', sanitize_text_field($_POST['user_title']));
    }
}
