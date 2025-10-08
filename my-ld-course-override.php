<?php
/**
 * Plugin Name: Villegas Courses Plugin
 * Description: Plugin de customización experiencia cursos en sitio El Villegas.
 * Version: 1.0
 * Author: Nicolás Pavez
 */

if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-course-quiz-helper.php';
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-politeia-course.php';
}

if ( ! class_exists( 'Politeia_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-politeia-quiz-stats.php';
}

if ( ! class_exists( 'Villegas_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-villegas-quiz-stats.php';
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-villegas-quiz-emails.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-villegas-quiz-email-handler.php';

register_activation_hook( __FILE__, function() {
    if ( ! wp_next_scheduled( 'villegas_process_quiz_email_queue' ) ) {
        wp_schedule_event( time() + 300, 'five_minutes', 'villegas_process_quiz_email_queue' );
    }
} );

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'villegas_process_quiz_email_queue' );
} );

add_filter( 'cron_schedules', function( $schedules ) {
    if ( ! isset( $schedules['five_minutes'] ) ) {
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display'  => __( 'Every Five Minutes', 'villegas-courses' ),
        ];
    }

    return $schedules;
} );

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-villegas-quiz-email-settings.php';
    Villegas_Quiz_Email_Settings::init();
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-course-checklist-handler.php';
}

add_action( 'learndash_quiz_completed', [ 'Villegas_Quiz_Email_Handler', 'on_quiz_completed' ], 10, 2 );

