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

// Render shortcode to populate static/global vars.
ob_start();
do_shortcode( '[villegas_quiz_attempts id="' . get_the_ID() . '"]' );
ob_end_clean();

// Get global values.
$villegas_average = isset( Villegas_Quiz_Attempts_Shortcode::$last_average ) ? intval( Villegas_Quiz_Attempts_Shortcode::$last_average ) : 0;

global $wpdb;
$current_user_id = get_current_user_id();
$quiz_id         = get_the_ID();

// Check course association.
$course_id     = null;
$is_first_quiz = false;
$is_final_quiz = false;

// Detect quiz type.
$course_id_from_first = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_first_quiz_id' AND meta_value = %d",
        $quiz_id
    )
);

if ( $course_id_from_first ) {
    $course_id     = $course_id_from_first;
    $is_first_quiz = true;
}

$course_id_from_final = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_final_quiz_id' AND meta_value = %d",
        $quiz_id
    )
);

if ( $course_id_from_final ) {
    $course_id     = $course_id_from_final;
    $is_final_quiz = true;
}

// Get First Quiz score if on Final Quiz.
$first_quiz_score = 0;

if ( $is_final_quiz && $course_id && class_exists( 'Villegas_Quiz_Stats' ) ) {
    $first_quiz_id = get_post_meta( $course_id, '_first_quiz_id', true );

    if ( $first_quiz_id ) {
        $latest_id = Villegas_Quiz_Stats::get_latest_attempt_id( $current_user_id, intval( $first_quiz_id ) );

        if ( $latest_id ) {
            $data = Villegas_Quiz_Stats::get_score_and_pct_by_activity( intval( $latest_id ) );

            if ( $data && isset( $data->percentage ) ) {
                $first_quiz_score = round( floatval( $data->percentage ) );
            }
        }
    }
}
?>

<div class="wpProQuiz_results" style="margin-top:40px; text-align:center;">
    <div id="score" style="font-weight: bold; font-size: 16px; margin-bottom: 20px;"></div>

    <div style="display:flex; justify-content:center; gap:40px; flex-wrap:wrap; margin-bottom:30px;">
        <div style="max-width:300px;"><div id="radial-chart"></div></div>
        <div style="max-width:300px;"><div id="radial-chart-promedio"></div></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const target = document.querySelector('.wpProQuiz_points.wpProQuiz_points--message span:nth-child(3)');
    if (!target) {
        return;
    }

    const chartContainer = document.querySelector('#radial-chart');
    const chartPromedio  = document.querySelector('#radial-chart-promedio');
    const isFinalQuiz    = <?php echo wp_json_encode( (bool) $is_final_quiz ); ?>;
    const firstQuizScore = <?php echo wp_json_encode( intval( $first_quiz_score ) ); ?>;
    const averagePHP     = <?php echo wp_json_encode( intval( $villegas_average ) ); ?>;

    const observer = new MutationObserver(() => {
        const pct = parseFloat(target.innerText.replace('%', '').trim());
        if (Number.isNaN(pct)) {
            return;
        }

        observer.disconnect();

        const chartOptions = (val, label) => ({
            series: [val],
            chart: { height: 350, type: 'radialBar' },
            plotOptions: {
                radialBar: {
                    hollow: { size: '60%' },
                    dataLabels: {
                        name: { show: true, color: '#555', fontSize: '16px' },
                        value: {
                            show: true,
                            fontSize: '30px',
                            fontWeight: 600,
                            color: '#111',
                            formatter: (value) => `${Math.round(value)}%`
                        }
                    }
                }
            },
            labels: [label],
            colors: ['#d29d01'],
            fill: { type: 'gradient', gradient: { shade: 'light', gradientToColors: ['#ffd000'], stops: [0, 100] } }
        });

        if (chartContainer) {
            new ApexCharts(chartContainer, chartOptions(pct, 'Tu Puntaje')).render();
        }

        if (chartPromedio) {
            const promedioValue = isFinalQuiz ? firstQuizScore : averagePHP;
            const promedioLabel = isFinalQuiz ? 'First Quiz' : 'Promedio Villegas';
            new ApexCharts(chartPromedio, chartOptions(promedioValue, promedioLabel)).render();
        }

        const scoreDiv = document.getElementById('score');
        if (isFinalQuiz && scoreDiv) {
            const delta = Math.round(pct - firstQuizScore);
            let msg = '';

            if (delta > 0) {
                msg = `<h3 style="color:#4CAF50;">¡Mejoraste ${delta} puntos!</h3>`;
            } else if (delta === 0) {
                msg = '<h3>Tu resultado se mantiene estable.</h3>';
            } else {
                msg = `<h3 style="color:#D32F2F;">Bajaste ${Math.abs(delta)} puntos. Puedes intentarlo nuevamente.</h3>`;
            }

            scoreDiv.innerHTML = msg;
        }
    });

    observer.observe(target, { childList: true, characterData: true, subtree: true });
});
</script>
