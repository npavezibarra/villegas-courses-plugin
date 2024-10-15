<?php

// Add the admin menu
function villegas_lm_register_menu() {
    add_menu_page(
        'VillegasLM',            // Page title
        'VillegasLM',            // Menu title
        'manage_options',        // Capability
        'villegas_lm',           // Menu slug
        'villegas_lm_register_page_callback', // Callback function
        'dashicons-welcome-learn-more',  // Icon
        6                        // Position
    );

    // Add only the sub-menu for Register Page
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

// Callback function for the Register Page
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


// Hook into WordPress 'the_content' to automatically add the shortcode to the selected page
function villegas_lm_display_shortcode_on_selected_page($content) {
    // Get the selected page ID from the settings
    $selected_page_id = get_option('villegas_lm_register_page_id');

    // Check if we're on the selected page
    if (is_page($selected_page_id)) {
        // Append the shortcode output to the page content
        $shortcode_content = do_shortcode('[registro_o_login]');
        $content .= $shortcode_content;
    }

    return $content;
}
add_filter('the_content', 'villegas_lm_display_shortcode_on_selected_page');
