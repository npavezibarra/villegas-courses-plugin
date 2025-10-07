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

$current_user_id       = get_current_user_id();
$quiz_post_id          = intval( learndash_get_quiz_id_by_pro_quiz_id( $quiz->getId() ) );
$quiz_post_id          = $quiz_post_id ? $quiz_post_id : get_the_ID();
$average_table_markup  = '';
$average_percentage    = null;
$course_id             = 0;
$course_permalink      = '';
$course_title          = '';
$first_quiz_id         = 0;
$final_quiz_id         = 0;
$is_first_quiz         = false;
$is_final_quiz         = false;
$related_product_id    = 0;
$product_permalink     = '';
$has_bought_course     = false;
$first_quiz_summary    = [];
$current_quiz_summary  = [];
$delta_percentage      = null;
$courses_archive_url   = get_post_type_archive_link( 'sfwd-courses' );
$courses_archive_url   = $courses_archive_url ? $courses_archive_url : home_url( '/cursos/' );

if ( $quiz_post_id && class_exists( 'Villegas_Quiz_Attempts_Shortcode' ) ) {
    $average_table_markup = do_shortcode( '[villegas_quiz_attempts id="' . intval( $quiz_post_id ) . '"]' );
    $average_percentage   = intval( Villegas_Quiz_Attempts_Shortcode::$last_average );
}

if ( $quiz_post_id && class_exists( 'PoliteiaCourse' ) ) {
    $course_id = PoliteiaCourse::getCourseFromQuiz( $quiz_post_id );

    if ( $course_id ) {
        $course_permalink   = get_permalink( $course_id );
        $course_title       = get_the_title( $course_id );
        $first_quiz_id      = PoliteiaCourse::getFirstQuizId( $course_id );
        $final_quiz_id      = PoliteiaCourse::getFinalQuizId( $course_id );
        $is_first_quiz      = $first_quiz_id && intval( $first_quiz_id ) === intval( $quiz_post_id );
        $is_final_quiz      = $final_quiz_id && intval( $final_quiz_id ) === intval( $quiz_post_id );

        // Prioritize final quiz mapping when both match.
        if ( $is_final_quiz ) {
            $is_first_quiz = false;
        }

        $related_product_id = PoliteiaCourse::getRelatedProductId( $course_id );

        if ( $related_product_id ) {
            $product_permalink = get_permalink( $related_product_id );

            if ( $current_user_id && function_exists( 'wc_get_orders' ) ) {
                $orders = wc_get_orders(
                    [
                        'customer_id' => $current_user_id,
                        'status'      => [ 'completed', 'processing', 'on-hold', 'course-on-hold' ],
                        'limit'       => -1,
                    ]
                );

                foreach ( $orders as $order ) {
                    foreach ( $order->get_items() as $item ) {
                        $item_product_id = $item->get_product_id();
                        if ( intval( $item_product_id ) === intval( $related_product_id ) ) {
                            $has_bought_course = true;
                            break 2;
                        }
                    }
                }
            }

            if ( ! $has_bought_course && $current_user_id && function_exists( 'wc_customer_bought_product' ) ) {
                $has_bought_course = wc_customer_bought_product( '', $current_user_id, $related_product_id );
            }
        } else {
            // Free or manually granted courses behave like already purchased.
            $has_bought_course = true;
        }
    }
}

if ( class_exists( 'Politeia_Quiz_Stats' ) && $quiz_post_id ) {
    $stats                = new Politeia_Quiz_Stats( $quiz_post_id, $current_user_id );
    $current_quiz_summary = $stats->get_current_quiz_summary();

    if ( $first_quiz_id ) {
        $first_quiz_summary = $stats->get_quiz_summary( $first_quiz_id );
    }

    if ( $is_final_quiz && ! empty( $current_quiz_summary ) && ! empty( $first_quiz_summary ) ) {
        $current_pct = isset( $current_quiz_summary['percentage_rounded'] ) ? $current_quiz_summary['percentage_rounded'] : null;
        $first_pct   = isset( $first_quiz_summary['percentage_rounded'] ) ? $first_quiz_summary['percentage_rounded'] : null;

        if ( is_numeric( $current_pct ) && is_numeric( $first_pct ) ) {
            $delta_percentage = intval( $current_pct ) - intval( $first_pct );
        }
    }
}

