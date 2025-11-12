<?php
// Template for displaying a page with the default Twenty Twenty-Four theme header and footer.
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        /* Styles for the layout */
        #lesson-wrapper {
            display: flex;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            align-items: flex-start;
            overflow: visible !important;
        }

        .my-container-class {
            align-items: flex-start;
            overflow: visible !important;
        }

        #lesson-navigation {
            width: 30%;
            padding: 20px 0px;
            background-color: #f9f9f9;
            border-right: 1px solid #000000;
        }

        li.course-section-header {
            border-top: 1px solid #000000 !important;
        }

        #lesson-content {
            width: 70%;
            padding: 20px;
        }

        .lesson-item {
            margin-bottom: 10px;
        }

        .lesson-circle {
            display: inline-block;
            width: 15px;
            height: 15px;
            margin-right: 10px;
            border-radius: 50%;
            background-color: #ddd;
        }

        .lesson-item.completed .lesson-circle {
            background: linear-gradient(314deg, rgb(236 196 146) 0%, rgb(198 176 100) 100%);
        }

        .lesson-item.completed {
            padding: 10px;
        }

        .lesson-item.current-lesson .lesson-circle {
            background-color: #dfdfdf;
        }

        /* Apply spacing for the header group rendered by the site editor */
        body.single-sfwd-lessons .wp-block-group.alignwide.has-base-background-color.has-background.has-global-padding.is-layout-constrained.wp-block-group-is-layout-constrained {
            border-bottom: 1px solid black !important;
        }

        /* Ensure the main content group keeps the lesson nav and content apart */
        body.single-sfwd-lessons .wp-block-group.alignwide.is-content-justification-space-between.is-layout-flex.wp-block-group-is-layout-flex {
            justify-content: space-between;
            align-items: flex-start;
            overflow: visible !important;
        }

        /* For screen sizes below 1020px */
        @media screen and (max-width: 1020px) {
            body.single-sfwd-lessons .wp-block-group.alignwide.is-content-justification-space-between.is-layout-flex.wp-block-group-is-layout-flex {
                justify-content: center;
            }
        }

        @media (max-width: 971px) {
            #lesson-navigation {
                margin-top: 20px;
            }
        }

        @media screen and (min-width: 970px) {
            #lesson-navigation {
                position: sticky;
                top: 32px;
                z-index: 100;
                max-height: 100vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            #lesson-navigation > h3 {
                flex-shrink: 0;
            }

            #lesson-navigation > ul {
                flex: 1;
                overflow-y: auto;
                max-height: calc(100vh - 120px);
                overscroll-behavior: contain;
            }
        }
    </style>
</head>

<body <?php body_class(); ?>>
<?php
// Load the default Twenty Twenty-Four header template part
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
?>

