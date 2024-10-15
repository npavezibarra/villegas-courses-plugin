<?php
// Add the admin menu
function villegas_lm_register_menu() {
    add_menu_page(
        'VillegasLM',            // Page title
        'VillegasLM',            // Menu title
        'manage_options',        // Capability
        'villegas_lm',           // Menu slug
        '',                      // Callback function (leave empty for now)
        'dashicons-welcome-learn-more',  // Icon
        6                        // Position
    );

    add_submenu_page(
        'villegas_lm',           // Parent slug
        'Register Page',         // Page title
        'Register Page',         // Menu title
        'manage_options',        // Capability
        'villegas_lm_register',  // Menu slug
        'villegas_lm_register_page_callback'  // Callback function
    );
}
add_action('admin_menu', 'villegas_lm_register_menu');

// Callback function for the register page
function villegas_lm_register_page_callback() {
    ?>
    <div class="wrap">
        <h1>Select the Register/Login Page</h1>
        <form method="post" action="options.php">
            <?php
            // Security field for saving the options
            settings_fields('villegas_lm_options_group');
            do_settings_sections('villegas_lm');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the settings for the register page
function villegas_lm_register_settings() {
    register_setting('villegas_lm_options_group', 'villegas_lm_register_page_id');

    add_settings_section(
        'villegas_lm_section',             // Section ID
        'Settings',                        // Section title
        '',                                // Callback (can leave empty)
        'villegas_lm'                      // Page slug
    );

    add_settings_field(
        'villegas_lm_register_page',       // Field ID
        'Select Register/Login Page',      // Field label
        'villegas_lm_register_page_field', // Field callback
        'villegas_lm',                     // Page slug
        'villegas_lm_section'              // Section ID
    );
}
add_action('admin_init', 'villegas_lm_register_settings');

// Callback function to display the dropdown for selecting the page
function villegas_lm_register_page_field() {
    $selected_page_id = get_option('villegas_lm_register_page_id');
    $pages = get_pages();
    ?>
    <select name="villegas_lm_register_page_id">
        <?php foreach ($pages as $page) : ?>
            <option value="<?php echo $page->ID; ?>" <?php selected($selected_page_id, $page->ID); ?>>
                <?php echo $page->post_title; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}
