<?php
/**
 * Clase para analizar el comportamiento de un Quiz en LearnDash:
 * - Determinar si un quiz es “Primer Quiz” o “Final Quiz”
 * - Obtener puntuaciones y fechas de intento
 * - Proveer datos de rendimiento para Frontend / Emails
 */
class QuizAnalytics {
    private $quiz_id;
    private $course_id;
    private $first_quiz_id;
    private $final_quiz_id;
    private $user_id;

    public function __construct( $quiz_id, $user_id = null ) {
        if ( ! class_exists( 'CourseQuizMetaHelper' ) ) {
            require_once __DIR__ . '/class-course-quiz-helper.php';
        }

        $this->quiz_id = intval( $quiz_id );
        $this->user_id = $user_id ? intval( $user_id ) : get_current_user_id();

        $this->course_id     = CourseQuizMetaHelper::getCourseFromQuiz( $this->quiz_id );
        $this->first_quiz_id = $this->course_id ? CourseQuizMetaHelper::getFirstQuizId( $this->course_id ) : 0;
        $this->final_quiz_id = $this->course_id ? CourseQuizMetaHelper::getFinalQuizId( $this->course_id ) : 0;
    }

    /**
     * Retorna el Course ID asociado a este quiz.
     * Si no pertenece a ningún curso, retorna 0.
     */
    public function getCourse() {
        return $this->course_id;
    }

    /**
     * Retorna el ID del Primer Quiz (postmeta "_first_quiz_id").
     * Si no existe, retorna 0.
     */
    public function getFirstQuiz() {
        return $this->first_quiz_id;
    }

    /**
     * Retorna el ID del Quiz Final (postmeta "_final_quiz_id").
     * Si no existe, retorna 0.
     */
    public function getFinalQuiz() {
        return $this->final_quiz_id;
    }

    /**
     * Determina si este quiz es la Prueba Inicial (“First Quiz”).
     */
    public function isFirstQuiz() {
        if ( ! $this->course_id || ! $this->first_quiz_id ) {
            return false;
        }

        return ( $this->quiz_id === $this->first_quiz_id );
    }

    /**
     * Determina si este quiz es la Prueba Final (“Final Quiz”).
     */
    public function isFinalQuiz() {
        if ( ! $this->course_id || ! $this->final_quiz_id ) {
            return false;
        }

        return ( $this->quiz_id === $this->final_quiz_id );
    }

