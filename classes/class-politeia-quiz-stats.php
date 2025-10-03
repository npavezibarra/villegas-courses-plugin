<?php
/**
 * Aggregates quiz analytics data for template rendering.
 */
class Politeia_Quiz_Stats {
    /**
     * Quiz ID currently being rendered.
     *
     * @var int
     */
    protected $quiz_id;

    /**
     * User ID for which the stats are being collected.
     *
     * @var int
     */
    protected $user_id;

    /**
     * Underlying analytics helper.
     *
     * @var QuizAnalytics
     */
    protected $analytics;

    public function __construct( $quiz_id, $user_id = null ) {
        $this->quiz_id = intval( $quiz_id );
        $this->user_id = $user_id ? intval( $user_id ) : get_current_user_id();

        if ( ! class_exists( 'QuizAnalytics' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-quiz-analytics.php';
        }

        $this->analytics = new QuizAnalytics( $this->quiz_id, $this->user_id );
    }

    /**
     * Get the course ID owning the quiz.
     */
    public function get_course_id() {
        return $this->analytics->getCourse();
    }

    /**
     * Whether the current quiz is configured as the first quiz.
     */
    public function is_first_quiz() {
        return $this->analytics->isFirstQuiz();
    }

    /**
     * Whether the current quiz is configured as the final quiz.
     */
    public function is_final_quiz() {
        return $this->analytics->isFinalQuiz();
    }

    /**
     * Get metadata for the configured first quiz.
     */
    public function get_first_quiz_id() {
        return $this->analytics->getFirstQuiz();
    }

    /**
     * Get metadata for the configured final quiz.
     */
    public function get_final_quiz_id() {
        return $this->analytics->getFinalQuiz();
    }

    /**
     * Return attempt information for the quiz currently in context.
     */
    public function get_current_quiz_summary() {
        return $this->get_quiz_summary( $this->quiz_id );
    }

    /**
     * Return attempt information for the first quiz.
     */
    public function get_first_quiz_summary() {
        $quiz_id = $this->get_first_quiz_id();

        return $quiz_id ? $this->get_quiz_summary( $quiz_id ) : $this->empty_summary( $quiz_id );
    }

    /**
     * Return attempt information for the final quiz.
     */
    public function get_final_quiz_summary() {
        $quiz_id = $this->get_final_quiz_id();

        return $quiz_id ? $this->get_quiz_summary( $quiz_id ) : $this->empty_summary( $quiz_id );
    }

    /**
     * Access raw analytics object when needed.
     */
    public function get_analytics() {
        return $this->analytics;
    }

    /**
     * Build a quiz attempt summary.
     */
    public function get_quiz_summary( $quiz_id ) {
        $quiz_id     = intval( $quiz_id );
        $performance = $this->analytics->getQuizPerformance( $quiz_id );

        $percentage = is_numeric( $performance['percentage'] ?? null )
            ? floatval( $performance['percentage'] )
            : null;

        $timestamp = isset( $performance['timestamp'] ) ? intval( $performance['timestamp'] ) : 0;
        $has_attempt = ! empty( $performance['has_attempt'] );

        if ( $timestamp <= 0 ) {
            $has_attempt = false;
        }

        return [
            'quiz_id'            => $quiz_id,
            'score'              => intval( $performance['score'] ?? 0 ),
            'percentage'         => $percentage,
            'percentage_rounded' => is_null( $percentage ) ? null : round( $percentage ),
            'timestamp'          => $timestamp,
            'date'               => $performance['date'] ?? null,
            'has_attempt'        => $has_attempt,
            'formatted_date'     => $timestamp > 0 ? date_i18n( 'j \d\e F \d\e Y', $timestamp ) : null,
            'activity_id'        => intval( $performance['activity_id'] ?? 0 ),
        ];
    }

    protected function empty_summary( $quiz_id ) {
        return [
            'quiz_id'            => $quiz_id ? intval( $quiz_id ) : 0,
            'score'              => 0,
            'percentage'         => null,
            'percentage_rounded' => null,
            'timestamp'          => 0,
            'date'               => null,
            'has_attempt'        => false,
            'formatted_date'     => null,
        ];
    }
}
