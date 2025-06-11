<div id="about-course">
    <div id="course-content">
        <h4 style="color: black;">Contenido del curso</h4>
        <ul style="list-style-type: none; padding-left: 0;">
            <?php
            $course_id = get_the_ID();
            if ($course_id) {
                $lessons_query = new WP_Query(array(
                    'post_type' => 'sfwd-lessons',
                    'meta_key' => 'course_id',
                    'meta_value' => $course_id,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'posts_per_page' => -1,
                ));

                $user_id = get_current_user_id();
                $course_builder_meta = get_post_meta($course_id, 'course_sections', true);
                $section_headers = json_decode($course_builder_meta, true) ?? []; // Default to empty array if null

                $lessons = $lessons_query->posts;
                $lesson_index = 0;

                for ($step_index = 0; $step_index < count($lessons) + count($section_headers); $step_index++) {
                    $current_section = array_filter($section_headers, function($header) use ($step_index) {
                        return isset($header['order']) && $header['order'] == $step_index;
                    });

                    if (!empty($current_section)) {
                        $current_section = reset($current_section);
                        echo '<li class="course-section-header" style="margin-bottom: 10px; padding: 10px 0px;">';
                        echo '<h4>' . esc_html($current_section['post_title']) . '</h4>';
                        echo '</li>';
                        continue;
                    }

                    if ($lesson_index < count($lessons)) {
                        $lesson_post = $lessons[$lesson_index];
                        $lesson_id = $lesson_post->ID;

                        $is_completed = learndash_is_lesson_complete($user_id, $lesson_id);
                        $circle_color_class = $is_completed ? 'completed' : 'not-completed';

                        echo '<li class="lesson-item ' . esc_attr($circle_color_class) . '" style="display: flex; align-items: center; margin-bottom: 10px;">';
                        echo '<span class="lesson-circle" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 10px; background-color: ' . ($is_completed ? '#ff9800' : '#ccc') . ';"></span>';
                        echo '<a href="' . esc_url(get_permalink($lesson_id)) . '">' . esc_html(get_the_title($lesson_id)) . '</a>';
                        echo '</li>';
                        $lesson_index++;
                    }
                }

                wp_reset_postdata();

                $quizzes = learndash_get_course_quiz_list($course_id);
                if (!empty($quizzes)) {
                    echo '<hr>';
                    foreach ($quizzes as $quiz) {
                        echo '<li style="display: flex; align-items: center; margin-bottom: 8px;">';
                        echo '<img src="https://cdn-icons-png.flaticon.com/512/3965/3965068.png" alt="Icono Examen" style="width: 20px; height: 20px; margin-right: 10px;">';
                        echo '<a href="' . esc_url(get_permalink($quiz['post']->ID)) . '" style="text-decoration: none; color: #333;">' . esc_html($quiz['post']->post_title) . '</a>';
                        echo '</li>';
                    }                    
                } else {
                    echo '<p>No hay quizzes asociados a este curso.</p>';
                }
            }
            ?>
        </ul>
    </div>

    <div id="description-course">
        <h4>Sobre este curso</h4>
        <hr>
        <?php the_content(); ?>
    </div>
</div>