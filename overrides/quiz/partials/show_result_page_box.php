<?php
/**
 * Displays Quiz Result Box with dual ApexCharts ("Tu Puntaje" and "Promedio Villegas").
 *
 * Plugin: Villegas Courses
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure the average class is loaded.
if ( ! class_exists( 'Villegas_Quiz_Attempts_Shortcode' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../../../includes/class-villegas-average-quiz-result.php';
}

if ( ! class_exists( 'Villegas_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../../../includes/class-villegas-quiz-stats.php';
}

// Render shortcode to populate static/global vars.
ob_start();
do_shortcode( '[villegas_quiz_attempts id="' . get_the_ID() . '"]' );
ob_end_clean();

// Get global values.
$villegas_average = Villegas_Quiz_Attempts_Shortcode::$last_average;

global $wpdb;
$current_user_id = get_current_user_id();
$quiz_id         = get_the_ID();

// Check course association.
$course_id      = null;
$is_first_quiz  = false;
$is_final_quiz  = false;

if ( $quiz_id ) {
    $course_id_from_first = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_first_quiz_id' AND meta_value = %d",
            $quiz_id
        )
    );

    if ( $course_id_from_first ) {
        $course_id     = (int) $course_id_from_first;
        $is_first_quiz = true;
    }

    $course_id_from_final = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_final_quiz_id' AND meta_value = %d",
            $quiz_id
        )
    );

    if ( $course_id_from_final ) {
        $course_id    = (int) $course_id_from_final;
        $is_final_quiz = true;
    }
}

// Get First Quiz score if on Final Quiz.
$first_quiz_score = 0;
if ( $is_final_quiz && $course_id && class_exists( 'Villegas_Quiz_Stats' ) ) {
    $first_quiz_id = get_post_meta( $course_id, '_first_quiz_id', true );

    if ( $first_quiz_id ) {
        $latest_id = Villegas_Quiz_Stats::get_latest_attempt_id( $current_user_id, (int) $first_quiz_id );

        if ( $latest_id ) {
            $data = Villegas_Quiz_Stats::get_score_and_pct_by_activity( (int) $latest_id );

            if ( $data && isset( $data->percentage ) ) {
                $first_quiz_score = round( (float) $data->percentage );
            }
        }
    }
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
                <div class="course_progress_blue sending_progress_bar" style="width: 0%;"></div>
            </dd>
        </div>
    </p>
</div>

<div style="display: none;" class="wpProQuiz_results">
    <h4 class="wpProQuiz_header"><?php esc_html_e( 'Resultados', 'learndash' ); ?></h4>

    <div class="villegas-quiz-results" style="margin-top: 20px; text-align: center;">
        <div id="villegas-quiz-score" style="font-weight: bold; font-size: 16px; margin-bottom: 20px;"></div>
        <div class="villegas-quiz-charts" style="display:flex; justify-content:center; gap:40px; flex-wrap:wrap; margin-bottom:30px;">
            <div class="villegas-quiz-chart" style="max-width:300px; width:100%;">
                <div id="villegas-radial-chart"></div>
            </div>
            <div class="villegas-quiz-chart" style="max-width:300px; width:100%;">
                <div id="villegas-radial-chart-promedio"></div>
            </div>
        </div>
    </div>

    <div class="villegas-quiz-default-data" style="display:none;">
        <?php if ( ! $quiz->isHideResultCorrectQuestion() ) { ?>
            <?php
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
            ?>
        <?php } ?>

        <?php if ( ! $quiz->isHideResultQuizTime() ) { ?>
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
        <?php } ?>

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

        <?php if ( ! $quiz->isHideResultPoints() ) { ?>
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
        <?php } ?>

        <?php if ( is_user_logged_in() ) { ?>
            <p class="wpProQuiz_certificate" style="display: none ;"><?php echo LD_QuizPro::certificate_link( '', $quiz ); ?></p>
            <?php echo LD_QuizPro::certificate_details( $quiz ); ?>
        <?php } ?>

        <?php if ( $quiz->isShowAverageResult() ) { ?>
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
        <?php } ?>
    </div>
</div>

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
            <?php foreach ( $quiz_view->category as $cat ) { ?>
                <?php
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
            <?php } ?>
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
    <div class='quiz_continue_link<?php if ( $show_quiz_continue_buttom_on_fail == true ) { echo ' show_quiz_continue_buttom_on_fail'; } ?>'>

    </div>
    <?php if ( ! $quiz->isBtnRestartQuizHidden() ) { ?>
        <input class="wpProQuiz_button wpProQuiz_button_restartQuiz" type="button" name="restartQuiz"
               value="<?php
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
               ?>"/>
    <?php }
    if ( ! $quiz->isBtnViewQuestionHidden() ) {
        ?>
        <input class="wpProQuiz_button wpProQuiz_button_reShowQuestion" type="button" name="reShowQuestion"
               value="<?php
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
               ?>" />
    <?php }
    if ( $quiz->isToplistActivated() && $quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_BUTTON ) {
        ?>
        <input class="wpProQuiz_button" type="button" name="showToplist"
               value="<?php
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
               ?>" />
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function() {
    const initCharts = function() {
        const target = document.querySelector('.villegas-quiz-default-data .wpProQuiz_points span:nth-child(3)');
        if (!target) {
            return;
        }

        const chartContainer = document.querySelector('#villegas-radial-chart');
        const chartPromedio  = document.querySelector('#villegas-radial-chart-promedio');
        if (!chartContainer || !chartPromedio) {
            return;
        }

        const isFinalQuiz    = <?php echo wp_json_encode( $is_final_quiz ); ?>;
        const firstQuizScore = <?php echo (int) $first_quiz_score; ?>;
        const averagePHP     = <?php echo (int) $villegas_average; ?>;

        const renderCharts = (pctValue) => {
            const pct = parseFloat(String(pctValue).replace('%', '').trim());
            if (Number.isNaN(pct)) {
                return;
            }

            chartContainer.innerHTML = '';
            chartPromedio.innerHTML = '';

            const chartOptions = (val, label) => ({
                series: [val],
                chart: { height: 350, type: 'radialBar' },
                plotOptions: {
                    radialBar: {
                        hollow: { size: '60%' },
                        dataLabels: {
                            name: { show: true, color: '#555', fontSize: '16px' },
                            value: { show: true, fontSize: '30px', fontWeight: 600, color: '#111', formatter: v => v + '%' }
                        }
                    }
                },
                labels: [label],
                colors: ['#d29d01'],
                fill: { type: 'gradient', gradient: { shade: 'light', gradientToColors: ['#ffd000'], stops: [0, 100] } }
            });

            new ApexCharts(chartContainer, chartOptions(pct, 'Tu Puntaje')).render();

            if (isFinalQuiz) {
                new ApexCharts(chartPromedio, chartOptions(firstQuizScore, 'First Quiz')).render();
            } else {
                new ApexCharts(chartPromedio, chartOptions(averagePHP, 'Promedio Villegas')).render();
            }

            const scoreDiv = document.getElementById('villegas-quiz-score');
            if (isFinalQuiz && scoreDiv) {
                const delta = Math.round(pct - firstQuizScore);
                let msg = '';
                if (delta > 0) {
                    msg = '<h3 style="color:#4CAF50;">¡Mejoraste ' + delta + ' puntos!</h3>';
                } else if (delta === 0) {
                    msg = '<h3>Tu resultado se mantiene estable.</h3>';
                } else {
                    msg = '<h3 style="color:#D32F2F;">Bajaste ' + Math.abs(delta) + ' puntos. Puedes intentarlo nuevamente.</h3>';
                }
                scoreDiv.innerHTML = msg;
            } else if (scoreDiv) {
                scoreDiv.innerHTML = '';
            }
        };

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    renderCharts(target.textContent || '');
                    observer.disconnect();
                    break;
                }
            }
        });

        observer.observe(target, { childList: true, characterData: true, subtree: true });

        const immediateValue = target.textContent || target.innerText;
        if (immediateValue && immediateValue.trim() !== '') {
            renderCharts(immediateValue);
            observer.disconnect();
        }
    };

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initCharts();
    } else {
        document.addEventListener('DOMContentLoaded', initCharts);
    }
})();
</script>
