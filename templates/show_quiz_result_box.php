<?php
/**
 * Displays Quiz Result Box (Legacy Template).
 *
 * @since 3.2.0
 * @version 4.17.0
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
.learndash-wrapper .wpProQuiz_quiz_time {
    color: #728188;
    font-size: .8em;
    font-weight: 700;
    background: white;
    border: 1px solid #d5d5d5;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.table-quiz-name {
    text-align: left !important;
}

.quiz-results-container {
    border: 1px solid #d5d5d5;
    margin-bottom: 20px;
}

.extra-stats-container {
    border: 1px solid #d5d5d5;
    padding: 20px;
    border-radius: 8px;
    background: white;
}

.next-steps {
    background: white;
    padding: 40px;
    border: 1px solid #d5d5d5;
    border-radius: 8px;
}

.next-steps>h3 {
    margin-top: 0px;
}

.ld-quiz-actions {
    display: none !important;
}
</style>

<!-- (A) LearnDash default sending container -->
<div style="display: none;" class="wpProQuiz_sending">

    <p>
        <div>
            <?php
            echo wp_kses_post(
                SFWD_LMS::get_template(
                    'learndash_quiz_messages',
                    array(
                        'quiz_post_id' => $quiz->getID(),
                        'context'      => 'quiz_complete_message',
                        'message'      => sprintf(
                            esc_html_x('%s complete. Results are being recorded.', 'placeholder: Quiz', 'learndash'),
                            LearnDash_Custom_Label::get_label('quiz')
                        ),
                    )
                )
            );
            ?>
        </div>
        <div>
            <dd class="course_progress">
                <div class="course_progress_blue sending_progress_bar" style="width: 0%;"></div>
            </dd>
        </div>
    </p>
</div>

<!-- (B) LearnDash default results container -->
<div style="display: none;" class="wpProQuiz_results">

    <?php if (!$quiz->isHideResultCorrectQuestion()) :

        // Show quiz time if not hidden.
        if (!$quiz->isHideResultQuizTime()) { ?>
            <p class="wpProQuiz_quiz_time">
                <?php
                echo wp_kses_post(
                    SFWD_LMS::get_template(
                        'learndash_quiz_messages',
                        array(
                            'quiz_post_id' => $quiz->getID(),
                            'context'      => 'quiz_your_time_message',
                            'message'      => sprintf(
                                esc_html_x('Your time: %s', 'placeholder: quiz time.', 'learndash'),
                                '<span></span>'
                            ),
                        )
                    )
                );
                ?>
            </p>
        <?php } ?>

        <div style="display: none;">
            <span class="wpProQuiz_correct_answer">0</span>
            <span class="total-questions"><?php echo intval($question_count); ?></span>
        </div>

        <?php
        global $post;
        $quiz_id = isset($post->ID) ? $post->ID : 0;

        if (!class_exists('QuizAnalytics')) {
            require_once plugin_dir_path(__FILE__) . 'classes/class-quiz-analytics.php';
        }

        if (class_exists('QuizAnalytics')) {
            $quiz_checker = new QuizAnalytics($quiz_id);
            $is_first_quiz = $quiz_checker->isFirstQuiz(); // Método que determina si es First Quiz
        } else {
            $is_first_quiz = false;
        }

        // Usamos la función nativa de LearnDash para obtener el Course ID a partir del quiz.
        $course_id = learndash_get_course_id($quiz_id);

        // Determine the container ID for the current quiz.
        $current_container_id = $is_first_quiz ? "quiz-results-container" : "final-quiz-results-container";
        ?>
        <!-- Current Quiz Results Container -->
        <div id="<?php echo esc_attr($current_container_id); ?>" class="quiz-results-container responsive-quiz-box">
            <div class="quiz-flex-item quiz-info">
                <div class="quiz-name" style="font-weight: bold; font-size: 16px;">
                    <?php echo esc_html(get_the_title()); ?>
                </div>
                <div style="color: #666; font-size: 14px;">
                <?php echo esc_html(date_i18n('j \d\e F \d\e Y')); ?>
                </div>
            </div>

            <div class="quiz-flex-item quiz-bar">
                <div class="progress-bar-container" style="background: #e9ecef; border-radius: 15px; height: 20px; overflow: hidden;">
                    <div id="quiz-progress-bar" style="width: 0%; height: 100%; background: #ff9800; transition: width 0.5s ease;"></div>
                </div>
            </div>

            <div class="quiz-flex-item quiz-percentage">
                <span id="quiz-percentage" style="font-size: 24px; font-weight: bold;">0%</span>
            </div>
        </div>

        <style>
        .responsive-quiz-box {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 8px;
            gap: 0;
        }

        .quiz-flex-item.quiz-info {
            flex: 0 0 40%;
            padding: 10px;
            text-align: left;
        }

        .quiz-flex-item.quiz-bar {
            flex: 0 0 40%;
            padding: 10px;
        }

        .quiz-flex-item.quiz-percentage {
            flex: 0 0 20%;
            padding: 10px;
            text-align: right;
        }

        /* Responsive: cambia a columna */
        @media (max-width: 767px) {
            .responsive-quiz-box {
                flex-direction: column;
                align-items: stretch;
            }

            .quiz-flex-item {
                flex: 1 1 100%;
                text-align: center !important;
            }

            .quiz-bar {
                width: 100%;
            }

            .quiz-info {
                text-align: center;
            }
        }
        </style>

        <?php
        if (!defined('ABSPATH')) {
            exit;
        }

        global $wpdb, $post;
        $quiz_id = isset($post->ID) ? (int) $post->ID : 0;

        if (!class_exists('QuizAnalytics')) {
            require_once plugin_dir_path(__FILE__) . 'classes/class-quiz-analytics.php';
        }

        if (class_exists('QuizAnalytics')) {
            $quiz_checker = new QuizAnalytics($quiz_id);
            $is_first_quiz = $quiz_checker->isFirstQuiz(); // Determines if the current quiz is a First Quiz.
        } else {
            $is_first_quiz = false;
        }

        // Only process the button if the quiz is a First Quiz.
        if ($is_first_quiz) {
            // Retrieve the Course ID from the meta where _first_quiz_id equals the quiz ID.
            $course_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id
                     FROM {$wpdb->postmeta}
                     WHERE meta_key = '_first_quiz_id'
                       AND meta_value = %d
                     LIMIT 1",
                    $quiz_id
                )
            );

            // Retrieve the Product ID associated with the Course.
            // First, try to get it from the '_linked_woocommerce_product' meta.
            $product_id = get_post_meta($course_id, '_linked_woocommerce_product', true);
            if (empty($product_id)) {
                // If not found, search for a product with _related_course matching the Course ID.
                $args = array(
                    'post_type'      => 'product',
                    'meta_query'     => array(
                        array(
                            'key'     => '_related_course',
                            'value'   => $course_id,
                            'compare' => 'LIKE',
                        ),
                    ),
                    'posts_per_page' => 1,
                );
                $products = get_posts($args);
                if (!empty($products)) {
                    $product_id = $products[0]->ID;
                }
            }

            // Generate URLs.
            $course_url  = $course_id  ? get_permalink($course_id)  : '#';
            $product_url = $product_id ? get_permalink($product_id) : '#';

            // Check if the current user has access to the course.
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            $has_access = sfwd_lms_has_access($course_id, $user_id);

            // Set button text and URL based on access.
            if ($has_access) {
                $button_text = 'Ir al curso';
                $button_url  = $course_url;
            
                // Texto para acceso permitido
                ?>
                <div class="next-steps" style="margin-bottom: 20px;">
                    <h3>¿Qué pasos seguir ahora?</h3>
                    <p>Ahora puedes proceder a completar todas las lecciones incluidas en este curso sobre <strong><?php echo esc_html(get_the_title($course_id)); ?></strong>.</p>
                    <p>Una vez finalizadas, estarás listo para realizar la Prueba Final, que reflejará el progreso alcanzado durante el curso.</p>
                    <p>Recuerda que puedes avanzar a tu propio ritmo: algunos estudiantes lo completan en un día, mientras que otros pueden tardar más.</p>
                </div>
                <?php
            } else {
                $button_text = 'Comprar curso';
                $button_url  = $product_url;
            
                // Texto para acceso denegado
                ?>
                <div class="next-steps" style="margin-bottom: 20px;">
                    <h3>Continúa tu aprendizaje</h3>
                    <p>Ya has completado la Prueba Inicial, ahora puedes comprar el curso y acceder al contenido exclusivo sobre <strong><?php echo esc_html(get_the_title($course_id)); ?></strong>.</p>
                    <p>Al finalizarlo, podrás rendir la Prueba Final y comparar tu progreso respecto a tu evaluación inicial.</p>
                </div>
                <?php
            }
            
            ?>
            <div id="testing-button" style="margin-top: 20px;">
                <a href="<?php echo esc_url($button_url); ?>"
                style="background-color: black; color: white; font-weight: 600; padding: 10px 15px; font-size: 14px; text-decoration: none; border-radius: 4px; display: inline-block;">
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>

            <?php
        }
        ?>
