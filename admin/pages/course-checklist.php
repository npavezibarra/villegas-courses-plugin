<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../../classes/class-course-quiz-helper.php';
}

if ( ! function_exists( 'villegas_course_checklist_quiz_exists' ) ) {
    /**
     * Determine whether the provided quiz ID references a valid LearnDash quiz.
     *
     * @param int $quiz_id Quiz post ID.
     *
     * @return bool True when the quiz exists and is not trashed.
     */
    function villegas_course_checklist_quiz_exists( $quiz_id ) {
        $quiz_id = absint( $quiz_id );
        if ( ! $quiz_id ) {
            return false;
        }

        $quiz = get_post( $quiz_id );
        if ( ! $quiz || 'sfwd-quiz' !== $quiz->post_type ) {
            return false;
        }

        return 'trash' !== $quiz->post_status;
    }
}

if ( ! function_exists( 'villegas_course_checklist_remove_quiz_from_steps' ) ) {
    /**
     * Remove a quiz reference from the LearnDash course steps array.
     *
     * @param mixed $steps    Steps structure.
     * @param int   $quiz_id  Quiz post ID to remove.
     *
     * @return mixed Updated steps structure.
     */
    function villegas_course_checklist_remove_quiz_from_steps( $steps, $quiz_id ) {
        if ( ! is_array( $steps ) ) {
            return $steps;
        }

        $quiz_id = absint( $quiz_id );

        foreach ( $steps as $key => $value ) {
            $key_matches   = absint( $key ) === $quiz_id;
            $value_matches = ! is_array( $value ) && absint( $value ) === $quiz_id;

            if ( $key_matches || $value_matches ) {
                unset( $steps[ $key ] );
                continue;
            }

            if ( is_array( $value ) ) {
                $updated = villegas_course_checklist_remove_quiz_from_steps( $value, $quiz_id );

                if ( empty( $updated ) ) {
                    unset( $steps[ $key ] );
                } else {
                    $steps[ $key ] = $updated;
                }
            }
        }

        return $steps;
    }
}

if ( ! function_exists( 'villegas_course_checklist_find_final_quiz_id' ) ) {
    /**
     * Recursively search LearnDash course steps for the first quiz ID.
     *
     * @param mixed $steps Course steps structure from ld_course_steps meta.
     *
     * @return int|null Quiz post ID if found.
     */
    function villegas_course_checklist_find_final_quiz_id( $steps ) {
        if ( empty( $steps ) || ! is_array( $steps ) ) {
            return null;
        }

        foreach ( $steps as $key => $value ) {
            // Some course step arrays store quiz IDs as the key, others in nested arrays or as numeric values.
            if ( is_numeric( $key ) ) {
                $quiz_id = absint( $key );
                if ( $quiz_id && 'sfwd-quiz' === get_post_type( $quiz_id ) ) {
                    return $quiz_id;
                }
            }

            if ( is_array( $value ) ) {
                $quiz_id = villegas_course_checklist_find_final_quiz_id( $value );
                if ( $quiz_id ) {
                    return $quiz_id;
                }
            } elseif ( is_numeric( $value ) ) {
                $quiz_id = absint( $value );
                if ( $quiz_id && 'sfwd-quiz' === get_post_type( $quiz_id ) ) {
                    return $quiz_id;
                }
            }
        }

        return null;
    }
}

if ( ! function_exists( 'villegas_course_checklist_product_exists' ) ) {
    /**
     * Determine whether the provided product ID references a valid WooCommerce product.
     *
     * @param int $product_id Product post ID.
     *
     * @return bool
     */
    function villegas_course_checklist_product_exists( $product_id ) {
        if ( function_exists( 'villegas_course_product_exists' ) ) {
            return villegas_course_product_exists( $product_id );
        }

        $product_id = absint( $product_id );

        if ( ! $product_id ) {
            return false;
        }

        $product = get_post( $product_id );

        if ( ! $product || 'product' !== $product->post_type ) {
            return false;
        }

        return 'trash' !== $product->post_status && 'auto-draft' !== $product->post_status;
    }
}

if ( ! function_exists( 'villegas_course_checklist_clear_product_links' ) ) {
    /**
     * Remove stored product relationships for the provided course.
     *
     * @param int $course_id Course post ID.
     */
    function villegas_course_checklist_clear_product_links( $course_id ) {
        delete_post_meta( $course_id, '_related_product' );
        delete_post_meta( $course_id, '_linked_woocommerce_product' );
    }
}

