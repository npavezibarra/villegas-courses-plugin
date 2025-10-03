<?php

if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-course-quiz-helper.php';
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-politeia-course.php';
}

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
    $first_quiz_id = PoliteiaCourse::getFirstQuizId( $course_id );
    $final_quiz_id = PoliteiaCourse::getFinalQuizId( $course_id );

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

/* INYECTAR EVALUACON CATEGORIAS A QUIZ (PRimera o Final) */

add_action('wp_footer', 'villegas_inyectar_quiz_data_footer');

function villegas_inyectar_quiz_data_footer() {
    if (!is_singular('sfwd-quiz')) return; // Asegurarse que estamos en una página de quiz

    global $post;

    $quiz_id = $post->ID;
    $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_id );
    $course_title = $course_id ? get_the_title($course_id) : '';
    $current_user_id = get_current_user_id(); // <-- Obtener el ID del usuario actual

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

    // Fallback para villegasAjax si no ha sido definido en otro lugar
    window.villegasAjax = window.villegasAjax || {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>'
    };
</script>

    <?php
}
// Asegúrate de que esta función esté enganchada correctamente, como ya lo tienes:
// add_action('wp_footer', 'villegas_inyectar_quiz_data_footer');


/* MENSAJE QUIZ TOMADO */

// Función auxiliar para contar intentos
function get_user_quiz_attempts($user_id, $quiz_id) {
    $attempts_count = 0;
    $usermeta = get_user_meta($user_id, '_sfwd-quizzes', true);

    if (!empty($usermeta)) {
        $usermeta = maybe_unserialize($usermeta);
        if (!is_array($usermeta)) $usermeta = [];

        foreach ($usermeta as $attempt) {
            if (isset($attempt['quiz']) && intval($attempt['quiz']) === intval($quiz_id)) {
                $attempts_count++;
            }
        }
    }

    return $attempts_count;
}

// Hook de mensaje de advertencia
add_action('learndash-quiz-attempts-alert-before', 'villegas_mensaje_personalizado_intentos', 10, 3);

function villegas_mensaje_personalizado_intentos($quiz_id, $ignored, $user_id) {
    // Obtener intentos del usuario
    $usermeta = get_user_meta($user_id, '_sfwd-quizzes', true);
    $usermeta = maybe_unserialize($usermeta);
    if (!is_array($usermeta)) $usermeta = [];

    // Buscar el último intento
    $ultimo_attempt = null;
    foreach ($usermeta as $attempt) {
        if (isset($attempt['quiz']) && intval($attempt['quiz']) === intval($quiz_id)) {
            if (!isset($ultimo_attempt) || $attempt['time'] > $ultimo_attempt['time']) {
                $ultimo_attempt = $attempt;
            }
        }
    }

    if (!$ultimo_attempt) return;

    // Variables base
    $fecha_rendida = date_i18n('j \d\e F \d\e Y', $ultimo_attempt['time']);
    $porcentaje = isset($ultimo_attempt['percentage']) ? round($ultimo_attempt['percentage']) : 0;
    $dias_pasados = floor((time() - $ultimo_attempt['time']) / (60 * 60 * 24));
    $dias_faltantes = max(0, 15 - $dias_pasados);
    $puede_reiniciar = $dias_pasados >= 15;

    // Obtener nombre del quiz actual
    $quiz_actual_nombre = get_the_title($quiz_id);

    global $wpdb;

    // Obtener Course ID desde el metacampo _first_quiz_id (solo funciona si el quiz es First)
    $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_id );
    $first_quiz_id = $course_id ? PoliteiaCourse::getFirstQuizId( $course_id ) : 0;

    if ( ! $course_id || $quiz_id !== $first_quiz_id ) {
        return;
    }

    $quiz_final_id = PoliteiaCourse::getFinalQuizId( $course_id );
    $quiz_final_nombre = 'Prueba Final';

    if ( $quiz_final_id ) {
        $quiz_final_nombre = get_the_title( $quiz_final_id );
    }
    
    /* DEBUG VISUAL
    //echo '<div style="background: #f3f3f3; padding: 10px; font-size: 14px; border: 1px solid #ccc; margin: 20px 0;">';
    echo '<strong>[DEBUG]</strong><br>';
    echo 'Quiz actual ID: ' . esc_html($quiz_id) . '<br>';
    echo 'Course ID detectado: ' . esc_html($course_id) . '<br>';
    echo 'First Quiz ID: ' . esc_html($first_quiz_id) . '<br>';
    echo 'Final Quiz ID detectado: ' . esc_html($quiz_final_id) . '<br>';
    echo 'Final Quiz Nombre: ' . esc_html($quiz_final_nombre) . '<br>';
    echo '</div>'; */
    
    
    // Mostrar bloque
    echo '<div style="margin: 20px 0; padding: 20px;">';

    // Fecha
    echo '<p><strong>Has rendido esta prueba el ' . esc_html($fecha_rendida) . '.</strong></p>';

    // Barra de progreso
    echo '
    <div style="margin: 10px auto 4px auto; background: #ddd; height: 20px; width: 80%; border-radius: 25px; overflow: hidden;">
        <div style="height: 100%; width: ' . esc_attr($porcentaje) . '%; background: #ff9800;"></div>
    </div>
    <p style="margin: 0; font-weight: bold; text-align: center;">' . esc_html($porcentaje) . '% respuestas correctas.</p>';

    // Explicación dinámica
    echo '<p>Este curso contiene dos evaluaciones: una <strong>' . esc_html($quiz_actual_nombre) . '</strong>, que ya completaste, y una <strong>' . esc_html($quiz_final_nombre) . '</strong>, que solo podrás rendir después de finalizar todas las lecciones.</p>';

    echo '<div id="reiniciar-quiz">';
    // Condición: días restantes o botón
    if ($puede_reiniciar) {
        echo '<button class="reiniciar-quiz-btn" data-quiz-id="' . esc_attr($quiz_id) . '" style="display:inline-block; margin-top:10px; padding:10px 20px; background:#f1c40f; color:#000; font-weight:bold; border-radius:5px; border:none; cursor:pointer;">
            Reiniciar curso
        </button>
        <div id="reiniciar-quiz-msg" style="margin-top:10px;"></div>';
    } else {
        echo '<h3 style="color: #ff9800; font-size: 18px; font-weight: bold;">Podrás reiniciar el curso en ' . esc_html($dias_faltantes) . ' día(s).</h3>';
    }

    // Consecuencias del reinicio
    echo '<p>Si decides <strong>reiniciar</strong>, se borrarán:</p>
            <p>Los resultados de esta evaluación.</p>
            <p>Tu avance en las lecciones.</p>
            <p>Tu acceso a la Prueba Final (hasta completar todo nuevamente).</p>';

    echo '</div>';
    echo '</div>';
}