<?php
if ( ! $is_first_quiz ) {

    /* ------------------------------------------------------------------
     * 1) Información del First Quiz
     * ------------------------------------------------------------------*/
    $first_quiz_id    = $quiz_checker->getFirstQuiz();
    $first_quiz_name  = ( $first_quiz_id && $first_quiz_id !== "Doesn't have" )
        ? get_the_title( $first_quiz_id )
        : "Doesn't exist";

    $perf                   = $quiz_checker->getFirstQuizPerformance();
    $first_quiz_percentage  = is_numeric( $perf['percentage'] )
        ? round( floatval( $perf['percentage'] ) )
        : 0;

    // ✅ Timestamp ya corregido en QuizAnalytics (activity_completed || activity_started)
    $first_quiz_date_ts = (int) $quiz_checker->getFirstQuizTimestamp();


    /* ------------------------------------------------------------------
     * 2) Información del Final Quiz (último intento registrado)
     * ------------------------------------------------------------------*/
    global $wpdb;
    $user_id       = get_current_user_id();

    $final_attempt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT activity_completed
               FROM {$wpdb->prefix}learndash_user_activity
              WHERE user_id       = %d
                AND post_id       = %d
                AND activity_type = 'quiz'
           ORDER BY activity_completed DESC
              LIMIT 1",
            $user_id,
            $quiz_id
        )
    );

    $final_quiz_date_ts = ! empty( $final_attempt->activity_completed )
        ? (int) $final_attempt->activity_completed
        : null;


    /* ------------------------------------------------------------------
     * 3) Diferencia de días (mínimo 1 día si ambas fechas existen)
     * ------------------------------------------------------------------*/
    $days_diff = 1;   // Valor por defecto
    if ( $first_quiz_date_ts && $final_quiz_date_ts ) {
        $diff_seconds   = $final_quiz_date_ts - $first_quiz_date_ts;
        $calculated     = floor( $diff_seconds / DAY_IN_SECONDS );
        $days_diff      = max( 1, $calculated );
    }

            ?>

            <!-- First Quiz Results Container -->
            <div id="first-quiz-results-container" class="quiz-results-container responsive-quiz-box" style="margin-top: 20px;">
                <div class="quiz-flex-item quiz-info">
                    <div class="quiz-name" style="font-weight: bold; font-size: 16px;">
                        <?php echo esc_html($first_quiz_name); ?>
                    </div>
                    <div style="color: #666; font-size: 14px;">
                        <?php echo $first_quiz_date_ts ? esc_html(date_i18n('j \d\e F \d\e Y', $first_quiz_date_ts)) : 'N/A'; ?>
                    </div>
                </div>

                <div class="quiz-flex-item quiz-bar">
                    <div class="progress-bar-container" style="background: #e9ecef; border-radius: 15px; height: 20px; overflow: hidden;">
                        <div id="first-quiz-progress-bar"
                             style="width: <?php echo esc_attr($first_quiz_percentage); ?>%; height: 100%; background: #ff9800; transition: width 0.5s ease;">
                        </div>
                    </div>
                </div>

                <div class="quiz-flex-item quiz-percentage">
                    <span id="first-quiz-percentage" style="font-size: 24px; font-weight: bold;">
                        <?php echo esc_html($first_quiz_percentage); ?>%
                    </span>
                </div>
            </div>

            <!-- Extra stats block (variación de conocimiento y días para completar) -->
            <div class="extra-stats-container" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-evenly; align-items: center;">
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 16px; color: #666;">Variación conocimientos</div>
                        <div id="knowledge-variation" style="font-size: 36px; color: #9fd99f; font-weight: bold;">
                            0% <span style="font-size: 28px;">▲</span>
                        </div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 16px; color: #666;">Completaste el curso en</div>
                        <div style="font-size: 36px; color: #333; font-weight: bold;">
                            <?php
                            $dias = intval($days_diff);
                            // Display "1 día" only if started and finished on the same day (days_diff = 0)
                            if ($dias === 0 && $first_quiz_date_ts && $final_quiz_date_ts) {
                                echo '1 día';
                            } else {
                                echo $dias . ' ' . ($dias === 1 ? 'día' : 'días');
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Al finalizar el quiz, actualizar la barra de progreso y calcular la variación.
                    jQuery(document).on('learndash-quiz-finished', function() {
                        var correctAnswers = parseInt(jQuery('.wpProQuiz_correct_answer').text(), 10);
                        var totalQuestions = parseInt(jQuery('.total-questions').text(), 10);
                        if (!isNaN(correctAnswers) && totalQuestions > 0) {
                            var finalPct = Math.round((correctAnswers / totalQuestions) * 100);
                            jQuery('#quiz-percentage').text(finalPct + '%');
                            jQuery('#quiz-progress-bar').css('width', finalPct + '%');

                            // Obtener el porcentaje del First Quiz
                            var firstQuizPct = <?php echo json_encode($first_quiz_percentage); ?>;
                            var variation = finalPct - firstQuizPct;
                            var arrow = variation >= 0 ? '▲' : '▼';
                            var color = variation >= 0 ? '#9fd99f' : 'red';

                            jQuery('#knowledge-variation')
                                .css('color', color)
                                .html(Math.abs(variation) + '% <span style="font-size: 28px;">' + arrow + '</span>');
                        }
                    });
                    // Ajustar el ancho inicial de la barra del First Quiz.
                    var pct = <?php echo json_encode(str_replace('%', '', $first_quiz_percentage)); ?>;
                    document.getElementById("first-quiz-progress-bar").style.width = pct + "%";
                });
            </script>
        <?php
        }


