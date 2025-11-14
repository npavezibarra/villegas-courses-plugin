<?php
/**
 * Displays Quiz Result Box.
 *
 * @since 3.2.0
 * @version 4.17.0
 *
 * @var WpProQuiz_Model_Quiz     $quiz           WpProQuiz_Model_Quiz instance.
 * @var array                    $shortcode_atts Array of shortcode attributes to create the Quiz.
 * @var int                      $question_count Number of Question to display.
 * @var array                    $result         Array of Quiz Result Messages.
 * @var WpProQuiz_View_FrontQuiz $quiz_view      WpProQuiz_View_FrontQuiz instance.
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$quiz_post_id = function_exists( 'learndash_get_quiz_id_by_pro_quiz_id' ) ? intval( learndash_get_quiz_id_by_pro_quiz_id( $quiz->getID() ) ) : 0;
?>
<div style="display: none;" class="wpProQuiz_sending">
<h4 class="wpProQuiz_header"><?php esc_html_e( 'Results', 'learndash' ); ?></h4>
<p>
<div>
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_complete_message',
// translators: placeholder: Quiz.
'message'      => sprintf( esc_html_x( '%s complete. Results are being recorded.', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
)
)
);
?>
</div>
<div>
<dd class="course_progress">
<div class="course_progress_blue sending_progress_bar" style="width: 0%;">
</div>
</dd>
</div>
</p>
</div>

<div style="display: none;" class="wpProQuiz_results">
    <hr>
    <h4 style="font-family: sans-serif; font-size: 34px;" class="wpProQuiz_header"><?php esc_html_e( 'Results', 'learndash' ); ?></h4>
<?php
if ( ! $quiz->isHideResultCorrectQuestion() ) {
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_questions_answered_correctly_message',
// translators: placeholder: correct answer, question count, questions.
'message'      => '<p>' . sprintf( esc_html_x( '%1$s of %2$s %3$s answered correctly', 'placeholder: correct answer, question count, questions', 'learndash' ), '<span class="wpProQuiz_correct_answer">0</span>', '<span>' . $question_count . '</span>', learndash_get_custom_label( 'questions' ) ) . '</p>',
'placeholders' => array( '0', $question_count ),
)
)
);
}

if ( ! $quiz->isHideResultQuizTime() ) {
?>
<p class="wpProQuiz_quiz_time">
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_your_time_message',
// translators: placeholder: quiz time.
'message'      => sprintf( esc_html_x( 'Your time: %s', 'placeholder: quiz time.', 'learndash' ), '<span></span>' ),
)
)
);
?>
</p>
<?php
}
?>
<p class="wpProQuiz_time_limit_expired" style="display: none;">
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_time_has_elapsed_message',
'message'      => esc_html__( 'Time has elapsed', 'learndash' ),
)
)
);
?>
</p>

<?php
if ( ! $quiz->isHideResultPoints() ) {
    if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../classes/class-course-quiz-helper.php';
    }

    if ( ! class_exists( 'PoliteiaCourse' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
    }

    $quiz_id = $quiz_post_id ? $quiz_post_id : intval( $quiz->getID() );

    $quiz_course_id = $quiz_id ? learndash_get_course_id( $quiz_id ) : 0;

    if ( ! $quiz_course_id && class_exists( 'CourseQuizMetaHelper' ) ) {
        $quiz_course_id = CourseQuizMetaHelper::getCourseFromQuiz( $quiz_id );
    }

    $first_quiz_id = $quiz_course_id ? (int) get_post_meta( $quiz_course_id, '_first_quiz_id', true ) : 0;

    if ( ! $first_quiz_id && class_exists( 'CourseQuizMetaHelper' ) ) {
        $first_quiz_id = (int) CourseQuizMetaHelper::getFirstQuizId( $quiz_course_id );
    }

    $final_quiz_id = 0;

    if ( $quiz_course_id ) {
        if ( class_exists( 'PoliteiaCourse' ) ) {
            $final_quiz_id = (int) PoliteiaCourse::getFinalQuizId( $quiz_course_id );
        }

        if ( ! $final_quiz_id ) {
            $final_quiz_id = (int) get_post_meta( $quiz_course_id, '_final_quiz_id', true );
        }
    }

    $is_first_quiz = $first_quiz_id && $quiz_id === (int) $first_quiz_id;
    $is_final_quiz = $final_quiz_id && $quiz_id === (int) $final_quiz_id;

    $user_id                 = get_current_user_id();
    $first_quiz_percentage   = null;
    $final_quiz_percentage   = null;
    $average_chart_markup    = '';

    if ( $quiz_id && shortcode_exists( 'villegas_quiz_attempts' ) ) {
        do_shortcode( sprintf( '[villegas_quiz_attempts id="%d"]', $quiz_id ) );
    }

    if ( $quiz_id && ! $is_final_quiz ) {
        $average_chart_markup = do_shortcode( sprintf( '[villegas_quiz_average_score quiz_id="%d" title="%s"]', $quiz_id, esc_attr__( 'Puntaje Promedio', 'villegas-courses' ) ) );
    }

    if ( $is_final_quiz && function_exists( 'villegas_get_last_quiz_percentage' ) ) {
        $final_quiz_percentage = villegas_get_last_quiz_percentage( $user_id, $quiz_id );

        if ( $first_quiz_id ) {
            $first_quiz_percentage = villegas_get_last_quiz_percentage( $user_id, $first_quiz_id );
        }
    }

    if ( $is_final_quiz ) {
        $final_quiz_user   = (int) get_current_user_id();
        $final_course_id   = $quiz_course_id ? (int) $quiz_course_id : 0;

        wp_localize_script(
            'custom-quiz-message',
            'FinalQuizEmailData',
            array(
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'quizId'      => $quiz_id,
                'courseId'    => $final_course_id,
                'userId'      => $final_quiz_user,
                'nonce'       => wp_create_nonce( 'villegas_final_quiz_email' ),
                'isFinalQuiz' => true,
            )
        );
    }
?>
<p class="wpProQuiz_points wpProQuiz_points--message" style="display: none;">
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_have_reached_points_message',
// translators: placeholder: points earned, points total.
'message'      => sprintf( esc_html_x( 'You have reached %1$s of %2$s point(s), (%3$s)', 'placeholder: points earned, points total', 'learndash' ), '<span>0</span>', '<span>0</span>', '<span>0</span>' ),
'placeholders' => array( '0', '0', '0' ),
)
)
);
?>
</p>
<?php if ( $is_final_quiz ) : ?>
    <?php
    $course_title = '';
    if ( $course_id ) {
        $course_title = get_the_title( $course_id );
    } elseif ( $quiz_id ) {
        $course_title = get_the_title( $quiz_id );
    }

    if ( ! $course_title && method_exists( $quiz, 'getName' ) ) {
        $course_title = (string) $quiz->getName();
    }

    $course_permalink = $course_id ? get_permalink( $course_id ) : '';
    $certificate_url  = '';
    $result_date      = '';
    $correct_answers  = null;
    $total_questions  = $question_count ? intval( $question_count ) : null;
    $time_string      = '';
    $attempt_meta     = array();
    $final_attempt    = null;

    if ( function_exists( 'politeia_get_latest_completed_quiz_attempt' ) ) {
        $final_attempt = politeia_get_latest_completed_quiz_attempt( $user_id, $quiz_id );
    }

    if ( is_array( $final_attempt ) ) {
        $attempt_meta = isset( $final_attempt['meta'] ) && is_array( $final_attempt['meta'] ) ? $final_attempt['meta'] : array();

        if ( ! empty( $final_attempt['formatted_date'] ) ) {
            $result_date = $final_attempt['formatted_date'];
        } elseif ( ! empty( $final_attempt['timestamp'] ) ) {
            $result_date = date_i18n( get_option( 'date_format' ), (int) $final_attempt['timestamp'] );
        }

        if ( isset( $final_attempt['score'] ) && null !== $final_attempt['score'] ) {
            $correct_answers = (int) round( floatval( $final_attempt['score'] ) );
        }

        if ( null === $total_questions && isset( $final_attempt['total_points'] ) && null !== $final_attempt['total_points'] ) {
            $total_questions = (int) round( floatval( $final_attempt['total_points'] ) );
        }
    }

    if ( empty( $result_date ) ) {
        $result_date = date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) );
    }

    if ( null === $correct_answers ) {
        foreach ( array( 'correct', 'score', 'points' ) as $meta_key ) {
            if ( isset( $attempt_meta[ $meta_key ] ) && is_numeric( $attempt_meta[ $meta_key ] ) ) {
                $correct_answers = (int) round( floatval( $attempt_meta[ $meta_key ] ) );
                break;
            }
        }
    }

    if ( null === $total_questions ) {
        foreach ( array( 'question_count', 'count', 'total_questions', 'total', 'questions', 'total_points' ) as $meta_key ) {
            if ( isset( $attempt_meta[ $meta_key ] ) && is_numeric( $attempt_meta[ $meta_key ] ) ) {
                $total_questions = (int) round( floatval( $attempt_meta[ $meta_key ] ) );
                break;
            }
        }
    }

    $duration_seconds = null;
    foreach ( array( 'time_spent', 'timespent', 'duration' ) as $time_key ) {
        if ( isset( $attempt_meta[ $time_key ] ) && is_numeric( $attempt_meta[ $time_key ] ) ) {
            $duration_seconds = (int) $attempt_meta[ $time_key ];
            break;
        }
    }

    if ( null !== $duration_seconds && $duration_seconds >= 0 ) {
        $hours   = floor( $duration_seconds / HOUR_IN_SECONDS );
        $minutes = floor( ( $duration_seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
        $seconds = $duration_seconds % MINUTE_IN_SECONDS;

        if ( $hours > 0 ) {
            $time_string = sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds );
        } else {
            $time_string = sprintf( '%02d:%02d', $minutes, $seconds );
        }
    }

    if ( empty( $time_string ) && ! empty( $attempt_meta['time_formatted'] ) ) {
        $time_string = (string) $attempt_meta['time_formatted'];
    }

    if ( ! $total_questions ) {
        $total_questions = intval( $question_count );
    }

    if ( function_exists( 'learndash_get_course_certificate_link' ) && $course_id && $user_id ) {
        $certificate_url = learndash_get_course_certificate_link( $course_id, $user_id );
    }

    if ( empty( $certificate_url ) && function_exists( 'learndash_get_certificate_link' ) && $quiz_id && $user_id ) {
        $certificate_url = learndash_get_certificate_link( $quiz_id, $user_id );
    }

    $initial_percent = null === $first_quiz_percentage ? null : (float) $first_quiz_percentage;
    $final_percent   = null === $final_quiz_percentage ? null : (float) $final_quiz_percentage;

    $correct_answers_display = null !== $correct_answers ? $correct_answers : 0;
    $total_questions_display = $total_questions ? $total_questions : 0;
    $time_display            = $time_string ? $time_string : '—';
    $result_date_display     = $result_date ? $result_date : '';
    ?>
    <div class="villegas-final-quiz-result">
        <div id="quiz-card" class="villegas-final-card">

            <div class="quiz-page-header">
                <h3 class="quiz-subtitle"><?php esc_html_e( 'Resultados Evaluación Final', 'villegas-courses' ); ?></h3>
                <h2 class="quiz-title"><?php echo esc_html( $course_title ); ?></h2>
                <p class="quiz-date"><?php echo esc_html( $result_date_display ); ?></p>
            </div>

            <div class="wpProQuiz_results-inner">
                <hr class="quiz-separator" />

                <p class="quiz-summary">
                    <span class="quiz-summary-correct"><?php echo esc_html( number_format_i18n( $correct_answers_display ) ); ?></span>
                    <?php esc_html_e( 'de', 'villegas-courses' ); ?>
                    <span class="quiz-summary-total"><?php echo esc_html( number_format_i18n( $total_questions_display ) ); ?></span>
                    <?php esc_html_e( 'Preguntas respondieron correctamente', 'villegas-courses' ); ?>
                </p>

                <p class="quiz-time">
                    <?php esc_html_e( 'Tu tiempo:', 'villegas-courses' ); ?>
                    <span class="quiz-time-value"><?php echo esc_html( $time_display ); ?></span>
                </p>

                <div class="quiz-charts-wrapper">

                    <div class="wpProQuiz_pointsChart villegas-donut villegas-donut--initial<?php echo null === $initial_percent ? ' wpProQuiz_pointsChart--empty' : ''; ?>" data-static-percent="<?php echo esc_attr( null === $initial_percent ? 0 : $initial_percent ); ?>">
                        <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" aria-label="<?php esc_attr_e( 'Evaluación Inicial', 'villegas-courses' ); ?>">
                            <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke-width="4"></circle>
                            <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke-width="4" stroke-linecap="round" transform="rotate(-90 18 18)"></circle>
                        </svg>
                        <div class="wpProQuiz_pointsChart__label"><?php if ( null === $initial_percent ) { esc_html_e( 'Sin datos', 'villegas-courses' ); } ?></div>
                        <div class="wpProQuiz_pointsChart__caption"><?php esc_html_e( 'Evaluación Inicial', 'villegas-courses' ); ?></div>
                    </div>

                    <div class="quiz-score-divider quiz-score-divider--vertical"></div>
                    <div class="quiz-score-divider quiz-score-divider--horizontal"></div>

                    <div id="wpProQuiz_pointsChartUser" class="wpProQuiz_pointsChart villegas-donut villegas-donut--final" data-chart-role="live-user-score" data-static-percent="<?php echo esc_attr( null === $final_percent ? 0 : $final_percent ); ?>">
                        <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" aria-label="<?php esc_attr_e( 'Evaluación Final', 'villegas-courses' ); ?>">
                            <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke-width="4"></circle>
                            <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke-width="4" stroke-linecap="round" transform="rotate(-90 18 18)"></circle>
                        </svg>
                        <div class="wpProQuiz_pointsChart__label"></div>
                        <div class="wpProQuiz_pointsChart__caption"><?php esc_html_e( 'Evaluación Final', 'villegas-courses' ); ?></div>
                    </div>
                </div>

                <hr class="quiz-separator quiz-separator--bottom" />

                <div class="quiz-variation-wrapper">
                    <div id="variacion-evaluacion" class="quiz-variation-content" data-course-url="<?php echo esc_url( $course_permalink ); ?>" data-certificate-url="<?php echo esc_url( $certificate_url ); ?>"></div>
                </div>

            </div>

        </div>
    </div>
<?php else : ?>
    <div id="wpProQuiz_pointsChartUser" class="wpProQuiz_pointsChart" aria-live="polite" data-chart-id="user-score" data-chart-role="live-user-score" data-chart-title="<?php esc_attr_e( 'Tu Puntaje', 'villegas-courses' ); ?>" style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em 1em 1em 0;">
        <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" style="width: 120px; height: 120px;">
            <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke="#E3E3E3" stroke-width="4"></circle>
            <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke="#f9c600" stroke-width="4" stroke-linecap="round" stroke-dasharray="0 100" stroke-dashoffset="25.12" transform="rotate(-90 18 18)"></circle>
        </svg>
        <div class="wpProQuiz_pointsChart__label" style="font-weight: 600;"></div>
        <div class="wpProQuiz_pointsChart__caption" style="font-size: 14px;">
            <?php esc_html_e( 'Tu Puntaje', 'villegas-courses' ); ?>
        </div>
    </div>
    <div id="quiz-score-divider" style="display: inline-block; width: 1px; height: 240px; background-color: #E3E3E3; margin: 0 28px; vertical-align: middle;"></div>
    <?php if ( ! empty( $average_chart_markup ) ) : ?>
        <?php echo $average_chart_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>
<?php endif; ?>
<?php

$course_id          = 0;
$course_label       = 'None';
$course_display     = 'None';
$product_id         = 0;
$product_display    = 'None';

if ( ! function_exists( 'villegas_is_user_enrolled_in_course' ) ) {
    /**
     * Determine if the provided user is enrolled in a specific course.
     *
     * @param int $user_id   WordPress user ID.
     * @param int $course_id LearnDash course/post ID.
     *
     * @return bool
     */
    function villegas_is_user_enrolled_in_course( $user_id, $course_id ) {
        if ( ! $user_id || ! $course_id ) {
            return false;
        }

        if ( function_exists( 'learndash_is_user_enrolled' ) ) {
            return (bool) learndash_is_user_enrolled( $user_id, $course_id );
        }

        if ( function_exists( 'sfwd_lms_has_access' ) ) {
            return (bool) sfwd_lms_has_access( $course_id, $user_id );
        }

        return false;
    }
}