<div id="lesson-wrapper" class="my-container-class">
    <!-- Lesson Navigation -->
    <div id="lesson-navigation">
        <h3>Contenido del curso</h3>
        <?php
        // Navigation logic
        if (is_singular('sfwd-lessons')) {
            $course_id = learndash_get_course_id();
            $current_lesson_id = get_the_ID();
            $user_id = get_current_user_id();

            if ( ! class_exists( 'PoliteiaCourse' ) ) {
                require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
            }

            if ( ! class_exists( 'Politeia_Quiz_Stats' ) ) {
                require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-quiz-stats.php';
            }

            if ($course_id) {
                // Get lessons
                $lessons_query = new WP_Query(array(
                    'post_type' => 'sfwd-lessons',
                    'meta_key' => 'course_id',
                    'meta_value' => $course_id,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'posts_per_page' => -1,
                ));

                // Section headers
                $course_builder_meta = get_post_meta($course_id, 'course_sections', true);
                $section_headers = json_decode($course_builder_meta, true);

                echo '<ul style="list-style-type: none; padding-left: 0;">';

                $lessons = $lessons_query->posts;
                $lesson_index = 0;
                $all_lessons_completed = true;
                $first_quiz_inserted = false;
                $first_quiz_nav_item = '';

                $first_quiz_id = class_exists( 'PoliteiaCourse' ) ? intval( PoliteiaCourse::getFirstQuizId( $course_id ) ) : 0;

                if ( $first_quiz_id ) {
                    $first_quiz_post = get_post( $first_quiz_id );

                    if ( $first_quiz_post instanceof WP_Post ) {
                        $first_quiz_title = get_the_title( $first_quiz_post );
                        $first_quiz_link  = get_permalink( $first_quiz_post );
                        $first_quiz_score = '0%';

                        if ( class_exists( 'Politeia_Quiz_Stats' ) ) {
                            $first_quiz_stats   = new Politeia_Quiz_Stats( $first_quiz_id, $user_id );
                            $first_quiz_summary = $first_quiz_stats->get_current_quiz_summary();
                            $rounded_percentage = $first_quiz_summary['percentage_rounded'] ?? null;

                            if ( ! is_null( $rounded_percentage ) ) {
                                $first_quiz_score = intval( $rounded_percentage ) . '%';
                            }
                        }

                        $first_quiz_nav_item = '<li class="lesson-item first-quiz-score" style="margin-bottom: 10px;">';
                        $first_quiz_nav_item .= '<a href="' . esc_url( $first_quiz_link ) . '" style="text-decoration: none; color: #333;">';
                        $first_quiz_nav_item .= esc_html( $first_quiz_title ) . ' - ' . esc_html( $first_quiz_score );
                        $first_quiz_nav_item .= '</a>';
                        $first_quiz_nav_item .= '</li>';
                    }
                }

                for ($step_index = 0; $step_index < count($lessons) + count($section_headers); $step_index++) {
                    $current_section = array_filter($section_headers, function ($header) use ($step_index) {
                        return isset($header['order']) && $header['order'] == $step_index;
                    });

                    if (!empty($current_section)) {
                        $current_section = reset($current_section);
                        echo '<li class="course-section-header" style="margin-bottom: 10px; padding: 10px;">';
                        echo '<h4>' . esc_html($current_section['post_title']) . '</h4>';
                        echo '</li>';
                        if ( ! $first_quiz_inserted && ! empty( $first_quiz_nav_item ) ) {
                            echo $first_quiz_nav_item;
                            $first_quiz_inserted = true;
                        }
                        continue;
                    }

                    if ($lesson_index < count($lessons)) {
                        $lesson_post = $lessons[$lesson_index];
                        $is_completed = learndash_is_lesson_complete($user_id, $lesson_post->ID, $course_id);
                        if ( ! $is_completed ) {
                            $all_lessons_completed = false;
                        }
                        $current_lesson_class = ($lesson_post->ID == $current_lesson_id) ? 'current-lesson' : '';

                        echo '<li class="lesson-item ' . ($is_completed ? 'completed' : 'not-completed') . ' ' . esc_attr($current_lesson_class) . '">';
                        echo '<span class="lesson-circle"></span>';
                        echo '<a href="' . esc_url(get_permalink($lesson_post->ID)) . '">' . esc_html($lesson_post->post_title) . '</a>';
                        echo '</li>';
                        $lesson_index++;
                    }
                }

                wp_reset_postdata();

                // Quizzes
                $final_quiz_id = absint(get_post_meta($course_id, '_final_quiz_id', true));

                $quiz_query = new WP_Query(array(
                    'post_type' => 'sfwd-quiz',
                    'meta_key' => 'course_id',
                    'meta_value' => $course_id,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'posts_per_page' => -1,
                ));

                if ($quiz_query->have_posts()) {
                    while ($quiz_query->have_posts()) {
                        $quiz_query->the_post();
                        $quiz_id = get_the_ID();

                        if ($quiz_id === $final_quiz_id) {
                            continue;
                        }

                        echo '<li class="lesson-item" style="display: flex; align-items: center; margin-bottom: 5px;">';
                        echo '<img src="https://cdn-icons-png.flaticon.com/512/3965/3965068.png" alt="Icono Examen" style="width: 20px; height: 20px; margin-right: 10px;">';
                        echo '<a href="' . esc_url(get_permalink($quiz_id)) . '" style="text-decoration: none; color: #333;">' . esc_html(get_the_title()) . '</a>';
                        echo '</li>';
                    }
                }

                if ( $final_quiz_id ) {
                    $final_quiz_post = get_post($final_quiz_id);

                    if ( $final_quiz_post && 'sfwd-quiz' === $final_quiz_post->post_type ) {
                        $final_quiz_title = get_the_title($final_quiz_post);
                        $final_quiz_permalink = get_permalink($final_quiz_post);
                        $final_quiz_classes = 'lesson-item final-quiz-item';

                        if ( $all_lessons_completed ) {
                            echo '<li class="' . esc_attr($final_quiz_classes) . '" style="display: flex; align-items: center; margin-bottom: 5px;">';
                            echo '<img src="https://cdn-icons-png.flaticon.com/512/3965/3965068.png" alt="Icono Examen" style="width: 20px; height: 20px; margin-right: 10px;">';
                            echo '<a href="' . esc_url($final_quiz_permalink) . '" style="text-decoration: none; color: #333;">' . esc_html($final_quiz_title) . '</a>';
                            echo '</li>';
                        } else {
                            echo '<li class="' . esc_attr($final_quiz_classes . ' final-quiz-locked') . '" style="display: flex; align-items: center; margin-bottom: 5px; opacity: 0.5; pointer-events: none;">';
                            echo '<img src="https://cdn-icons-png.flaticon.com/512/3965/3965068.png" alt="Icono Examen" style="width: 20px; height: 20px; margin-right: 10px;">';
                            echo '<span style="color: #333;">' . esc_html($final_quiz_title) . '</span>';
                            echo '</li>';
                        }
                    }
                }


                echo '</ul>';
            }
        }
        ?>
    </div>

    <!-- Lesson Content -->
    <div id="lesson-content">
    <div id="lesson-title-section">
    <?php
    // Ensure the global post is set correctly
    wp_reset_postdata();
    ?>
    <h3>
        <?php echo get_the_title(get_the_ID()); ?>
        <?php
        // Retrieve the course and user IDs
        $course_id = learndash_get_course_id(get_the_ID());
        $user_id = get_current_user_id();

        // Check access and completion status
        if ($course_id && is_user_logged_in()) {
            $is_complete = learndash_is_lesson_complete($user_id, get_the_ID(), $course_id);

            // Display the status bubble
            if ($is_complete) {
                echo '<span id="status-complete" style="margin-left: 10px;">VISTO</span>';
            } else {
                echo '<span id="status-incomplete" style="margin-left: 10px;">NO VISTO</span>';
            }
        }
        ?>
    </h3>
    <p id="pertecene-curso">
        <?php 
        // Check if a valid course ID is returned
        if ( $course_id ) {
            echo 'Curso: <a href="' . esc_url( get_permalink( $course_id ) ) . '">' . esc_html( get_the_title( $course_id ) ) . '</a>';
        } else {
            // If no course is associated, display a fallback message
            echo 'Esta lección no tiene un curso asociado.';
        }
        ?>
    </p>
