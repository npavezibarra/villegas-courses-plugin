<?php
// Añadir el metabox para seleccionar el Quiz Inicial
function add_first_quiz_metabox() {
    add_meta_box(
        'first_quiz_metabox',           // ID único
        'Quiz Inicial',                 // Título del metabox
        'render_first_quiz_metabox',    // Función de callback que renderiza el contenido
        'sfwd-courses',                 // Pantalla o post type
        'side',                         // Contexto (donde aparecerá: 'normal', 'side', 'advanced')
        'high'                          // Prioridad
    );
}
add_action('add_meta_boxes', 'add_first_quiz_metabox');

// Renderizar el metabox con un dropdown para seleccionar el quiz
function render_first_quiz_metabox($post) {
    // Obtener el ID del quiz seleccionado previamente (si existe)
    $selected_quiz_id = get_post_meta($post->ID, '_first_quiz_id', true);

    // Query para obtener todos los quizzes publicados que no estén asociados a ningún curso
    $args = array(
        'post_type' => 'sfwd-quiz',           // Tipo de post: Quiz de LearnDash
        'posts_per_page' => -1,               // Obtener todos los quizzes
        'post_status' => 'publish',           // Solo quizzes publicados
        'meta_query' => array(
            array(
                'key' => 'course_id',        // Filtrar los quizzes que no tienen curso asociado
                'compare' => 'NOT EXISTS'
            ),
        ),
    );
    $quizzes = get_posts($args);

    // Seguridad: Generar un nonce para la validación del formulario
    wp_nonce_field('save_first_quiz_metabox', 'first_quiz_nonce');

    // Crear el dropdown
    echo '<label for="first_quiz">Selecciona el Quiz Inicial:</label>';
    echo '<select name="first_quiz" id="first_quiz">';
    echo '<option value="">-- Selecciona un quiz --</option>';

    foreach ($quizzes as $quiz) {
        echo '<option value="' . esc_attr($quiz->ID) . '" ' . selected($selected_quiz_id, $quiz->ID, false) . '>' . esc_html($quiz->post_title) . '</option>';
    }

    echo '</select>';
}

// Guardar el Quiz Inicial seleccionado
function save_first_quiz_metabox($post_id) {
    // Verificar el nonce para asegurar que el formulario fue enviado desde nuestra pantalla
    if (!isset($_POST['first_quiz_nonce']) || !wp_verify_nonce($_POST['first_quiz_nonce'], 'save_first_quiz_metabox')) {
        return;
    }

    // Verificar que no sea un autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar que el usuario tenga permiso para editar el post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Verificar si el campo "first_quiz" está presente en la solicitud
    if (isset($_POST['first_quiz'])) {
        $first_quiz_id = sanitize_text_field($_POST['first_quiz']);
        // Guardar el ID del quiz como meta data
        update_post_meta($post_id, '_first_quiz_id', $first_quiz_id);
    } else {
        // Si no se selecciona nada, eliminar el meta data
        delete_post_meta($post_id, '_first_quiz_id');
    }
}
add_action('save_post', 'save_first_quiz_metabox');


// Cargar solo los scripts necesarios en la pantalla de edición del curso
function enqueue_metabox_admin_scripts($hook_suffix) {
    global $post_type;
    if ('sfwd-courses' === $post_type && 'post.php' === $hook_suffix) {
        // Cargar cualquier script o estilo que necesites, si es necesario
        wp_enqueue_script('my-metabox-script', plugin_dir_url(__FILE__) . 'assets/js/custom-metabox.js', array('jquery'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'enqueue_metabox_admin_scripts');
