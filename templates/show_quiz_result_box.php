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

<hr class="border-gray-200 my-8" style="border: 0; border-top: 1px solid #e5e7eb; margin: 0.5rem 0;">
<div style="display: none;" class="wpProQuiz_results text-center">
    <div class="villegas-results-wrapper" style="max-width: 920px; margin: 0 auto;">
<?php if ( ! $quiz->isHideResultCorrectQuestion() ) : ?>
        <p class="wpProQuiz_results-summary text-lg text-gray-700 mb-2" style="font-size: 18px; color: #4a4a4a; margin-bottom: 0.5rem;">
            <span class="wpProQuiz_correct_answer font-bold text-primary-yellow" style="color: #f9c600; font-weight: 700;">0</span>
            <?php esc_html_e( 'de', 'villegas-courses' ); ?>
            <span class="font-bold" style="font-weight: 700;">
                <?php echo esc_html( $question_count ); ?>
            </span>
            <?php esc_html_e( 'preguntas respondidas correctamente', 'villegas-courses' ); ?>
        </p>
<?php endif; ?>

<?php if ( ! $quiz->isHideResultQuizTime() ) : ?>
        <p class="wpProQuiz_quiz_time text-sm text-gray-500 mb-8" style="font-size: 14px; color: #6b7280; margin-bottom: 2rem;">
            <?php esc_html_e( 'Tu tiempo:', 'villegas-courses' ); ?>
            <span class="font-semibold" style="font-weight: 600;">00:00:00</span>
        </p>
<?php endif; ?>
    </div>
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
),
)
);
?>
</p>
<?php if ( $is_final_quiz ) : ?>
        <div class="villegas-results-charts flex flex-col md:flex-row items-center justify-center space-y-8 md:space-y-0" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem;">
            <div class="wpProQuiz_pointsChart villegas-donut villegas-donut--initial<?php echo null === $first_quiz_percentage ? ' wpProQuiz_pointsChart--empty' : ''; ?>" aria-live="polite" data-chart-id="first-quiz-score" data-chart-title="<?php esc_attr_e( 'First Quiz', 'villegas-courses' ); ?>"<?php if ( null === $first_quiz_percentage ) : ?> aria-label="<?php echo esc_attr__( 'First Quiz sin datos', 'villegas-courses' ); ?>"<?php endif; ?><?php if ( null !== $first_quiz_percentage ) : ?> data-static-percent="<?php echo esc_attr( $first_quiz_percentage ); ?>"<?php endif; ?> style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em;<?php echo null === $first_quiz_percentage ? ' opacity: 0.6;' : ''; ?>">
                <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" style="width: 120px; height: 120px;">
                    <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke="#E3E3E3" stroke-width="4"></circle>
                    <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke="#f9c600" stroke-width="4" stroke-linecap="round" stroke-dasharray="0 100" stroke-dashoffset="25.12" transform="rotate(-90 18 18)"></circle>
                </svg>
                <div class="wpProQuiz_pointsChart__label" style="font-weight: 600;">
                    <span class="villegas-donut-percent-initial">
                    <?php
                    if ( null === $first_quiz_percentage ) {
                        esc_html_e( 'Sin datos', 'villegas-courses' );
                    } else {
                        echo esc_html( number_format_i18n( $first_quiz_percentage ) . '%' );
                    }
                    ?>
                    </span>
                </div>
                <div class="wpProQuiz_pointsChart__caption" style="font-size: 14px; color: #374151;">
                    <?php esc_html_e( 'Evaluación Inicial', 'villegas-courses' ); ?>
                </div>
            </div>
            <div class="villegas-results-divider villegas-results-divider--desktop" id="quiz-score-divider" style="display: inline-block; width: 1px; height: 240px; background-color: #E3E3E3; margin: 0 28px;"></div>
            <div class="villegas-results-divider villegas-results-divider--mobile" style="display: none; width: 66%; height: 1px; background-color: #E3E3E3; margin: 16px auto;"></div>
            <div id="wpProQuiz_pointsChartUser" class="wpProQuiz_pointsChart villegas-donut villegas-donut--final" aria-live="polite" data-chart-id="final-quiz-score" data-chart-role="live-user-score" data-chart-title="<?php esc_attr_e( 'Final Quiz', 'villegas-courses' ); ?>"<?php if ( null !== $final_quiz_percentage ) : ?> data-static-percent="<?php echo esc_attr( $final_quiz_percentage ); ?>"<?php endif; ?>
                style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em;">
                <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" style="width: 120px; height: 120px;">
                    <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke="#E3E3E3" stroke-width="4"></circle>
                    <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke="#f9c600" stroke-width="4" stroke-linecap="round" stroke-dasharray="0 100" stroke-dashoffset="25.12" transform="rotate(-90 18 18)"></circle>
                </svg>
                <div class="wpProQuiz_pointsChart__label" style="font-weight: 600;">
                    <span class="villegas-donut-percent-final">
                    <?php
                    if ( null !== $final_quiz_percentage ) {
                        echo esc_html( number_format_i18n( $final_quiz_percentage ) . '%' );
                    }
                    ?>
                    </span>
                </div>
                <div class="wpProQuiz_pointsChart__caption" style="font-size: 14px; color: #374151;">
                    <?php esc_html_e( 'Evaluación Final', 'villegas-courses' ); ?>
                </div>
            </div>
        </div>
        <hr class="border-gray-200 my-8" style="border: 0; border-top: 1px solid #e5e7eb; margin: 0.5rem 0;">
        <div class="text-center" style="margin-top: 12px;">
            <div id="variacion-evaluacion" class="w-full h-24" style="width: 100%; height: 96px;"></div>
        </div>
