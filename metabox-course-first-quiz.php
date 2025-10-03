<?php
/**
 * LearnDash Course Quiz metaboxes with AJAX Select2 search.
 */

add_action( 'add_meta_boxes', 'villegas_register_course_quiz_metaboxes' );
/**
 * Register First and Final quiz metaboxes on LearnDash courses.
 */
function villegas_register_course_quiz_metaboxes() {
    add_meta_box(
        'villegas_first_quiz_metabox',
        __( 'First Quiz', 'villegas-courses' ),
        'villegas_render_course_quiz_metabox',
        'sfwd-courses',
        'side',
        'default',
        array(
            'meta_key'   => '_first_quiz_id',
            'field_id'   => 'villegas-first-quiz',
            'field_name' => 'villegas_first_quiz',
            'label'      => __( 'Select the First Quiz', 'villegas-courses' ),
        )
    );

    add_meta_box(
        'villegas_final_quiz_metabox',
        __( 'Final Quiz', 'villegas-courses' ),
        'villegas_render_course_quiz_metabox',
        'sfwd-courses',
        'side',
        'default',
        array(
            'meta_key'   => '_final_quiz_id',
            'field_id'   => 'villegas-final-quiz',
            'field_name' => 'villegas_final_quiz',
            'label'      => __( 'Select the Final Quiz', 'villegas-courses' ),
        )
    );
}

/**
 * Render the Select2 enabled metabox field.
 *
 * @param WP_Post $post Current post object.
 * @param array   $box  Metabox configuration.
 */
function villegas_render_course_quiz_metabox( $post, $box ) {
    static $nonce_printed = false;

    $meta_key   = isset( $box['args']['meta_key'] ) ? $box['args']['meta_key'] : '';
    $field_id   = isset( $box['args']['field_id'] ) ? $box['args']['field_id'] : '';
    $field_name = isset( $box['args']['field_name'] ) ? $box['args']['field_name'] : '';
    $label      = isset( $box['args']['label'] ) ? $box['args']['label'] : '';

    $selected_quiz_id = $meta_key ? get_post_meta( $post->ID, $meta_key, true ) : '';
    $selected_quiz    = $selected_quiz_id ? get_post( (int) $selected_quiz_id ) : null;

    if ( ! $nonce_printed ) {
        wp_nonce_field( 'villegas_course_quiz_metabox', 'villegas_course_quiz_nonce' );
        $nonce_printed = true;
    }
    ?>
    <p>
        <label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
    </p>
    <select
        id="<?php echo esc_attr( $field_id ); ?>"
        name="<?php echo esc_attr( $field_name ); ?>"
        class="villegas-quiz-select"
        data-placeholder="<?php echo esc_attr__( 'Search for a quiz…', 'villegas-courses' ); ?>"
        data-selected-id="<?php echo esc_attr( $selected_quiz_id ); ?>"
        data-selected-text="<?php echo $selected_quiz ? esc_attr( $selected_quiz->post_title ) : ''; ?>"
        style="width:100%;"
    >
        <option value=""></option>
        <?php if ( $selected_quiz ) : ?>
            <option value="<?php echo esc_attr( $selected_quiz_id ); ?>" selected>
                <?php echo esc_html( $selected_quiz->post_title ); ?>
            </option>
        <?php endif; ?>
    </select>
    <?php
}

add_action( 'save_post', 'villegas_save_course_quiz_metabox', 10, 2 );
/**
 * Persist the selected quiz IDs for the current course.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function villegas_save_course_quiz_metabox( $post_id, $post ) {
    if ( 'sfwd-courses' !== $post->post_type ) {
        return;
    }

    if ( ! isset( $_POST['villegas_course_quiz_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['villegas_course_quiz_nonce'] ) ), 'villegas_course_quiz_metabox' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'villegas_first_quiz' => '_first_quiz_id',
        'villegas_final_quiz' => '_final_quiz_id',
    );

    foreach ( $fields as $field_name => $meta_key ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $value = absint( wp_unslash( $_POST[ $field_name ] ) );
            if ( $value ) {
                update_post_meta( $post_id, $meta_key, $value );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}

add_action( 'admin_enqueue_scripts', 'villegas_course_quiz_metabox_assets' );
/**
 * Enqueue Select2 assets and localised data for the metaboxes.
 *
 * @param string $hook Current admin page hook suffix.
 */
function villegas_course_quiz_metabox_assets( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'sfwd-courses' !== $screen->post_type ) {
        return;
    }

    wp_enqueue_style(
        'villegas-select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        array(),
        '4.1.0-rc.0'
    );

    wp_enqueue_script(
        'villegas-select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        array( 'jquery' ),
        '4.1.0-rc.0',
        true
    );

    wp_enqueue_script(
        'villegas-course-quiz-metabox',
        plugin_dir_url( __FILE__ ) . 'assets/js/course-quiz-metabox.js',
        array( 'jquery', 'villegas-select2' ),
        '1.0.0',
        true
    );

    wp_localize_script(
        'villegas-course-quiz-metabox',
        'villegasCourseQuizData',
        array(
            'restUrl' => esc_url_raw( rest_url( 'villegas-course/v1/quizzes' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'labels'  => array(
                'placeholder'   => __( 'Search for a quiz…', 'villegas-courses' ),
                'noResults'     => __( 'No quizzes found.', 'villegas-courses' ),
                'searching'     => __( 'Searching…', 'villegas-courses' ),
                'inputTooShort' => __( 'Please enter 1 or more characters', 'villegas-courses' ),
            ),
        )
    );
}

add_action( 'rest_api_init', 'villegas_register_quiz_search_route' );
/**
 * Register the REST API route for quiz searching.
 */
function villegas_register_quiz_search_route() {
    register_rest_route(
        'villegas-course/v1',
        '/quizzes',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'villegas_rest_search_quizzes',
            'permission_callback' => 'villegas_rest_quiz_permission_check',
            'args'                => array(
                'search' => array(
                    'description' => __( 'Quiz search term.', 'villegas-courses' ),
                    'type'        => 'string',
                    'required'    => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        )
    );
}

/**
 * Permission callback for the quiz search route.
 *
 * @return bool
 */
function villegas_rest_quiz_permission_check() {
    return current_user_can( 'edit_posts' );
}

/**
 * REST callback that searches for quizzes by title and returns Select2 formatted data.
 *
 * @param WP_REST_Request $request Request instance.
 *
 * @return WP_REST_Response
 */
function villegas_rest_search_quizzes( WP_REST_Request $request ) {
    $search = $request->get_param( 'search' );

    $query_args = array(
        'post_type'      => 'sfwd-quiz',
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    if ( ! empty( $search ) ) {
        $query_args['s'] = $search;
    }

    $query = new WP_Query( $query_args );

    $results = array();

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $quiz ) {
            $results[] = array(
                'id'   => $quiz->ID,
                'text' => html_entity_decode( wp_strip_all_tags( $quiz->post_title ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
            );
        }
    }

    return rest_ensure_response(
        array(
            'results' => $results,
        )
    );
}
