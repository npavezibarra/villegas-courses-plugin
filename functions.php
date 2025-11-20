<?php

if ( ! defined( 'VILLEGAS_COURSES_PLUGIN_FILE' ) ) {
    define( 'VILLEGAS_COURSES_PLUGIN_FILE', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';

if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-course-quiz-helper.php';
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-politeia-course.php';
}

if ( ! class_exists( 'Villegas_Course' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-villegas-course.php';
}

require_once plugin_dir_path( __FILE__ ) . 'includes/emails.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/first-quiz-email-ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/first-quiz-email-rendered.php';

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

/**
 * Retrieve the percentage value from the latest LearnDash quiz attempt for a user.
 *
 * @param int $user_id WordPress user ID.
 * @param int $quiz_id LearnDash quiz post ID.
 *
 * @return int|null Percentage (0-100) or null when no attempt exists.
 */
function villegas_get_last_quiz_percentage( $user_id, $quiz_id ) {
    $user_id = intval( $user_id );
    $quiz_id = intval( $quiz_id );

    if ( ! $user_id || ! $quiz_id ) {
        return null;
    }

    global $wpdb;

    $activity_table     = $wpdb->prefix . 'learndash_user_activity';
    $activity_meta_table = $wpdb->prefix . 'learndash_user_activity_meta';

    $activity_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT activity_id FROM {$activity_table} WHERE user_id = %d AND post_id = %d AND activity_type = %s ORDER BY activity_completed DESC LIMIT 1",
            $user_id,
            $quiz_id,
            'quiz'
        )
    );

    if ( ! $activity_id ) {
        return null;
    }

    $percentage = learndash_get_user_activity_meta( $activity_id, 'percentage', true );

    if ( '' === $percentage || null === $percentage ) {
        $percentage = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT activity_meta_value FROM {$activity_meta_table} WHERE activity_id = %d AND activity_meta_key = %s LIMIT 1",
                $activity_id,
                'percentage'
            )
        );
    }

    if ( '' === $percentage || null === $percentage ) {
        return null;
    }

    $percentage = (float) $percentage;
    $percentage = max( 0, min( 100, $percentage ) );

    return villegas_round_half_up( $percentage );
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
    $porcentaje = isset($ultimo_attempt['percentage']) ? villegas_round_half_up($ultimo_attempt['percentage']) : 0;
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
 * Check whether a WooCommerce product exists and is usable.
 *
 * @param int $product_id Product post ID.
 *
 * @return bool
 */
