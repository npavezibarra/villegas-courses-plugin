<?php
// admin/admin-page.php

// Asegurar que no se acceda directamente
if (!defined('ABSPATH')) {
    exit;
}

// Función para registrar el menú de administración
function villegas_admin_menu()
{
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

    add_submenu_page(
        'villegas-lms',
        'Course Checklist',
        'Course Checklist',
        'manage_options',
        'villegas-course-checklist',
        'villegas_render_course_checklist_page'
    );
}
add_action('admin_menu', 'villegas_admin_menu');

// Contenido de la página principal
function villegas_admin_main_page()
{
    if (isset($_POST['villegas_lms_settings_nonce']) && wp_verify_nonce($_POST['villegas_lms_settings_nonce'], 'villegas_lms_save_settings')) {
        $enabled = isset($_POST['villegas_lms_enabled']) ? 'yes' : 'no';
        update_option('villegas_lms_enabled', $enabled);
        echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>';
    }

    $lms_enabled = get_option('villegas_lms_enabled', 'yes');
    ?>
    <div class="wrap">
        <h1>Villegas LMS</h1>
        <p>Bienvenido al panel de administración de El Villegas Plugin.</p>

        <form method="post" action="">
            <?php wp_nonce_field('villegas_lms_save_settings', 'villegas_lms_settings_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Habilitar LMS (Mis Cursos)</th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="villegas_lms_enabled" value="yes" <?php checked($lms_enabled, 'yes'); ?>>
                            <span class="slider round"></span>
                        </label>
                        <p class="description">Si se desactiva, la pestaña "Mis Cursos" no será accesible y la página
                            /cursos redirigirá al inicio.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <style>
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                -webkit-transition: .4s;
                transition: .4s;
                border-radius: 34px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                -webkit-transition: .4s;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked+.slider {
                background-color: #2196F3;
            }

            input:focus+.slider {
                box-shadow: 0 0 1px #2196F3;
            }

            input:checked+.slider:before {
                -webkit-transform: translateX(26px);
                -ms-transform: translateX(26px);
                transform: translateX(26px);
            }
        </style>
    </div>
    <?php
}

// Contenido de la subpágina "Quizzes"
function villegas_admin_quizzes_page()
{
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
    wp_localize_script(
        'villegas-quiz-style',
        'villegasQuizStyleData',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('guardar_imagen_estilo_quiz'),
        ]
    );
}

