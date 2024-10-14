<?php
ob_start();

// Function to auto-enroll the user in a course
function auto_enroll_user_in_course($user_id, $course_id) {
    if (!sfwd_lms_has_access($course_id, $user_id)) {
        ld_update_course_access($user_id, $course_id);
    }
}

// Function to get the percentage of correct answers from the latest quiz attempt
function get_latest_quiz_percentage($user_id, $quiz_id) {
    $quiz_attempts = get_user_meta($user_id, '_sfwd-quizzes', true);
    $latest_attempt = null;

    // Check if there are any attempts recorded
    if (!empty($quiz_attempts)) {
        foreach ($quiz_attempts as $attempt) {
            if ($attempt['quiz'] == $quiz_id) {
                // Update latest attempt
                if (is_null($latest_attempt) || $attempt['time'] > $latest_attempt['time']) {
                    $latest_attempt = $attempt;
                }
            }
        }
    }

    // Determine if a quiz attempt was found
    if ($latest_attempt) {
        $percentage_correct = round(($latest_attempt['score'] / $latest_attempt['count']) * 100, 2);
        return [true, $percentage_correct];
    }

    return [false, 0];
}

// Function to display the progress bar and buttons
function mostrar_comprar_stats() {
    // Obtener el ID del usuario y el curso
    $user_id = get_current_user_id();
    $course_id = get_the_ID();
    
    // Verificar si el usuario está inscrito en el curso
    $is_enrolled = sfwd_lms_has_access($course_id, $user_id);

    // Obtener el valor del metabox (el quiz inicial asociado)
    $first_quiz_id = get_post_meta($course_id, '_first_quiz_id', true);

    // Obtener el ID del quiz final asociado al curso
    $quizzes = learndash_get_course_quiz_list($course_id);
    $final_quiz_id = !empty($quizzes) ? $quizzes[0]['post']->ID : null;

    // Verificar que $first_quiz_id no esté vacío y obtener el enlace del quiz
    if (!empty($first_quiz_id)) {
        // Obtén el slug del quiz y genera manualmente la URL correcta
        $quiz_post = get_post($first_quiz_id);
        if ($quiz_post) {
            $first_quiz_url = home_url('/quizzes/' . $quiz_post->post_name . '/'); // URL corregida manualmente
        }
    } else {
        $first_quiz_url = '#'; // O un enlace predeterminado si no hay quiz asociado
    }

    // Obtener el progreso del curso basado en las lecciones completadas
    $total_lessons = count(learndash_get_course_steps($course_id));
    $completed_lessons = learndash_course_get_completed_steps_legacy($user_id, $course_id);

    // Calcular el porcentaje de progreso basado solo en lecciones
    if ($total_lessons > 0) {
        $percentage_complete = min(100, ($completed_lessons / $total_lessons) * 100);
    } else {
        $percentage_complete = 0;
    }

    // If user is not logged in or not enrolled
    if (!is_user_logged_in()) {
        // Usuario no logueado
        ?>
        <div class="progress-widget" style="display: flex; align-items: center; background-color: #eeeeee; padding: 20px 20px; border-radius: 10px; width: 100%;">
        <div class="progress-bar" style="flex: 1; width: 50%; margin-right: 20px;">
            <div style="background-color: #e0e0e0; height: 10px; border-radius: 5px; position: relative;">
                <div style="width: <?php echo esc_attr($percentage_complete); ?>%; background-color: #4c8bf5; height: 100%; border-radius: 5px;"></div> <!-- Barra con progreso real -->
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #333;">
    <span>0%</span> <!-- Valor estático para 0% -->
    <span>100%</span> <!-- Valor estático para 100% -->
</div>

        </div>

            <div class="buy-button" style="flex: 1; width: 50%; text-align: right;">
                <button style="width: 80%; background-color: #4c8bf5; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; cursor: pointer;"
                        onclick="window.location.href='<?php echo wp_login_url(get_permalink($course_id)); ?>'">
                    Iniciar Sesión para Inscribirse
                </button>
            </div>
        </div>
        <?php
    } elseif (!$is_enrolled) {
        // Usuario logueado pero no inscrito
        if (isset($_GET['enroll']) && $_GET['enroll'] == 1) {
            // Enroll the user when clicking the button
            auto_enroll_user_in_course($user_id, $course_id);
            wp_redirect(get_permalink($course_id));
            exit();
        }
        ?>
        <div class="progress-widget" style="display: flex; align-items: center; background-color: #eeeeee; padding: 20px 20px; border-radius: 10px; width: 100%;">
            <div class="progress-bar" style="flex: 1; width: 50%; margin-right: 20px;">
                <div style="background-color: #e0e0e0; height: 10px; border-radius: 5px; position: relative;">
                    <div style="width: 0%; background-color: #ccc; height: 100%; border-radius: 5px;"></div> <!-- Barra vacía -->
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #333;">
                    <span>0%</span>
                    <span>100%</span>
                </div>
            </div>
            <div class="buy-button" style="flex: 1; width: 50%; text-align: right;">
                <button style="width: 80%; background-color: #4c8bf5; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; cursor: pointer;"
                        onclick="window.location.href='<?php echo add_query_arg('enroll', 1, get_permalink($course_id)); ?>'">
                    Tomar Curso
                </button>
            </div>
        </div>
        <?php
    } else {
        // Usuario logueado y ya inscrito en el curso
        ?>
        <div class="progress-widget" style="display: flex; align-items: center; background-color: #eeeeee; padding: 20px 20px; border-radius: 10px; width: 100%;">
            <div class="progress-bar" style="flex: 1; width: 50%; margin-right: 20px;">
                <div style="background-color: #e0e0e0; height: 10px; border-radius: 5px; position: relative;">
                    <div style="width: <?php echo esc_attr($percentage_complete); ?>%; background-color: #4c8bf5; height: 100%; border-radius: 5px;"></div> <!-- Barra con progreso real -->
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #333;">
                    <span><?php echo esc_html(round($percentage_complete)); ?>%</span>
                    <span>100%</span>
                </div>
            </div>
            <div class="test-buttons" style="flex: 1; text-align: right; display: flex; gap: 20px;">
                <!-- Quiz Percentage Display -->
                <div id="primer-test-score" style="display: flex; width: 50%; justify-content: center; align-items: center;">
                    <?php
                    // Check quiz attempts and display result
                    list($has_completed_quiz, $percentage_correct) = get_latest_quiz_percentage($user_id, $first_quiz_id);
                    if ($has_completed_quiz) {
                        echo "<strong>$percentage_correct%</strong>"; // Show percentage
                        echo '<p id="primer-test-legend">Primer Test</p>'; // Add the "Primer Test" paragraph below the percentage
                    } else {
                        echo '<a href="' . esc_url($first_quiz_url) . '" style="width: auto; color: white; border: none; width: 100%; height: auto; background: #2196f3; padding: 10px 0px; border-radius: 5px; font-size: 14px; text-align: center; text-decoration: none;">
                                Examen Incial
                            </a>';
                    }
                    ?>
                </div>

                <!-- Button with Tooltip for "Evaluación Final" -->
                <div id="final-test-button" class="tooltip" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <?php
                    // Check quiz attempts for the final exam
                    // Check quiz attempts for the final exam
                        list($has_completed_final_quiz, $final_percentage_correct) = get_latest_quiz_percentage($user_id, $final_quiz_id);

                        // Get the total number of lessons and completed lessons
                        $total_lessons = count(learndash_get_course_steps($course_id));
                        $completed_lessons = learndash_course_get_completed_steps_legacy($user_id, $course_id);

                        // Check if all lessons are completed
                        if ($completed_lessons === $total_lessons) {
                            // All lessons completed, show clickable button linking to the final quiz
                            echo '<a href="' . esc_url(get_permalink($final_quiz_id)) . '" style="width: 100%; background-color: #4c8bf5; color: white; border: none; padding: 10px 0px; border-radius: 5px; font-size: 14px; text-align: center; text-decoration: none;">
                                    Examen Final
                                </a>';
                        } elseif ($has_completed_final_quiz) {
                            // Show the percentage if the final quiz has been completed
                            echo "<strong>$final_percentage_correct%</strong><p>Examen Final</p>"; // Show percentage and label
                        } else {
                            // Not completed, show disabled button
                            echo '<button id="final-evaluation-button" style="width: 100%; background-color: #ccc; color: #333; border: none; padding: 10px 0px; border-radius: 5px; font-size: 14px; cursor: not-allowed; display: flex; align-items: center; justify-content: center;">
                                    Examen Final
                                </button>';
                        }

                    ?>
                    <span class="tooltiptext">Completa todas las lecciones de este curso para tomar el Examen Final</span>
                </div>
            </div>
        </div>
        <?php
    }
}

if (headers_sent($file, $line)) {
    echo "Headers already sent in $file on line $line";
}

?>