function villegas_course_product_exists( $product_id ) {
    $product_id = intval( $product_id );

    if ( ! $product_id ) {
        return false;
    }

    $product = get_post( $product_id );

    if ( ! $product || 'product' !== $product->post_type ) {
        return false;
    }

    $status = get_post_status( $product_id );

    return ! in_array( $status, array( 'trash', 'auto-draft' ), true );
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

    if ( $product_id && ! villegas_course_product_exists( $product_id ) ) {
        delete_post_meta( $course_id, '_linked_woocommerce_product' );
        delete_post_meta( $course_id, '_related_product' );
        $product_id = 0;
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

    if ( $product_id && ! villegas_course_product_exists( $product_id ) ) {
        $product_id = 0;
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
 * Recursively collect lesson IDs from a serialized LearnDash course steps tree.
 *
 * @param mixed $steps Course steps structure.
 *
 * @return array<int>
 */
function villegas_collect_lesson_ids_from_ld_steps( $steps ) {
    if ( ! is_array( $steps ) ) {
        return [];
    }

    $lesson_ids = [];

    foreach ( $steps as $key => $value ) {
        if ( 'sfwd-lessons' === $key && is_array( $value ) ) {
            foreach ( $value as $lesson_id => $lesson_children ) {
                if ( is_numeric( $lesson_id ) ) {
                    $lesson_ids[] = intval( $lesson_id );
                }
            }
        } elseif ( is_array( $value ) ) {
            $lesson_ids = array_merge( $lesson_ids, villegas_collect_lesson_ids_from_ld_steps( $value ) );
        } elseif ( is_numeric( $value ) && 'sfwd-lessons' === get_post_type( $value ) ) {
            $lesson_ids[] = intval( $value );
        }
    }

    return $lesson_ids;
}

/**
 * Retrieve the list of lesson IDs associated with a LearnDash course.
 *
 * @param int $course_id Course post ID.
 *
 * @return array<int>
 */
function villegas_get_course_lesson_ids( $course_id ) {
    $course_id = intval( $course_id );

    if ( ! $course_id ) {
        return [];
    }

    static $cache = [];

    if ( isset( $cache[ $course_id ] ) ) {
        return $cache[ $course_id ];
    }

    $lesson_ids = [];

    if ( function_exists( 'learndash_get_course_steps' ) ) {
        $course_steps = learndash_get_course_steps( $course_id );

        if ( is_object( $course_steps ) ) {
            $lesson_steps = [];

            if ( method_exists( $course_steps, 'get_steps' ) ) {
                $lesson_steps = $course_steps->get_steps( 'sfwd-lessons' );
            } elseif ( method_exists( $course_steps, 'get_steps_by_type' ) ) {
                $lesson_steps = $course_steps->get_steps_by_type( 'sfwd-lessons' );
            }

            if ( is_array( $lesson_steps ) ) {
                foreach ( $lesson_steps as $lesson_id => $children ) {
                    if ( is_numeric( $lesson_id ) ) {
                        $lesson_ids[] = intval( $lesson_id );
                    }
                }
            }
        } elseif ( is_array( $course_steps ) ) {
            if ( isset( $course_steps['sfwd-lessons'] ) && is_array( $course_steps['sfwd-lessons'] ) ) {
                foreach ( $course_steps['sfwd-lessons'] as $lesson_id => $children ) {
                    if ( is_numeric( $lesson_id ) ) {
                        $lesson_ids[] = intval( $lesson_id );
                    }
                }
            } elseif ( isset( $course_steps['steps']['sfwd-lessons'] ) && is_array( $course_steps['steps']['sfwd-lessons'] ) ) {
                foreach ( $course_steps['steps']['sfwd-lessons'] as $lesson_id => $children ) {
                    if ( is_numeric( $lesson_id ) ) {
                        $lesson_ids[] = intval( $lesson_id );
                    }
                }
            } else {
                foreach ( $course_steps as $step_id ) {
                    if ( is_numeric( $step_id ) && 'sfwd-lessons' === get_post_type( $step_id ) ) {
                        $lesson_ids[] = intval( $step_id );
                    } elseif ( is_array( $step_id ) ) {
                        $lesson_ids = array_merge( $lesson_ids, villegas_collect_lesson_ids_from_ld_steps( $step_id ) );
                    }
                }
            }
        }
    }

    if ( empty( $lesson_ids ) ) {
        $raw_steps = get_post_meta( $course_id, 'ld_course_steps', true );

        if ( ! empty( $raw_steps ) ) {
            $parsed_steps = maybe_unserialize( $raw_steps );
            $lesson_ids   = villegas_collect_lesson_ids_from_ld_steps( $parsed_steps );
        }
    }

    $lesson_ids = array_values(
        array_unique(
            array_filter(
                array_map( 'intval', $lesson_ids ),
                static function ( $lesson_id ) {
                    return $lesson_id > 0;
                }
            )
        )
    );

    $cache[ $course_id ] = $lesson_ids;

    return $lesson_ids;
}

/**
 * Calculate the lesson completion totals for a course/user pair.
 *
 * @param int $course_id Course post ID.
 * @param int $user_id   User ID.
 *
 * @return array{lesson_ids:array<int>,total:int,completed:int}
 */
function villegas_calculate_course_lesson_stats( $course_id, $user_id = 0 ) {
    $course_id = intval( $course_id );
    $user_id   = intval( $user_id );

    $default = [
        'lesson_ids' => [],
        'total'      => 0,
        'completed'  => 0,
    ];

    if ( ! $course_id ) {
        return $default;
    }

    static $cache = [];
    $cache_key    = $course_id . '|' . $user_id;

    if ( isset( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }

    $lesson_ids    = villegas_get_course_lesson_ids( $course_id );
    $total_lessons = count( $lesson_ids );
    $completed     = 0;

    if ( $user_id && $total_lessons > 0 ) {
        if ( function_exists( 'learndash_is_lesson_complete' ) ) {
            foreach ( $lesson_ids as $lesson_id ) {
                if ( learndash_is_lesson_complete( $user_id, $lesson_id, $course_id ) ) {
                    $completed++;
                }
            }
        } elseif ( function_exists( 'learndash_course_progress' ) ) {
            $progress = learndash_course_progress(
                [
                    'user_id'   => $user_id,
                    'course_id' => $course_id,
                    'array'     => true,
                ]
            );

            if ( is_array( $progress ) && isset( $progress['posts']['sfwd-lessons']['completed'] ) && is_array( $progress['posts']['sfwd-lessons']['completed'] ) ) {
                $completed = count( $progress['posts']['sfwd-lessons']['completed'] );
            }
        }
    }

    $summary = [
        'lesson_ids' => $lesson_ids,
        'total'      => $total_lessons,
        'completed'  => $completed,
    ];

    $cache[ $cache_key ] = $summary;

    return $summary;
}

/**
 * Determine if a user can take the Final Quiz for a course.
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course post ID.
 *
 * @return bool
 */
function villegas_can_user_take_final_quiz( $user_id, $course_id ) {
    $course_id = intval( $course_id );
    $user_id   = intval( $user_id );

    if ( ! $course_id || ! $user_id ) {
        return false;
    }

    static $cache = [];
    $cache_key    = $course_id . '|' . $user_id;

    if ( isset( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }

    $stats             = villegas_calculate_course_lesson_stats( $course_id, $user_id );
    $total_lessons     = isset( $stats['total'] ) ? intval( $stats['total'] ) : 0;
    $completed_lessons = isset( $stats['completed'] ) ? intval( $stats['completed'] ) : 0;

    $can_take = ( $total_lessons > 0 && $completed_lessons >= $total_lessons );

    $cache[ $cache_key ] = $can_take;

    return $can_take;
}

/**
 * Summarize a user's lesson completion status for a course.
 *
 * @param int $course_id Course post ID.
 * @param int $user_id   User ID.
 *
 * @return array{lesson_ids:array<int>,total:int,completed:int,can_take_final_quiz:bool}
 */
function villegas_get_course_lesson_progress( $course_id, $user_id = 0 ) {
    $course_id = intval( $course_id );
    $user_id   = intval( $user_id );

    $stats = villegas_calculate_course_lesson_stats( $course_id, $user_id );

    $summary = [
        'lesson_ids'          => isset( $stats['lesson_ids'] ) ? (array) $stats['lesson_ids'] : [],
        'total'               => isset( $stats['total'] ) ? intval( $stats['total'] ) : 0,
        'completed'           => isset( $stats['completed'] ) ? intval( $stats['completed'] ) : 0,
        'can_take_final_quiz' => false,
    ];

    if ( $course_id && $user_id ) {
        $summary['can_take_final_quiz'] = villegas_can_user_take_final_quiz( $user_id, $course_id );
    }

    return $summary;
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
            return villegas_normalize_course_progress_percentage( $progress['percentage'] );
        }
    }

    if ( function_exists( 'learndash_user_get_course_progress' ) ) {
        $progress = learndash_user_get_course_progress( $user_id, $course_id );

        if ( is_array( $progress ) && isset( $progress['percentage'] ) ) {
            return villegas_normalize_course_progress_percentage( $progress['percentage'] );
        }
    }

    return 0;
}

/**
 * Retrieve the canonical permalink for a quiz without prepending the course URL.
 *
 * @param int $quiz_id Quiz post ID.
 *
 * @return string Quiz permalink or empty string when unavailable.
 */
function villegas_get_quiz_canonical_permalink( $quiz_id ) {
    $quiz_id = intval( $quiz_id );

    if ( ! $quiz_id ) {
        return '';
    }

    $permalink = get_permalink( $quiz_id );

    if ( ! $permalink ) {
        $quiz_post = get_post( $quiz_id );

        if ( $quiz_post instanceof WP_Post ) {
            $permalink = home_url( '/evaluaciones/' . $quiz_post->post_name . '/' );
        }
    }

    return $permalink ? esc_url_raw( $permalink ) : '';
}

/**
 * Normalize raw LearnDash course progress into a capped percentage.
 *
 * This prevents floating point quirks (e.g. 99.999999) from blocking 100 % access checks.
 *
 * @param mixed $percentage Raw percentage value returned by LearnDash helpers.
 *
 * @return float
 */
function villegas_normalize_course_progress_percentage( $percentage ) {
    $value = floatval( $percentage );

    if ( $value >= 99.5 ) {
        return 100.0;
    }

    if ( $value <= 0 ) {
        return 0.0;
    }

    return min( 100.0, $value );
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

    return villegas_can_user_take_final_quiz( $user_id, $course_id );
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
    $course_title = get_the_title( $course_id );
    $is_enrolled  = $user_id ? villegas_user_has_course_access( $course_id, $user_id ) : false;

    $lesson_stats       = villegas_calculate_course_lesson_stats( $course_id, $user_id );
    $total_lessons      = isset( $lesson_stats['total'] ) ? intval( $lesson_stats['total'] ) : 0;
    $lessons_completed  = isset( $lesson_stats['completed'] ) ? intval( $lesson_stats['completed'] ) : 0;
    $can_take_final_quiz = villegas_can_user_take_final_quiz( $user_id, $course_id );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log(
            sprintf(
                'Final Quiz gate debug -> course %d, quiz %d: total_lessons=%d, lessons_completed=%d, can_take_final_quiz=%s',
                $course_id,
                $final_quiz_id,
                $total_lessons,
                $lessons_completed,
                $can_take_final_quiz ? 'true' : 'false'
            )
        );
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

    if ( $can_take_final_quiz && $is_enrolled ) {
        return;
    }

    if ( ! $can_take_final_quiz ) {
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

/**
 * Retrieve all WooCommerce products assigned to a given user through the
 * `_product_assigned_authors` meta key.
 *
 * @param int $user_id User ID.
 *
 * @return WP_Post[]
 */
function villegas_get_user_books( $user_id ) {
    $pattern = 'i:' . intval( $user_id ) . ';';

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => '_product_assigned_authors',
                'value'   => $pattern,
                'compare' => 'LIKE',
            ],
        ],
    ];

    return get_posts( $args );
}

/**
 * Render the books section for the author in the current archive view.
 *
 * @return string
 */
function villegas_render_user_books_section() {
    $author_id = get_queried_object_id();

    if ( ! $author_id ) {
        return '';
    }

    $books = villegas_get_user_books( $author_id );

    ob_start();
    ?>

    <section class="books-section">
        <h2>Libros</h2>
        <div class="books-grid">

            <?php if ( ! empty( $books ) ) : ?>
                <?php foreach ( $books as $book ) :
                    $price     = get_post_meta( $book->ID, '_price', true );
                    $thumbnail = get_the_post_thumbnail_url( $book->ID, 'medium' );

                    if ( ! $thumbnail ) {
                        $thumbnail = 'https://placehold.co/320x480/ededeb/111111?text=Libro';
                    }
                    ?>
                    <article class="book-item">
                        <a href="<?php echo esc_url( get_permalink( $book->ID ) ); ?>">
                            <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $book->post_title ); ?>">
                        </a>
                        <h3>
                            <a href="<?php echo esc_url( get_permalink( $book->ID ) ); ?>">
                                <?php echo esc_html( $book->post_title ); ?>
                            </a>
                        </h3>
                        <p class="book-price">
                            <?php echo wc_price( $price ); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No books assigned to this author.</p>
            <?php endif; ?>

        </div>
    </section>

    <?php
    return ob_get_clean();
}

/**
 * Count how many WooCommerce courses (products) are assigned to a given author.
 *
 * @param int $user_id User ID.
 *
 * @return int
 */
function villegas_count_courses_by_author( $user_id ) {
    global $wpdb;

    $serialized = 'i:' . intval( $user_id ) . ';';

    $query = $wpdb->prepare(
        "SELECT COUNT(*)
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_product_assigned_authors'
        AND meta_value LIKE %s",
        '%' . $wpdb->esc_like( $serialized ) . '%'
    );

    return intval( $wpdb->get_var( $query ) );
}

/**
 * Count how many LearnDash courses are authored by the given user.
 *
 * @param int $author_id Author ID.
 *
 * @return int
 */
function villegas_count_learndash_courses_by_author( $author_id ) {
    $args = [
        'post_type'      => 'sfwd-courses',
        'post_status'    => 'publish',
        'author'         => $author_id,
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ];

    $courses = get_posts( $args );

    return count( $courses );
}

/**
 * Count how many blog posts are authored by the given user.
 *
 * @param int $user_id User ID.
 *
 * @return int
 */
function villegas_count_columns_by_author( $user_id ) {
    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ];

    $posts = get_posts( $args );

    return count( $posts );
}

