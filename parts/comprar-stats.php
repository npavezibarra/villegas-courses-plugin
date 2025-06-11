<?php
ob_start();

// Function to get latest quiz percentage
function get_latest_quiz_percentage($user_id, $quiz_id) {
    $quiz_attempts = get_user_meta($user_id, '_sfwd-quizzes', true);
    $latest_attempt = null;

    if (!empty($quiz_attempts) && is_array($quiz_attempts)) {
        foreach ($quiz_attempts as $attempt) {
            if (isset($attempt['quiz']) && (int)$attempt['quiz'] === (int)$quiz_id) {
                if (is_null($latest_attempt) || $attempt['time'] > $latest_attempt['time']) {
                    $latest_attempt = $attempt;
                }
            }
        }
    }

    if ($latest_attempt) {
        $percentage_correct = round(($latest_attempt['score'] / $latest_attempt['count']) * 100, 2);
        return [true, $percentage_correct];
    }

    return [false, 0];
}

// Function to get login redirect URL
function get_login_redirect_url_for_course() {
    $args = array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    $pages = get_posts($args);
    $login_page_id = null;
    $course_id_found = null;

    foreach ($pages as $page_id) {
        $content = get_post_field('post_content', $page_id);
        if (has_shortcode($content, 'villegas_login_register')) {
            preg_match_all('/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $shortcode_match) {
                if ($shortcode_match[2] === 'villegas_login_register') {
                    $atts = shortcode_parse_atts($shortcode_match[3]);
                    if (isset($atts['course_id'])) {
                        $login_page_id = $page_id;
                        $course_id_found = $atts['course_id'];
                        break 2;
                    }
                }
            }
        }
    }

    if (!$login_page_id || !$course_id_found) {
        return wp_login_url();
    }

    global $post;
    $current_course_id = $post->ID;
    $login_url = get_permalink($login_page_id);
    return add_query_arg('fref', $current_course_id, $login_url);
}

