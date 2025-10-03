<?php
/**
 * Custom quiz result box focused on "buy-button-stats" experience.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Politeia_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-quiz-stats.php';
}

if ( ! class_exists( 'PoliteiaCourse' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-politeia-course.php';
}

global $post;
$quiz_id = isset( $post->ID ) ? intval( $post->ID ) : 0;
$user_id = get_current_user_id();
$stats    = new Politeia_Quiz_Stats( $quiz_id, $user_id );

$course_id       = $stats->get_course_id();
$course_title    = $course_id ? get_the_title( $course_id ) : '';
$is_first_quiz   = $stats->is_first_quiz();
$is_final_quiz   = $stats->is_final_quiz();
$latest_summary  = $stats->get_current_quiz_summary();
$first_summary   = $stats->get_first_quiz_summary();
$final_summary   = $stats->get_final_quiz_summary();
$first_quiz_id   = $stats->get_first_quiz_id();
$final_quiz_id   = $stats->get_final_quiz_id();

$has_first_quiz = (bool) $first_quiz_id;
$has_final_quiz = (bool) $final_quiz_id;

$first_percentage_value   = is_numeric( $first_summary['percentage_rounded'] ) ? intval( $first_summary['percentage_rounded'] ) : null;
$final_percentage_value   = is_numeric( $final_summary['percentage_rounded'] ) ? intval( $final_summary['percentage_rounded'] ) : null;

$last_attempt_timestamp = intval( $latest_summary['timestamp'] );
$has_latest_attempt     = ! empty( $latest_summary['has_attempt'] );

$has_course    = (bool) $course_id;
$has_access    = $has_course ? PoliteiaCourse::userHasAccess( $course_id, $user_id ) : false;
$product_id    = $has_course ? PoliteiaCourse::getRelatedProductId( $course_id ) : 0;
$product_url   = $product_id ? get_permalink( $product_id ) : '#';
$course_url    = $has_course ? get_permalink( $course_id ) : '#';

$first_alert_messages = [];
if ( $is_first_quiz && ! $has_latest_attempt ) {
    $first_alert_messages[] = __( 'Estamos guardando tu resultado. Refresca la página si no aparece en unos minutos.', 'villegas-courses' );
}

$final_alert_messages = [];
if ( $is_final_quiz ) {
    if ( ! $has_first_quiz ) {
        $final_alert_messages[] = __( 'El curso no tiene configurada una Prueba Inicial.', 'villegas-courses' );
    }

    if ( ! $has_final_quiz ) {
        $final_alert_messages[] = __( 'El curso no tiene configurada una Prueba Final.', 'villegas-courses' );
    }

    if ( $has_first_quiz && ! $first_summary['has_attempt'] ) {
        $final_alert_messages[] = __( 'Aún no registramos resultados de tu Prueba Inicial.', 'villegas-courses' );
    }

    if ( $has_final_quiz && ! $final_summary['has_attempt'] ) {
        $final_alert_messages[] = __( 'Aún no registramos resultados de tu Prueba Final.', 'villegas-courses' );
    }
}

$cta_text = $has_access
    ? __( 'Go to Course', 'villegas-courses' )
    : __( 'Buy Course', 'villegas-courses' );
$cta_url  = $has_access ? $course_url : $product_url;

if ( $is_final_quiz ) {
    $cta_text = __( 'View course summary', 'villegas-courses' );
    $cta_url  = $course_url ? $course_url : home_url();
}
?>
<style>
.politeia-quiz-results {
    background: #ffffff;
    border: 1px solid #d5d5d5;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}
.politeia-quiz-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}
.politeia-quiz-header h3 {
    margin: 0;
    font-size: 20px;
}
.politeia-quiz-meta {
    color: #728188;
    font-size: 14px;
    font-weight: 600;
}
.politeia-quiz-chart {
    margin: 30px auto;
    max-width: 320px;
}
.politeia-score-highlight {
    font-size: 32px;
    font-weight: 700;
    text-align: center;
}
.politeia-score-detail {
    text-align: center;
    color: #5f6b75;
    font-weight: 600;
    margin-top: 8px;
}
.politeia-cta-box {
    margin-top: 24px;
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
}
.politeia-cta-box p {
    margin-top: 0;
    margin-bottom: 16px;
}
.politeia-cta-box .politeia-button {
    display: inline-block;
    background: #000000;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
}
.politeia-comparison {
    margin-top: 30px;
    border-top: 1px solid #e6e6e6;
    padding-top: 24px;
}
.politeia-comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}
.politeia-comparison-card {
    background: #f6f7f8;
    padding: 18px;
    border-radius: 10px;
    text-align: center;
}
.politeia-comparison-card span {
    display: block;
    font-size: 14px;
    color: #5f6b75;
    margin-bottom: 6px;
}
.politeia-comparison-card strong {
    font-size: 26px;
}
.politeia-alert {
    margin-top: 24px;
    background: #fff4e5;
    border: 1px solid #ff9800;
    border-radius: 10px;
    padding: 16px 20px;
    color: #7a4b00;
}
.politeia-alert p {
    margin: 0 0 6px;
    font-weight: 600;
}
.politeia-alert ul {
    margin: 0;
    padding-left: 20px;
}
.politeia-comparison-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
    margin-top: 18px;
}
.politeia-chip {
    background: #eef0f2;
    padding: 10px 16px;
    border-radius: 30px;
    font-weight: 600;
    color: #333;
}
.politeia-quiz-attempt {
    margin-top: 24px;
    background: #ffffff;
    border: 1px solid #d5d5d5;
    border-radius: 10px;
    padding: 20px;
    display: none;
}
.politeia-quiz-attempt h4 {
    margin-top: 0;
}
.politeia-quiz-attempt .politeia-comparison-grid {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}
.learndash-wrapper .wpProQuiz_quiz_time,
.learndash-wrapper .wpProQuiz_graded_points,
.learndash-wrapper .wpProQuiz_certificate,
.learndash-wrapper .wpProQuiz_resultTable,
.learndash-wrapper .wpProQuiz_points,
.learndash-wrapper .wpProQuiz_finishQuiz,
.learndash-wrapper .wpProQuiz_solved {
    display: none !important;
}
</style>

<div style="display:none;" class="wpProQuiz_sending">
    <p>
        <div>
            <?php echo esc_html__( 'Quiz complete. Results are being recorded.', 'learndash' ); ?>
        </div>
        <div>
            <dd class="course_progress">
                <div class="course_progress_blue sending_progress_bar" style="width:0%"></div>
            </dd>
        </div>
    </p>
</div>

<div style="display:none;" class="wpProQuiz_results">
    <div id="politeia-loading-notice" class="politeia-alert" style="display:block;">
        <p><?php esc_html_e( 'Estamos registrando tu último resultado. Actualizaremos esta vista en unos segundos.', 'villegas-courses' ); ?></p>
    </div>

    <div class="politeia-quiz-results" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>" style="display:none;">
        <div class="politeia-quiz-header">
            <div>
                <h3><?php echo esc_html( get_the_title( $quiz_id ) ); ?></h3>
                <div class="politeia-quiz-meta">
                    <?php echo esc_html( $course_title ); ?> · <span id="politeia-quiz-date">--</span>
                </div>
            </div>
            <?php if ( $is_final_quiz && $first_summary['formatted_date'] ) : ?>
                <div class="politeia-quiz-meta" style="text-align:right;">
                    <?php esc_html_e( 'Prueba Inicial:', 'villegas-courses' ); ?> <?php echo esc_html( $first_summary['formatted_date'] ); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="politeia-quiz-chart">
            <div id="politeia-quiz-chart"></div>
        </div>

        <div class="politeia-score-highlight">
            <span id="quiz-percentage">--%</span>
        </div>

        <div
            class="politeia-score-detail"
            id="politeia-score-detail"
            data-score-template="<?php echo esc_attr__( 'Puntaje obtenido: %d pts.', 'villegas-courses' ); ?>"
            data-score-fallback="<?php echo esc_attr__( 'Puntaje disponible pronto.', 'villegas-courses' ); ?>"
        >
            <?php esc_html_e( 'Puntaje disponible pronto.', 'villegas-courses' ); ?>
        </div>

        <?php if ( $is_first_quiz ) : ?>
            <?php if ( ! empty( $first_alert_messages ) ) : ?>
                <div class="politeia-alert" id="politeia-first-alert">
                    <?php foreach ( $first_alert_messages as $message ) : ?>
                        <p><?php echo esc_html( $message ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div
                class="politeia-cta-box"
                id="politeia-motivation"
                data-motivation-high="<?php echo esc_attr__( '¡Excelente comienzo con %s%%! Imagina cuánto podrás reforzar tu conocimiento al acceder a todas las lecciones.', 'villegas-courses' ); ?>"
                data-motivation-mid="<?php echo esc_attr__( 'Tu puntaje de %s%% demuestra que ya conoces parte del contenido. Con el curso completo podrás dominarlo.', 'villegas-courses' ); ?>"
                data-motivation-low="<?php echo esc_attr__( 'Este es solo el inicio: con el curso completo podrás mejorar ampliamente ese %s%% obtenido en la Prueba Inicial.', 'villegas-courses' ); ?>"
            >
                <p id="politeia-motivation-text" style="display:none;"></p>
                <?php if ( $has_course ) : ?>
                    <a class="politeia-button" href="<?php echo esc_url( $cta_url ); ?>">
                        <?php echo esc_html( $cta_text ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif ( $is_final_quiz ) : ?>
            <div class="politeia-comparison" id="politeia-comparison-block" style="display:none;">
                <h4><?php esc_html_e( 'Comparativa con tu Prueba Inicial', 'villegas-courses' ); ?></h4>
                <div class="politeia-comparison-grid">
                    <div class="politeia-comparison-card">
                        <span><?php esc_html_e( 'Prueba Inicial', 'villegas-courses' ); ?></span>
                        <strong id="politeia-first-score">--%</strong>
                    </div>
                    <div class="politeia-comparison-card">
                        <span><?php esc_html_e( 'Prueba Final', 'villegas-courses' ); ?></span>
                        <strong id="politeia-final-score">--%</strong>
                    </div>
                </div>
                <div class="politeia-comparison-meta">
                    <div
                        class="politeia-chip"
                        id="politeia-progress-delta"
                        data-label="<?php echo esc_attr__( 'Progreso:', 'villegas-courses' ); ?>"
                        style="display:none;"
                    ></div>
                    <div
                        class="politeia-chip"
                        id="politeia-days-elapsed"
                        data-label="<?php echo esc_attr__( 'Días transcurridos:', 'villegas-courses' ); ?>"
                        style="display:none;"
                    ></div>
                </div>
            </div>

            <?php if ( ! empty( $final_alert_messages ) ) : ?>
                <div class="politeia-alert" id="politeia-final-alert">
                    <p><?php esc_html_e( 'Para comparar ambos resultados necesitamos lo siguiente:', 'villegas-courses' ); ?></p>
                    <ul>
                        <?php foreach ( $final_alert_messages as $message ) : ?>
                            <li><?php echo esc_html( $message ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( $has_course ) : ?>
                <div style="text-align:center; margin-top:20px;">
                    <a class="politeia-button" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                </div>
            <?php endif; ?>
        <?php elseif ( ! empty( $first_alert_messages ) ) : ?>
            <div class="politeia-alert" id="politeia-generic-alert">
                <?php foreach ( $first_alert_messages as $message ) : ?>
                    <p><?php echo esc_html( $message ); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="politeia-quiz-attempt" class="politeia-quiz-attempt">
            <h4><?php esc_html_e( 'Datos del Intento', 'villegas-courses' ); ?></h4>
            <div class="politeia-comparison-grid">
                <div class="politeia-comparison-card">
                    <span><?php esc_html_e( 'Puntaje obtenido', 'villegas-courses' ); ?></span>
                    <strong id="politeia-attempt-percentage">--%</strong>
                </div>
                <div class="politeia-comparison-card">
                    <span><?php esc_html_e( 'Fecha de intento', 'villegas-courses' ); ?></span>
                    <strong id="politeia-attempt-date">--</strong>
                </div>
            </div>
        </div>
    </div>

    <?php
    $user_private_score = get_user_meta( $user_id, 'puntaje_privado', true );
    $is_checked         = ( '1' === $user_private_score || 1 === (int) $user_private_score ) ? 'checked' : '';
    ?>
    <div class="quiz-private-toggle" style="background:#f9f9f9;padding:15px;border-radius:8px;text-align:center;margin-top:16px;">
        <label style="font-size:15px;font-weight:500;">
            <input type="checkbox" id="puntaje_privado_checkbox" data-user-id="<?php echo esc_attr( $user_id ); ?>" <?php echo esc_attr( $is_checked ); ?>>
            No mostrar mi puntaje en rankings públicos
        </label>
    </div>
</div>

<script>
(function($){
    const ajaxConfig = window.villegasAjax || {};
    const quizConfig = {
        quizId: <?php echo (int) $quiz_id; ?>,
        userId: <?php echo (int) $user_id; ?>,
        isFirstQuiz: <?php echo $is_first_quiz ? 'true' : 'false'; ?>,
        isFinalQuiz: <?php echo $is_final_quiz ? 'true' : 'false'; ?>,
        firstScore: <?php echo ! is_null( $first_percentage_value ) ? (int) $first_percentage_value : 'null'; ?>,
        finalScore: <?php echo ! is_null( $final_percentage_value ) ? (int) $final_percentage_value : 'null'; ?>,
        currentAttemptTimestamp: <?php echo $last_attempt_timestamp; ?>,
        awaitingAttempt: <?php echo $has_latest_attempt ? 'false' : 'true'; ?>,
        hideResultsWhilePending: true,
        hasRenderedAttempt: false,
        chartLabels: <?php echo wp_json_encode(
            [
                'single' => __( 'Resultado', 'villegas-courses' ),
                'final'  => __( 'Prueba Final', 'villegas-courses' ),
                'first'  => __( 'Prueba Inicial', 'villegas-courses' ),
            ]
        ); ?>,
        nonce: (typeof quizData !== 'undefined' && quizData.activityNonce)
            ? quizData.activityNonce
            : (ajaxConfig.activityNonce || '')
    };

    const ajaxUrl = ajaxConfig.ajaxUrl || '';
    const defaultRetry = parseInt(ajaxConfig.retryAfter, 10) > 0 ? parseInt(ajaxConfig.retryAfter, 10) : 5;

    let chartInstance = null;
    const attemptBox = $('#politeia-quiz-attempt');
    const loadingNotice = $('#politeia-loading-notice');
    const resultsWrapper = $('.politeia-quiz-results');

    const storage = (function(){
        try {
            return window.sessionStorage;
        } catch (error) {
            return null;
        }
    })();

    const awaitingStorageKey = 'politeiaAwaitingAttempt_' + quizConfig.quizId + '_' + quizConfig.userId;

    if (storage) {
        const storedAwaiting = storage.getItem(awaitingStorageKey);
        if (storedAwaiting) {
            const storedTime = parseInt(storedAwaiting, 10);
            if (!isNaN(storedTime) && (Date.now() - storedTime) < (5 * 60 * 1000)) {
                quizConfig.awaitingAttempt = true;
                quizConfig.hideResultsWhilePending = true;
            } else {
                storage.removeItem(awaitingStorageKey);
            }
        }
    }

    function setAwaitingState(isAwaiting, options) {
        const shouldUpdateFlag = !(options && options.skipFlagUpdate);
        if (shouldUpdateFlag) {
            quizConfig.awaitingAttempt = !!isAwaiting;
        }

        if (isAwaiting) {
            if (loadingNotice && loadingNotice.length) {
                loadingNotice.show();
            }

            if (attemptBox && attemptBox.length) {
                attemptBox.stop(true, true).slideUp();
            }
            if (resultsWrapper && resultsWrapper.length && quizConfig.hideResultsWhilePending) {
                resultsWrapper.hide();
            }
        } else {
            if (attemptBox && attemptBox.length) {
                attemptBox.stop(true, true).slideDown();
            }

            if (loadingNotice && loadingNotice.length) {
                loadingNotice.hide();
            }

            if (resultsWrapper && resultsWrapper.length) {
                resultsWrapper.show();
            }
        }
    }

    function renderChart(series, labels) {
        const chartEl = document.querySelector('#politeia-quiz-chart');
        if (!chartEl || typeof ApexCharts === 'undefined') {
            return;
        }

        if (chartInstance) {
            chartInstance.updateOptions({ series: series, labels: labels });
            return;
        }

        const options = {
            chart: {
                type: 'radialBar',
                height: 320,
                fontFamily: 'inherit'
            },
            colors: ['#ff9800', '#9fd99f'],
            series: series,
            labels: labels,
            plotOptions: {
                radialBar: {
                    hollow: { size: '60%' },
                    dataLabels: {
                        name: { fontSize: '14px' },
                        value: { fontSize: '24px', formatter: function(val){ return Math.round(val) + '%'; } }
                    }
                }
            }
        };

        chartInstance = new ApexCharts(chartEl, options);
        chartInstance.render();
    }

    function updateAttemptUI(data) {
        if (!data || typeof data !== 'object') {
            return;
        }

        const rawPercentage = (typeof data.percentage === 'number')
            ? data.percentage
            : (typeof data.percentage_rounded === 'number' ? data.percentage_rounded : null);
        const attemptPercentage = rawPercentage !== null ? Math.round(rawPercentage) : null;
        const attemptScore = (typeof data.score === 'number') ? Math.round(data.score) : null;
        const attemptTimestamp = parseInt(data.timestamp, 10);

        if (!isNaN(attemptTimestamp) && attemptTimestamp > 0) {
            quizConfig.currentAttemptTimestamp = attemptTimestamp;
            if (storage) {
                storage.removeItem(awaitingStorageKey);
            }
        }

        const attemptPercentageLabel = (attemptPercentage !== null && !isNaN(attemptPercentage))
            ? attemptPercentage + '%'
            : '--%';
        $('#politeia-attempt-percentage').text(attemptPercentageLabel);
        $('#quiz-percentage').text(attemptPercentageLabel);

        const formattedDate = data.formatted_date || '';
        $('#politeia-attempt-date').text(formattedDate || '--');
        $('#politeia-quiz-date').text(formattedDate || '--');

        const scoreDetail = document.getElementById('politeia-score-detail');
        if (scoreDetail) {
            if (attemptScore !== null && !isNaN(attemptScore)) {
                const template = scoreDetail.dataset.scoreTemplate || '';
                scoreDetail.textContent = template ? template.replace('%d', attemptScore) : attemptScore + ' pts';
            } else if (scoreDetail.dataset.scoreFallback) {
                scoreDetail.textContent = scoreDetail.dataset.scoreFallback;
            }
        }

        if (quizConfig.isFirstQuiz) {
            $('#politeia-first-alert').remove();

            const motivationBox = $('#politeia-motivation');
            if (motivationBox.length) {
                const textEl = $('#politeia-motivation-text');
                let message = '';

                if (attemptPercentage !== null && !isNaN(attemptPercentage)) {
                    if (attemptPercentage >= 80) {
                        message = motivationBox.data('motivationHigh') || '';
                    } else if (attemptPercentage >= 50) {
                        message = motivationBox.data('motivationMid') || '';
                    } else {
                        message = motivationBox.data('motivationLow') || '';
                    }

                    if (message) {
                        message = message.replace('%s', attemptPercentage);
                    }
                }

                if (textEl.length) {
                    if (message) {
                        textEl.text(message).show();
                    } else {
                        textEl.text('').hide();
                    }
                }
            }
        } else if (quizConfig.isFinalQuiz) {
            const comparisonBlock = $('#politeia-comparison-block');
            const finalValue = (typeof data.final_percentage === 'number')
                ? Math.round(data.final_percentage)
                : (attemptPercentage !== null ? attemptPercentage : null);
            const firstValue = (typeof data.first_percentage === 'number')
                ? Math.round(data.first_percentage)
                : (typeof quizConfig.firstScore === 'number' ? quizConfig.firstScore : null);

            if (comparisonBlock.length && finalValue !== null && !isNaN(finalValue)) {
                comparisonBlock.show();
            }

            if (finalValue !== null && !isNaN(finalValue)) {
                $('#politeia-final-score').text(finalValue + '%');
                quizConfig.finalScore = finalValue;
            }

            if (firstValue !== null && !isNaN(firstValue)) {
                $('#politeia-first-score').text(firstValue + '%');
                quizConfig.firstScore = firstValue;
            }

            $('#politeia-final-alert').remove();

            const progressChip = $('#politeia-progress-delta');
            if (progressChip.length) {
                let progressDelta = null;
                if (typeof data.progress_delta === 'number') {
                    progressDelta = Math.round(data.progress_delta);
                } else if (
                    firstValue !== null && !isNaN(firstValue) &&
                    finalValue !== null && !isNaN(finalValue)
                ) {
                    progressDelta = Math.round(finalValue - firstValue);
                }

                if (progressDelta !== null && !isNaN(progressDelta)) {
                    const progressLabel = progressChip.data('label') || '';
                    const progressSign = progressDelta > 0 ? '+' : '';
                    progressChip
                        .text((progressLabel ? progressLabel + ' ' : '') + progressSign + progressDelta + '%')
                        .show();
                } else {
                    progressChip.hide();
                }
            }

            const daysChip = $('#politeia-days-elapsed');
            if (daysChip.length) {
                let daysValue = null;
                if (typeof data.days_elapsed === 'number') {
                    daysValue = data.days_elapsed;
                } else if (typeof data.days_elapsed === 'string' && data.days_elapsed) {
                    const parsed = parseInt(data.days_elapsed, 10);
                    daysValue = isNaN(parsed) ? null : parsed;
                }

                if (daysValue !== null && !isNaN(daysValue)) {
                    const daysLabel = daysChip.data('label') || '';
                    daysChip.text((daysLabel ? daysLabel + ' ' : '') + daysValue).show();
                } else {
                    daysChip.hide();
                }
            }
        }

        quizConfig.hideResultsWhilePending = false;
        quizConfig.hasRenderedAttempt = true;
        setAwaitingState(false);

        const chartLabels = quizConfig.isFinalQuiz
            ? [quizConfig.chartLabels.final, quizConfig.chartLabels.first]
            : [quizConfig.chartLabels.single];
        const chartSeries = quizConfig.isFinalQuiz
            ? [
                (typeof quizConfig.finalScore === 'number' && !isNaN(quizConfig.finalScore)
                    ? quizConfig.finalScore
                    : (attemptPercentage !== null && !isNaN(attemptPercentage) ? attemptPercentage : 0)),
                (typeof quizConfig.firstScore === 'number' && !isNaN(quizConfig.firstScore)
                    ? quizConfig.firstScore
                    : 0)
            ]
            : [attemptPercentage !== null && !isNaN(attemptPercentage) ? attemptPercentage : 0];

        renderChart(chartSeries, chartLabels);
    }

    function queueRetry(retriesLeft, waitSeconds) {
        if (retriesLeft <= 0) {
            return;
        }

        setTimeout(function(){ pollLatestAttempt(retriesLeft); }, waitSeconds * 1000);
    }

    function pollLatestAttempt(retries) {
        if (!quizConfig.nonce || !ajaxUrl) {
            return;
        }

        const retriesLeft = typeof retries === 'number' ? retries : 0;
        const lastTimestamp = parseInt(quizConfig.currentAttemptTimestamp, 10) || 0;

        const requestData = {
            action: 'get_latest_quiz_activity',
            quiz_id: quizConfig.quizId,
            user_id: quizConfig.userId,
            nonce: quizConfig.nonce,
            last_timestamp: lastTimestamp
        };

        if (quizConfig.awaitingAttempt) {
            requestData.awaiting_attempt = '1';
        }

        $.post(ajaxUrl, requestData).done(function(response){
            if (response && response.success) {
                const payload = response.data || {};
                const waitSeconds = parseInt(payload.retry_after, 10) > 0 ? parseInt(payload.retry_after, 10) : defaultRetry;

                if (payload.status === 'pending') {
                    queueRetry(retriesLeft - 1, waitSeconds);
                    return;
                }

                if (payload.status === 'ready') {
                    const responseTimestamp = parseInt(payload.timestamp, 10) || 0;
                    const expectingNewerAttempt = !!quizConfig.awaitingAttempt;

                    if (!responseTimestamp) {
                        queueRetry(retriesLeft - 1, waitSeconds);
                        return;
                    }

                    if (expectingNewerAttempt && lastTimestamp && responseTimestamp <= lastTimestamp) {
                        queueRetry(retriesLeft - 1, waitSeconds);
                        return;
                    }

                    updateAttemptUI({
                        percentage: (typeof payload.percentage_rounded === 'number') ? payload.percentage_rounded : payload.percentage,
                        formatted_date: payload.formatted_date,
                        final_percentage: payload.final_percentage,
                        first_percentage: payload.first_percentage,
                        progress_delta: (typeof payload.progress_delta === 'number') ? payload.progress_delta : null,
                        days_elapsed: (typeof payload.days_elapsed === 'number') ? payload.days_elapsed : null,
                        score: (typeof payload.score === 'number') ? payload.score : null,
                        timestamp: responseTimestamp
                    });
                    return;
                }

                queueRetry(retriesLeft - 1, waitSeconds);
            } else {
                queueRetry(retriesLeft - 1, defaultRetry);
            }
        }).fail(function(){
            queueRetry(retriesLeft - 1, defaultRetry);
        });
    }

    document.addEventListener('DOMContentLoaded', function(){
        const wasAwaiting = !!quizConfig.awaitingAttempt;
        quizConfig.hideResultsWhilePending = true;
        setAwaitingState(true, { skipFlagUpdate: !wasAwaiting });
        pollLatestAttempt(6);
    });

    $(document).on('learndash-quiz-finished', function(){
        quizConfig.hideResultsWhilePending = true;
        quizConfig.hasRenderedAttempt = false;
        setAwaitingState(true);
        if (storage) {
            storage.setItem(awaitingStorageKey, String(Date.now()));
        }
        pollLatestAttempt(6);
    });
})(jQuery);
</script>
