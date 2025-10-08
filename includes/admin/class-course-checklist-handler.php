<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Villegas_Course_Checklist_Handler {

    public function __construct() {
        add_action( 'wp_ajax_villegas_create_quiz', [ $this, 'ajax_create_quiz' ] );
        add_action( 'wp_ajax_villegas_clone_opposite_quiz', [ $this, 'ajax_clone_opposite_quiz' ] );
        add_action( 'wp_ajax_villegas_create_product', [ $this, 'ajax_create_product' ] );
    }

    /**
     * Handle request to open the quiz creation screen.
     */
    public function ajax_create_quiz() {
        check_ajax_referer( 'villegas_checklist_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You are not allowed to perform this action.', 'villegas-courses' ) ], 403 );
        }

        wp_send_json_success(
            [
                'redirect' => admin_url( 'post-new.php?post_type=sfwd-quiz' ),
            ]
        );
    }

    /**
     * Clone the opposite quiz type and link it to the course.
     */
    public function ajax_clone_opposite_quiz() {
        check_ajax_referer( 'villegas_checklist_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You are not allowed to perform this action.', 'villegas-courses' ) ], 403 );
        }

        $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
        $type      = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        if ( ! $course_id || ! in_array( $type, [ 'first', 'final' ], true ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid data.', 'villegas-courses' ) ] );
        }

        $result = $this->clone_opposite_quiz( $course_id, $type );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        if ( ! $result ) {
            wp_send_json_error( [ 'message' => __( 'Clone failed.', 'villegas-courses' ) ] );
        }

        wp_send_json_success(
            [
                'quiz_id' => $result,
                'message' => __( 'Quiz cloned successfully.', 'villegas-courses' ),
            ]
        );
    }

    /**
     * Create a related WooCommerce product for the given course.
     */
    public function ajax_create_product() {
        check_ajax_referer( 'villegas_checklist_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You are not allowed to perform this action.', 'villegas-courses' ) ], 403 );
        }

        $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
        $price     = isset( $_POST['price'] ) ? intval( $_POST['price'] ) : 0;

        if ( ! $course_id || $price <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid input.', 'villegas-courses' ) ] );
        }

        $product_id = $this->create_related_product( $course_id, $price );

        if ( is_wp_error( $product_id ) ) {
            wp_send_json_error( [ 'message' => $product_id->get_error_message() ] );
        }

        if ( ! $product_id ) {
            wp_send_json_error( [ 'message' => __( 'Product creation failed.', 'villegas-courses' ) ] );
        }

        wp_send_json_success(
            [
                'product_id' => $product_id,
                'message'    => __( 'Product created successfully.', 'villegas-courses' ),
            ]
        );
    }

    /* ---------------- Core Logic ---------------- */

    /**
     * Clone the opposite quiz for the course.
     *
     * @param int    $course_id Course ID.
     * @param string $type      Target quiz type being created (first|final).
     *
     * @return int|WP_Error
     */
    private function clone_opposite_quiz( $course_id, $type ) {
        $source_quiz_id = ( 'first' === $type ) ? $this->get_final_quiz_id( $course_id ) : $this->get_first_quiz_id( $course_id );

        if ( ! $source_quiz_id ) {
            return new WP_Error( 'missing_source_quiz', __( 'The opposite quiz could not be found.', 'villegas-courses' ) );
        }

        $source_quiz = get_post( $source_quiz_id );
        if ( ! $source_quiz ) {
            return new WP_Error( 'invalid_source_quiz', __( 'The source quiz no longer exists.', 'villegas-courses' ) );
        }

        $search  = ( 'first' === $type ) ? 'Final' : 'First';
        $replace = ( 'first' === $type ) ? 'First' : 'Final';

        $new_title = $source_quiz->post_title;
        $count     = 0;
        $new_title = str_ireplace( $search, $replace, $new_title, $count );

        if ( 0 === $count ) {
            $new_title = sprintf( '%s (%s)', $new_title, $replace );
        }

        $new_quiz_id = wp_insert_post(
            [
                'post_type'    => 'sfwd-quiz',
                'post_title'   => wp_slash( $new_title ),
                'post_content' => wp_slash( $source_quiz->post_content ),
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ],
            true
        );

        if ( is_wp_error( $new_quiz_id ) ) {
            return $new_quiz_id;
        }

        if ( ! $new_quiz_id ) {
            return new WP_Error( 'quiz_insert_failed', __( 'Unable to create the new quiz.', 'villegas-courses' ) );
        }

        $meta = get_post_meta( $source_quiz_id );
        foreach ( $meta as $key => $values ) {
            if ( in_array( $key, [ '_edit_lock', '_edit_last' ], true ) ) {
                continue;
            }

            foreach ( $values as $value ) {
                update_post_meta( $new_quiz_id, $key, maybe_unserialize( $value ) );
            }
        }

        if ( 'first' === $type ) {
            update_post_meta( $course_id, '_first_quiz_id', $new_quiz_id );
        } else {
            update_post_meta( $course_id, '_final_quiz_id', $new_quiz_id );
            $this->append_quiz_to_course_steps( $course_id, $new_quiz_id );
        }

        return $new_quiz_id;
    }

    /**
     * Create and link a WooCommerce product with the course.
     *
     * @param int $course_id Course ID.
     * @param int $price     Product price.
     *
     * @return int|WP_Error Product ID on success.
     */
    private function create_related_product( $course_id, $price ) {
        $course = get_post( $course_id );
        if ( ! $course ) {
            return new WP_Error( 'invalid_course', __( 'Course not found.', 'villegas-courses' ) );
        }

        $product_id = wp_insert_post(
            [
                'post_type'    => 'product',
                'post_status'  => 'publish',
                'post_title'   => wp_slash( $course->post_title ),
                'post_content' => wp_slash( $course->post_content ),
                'post_author'  => get_current_user_id(),
            ],
            true
        );

        if ( is_wp_error( $product_id ) ) {
            return $product_id;
        }

        if ( ! $product_id ) {
            return new WP_Error( 'product_insert_failed', __( 'Unable to create the product.', 'villegas-courses' ) );
        }

        wp_set_object_terms( $product_id, 'course', 'product_type', false );

        $thumbnail_id = get_post_thumbnail_id( $course_id );
        if ( $thumbnail_id ) {
            set_post_thumbnail( $product_id, $thumbnail_id );
        }

        $course_category = get_term_by( 'name', 'Cursos', 'product_cat' );
        if ( $course_category && ! is_wp_error( $course_category ) ) {
            wp_set_object_terms( $product_id, [ (int) $course_category->term_id ], 'product_cat', false );
        }

        update_post_meta( $product_id, '_price', $price );
        update_post_meta( $product_id, '_regular_price', $price );
        update_post_meta( $product_id, '_related_course', $course_id );
        update_post_meta( $product_id, '_virtual', 'yes' );

        update_post_meta( $course_id, '_related_product', $product_id );

        return $product_id;
    }

    private function get_first_quiz_id( $course_id ) {
        $quiz_id = intval( get_post_meta( $course_id, '_first_quiz_id', true ) );

        if ( $quiz_id && ! $this->quiz_exists( $quiz_id ) ) {
            delete_post_meta( $course_id, '_first_quiz_id' );
            return 0;
        }

        return $quiz_id;
    }

    private function get_final_quiz_id( $course_id ) {
        $quiz_id = intval( get_post_meta( $course_id, '_final_quiz_id', true ) );
        if ( $quiz_id ) {
            if ( $this->quiz_exists( $quiz_id ) ) {
                return $quiz_id;
            }

            delete_post_meta( $course_id, '_final_quiz_id' );
        }

        $steps          = get_post_meta( $course_id, 'ld_course_steps', true );
        $original_steps = $steps;

        if ( ! is_array( $steps ) ) {
            return 0;
        }

        while ( true ) {
            $candidate = $this->find_quiz_in_steps( $steps );

            if ( ! $candidate ) {
                $quiz_id = 0;
                break;
            }

            if ( $this->quiz_exists( $candidate ) ) {
                $quiz_id = $candidate;
                break;
            }

            $updated_steps = $this->remove_quiz_from_steps( $steps, $candidate );

            if ( $updated_steps === $steps ) {
                $quiz_id = 0;
                break;
            }

            $steps = $updated_steps;
        }

        if ( $original_steps !== $steps ) {
            if ( empty( $steps ) ) {
                delete_post_meta( $course_id, 'ld_course_steps' );
            } else {
                update_post_meta( $course_id, 'ld_course_steps', $steps );
            }
        }

        return $quiz_id;
    }

    private function find_quiz_in_steps( $steps ) {
        foreach ( $steps as $key => $value ) {
            if ( is_numeric( $key ) ) {
                $quiz_id = intval( $key );
                if ( $quiz_id && 'sfwd-quiz' === get_post_type( $quiz_id ) ) {
                    return $quiz_id;
                }
            }

            if ( is_array( $value ) ) {
                $quiz_id = $this->find_quiz_in_steps( $value );
                if ( $quiz_id ) {
                    return $quiz_id;
                }
            } elseif ( is_numeric( $value ) ) {
                $quiz_id = intval( $value );
                if ( $quiz_id && 'sfwd-quiz' === get_post_type( $quiz_id ) ) {
                    return $quiz_id;
                }
            }
        }

        return 0;
    }

    private function quiz_exists( $quiz_id ) {
        $quiz_id = intval( $quiz_id );

        if ( ! $quiz_id ) {
            return false;
        }

        $quiz = get_post( $quiz_id );

        return $quiz && 'sfwd-quiz' === $quiz->post_type && 'trash' !== $quiz->post_status;
    }

    private function remove_quiz_from_steps( $steps, $quiz_id ) {
        if ( ! is_array( $steps ) ) {
            return $steps;
        }

        $quiz_id = intval( $quiz_id );

        foreach ( $steps as $key => $value ) {
            $key_matches   = intval( $key ) === $quiz_id;
            $value_matches = ! is_array( $value ) && intval( $value ) === $quiz_id;

            if ( $key_matches || $value_matches ) {
                unset( $steps[ $key ] );
                continue;
            }

            if ( is_array( $value ) ) {
                $updated = $this->remove_quiz_from_steps( $value, $quiz_id );

                if ( empty( $updated ) ) {
                    unset( $steps[ $key ] );
                } else {
                    $steps[ $key ] = $updated;
                }
            }
        }

        return $steps;
    }

    private function append_quiz_to_course_steps( $course_id, $quiz_id ) {
        $steps = get_post_meta( $course_id, 'ld_course_steps', true );
        if ( ! is_array( $steps ) ) {
            $steps = [];
        }

        $modified = $this->insert_quiz_into_steps_recursive( $steps, $quiz_id );

        if ( ! $modified ) {
            $steps[] = $quiz_id;
        }

        update_post_meta( $course_id, 'ld_course_steps', $steps );
    }

    private function insert_quiz_into_steps_recursive( &$steps, $quiz_id ) {
        if ( isset( $steps['sfwd-quiz'] ) && is_array( $steps['sfwd-quiz'] ) ) {
            if ( ! isset( $steps['sfwd-quiz'][ $quiz_id ] ) ) {
                $steps['sfwd-quiz'][ $quiz_id ] = 0;
            }

            return true;
        }

        foreach ( $steps as &$value ) {
            if ( is_array( $value ) ) {
                if ( $this->insert_quiz_into_steps_recursive( $value, $quiz_id ) ) {
                    unset( $value );
                    return true;
                }
            }
        }

        unset( $value );
        return false;
    }
}

new Villegas_Course_Checklist_Handler();