// Main function with simplified interface
function mostrar_comprar_stats() {
    $user_id = get_current_user_id();
    $course_id = get_the_ID();
    $is_enrolled = sfwd_lms_has_access($course_id, $user_id);

    // Product ID lookup
    $product_id = get_post_meta($course_id, '_linked_woocommerce_product', true);
    if (empty($product_id)) {
        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_related_course',
                    'value' => $course_id,
                    'compare' => 'LIKE',
                ),
            ),
            'posts_per_page' => 1,
        );
        $products = get_posts($args);
        $product_id = !empty($products) ? $products[0]->ID : '';
    }

    // First quiz ID and URL
    $first_quiz_id = get_post_meta($course_id, '_first_quiz_id', true);
    $first_quiz_url = !empty($first_quiz_id) && ($quiz_post = get_post($first_quiz_id)) 
        ? home_url('/evaluaciones/' . $quiz_post->post_name . '/') 
        : '#';

    // Course progress
    $total_lessons = count(learndash_get_course_steps($course_id));
    $completed_lessons = learndash_course_get_completed_steps_legacy($user_id, $course_id);
    $percentage_complete = ($total_lessons > 0) ? min(100, ($completed_lessons / $total_lessons) * 100) : 0;

    // Common styles
    $widget_style = "display: flex; align-items: center; background-color: white; padding: 20px; border-radius: 0px; border: 1px solid #e2e2e2; width: 100%;";
    $progress_bar_style = "flex: 1; width: 50%; margin-right: 20px;";
    $bar_style = "background-color: #e0e0e0; height: 10px; border-radius: 5px; position: relative;";
    $labels_style = "display: flex; justify-content: space-between; font-size: 12px; color: #333;";
    $button_style = "background-color: %s; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; cursor: pointer;";

    // Render widget
    ?>
    <div class="progress-widget" style="<?php echo $widget_style; ?>">
        <div class="progress-bar-stats" style="<?php echo $progress_bar_style; ?>">
            <div style="<?php echo $bar_style; ?>">
                <div style="width: <?php echo esc_attr($is_enrolled || !is_user_logged_in() ? $percentage_complete : 0); ?>%; background-color: <?php echo $is_enrolled ? '#ff9800' : (!is_user_logged_in() ? '#4c8bf5' : '#ccc'); ?>; height: 100%; border-radius: 5px;"></div>
            </div>
            <div style="<?php echo $labels_style; ?>">
                <span><?php echo $is_enrolled ? esc_html(round($percentage_complete)) : '0'; ?>%</span>
                <span>100%</span>
            </div>
        </div>

        <?php if (!is_user_logged_in()): ?>
            <div class="buy-button" style="flex: 1; width: 50%; text-align: right;">
                <button style="<?php echo sprintf($button_style, '#4c8bf5'); ?> width: 100%;"
                        onclick="window.location.href='<?php echo esc_url(get_login_redirect_url_for_course()); ?>'">
                    Iniciar Sesión
                </button>
            </div>
        <?php elseif (!$is_enrolled): ?>
            <div class="action-buttons" style="flex: 1; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                <?php
                list($has_completed_quiz, $percentage_correct) = get_latest_quiz_percentage($user_id, $first_quiz_id);
                if ($has_completed_quiz): ?>
                    <div class="examen-inicial">
                        <strong><?php echo $percentage_correct; ?>%</strong>
                        <p id="primer-test-legend">Prueba Inicial</p>
                    </div>
                <?php else: ?>
                    <button onclick="window.location.href='<?php echo esc_url($first_quiz_url); ?>'"
                            class="button exam-inicial-btn"
                            style="<?php echo sprintf($button_style, '#2196f3'); ?> flex: 1; text-align: center;">
                        Prueba Inicial
                    </button>
                <?php endif; ?>
                <button onclick="window.location.href='<?php echo esc_url(get_permalink($product_id)); ?>'"
                        class="button buy-button"
                        style="<?php echo sprintf($button_style, '#4c8bf5'); ?> flex: 1; text-align: center;">
                    Comprar Curso
                </button>
            </div>
        <?php else: ?>
            <div class="test-buttons" style="flex: 1; text-align: right; display: flex; gap: 20px;">
                <div id="primer-test-score" class="quiz-score-display" style="width: 50%;">
                    <?php
                    list($has_completed_quiz, $percentage_correct) = get_latest_quiz_percentage($user_id, $first_quiz_id);
                    if ($has_completed_quiz): ?>
                        <div class="quiz-result" style="background-color: white; border: 1px solid #e2e2e2; text-align: center;">
                            <strong><?php echo $percentage_correct; ?>%</strong>
                            <p id="primer-test-legend">Prueba Inicial</p>
                        </div>
                    <?php else: ?>
                        <button onclick="window.location.href='<?php echo esc_url($first_quiz_url); ?>'"
                                style="<?php echo sprintf($button_style, '#2196f3'); ?> width: 100%; padding: 10px 0;">
                            Prueba Inicial
                        </button>
                    <?php endif; ?>
                </div>
                <div id="final-test-button" class="tooltip" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <?php
                    global $wpdb;
                    $final_quiz_id = end(learndash_course_get_steps_by_type($course_id, 'sfwd-quiz')) ?: 0;
                    $final_quiz_url = $final_quiz_id ? get_permalink($final_quiz_id) : '';

                    $latest_attempt_final = $wpdb->get_row($wpdb->prepare(
                        "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
                        WHERE user_id = %d AND post_id = %d AND activity_type = 'quiz' 
                        ORDER BY activity_completed DESC LIMIT 1",
                        $user_id, $final_quiz_id
                    ));

                    $final_quiz_score = 0;
                    $has_completed_final_quiz = false;
                    if ($latest_attempt_final) {
                        $final_quiz_score = $wpdb->get_var($wpdb->prepare(
                            "SELECT activity_meta_value FROM {$wpdb->prefix}learndash_user_activity_meta 
                            WHERE activity_id = %d AND activity_meta_key = 'percentage'",
                            $latest_attempt_final->activity_id
                        ));
                        $has_completed_final_quiz = ($final_quiz_score !== null && $final_quiz_score > 0);
                    }

                    if ((int)$percentage_complete >= 100 && !empty($final_quiz_url) && !$has_completed_final_quiz): ?>
                        <button onclick="window.location.href='<?php echo esc_url($final_quiz_url); ?>'"
                                style="<?php echo sprintf($button_style, '#4c8bf5'); ?> width: 100%; padding: 10px 0; font-size: 12px;">
                            Prueba Final
                        </button>
                    <?php elseif ($has_completed_final_quiz): ?>
                        <div class="quiz-result" style="background-color: white; border: 1px solid #e2e2e2; text-align: center; padding: 10px;">
                            <strong><?php echo esc_html($final_quiz_score); ?>%</strong>
                            <p style="font-size: 9px;">Prueba Final</p>
                        </div>
                    <?php else: ?>
                        <button id="final-evaluation-button"
                                style="border: 2px solid #2196f3 !important; padding: 8px !important; color: #2196f3 !important; border-radius: 5px; font-size: 14px; cursor: not-allowed; width: 100%; background: white;">
                            Prueba Final
                        </button>
                    <?php endif; ?>
                    <span class="tooltiptext">Completa todas las lecciones de este curso para tomar la Prueba Final</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

if (headers_sent($file, $line)) {
    echo "Headers already sent in $file on line $line";
}
?>