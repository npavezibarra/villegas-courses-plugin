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

<div style="display: none;" class="wpProQuiz_results text-center">
    <hr class="border-gray-200 my-6">

    <?php if ( ! $quiz->isHideResultCorrectQuestion() ) : ?>
        <p class="text-lg text-gray-700 mb-2">
            <span class="wpProQuiz_correct_answer font-bold text-primary-yellow">0</span>
            <?php echo esc_html__( ' de ', 'villegas-courses' ); ?>
            <span class="font-bold"><?php echo esc_html( $question_count ); ?></span>
            <?php echo esc_html__( ' Preguntas respondieron correctamente', 'villegas-courses' ); ?>
        </p>
    <?php endif; ?>

    <?php if ( ! $quiz->isHideResultQuizTime() ) : ?>
        <p class="wpProQuiz_quiz_time text-sm text-gray-500 mb-8">
            <?php esc_html_e( 'Tu tiempo:', 'villegas-courses' ); ?>
            <span class="font-semibold"></span>
        </p>
    <?php endif; ?>

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
$raw_ld_points_message = '';
$average_chart_markup  = '';
$first_quiz_percentage = null;
$final_quiz_percentage = null;
$quiz_id               = $quiz_post_id ? $quiz_post_id : intval( $quiz->getID() );
$quiz_course_id        = 0;
$first_quiz_id         = 0;
$final_quiz_id         = 0;
$is_first_quiz         = false;
$is_final_quiz         = false;
$course_id             = 0;
$course_label          = 'None';
$course_display        = 'None';
$product_id            = 0;
$product_display       = 'None';
$button_label          = '';
$button_url            = '';
$course_url            = '';
$product_url           = '';
$is_enrolled_course    = false;

