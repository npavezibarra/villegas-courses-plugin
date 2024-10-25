<?php
// create-page.php
function villegas_create_login_registration_page() {
    // Verificar si la página ya existe
    $page_title = 'Ingresa Roma'; // Título de la página
    $page_content = '[villegas_registration_login]'; // Shortcode que usaremos
    $page_template = ''; // Puedes especificar una plantilla si lo deseas

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
            'post_name' => 'ingresa-roma', // Cambiado a ingresa-roma
            'page_template' => $page_template
        ));
    }
}
add_action('init', 'villegas_create_login_registration_page');