/**
 * Resolve the WooCommerce product associated with a LearnDash course.
 *
 * @param int $course_id Course post ID.
 *
 * @return int Product post ID or 0 when none can be found.
 */
function villegas_get_course_product_id( $course_id ) {
    $course_id  = intval( $course_id );
    $product_id = 0;

    if ( ! $course_id ) {
        return 0;
    }

    $linked_product = get_post_meta( $course_id, '_linked_woocommerce_product', true );
    if ( $linked_product ) {
        $product_id = intval( $linked_product );
    }

    if ( ! $product_id ) {
        $products = get_posts(
            array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_related_course',
                        'value'   => $course_id,
                        'compare' => 'LIKE',
                    ),
                ),
            )
        );

        if ( ! empty( $products ) ) {
            $product_id = intval( $products[0] );
        }
    }

    return $product_id;
}

/**
 * Determine if the user has access to a LearnDash course through enrollment or purchase.
 *
 * @param int $course_id Course post ID.
 * @param int $user_id   User ID.
 *
 * @return bool
 */
function villegas_user_has_course_access( $course_id, $user_id ) {
    $course_id = intval( $course_id );
    $user_id   = intval( $user_id );

    if ( ! $course_id || ! $user_id ) {
        return false;
    }

    if ( function_exists( 'sfwd_lms_has_access' ) && sfwd_lms_has_access( $course_id, $user_id ) ) {
        return true;
    }

    $product_id = villegas_get_course_product_id( $course_id );

    if ( $product_id && function_exists( 'wc_customer_bought_product' ) ) {
        $user      = get_userdata( $user_id );
        $user_mail = $user ? $user->user_email : '';

        if ( $user_mail && wc_customer_bought_product( $user_mail, $user_id, $product_id ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Retrieve the completion percentage for a user in a given course.
 *
 * @param int $course_id Course post ID.
 * @param int $user_id   User ID.
 *
 * @return float Value from 0 to 100.
 */
function villegas_get_course_progress_percentage( $course_id, $user_id ) {
    $course_id = intval( $course_id );
    $user_id   = intval( $user_id );

    if ( ! $course_id || ! $user_id ) {
        return 0;
    }

    if ( function_exists( 'learndash_course_progress' ) ) {
        $progress = learndash_course_progress(
            array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'array' => true,
            )
        );

        if ( is_array( $progress ) && isset( $progress['percentage'] ) ) {
            return floatval( $progress['percentage'] );
        }
    }

    if ( function_exists( 'learndash_user_get_course_progress' ) ) {
        $progress = learndash_user_get_course_progress( $user_id, $course_id );

        if ( is_array( $progress ) && isset( $progress['percentage'] ) ) {
            return floatval( $progress['percentage'] );
        }
    }

    return 0;
}

/**
 * Determine if a Final Quiz is available for a user based on enrollment and completion.
 *
 * @param int $quiz_id Quiz post ID.
 * @param int $user_id User ID.
 *
 * @return bool
 */
function isFinalQuizAccessible( $quiz_id, $user_id ) {
    $quiz_id = intval( $quiz_id );
    $user_id = intval( $user_id );

    if ( ! $quiz_id ) {
        return false;
    }

    $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_id );

    if ( ! $course_id ) {
        return true;
    }

    $final_quiz_id = PoliteiaCourse::getFinalQuizId( $course_id );

    if ( ! $final_quiz_id || intval( $final_quiz_id ) !== $quiz_id ) {
        return true;
    }

    if ( ! $user_id ) {
        return false;
    }

    if ( ! villegas_user_has_course_access( $course_id, $user_id ) ) {
        return false;
    }

    $percentage = villegas_get_course_progress_percentage( $course_id, $user_id );

    return $percentage >= 100;
}

/**
 * Enforce access rules for First and Final Quizzes.
 */
function villegas_enforce_quiz_access_control() {
    if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        return;
    }

    if ( ! is_singular( 'sfwd-quiz' ) ) {
        return;
    }

    global $post;

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $quiz_id   = intval( $post->ID );
    $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_id );

    if ( ! $course_id ) {
        return;
    }

    $first_quiz_id = PoliteiaCourse::getFirstQuizId( $course_id );
    $final_quiz_id = PoliteiaCourse::getFinalQuizId( $course_id );

    if ( $first_quiz_id && $quiz_id === intval( $first_quiz_id ) ) {
        if ( is_user_logged_in() ) {
            return;
        }

        $login_url    = wp_login_url( get_permalink( $quiz_id ) );
        $register_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_registration_url();

        $message  = '<div class="villegas-quiz-gate villegas-quiz-gate--login">';
        $message .= '<h2>' . esc_html__( 'Inicia sesión para continuar', 'villegas-courses' ) . '</h2>';
        $message .= '<p>' . esc_html__( 'Debes iniciar sesión o crear una cuenta para rendir la Prueba Inicial.', 'villegas-courses' ) . '</p>';
        $message .= '<p><a class="button" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Iniciar sesión', 'villegas-courses' ) . '</a>';

        if ( $register_url ) {
            $message .= ' <a class="button button-primary" href="' . esc_url( $register_url ) . '">' . esc_html__( 'Crear cuenta', 'villegas-courses' ) . '</a>';
        }

        $message .= '</p></div>';

        wp_die( wp_kses_post( $message ), esc_html__( 'Acceso restringido', 'villegas-courses' ), array( 'response' => 401 ) );
    }

    if ( ! $final_quiz_id || $quiz_id !== intval( $final_quiz_id ) ) {
        return;
    }

    $user_id      = get_current_user_id();
    $has_access   = isFinalQuizAccessible( $quiz_id, $user_id );
    $is_enrolled  = $user_id ? villegas_user_has_course_access( $course_id, $user_id ) : false;
    $progress     = $user_id ? villegas_get_course_progress_percentage( $course_id, $user_id ) : 0;
    $course_title = get_the_title( $course_id );

    if ( $has_access ) {
        return;
    }

    if ( ! $user_id ) {
        $login_url = wp_login_url( get_permalink( $quiz_id ) );

        $message  = '<div class="villegas-quiz-gate villegas-quiz-gate--login">';
        $message .= '<h2>' . esc_html__( 'Inicia sesión para continuar', 'villegas-courses' ) . '</h2>';
        $message .= '<p>' . esc_html__( 'Debes iniciar sesión con tu cuenta para acceder a la Prueba Final.', 'villegas-courses' ) . '</p>';
        $message .= '<p><a class="button" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Iniciar sesión', 'villegas-courses' ) . '</a></p>';
        $message .= '</div>';

        wp_die( wp_kses_post( $message ), esc_html__( 'Prueba Final bloqueada', 'villegas-courses' ), array( 'response' => 403 ) );
    }

    if ( ! $is_enrolled ) {
        $product_id = villegas_get_course_product_id( $course_id );
        $cta_url    = $product_id ? get_permalink( $product_id ) : get_permalink( $course_id );

        $message  = '<div class="villegas-quiz-gate villegas-quiz-gate--purchase">';
        $message .= '<h2>' . esc_html__( 'Purchase the course to unlock the Final Quiz', 'villegas-courses' ) . '</h2>';
        $message .= '<p>' . sprintf( esc_html__( 'Compra el curso %s para acceder a la Prueba Final.', 'villegas-courses' ), esc_html( $course_title ) ) . '</p>';
        $message .= '<p><a class="button button-primary" href="' . esc_url( $cta_url ) . '">' . esc_html__( 'Comprar curso', 'villegas-courses' ) . '</a></p>';
        $message .= '</div>';

        wp_die( wp_kses_post( $message ), esc_html__( 'Prueba Final bloqueada', 'villegas-courses' ), array( 'response' => 403 ) );
    }

    if ( $progress < 100 ) {
        $course_url = get_permalink( $course_id );

        $message  = '<div class="villegas-quiz-gate villegas-quiz-gate--progress">';
        $message .= '<h2>' . esc_html__( 'Complete all lessons to unlock the Final Quiz', 'villegas-courses' ) . '</h2>';
        $message .= '<p>' . esc_html__( 'Debes completar el 100% de las lecciones antes de rendir la Prueba Final.', 'villegas-courses' ) . '</p>';
        $message .= '<p><a class="button" href="' . esc_url( $course_url ) . '">' . esc_html__( 'Volver al curso', 'villegas-courses' ) . '</a></p>';
        $message .= '</div>';

        wp_die( wp_kses_post( $message ), esc_html__( 'Prueba Final bloqueada', 'villegas-courses' ), array( 'response' => 403 ) );
    }
}

