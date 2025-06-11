<?php
// Shortcode to display course name, First Quiz status, number of attempts, and attempt percentages
function shortcode_display_course_and_quiz_status($atts) {
    $atts = shortcode_atts(
        array(
            'course_id' => get_the_ID(),
        ),
        $atts,
        'quiz_analytics'
    );

    $course_id    = $atts['course_id'];
    $course_title = get_the_title($course_id);
    if (!$course_title) {
        return "Course not found.";
    }

    // Retrieve the First Quiz ID
    $first_quiz_id   = get_post_meta($course_id, '_first_quiz_id', true);
    $has_first_quiz  = $first_quiz_id ? 'Yes' : 'No';

    $user_id          = get_current_user_id();
    $attempts         = get_user_meta($user_id, '_sfwd-quizzes', true);
    $attempt_count    = 0;
    $attempt_percentages = [];

    // Optional: debug log
    // error_log('All $attempts: ' . print_r($attempts, true));

    if (!empty($attempts) && is_array($attempts)) {
        foreach ($attempts as $attempt) {
            if (!is_array($attempt)) {
                continue;
            }
            if (isset($attempt['quiz']) && (int) $attempt['quiz'] === (int) $first_quiz_id) {
                $attempt_count++;

                // error_log('Attempt Data: ' . print_r($attempt, true));

                // Find activity_id or statistic_ref_id
                $activity_id = 0;
                if (!empty($attempt['activity_id'])) {
                    $activity_id = (int) $attempt['activity_id'];
                } elseif (!empty($attempt['statistic_ref_id'])) {
                    $activity_id = (int) $attempt['statistic_ref_id'];
                }

                // Try to retrieve "percentage" from DB
                global $wpdb;
                $percentage = null;
                if ($activity_id) {
                    $percentage = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT activity_meta_value
                               FROM {$wpdb->prefix}learndash_user_activity_meta
                              WHERE activity_id = %d
                                AND activity_meta_key = 'percentage'",
                            $activity_id
                        )
                    );
                }
                // Fallback: check in $attempt if DB query is empty
                if (($percentage === null || $percentage === '') && isset($attempt['percentage'])) {
                    $percentage = $attempt['percentage'];
                }

                // IMPORTANT: allow "0"
                if ($percentage !== null && $percentage !== '') {
                    $attempt_percentages[] = $percentage . '%';
                }
            }
        }
    }

    $percentages_output = ($attempt_count > 0) 
        ? implode(' | ', $attempt_percentages) 
        : 'No attempts yet';

    // Build table
    $output  = '<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
    $output .= '<tr><th>Course Name</th><th>First Quiz</th><th>Attempts</th><th>Percentages</th></tr>';
    $output .= '<tr>';
    $output .= '<td>' . esc_html($course_title) . '</td>';
    $output .= '<td>' . esc_html($has_first_quiz) . '</td>';
    $output .= '<td>' . esc_html($attempt_count) . '</td>';
    $output .= '<td>' . esc_html($percentages_output) . '</td>';
    $output .= '</tr>';
    $output .= '</table>';

    return $output;
}
add_shortcode('quiz_analytics', 'shortcode_display_course_and_quiz_status');


/* FINAL QUIZ SHORTCODE */

