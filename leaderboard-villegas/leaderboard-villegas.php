<?php
// Registrar el shortcode del leaderboard
function leaderboard_villegas_shortcode($atts) {
    // Atributos predeterminados para el shortcode
    $atts = shortcode_atts(
        array(
            'quiz_id' => 0,  // Por defecto, 0, cambia al ID del quiz que deseas
        ),
        $atts,
        'leaderboard_villegas'
    );

    // Verificar si el ID del quiz es válido
    if (!$atts['quiz_id']) {
        return '<p>Por favor, proporciona un ID de quiz válido.</p>';
    }

    // Obtener los resultados del quiz para el leaderboard
    global $wpdb;

    $quiz_id = $atts['quiz_id'];

    // Consultar la tabla de actividad de usuarios para obtener los datos del quiz, incluyendo el score y porcentaje
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT ua.user_id, ua.post_id, ua.activity_started, ua.activity_completed, 
                am.activity_meta_value AS score, am_percentage.activity_meta_value AS percentage
        FROM {$wpdb->prefix}learndash_user_activity ua
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am ON ua.activity_id = am.activity_id AND am.activity_meta_key = 'score'
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am_percentage ON ua.activity_id = am_percentage.activity_id AND am_percentage.activity_meta_key = 'percentage'
        WHERE ua.post_id = %d 
        AND ua.activity_type = 'quiz' 
        ORDER BY ua.user_id, ua.activity_completed DESC",
        $quiz_id
    ));

    // Si no se encuentran resultados
    if (empty($results)) {
        return '<p>No se encontraron resultados para este quiz.</p>';
    }

    // Obtener el nombre del quiz
    $quiz_name = get_the_title($quiz_id);

    // Generar la tabla del leaderboard
    $output = '<table class="leaderboard-villegas">';
    $output .= '<thead><tr><th>Posición</th><th>Usuario</th><th>Quiz</th><th>Completado</th><th>Score</th><th>% Correcto</th></tr></thead>';
    $output .= '<tbody>';

    // Contador de posición
    $position = 1;

    // Mostrar los datos del leaderboard
    foreach ($results as $result) {
        // Obtener la información del usuario usando user_id
        $user = get_user_by('id', $result->user_id);
        $user_name = $user ? esc_html($user->display_name) : 'Usuario Desconocido';

        // Formatear las fechas
        $completed_date = date('Y-m-d', $result->activity_completed);  // Mostrar solo la fecha (sin hora)

        // Obtener el score y el porcentaje
        $score = isset($result->score) ? $result->score : 0;
        $percentage = isset($result->percentage) ? $result->percentage : 0;

        $output .= '<tr>';
        $output .= '<td>' . $position . '</td>';
        $output .= '<td>' . $user_name . '</td>';
        $output .= '<td>' . esc_html($quiz_name) . '</td>';
        $output .= '<td>' . $completed_date . '</td>';
        $output .= '<td>' . esc_html($score) . '</td>';
        $output .= '<td>' . esc_html($percentage) . '%</td>';
        $output .= '</tr>';

        $position++;
    }

    $output .= '</tbody></table>';

    // Retornar el HTML del leaderboard
    return $output;
}
add_shortcode('leaderboard_villegas', 'leaderboard_villegas_shortcode');

// Opcional: Cargar estilos y scripts
function leaderboard_villegas_enqueue_scripts() {
    wp_enqueue_style('leaderboard-villegas', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('leaderboard-villegas', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'leaderboard_villegas_enqueue_scripts');