$primary_button_url   = '';
$primary_button_label = '';

if ( $is_final_quiz ) {
    $primary_button_url   = $courses_archive_url;
    $primary_button_label = __( 'Ver más cursos', 'villegas-courses' );
} elseif ( $course_permalink ) {
    if ( $product_permalink && ! $has_bought_course ) {
        $primary_button_url   = $product_permalink;
        $primary_button_label = __( 'Comprar curso', 'villegas-courses' );
    } else {
        $primary_button_url   = $course_permalink;
        $primary_button_label = __( 'Ir al curso', 'villegas-courses' );
    }
}

$cta_heading    = __( 'Resultados del quiz', 'villegas-courses' );
$cta_paragraph  = __( 'Continúa con tu aprendizaje y aprovecha tus resultados.', 'villegas-courses' );

if ( $is_final_quiz ) {
    $cta_heading = __( '¡Terminaste el curso!', 'villegas-courses' );
    $cta_paragraph = $course_title
        ? sprintf( __( 'Explora otros cursos después de completar %s.', 'villegas-courses' ), esc_html( $course_title ) )
        : __( 'Explora otros cursos disponibles para seguir aprendiendo.', 'villegas-courses' );

    if ( null !== $delta_percentage ) {
        $cta_paragraph .= ' ' . sprintf(
            __( 'Tu puntaje cambió %s respecto a la evaluación inicial.', 'villegas-courses' ),
            sprintf( '%+d%%', intval( $delta_percentage ) )
        );
    }
} elseif ( $is_first_quiz ) {
    $cta_heading   = __( 'Primer paso completado', 'villegas-courses' );
    $cta_paragraph = $course_title
        ? sprintf( __( 'Ya puedes continuar con el curso %s.', 'villegas-courses' ), esc_html( $course_title ) )
        : __( 'Ya puedes continuar con tu curso.', 'villegas-courses' );
}

$metrics = [];

$current_has_attempt = ! empty( $current_quiz_summary['has_attempt'] );
$current_pct_display = ( $current_has_attempt && isset( $current_quiz_summary['percentage_rounded'] ) && is_numeric( $current_quiz_summary['percentage_rounded'] ) )
    ? intval( $current_quiz_summary['percentage_rounded'] ) . '%'
    : '—';

$metrics[] = [
    'label' => __( 'Tu resultado', 'villegas-courses' ),
    'value' => $current_pct_display,
];

if ( $is_final_quiz && $first_quiz_id ) {
    $first_has_attempt = ! empty( $first_quiz_summary['has_attempt'] );
    $first_pct_display = ( $first_has_attempt && isset( $first_quiz_summary['percentage_rounded'] ) && is_numeric( $first_quiz_summary['percentage_rounded'] ) )
        ? intval( $first_quiz_summary['percentage_rounded'] ) . '%'
        : '—';

    $metrics[] = [
        'label' => __( 'Primera evaluación', 'villegas-courses' ),
        'value' => $first_pct_display,
    ];

    if ( null !== $delta_percentage ) {
        $metrics[] = [
            'label' => __( 'Cambio total', 'villegas-courses' ),
            'value' => sprintf( '%+d%%', intval( $delta_percentage ) ),
        ];
    }
}

if ( null !== $average_percentage ) {
    $metrics[] = [
        'label' => __( 'Promedio general', 'villegas-courses' ),
        'value' => intval( $average_percentage ) . '%',
    ];
}
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
<h4 class="wpProQuiz_header"><?php esc_html_e( 'Results', 'learndash' ); ?></h4>
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
?>
<p class="wpProQuiz_points wpProQuiz_points--message">
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
<div class="wpProQuiz_pointsChart" aria-live="polite" style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; margin: 1em 0;">
    <svg class="wpProQuiz_pointsChart__svg" viewBox="0 0 36 36" role="img" style="width: 120px; height: 120px;">
        <circle class="wpProQuiz_pointsChart__track" cx="18" cy="18" r="16" fill="none" stroke="#E3E3E3" stroke-width="4"></circle>
        <circle class="wpProQuiz_pointsChart__progress" cx="18" cy="18" r="16" fill="none" stroke="#4CAF50" stroke-width="4" stroke-linecap="round" stroke-dasharray="0 100" stroke-dashoffset="25.12" transform="rotate(-90 18 18)"></circle>
    </svg>
    <div class="wpProQuiz_pointsChart__label" style="font-weight: 600;"></div>