/**
 * Render a single column item for the author columns list.
 */
function villegas_render_single_column_item() {
    ?>
    <article class="column-item">

        <!-- Thumbnail -->
        <img
            src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ?: 'https://placehold.co/140x140/efefed/1d1d1b?text=No+Image' ); ?>"
            alt="<?php echo esc_attr( get_the_title() ); ?>"
        >

        <div>
            <!-- Post Title -->
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>

            <!-- Post Meta -->
            <p><?php echo esc_html( get_the_date() ); ?> — Columna</p>
        </div>

    </article>
    <?php
}

/**
 * Render the author columns list for AJAX/infinite scroll consumption.
 *
 * @param int $author_id Author ID.
 * @param int $paged     Page number.
 *
 * @return string
 */
function villegas_render_author_columns( $author_id, $paged = 1, $return_data = false ) {
    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'author'         => $author_id,
        'posts_per_page' => 4,
        'paged'          => max( 1, (int) $paged ),
    ];

    $query = new WP_Query( $args );

    ob_start();

    echo '<div class="columns-list">';

    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) :
            $query->the_post();

            villegas_render_single_column_item();

        endwhile;
    else :
        echo '<p>No hay columnas publicadas aún.</p>';
    endif;

    echo '</div>';

    wp_reset_postdata();

    $rendered = ob_get_clean();

    if ( $return_data ) {
        return [
            'html'         => $rendered,
            'current_page' => max( 1, (int) $query->get( 'paged', $paged ) ),
            'max_pages'    => (int) $query->max_num_pages,
        ];
    }

    return $rendered;
}

add_action( 'wp_ajax_villegas_load_author_columns', 'villegas_load_author_columns' );
add_action( 'wp_ajax_nopriv_villegas_load_author_columns', 'villegas_load_author_columns' );

/**
 * AJAX handler to load author columns via pagination.
 */
function villegas_load_author_columns() {
    $author_id = isset( $_POST['author_id'] ) ? intval( $_POST['author_id'] ) : 0;
    $paged     = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;

    echo villegas_render_author_columns( $author_id, $paged );
    wp_die();
}

add_action( 'wp_enqueue_scripts', 'villegas_enqueue_author_columns_script' );

/**
 * Enqueue script for AJAX-based author columns pagination.
 */
function villegas_enqueue_author_columns_script() {
    if ( ! is_page_template( 'templates/author-profile-template.php' ) ) {
        return;
    }

    wp_enqueue_script(
        'villegas-author-infinite-scroll',
        plugin_dir_url( __FILE__ ) . 'assets/js/author-infinite-scroll.js',
        [ 'jquery' ],
        '1.0',
        true
    );

    wp_localize_script(
        'villegas-author-infinite-scroll',
        'villegasColumns',
        [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ]
    );
}