if ( ! $quiz->isHideResultPoints() ) {
    if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../classes/class-course-quiz-helper.php';
    }

    if ( ! class_exists( 'PoliteiaCourse' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
    }

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

    $user_id = get_current_user_id();

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
        $final_quiz_user = (int) $user_id;
        $final_course_id = $quiz_course_id ? (int) $quiz_course_id : 0;

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

    $raw_ld_points_message = wp_kses_post(
        SFWD_LMS::get_template(
            'learndash_quiz_messages',
            array(
                'quiz_post_id' => $quiz->getID(),
                'context'      => 'quiz_have_reached_points_message',
                'message'      => sprintf( esc_html_x( 'You have reached %1$s of %2$s point(s), (%3$s)', 'placeholder: points earned, points total', 'learndash' ), '<span>0</span>', '<span>0</span>', '<span>0</span>' ),
                'placeholders' => array( '0', '0', '0' ),
            )
        )
    );

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

    $course_url  = $course_id ? get_permalink( $course_id ) : '';
    $product_url = $product_id ? get_permalink( $product_id ) : '';

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
}
?>

    <?php if ( ! $quiz->isHideResultPoints() ) : ?>
        <?php
        $initial_chart_classes = 'wpProQuiz_pointsChart villegas-donut villegas-donut--initial';

        if ( null === $first_quiz_percentage ) {
            $initial_chart_classes .= ' wpProQuiz_pointsChart--empty';
        }

        $initial_label_text     = '';
        $initial_svg_aria_label = '';

        if ( null === $first_quiz_percentage ) {
            $initial_label_text     = esc_html__( 'Sin datos', 'villegas-courses' );
            $initial_svg_aria_label = esc_attr__( 'First Quiz sin datos', 'villegas-courses' );
        } else {
            $initial_label_text     = esc_html( round( $first_quiz_percentage ) . '%' );
            $initial_svg_aria_label = esc_attr(
                sprintf(
                    /* translators: %s: quiz percentage. */
                    esc_html__( 'First Quiz %s%%', 'villegas-courses' ),
                    round( $first_quiz_percentage )
                )
            );
        }
        ?>
        <div class="flex flex-col md:flex-row items-center justify-center space-y-8 md:space-y-0">
            <?php if ( $is_final_quiz ) : ?>
                <div
                    class="<?php echo esc_attr( $initial_chart_classes ); ?>"
                    data-chart-id="first-quiz-score"
                    data-chart-title="<?php esc_attr_e( 'First Quiz', 'villegas-courses' ); ?>"
                    <?php if ( null !== $first_quiz_percentage ) : ?>
                        data-static-percent="<?php echo esc_attr( $first_quiz_percentage ); ?>"
                    <?php endif; ?>
                    aria-live="polite"
                    style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em 1em 1em 0;<?php echo null === $first_quiz_percentage ? ' opacity: 0.6;' : ''; ?>"
                >
                    <svg class="wpProQuiz_pointsChart__svg"
                        viewBox="0 0 36 36"
                        role="img"
                        style="width: 120px; height: 120px;"
                        aria-label="<?php echo $initial_svg_aria_label; ?>">
                        <circle class="wpProQuiz_pointsChart__track"
                            cx="18" cy="18" r="16"
                            fill="none" stroke-width="4"></circle>
                        <circle class="wpProQuiz_pointsChart__progress"
                            cx="18" cy="18" r="16"
                            fill="none" stroke-width="4"
                            stroke-linecap="round"></circle>
                    </svg>
                    <div class="wpProQuiz_pointsChart__label text-xl font-bold text-gray-900">
                        <?php echo $initial_label_text; ?>
                    </div>
                    <div class="wpProQuiz_pointsChart__caption text-sm font-medium text-gray-700">
                        <?php esc_html_e( 'Evaluación Inicial', 'villegas-courses' ); ?>
                    </div>
                </div>

                <div
                    class="hidden md:block bg-light-gray-track mx-7"
                    style="width: 1px; height: 240px; vertical-align: middle;"></div>
                <div
                    class="block md:hidden bg-light-gray-track my-4 w-2/3"
                    style="height: 1px;"></div>

                <div
                    id="wpProQuiz_pointsChartUser"
                    class="wpProQuiz_pointsChart villegas-donut villegas-donut--final"
                    data-chart-id="final-quiz-score"
                    data-chart-role="live-user-score"
                    data-chart-title="<?php esc_attr_e( 'Final Quiz', 'villegas-courses' ); ?>"
                    aria-live="polite"
                    style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em 0 1em 1em;"
                >
                    <svg class="wpProQuiz_pointsChart__svg"
                        viewBox="0 0 36 36"
                        role="img"
                        style="width: 120px; height: 120px;"
                        aria-label="<?php echo esc_attr__( 'Final Quiz 0%', 'villegas-courses' ); ?>">
                        <circle class="wpProQuiz_pointsChart__track"
                            cx="18" cy="18" r="16"
                            fill="none" stroke-width="4"></circle>
                        <circle class="wpProQuiz_pointsChart__progress"
                            cx="18" cy="18" r="16"
                            fill="none" stroke-width="4"
                            stroke-linecap="round"></circle>
                    </svg>
                    <div class="wpProQuiz_pointsChart__label text-xl font-bold text-gray-900">
                        0%
                    </div>
                    <div class="wpProQuiz_pointsChart__caption text-sm font-medium text-gray-700">
                        <?php esc_html_e( 'Evaluación Final', 'villegas-courses' ); ?>
                    </div>
                </div>
            <?php else : ?>
                <div
                    id="wpProQuiz_pointsChartUser"
                    class="wpProQuiz_pointsChart villegas-donut"
                    data-chart-id="user-score"
                    data-chart-role="live-user-score"
                    data-chart-title="<?php esc_attr_e( 'Tu Puntaje', 'villegas-courses' ); ?>"
                    aria-live="polite"
                    style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em 1em 1em 0;"
                >
                    <svg class="wpProQuiz_pointsChart__svg"
                        viewBox="0 0 36 36"
                        role="img"
                        style="width: 120px; height: 120px;"
                        aria-label="<?php esc_attr_e( 'Tu Puntaje', 'villegas-courses' ); ?>">
                        <circle class="wpProQuiz_pointsChart__track"
                            cx="18" cy="18" r="16"
                            fill="none" stroke-width="4"></circle>
                        <circle class="wpProQuiz_pointsChart__progress"
                            cx="18" cy="18" r="16"
                            fill="none" stroke-width="4"
                            stroke-linecap="round"></circle>
                    </svg>
                    <div class="wpProQuiz_pointsChart__label text-xl font-bold text-gray-900"></div>
                    <div class="wpProQuiz_pointsChart__caption text-sm font-medium text-gray-700">
                        <?php esc_html_e( 'Tu Puntaje', 'villegas-courses' ); ?>
                    </div>
                </div>

                <?php if ( ! empty( $average_chart_markup ) ) : ?>
                    <div
                        class="hidden md:block bg-light-gray-track mx-7"
                        style="width: 1px; height: 240px; vertical-align: middle;"></div>
                    <div
                        class="block md:hidden bg-light-gray-track my-4 w-2/3"
                        style="height: 1px;"></div>

                    <?php echo $average_chart_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <hr class="border-gray-200 my-8" style="margin-bottom: 40px;">

        <div class="text-center" style="margin-top: 12px;">
            <div id="variacion-evaluacion"
                class="w-full flex flex-col items-center justify-center p-4 min-h-[100px]"
                style="min-height: 100px;">
                <?php if ( ! $is_final_quiz && $button_label && $button_url ) : ?>
                    <a class="wpProQuiz_pointsChart__cta inline-block bg-black text-white font-semibold py-3 px-8 rounded-lg shadow-lg transition duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
                        href="<?php echo esc_url( $button_url ); ?>">
                        <?php echo esc_html( $button_label ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php // DO NOT REMOVE: LearnDash uses this internally and our JS reads the percentage from here. ?>
        <?php if ( $raw_ld_points_message ) : ?>
            <p class="wpProQuiz_points wpProQuiz_points--message" style="display: none;">
                <?php echo $raw_ld_points_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </p>
        <?php endif; ?>

        <table class="wpProQuiz_pointsChart__meta debug_table"
            style="text-align: center; font-size: 14px; display: none;">
            <tbody>
            <tr>
                <th scope="row" style="padding-right: 8px; text-align: right;">
                    <strong>Quiz ID:</strong>
                </th>
                <td style="text-align: left;"><?php echo esc_html( $quiz_id ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="padding-right: 8px; text-align: right;">
                    <strong>Course ID (<?php echo esc_html( $course_label ); ?>):</strong>
                </th>
                <td style="text-align: left;"><?php echo esc_html( $course_display ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="padding-right: 8px; text-align: right;">
                    <strong>Product ID:</strong>
                </th>
                <td style="text-align: left;"><?php echo esc_html( $product_display ); ?></td>
            </tr>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php if ( ! $quiz->isHideResultPoints() ) : ?>
    <?php if ( $is_final_quiz ) : ?>
        <script>
            window.villegasQuizCourseUrl = <?php echo wp_json_encode( $button_url ? $button_url : '#' ); ?>;
        </script>
    <?php endif; ?>
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

            function updateVariationMessage(initialPercent, finalPercent) {
                var variationDiv = document.getElementById('variacion-evaluacion');

                if (!variationDiv) {
                    return;
                }

                if (
                    typeof initialPercent !== 'number' || isNaN(initialPercent) ||
                    typeof finalPercent !== 'number' || isNaN(finalPercent)
                ) {
                    variationDiv.innerHTML = '<p class="text-red-600">Error al cargar puntajes.</p>';
                    return;
                }

                var variation = finalPercent - initialPercent;
                var absVariation = Math.abs(variation).toFixed(0);

                var message = '';
                var buttonText = '';
                var buttonClass = '';
                var messageClass = 'text-gray-800';

                if (variation > 0) {
                    message =
                        '¡Felicidades! Obtuviste una variación positiva de ' +
                        '<span class="font-bold text-green-600">' + absVariation + '%</span>. ' +
                        'Has progresado en el curso.';
                    buttonText = 'VER CERTIFICADO';
                    buttonClass = 'bg-green-600 hover:bg-green-700';
                    messageClass = 'text-green-800';
                } else {
                    message =
                        '¡Bien hecho por completar el curso! La variación fue de ' +
                        '<span class="font-bold text-red-600">' + absVariation + '%</span>. ' +
                        'Te aconsejamos retomar el curso para consolidar tus conocimientos.';
                    buttonText = 'VER CURSO';
                    buttonClass = 'bg-black hover:bg-gray-800';
                    messageClass = 'text-gray-800';
                }

                var courseUrl = (typeof window.villegasQuizCourseUrl !== 'undefined')
                    ? window.villegasQuizCourseUrl
                    : '#';

                variationDiv.innerHTML = ''
                    + '<p class="' + messageClass + ' text-lg font-medium mb-4 text-center max-w-lg">'
                    + message
                    + '</p>'
                    + '<a href="' + courseUrl + '"'
                    +    ' class="' + buttonClass + ' text-white font-semibold py-3 px-8 rounded-lg shadow-lg'
                    +           ' transition duration-300 transform hover:scale-[1.02] active:scale-[0.98]">'
                    +    buttonText
                    + '</a>';
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

                        var initialChart = document.querySelector('.wpProQuiz_pointsChart.villegas-donut--initial');

                        if (initialChart) {
                            var initialAttr = parseFloat(initialChart.dataset.staticPercent);

                            if (!isNaN(initialAttr)) {
                                updateVariationMessage(initialAttr, percent);
                            }
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
<?php endif; ?>
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
