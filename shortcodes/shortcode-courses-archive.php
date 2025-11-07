<?php
/**
 * Shortcode to render the LearnDash course archive grid anywhere.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'villegas_courses_archive_shortcode' ) ) {
    add_shortcode( 'villegas_courses_archive', 'villegas_courses_archive_shortcode' );

    /**
     * Render the course archive grid.
     *
     * @return string
     */
    function villegas_courses_archive_shortcode() {
        if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../classes/class-course-quiz-helper.php';
        }

        global $wpdb;

        $user_id = get_current_user_id();

        $query = new WP_Query(
            [
                'post_type'      => 'sfwd-courses',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => [
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ],
            ]
        );

        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="courses-container">';

            while ( $query->have_posts() ) {
                $query->the_post();

                $course_id       = get_the_ID();
                $first_quiz_id   = CourseQuizMetaHelper::getFirstQuizId( $course_id );
                $final_quiz_id   = CourseQuizMetaHelper::getFinalQuizId( $course_id );
                $first_score     = 0;
                $final_score     = 0;
                $first_completed = false;

                if ( $user_id ) {
                    $first_score = villegas_get_latest_quiz_percentage( $wpdb, $user_id, $first_quiz_id );
                    $final_score = villegas_get_latest_quiz_percentage( $wpdb, $user_id, $final_quiz_id );

                    if ( $first_quiz_id ) {
                        $first_completed = villegas_is_quiz_completed( $first_quiz_id, $user_id );
                    }
                }

                $first_score_display = $first_completed ? $first_score : 0;
                $first_progress      = $first_completed ? $first_score : 0;
                $final_progress      = $final_score;

                $course_permalink = get_permalink( $course_id );
                $classes          = get_post_class( 'course-item', $course_id );
                $class_attribute  = $classes ? 'class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';

                echo '<article id="post-' . esc_attr( $course_id ) . '" ' . $class_attribute . '>';

                echo '<a href="' . esc_url( $course_permalink ) . '" class="course-thumbnail">';
                if ( has_post_thumbnail( $course_id ) ) {
                    echo get_the_post_thumbnail( $course_id, 'medium' );
                }
                echo '</a>';

                echo '<div id="bottom-course-card">';

                echo '<header class="entry-header">';
                echo '<h2 class="entry-title">';
                echo '<a href="' . esc_url( $course_permalink ) . '">' . esc_html( get_the_title( $course_id ) ) . '</a>';
                echo '</h2>';
                echo '</header>';

                echo '<div class="entry-content">';
                echo wp_kses_post( get_the_excerpt( $course_id ) );
                echo '</div>';

                echo '<div class="course-post-evaluations">';

                echo '<div class="evaluation-row">';
                if ( ! is_user_logged_in() ) {
                    $first_redirect = $first_quiz_id ? get_permalink( $first_quiz_id ) : $course_permalink;
                    $first_login    = add_query_arg( 'redirect_to', rawurlencode( $first_redirect ), home_url( '/mi-cuenta/' ) );
                    echo '<a class="evaluation-title" href="' . esc_url( $first_login ) . '">' . esc_html__( 'Prueba Inicial', 'villegas-courses' ) . '</a>';
                } else {
                    $first_link = $first_quiz_id ? get_permalink( $first_quiz_id ) : $course_permalink;
                    echo '<a class="evaluation-title" href="' . esc_url( $first_link ) . '">' . esc_html__( 'Prueba Inicial', 'villegas-courses' ) . '</a>';
                }
                echo '<div class="progress-bar" id="progress-first">';
                echo '<div class="progress" style="width: ' . esc_attr( $first_progress ) . '%;"></div>';
                echo '</div>';
                echo '<span class="evaluation-percentage">' . esc_html( $first_score_display ) . '%</span>';
                echo '</div>';

                echo '<div class="evaluation-row">';
                if ( ! is_user_logged_in() ) {
                    $final_redirect = $final_quiz_id ? get_permalink( $final_quiz_id ) : $course_permalink;
                    $final_login    = add_query_arg( 'redirect_to', rawurlencode( $final_redirect ), home_url( '/mi-cuenta/' ) );
                    echo '<a class="evaluation-title" href="' . esc_url( $final_login ) . '">' . esc_html__( 'Prueba Final', 'villegas-courses' ) . '</a>';
                } elseif ( 0 === intval( $first_progress ) ) {
                    echo '<span class="evaluation-title" style="opacity: 0.5; cursor: not-allowed;">' . esc_html__( 'Prueba Final', 'villegas-courses' ) . '</span>';
                } else {
                    $completed = function_exists( 'learndash_is_user_complete' ) ? learndash_is_user_complete( $user_id, $course_id ) : false;
                    $final_link = ( $completed && $final_quiz_id ) ? get_permalink( $final_quiz_id ) : $course_permalink;
                    echo '<a class="evaluation-title" href="' . esc_url( $final_link ) . '">' . esc_html__( 'Prueba Final', 'villegas-courses' ) . '</a>';
                }
                echo '<div class="progress-bar" id="progress-final">';
                echo '<div class="progress" style="width: ' . esc_attr( $final_progress ) . '%;"></div>';
                echo '</div>';
                echo '<span class="evaluation-percentage">' . esc_html( $final_score ) . '%</span>';
                echo '</div>';

                echo '</div>';

                echo '<footer class="entry-footer">';
                echo '<a href="' . esc_url( $course_permalink ) . '" class="btn">' . esc_html__( 'Ver Curso', 'villegas-courses' ) . '</a>';
                echo '</footer>';

                echo '</div>';

                echo '</article>';
            }

            echo '</div>';
        } else {
            echo '<p class="no-courses">' . esc_html__( 'No hay cursos disponibles en este momento.', 'villegas-courses' ) . '</p>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }
}

if ( ! function_exists( 'villegas_get_latest_quiz_percentage' ) ) {
    /**
     * Get the latest quiz percentage for a user/quiz pair.
     *
     * @param wpdb $wpdb
     * @param int  $user_id
     * @param int  $quiz_id
     *
     * @return int
     */
    function villegas_get_latest_quiz_percentage( $wpdb, $user_id, $quiz_id ) {
        $user_id = intval( $user_id );
        $quiz_id = intval( $quiz_id );

        if ( ! $user_id || ! $quiz_id ) {
            return 0;
        }

        $attempt = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT activity_id
                   FROM {$wpdb->prefix}learndash_user_activity
                  WHERE user_id = %d
                    AND post_id = %d
                    AND activity_type = 'quiz'
               ORDER BY activity_completed DESC
                  LIMIT 1",
                $user_id,
                $quiz_id
            )
        );

        if ( ! $attempt ) {
            return 0;
        }

        $percentage = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT activity_meta_value
                   FROM {$wpdb->prefix}learndash_user_activity_meta
                  WHERE activity_id = %d
                    AND activity_meta_key = 'percentage'",
                $attempt->activity_id
            )
        );

        return $percentage !== null ? round( floatval( $percentage ) ) : 0;
    }
}
