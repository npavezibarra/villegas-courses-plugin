<?php
// Function to display the profile picture upload input
function display_profile_picture_upload() {
    $user_id = get_current_user_id();
    $user_photo_url = get_user_meta($user_id, 'profile_picture', true);
    ?>
    <div style="margin-top: 20px;">
        <h3>Upload Profile Picture</h3>
        <?php if ($user_photo_url): ?>
            <img src="<?php echo esc_url($user_photo_url); ?>" alt="Profile Picture" style="width: 100px; height: 100px; border-radius: 50%;">
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="profile_photo" accept="image/*" />
            <button type="submit" name="upload_photo" style="margin-top: 10px;">Upload</button>
        </form>
    </div>
    <?php
}

// User Photo Upload Function
function handle_photo_upload() {
    if (isset($_FILES['profile_photo']) && !empty($_FILES['profile_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploadedfile = $_FILES['profile_photo'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $uploaded_url = $movefile['url'];
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'profile_picture', $uploaded_url);
            echo "File is valid, and was successfully uploaded.\n";
        } else {
            echo "File upload error: " . $movefile['error'];
        }
    }
}

// Hook to display the upload form on the account dashboard
add_action('woocommerce_account_dashboard', 'display_profile_picture_upload');
add_action('init', 'handle_photo_upload'); // Ensure WordPress functions are available
?>