    /**
     * Retorna datos de rendimiento (score, percentage, date, attempts) de un quiz dado para este usuario.
     * Si no hay intentos, devolvemos valores por defecto.
     */
    private function getUserQuizPerformance( $quiz_id ) {
        global $wpdb;

        $activity_row = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT ua.activity_id, ua.activity_started, ua.activity_completed
                  FROM {$wpdb->prefix}learndash_user_activity AS ua
            INNER JOIN {$wpdb->prefix}learndash_user_activity_meta AS uam
                    ON uam.activity_id = ua.activity_id
                   AND uam.activity_meta_key = 'quiz'
                 WHERE ua.user_id = %d
                   AND ua.activity_type = 'quiz'
                   AND uam.activity_meta_value+0 = %d
              ORDER BY ua.activity_completed DESC, ua.activity_id DESC
                 LIMIT 1
                ",
                $this->user_id,
                $quiz_id
            )
        );

        $activity_id = $activity_row ? intval( $activity_row->activity_id ) : 0;

        if ( ! $activity_id ) {
            return [
                'score'       => 0,
                'percentage'  => null,
                'attempts'    => 0,
                'date'        => '',
                'timestamp'   => 0,
                'has_attempt' => false,
                'activity_id' => 0,
                'duration'    => 0,
                'questions_correct' => 0,
                'questions_total'   => 0,
            ];
        }

        $meta = politeia_fetch_activity_meta_map( $activity_id );

        $quiz_meta_value = isset( $meta['quiz'] ) ? intval( $meta['quiz'] ) : 0;
        $percentage_raw  = isset( $meta['percentage'] ) ? $meta['percentage'] : null;
        $has_percentage  = ( '' !== $percentage_raw && null !== $percentage_raw && is_numeric( $percentage_raw ) );

        if ( $quiz_meta_value !== intval( $quiz_id ) || ! $has_percentage ) {
            error_log( sprintf( '[QuizAnalytics] User %d, Quiz %d, Activity %d, Status: pending (metadata incomplete)', $this->user_id, $quiz_id, $activity_id ) );

            return [
                'score'       => null,
                'percentage'  => null,
                'attempts'    => 1,
                'date'        => '',
                'timestamp'   => 0,
                'has_attempt' => false,
                'activity_id' => $activity_id,
                'duration'    => 0,
                'questions_correct' => 0,
                'questions_total'   => 0,
            ];
        }

        $score = ( isset( $meta['score'] ) && is_numeric( $meta['score'] ) ) ? intval( $meta['score'] ) : 0;
        $percentage = round( floatval( $percentage_raw ), 2 );

        $activity_completed_raw = $activity_row && ! empty( $activity_row->activity_completed )
            ? $activity_row->activity_completed
            : '';
        $activity_started_raw = $activity_row && ! empty( $activity_row->activity_started )
            ? $activity_row->activity_started
            : '';

        $timestamp = $activity_completed_raw ? strtotime( $activity_completed_raw ) : 0;
        $started   = $activity_started_raw ? strtotime( $activity_started_raw ) : 0;

        $attempt_date = $timestamp
            ? date_i18n( 'j \d\e F \d\e Y', $timestamp )
            : '';

        $duration = 0;

        if ( $timestamp && $started && $timestamp >= $started ) {
            $duration = $timestamp - $started;
        }

        $questions_total   = isset( $meta['question_count'] ) ? intval( $meta['question_count'] ) : 0;
        $questions_correct = isset( $meta['correct'] ) ? intval( $meta['correct'] ) : 0;

        if ( ! $questions_total && isset( $meta['total'] ) && is_numeric( $meta['total'] ) ) {
            $questions_total = intval( $meta['total'] );
        }

        if ( ! $questions_correct && isset( $meta['count'] ) && is_numeric( $meta['count'] ) ) {
            $questions_correct = intval( $meta['count'] );
        }

        error_log( sprintf( '[QuizAnalytics] User %d, Quiz %d, Activity %d, Status: ready, Percentage %s', $this->user_id, $quiz_id, $activity_id, $percentage ) );

        return [
            'score'       => $score,
            'percentage'  => $percentage,
            'attempts'    => 1,
            'date'        => $attempt_date,
            'timestamp'   => $timestamp,
            'has_attempt' => ( $timestamp > 0 ),
            'activity_id' => $activity_id,
            'duration'    => $duration,
            'questions_correct' => $questions_correct,
            'questions_total'   => $questions_total,
        ];
    }

    /**
     * Retrieve performance information for any quiz ID.
     */
    public function getQuizPerformance( $quiz_id = null ) {
        $quiz_id = $quiz_id ? intval( $quiz_id ) : $this->quiz_id;

        if ( ! $quiz_id ) {
            return [
                'score'      => 0,
                'percentage' => 'N/A',
                'attempts'   => 0,
                'date'       => 'No Attempts',
                'timestamp'  => 0,
            ];
        }

        return $this->getUserQuizPerformance( $quiz_id );
    }

    public function getFirstQuizTimestamp() {
        global $wpdb;
    
        $first_quiz_id = $this->getFirstQuiz();
        if ( ! $first_quiz_id ) {
            return 0;
        }
    
        // - Obtiene el último intento del usuario para ese quiz
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT activity_completed, activity_started
                   FROM {$wpdb->prefix}learndash_user_activity
                  WHERE user_id       = %d
                    AND post_id       = %d
                    AND activity_type = 'quiz'
               ORDER  BY activity_completed DESC
                  LIMIT 1",
                $this->user_id,
                $first_quiz_id
            )
        );
    
        if ( ! $row ) {
            return 0;                                    // Nunca lo abrió
        }
    
        $timestamp = (int) $row->activity_completed;
        if ( $timestamp === 0 ) {
            $timestamp = (int) $row->activity_started;   // Fallback
        }
    
        return $timestamp;
    }
    
    
    

    /**
     * Retorna datos de rendimiento para el Primer Quiz.
     * Si no hay _first_quiz_id o no se han intentado, retorna valores por defecto.
     */
    public function getFirstQuizPerformance() {
        if ( $this->first_quiz_id === 0 ) {
            return [
                'score'      => 0,
                'percentage' => 'N/A',
                'attempts'   => 0,
                'date'       => 'No Attempts',
                'timestamp'  => 0,
            ];
        }
        return $this->getUserQuizPerformance( $this->first_quiz_id );
    }

    /**
     * Retorna datos de rendimiento para el Final Quiz (este mismo quiz).
     * Solo aplica si isFinalQuiz() es verdadero.
     */
    public function getFinalQuizPerformance() {
        if ( ! $this->final_quiz_id ) {
            return [
                'score'      => 0,
                'percentage' => 'N/A',
                'attempts'   => 0,
                'date'       => 'No Attempts',
                'timestamp'  => 0,
            ];
        }
        return $this->getUserQuizPerformance( $this->final_quiz_id );
    }

    /**
     * Muestra un bloque HTML con información de debugging:
     * Quiz ID, Course ID, First Quiz ID, Final Quiz ID, e intentos.
     */
    public function displayResults() {
        $course_display = $this->course_id ? esc_html( $this->course_id ) : "No Course";
        $first_display  = $this->first_quiz_id ? esc_html( $this->first_quiz_id ) : "No First Quiz";
        $final_display  = $this->final_quiz_id ? esc_html( $this->final_quiz_id ) : "No Final Quiz";

        $first_perf = $this->getFirstQuizPerformance();
        $final_perf = $this->getFinalQuizPerformance();

        echo "<div style='background: #f4f4f4; padding: 15px; border-radius: 6px; max-width: 600px; margin: 20px auto;'>";
        echo "<p><strong>Quiz ID:</strong> " . esc_html( $this->quiz_id ) . "</p>";
        echo "<p><strong>Course ID:</strong> " . $course_display . "</p>";
        echo "<p><strong>First Quiz ID:</strong> " . $first_display . "</p>";
        echo "<p><strong>Final Quiz ID:</strong> " . $final_display . "</p>";
        echo "<hr style='margin: 10px 0;'>";
        echo "<p><strong>First Quiz Score:</strong> " . esc_html( $first_perf['score'] ) . "</p>";
        echo "<p><strong>First Quiz %:</strong> " . esc_html( $first_perf['percentage'] ) . "</p>";
        echo "<p><strong>First Quiz Date:</strong> " . esc_html( $first_perf['date'] ) . "</p>";
        echo "<hr style='margin: 10px 0;'>";
        echo "<p><strong>Final Quiz Score:</strong> " . esc_html( $final_perf['score'] ) . "</p>";
        echo "<p><strong>Final Quiz %:</strong> " . esc_html( $final_perf['percentage'] ) . "</p>";
        echo "<p><strong>Final Quiz Date:</strong> " . esc_html( $final_perf['date'] ) . "</p>";
        echo "</div>";
    }
}
