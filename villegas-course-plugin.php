<?php
/**
 * Plugin Name: Villegas Course Plugin
 * Description: Custom functionality for Villegas courses.
 * Version: 1.0.0
 * Author: Villegas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-vcp-auth-shortcode.php';
require_once __DIR__ . '/includes/vcp-auth-ajax.php';

add_action('wp_enqueue_scripts', function () {
    if (!is_singular() || !isset($GLOBALS['post'])) {
        return;
    }

    if (has_shortcode($GLOBALS['post']->post_content, 'vcp_auth')) {
        wp_enqueue_style('vcp-auth-css', plugin_dir_url(__FILE__) . 'assets/css/vcp-auth.css', [], '1.0');
        wp_enqueue_script('vcp-auth-js', plugin_dir_url(__FILE__) . 'assets/js/vcp-auth.js', ['jquery'], '1.0', true);

        wp_localize_script('vcp-auth-js', 'VCP_AUTH', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vcp_auth_nonce'),
        ]);
    }
});
