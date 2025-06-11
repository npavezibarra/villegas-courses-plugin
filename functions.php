<?php

function allow_pending_role_users_access_quiz( $has_access, $post_id, $user_id ) {
    // Get the user's role(s)
    $user = get_userdata( $user_id );
    $user_roles = (array) $user->roles;

    // Check if the user has the 'pending' role and is logged in
    if ( in_array( 'pending', $user_roles ) && is_user_logged_in() ) {
        // Allow access to the quiz for users with 'pending' role
        $has_access = true;
    }
    
    return $has_access;
}
add_filter( 'learndash_is_course_accessable', 'allow_pending_role_users_access_quiz', 10, 3 );

// Add a button "Ir al Curso" after the product name on the order-received page
add_action('woocommerce_order_item_meta_end', 'add_course_button_after_product_name', 10, 3);

function add_course_button_after_product_name($item_id, $item, $order) {
    // Get the product ID from the order item
    $product_id = $item->get_product_id();

    // Retrieve the course ID associated with this product
    $course_meta = get_post_meta($product_id, '_related_course', true);

    // Check if course_meta is serialized
    if (is_serialized($course_meta)) {
        $course_meta = unserialize($course_meta);
    }

    // If course_meta is an array, get the first item (assuming one course)
    if (is_array($course_meta) && isset($course_meta[0])) {
        $course_id = $course_meta[0];
    } else {
        $course_id = $course_meta;
    }

    // Check if a valid course ID was retrieved and generate the button if it exists
    if (!empty($course_id) && is_numeric($course_id)) {
        // Generate the course URL
        $course_url = get_permalink($course_id);

        // Display the button with the course URL
        echo '<br><a href="' . esc_url($course_url) . '" class="button" style="display: inline-block; margin-top: 10px; padding: 5px 10px; background-color: black; color: #fff; text-decoration: none; border-radius: 3px; font-size: 12px;">Ir al Curso</a>';
    }
}

/* PASAR A CHECKOUT INMEDIATAMENTE (solo para cursos) */

function custom_redirect_to_checkout() {
    if (is_product() || is_shop()) {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                // Listen for the WooCommerce AJAX add to cart event
                $(document.body).on('added_to_cart', function() {
                    // Redirect to checkout page
                    window.location.href = '<?php echo esc_url(wc_get_checkout_url()); ?>';
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_redirect_to_checkout');

// Redirect to checkout for non-AJAX add to cart (for single product pages)
function non_ajax_redirect_to_checkout($url) {
    // Ensure we are on the front end and not in the admin area
    if (!is_admin()) {
        $url = wc_get_checkout_url();
    }
    return $url;
}
add_filter('woocommerce_add_to_cart_redirect', 'non_ajax_redirect_to_checkout');


/* FUNCION RESULTADOS CURSO QUIZ */

function villegas_show_resultados_button($course_id, $user_id) {
    global $wpdb;

    if (!$course_id || !$user_id) return;

    // Obtener IDs de quizzes
    $first_quiz_id = get_post_meta($course_id, '_first_quiz_id', true);
    $final_quiz_id = 0;
    $quiz_steps = learndash_course_get_steps_by_type($course_id, 'sfwd-quiz');
    if (!empty($quiz_steps)) {
        $final_quiz_id = end($quiz_steps);
    }

    if (!$first_quiz_id || !$final_quiz_id) return;

    // Revisar si el usuario completó el First Quiz
    $first_attempt = $wpdb->get_var($wpdb->prepare(
        "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz' 
         AND activity_completed IS NOT NULL 
         ORDER BY activity_completed DESC LIMIT 1",
        $user_id,
        $first_quiz_id
    ));

    // Revisar si el usuario completó el Final Quiz
    $final_attempt = $wpdb->get_var($wpdb->prepare(
        "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz' 
         AND activity_completed IS NOT NULL 
         ORDER BY activity_completed DESC LIMIT 1",
        $user_id,
        $final_quiz_id
    ));

    // Si ambos existen, mostrar el botón RESULTADOS
    if ($first_attempt && $final_attempt) {
        // URL a la página de resultados
        $resultados_url = home_url('/resultados/?course_id=' . $course_id);
        echo '<a class="ver-resultados-btn" data-course-id="' . esc_attr($course_id) . '" href="#">VER RESULTADOS</a>';
    }
}

add_action('wp_enqueue_scripts', function() {
    wp_localize_script('custom-quiz-message', 'ajax_object', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
});

/* INYECTAR EVALUACON CATEGORIAS A QUIZ (PRimera o Final) */

add_action('wp_footer', 'villegas_inyectar_quiz_data_footer');

function villegas_inyectar_quiz_data_footer() {
    if (!is_singular('sfwd-quiz')) return; // Asegurarse que estamos en una página de quiz

    global $post;

    $quiz_id = $post->ID;
    $course_id = learndash_get_course_id($quiz_id);
    $course_title = get_the_title($course_id);
    $current_user_id = get_current_user_id(); // <-- Obtener el ID del usuario actual

    // Obtener la categoría de evaluación (taxonomía personalizada de LearnDash)
    $terms = wp_get_post_terms($quiz_id, 'ld_quiz_category');
    $type = 'final';

    foreach ($terms as $term) {
        if (strtolower($term->name) === 'primera') {
            $type = 'first';
            break;
        }
    }

    ?>
    <script>
        var quizData = {
            courseName: <?php echo json_encode($course_title); ?>,
            type: <?php echo json_encode($type); ?>,
            userId: <?php echo json_encode($current_user_id); ?>, // <-- Añadir userId
            quizId: <?php echo json_encode($quiz_id); ?> // <-- Añadir quizId
        };
        // Asegurarse de que ajax_object también está disponible si no fue localizado antes
        // (Aunque tu otro código ya lo hace, es buena práctica tener un fallback o comprobar)
        var ajax_object = ajax_object || { ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>' };

    </script>
    <?php
}
// Asegúrate de que esta función esté enganchada correctamente, como ya lo tienes:
// add_action('wp_footer', 'villegas_inyectar_quiz_data_footer');