if ( $quiz_course_id ) {
    $course_id      = $quiz_course_id;
    $course_display = $course_id;

    if ( $is_first_quiz ) {
        $course_label = 'First Quiz';
    } elseif ( $is_final_quiz ) {
        $course_label = 'Final Quiz';
    }

    if ( function_exists( 'villegas_get_course_product_id' ) ) {
        $related_product_id = (int) villegas_get_course_product_id( $course_id );

        if ( $related_product_id ) {
            $product_id      = $related_product_id;
            $product_display = $product_id;
        }
    }

    if ( ! $product_id ) {
        $related_products = get_posts(
            array(
                'post_type'      => 'product',
                'post_status'    => 'any',
                'fields'         => 'ids',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => '_related_course',
                        'value'   => sprintf( 'i:%d;', $course_id ),
                        'compare' => 'LIKE',
                    ),
                ),
            )
        );

        if ( ! empty( $related_products ) ) {
            $product_id      = (int) $related_products[0];
            $product_display = $product_id;
        }
    }
}
?>
<hr style="margin-bottom: 40px;">
<table class="wpProQuiz_pointsChart__meta debug_table" style="text-align: center; font-size: 14px; display: none;">
    <tbody>
        <tr>
            <th scope="row" style="padding-right: 8px; text-align: right;">
                <strong>Quiz ID:</strong>
            </th>
            <td style="text-align: left;">
                <?php echo esc_html( $quiz_id ); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" style="padding-right: 8px; text-align: right;">
                <strong>Course ID (<?php echo esc_html( $course_label ); ?>):</strong>
            </th>
            <td style="text-align: left;">
                <?php echo esc_html( $course_display ); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" style="padding-right: 8px; text-align: right;">
                <strong>Product ID:</strong>
            </th>
            <td style="text-align: left;">
                <?php echo esc_html( $product_display ); ?>
            </td>
        </tr>
    </tbody>