</div>


        <div id="lesson-main-content">
            <?php 
            if (have_posts()) {
                while (have_posts()) {
                    the_post();
                    the_content();
                }
            }
            ?>
        </div>

        <div id="lesson-quiz">
            <?php
            $quizzes = learndash_get_lesson_quiz_list(get_the_ID());
            if (!empty($quizzes)) {
                echo '<h3>Quizzes</h3>';
                foreach ($quizzes as $quiz) {
                    echo '<div style="margin-bottom: 10px;">';
                    echo '<a href="' . esc_url(get_permalink($quiz['post']->ID)) . '">' . esc_html($quiz['post']->post_title) . '</a>';
                    echo '</div>';
                }
            } else {
                // echo '<p>No quizzes are associated with this lesson.</p>';
            }
            ?>
        </div>
</div>
</div>

<?php if ( is_singular('sfwd-lessons') ) : ?>
  <!-- Backdrop (hidden by default) -->
  <div id="vil-lesson-backdrop" class="vil-lesson-backdrop" aria-hidden="true"></div>

  <!-- Floating hamburger (mobile only via CSS) -->
  <button
    id="vil-lesson-toggle"
    class="vil-lesson-btn"
    type="button"
    aria-controls="lesson-navigation"
    aria-expanded="false"
    aria-label="Abrir menú de lecciones"
  >
    <!-- simple hamburger -->
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <line x1="3" y1="6"  x2="21" y2="6"></line>
      <line x1="3" y1="12" x2="21" y2="12"></line>
      <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
  </button>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navigation = document.getElementById('lesson-navigation');
        const mediaQuery = window.matchMedia('(min-width: 970px)');
        const body = document.body;
        const html = document.documentElement;

        if (!navigation) {
            return;
        }

        let scrollLocked = false;
        let previousBodyOverflow = body.style.overflow;
        let previousHtmlOverflow = html.style.overflow;

        const lockBodyScroll = function () {
            if (scrollLocked) {
                return;
            }

            previousBodyOverflow = body.style.overflow;
            previousHtmlOverflow = html.style.overflow;

            body.style.overflow = 'hidden';
            html.style.overflow = 'hidden';
            scrollLocked = true;
        };

        const unlockBodyScroll = function () {
            if (!scrollLocked) {
                return;
            }

            body.style.overflow = previousBodyOverflow || '';
            html.style.overflow = previousHtmlOverflow || '';
            scrollLocked = false;
        };

        const handleMouseEnter = function () {
            if (mediaQuery.matches) {
                lockBodyScroll();
            }
        };

        const handleMouseLeave = function () {
            unlockBodyScroll();
        };

        const updateListeners = function () {
            if (mediaQuery.matches) {
                navigation.addEventListener('mouseenter', handleMouseEnter);
                navigation.addEventListener('mouseleave', handleMouseLeave);
            } else {
                navigation.removeEventListener('mouseenter', handleMouseEnter);
                navigation.removeEventListener('mouseleave', handleMouseLeave);
                unlockBodyScroll();
            }
        };

        updateListeners();

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', function () {
                updateListeners();
            });
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(function () {
                updateListeners();
            });
        }
    });
</script>

<?php
// Load the default Twenty Twenty-Four footer template part
echo do_blocks('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');

wp_footer(); 
?>
</body>
</html>
