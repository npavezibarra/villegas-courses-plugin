<?php
// Asegúrate de que este archivo no sea accedido directamente
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user_id = get_current_user_id();

// Verificar si el usuario está logueado
if ( !is_user_logged_in() || !$user_id ) {
    echo '<p>Debes estar conectado para ver tus cursos.</p>';
    return;
}

// Obtener los cursos del usuario
$user_courses = ld_get_mycourses( $user_id );

// Si no hay cursos inscritos
if ( empty( $user_courses ) ) {
    echo '<div class="no-courses">';
    echo '<p>No estás inscrito en ningún curso, visita el catálogo de cursos y una vez que te inscribas en uno podrás verlos en esta sección.</p>';
    echo '<a href="' . esc_url( home_url( '/cursos' ) ) . '" class="button ver-cursos" style="display: inline-block; font-size: 12px; padding: 10px 20px; background-color: black; color: #fff; text-decoration: none; border-radius: 5px;">VER CURSOS</a>';
    echo '</div>';
    return;
}

// Mostrar los cursos del usuario
echo '<div class="cursos-usuario">';
echo '<h3 style="color: black;">Mis Cursos</h3>';
echo '<p style="margin-bottom: 0px;">Estos son los cursos que estás cursando.</p>';

$user_id = get_current_user_id();
$puntaje_privado = get_user_meta($user_id, 'puntaje_privado', true);
$is_checked = ($puntaje_privado === '1' || $puntaje_privado === 1) ? 'checked' : '';
?>
<div class="quiz-private-toggle-my-account">
    <label style="font-size: 12px; font-weight: 500;">
        <input type="checkbox" id="puntaje_privado_checkbox" data-user-id="<?php echo esc_attr($user_id); ?>" <?php echo $is_checked; ?>>
        No mostrar mi puntaje en rankings públicos
    </label>
</div>

<?php
foreach ( $user_courses as $course_id ) {
    $course_title = get_the_title( $course_id );
    $course_link = get_permalink( $course_id );
    $course_image = get_the_post_thumbnail( $course_id, 'medium' );

    $total_lessons = count(learndash_get_course_steps($course_id));
    $completed_lessons = learndash_course_get_completed_steps_legacy( $user_id, $course_id );

    $percentage_complete = ($total_lessons > 0) ? min(100, ($completed_lessons / $total_lessons) * 100) : 0;

    echo '<div class="curso-item" style="display: flex; align-items: center; margin-bottom: 20px;">';

    // Imagen del curso
    if ( $course_image ) {
        echo '<div class="curso-imagen" style="margin-right: 20px;">' . $course_image . '</div>';
    } else {
        echo '<div class="curso-imagen" style="width: 150px; height: 150px; background-color: #add8e6; margin-right: 20px;"></div>';
    }

    // Info del curso
    echo '<div class="curso-info" style="flex-grow: 1;">';
    echo '<a href="' . esc_url( $course_link ) . '">';
    echo '<h4 style="margin: 0 0 10px;">' . esc_html( $course_title ) . '</h4>';
    echo '</a>';

    echo '<div class="card__progress">';
    echo '<p>' . esc_html( round($percentage_complete) ) . '% completado</p>';
    villegas_show_resultados_button($course_id, $user_id);
    echo '</div>';

    echo '</div>'; // .curso-info
    echo '</div>'; // .curso-item

}

echo '</div>'; // .cursos-usuario
?>

<div id="bloque-resultados" class="">
    <h3>Resultados del Curso</h3>
    <p>Aquí aparecerán los datos comparativos...</p>
    <button id="cerrar-resultados">Cerrar</button>
</div>

<!-- Fondo oscuro -->
<div id="fondo-modal" class="">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('bloque-resultados');
    const overlay = document.getElementById('fondo-modal');

    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('ver-resultados-btn')) {
            e.preventDefault();
            const cursoId = e.target.dataset.courseId;

            fetch(ajax_object.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mostrar_resultados_curso&course_id=' + encodeURIComponent(cursoId)
            })
            .then(response => response.text())
            .then(html => {
                modal.innerHTML = `
                    <button id="cerrar-resultados" style="position: absolute; top: 10px; right: 10px; background-color: #ccc; border: none; border-radius: 50%; width: 32px; height: 32px; font-weight: bold; font-size: 16px; cursor: pointer; line-height: 30px;">×</button>
                    ` + html;
                    modal.classList.add('visible');
                    overlay.classList.add('visible');

                document.getElementById('cerrar-resultados')?.addEventListener('click', () => {
                    modal.classList.remove('visible');
                    overlay.classList.remove('visible');
                });
            });
        }
    });
});

</script>