// Reemplazar la plantilla del curso de LearnDash
function my_custom_ld_course_template( $template ) {
    if ( is_singular( 'sfwd-courses' ) ) {
        $custom_template = plugin_dir_path( __FILE__ ) . 'templates/single-sfwd-course.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'my_custom_ld_course_template' );

// Reemplaza Archive COurses
function villegas_override_learndash_templates( $template ) {
    if ( is_post_type_archive( 'sfwd-courses' ) ) {
        $custom_template = plugin_dir_path( __FILE__ ) . 'templates/archive-sfwd-courses.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'villegas_override_learndash_templates', 99 );



// Encolar estilo de Course Page
function my_custom_ld_course_styles() {
    // Encolar estilos específicos de la página del curso
    wp_enqueue_style('my-course-page-style', plugin_dir_url(__FILE__) . 'assets/course-page.css', [], '1.0', 'all');
    wp_enqueue_style('ingresa-roma-css', plugin_dir_url(__FILE__) . 'assets/ingresa-roma.css', [], '1.0', 'all');
    wp_enqueue_style('comprar-stats-css', plugin_dir_url(__FILE__) . 'assets/comprar-stats.css', [], '1.0', 'all');
    wp_enqueue_style('my-account-css', plugin_dir_url(__FILE__) . 'assets/my-account.css', [], '1.0', 'all');
    wp_enqueue_style('single-lessons-css', plugin_dir_url(__FILE__) . 'assets/single-lessons.css', [], '1.0', 'all');
    wp_enqueue_style('login-form-styles', plugin_dir_url(__FILE__) . 'login/login-form-styles.css', [], '1.0', 'all');
    wp_enqueue_style('quiz-styles', plugin_dir_url(__FILE__) . 'assets/quiz-styles.css', [], '1.0', 'all');
    wp_enqueue_style('cursos-finalizados', plugin_dir_url(__FILE__) . 'assets/cursos-finalizados.css', [], '1.0', 'all');

    // Encolar estilo solo en la página de cursos
    if (is_post_type_archive('sfwd-courses')) {
        wp_enqueue_style('archive-courses-style', plugin_dir_url(__FILE__) . 'assets/archive-courses.css', [], '1.0', 'all');
    }

    // Verificar si el shortcode [login_register_form] está presente en la página
    global $post;
    if (isset($post->post_content) && has_shortcode($post->post_content, 'login_register_form')) {
        // Encolar estilos específicos para el formulario de login/registro
        wp_enqueue_style('login-register-style', plugin_dir_url(__FILE__) . 'assets/login-register.css', [], '1.0', 'all');
    }
}
add_action('wp_enqueue_scripts', 'my_custom_ld_course_styles');

add_action('wp_enqueue_scripts', 'villegas_enqueue_profile_picture_script');
function villegas_enqueue_profile_picture_script() {
    if (is_account_page()) {
        wp_enqueue_script(
            'villegas-profile-picture',
            plugin_dir_url(__FILE__) . 'assets/js/profile-picture.js',
            [],
            '1.0',
            true
        );
    }
}

/* AJAX PARA RESULTADOS QUIZ */
add_action( 'wp_ajax_mostrar_resultados_curso', 'villegas_ajax_resultados_curso' );
function villegas_ajax_resultados_curso() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'Debes iniciar sesión para ver los resultados.', 'villegas-courses' ) ], 401 );
    }

    if ( ! current_user_can( 'read' ) ) {
        wp_send_json_error( [ 'message' => __( 'No tienes permisos para ver estos resultados.', 'villegas-courses' ) ], 403 );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'mostrar_resultados_curso' ) ) {
        wp_send_json_error( [ 'message' => __( 'Solicitud inválida. Actualiza la página e inténtalo nuevamente.', 'villegas-courses' ) ], 403 );
    }

    $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
    if ( ! $course_id ) {
        wp_send_json_error( [ 'message' => __( 'Curso inválido.', 'villegas-courses' ) ], 400 );
    }

    $user_id = get_current_user_id();

    if ( function_exists( 'villegas_user_has_course_access' ) && ! villegas_user_has_course_access( $course_id, $user_id ) ) {
        wp_send_json_error( [ 'message' => __( 'No cuentas con acceso a este curso.', 'villegas-courses' ) ], 403 );
    }

    $first_quiz_id = PoliteiaCourse::getFirstQuizId( $course_id );
    $final_quiz_id = PoliteiaCourse::getFinalQuizId( $course_id );

    if ( ! $first_quiz_id && ! $final_quiz_id ) {
        wp_send_json_error( [ 'message' => __( 'Este curso no tiene cuestionarios configurados.', 'villegas-courses' ) ] );
    }

    $reference_quiz_id = $final_quiz_id ? $final_quiz_id : $first_quiz_id;

    if ( ! $reference_quiz_id ) {
        wp_send_json_error( [ 'message' => __( 'No se pudo determinar un cuestionario de referencia.', 'villegas-courses' ) ] );
    }

    $stats = new Politeia_Quiz_Stats( $reference_quiz_id, $user_id );

    $first_summary = [
        'quiz_id'            => $first_quiz_id,
        'has_attempt'        => false,
        'percentage_rounded' => null,
        'score'              => 0,
        'formatted_date'     => null,
        'timestamp'          => 0,
    ];

    if ( $first_quiz_id ) {
        $first_summary = $stats->get_quiz_summary( $first_quiz_id );
    }

    $final_summary = [
        'quiz_id'            => $final_quiz_id,
        'has_attempt'        => false,
        'percentage_rounded' => null,
        'score'              => 0,
        'formatted_date'     => null,
        'timestamp'          => 0,
    ];

    if ( $final_quiz_id ) {
        $final_summary = $stats->get_quiz_summary( $final_quiz_id );
    }

    $delta        = null;
    $days_elapsed = null;

    $first_numeric = is_numeric( $first_summary['percentage_rounded'] );
    $final_numeric = is_numeric( $final_summary['percentage_rounded'] );

    if ( $first_summary['has_attempt'] && $final_summary['has_attempt'] && $first_numeric && $final_numeric ) {
        $delta = intval( $final_summary['percentage_rounded'] ) - intval( $first_summary['percentage_rounded'] );

        if ( $final_summary['timestamp'] && $first_summary['timestamp'] && $final_summary['timestamp'] >= $first_summary['timestamp'] ) {
            $days_elapsed = max( 1, floor( ( $final_summary['timestamp'] - $first_summary['timestamp'] ) / DAY_IN_SECONDS ) );
        }
    }

    $results = [
        'course'  => [
            'id'    => (int) $course_id,
            'title' => sanitize_text_field( get_the_title( $course_id ) ),
        ],
        'first'   => [
            'quiz_id'        => $first_quiz_id,
            'has_attempt'    => (bool) $first_summary['has_attempt'],
            'score'          => intval( $first_summary['score'] ),
            'percentage'     => $first_numeric ? intval( $first_summary['percentage_rounded'] ) : null,
            'formatted_date' => $first_summary['formatted_date'] ? sanitize_text_field( $first_summary['formatted_date'] ) : null,
        ],
        'final'   => [
            'quiz_id'        => $final_quiz_id,
            'has_attempt'    => (bool) $final_summary['has_attempt'],
            'score'          => intval( $final_summary['score'] ),
            'percentage'     => $final_numeric ? intval( $final_summary['percentage_rounded'] ) : null,
            'formatted_date' => $final_summary['formatted_date'] ? sanitize_text_field( $final_summary['formatted_date'] ) : null,
        ],
        'metrics' => [
            'delta'        => $delta,
            'days_elapsed' => $days_elapsed,
        ],
    ];

    $modal_data = $results;

    ob_start();
    include plugin_dir_path( __FILE__ ) . 'partials/ajax-results-box.php';
    $raw_html     = trim( ob_get_clean() );
    $allowed_html = wp_kses_allowed_html( 'post' );
    $allowed_html['style'] = [ 'type' => true ];
    $html = wp_kses( $raw_html, $allowed_html );

    wp_send_json_success(
        [
            'html'    => $html,
            'results' => $results,
        ]
    );
}