</table>
<?php
$button_label       = '';
$button_url         = '';
$course_url         = $course_id ? get_permalink( $course_id ) : '';
$product_url        = $product_id ? get_permalink( $product_id ) : '';
$is_enrolled_course = false;

if ( $course_id && is_user_logged_in() ) {
    $current_user = wp_get_current_user();

    if ( $current_user && $current_user->exists() ) {
        $is_enrolled_course = villegas_is_user_enrolled_in_course( (int) $current_user->ID, $course_id );
    }
}

if ( $is_enrolled_course && $course_url ) {
    $button_label = __( 'Ir al Curso', 'villegas-courses' );
    $button_url   = $course_url;
} elseif ( $product_url ) {
    $button_label = __( 'Comprar Curso', 'villegas-courses' );
    $button_url   = $product_url;
}

if ( ! $is_final_quiz && $button_label && $button_url ) :
    ?>
    <div style="text-align: center; margin-top: 12px;">
        <a class="wpProQuiz_pointsChart__cta" href="<?php echo esc_url( $button_url ); ?>" style="display: inline-block; padding: 10px 20px; background-color: black; color: #fff; border-radius: 4px; text-decoration: none; font-weight: 600;">
            <?php echo esc_html( $button_label ); ?>
        </a>
    </div>
<?php
endif;
?>
<script>
    (function() {
        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function parsePercentage(messageEl) {
            if (!messageEl) {
                return null;
            }

            var spans = messageEl.querySelectorAll('span');
            var percentText = '';

            if (spans.length >= 3) {
                percentText = spans[2].textContent || '';
            } else {
                percentText = messageEl.textContent || '';
            }

            var match = percentText.match(/-?\d+(?:[\.,]\d+)?/);

            if (!match) {
                return null;
            }

            var normalized = match[0].replace(',', '.');
            var value = parseFloat(normalized);

            if (isNaN(value)) {
                return null;
            }

            return clamp(value, 0, 100);
        }

        function drawChart(svg, percent, labelEl) {
            if (!svg) {
                return;
            }

            var progressCircle = svg.querySelector('.wpProQuiz_pointsChart__progress');

            if (!progressCircle) {
                return;
            }

            percent = clamp(percent, 0, 100);

            var radius = parseFloat(progressCircle.getAttribute('r'));
            var circumference = 2 * Math.PI * radius;
            var offset = circumference * (1 - percent / 100);

            progressCircle.setAttribute('stroke-dasharray', circumference + ' ' + circumference);
            progressCircle.setAttribute('stroke-dashoffset', offset);

            if (labelEl) {
                labelEl.textContent = percent.toFixed(0) + '%';
            }
        }

        function setupLiveCharts(messageEl) {
            if (!messageEl) {
                return;
            }

            var liveCharts = document.querySelectorAll('.wpProQuiz_pointsChart[data-chart-role="live-user-score"]');

            liveCharts.forEach(function(chartContainer) {
                if (!chartContainer || chartContainer.dataset.chartInitialized) {
                    return;
                }

                var svg = chartContainer.querySelector('.wpProQuiz_pointsChart__svg');
                var labelEl = chartContainer.querySelector('.wpProQuiz_pointsChart__label');

                if (!svg) {
                    return;
                }

                chartContainer.dataset.chartInitialized = 'true';

                var updateChart = function() {
                    var percent = parsePercentage(messageEl);

                    if (percent === null) {
                        return;
                    }

                    drawChart(svg, percent, labelEl);

                    var labelText = percent.toFixed(0) + '%';
                    var chartTitle = chartContainer.dataset.chartTitle || '';

                    if (chartTitle) {
                        svg.setAttribute('aria-label', chartTitle + ' ' + labelText);
                    } else {
                        svg.setAttribute('aria-label', labelText + ' quiz score');
                    }
                };

                updateChart();

                var observer = new MutationObserver(function() {
                    updateChart();
                });

                observer.observe(messageEl, {
                    childList: true,
                    characterData: true,
                    subtree: true
                });
            });
        }

        function initializeStaticCharts() {
            var staticCharts = document.querySelectorAll('.wpProQuiz_pointsChart[data-static-percent]');

            staticCharts.forEach(function(chartContainer) {
                if (chartContainer.dataset.chartInitialized) {
                    return;
                }

                var svg = chartContainer.querySelector('.wpProQuiz_pointsChart__svg');
                var labelEl = chartContainer.querySelector('.wpProQuiz_pointsChart__label');

                if (!svg) {
                    return;
                }

                var percentAttr = parseFloat(chartContainer.dataset.staticPercent);

                if (isNaN(percentAttr)) {
                    return;
                }

                chartContainer.dataset.chartInitialized = 'true';

                drawChart(svg, percentAttr, labelEl);

                var labelText = clamp(percentAttr, 0, 100).toFixed(0) + '%';
                var chartTitle = chartContainer.dataset.chartTitle || '';

                if (chartTitle) {
                    svg.setAttribute('aria-label', chartTitle + ' ' + labelText);
                } else {
                    svg.setAttribute('aria-label', labelText + ' quiz score');
                }
            });
        }

        var initializeCharts = function() {
            var messageEls = document.querySelectorAll('.wpProQuiz_points.wpProQuiz_points--message');

            messageEls.forEach(function(messageEl) {
                setupLiveCharts(messageEl);
            });

            initializeStaticCharts();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeCharts);
        } else {
            initializeCharts();
        }
    })();
