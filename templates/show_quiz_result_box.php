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
$current_summary = $stats->get_current_quiz_summary();
$first_summary   = $stats->get_first_quiz_summary();
$final_summary   = $stats->get_final_quiz_summary();
$first_quiz_id   = $stats->get_first_quiz_id();
$final_quiz_id   = $stats->get_final_quiz_id();

$has_first_quiz = (bool) $first_quiz_id;
$has_final_quiz = (bool) $final_quiz_id;

$current_percentage       = $current_summary['percentage_rounded'];
$current_percentage_value = is_numeric( $current_percentage ) ? intval( $current_percentage ) : null;
$first_percentage_value   = is_numeric( $first_summary['percentage_rounded'] ) ? intval( $first_summary['percentage_rounded'] ) : null;
$final_percentage_value   = is_numeric( $final_summary['percentage_rounded'] ) ? intval( $final_summary['percentage_rounded'] ) : null;
$current_formatted  = $current_summary['formatted_date'] ?: date_i18n( 'j \d\e F \d\e Y' );

$has_course    = (bool) $course_id;
$has_access    = $has_course ? PoliteiaCourse::userHasAccess( $course_id, $user_id ) : false;
$product_id    = $has_course ? PoliteiaCourse::getRelatedProductId( $course_id ) : 0;
$product_url   = $product_id ? get_permalink( $product_id ) : '#';
$course_url    = $has_course ? get_permalink( $course_id ) : '#';

$comparison_available = $is_final_quiz
    && $has_first_quiz
    && $has_final_quiz
    && $first_summary['has_attempt']
    && $final_summary['has_attempt'];

$progress_delta = null;
$days_elapsed   = null;

if ( $comparison_available ) {
    if ( ! is_null( $final_percentage_value ) && ! is_null( $first_percentage_value ) ) {
        $progress_delta = $final_percentage_value - $first_percentage_value;
    }

    if ( $first_summary['timestamp'] && $final_summary['timestamp'] && $final_summary['timestamp'] >= $first_summary['timestamp'] ) {
        $days_elapsed = max( 1, floor( ( $final_summary['timestamp'] - $first_summary['timestamp'] ) / DAY_IN_SECONDS ) );
    }
}

