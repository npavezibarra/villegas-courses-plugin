<?php
/**
 * Shortcode: [cursos_finalizados]
 * Muestra los cursos en los que el usuario está inscrito, con estado de quizzes y estadísticas.
 */

function villegas_shortcode_cursos_finalizados() {
    if (!is_user_logged_in()) {
        return '<h2>Debes iniciar sesión para ver tus cursos.</h2>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $courses = ld_get_mycourses($user_id);

    if (empty($courses)) {
        return '<div style="display:flex; align-items:center; justify-content:center; height:300px;"><h2>Aún no te has inscrito a ningún curso.</h2></div>';
    }

    ob_start();
    echo '<div class="cursos-finalizados-grid">';

    foreach ($courses as $course_id) {
        if (get_post_status($course_id) !== 'publish') continue;

        echo '<div class="curso-finalizado-box">';
        echo "<h2><a href='" . esc_url(get_permalink($course_id)) . "' style='font-family: \"Cardo\";'>" . esc_html(get_the_title($course_id)) . "</a></h2>";

        if (has_post_thumbnail($course_id)) {
            $image_url = get_the_post_thumbnail_url($course_id, 'full');
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($course_id)) . '" style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px;">';
        }

        // Progreso por lecciones (excluyendo quizzes)
        $all_steps = learndash_get_course_steps($course_id);
        $lesson_steps = array_filter($all_steps, function ($step_id) {
            return get_post_type($step_id) === 'sfwd-lessons';
        });

        $completed_lessons = 0;
        foreach ($lesson_steps as $lesson_id) {
            if (learndash_is_lesson_complete($user_id, $lesson_id)) {
                $completed_lessons++;
            }
        }

        $percentage = count($lesson_steps) > 0 ? round(($completed_lessons / count($lesson_steps)) * 100) : 0;
        echo '<p id="progreso-porentaje">Completado: ' . $percentage . '%</p>';
        echo '<hr>';

        // Obtener IDs de quizzes
        $first_quiz_id = get_post_meta($course_id, '_first_quiz_id', true);
        $quiz_steps = learndash_course_get_steps_by_type($course_id, 'sfwd-quiz');
        $final_quiz_id = !empty($quiz_steps) ? end($quiz_steps) : 0;

        // === PRUEBA INICIAL ===
        echo '<div class="quiz-row-inicial">';
        echo '<div><div class="quiz-label">Prueba Inicial</div>';
        if (villegas_is_quiz_completed($first_quiz_id, $user_id)) {
            $date = get_quiz_completion_date($first_quiz_id, $user_id);
            echo '<div class="quiz-date">' . esc_html($date) . '</div>';
        } else {
            echo '<div class="quiz-date">-</div>';
        }
        echo '</div>';

        if (villegas_is_quiz_completed($first_quiz_id, $user_id)) {
            echo villegas_render_quiz_result($first_quiz_id, $user_id);
        } else {
            echo '<a class="quiz-status-link" style="font-size:11px; letter-spacing: 4px;" href="' . esc_url(get_permalink($first_quiz_id)) . '">NO RENDIDA</a>';
            echo '<span class="quiz-percentage">–</span>';
        }
        echo '</div>';
        echo '<hr>';
        // === PRUEBA FINAL ===
        if ($final_quiz_id) {
            $final_completed = villegas_is_quiz_completed($final_quiz_id, $user_id);
            $final_quiz_link = get_permalink($final_quiz_id);

            echo '<div class="quiz-row-final">';
            echo '<div>';
            echo '<div class="quiz-label">Prueba Final</div>';
            echo $final_completed ? '<div class="quiz-date">' . esc_html(get_quiz_completion_date($final_quiz_id, $user_id)) . '</div>' : '<div class="quiz-date">-</div>';
            echo '</div>';

            if ($final_completed) {
                echo villegas_render_quiz_result($final_quiz_id, $user_id);
            } else {
                if ($percentage >= 100) {
                    echo '<a class="quiz-status-link" style="font-size:11px; letter-spacing: 4px;" href="' . esc_url($final_quiz_link) . '">NO RENDIDA</a>';
                } else {
                    echo '<div style="font-size:11px; letter-spacing: 4px;">NO RENDIDA</div>';
                }
                echo '<span class="quiz-percentage">–</span>';
            }

            echo '</div>';
            echo '<hr style="margin-bottom: 0px;">';
            // === ESTADÍSTICAS ADICIONALES ===
if (
    $first_quiz_id && $final_quiz_id &&
    villegas_is_quiz_completed($first_quiz_id, $user_id) &&
    villegas_is_quiz_completed($final_quiz_id, $user_id)
) {
    $first_data = villegas_get_quiz_data($first_quiz_id, $user_id);
    $final_data = villegas_get_quiz_data($final_quiz_id, $user_id);

    $diff = $final_data['score'] - $first_data['score'];
    $diff_sign = ($diff >= 0) ? '+' : '';
    $color = ($diff >= 0) ? '#50c150' : 'red';
    $days = max(1, floor(($final_data['timestamp'] - $first_data['timestamp']) / DAY_IN_SECONDS));
    echo '<div class="quiz-row-stats" style="display: flex; justify-content: flex-end; flex-direction: column; align-items: flex-end; gap: 4px; padding-top: 15px;">';
    echo '<div class="quiz-variation" style="color:' . esc_attr($color) . '; font-size: 13px;">Variación: ' . $diff_sign . $diff . '%</div>';
    echo '<div class="quiz-days" style="font-size: 13px;">Lo completaste en: ' . $days . ' ' . ($days === 1 ? 'día' : 'días') . '</div>';
    echo '</div>';
}

        }

        echo '</div>'; // .curso-finalizado-box
    }
    
    

    echo '</div>'; // .cursos-finalizados-grid
    return ob_get_clean();
}
add_shortcode('cursos_finalizados', 'villegas_shortcode_cursos_finalizados');