</script>
<p class="wpProQuiz_graded_points" style="display: none;">
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_earned_points_message',
// translators: placeholder: points earned, points total, points percentage.
'message'      => sprintf( esc_html_x( 'Earned Point(s): %1$s of %2$s, (%3$s)', 'placeholder: points earned, points total, points percentage', 'learndash' ), '<span>0</span>', '<span>0</span>', '<span>0</span>' ),
'placeholders' => array( '0', '0', '0' ),
)
)
);
?>
<br />
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_essay_possible_points_message',
// translators: placeholder: number of essays, possible points.
'message'      => sprintf( esc_html_x( '%1$s Essay(s) Pending (Possible Point(s): %2$s)', 'placeholder: number of essays, possible points', 'learndash' ), '<span>0</span>', '<span>0</span>' ),
'placeholders' => array( '0', '0' ),
)
)
);
?>
<br />
</p>
<?php
}

if ( is_user_logged_in() ) {
?>
<p class="wpProQuiz_certificate" style="display: none ;"><?php echo LD_QuizPro::certificate_link( '', $quiz ); ?></p>
<?php echo LD_QuizPro::certificate_details( $quiz ); ?>
<?php
}

if ( $quiz->isShowAverageResult() ) {
?>
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
'message'      => esc_html__( 'Average score', 'learndash' ),
)
)
);
?>
</td>
<td class="wpProQuiz_resultValue wpProQuiz_resultValue_AvgScore">
<div class="progress-meter" style="background-color: #6CA54C;">&nbsp;</div>
<span class="progress-number">&nbsp;</span>
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
'message'      => esc_html__( 'Your score', 'learndash' ),
)
)
);
?>
</td>
<td class="wpProQuiz_resultValue wpProQuiz_resultValue_YourScore">
<div class="progress-meter">&nbsp;</div>
<span class="progress-number">&nbsp;</span>
</td>
</tr>
</tbody>
</table>
</div>
<?php
}
?>