</div>
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

            var radius = parseFloat(progressCircle.getAttribute('r'));
            var circumference = 2 * Math.PI * radius;
            var offset = circumference * (1 - percent / 100);

            progressCircle.setAttribute('stroke-dasharray', circumference + ' ' + circumference);
            progressCircle.setAttribute('stroke-dashoffset', offset);

            if (labelEl) {
                labelEl.textContent = percent.toFixed(0) + '%';
            }
        }

        function setupChart(messageEl) {
            var chartContainer = messageEl.nextElementSibling;

            if (!chartContainer || !chartContainer.classList.contains('wpProQuiz_pointsChart')) {
                return;
            }

            var svg = chartContainer.querySelector('.wpProQuiz_pointsChart__svg');
            var labelEl = chartContainer.querySelector('.wpProQuiz_pointsChart__label');

            if (!svg) {
                return;
            }

            if (chartContainer.dataset.chartInitialized) {
                return;
            }

            chartContainer.dataset.chartInitialized = 'true';

            var updateChart = function() {
                var percent = parsePercentage(messageEl);

                if (percent === null) {
                    return;
                }

                drawChart(svg, percent, labelEl);
                svg.setAttribute('aria-label', percent.toFixed(0) + '% quiz score');
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
        }

        var initializeCharts = function() {
            var messageEls = document.querySelectorAll('.wpProQuiz_points.wpProQuiz_points--message');

            if (!messageEls.length) {
                return;
            }

            messageEls.forEach(function(messageEl) {
                setupChart(messageEl);
            });
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
<div class="ld-quiz-actions" style="margin: 10px 0px;">
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
<?php if ( $course_id ) : ?>
    <div class="villegas-quiz-result-cta" style="margin: 24px 0; padding: 24px; border: 1px solid #e6e8eb; border-radius: 10px; background: #f7f8fa; display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
        <div class="villegas-quiz-result-cta__body" style="flex: 1 1 280px; min-width: 260px;">
            <h4 style="margin: 0 0 8px; font-size: 1.25rem; font-weight: 600; color: #111827;">
                <?php echo esc_html( $cta_heading ); ?>
            </h4>
            <p style="margin: 0 0 16px; color: #4b5563;">
                <?php echo esc_html( $cta_paragraph ); ?>
            </p>
            <?php if ( ! empty( $metrics ) ) : ?>
                <ul class="villegas-quiz-result-cta__metrics" style="list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
                    <?php foreach ( $metrics as $metric ) : ?>
                        <li style="background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; text-align: center;">
                            <span class="villegas-quiz-result-cta__metric-label" style="display:block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 6px;">
                                <?php echo esc_html( $metric['label'] ); ?>
                            </span>
                            <span class="villegas-quiz-result-cta__metric-value" style="display:block; font-size: 1.5rem; font-weight: 700; color: #111827;">
                                <?php echo esc_html( $metric['value'] ); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ( $primary_button_url ) : ?>
            <div class="villegas-quiz-result-cta__actions" style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start;">
                <a class="wpProQuiz_button villegas-quiz-cta__button" style="font-size: 1rem; padding: 12px 24px;" href="<?php echo esc_url( $primary_button_url ); ?>">
                    <?php echo esc_html( $primary_button_label ); ?>
                </a>
                <?php if ( $is_final_quiz && $course_permalink ) : ?>
                    <a class="wpProQuiz_button wpProQuiz_button_secondary" style="font-size: 0.95rem; padding: 10px 20px; background: #ffffff; color: #111827; border: 1px solid #d1d5db;" href="<?php echo esc_url( $course_permalink ); ?>">
                        <?php esc_html_e( 'Revisar curso', 'villegas-courses' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
<?php if ( ! empty( $average_table_markup ) ) : ?>
<div class="villegas-quiz-attempts-table" style="display:none;">
    <?php echo $average_table_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php endif; ?>