$user_id = get_current_user_id();
$puntaje_privado = get_user_meta($user_id, 'puntaje_privado', true);
$is_checked = ($puntaje_privado === '1' || $puntaje_privado === 1) ? 'checked' : '';
?>
<div class="quiz-private-toggle" style="background: #f9f9f9; padding: 15px; border-radius: 8px; text-align: center;">
    <label style="font-size: 15px; font-weight: 500;">
    <input type="checkbox" id="puntaje_privado_checkbox" data-user-id="<?php echo get_current_user_id(); ?>" <?php echo $is_checked; ?>>
        No mostrar mi puntaje en rankings públicos
    </label>
</div>


<?php
        // Opcional: configurar la fecha de finalización del Final Quiz.
        global $wpdb;
        $user_id = get_current_user_id();
        if (!$is_first_quiz) {
            $final_attempt = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT activity_completed
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
            if (!empty($final_attempt)) {
                if (!isset($quiz)) {
                    $quiz = new stdClass();
                }
                $quiz->completed_date = date('Y-m-d H:i:s', (int) $final_attempt->activity_completed);
            }
        }
        ?>

        <script>
        // Definir ajaxurl y si es First Quiz
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        var isFirstQuiz = <?php echo $is_first_quiz ? 'true' : 'false'; ?>;

        jQuery(document).ready(function($) {
            const firstQuizNonce = (typeof quizData !== 'undefined' && quizData.firstQuizNonce) ? quizData.firstQuizNonce : '';

            $(document).on('learndash-quiz-finished', function() {
                var correctAnswers = parseInt($('.wpProQuiz_correct_answer').text(), 10);
                var totalQuestions = parseInt($('.total-questions').text(), 10);

                if (!isNaN(correctAnswers) && totalQuestions > 0) {
                    var percentage = Math.round((correctAnswers / totalQuestions) * 100);
                    $('#quiz-percentage').text(percentage + '%');
                    $('#quiz-progress-bar').css('width', percentage + '%');

                    if (isFirstQuiz) {
                        $.post(ajaxurl, {
                            action: 'enviar_correo_first_quiz',
                            quiz_percentage: percentage,
                            quiz_id: <?php echo (int)$quiz_id; ?>,
                            nonce: firstQuizNonce
                        }, function(response) {
                            console.log('Correo enviado (First Quiz):', response);
                        });
                    }
                    // IMPORTANTE: No hacer nada aquí si es Final Quiz.
                }
            });
        });

        </script>

    <?php endif; ?>

    <p class="wpProQuiz_time_limit_expired" style="display: none;">
        <?php
        echo wp_kses_post(
            SFWD_LMS::get_template(
                'learndash_quiz_messages',
                array(
                    'quiz_post_id' => $quiz->getID(),
                    'context'      => 'quiz_time_has_elapsed_message',
                    'message'      => esc_html__('Time has elapsed', 'learndash'),
                )
            )
        );
        ?>
    </p>

    <?php
    // Bloque de resultados basados en puntos.
    if (!$quiz->isHideResultPoints()) { ?>
        <p class="wpProQuiz_graded_points" style="display: none;">
            <?php
            echo wp_kses_post(
                SFWD_LMS::get_template(
                    'learndash_quiz_messages',
                    array(
                        'quiz_post_id' => $quiz->getID(),
                        'context'      => 'quiz_earned_points_message',
                        'message'      => sprintf(
                            esc_html_x('Earned Point(s): %1$s of %2$s, (%3$s)', 'placeholder: points earned, points total, points percentage', 'learndash'),
                            '<span>0</span>',
                            '<span>0</span>',
                            '<span>0</span>'
                        ),
                        'placeholders' => array('0', '0', '0'),
                    )
                )
            );
            ?>
        </p>
    <?php }

    if (is_user_logged_in()) { ?>
        <p class="wpProQuiz_certificate" style="display: none;"><?php echo LD_QuizPro::certificate_link('', $quiz); ?></p>
        <?php echo LD_QuizPro::certificate_details($quiz); ?>
    <?php }

    if ($quiz->isShowAverageResult()) { ?>
        <div class="wpProQuiz_resultTable">
            <table>
                <tbody>
                    <tr>
                        <td class="wpProQuiz_resultName">
                            <?php
                            echo wp_kses_post(
                                SFWD_LMS::get_template(
                                    'learndash_quiz_messages',
                                    array(
                                        'quiz_post_id' => $quiz->getID(),
                                        'context'      => 'quiz_average_score_message',
                                        'message'      => esc_html__('Average score', 'learndash'),
                                    )
                                )
                            );
                            ?>
                        </td>
                        <td class="wpProQuiz_resultValue wpProQuiz_resultValue_AvgScore">
                            <div class="progress-meter" style="background-color: #6CA54C;"> </div>
                            <span class="progress-number"> </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="wpProQuiz_resultName">
                            <?php
                            echo wp_kses_post(
                                SFWD_LMS::get_template(
                                    'learndash_quiz_messages',
                                    array(
                                        'quiz_post_id' => $quiz->getID(),
                                        'context'      => 'quiz_your_score_message',
                                        'message'      => esc_html__('Your score', 'learndash'),
                                    )
                                )
                            );
                            ?>
                        </td>
                        <td class="wpProQuiz_resultValue wpProQuiz_resultValue_YourScore">
                            <div class="progress-meter"> </div>
                            <span class="progress-number"> </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <div class="wpProQuiz_catOverview" <?php $quiz_view->isDisplayNone($quiz->isShowCategoryScore()); ?>>
        <h4>
            <?php
            echo wp_kses_post(
                SFWD_LMS::get_template(
                    'learndash_quiz_messages',
                    array(
                        'quiz_post_id' => $quiz->getID(),
                        'context'      => 'learndash_categories_header',
                        'message'      => esc_html__('Categories', 'learndash'),
                    )
                )
            );
            ?>
        </h4>
        <div style="margin-top: 10px;">
            <ol>
                <?php
                foreach ($quiz_view->category as $cat) {
                    if (!$cat->getCategoryId()) {
                        $cat->setCategoryName(
                            wp_kses_post(
                                SFWD_LMS::get_template(
                                    'learndash_quiz_messages',
                                    array(
                                        'quiz_post_id' => $quiz->getID(),
                                        'context'      => 'learndash_not_categorized_messages',
                                        'message'      => esc_html__('Not categorized', 'learndash'),
                                    )
                                )
                            )
                        );
                    }
                    ?>
                    <li data-category_id="<?php echo esc_attr($cat->getCategoryId()); ?>">
                        <span class="wpProQuiz_catName"><?php echo esc_attr($cat->getCategoryName()); ?></span>
                        <span class="wpProQuiz_catPercent">0%</span>
                    </li>
                    <?php
                }
                ?>
            </ol>
        </div>
    </div>
    <div>
        <ul class="wpProQuiz_resultsList">
            <?php
            foreach ($result['text'] as $resultText) { ?>
                <li style="display: none;">
                    <div>
                        <?php
                        if ($quiz->is_result_message_enabled()) {
                            echo do_shortcode(apply_filters('comment_text', $resultText, null, null));
                        }
                        ?>
                    </div>
                </li>
            <?php } ?>
        </ul>
    </div>
    <?php
    if ($quiz->isToplistActivated()) {
        if ($quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_NORMAL) {
            echo do_shortcode('[LDAdvQuiz_toplist ' . $quiz->getId() . ' q="true"]');
        }
        $quiz_view->showAddToplist();
    }
    ?>
    <div class="ld-quiz-actions" style="margin: 10px 0px;">
        <?php
        $show_quiz_continue_buttom_on_fail = apply_filters('show_quiz_continue_buttom_on_fail', false, learndash_get_quiz_id_by_pro_quiz_id($quiz->getId()));
        ?>
        <div class='quiz_continue_link <?php if (true === $show_quiz_continue_buttom_on_fail) { echo ' show_quiz_continue_buttom_on_fail'; } ?>'></div>
        <?php if (!$quiz->isBtnRestartQuizHidden()) { ?>
            <input class="wpProQuiz_button wpProQuiz_button_restartQuiz" type="button" name="restartQuiz"
                   value="<?php echo wp_kses_post(
                       SFWD_LMS::get_template(
                           'learndash_quiz_messages',
                           array(
                               'quiz_post_id' => $quiz->getID(),
                               'context'      => 'quiz_restart_button_label',
                               'message'      => sprintf(
                                   esc_html_x('Restart %s', 'Restart Quiz Button Label', 'learndash'),
                                   LearnDash_Custom_Label::get_label('quiz')
                               ),
                           )
                       )
                   ); ?>"/>
        <?php } ?>
        <?php if (!$quiz->isBtnViewQuestionHidden()) { ?>
            <input class="wpProQuiz_button wpProQuiz_button_reShowQuestion" type="button" name="reShowQuestion"
                   value="<?php echo wp_kses_post(
                       SFWD_LMS::get_template(
                           'learndash_quiz_messages',
                           array(
                               'quiz_post_id' => $quiz->getID(),
                               'context'      => 'quiz_view_questions_button_label',
                               'message'      => sprintf(
                                   esc_html_x('View %s', 'View Questions Button Label', 'learndash'),
                                   LearnDash_Custom_Label::get_label('questions')
                               ),
                           )
                       )
                   ); ?>"/>
        <?php } ?>
        <?php if ($quiz->isToplistActivated() && $quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_BUTTON) { ?>
            <input class="wpProQuiz_button" type="button" name="showToplist"
                   value="<?php echo wp_kses_post(
                       SFWD_LMS::get_template(
                           'learndash_quiz_messages',
                           array(
                               'quiz_post_id' => $quiz->getID(),
                               'context'      => 'quiz_show_leaderboard_button_label',
                               'message'      => esc_html__('Show leaderboard', 'learndash'),
                           )
                       )
                   ); ?>"/>
        <?php } ?>
    </div>
</div>