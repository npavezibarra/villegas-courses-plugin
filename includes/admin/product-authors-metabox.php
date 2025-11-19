<?php
/**
 * Product authors/admins metabox for WooCommerce products.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 'villegas_add_product_authors_metabox');
/**
 * Register the product authors metabox.
 */
function villegas_add_product_authors_metabox() {
    add_meta_box(
        'villegas_product_authors',
        __('Product Authors', 'villegas'),
        'villegas_render_product_authors_metabox',
        'product',
        'side',
        'default'
    );
}

/**
 * Render the product authors metabox UI.
 *
 * @param WP_Post $post Current post object.
 */
function villegas_render_product_authors_metabox($post) {
    $saved_users = get_post_meta($post->ID, '_product_assigned_authors', true);
    if (!is_array($saved_users)) {
        $saved_users = [];
    }

    wp_nonce_field('villegas_save_product_authors', 'villegas_product_authors_nonce');
    ?>
    <div id="villegas-authors-wrapper">
        <input
            type="text"
            id="villegas-author-search"
            placeholder="<?php esc_attr_e('Search user…', 'villegas'); ?>"
            style="width:100%; margin-bottom:10px;"
        />

        <ul id="villegas-authors-selected" style="margin-top:10px;">
            <?php foreach ($saved_users as $user_id) :
                $u = get_user_by('id', $user_id);
                if (!$u) {
                    continue;
                }
                ?>
                <li data-user-id="<?php echo esc_attr($user_id); ?>">
                    <?php echo esc_html($u->display_name); ?>
                    <a href="#" class="villegas-remove-author" style="color:red; margin-left:5px;">×</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <input
            type="hidden"
            id="villegas-authors-hidden"
            name="villegas_assigned_authors"
            value="<?php echo esc_attr(wp_json_encode($saved_users)); ?>"
        />
    </div>
    <?php
}

add_action('admin_enqueue_scripts', 'villegas_enqueue_product_author_scripts');
/**
 * Enqueue admin assets for the metabox UI.
 *
 * @param string $hook Current screen hook.
 */
function villegas_enqueue_product_author_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') {
        return;
    }

    wp_enqueue_script(
        'villegas-product-authors',
        plugin_dir_url(VILLEGAS_COURSES_PLUGIN_FILE) . 'assets/js/product-authors.js',
        ['jquery', 'jquery-ui-autocomplete'],
        '1.0',
        true
    );

    wp_localize_script('villegas-product-authors', 'VillegasAuthorSearch', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('villegas_search_user_nonce'),
    ]);
}

add_action('wp_ajax_villegas_search_user', 'villegas_search_user');
/**
 * AJAX search handler for admin/author users.
 */
function villegas_search_user() {
    check_ajax_referer('villegas_search_user_nonce');

    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';

    $args = [
        'search'         => '*' . $term . '*',
        'search_columns' => ['display_name', 'user_login'],
        'role__in'       => ['administrator', 'author'],
        'number'         => 10,
    ];

    $users = get_users($args);
    $results = [];

    foreach ($users as $u) {
        $results[] = [
            'id'    => $u->ID,
            'label' => $u->display_name,
            'value' => $u->display_name,
        ];
    }

    wp_send_json($results);
}

add_action('save_post_product', 'villegas_save_product_authors');
/**
 * Persist assigned authors to post meta.
 *
 * @param int $post_id Product ID.
 */
function villegas_save_product_authors($post_id) {
    if (!isset($_POST['villegas_product_authors_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['villegas_product_authors_nonce'])), 'villegas_save_product_authors')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $json = isset($_POST['villegas_assigned_authors']) ? wp_unslash($_POST['villegas_assigned_authors']) : '[]';
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        $decoded = [];
    }

    $valid_roles = ['administrator', 'author'];
    $valid_ids   = [];

    foreach ($decoded as $id) {
        $user_id = intval($id);
        $user    = get_user_by('id', $user_id);

        if ($user && array_intersect($valid_roles, (array) $user->roles)) {
            $valid_ids[] = $user_id;
        }
    }

    update_post_meta($post_id, '_product_assigned_authors', $valid_ids);
}
