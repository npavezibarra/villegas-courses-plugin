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

        $activity_id = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT activity_id
                FROM {$wpdb->prefix}learndash_user_activity
                WHERE user_id       = %d
                  AND post_id       = %d
                  AND activity_type = 'quiz'
                ORDER BY activity_completed DESC
                LIMIT 1
                ",
                $this->user_id,
                $quiz_id
            )
        );

        if ( ! $activity_id ) {
            return [
                'score'      => 0,
                'percentage' => 'N/A',
                'attempts'   => 0,
                'date'       => 'No Attempts',
                'timestamp'  => 0,
            ];
        }

        $score = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT activity_meta_value
                FROM {$wpdb->prefix}learndash_user_activity_meta
                WHERE activity_id       = %d
                  AND activity_meta_key = 'score'
                LIMIT 1
                ",
                $activity_id
            )
        );
        $score = $score !== null ? intval( $score ) : 0;

        $percentage = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT activity_meta_value
                FROM {$wpdb->prefix}learndash_user_activity_meta
                WHERE activity_id       = %d
                  AND activity_meta_key = 'percentage'
                LIMIT 1
                ",
                $activity_id
            )
        );
        $percentage = $percentage !== null ? floatval( $percentage ) : 'N/A';

        $latest_attempt_ts = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT activity_completed
                FROM {$wpdb->prefix}learndash_user_activity
                WHERE activity_id = %d
                LIMIT 1
                ",
                $activity_id
            )
        );

        $attempt_date = ( $latest_attempt_ts && intval( $latest_attempt_ts ) > 0 )
            ? date_i18n( 'j \d\e F \d\e Y', intval( $latest_attempt_ts ) )
            : 'No Attempts';

        return [
            'score'      => $score,
            'percentage' => $percentage,
            'attempts'   => 1,
            'date'       => $attempt_date,
            'timestamp'  => intval( $latest_attempt_ts ),
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