if ( ! function_exists( 'villegas_course_checklist_render_dropdown' ) ) {
    /**
     * Render a dropdown button with the provided actions.
     *
     * @param array $actions Dropdown action definitions.
     */
    function villegas_course_checklist_render_dropdown( $actions ) {
        if ( empty( $actions ) ) {
            return;
        }

        echo '<div class="villegas-dropdown">';
        echo '<button type="button" class="button button-primary create-dropdown" aria-haspopup="true" aria-expanded="false">' . esc_html__( 'CREATE', 'villegas-courses' ) . '</button>';
        echo '<ul class="dropdown-menu" role="menu">';

        foreach ( $actions as $action ) {
            $class      = isset( $action['class'] ) ? $action['class'] : '';
            $attributes = isset( $action['attributes'] ) && is_array( $action['attributes'] ) ? $action['attributes'] : [];

            $attr_html = '';
            foreach ( $attributes as $attr_key => $attr_value ) {
                $attr_html .= sprintf( ' %s="%s"', esc_attr( $attr_key ), esc_attr( $attr_value ) );
            }

            echo '<li role="presentation">';
            echo '<button type="button" class="dropdown-item ' . esc_attr( $class ) . '" role="menuitem"' . $attr_html . '>' . esc_html( $action['label'] ) . '</button>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
    }
}

/**
 * Render the Course Checklist admin page.
 */
function villegas_render_course_checklist_page() {
    global $wpdb;

    $courses = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'sfwd-courses' AND post_status = 'publish' ORDER BY post_title ASC"
    );

    wp_enqueue_script(
        'villegas-course-checklist',
        plugin_dir_url( __FILE__ ) . '../js/course-checklist.js',
        [ 'jquery' ],
        '1.0.0',
        true
    );

    wp_localize_script(
        'villegas-course-checklist',
        'villegas_checklist',
        [
            'nonce'   => wp_create_nonce( 'villegas_checklist_nonce' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'i18n'    => [
                'pricePrompt' => __( 'Enter product price (integer, no commas or dots):', 'villegas-courses' ),
                'priceError'  => __( 'Please enter a valid integer price greater than zero.', 'villegas-courses' ),
                'ajaxError'   => __( 'An unexpected error occurred. Please try again.', 'villegas-courses' ),
            ],
        ]
    );
    ?>
    <div class="wrap">
        <style>
            table.widefat.fixed.striped {
                max-width: 1000px;
            }

            .villegas-dropdown {
                position: relative;
                display: inline-block;
            }

            .villegas-dropdown .dropdown-menu {
                position: absolute;
                top: 100%;
                left: 0;
                z-index: 100;
                display: none;
                margin: 4px 0 0;
                padding: 4px 0;
                min-width: 180px;
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
                list-style: none;
            }

            .villegas-dropdown .dropdown-menu.show {
                display: block;
            }

            .villegas-dropdown .dropdown-item {
                display: block;
                width: 100%;
                padding: 6px 12px;
                text-align: left;
                background: transparent;
                border: none;
                color: #1d2327;
                cursor: pointer;
            }

            .villegas-dropdown .dropdown-item:hover,
            .villegas-dropdown .dropdown-item:focus {
                background-color: #f0f0f1;
                outline: none;
            }

            .villegas-product-form {
                display: flex;
                flex-direction: column;
                gap: 8px;
                padding: 8px 12px;
            }

            .villegas-product-form__label {
                font-weight: 600;
            }

            .villegas-product-form__input {
                width: 100%;
            }
        </style>
        <h1><?php esc_html_e( 'Course Checklist', 'villegas-courses' ); ?></h1>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Course ID', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'Course Title', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'First Quiz', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'Final Quiz', 'villegas-courses' ); ?></th>
                    <th><?php esc_html_e( 'Product', 'villegas-courses' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $courses ) ) : ?>
                    <?php foreach ( $courses as $course ) : ?>
                        <?php
                        $course_id = absint( $course->ID );
                        $course_title = isset( $course->post_title ) ? $course->post_title : '';

                        $first_quiz_id = 0;
                        $final_quiz_id = 0;
                        $product_id    = 0;

                        $ld_steps = null;

                        if ( class_exists( 'CourseQuizMetaHelper' ) ) {
                            $first_quiz_id = absint( CourseQuizMetaHelper::getFirstQuizId( $course_id ) );
                            $final_quiz_id = absint( CourseQuizMetaHelper::getFinalQuizId( $course_id ) );
                        }

                        if ( $first_quiz_id && ! villegas_course_checklist_quiz_exists( $first_quiz_id ) ) {
                            delete_post_meta( $course_id, '_first_quiz_id' );
                            $first_quiz_id = 0;
                        }

                        if ( $final_quiz_id && ! villegas_course_checklist_quiz_exists( $final_quiz_id ) ) {
                            delete_post_meta( $course_id, '_final_quiz_id' );
                            $final_quiz_id = 0;
                        }

                        if ( ! $first_quiz_id ) {
                            $first_meta = absint( get_post_meta( $course_id, '_first_quiz_id', true ) );

                            if ( $first_meta && villegas_course_checklist_quiz_exists( $first_meta ) ) {
                                $first_quiz_id = $first_meta;
                            } elseif ( $first_meta ) {
                                delete_post_meta( $course_id, '_first_quiz_id' );
                            }
                        }

                        if ( ! $final_quiz_id ) {
                            if ( ! is_array( $ld_steps ) ) {
                                $ld_steps = get_post_meta( $course_id, 'ld_course_steps', true );
                            }

                            if ( is_array( $ld_steps ) ) {
                                $original_steps = $ld_steps;

                                while ( true ) {
                                    $candidate = absint( villegas_course_checklist_find_final_quiz_id( $ld_steps ) );

                                    if ( ! $candidate ) {
                                        $final_quiz_id = 0;
                                        break;
                                    }

                                    if ( villegas_course_checklist_quiz_exists( $candidate ) ) {
                                        $final_quiz_id = $candidate;
                                        break;
                                    }

                                    $updated_steps = villegas_course_checklist_remove_quiz_from_steps( $ld_steps, $candidate );

                                    if ( $updated_steps === $ld_steps ) {
                                        $final_quiz_id = 0;
                                        break;
                                    }

                                    $ld_steps = $updated_steps;
                                }

                                if ( $ld_steps !== $original_steps ) {
                                    if ( empty( $ld_steps ) ) {
                                        delete_post_meta( $course_id, 'ld_course_steps' );
                                    } else {
                                        update_post_meta( $course_id, 'ld_course_steps', $ld_steps );
                                    }
                                }
                            }
                        }

                        if ( function_exists( 'villegas_get_course_product_id' ) ) {
                            $product_id = absint( villegas_get_course_product_id( $course_id ) );
                        }

                        if ( $product_id && ! villegas_course_checklist_product_exists( $product_id ) ) {
                            villegas_course_checklist_clear_product_links( $course_id );
                            $product_id = 0;
                        }

                        if ( ! $product_id ) {
                            $product_id = absint( get_post_meta( $course_id, '_related_product', true ) );

                            if ( $product_id && ! villegas_course_checklist_product_exists( $product_id ) ) {
                                villegas_course_checklist_clear_product_links( $course_id );
                                $product_id = 0;
                            }
                        }

                        if ( ! $product_id ) {
                            $product_id = absint( get_post_meta( $course_id, '_linked_woocommerce_product', true ) );

                            if ( $product_id && ! villegas_course_checklist_product_exists( $product_id ) ) {
                                villegas_course_checklist_clear_product_links( $course_id );
                                $product_id = 0;
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( $course_id ); ?></td>
                            <td><?php echo esc_html( $course_title ); ?></td>
                            <td>
                                <?php if ( $first_quiz_id ) : ?>
                                    <?php echo esc_html( $first_quiz_id ); ?>
                                <?php else : ?>
                                    <?php
                                    $first_quiz_actions = [
                                        [
                                            'label'      => __( 'Create New', 'villegas-courses' ),
                                            'class'      => 'action-create-quiz',
                                            'attributes' => [
                                                'data-course-id' => $course_id,
                                                'data-quiz-type' => 'first',
                                            ],
                                        ],
                                    ];

                                    if ( $final_quiz_id ) {
                                        $first_quiz_actions[] = [
                                            'label'      => __( 'Copy Opposite Quiz', 'villegas-courses' ),
                                            'class'      => 'action-clone-quiz',
                                            'attributes' => [
                                                'data-course-id' => $course_id,
                                                'data-quiz-type' => 'first',
                                            ],
                                        ];
                                    }

                                    villegas_course_checklist_render_dropdown( $first_quiz_actions );
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $final_quiz_id ) : ?>
                                    <?php echo esc_html( $final_quiz_id ); ?>
                                <?php else : ?>
                                    <?php
                                    $final_quiz_actions = [
                                        [
                                            'label'      => __( 'Create New', 'villegas-courses' ),
                                            'class'      => 'action-create-quiz',
                                            'attributes' => [
                                                'data-course-id' => $course_id,
                                                'data-quiz-type' => 'final',
                                            ],
                                        ],
                                    ];

                                    if ( $first_quiz_id ) {
                                        $final_quiz_actions[] = [
                                            'label'      => __( 'Copy Opposite Quiz', 'villegas-courses' ),
                                            'class'      => 'action-clone-quiz',
                                            'attributes' => [
                                                'data-course-id' => $course_id,
                                                'data-quiz-type' => 'final',
                                            ],
                                        ];
                                    }

                                    villegas_course_checklist_render_dropdown( $final_quiz_actions );
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $product_id ) : ?>
                                    <?php echo esc_html( $product_id ); ?>
                                <?php else : ?>
                                    <div class="villegas-dropdown villegas-product-dropdown">
                                        <button type="button" class="button button-primary create-dropdown" aria-haspopup="true" aria-expanded="false">
                                            <?php esc_html_e( 'CREATE', 'villegas-courses' ); ?>
                                        </button>
                                        <div class="dropdown-menu" role="menu">
                                            <form class="villegas-product-form" data-course-id="<?php echo esc_attr( $course_id ); ?>">
                                                <label for="villegas-product-price-<?php echo esc_attr( $course_id ); ?>" class="villegas-product-form__label">
                                                    <?php esc_html_e( 'Price', 'villegas-courses' ); ?>
                                                </label>
                                                <input
                                                    id="villegas-product-price-<?php echo esc_attr( $course_id ); ?>"
                                                    class="villegas-product-form__input"
                                                    type="number"
                                                    name="price"
                                                    min="1"
                                                    step="1"
                                                    inputmode="numeric"
                                                    required
                                                />
                                                <button type="submit" class="button button-primary villegas-product-form__submit">
                                                    <?php esc_html_e( 'Create Product', 'villegas-courses' ); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e( 'No published courses found.', 'villegas-courses' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
