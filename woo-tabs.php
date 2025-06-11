<?php

/* TAB MY ACCOUNT MIS CURSOS MIS EVALUACIONES */

// Registrar el endpoint de forma global en init
add_action( 'init', 'villegas_registrar_endpoint_mis_cursos' );
function villegas_registrar_endpoint_mis_cursos() {
    add_rewrite_endpoint( 'mis-cursos', EP_ROOT | EP_PAGES );
}

// Contenido del tab "Mis Cursos"
function villegas_contenido_tab_mis_cursos() {
    $template_path = plugin_dir_path(__FILE__) . 'templates/cursos-my-account.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p>No se encontró la plantilla cursos-my-account.php.</p>';
    }
}
add_action( 'woocommerce_account_mis-cursos_endpoint', 'villegas_contenido_tab_mis_cursos' );


// Agregar el tab "Mis Cursos" al menú de Mi Cuenta
add_filter( 'woocommerce_account_menu_items', 'villegas_agregar_tab_mis_cursos' );
function villegas_agregar_tab_mis_cursos( $items ) {
    // Extraer el item de "Cerrar sesión"
    $logout = $items['customer-logout'];
    unset( $items['customer-logout'] );
    
    // Agregar el tab "Mis Cursos"
    $items['mis-cursos'] = 'Mis Cursos';
    
    // Reinsertar "Cerrar sesión" al final
    $items['customer-logout'] = $logout;
    return $items;
}

// Forzar la URL del tab usando el filtro específico para los enlaces de menú
add_filter( 'woocommerce_get_account_menu_item_permalink', 'villegas_forzar_account_menu_item_permalink', 10, 2 );
function villegas_forzar_account_menu_item_permalink( $url, $endpoint ) {
    if ( 'mis-cursos' === $endpoint ) {
        // Obtenemos la URL base de Mi Cuenta, p.ej., /mi-cuenta/ y le concatenamos el endpoint
        $url = trailingslashit( wc_get_page_permalink( 'myaccount' ) ) . $endpoint . '/';
    }
    return $url;
}

// Flush rewrite rules al activar/desactivar el plugin
register_activation_hook( __FILE__, 'villegas_flush_rewrite_rules' );
function villegas_flush_rewrite_rules() {
    villegas_registrar_endpoint_mis_cursos();
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );