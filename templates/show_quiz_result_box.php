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
<?php if ( ! $quiz->isBtnViewQuestionHidden() ) { ?>
<div class="ld-quiz-view-questions">
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
</div>
<?php } ?>
<?php if ( $quiz->isToplistActivated() && $quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_BUTTON ) { ?>
<div class="ld-quiz-show-toplist">
<input class="wpProQuiz_button" type="button" name="showToplist"
value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
echo wp_kses_post(
SFWD_LMS::get_template(
'learndash_quiz_messages',
array(
'quiz_post_id' => $quiz->getID(),
'context'      => 'quiz_show_leaderboard_button_label',
'message'      => esc_html__( 'Show leaderboard', 'learndash' ),
),
)
);
?>" /><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>
</div>
<?php } ?>
</div>
</div>
