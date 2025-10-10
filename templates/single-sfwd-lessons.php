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
            flex-direction: column;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            gap: 20px;
            padding: 20px;
        }

        #lesson-content {
            width: 100%;
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

        /* Apply spacing for the header group rendered by the site editor */
        body.single-sfwd-lessons .wp-block-group.alignwide.has-base-background-color.has-background.has-global-padding.is-layout-constrained.wp-block-group-is-layout-constrained {
            border-bottom: 1px solid black !important;
        }

        /* Ensure the main content group keeps the lesson nav and content apart */
        body.single-sfwd-lessons .wp-block-group.alignwide.is-content-justification-space-between.is-layout-flex.wp-block-group-is-layout-flex {
            justify-content: space-between;
        }

        @media screen and (min-width: 1024px) {
            #lesson-wrapper {
                flex-direction: row;
                align-items: flex-start;
            }

            #lesson-content {
                width: 70%;
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
    <?php if (is_singular('sfwd-lessons')) : ?>
        <!-- Backdrop -->
        <div id="menu-backdrop"
             class="fixed inset-0 bg-black bg-opacity-40 z-10 hidden transition-opacity duration-300"></div>

        <!-- Lesson Navigation -->
        <div id="lesson-navigation"
             class="fixed top-4 right-4 bg-gray-200 w-11/12 max-w-sm max-h-[80vh] rounded-xl shadow-2xl p-4
                overflow-y-auto z-20 transition-all duration-300 transform opacity-0 -translate-y-96 invisible
                md:static md:w-1/3 md:max-h-full md:opacity-100 md:translate-y-0 md:visible md:block">
            <h3 class="text-xl font-semibold mb-3 border-b pb-2 border-gray-300 text-gray-800">Contenido del curso</h3>
            <ul class="list-none pl-0 space-y-2">
                <?php
                $course_id = learndash_get_course_id();
                $current_lesson_id = get_the_ID();
                $user_id = get_current_user_id();

                if ($course_id) {
                    $lessons_query = new WP_Query(array(
                        'post_type' => 'sfwd-lessons',
                        'meta_key' => 'course_id',
                        'meta_value' => $course_id,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'posts_per_page' => -1,
                    ));

                    $course_builder_meta = get_post_meta($course_id, 'course_sections', true);
                    $section_headers = json_decode($course_builder_meta, true);

                    $lessons = $lessons_query->posts;
                    $lesson_index = 0;

                    $total_steps = count($lessons) + count($section_headers);

                    for ($step_index = 0; $step_index < $total_steps; $step_index++) {
                        $current_section = array_filter($section_headers ?? array(), function ($header) use ($step_index) {
                            return isset($header['order']) && (int) $header['order'] === $step_index;
                        });

                        if (!empty($current_section)) {
                            $current_section = reset($current_section);
                            echo '<li class="course-section-header text-sm font-semibold uppercase tracking-wide text-gray-600 pt-4">';
                            echo esc_html($current_section['post_title']);
                            echo '</li>';
                            continue;
                        }

                        if ($lesson_index < count($lessons)) {
                            $lesson_post = $lessons[$lesson_index];
                            $is_completed = learndash_is_lesson_complete($user_id, $lesson_post->ID, $course_id);
                            $current_lesson_class = ($lesson_post->ID == $current_lesson_id) ? 'current-lesson' : '';

                            $item_classes = array(
                                'lesson-item',
                                $is_completed ? 'completed' : 'not-completed',
                                $current_lesson_class,
                                'flex',
                                'items-center',
                                'gap-3',
                                'rounded-lg',
                                'px-3',
                                'py-2',
                                'transition',
                                'duration-200',
                            );

                            echo '<li class="' . esc_attr(implode(' ', array_filter($item_classes))) . '">';
                            echo '<span class="lesson-circle"></span>';
                            echo '<a class="text-gray-800 hover:text-orange-500 font-medium" href="' . esc_url(get_permalink($lesson_post->ID)) . '">';
                            echo esc_html($lesson_post->post_title);
                            echo '</a>';
                            echo '</li>';
                            $lesson_index++;
                        }
                    }

                    wp_reset_postdata();

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
                            echo '<li class="lesson-item flex items-center gap-3 rounded-lg px-3 py-2">';
                            echo '<img src="https://cdn-icons-png.flaticon.com/512/3965/3965068.png" alt="Icono Examen" class="w-5 h-5">';
                            echo '<a class="text-gray-800 hover:text-orange-500 font-medium" href="' . esc_url(get_permalink(get_the_ID())) . '">';
                            echo esc_html(get_the_title());
                            echo '</a>';
                            echo '</li>';
                        }
                    }

                    wp_reset_postdata();
                }
                ?>
            </ul>
        </div>

        <!-- Floating Button -->
        <button id="lesson-menu-toggle"
                class="fixed bottom-6 left-6 w-14 h-14 bg-white rounded-full shadow-xl flex items-center justify-center
                   cursor-pointer hover:shadow-2xl transition duration-300 ease-in-out transform active:scale-95 z-50 md:hidden">
            <svg id="menu-icon" class="h-6 w-6 text-gray-800 transition-transform duration-300 ease-in-out"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    <?php endif; ?>

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

<?php
// Load the default Twenty Twenty-Four footer template part
echo do_blocks('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');

wp_footer(); 
?>
</body>
</html>
