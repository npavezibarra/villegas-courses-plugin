<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function villegas_get_final_quiz_email_content( array $debug, WP_User $user ): array {
    error_log( '[FinalQuizEmail] villegas_get_final_quiz_email_content START' );

    if ( empty( $debug['is_final_quiz'] ) ) {
        error_log( '[FinalQuizEmail] Debug payload is not final quiz. Returning empty content.' );
        return [
            'subject'             => '',
            'body'                => '',
            'initial_percentage'  => null,
            'final_percentage'    => null,
        ];
    }

    $first_score = villegas_normalize_percentage_value( $debug['first_attempt']['percentage'] ?? null );
    $final_score = villegas_normalize_percentage_value( $debug['final_attempt']['percentage'] ?? null );

    $first_score = null !== $first_score ? round( max( 0, min( 100, $first_score ) ), 2 ) : null;
    $final_score = null !== $final_score ? round( max( 0, min( 100, $final_score ) ), 2 ) : null;

    error_log(
        sprintf(
            '[FinalQuizEmail] Content builder percentages: initial=%s final=%s',
            null === $first_score ? 'null' : $first_score,
            null === $final_score ? 'null' : $final_score
        )
    );

    $course_title = $debug['course_title'] ?: ( $debug['quiz_title'] ?? '' );
    $quiz_title   = $debug['quiz_title'] ?: $course_title;

    $email_file = plugin_dir_path( __FILE__ ) . 'final-quiz-email.php';
    if ( file_exists( $email_file ) ) {
        $email_body = file_get_contents( $email_file );
    } else {
        $email_body = '<p>Has finalizado la Evaluación Final.</p>';
    }

    $first_display = null !== $first_score ? Villegas_Quiz_Stats::format_percentage( (float) $first_score ) : null;
    $final_display = null !== $final_score ? Villegas_Quiz_Stats::format_percentage( (float) $final_score ) : null;

    $replacements = [
        '{{user_name}}'              => $user->display_name,
        '{{course_name}}'            => $course_title,
        '{{quiz_name}}'              => $quiz_title,
        '{{first_quiz_percentage}}'  => null !== $first_display ? $first_display : __( 'Sin datos', 'villegas-courses' ),
        '{{final_quiz_percentage}}'  => null !== $final_display ? $final_display : __( 'Sin datos', 'villegas-courses' ),
    ];

    $body = strtr( $email_body, $replacements );

    $subject = sprintf(
        /* translators: %s: quiz or course title */
        __( 'Has finalizado la Evaluación Final: %s', 'villegas-courses' ),
        $course_title ?: $quiz_title
    );

    return [
        'subject'             => $subject,
        'body'                => $body,
        'initial_percentage'  => $first_score,
        'final_percentage'    => $final_score,
    ];
}