// === FUNCIONES AUXILIARES ===

function villegas_is_quiz_completed($quiz_id, $user_id) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz'
         AND activity_completed IS NOT NULL LIMIT 1",
        $user_id, $quiz_id
    ));
}

function get_quiz_completion_date($quiz_id, $user_id) {
    global $wpdb;
    $timestamp = $wpdb->get_var($wpdb->prepare(
        "SELECT activity_completed FROM {$wpdb->prefix}learndash_user_activity
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz'
         ORDER BY activity_completed DESC LIMIT 1",
        $user_id, $quiz_id
    ));
    return $timestamp ? date_i18n('d F Y', $timestamp) : '-';
}

function villegas_get_quiz_data($quiz_id, $user_id) {
    global $wpdb;

    $attempt = $wpdb->get_row($wpdb->prepare(
        "SELECT activity_id, activity_completed FROM {$wpdb->prefix}learndash_user_activity 
         WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz' 
         ORDER BY activity_completed DESC LIMIT 1",
        $user_id, $quiz_id
    ));

    if (!$attempt) return null;

    $pct = $wpdb->get_var($wpdb->prepare(
        "SELECT activity_meta_value FROM {$wpdb->prefix}learndash_user_activity_meta
         WHERE activity_id = %d AND activity_meta_key = 'percentage'",
        $attempt->activity_id
    ));

    return [
        'score'     => round(floatval($pct)),
        'timestamp' => (int) $attempt->activity_completed,
    ];
}

function villegas_render_quiz_result($quiz_id, $user_id) {
    $data = villegas_get_quiz_data($quiz_id, $user_id);
    if (!$data) return '<span class="quiz-percentage">-</span>';

    ob_start(); ?>
    <div class="progress-bar-container">
        <div class="progress-bar" style="width: <?php echo esc_attr($data['score']); ?>%;"></div>
    </div>
    <div class="quiz-percentage"><?php echo esc_html($data['score']); ?>%</div>
    <?php return ob_get_clean();
}
