<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$course_id = intval( $_POST['course_id'] ?? 0 );
$user_id   = get_current_user_id();

if ( ! $course_id || ! $user_id ) {
    echo '<p>Error: faltan datos.</p>';
    return;
}

// -----------------------------------------------------------------------------
// Utilidades
// -----------------------------------------------------------------------------
global $wpdb;

/**
 * Devuelve el último intento de un quiz (o null si no hay intento).
 */
function villegas_last_quiz_attempt( $wpdb, $user_id, $quiz_id ) {
    if ( ! $quiz_id ) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT activity_id, activity_completed
             FROM {$wpdb->prefix}learndash_user_activity
             WHERE user_id   = %d
               AND post_id   = %d
               AND activity_type = 'quiz'
             ORDER BY activity_completed DESC
             LIMIT 1",
            $user_id,
            $quiz_id
        )
    );
}

/**
 * Construye un array normalizado con % y fecha formateada.
 */
function villegas_build_quiz_data( $wpdb, $attempt ) {
    $data = [
        'pct'            => 0,
        'date'           => null,
        'formatted_date' => 'N/A',
    ];

    if ( ! $attempt ) {  // no hay intento
        return $data;
    }

    // 1. Porcentaje
    $pct = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT activity_meta_value
             FROM {$wpdb->prefix}learndash_user_activity_meta
             WHERE activity_id       = %d
               AND activity_meta_key = 'percentage'",
            $attempt->activity_id
        )
    );
    $data['pct'] = round( floatval( $pct ) );

    // 2. Timestamp: si activity_completed es 0, usamos activity_started
    $timestamp = intval( $attempt->activity_completed );
    if ( $timestamp === 0 ) {
        $timestamp = intval( $attempt->activity_started );
    }
    $data['date'] = $timestamp;

    if ( $timestamp > 0 ) {
        $data['formatted_date'] = date_i18n( 'j \d\e F \d\e Y', $timestamp );
    }
    return $data;
}


// -----------------------------------------------------------------------------
// Datos del curso / quizzes
// -----------------------------------------------------------------------------
$course_title   = get_the_title( $course_id );
$first_quiz_id  = intval( get_post_meta( $course_id, '_first_quiz_id', true ) );
$quiz_steps     = learndash_course_get_steps_by_type( $course_id, 'sfwd-quiz' );
$final_quiz_id  = ! empty( $quiz_steps ) ? end( $quiz_steps ) : 0;

$first_data = villegas_build_quiz_data(
    $wpdb,
    villegas_last_quiz_attempt( $wpdb, $user_id, $first_quiz_id )
);

$final_data = villegas_build_quiz_data(
    $wpdb,
    villegas_last_quiz_attempt( $wpdb, $user_id, $final_quiz_id )
);

if ( $first_data['date'] === null || $final_data['date'] === null ) {
    echo '<p>Faltan resultados para mostrar comparativa.</p>';
    return;
}

// Variación y días
$variation = $final_data['pct'] - $first_data['pct'];
$days_diff = max( 1, floor( ( $final_data['date'] - $first_data['date'] ) / DAY_IN_SECONDS ) );
?>
<style>
.quiz-results-container,
.extra-stats-container{
    border:1px solid #d5d5d5;
    padding:20px;
    border-radius:8px;
    background:#fff;
    margin-bottom:20px;
}
.quiz-flex{display:flex;justify-content:space-between;align-items:center;}
.quiz-name{font-weight:bold;font-size:16px;}
.quiz-percentage{font-size:24px;font-weight:bold;text-align:right;}
</style>

<p style="font-size:10px;text-align:center;margin-bottom:10px;letter-spacing:10px;">CURSO</p>
<h3 style="text-align:center;margin-top:0;"><?php echo esc_html( $course_title ); ?></h3>

<!-- PRUEBA INICIAL -->
<div class="quiz-results-container">
  <div class="quiz-flex">
    <div>
      <div class="quiz-name">Prueba Inicial</div>
      <div style="color:#666;">HOLA</div>
    </div>
    <div style="width:50%;background:#e9ecef;border-radius:15px;height:20px;overflow:hidden;">
      <div style="width:<?php echo $first_data['pct']; ?>%;height:100%;background:#ff9800;"></div>
    </div>
    <div class="quiz-percentage"><?php echo $first_data['pct']; ?>%</div>
  </div>
</div>

<!-- PRUEBA FINAL -->
<div class="quiz-results-container">
  <div class="quiz-flex">
    <div>
      <div class="quiz-name">Prueba Final</div>
      <div style="color:#666;"><?php echo esc_html( $final_data['formatted_date'] ); ?></div>
    </div>
    <div style="width:50%;background:#e9ecef;border-radius:15px;height:20px;overflow:hidden;">
      <div style="width:<?php echo $final_data['pct']; ?>%;height:100%;background:#ff9800;"></div>
    </div>
    <div class="quiz-percentage"><?php echo $final_data['pct']; ?>%</div>
  </div>
</div>

<!-- BLOQUE EXTRA -->
<div class="extra-stats-container" style="margin-bottom:0;">
  <div class="quiz-flex">
    <div style="flex:1;text-align:center;">
      <div style="font-size:16px;color:#666;">Variación conocimientos</div>
      <div style="font-size:36px;font-weight:bold;color:<?php echo $variation >= 0 ? '#9fd99f' : 'red'; ?>">
        <?php echo abs( $variation ); ?>% <span><?php echo $variation >= 0 ? '▲' : '▼'; ?></span>
      </div>
    </div>
    <div style="flex:1;text-align:center;">
      <div style="font-size:16px;color:#666;">Completaste el curso en</div>
      <div style="font-size:36px;font-weight:bold;"><?php echo $days_diff . ' ' . ( $days_diff === 1 ? 'día' : 'días' ); ?></div>
    </div>
  </div>
</div>