<div class="wpProQuiz_catOverview" <?php $quiz_view->isDisplayNone( $quiz->isShowCategoryScore() ); ?>>
<h4>
<?php
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'learndash_categories_header',
'message'      => esc_html__( 'Categories', 'learndash' ),
)
)
);
?>
</h4>

<div style="margin-top: 10px;">
<ol>
<?php
foreach ( $quiz_view->category as $cat ) {
if ( ! $cat->getCategoryId() ) {
$cat->setCategoryName(
wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'learndash_not_categorized_messages',
'message'      => esc_html__( 'Not categorized', 'learndash' ),
)
)
)
);
}
?>
<li data-category_id="<?php echo esc_attr( $cat->getCategoryId() ); ?>">
<span class="wpProQuiz_catName"><?php echo esc_attr( $cat->getCategoryName() ); ?></span>
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
<?php foreach ( $result['text'] as $resultText ) { ?>
<li style="display: none;">
<div>
<?php if ( $quiz->is_result_message_enabled() ) : ?>
<?php echo do_shortcode( apply_filters( 'comment_text', $resultText, null, null ) ); ?>
<?php endif; ?>
</div>
</li>
<?php } ?>
</ul>
</div>
<?php
if ( $quiz->isToplistActivated() ) {
if ( $quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_NORMAL ) {
echo do_shortcode( '[LDAdvQuiz_toplist ' . $quiz->getId() . ' q="true"]' );
}

$quiz_view->showAddToplist();
}
?>
<div class="ld-quiz-actions" style="margin: 10px 0px; display: none;">
<?php
/**
 *  See snippet https://developers.learndash.com/hook/show_quiz_continue_buttom_on_fail/
 *
 * @since 2.3.0.2
 */
