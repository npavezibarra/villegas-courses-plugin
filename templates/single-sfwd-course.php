<?php
get_header(); ?>
<style>
    html {
    margin-top: 0px !important;
}
</style>

<div id="main-menu">
    <div id="logo-website">
        <?php
        // Mostrar el logo del sitio si está configurado, de lo contrario, mostrar el nombre del sitio.
        if (function_exists('the_custom_logo') && has_custom_logo()) {
            the_custom_logo(); // Mostrar el logo del sitio.
        } else {
            // Mostrar el nombre del sitio si no hay logo.
            echo '<h1>' . get_bloginfo('name') . '</h1>';
        }
        ?>
    </div>
    <nav id="menu-replica" class="is-responsive items-justified-right wp-block-navigation is-horizontal is-content-justification-right is-layout-flex wp-container-core-navigation-is-layout-1 wp-block-navigation-is-layout-flex">
        <?php
        // Cargar el menú de navegación principal.
        echo do_blocks( '<!-- wp:navigation {"overlayMenu":"never"} /-->' );
        ?>
    </nav>
    
</div>
<div id="mensaje-logeado">
    <?php 
    if (is_user_logged_in()) {
        // Obtener el nombre del usuario actual
        $current_user = wp_get_current_user();
        $first_name = $current_user->user_firstname;
        $display_name = $current_user->display_name; // Obtener el display name

        // Mostrar saludo con First Name o Display Name
        echo 'Hola ' . esc_html(!empty($first_name) ? $first_name : $display_name) . " "; // Priorizar First Name

        // Mostrar enlace de cerrar sesión solo si el usuario ha iniciado sesión
        echo '<a class="logout-cuenta" href="' . esc_url(wp_logout_url(get_permalink())) . '">Cerrar sesión</a>';
    } else {
        // Obtener la página de Registro y Login "Ingresa Roma"
        $register_page_check = get_page_by_path('ingresa-roma');
        $register_page_url = $register_page_check ? get_permalink($register_page_check) : wp_login_url();

        echo 'No estás logeado <a class="logout-cuenta" href="' . esc_url($register_page_url) . '">log in</a>'; // Redirigir a la nueva página Ingresa Roma
    }
    ?>
</div>




<div id="body-content" 
    style="background-image: url(<?php 
        // Verificar si el post tiene una imagen destacada.
        if (has_post_thumbnail()) {
            // Obtener la URL de la imagen destacada.
            echo get_the_post_thumbnail_url(null, 'full'); // Obtener la URL de la imagen destacada en tamaño completo.
        } else {
            // En caso de no haber imagen destacada, puedes usar una imagen por defecto o un color.
            echo 'https://via.placeholder.com/1920x1080'; // URL de una imagen por defecto.
        }
    ?>); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div id="datos-generales-curso">
    <h1><?php the_title(); ?></h1> <!-- Título del curso dinámico -->
    <h4>Profesor <?php 
        $author_id = get_post_field( 'post_author', get_the_ID() );
        $first_name = get_the_author_meta( 'first_name', $author_id );
        $last_name = get_the_author_meta( 'last_name', $author_id );
        echo $first_name . ' ' . $last_name;
    ?></h4> <!-- Nombre del autor (profesor) dinámico con nombre y apellido -->
    </div>
    <!-- Puedes añadir más contenido aquí si es necesario -->
</div>

<div id="buy-button-stats">
    <?php
    if (function_exists('mostrar_comprar_stats')) {
        mostrar_comprar_stats(); // Llamada dentro del div.
    } ?>
</div>