function enqueue_my_account_script() {
    if (is_account_page()) {
        wp_enqueue_script(
            'my-account-script',
            plugin_dir_url(__FILE__) . 'assets/js/my-account.js',
            ['jquery'],
            '1.0',
            true
        );

    }
}
add_action('wp_enqueue_scripts', 'enqueue_my_account_script');

// Incluir metabox personalizado y otros archivos necesarios
include_once 'learndash-course-metabox.php';
include_once 'functions.php';
include plugin_dir_path(__FILE__) . 'parts/comprar-stats.php';
include_once plugin_dir_path(__FILE__) . 'metabox-course-first-quiz.php';
include_once plugin_dir_path(__FILE__) . 'woo-tabs.php';
// Include the leaderboard-villegas.php file
/*require_once plugin_dir_path(__FILE__) . 'leaderboard-villegas/leaderboard-villegas.php'; */

/* LOGIN MECHANISM 
require_once plugin_dir_path(__FILE__) . 'login/shortcode-login-register.php';
require_once plugin_dir_path(__FILE__) . 'login/email-confirmation.php';
require_once plugin_dir_path(__FILE__) . 'login/process-registration.php';*/
/* CLASSES */
require_once plugin_dir_path(__FILE__) . 'classes/class-quiz-analytics.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/quiz-class-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes/quiz-average-score-shortcode.php';
/* AJAX HANDLER*/
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
/* USER OPTIONS */
require_once plugin_dir_path(__FILE__) . 'opciones-usuario.php';
/* PROFILE PHOTO */
require_once plugin_dir_path(__FILE__) . 'profile/profile-picture.php';
/* SHORTCODES */
require_once plugin_dir_path(__FILE__) . 'shortcodes/shortcode-cursos-finalizados.php';
/* ADMIN PAGES */
require_once plugin_dir_path(__FILE__) . 'admin/pages/course-checklist.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';


// Customize LearnDash quiz result template by replacing the original with a custom one.

add_filter('learndash_template', 'custom_quiz_result_template', 10, 5);
function custom_quiz_result_template($filepath, $name, $args, $echo, $return_file_path) {
    if ($name == 'quiz/partials/show_quiz_result_box.php') {
        $custom_template_path = plugin_dir_path(__FILE__) . 'templates/show_quiz_result_box.php';
        if (file_exists($custom_template_path)) {
            return $custom_template_path;
        }
    }
    return $filepath;
}

/*
function enqueue_login_scripts() {
    wp_enqueue_script('form-toggle', plugin_dir_url(__FILE__) . 'login/form-toggle.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_login_scripts');
*/

// Enqueue custom CSS and JavaScript for quiz and lesson pages.

