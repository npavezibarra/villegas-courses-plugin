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
        }

        #lesson-navigation {
            width: 30%;
            padding: 20px 0px;
            background-color: #f9f9f9;
            border-right: 1px solid #ddd;
            overflow-y: auto;
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
            width: 10px;
            height: 10px;
            margin-right: 10px;
            border-radius: 50%;
            background-color: #ddd;
        }

        .lesson-item.completed .lesson-circle {
            background: #ff9800;
        }

        .lesson-item.completed {
            padding: 10px;
        }

        .lesson-item.current-lesson .lesson-circle {
            background-color: #dfdfdf;
        }
    </style>
</head>

<body <?php body_class(); ?>>
<?php
// Load the default Twenty Twenty-Four header template part
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
?>

<div id="lesson-wrapper">
    <!-- Lesson Navigation -->
    <div id="lesson-navigation">
        <h3>Contenido del curso</h3>
        <?php
        // Navigation logic
        if (is_singular('sfwd-lessons')) {
            $course_id = learndash_get_course_id();
            $current_lesson_id = get_the_ID();
            $user_id = get_current_user_id();

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

                for ($step_index = 0; $step_index < count($lessons) + count($section_headers); $step_index++) {
                    $current_section = array_filter($section_headers, function ($header) use ($step_index) {
                        return isset($header['order']) && $header['order'] == $step_index;
                    });

                    if (!empty($current_section)) {
                        $current_section = reset($current_section);
                        echo '<li class="course-section-header" style="margin-bottom: 10px; padding: 10px;">';
                        echo '<h4>' . esc_html($current_section['post_title']) . '</h4>';
                        echo '</li>';
                        continue;
                    }

                    if ($lesson_index < count($lessons)) {
                        $lesson_post = $lessons[$lesson_index];
                        $is_completed = learndash_is_lesson_complete($user_id, $lesson_post->ID);
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
                        echo '<li class="lesson-item" style="display: flex; align-items: center; margin-bottom: 5px;">';
                        echo '<img src="https://cdn-icons-png.flaticon.com/512/3965/3965068.png" alt="Icono Examen" style="width: 20px; height: 20px; margin-right: 10px;">';
                        echo '<a href="' . esc_url(get_permalink(get_the_ID())) . '" style="text-decoration: none; color: #333;">' . esc_html(get_the_title()) . '</a>';
                        echo '</li>';
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
            $status = learndash_is_item_complete(get_the_ID(), $user_id, $course_id) ? 'complete' : 'incomplete';

            // Display the status bubble
            if ($status === 'complete') {
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

<?php
// Load the default Twenty Twenty-Four footer template part
echo do_blocks('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');

wp_footer(); 
?>
</body>
</html>
