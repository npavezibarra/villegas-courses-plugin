<?php
/**
 * Plugin Name: Villegas Courses Plugin
 * Description: Plugin de customización experiencia cursos en sitio El Villegas.
 * Version: 1.0
 * Author: Nicolás Pavez
 */

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

// Encolar estilo de Course Page
function my_custom_ld_course_styles() {
    wp_enqueue_style( 'my-course-page-style', plugin_dir_url( __FILE__ ) . 'assets/course-page.css', array(), '1.0', 'all' );
    wp_enqueue_style('ingresa-roma-css', plugins_url('assets/ingresa-roma.css', __FILE__));
    wp_enqueue_script('form-toggle-js', plugins_url('login-register/form-toggle.js', __FILE__), array('jquery'), null, true);
}
add_action( 'wp_enqueue_scripts', 'my_custom_ld_course_styles' );


// Encolar el archivo CSS
    wp_enqueue_style('ingresa-roma-css', plugins_url('assets/ingresa-roma.css', __FILE__));
    
    // Encolar el archivo JavaScript
    wp_enqueue_script('form-toggle-js', plugins_url('login-register/form-toggle.js', __FILE__), array('jquery'), null, true);
// Incluir metabox personalizado
include_once 'learndash-course-metabox.php';
include plugin_dir_path( __FILE__ ) . 'parts/comprar-stats.php';
include_once plugin_dir_path( __FILE__ ) . 'metabox-course-first-quiz.php';
include_once plugin_dir_path(__FILE__) . 'user-profile-photo.php';
require_once( plugin_dir_path( __FILE__ ) . 'woo-tabs.php' );

/**
 * Customize LearnDash quiz result template by replacing the original with a custom one.
 */
add_filter('learndash_template', 'custom_quiz_result_template', 10, 5);

function custom_quiz_result_template($filepath, $name, $args, $echo, $return_file_path) {
    // Check if the quiz result box template is being loaded
    if ($name == 'quiz/partials/show_quiz_result_box.php') {
        // Define the path to the custom template in your plugin
        $custom_template_path = plugin_dir_path(__FILE__) . 'learndash/templates/quiz/partials/show_quiz_result_box.php';

        // If the custom template file exists, use it
        if (file_exists($custom_template_path)) {
            return $custom_template_path;
        }
    }
    // Return the original template if the custom one is not found
    return $filepath;
}

/**
 * Enqueue custom CSS and JavaScript for quiz and lesson pages.
 */
add_action('wp_enqueue_scripts', 'enqueue_quiz_resources');

function enqueue_quiz_resources() {
    // Define the plugin directory URL to get the path to the assets folder
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue the common CSS files
    wp_enqueue_style('quiz-result-style', $plugin_url . 'assets/quiz-result.css', array(), '1.0.0', 'all');
    wp_enqueue_style('custom-left-div-style', $plugin_url . 'assets/custom-left-div.css', array(), '1.0.0', 'all');

    // Enqueue woo-tabs CSS for WooCommerce My Account tabs
    wp_enqueue_style('woo-tabs-style', $plugin_url . 'assets/woo-tabs.css', array(), '1.0.0', 'all');

    // Check if it's a quiz page
    if (is_singular('sfwd-quiz')) {
        // Enqueue the custom JS for quiz message
        wp_enqueue_script('custom-quiz-message', $plugin_url . 'assets/custom-quiz-message.js', array(), '1.0.0', true);

        // Pass the course name to JS via wp_localize_script
        $course_id = learndash_get_course_id();
        $course_title = get_the_title($course_id);
        wp_localize_script('custom-quiz-message', 'quizData', array(
            'courseName' => $course_title
        ));
    }

    // Check if it's a lesson page
    if (is_singular('sfwd-lessons')) {
        // Enqueue the JS file
        wp_enqueue_script('custom-lesson-script', $plugin_url . 'assets/custom-lesson-script.js', array(), '1.0.0', true);

        // Localize the arrow image URL
        wp_localize_script('custom-lesson-script', 'lessonData', array(
            'lessonList' => 'Here is where your lesson list would go', // example
            'arrowImageUrl' => $plugin_url . 'assets/arrow.svg' // Pass the arrow image URL
        ));
    }
}

/**
 * Require the course outline functionality from an external file.
 */
require_once plugin_dir_path(__FILE__) . 'course-outline.php';
require_once plugin_dir_path(__FILE__) . 'login-register/create-page.php';
require_once plugin_dir_path(__FILE__) . 'login-register/registration-login.php';
// Incluye el archivo de confirmación de correo electrónico
require_once plugin_dir_path(__FILE__) . 'login-register/email-confirmation.php';
// Incluye el archivo de capacidades de usuario
require_once plugin_dir_path(__FILE__) . 'login-register/user-capabilities.php';




/**
 * Hook into 'wp_footer' to dynamically add the new div before entry content.
 */
add_action('wp_footer', 'insert_div_before_entry_content');

// Register the shortcode for the registration/login functionality
add_shortcode('villegas_registration_login', 'villegas_registration_login_shortcode');

?>