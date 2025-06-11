<?php
// admin/admin-page.php

// Asegurar que no se acceda directamente
if (!defined('ABSPATH')) {
    exit;
}

// Función para registrar el menú de administración
function villegas_admin_menu() {
    add_menu_page(
        'Villegas LMS',
        'VillegasLMS',
        'manage_options',
        'villegas-lms',
        'villegas_admin_main_page',
        'dashicons-welcome-learn-more',
        6
    );

    add_submenu_page(
        'villegas-lms',
        'Quizzes',
        'Quizzes',
        'manage_options',
        'villegas-lms-quizzes',
        'villegas_admin_quizzes_page'
    );
}
add_action('admin_menu', 'villegas_admin_menu');

// Contenido de la página principal (puedes cambiarlo más adelante)
function villegas_admin_main_page() {
    echo '<div class="wrap"><h1>Villegas LMS</h1><p>Bienvenido al panel de administración de El Villegas Plugin.</p></div>';
}

// Contenido de la subpágina "Quizzes"
function villegas_admin_quizzes_page() {
    ?>
    <div class="wrap">
        <h1>Gestión de Quizzes</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Quiz</th>
                    <th>Imagen de Fondo</th>
                </tr>
            </thead>
            <tbody id="quizzes-style-table">
                <?php
                $quizzes = get_posts([
                    'post_type' => 'sfwd-quiz',
                    'posts_per_page' => -1,
                ]);

                foreach ($quizzes as $quiz) {
                    $image_id = get_post_meta($quiz->ID, '_quiz_style_image', true);
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                    ?>
                    <tr data-quiz-id="<?php echo esc_attr($quiz->ID); ?>">
                        <td><?php echo esc_html($quiz->post_title); ?></td>
                        <td>
                            <button class="select-image-button button">Seleccionar Imagen</button>
                            <input type="hidden" class="image-id-field" value="<?php echo esc_attr($image_id); ?>">
                            <div class="preview-image" style="margin-top: 10px;">
                                <?php if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 80px; height: auto;">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
    // Encolar JS
    wp_enqueue_media();
    wp_enqueue_script('villegas-quiz-style', plugin_dir_url(__FILE__) . 'js/quiz-style.js', ['jquery'], '1.0', true);
}

