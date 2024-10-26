<?php
// create-page.php
function villegas_create_login_registration_page() {
    // Verificar si la página ya existe
    $page_title = 'Ingresa Roma'; // Título de la página
    $page_content = '[villegas_registration_login]'; // Shortcode que usaremos
    $page_template = 'page-no-title.php'; // Especificar la plantilla deseada

    // Comprobar si la página ya existe
    $page_check = get_page_by_path('ingresa-roma'); // Cambiado a ingresa-roma

    if (!isset($page_check->ID)) {
        // Crear la página
        $new_page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1, // ID del autor (normalmente el admin)
            'post_name' => 'ingresa-roma' // Cambiado a ingresa-roma
        ));

        // Establecer la plantilla de la página
        if ($new_page_id) {
            update_post_meta($new_page_id, '_wp_page_template', $page_template);
        }
    } else {
        // Si la página ya existe, actualizar el contenido y la plantilla
        $page_id = $page_check->ID;

        // Actualizar el contenido si es necesario
        if ($page_content !== $page_check->post_content) {
            wp_update_post(array(
                'ID' => $page_id,
                'post_content' => $page_content,
            ));
        }

        // Establecer la plantilla de la página
        update_post_meta($page_id, '_wp_page_template', $page_template);
    }
}
add_action('init', 'villegas_create_login_registration_page');
?>