$first_alert_messages = [];
if ( $is_first_quiz && ! $current_summary['has_attempt'] ) {
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

$motivation_copy = '';
if ( $is_first_quiz && ! is_null( $current_percentage_value ) ) {
    if ( $current_percentage_value >= 80 ) {
        $motivation_copy = sprintf(
            '¡Excelente comienzo con %s%%! Imagina cuánto podrás reforzar tu conocimiento al acceder a todas las lecciones.',
            $current_percentage_value
        );
    } elseif ( $current_percentage_value >= 50 ) {
        $motivation_copy = sprintf(
            'Tu puntaje de %s%% demuestra que ya conoces parte del contenido. Con el curso completo podrás dominarlo.',
            $current_percentage_value
        );
    } else {
        $motivation_copy = sprintf(
            'Este es solo el inicio: con el curso completo podrás mejorar ampliamente ese %s%% obtenido en la Prueba Inicial.',
            $current_percentage_value
        );
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
    <div class="politeia-quiz-results" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>">
        <div class="politeia-quiz-header">
            <div>
                <h3><?php echo esc_html( get_the_title( $quiz_id ) ); ?></h3>
                <div class="politeia-quiz-meta">
                    <?php echo esc_html( $course_title ); ?> · <?php echo esc_html( $current_formatted ); ?>
                </div>
            </div>
            <?php if ( $is_final_quiz && $first_summary['formatted_date'] ) : ?>
                <div class="politeia-quiz-meta" style="text-align:right;">
                    Prueba Inicial: <?php echo esc_html( $first_summary['formatted_date'] ); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="politeia-quiz-chart">
            <div id="politeia-quiz-chart"></div>
        </div>

        <div class="politeia-score-highlight">
            <span id="quiz-percentage">
                <?php echo ! is_null( $current_percentage_value ) ? esc_html( $current_percentage_value ) : '0'; ?>%
            </span>
        </div>

        <?php if ( $current_summary['has_attempt'] ) : ?>
            <div
                class="politeia-score-detail"
                id="politeia-score-detail"
                data-score-template="<?php echo esc_attr__( 'Puntaje obtenido: %d pts.', 'villegas-courses' ); ?>"
                data-score-fallback="<?php echo esc_attr__( 'Puntaje disponible pronto.', 'villegas-courses' ); ?>"
            >
                <?php
                if ( $current_summary['score'] ) {
                    printf(
                        /* translators: %d: quiz score */
                        esc_html__( 'Puntaje obtenido: %d pts.', 'villegas-courses' ),
                        intval( $current_summary['score'] )
                    );
                } else {
                    esc_html_e( 'Puntaje disponible pronto.', 'villegas-courses' );
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ( $is_first_quiz ) : ?>
            <?php if ( ! empty( $first_alert_messages ) ) : ?>
                <div class="politeia-alert" id="politeia-first-alert">
                    <?php foreach ( $first_alert_messages as $message ) : ?>
                        <p><?php echo esc_html( $message ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="politeia-cta-box">
                <?php if ( $motivation_copy ) : ?>
                    <p><?php echo esc_html( $motivation_copy ); ?></p>
                <?php endif; ?>
                <?php if ( $has_course ) : ?>
                    <a class="politeia-button" href="<?php echo esc_url( $cta_url ); ?>">
                        <?php echo esc_html( $cta_text ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif ( $is_final_quiz ) : ?>
            <?php if ( $comparison_available ) : ?>
                <div class="politeia-comparison">
                    <h4><?php esc_html_e( 'Comparativa con tu Prueba Inicial', 'villegas-courses' ); ?></h4>
                    <div class="politeia-comparison-grid">
                        <div class="politeia-comparison-card">
                            <span><?php esc_html_e( 'Prueba Inicial', 'villegas-courses' ); ?></span>
                            <strong id="politeia-first-score">
                                <?php
                                echo ! is_null( $first_percentage_value )
                                    ? esc_html( $first_percentage_value ) . '%'
                                    : '--';
                                ?>
                            </strong>
                        </div>
                        <div class="politeia-comparison-card">
                            <span><?php esc_html_e( 'Prueba Final', 'villegas-courses' ); ?></span>
                            <strong id="politeia-final-score">
                                <?php
                                echo ! is_null( $final_percentage_value )
                                    ? esc_html( $final_percentage_value ) . '%'
                                    : '--';
                                ?>
                            </strong>
                        </div>
                    </div>
                    <div class="politeia-comparison-meta">
                        <?php if ( ! is_null( $progress_delta ) ) : ?>
                            <div
                                class="politeia-chip"
                                id="politeia-progress-delta"
                                data-label="<?php echo esc_attr__( 'Progreso:', 'villegas-courses' ); ?>"
                            >
                                <?php esc_html_e( 'Progreso:', 'villegas-courses' ); ?> <?php echo $progress_delta >= 0 ? '+' : ''; ?><?php echo esc_html( $progress_delta ); ?>%
                            </div>
                        <?php endif; ?>
                        <?php if ( ! is_null( $days_elapsed ) ) : ?>
                            <div
                                class="politeia-chip"
                                id="politeia-days-elapsed"
                                data-label="<?php echo esc_attr__( 'Días transcurridos:', 'villegas-courses' ); ?>"
                            >
                                <?php esc_html_e( 'Días transcurridos:', 'villegas-courses' ); ?> <?php echo esc_html( $days_elapsed ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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
            <h4>Datos del Intento</h4>
            <div class="politeia-comparison-grid">
                <div class="politeia-comparison-card">
                    <span>Puntaje obtenido</span>
                    <strong id="politeia-attempt-percentage">--%</strong>
                </div>
                <div class="politeia-comparison-card">
                    <span>Fecha de intento</span>
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
        isFinalQuiz: <?php echo $is_final_quiz ? 'true' : 'false'; ?>,
        firstScore: <?php echo ! is_null( $first_percentage_value ) ? (int) $first_percentage_value : 'null'; ?>,
        finalScore: <?php echo ! is_null( $final_percentage_value ) ? (int) $final_percentage_value : 'null'; ?>,
        currentScore: <?php echo ! is_null( $current_percentage_value ) ? (int) $current_percentage_value : 'null'; ?>,
        nonce: (typeof quizData !== 'undefined' && quizData.activityNonce)
            ? quizData.activityNonce
            : (ajaxConfig.activityNonce || '')
    };

    const ajaxUrl = ajaxConfig.ajaxUrl || '';
    const defaultRetry = parseInt(ajaxConfig.retryAfter, 10) > 0 ? parseInt(ajaxConfig.retryAfter, 10) : 5;

    let chartInstance = null;

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

        const attemptBox = $('#politeia-quiz-attempt');
        const attemptPercentage = (typeof data.percentage === 'number') ? Math.round(data.percentage) : 0;
        const attemptScore = (typeof data.score === 'number') ? Math.round(data.score) : null;

        $('#politeia-attempt-percentage').text(attemptPercentage + '%');
        $('#politeia-attempt-date').text(data.formatted_date || '--');
        $('#quiz-percentage').text(attemptPercentage + '%');

        const scoreDetail = document.getElementById('politeia-score-detail');
        if (scoreDetail) {
            if (attemptScore !== null) {
                const template = scoreDetail.dataset.scoreTemplate || '';
                scoreDetail.textContent = template ? template.replace('%d', attemptScore) : attemptScore + ' pts';
            } else if (scoreDetail.dataset.scoreFallback) {
                scoreDetail.textContent = scoreDetail.dataset.scoreFallback;
            }
        }

        if (quizConfig.isFinalQuiz) {
            const finalValue = (typeof data.final_percentage === 'number') ? Math.round(data.final_percentage) : attemptPercentage;
            $('#politeia-final-score').text(finalValue + '%');

            if (typeof data.first_percentage === 'number') {
                const firstRounded = Math.round(data.first_percentage);
                $('#politeia-first-score').text(firstRounded + '%');
                quizConfig.firstScore = firstRounded;
            }

            if (typeof data.final_percentage === 'number') {
                quizConfig.finalScore = Math.round(data.final_percentage);
            }

            if (typeof data.first_percentage === 'number' && typeof data.final_percentage === 'number') {
                $('#politeia-final-alert').remove();

                const progressDelta = Math.round(data.final_percentage - data.first_percentage);
                const progressChip = $('#politeia-progress-delta');
                if (progressChip.length) {
                    const progressLabel = progressChip.data('label') || '';
                    const progressSign = progressDelta > 0 ? '+' : '';
                    progressChip.text((progressLabel ? progressLabel + ' ' : '') + progressSign + progressDelta + '%');
                }
            }
        } else {
            $('#politeia-first-alert').remove();
        }

        quizConfig.currentScore = attemptPercentage;

        if (attemptBox.length) {
            attemptBox.slideDown();
        }

        if (chartInstance) {
            const finalSeriesValue = quizConfig.isFinalQuiz
                ? (typeof data.final_percentage === 'number'
                    ? Math.round(data.final_percentage)
                    : (quizConfig.finalScore !== null ? quizConfig.finalScore : attemptPercentage))
                : attemptPercentage;
            const firstSeriesValue = quizConfig.isFinalQuiz
                ? (typeof data.first_percentage === 'number'
                    ? Math.round(data.first_percentage)
                    : (quizConfig.firstScore !== null ? quizConfig.firstScore : 0))
                : null;
            const newSeries = quizConfig.isFinalQuiz
                ? [finalSeriesValue, firstSeriesValue]
                : [attemptPercentage];
            chartInstance.updateSeries(newSeries);
        }
    }

    function pollLatestAttempt(retries) {
        if (!quizConfig.nonce || !ajaxUrl) {
            return;
        }

        $.post(ajaxUrl, {
            action: 'get_latest_quiz_activity',
            quiz_id: quizConfig.quizId,
            user_id: quizConfig.userId,
            nonce: quizConfig.nonce
        }).done(function(response){
            if (response && response.success) {
                const payload = response.data || {};

                if (payload.status === 'pending') {
                    if (retries > 0) {
                        const waitSeconds = parseInt(payload.retry_after, 10) > 0 ? parseInt(payload.retry_after, 10) : defaultRetry;
                        setTimeout(function(){ pollLatestAttempt(retries - 1); }, waitSeconds * 1000);
                    }
                    return;
                }

                updateAttemptUI({
                    percentage: (typeof payload.percentage_rounded === 'number') ? payload.percentage_rounded : payload.percentage,
                    formatted_date: payload.formatted_date,
                    final_percentage: payload.final_percentage,
                    first_percentage: payload.first_percentage,
                    score: (typeof payload.score === 'number') ? payload.score : null
                });
            } else if (retries > 0) {
                setTimeout(function(){ pollLatestAttempt(retries - 1); }, defaultRetry * 1000);
            }
        }).fail(function(){
            if (retries > 0) {
                setTimeout(function(){ pollLatestAttempt(retries - 1); }, defaultRetry * 1000);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function(){
        const baseSeries = quizConfig.isFinalQuiz
            ? [
                (quizConfig.finalScore !== null ? quizConfig.finalScore : 0),
                (quizConfig.firstScore !== null ? quizConfig.firstScore : 0)
            ]
            : [
                (quizConfig.currentScore !== null ? quizConfig.currentScore : 0)
            ];
        const baseLabels = quizConfig.isFinalQuiz ? ['Prueba Final', 'Prueba Inicial'] : ['Resultado'];
        renderChart(baseSeries, baseLabels);

        if (<?php echo $current_summary['has_attempt'] ? 'true' : 'false'; ?>) {
            updateAttemptUI({
                percentage: <?php echo ! is_null( $current_percentage_value ) ? (int) $current_percentage_value : 0; ?>,
                formatted_date: <?php echo wp_json_encode( $current_summary['formatted_date'] ); ?>,
                final_percentage: <?php echo ! is_null( $final_percentage_value ) ? (int) $final_percentage_value : 'null'; ?>,
                first_percentage: <?php echo ! is_null( $first_percentage_value ) ? (int) $first_percentage_value : 'null'; ?>,
                score: <?php echo intval( $current_summary['score'] ); ?>
            });
        }
    });

    $(document).on('learndash-quiz-finished', function(){
        pollLatestAttempt(6);
    });
})(jQuery);
</script>