$show_quiz_continue_buttom_on_fail = apply_filters( 'show_quiz_continue_buttom_on_fail', false, learndash_get_quiz_id_by_pro_quiz_id( $quiz->getId() ) );
?>
<div class='quiz_continue_link
<?php
if ( $show_quiz_continue_buttom_on_fail == true ) {
echo ' show_quiz_continue_buttom_on_fail'; }
?>
'>

</div>
<?php if ( ! $quiz->isBtnRestartQuizHidden() ) { ?>
<input class="wpProQuiz_button wpProQuiz_button_restartQuiz" type="button" name="restartQuiz"
value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_restart_button_label',
'message'      => sprintf(
// translators: Restart Quiz Button Label.
esc_html_x( 'Restart %s', 'Restart Quiz Button Label', 'learndash' ),
LearnDash_Custom_Label::get_label( 'quiz' )
),
)
)
);
?>"/><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>
<?php
}
if ( ! $quiz->isBtnViewQuestionHidden() ) {
?>
<input class="wpProQuiz_button wpProQuiz_button_reShowQuestion" type="button" name="reShowQuestion"
value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_view_questions_button_label',
'message'      => sprintf(
// translators: View Questions Button Label.
esc_html_x( 'View %s', 'View Questions Button Label', 'learndash' ),
LearnDash_Custom_Label::get_label( 'questions' )
),
)
)
);
?>" /><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>
<?php } ?>
<?php if ( $quiz->isToplistActivated() && $quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_BUTTON ) { ?>
<input class="wpProQuiz_button" type="button" name="showToplist"
value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_show_leaderboard_button_label',
'message'      => esc_html__( 'Show leaderboard', 'learndash' ),
)
)
);
?>" /><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>
<?php } ?>
</div>
</div>