function shortcode_display_course_and_final_quiz_status($atts) {
    // Default to current page ID if course_id not provided
    $atts = shortcode_atts(
        array('course_id' => get_the_ID()),
        $atts,
        'quiz_analytics_final'
    );

    $course_id    = $atts['course_id'];
    $course_title = get_the_title($course_id);
    if (!$course_title) {
        return "Course not found.";
    }

    // Retrieve course steps meta
    $course_steps = get_post_meta($course_id, 'ld_course_steps', true);
    $final_quiz_exists = 'No';
    $final_quiz_id     = null;

    // Convert steps to array (in case it's serialized)
    if (!empty($course_steps) && !is_array($course_steps)) {
        $course_steps = @unserialize($course_steps);
    }

    // Find the "final" quiz in the course steps
    if (!empty($course_steps['steps']) && is_array($course_steps['steps'])) {
        foreach ($course_steps['steps'] as $step) {
            if (!empty($step['sfwd-quiz']) && is_array($step['sfwd-quiz'])) {
                foreach ($step['sfwd-quiz'] as $quiz_id => $quiz_data) {
                    $final_quiz_exists = 'Yes';
                    $final_quiz_id     = $quiz_id;
                    break 2;  // Exit both loops when found
                }
            }
        }
    }

    $user_id = get_current_user_id();
    global $wpdb;

    $percentages   = array();
    $attempt_count = 0;

    // If a final quiz is found, look up attempts in wp_learndash_user_activity
    if ($final_quiz_id) {
        // Get all activity_ids for this quiz from the activity table
        $activity_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT activity_id 
                 FROM {$wpdb->prefix}learndash_user_activity
                 WHERE user_id = %d
                   AND course_id = %d
                   AND post_id = %d
                   AND activity_type = 'quiz'
                 ORDER BY activity_id ASC",
                $user_id,
                $course_id,
                $final_quiz_id
            )
        );

        // Count attempts
        $attempt_count = count($activity_ids);

        // For each activity_id, get the percentage from user_activity_meta
        if ($attempt_count > 0) {
            foreach ($activity_ids as $act_id) {
                $percentage = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT activity_meta_value
                         FROM {$wpdb->prefix}learndash_user_activity_meta
                         WHERE activity_id = %d
                           AND activity_meta_key = 'percentage'",
                        $act_id
                    )
                );
                // Fix: check specifically for null/empty string, 
                // so "0" (0%) will pass:
                if ($percentage !== null && $percentage !== '') {
                    $percentages[] = $percentage . '%';
                }
            }
        }
    }

    // Generate output for percentages
    $percentages_output = !empty($percentages)
        ? implode(' | ', $percentages)
        : 'No attempts yet';

    // Generate table
    $output  = '<table border="1" cellpadding="10" cellspacing="0" style="width:100%;border-collapse:collapse;">';
    $output .= '<tr><th>Course Title</th><th>Final Quiz</th><th>Attempts</th><th>Percentages</th></tr>';
    $output .= '<tr>';
    $output .= '<td>' . esc_html($course_title) . '</td>';
    $output .= '<td>' . esc_html($final_quiz_exists) . '</td>';
    $output .= '<td>' . esc_html($attempt_count) . '</td>';
    $output .= '<td>' . esc_html($percentages_output) . '</td>';
    $output .= '</tr>';
    $output .= '</table>';

    return $output;
}

add_shortcode('quiz_analytics_final', 'shortcode_display_course_and_final_quiz_status');


/* SHORTCODE CLASS TEST */

function test_quiz_analytics_shortcode($atts) {
    // Set default values for user_id and course_id
    $atts = shortcode_atts(
        array(
            'course_id' => get_the_ID(),  // Default to current page's ID if not provided
        ),
        $atts,
        'quiz_analytics_test'
    );

    $user_id = get_current_user_id();
    $course_id = $atts['course_id']; // Use the course_id passed in the shortcode

    // Instantiate the class
    $user_quiz_analytics = new QuizAnalytics($quiz_id, $user_id);

    // Get First Quiz Results
    $first_quiz_data = $user_quiz_analytics->get_first_quiz();

    // Get Final Quiz Results
    $final_quiz_data = $user_quiz_analytics->get_final_quiz();

    // Output the results
    $output = 'First Quiz Score: ' . $first_quiz_data['score'] . ' - First Quiz Percentage: ' . $first_quiz_data['percentage'];
    $output .= '<br>';
    $output .= 'Final Quiz Score: ' . $final_quiz_data['score'] . ' - Final Quiz Percentage: ' . $final_quiz_data['percentage'];

    return $output;
}

add_shortcode('quiz_analytics_test', 'test_quiz_analytics_shortcode');