function enqueue_quiz_resources() {
    $plugin_url = plugin_dir_url(__FILE__);

    // Common CSS files
    /*wp_enqueue_style('quiz-result-style', $plugin_url . 'assets/quiz-result.css', [], '1.0', 'all'); */
    wp_enqueue_style('custom-left-div-style', $plugin_url . 'assets/custom-left-div.css', [], '1.0', 'all');
    wp_enqueue_style('woo-tabs-style', $plugin_url . 'assets/woo-tabs.css', [], '1.0', 'all');

    // Check if it's a quiz page
    // en enqueue_quiz_resources()
if ( is_singular( 'sfwd-quiz' ) ) {

    wp_enqueue_script(
        'custom-quiz-message',
        $plugin_url . 'assets/custom-quiz-message.js',
        [ 'jquery' ],
        '1.1',
        true
    );

    $quiz_id      = get_the_ID();
    $course_id    = PoliteiaCourse::getCourseFromQuiz( $quiz_id );
    $course_title = $course_id ? get_the_title( $course_id ) : '';

    // Determinar si es First o Final
    // Asegúrate de cargar la clase QuizAnalytics si aún no existe
    if ( ! class_exists('QuizAnalytics') ) {
        require_once plugin_dir_path(__FILE__) . 'classes/class-quiz-analytics.php';
    }

    // Instancia QuizAnalytics y determina tipo de quiz
    $analytics = new QuizAnalytics($quiz_id, get_current_user_id());
    $type      = $analytics->isFirstQuiz() ? 'first' : 'final';

    $first_quiz_nonce = wp_create_nonce( 'villegas_send_first_quiz_email' );
    $final_quiz_nonce = wp_create_nonce( 'enviar_correo_final_quiz' );


    $quiz_description_raw = get_post_field('post_content', $quiz_id);
    $quiz_description = $quiz_description_raw ? apply_filters('the_content', do_blocks($quiz_description_raw)) : '';

    wp_localize_script( 'custom-quiz-message', 'quizData', [
        'quizId'          => $quiz_id,
        'userId'          => get_current_user_id(),
        'courseName'      => $course_title,
        'type'            => $type,
        'description'     => $quiz_description,
        'firstQuizNonce'  => $first_quiz_nonce,
        'finalQuizNonce'  => $final_quiz_nonce,
        'activityNonce'   => wp_create_nonce( 'politeia_quiz_activity' ),
    ] );


}

    // Check if it's a lesson page
    if (is_singular('sfwd-lessons')) {
        wp_enqueue_script('custom-lesson-script', $plugin_url . 'assets/custom-lesson-script.js', [], '1.0', true);
        wp_localize_script('custom-lesson-script', 'lessonData', [
            'lessonList' => 'Here is where your lesson list would go',
            'arrowImageUrl' => $plugin_url . 'assets/arrow.svg'
        ]);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_quiz_resources');

function villegas_enqueue_quiz_float_fix() {
    wp_enqueue_script(
        'quiz-float-fix',
        plugin_dir_url(__FILE__) . 'assets/js/quiz-float-fix.js',
        [],
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'villegas_enqueue_quiz_float_fix');

add_action('wp_ajax_guardar_imagen_estilo_quiz', function () {
    check_ajax_referer('guardar_imagen_estilo_quiz', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }

    $quiz_id = intval($_POST['quiz_id']);
    $image_id = intval($_POST['image_id']);

    if ($quiz_id && $image_id) {
        update_post_meta($quiz_id, '_quiz_style_image', $image_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('Datos inválidos');
    }
});

/**
 * Require the course outline functionality from an external file.
 */
require_once plugin_dir_path(__FILE__) . 'course-outline.php';


/**
 * Hook into 'wp_footer' to dynamically add the new div before entry content.
 */
add_action('wp_footer', 'insert_div_before_entry_content');

/**
 * Register the shortcode for the registration/login functionality
 */
add_shortcode('login_register_form', 'login_register_form_shortcode');

/* OVERRIDE LESSON PAGE */

add_filter('template_include', 'el_villegas_override_single_sfwd_lessons', 99);

function el_villegas_override_single_sfwd_lessons($template) {
    if (is_singular('sfwd-lessons')) { // Check if it's a lesson page
        $custom_template = plugin_dir_path(__FILE__) . 'templates/single-sfwd-lessons.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template; // Fallback to the default template
}

/* OVERRIDE INFOBAR PHP */

add_filter('learndash_template', 'el_villegas_override_infobar_template', 10, 5);

function el_villegas_override_infobar_template($filepath, $name, $args, $type, $template_key) {
    // Check if the requested template is the infobar.php file
    if ($name === 'modules/infobar.php') {
        // Return the path to your custom infobar.php
        $custom_template = plugin_dir_path(__FILE__) . 'templates/infobar.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $filepath; // Return the original template if no override
}


/* OVERRIDE QUIZ PAGE */

add_filter('template_include', 'el_villegas_override_single_sfwd_quiz', 99);

function el_villegas_override_single_sfwd_quiz($template) {
    if (is_singular('sfwd-quiz')) { // Check if it's a quiz page
        $custom_template = plugin_dir_path(__FILE__) . 'templates/single-sfwd-quiz.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template; // Fallback to the default template
}

/* --------------------------------------------------------------------------
 *  FINAL QUIZ – ENVÍO DE CORREO CON PLANTILLA (una vez por intento)
 * --------------------------------------------------------------------------*/
add_action( 'wp_ajax_enviar_correo_final_quiz', 'handle_enviar_correo_final_quiz' );

function handle_enviar_correo_final_quiz() {
        check_ajax_referer( 'enviar_correo_final_quiz', 'nonce' );

        if ( ! is_user_logged_in() ) {
                wp_send_json_error( 'No autorizado' );
        }

        $req             = wp_unslash( $_POST );
        $quiz_id         = isset( $req['quiz_id'] ) ? (int) $req['quiz_id'] : 0;
        $requested_user  = isset( $req['user_id'] ) ? (int) $req['user_id'] : 0;
        $current_user_id = get_current_user_id();

        if ( $requested_user && $requested_user !== $current_user_id ) {
                wp_send_json_error( 'Usuario no autorizado' );
        }

        $user_id      = $current_user_id;
        $quiz_percent = isset( $req['quiz_percentage'] ) ? (float) $req['quiz_percentage'] : null;

        if ( ! $quiz_id || ! $user_id ) {
                wp_send_json_error( 'Datos incompletos' );
        }

        global $wpdb;

        $attempt = $wpdb->get_row(
                $wpdb->prepare(
                        "SELECT activity_id, activity_completed
                         FROM {$wpdb->prefix}learndash_user_activity
                         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz'
                         ORDER BY activity_completed DESC LIMIT 1",
                        $user_id,
                        $quiz_id
                ),
                ARRAY_A
        );

        if ( ! $attempt || empty( $attempt['activity_id'] ) ) {
                wp_send_json_error( 'Intento no encontrado' );
        }

        $activity_id = (int) $attempt['activity_id'];
        $key         = "villegas_final_quiz_email_{$activity_id}";

        if ( get_transient( $key ) ) {
                wp_send_json_success( 'Correo ya enviado para este intento' );
        }

        $user = get_userdata( $user_id );

        if ( ! $user ) {
                wp_send_json_error( 'Usuario no encontrado' );
        }

        $email = villegas_get_final_quiz_email_content(
                [
                        'quiz'       => $quiz_id,
                        'percentage' => $quiz_percent,
                ],
                $user
        );

        if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
                wp_send_json_error( 'Plantilla de correo no disponible' );
        }

        $sent = wp_mail(
                $user->user_email,
                $email['subject'],
                $email['body'],
                [ 'Content-Type: text/html; charset=UTF-8' ]
        );

        if ( $sent ) {
                set_transient( $key, 1, HOUR_IN_SECONDS );
                wp_send_json_success( 'Correo enviado' );
        }

        wp_send_json_error( 'Error al enviar el correo' );
}



/* ENQUEUE JAVASCRIPR PUNTAJE PRIVADO */

add_action('wp_enqueue_scripts', 'villegas_enqueue_puntaje_privado_script');

function villegas_enqueue_puntaje_privado_script() {
    $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));

    if (is_singular('sfwd-quiz') || strpos($current_url, '/mi-cuenta/mis-cursos') !== false) {
        wp_enqueue_script(
            'puntaje-privado',
            plugin_dir_url(__FILE__) . 'assets/js/puntaje-privado.js',
            ['jquery'],
            '1.0',
            true
        );

    }
}

/* GUARDAR VALOR DE PRIVACIDAD PUNTAJE */

add_action('wp_ajax_guardar_privacidad_puntaje', 'villegas_guardar_privacidad_puntaje');

function villegas_guardar_privacidad_puntaje() {
    if ( ! check_ajax_referer( 'guardar_privacidad_puntaje', 'nonce', false ) ) {
        wp_send_json_error( __( 'Solicitud no válida.', 'villegas-courses' ), 403 );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
        wp_send_json_error( __( 'No tienes permisos para actualizar esta preferencia.', 'villegas-courses' ), 403 );
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $puntaje_privado = isset($_POST['puntaje_privado']) && $_POST['puntaje_privado'] === '1';

    if (!$user_id || get_current_user_id() !== $user_id) {
        wp_send_json_error( __( 'Usuario inválido.', 'villegas-courses' ), 403 );
    }

    update_user_meta($user_id, 'puntaje_privado', $puntaje_privado ? '1' : '0');
    wp_send_json_success( __( 'Preferencia guardada.', 'villegas-courses' ) );
}



/**
 * ------------------------------------------------------
 * 1) Sobrescribir la plantilla “single-sfwd-quiz.php” de LearnDash
 * ------------------------------------------------------
 */
add_filter( 'learndash_template', 'el_villegas_override_single_quiz_template', 10, 5 );
function el_villegas_override_single_quiz_template( $filepath, $name, $args, $echo, $return_file_path ) {
    // Dependiendo de tu versión de LearnDash, el $name puede venir como 'quiz/single-sfwd-quiz.php'
    // o simplemente como 'single-sfwd-quiz.php'. Para estar seguros, chequeamos ambas:
    if ( $name === 'quiz/single-sfwd-quiz.php' || $name === 'single-sfwd-quiz.php' ) {
        // Ruta a tu propia copia dentro del plugin:
        $custom = plugin_dir_path( __FILE__ ) . 'templates/single-sfwd-quiz.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $filepath;
}