add_action( 'template_redirect', 'villegas_enforce_quiz_access_control' );

// Register AJAX endpoint for logged-in and guest users.
add_action( 'wp_ajax_villegas_get_latest_quiz_result', 'villegas_get_latest_quiz_result' );
add_action( 'wp_ajax_nopriv_villegas_get_latest_quiz_result', 'villegas_get_latest_quiz_result' );

function villegas_get_latest_quiz_result() {
    global $wpdb;

    $user_id = get_current_user_id();
    $quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0;

    if ( ! $user_id || ! $quiz_id ) {
        wp_send_json_error( [ 'message' => 'Missing parameters' ] );
    }

    $activity = $wpdb->get_row( $wpdb->prepare(
        "SELECT ua.activity_id, ua.activity_completed
         FROM {$wpdb->prefix}learndash_user_activity AS ua
         INNER JOIN {$wpdb->prefix}learndash_user_activity_meta AS uam
           ON ua.activity_id = uam.activity_id
         WHERE ua.user_id = %d
           AND uam.activity_meta_key = 'quiz'
           AND uam.activity_meta_value+0 = %d
           AND ua.activity_type = 'quiz'
           AND ua.activity_completed IS NOT NULL
         ORDER BY ua.activity_id DESC
         LIMIT 1",
        $user_id,
        $quiz_id
    ) );

    if ( ! $activity ) {
        wp_send_json_error( [ 'message' => 'No attempt found' ] );
    }

    $meta = $wpdb->get_results( $wpdb->prepare(
        "SELECT activity_meta_key, activity_meta_value
         FROM {$wpdb->prefix}learndash_user_activity_meta
         WHERE activity_id = %d",
        $activity->activity_id
    ), OBJECT_K );

    $percentage_value = isset( $meta['percentage'] )
        ? round( floatval( $meta['percentage']->activity_meta_value ), 2 )
        : null;
    $score_value = isset( $meta['score'] ) ? intval( $meta['score']->activity_meta_value ) : null;
    $total_points_value = isset( $meta['total_points'] )
        ? intval( $meta['total_points']->activity_meta_value )
        : null;

    $timestamp = intval( $activity->activity_completed );
    $formatted_date = $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : '';

    $response = [
        'activity_id'        => intval( $activity->activity_id ),
        'completed'          => $timestamp,
        'timestamp'          => $timestamp,
        'formatted_date'     => $formatted_date,
        'percentage'         => $percentage_value,
        'percentage_rounded' => is_null( $percentage_value ) ? null : round( $percentage_value ),
        'score'              => $score_value,
        'total_points'       => $total_points_value,
        'status'             => is_null( $percentage_value ) ? 'pending' : 'ready',
    ];

    wp_send_json_success( $response );
}

