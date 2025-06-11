<div id="mensaje-logeado">
    <?php 
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $first_name = $current_user->user_firstname;
        $display_name = $current_user->display_name;
        echo 'Hola ' . esc_html(!empty($first_name) ? $first_name : $display_name) . " ";
        echo '<a class="logout-cuenta" href="' . esc_url(wp_logout_url(get_permalink())) . '">Cerrar sesión</a>';
    } else {
        $register_page_check = get_page_by_path('ingresa-roma');
        $register_page_url = $register_page_check ? get_permalink($register_page_check) : wp_login_url();
        echo 'No estás logeado <a class="logout-cuenta" href="' . esc_url($register_page_url) . '">log in</a>';
    }
    ?>
</div>