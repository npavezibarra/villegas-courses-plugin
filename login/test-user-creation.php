<?php
// Only run this code if accessed directly for testing
if (!defined('ABSPATH')) exit;

function test_user_creation() {
    $user_id = wp_create_user('testuser', 'testpassword123', 'testuser@example.com');
    if (is_wp_error($user_id)) {
        error_log("Direct user creation failed: " . $user_id->get_error_message());
    } else {
        error_log("Direct user created successfully with ID: " . $user_id);
    }
}

add_action('init', 'test_user_creation');