<div id="about-course">
<div id="course-content">
    <h4 style="color: black;">Contenido del curso</h4>
    <ul style="list-style-type: none; padding-left: 0;">
        <?php
        // Obtener el ID del curso actual
        $course_id = get_the_ID();

        // Verificar si es un curso válido de LearnDash
        if ($course_id) {
            // Obtener las lecciones asociadas al curso por orden de menú
            $lessons_query = new WP_Query(array(
                'post_type' => 'sfwd-lessons',
                'meta_key' => 'course_id',
                'meta_value' => $course_id,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'posts_per_page' => -1, // Obtener todas las lecciones
            ));

            // Obtener el ID del usuario actual
            $user_id = get_current_user_id();

            // Obtener headers (secciones) del curso
            $course_builder_meta = get_post_meta($course_id, 'course_sections', true);
            $section_headers = json_decode($course_builder_meta, true); // Parse the JSON data

            // Inicializar contador para las lecciones
            $lesson_index = 0;
            $lessons = $lessons_query->posts;

            // Crear la lista de lecciones y headers
            for ($step_index = 0; $step_index < count($lessons) + count($section_headers); $step_index++) {

                // Verificar si hay un header (sección) en esta posición
                $current_section = array_filter($section_headers, function($header) use ($step_index) {
                    return isset($header['order']) && $header['order'] == $step_index;
                });

                // Mostrar el header si existe
                if (!empty($current_section)) {
                    $current_section = reset($current_section); // Obtener el primer header coincidente
                    echo '<li class="course-section-header" style="margin-bottom: 10px; padding: 20px;">';
                    echo '<h4>' . esc_html($current_section['post_title']) . '</h4>';
                    echo '</li>';
                    continue;
                }

                // Mostrar la lección si no es un header
                if ($lesson_index < count($lessons)) {
                    $lesson_post = $lessons[$lesson_index];
                    $lesson_id = $lesson_post->ID;

                    // Verificar si la lección está completada
                    $is_completed = learndash_is_lesson_complete($user_id, $lesson_id);
                    $circle_color_class = $is_completed ? 'completed' : 'not-completed';

                    echo '<li class="lesson-item ' . $circle_color_class . '" style="display: flex; align-items: center; margin-bottom: 10px;">';
                    echo '<span class="lesson-circle" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 10px; background-color: ' . ($is_completed ? '#4c8bf5' : '#ccc') . ';"></span>';
                    echo '<a href="' . get_permalink($lesson_id) . '">' . get_the_title($lesson_id) . '</a>';
                    echo '</li>';
                    $lesson_index++;
                }
            }

            // Reset post data después del query
            wp_reset_postdata();

            // Obtener y mostrar quizzes asociados al curso
            $quizzes = learndash_get_course_quiz_list($course_id);
            if (!empty($quizzes)) {
                echo '<hr>';
                foreach ($quizzes as $quiz) {
                    echo '<li style="display: flex; align-items: center;">';
                    echo '<img src="' . esc_url(plugins_url('assets/svg/exam-icon.svg', __DIR__)) . '" alt="Exam Icon" style="width: 20px; height: 20px; margin-right: 10px;">';
                    echo '<a href="' . get_permalink($quiz['post']->ID) . '">' . esc_html($quiz['post']->post_title) . '</a>';
                    echo '</li>';
                }
            } else {
                echo '<p>No hay quizzes asociados a este curso.</p>';
            }
        }
        ?>
    </ul>
</div>


<div id="description-course">
    <h4>Sobre este curso</h4>
    <hr>
    <?php
    // Mostrar el contenido descriptivo del curso almacenado en el backend
    the_content();
    ?>
</div>

</div>

<div id="autor-box" style="padding: 40px; border-radius: 10px; background-color: #f9f9f9; display: flex; align-items: flex-start; margin-top: 20px;">
    <div style="flex: 0 0 auto; margin-right: 20px;">
        <div class="user-photo-circle" style="width: 70px; height: 70px; border-radius: 50%; display: flex; justify-content: center; align-items: center; background-color: red;">
            <?php 
            // Get the author ID
            $author_id = get_post_field('post_author', get_the_ID());
            // Get the author's photo URL
            $user_photo_url = get_user_meta($author_id, 'profile_picture', true); // Asegúrate de que esta clave sea correcta

            if ($user_photo_url) {
                // If the author has a profile photo, display it
                echo '<img src="' . esc_url($user_photo_url) . '" alt="Profile Photo" style="width: 100%; height: 100%; border-radius: 50%;">';
            } else {
                // Otherwise, display the initial
                $first_name = get_the_author_meta('first_name', $author_id);
                echo '<span style="color: white; font-size: 24px;">' . strtoupper(substr($first_name, 0, 1)) . '</span>'; // Display the first letter of the first name
            }
            ?>
        </div>
    </div>
    <div style="flex: 1;">
        <h2 style="margin: 0; font-size: 24px; text-align: left;">
            <?php 
            $first_name = get_the_author_meta('first_name', $author_id);
            $last_name = get_the_author_meta('last_name', $author_id);
            echo esc_html($first_name . ' ' . $last_name); // Mostrar el nombre del autor
            ?>
        </h2>
        <p style="margin: 5px 0;"><?php echo esc_html(get_the_author_meta('description', $author_id)); ?></p>
    </div>
</div>