<?php else : ?>
        <div class="villegas-results-charts flex flex-col md:flex-row items-center justify-center space-y-8 md:space-y-0" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem;">
            <div id="wpProQuiz_pointsChartUser" class="wpProQuiz_pointsChart" aria-live="polite" data-chart-id="user-score" data-chart-role="live-user-score" data-chart-title="<?php esc_attr_e( 'Tu Puntaje', 'villegas-courses' ); ?>" style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em;">
                <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" style="width: 120px; height: 120px;">
                    <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke="#E3E3E3" stroke-width="4"></circle>
                    <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke="#f9c600" stroke-width="4" stroke-linecap="round" stroke-dasharray="0 100" stroke-dashoffset="25.12" transform="rotate(-90 18 18)"></circle>
                </svg>
                <div class="wpProQuiz_pointsChart__label" style="font-weight: 600;"></div>
                <div class="wpProQuiz_pointsChart__caption" style="font-size: 14px; color: #374151;">
                    <?php esc_html_e( 'Tu Puntaje', 'villegas-courses' ); ?>
                </div>
            </div>
            <div class="villegas-results-divider villegas-results-divider--desktop" id="quiz-score-divider" style="display: inline-block; width: 1px; height: 240px; background-color: #E3E3E3; margin: 0 28px;"></div>
            <div class="villegas-results-divider villegas-results-divider--mobile" style="display: none; width: 66%; height: 1px; background-color: #E3E3E3; margin: 16px auto;"></div>
            <?php if ( ! empty( $average_chart_markup ) ) : ?>
                <div class="villegas-average-chart" style="display: flex; justify-content: center; margin: 1em;">
                    <?php echo $average_chart_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
        </div>
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

if ( $button_label && $button_url && ! $is_final_quiz ) :
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

        function getChartPercent(chartContainer) {
            if (!chartContainer) {
                return null;
            }

            var staticPercent = chartContainer.dataset ? chartContainer.dataset.staticPercent : null;

            if (staticPercent !== undefined && staticPercent !== null && staticPercent !== '') {
                var parsedStatic = parseFloat(staticPercent);

                if (!isNaN(parsedStatic)) {
                    return clamp(parsedStatic, 0, 100);
                }
            }

            var labelWrapper = chartContainer.querySelector('.wpProQuiz_pointsChart__label');

            if (!labelWrapper) {
                return null;
            }

            var rawText = labelWrapper.textContent || '';
            var match = rawText.match(/-?\d+(?:[\.,]\d+)?/);

            if (!match) {
                return null;
            }

            var normalizedText = match[0].replace(',', '.');
            var parsedValue = parseFloat(normalizedText);

            if (isNaN(parsedValue)) {
                return null;
            }

            return clamp(parsedValue, 0, 100);
        }

        function updateVariationMessage() {
            var variationDiv = document.getElementById('variacion-evaluacion');

            if (!variationDiv) {
                return;
            }

            var initialChart = document.querySelector('.villegas-donut--initial');
            var finalChart = document.querySelector('.villegas-donut--final');

            if (!initialChart || !finalChart) {
                return;
            }

            var initialPercent = getChartPercent(initialChart);
            var finalPercent = getChartPercent(finalChart);

            if (initialPercent === null || finalPercent === null) {
                return;
            }

            var difference = finalPercent - initialPercent;
            var absDifference = Math.abs(difference);
            var baseClasses = 'w-full h-24 rounded-lg flex items-center justify-center p-2';

            variationDiv.className = baseClasses;
            variationDiv.classList.add('border', 'border-gray-500');

            var messageHtml;

            if (difference > 0) {
                messageHtml = '' +
                    '<div class="h-full w-full flex flex-col justify-center items-center p-4 rounded-lg" style="border-radius:8px; padding:16px; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">' +
                    '<p class="text-xl font-bold text-gray-900" style="font-size:20px; font-weight:700; color:#111827; margin:0;">¡Gran Progreso!</p>' +
                    '<p class="text-gray-700 mt-1" style="color:#4b5563; margin-top:4px; margin-bottom:0; text-align:center;">Has mejorado un <span class="text-2xl font-extrabold" style="font-size:24px; font-weight:800; color:#111827;">' + absDifference.toFixed(0) + '%</span> respecto a tu evaluación inicial.</p>' +
                    '</div>';
            } else {
                messageHtml = '' +
                    '<div class="h-full w-full flex flex-col justify-center items-center p-4 rounded-lg" style="border-radius:8px; padding:16px; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">' +
                    '<p class="text-xl font-bold text-gray-900" style="font-size:20px; font-weight:700; color:#111827; margin:0;">¡Felicidades por Terminar!</p>' +
                    '<p class="text-gray-700 text-sm md:text-base mt-1 text-center" style="color:#4b5563; margin-top:4px; margin-bottom:0; text-align:center;">Tu puntaje es similar o inferior (Diferencia: ' + difference.toFixed(0) + '%). Te recomendamos repasar los temas.</p>' +
                    '</div>';
            }

            variationDiv.innerHTML = messageHtml;
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

                    chartContainer.dataset.staticPercent = String(percent);
                    updateVariationMessage();

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

                chartContainer.dataset.staticPercent = String(percentAttr);
                updateVariationMessage();

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
            updateVariationMessage();
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
