<?php

if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-course-quiz-helper.php';
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-politeia-course.php';
}

function allow_pending_role_users_access_quiz( $has_access, $post_id, $user_id ) {
    $user = get_userdata( $user_id );
    $user_roles = (array) $user->roles;

    if ( in_array( 'pending', $user_roles ) && is_user_logged_in() ) {
        $has_access = true;
    }
    return $has_access;
}
add_filter( 'learndash_is_course_accessable', 'allow_pending_role_users_access_quiz', 10, 3 );

// Add a button "Ir al Curso" after the product name on the order-received page
add_action('woocommerce_order_item_meta_end', 'add_course_button_after_product_name', 10, 3);

function add_course_button_after_product_name($item_id, $item, $order) {
    $product_id = $item->get_product_id();
    $course_meta = get_post_meta($product_id, '_related_course', true);

    if (is_serialized($course_meta)) {
        $course_meta = unserialize($course_meta);
    }

    if (is_array($course_meta) && isset($course_meta[0])) {
        $course_id = $course_meta[0];
    } else {
        $course_id = $course_meta;
    }

    if (!empty($course_id) && is_numeric($course_id)) {
        $course_url = get_permalink($course_id);
        echo '<br><a href="' . esc_url($course_url) . '" class="button" style="display: inline-block; margin-top: 10px; padding: 5px 10px; background-color: black; color: #fff; text-decoration: none; border-radius: 3px; font-size: 12px;">Ir al Curso</a>';
    }
}

/* PASAR A CHECKOUT INMEDIATAMENTE (solo para cursos) */
function custom_redirect_to_checkout() {
    if (is_product() || is_shop()) {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $(document.body).on('added_to_cart', function() {
                    window.location.href = '<?php echo esc_url(wc_get_checkout_url()); ?>';
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_redirect_to_checkout');

function non_ajax_redirect_to_checkout($url) {
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

    $first_quiz_id = PoliteiaCourse::getFirstQuizId( $course_id );
    $final_quiz_id = PoliteiaCourse::getFinalQuizId( $course_id );
    if (!$first_quiz_id || !$final_quiz_id) return;

    $first_attempt = $wpdb->get_var($wpdb->prepare(
        "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz' 
         AND activity_completed IS NOT NULL 
         ORDER BY activity_completed DESC LIMIT 1",
        $user_id,
        $first_quiz_id
    ));

    $final_attempt = $wpdb->get_var($wpdb->prepare(
        "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz' 
         AND activity_completed IS NOT NULL 
         ORDER BY activity_completed DESC LIMIT 1",
        $user_id,
        $final_quiz_id
    ));

    if ($first_attempt && $final_attempt) {
        echo '<a class="ver-resultados-btn" data-course-id="' . esc_attr($course_id) . '" href="#">VER RESULTADOS</a>';
    }
}

add_action( 'wp_enqueue_scripts', 'villegas_enqueue_ajax_globals' );
function villegas_enqueue_ajax_globals() {
    wp_register_script( 'villegas-ajax-globals', '', [], false, true );
    wp_enqueue_script( 'villegas-ajax-globals' );

    $ajax_data = [
        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        'activityNonce'=> wp_create_nonce( 'politeia_quiz_activity' ),
        'resultsNonce' => wp_create_nonce( 'mostrar_resultados_curso' ),
        'privacyNonce' => wp_create_nonce( 'guardar_privacidad_puntaje' ),
        'retryAfter'   => 5,
    ];
    wp_localize_script( 'villegas-ajax-globals', 'villegasAjax', $ajax_data );
}

/* INYECTAR EVALUACON CATEGORIAS A QUIZ (Primera o Final) */
add_action('wp_footer', 'villegas_inyectar_quiz_data_footer');
function villegas_inyectar_quiz_data_footer() {
    if (!is_singular('sfwd-quiz')) return;
    global $post;

    $quiz_id = $post->ID;
    $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_id );
    $course_title = $course_id ? get_the_title($course_id) : '';
    $current_user_id = get_current_user_id();

    $type = 'unknown';
    if ( $course_id ) {
        if ( $quiz_id === PoliteiaCourse::getFirstQuizId( $course_id ) ) {
            $type = 'first';
        } elseif ( $quiz_id === PoliteiaCourse::getFinalQuizId( $course_id ) ) {
            $type = 'final';
        }
    }
    ?>
    <script>
    window.quizData = Object.assign(window.quizData || {}, {
        courseName: <?php echo json_encode($course_title); ?>,
        type: <?php echo json_encode($type); ?>,
        userId: <?php echo json_encode($current_user_id); ?>,
        quizId: <?php echo json_encode($quiz_id); ?>
    });
    window.villegasAjax = window.villegasAjax || {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>'
    };
    </script>
    <?php
}

/* MENSAJE QUIZ TOMADO */
// (the get_user_quiz_attempts + villegas_mensaje_personalizado_intentos functions remain as you had them)

/**
 * WooCommerce <-> LearnDash helpers
 */
function villegas_get_course_product_id( $course_id ) { /* ... same as before ... */ }
function villegas_user_has_course_access( $course_id, $user_id ) { /* ... same as before ... */ }
function villegas_get_course_progress_percentage( $course_id, $user_id ) { /* ... same as before ... */ }

/**
 * Enforce access rules for First and Final Quizzes.
 */
function villegas_enforce_quiz_access_control() { /* ... same as before ... */ }
add_action( 'template_redirect', 'villegas_enforce_quiz_access_control' );
