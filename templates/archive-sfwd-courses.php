<!-- HEADER -->
<?php
include plugin_dir_path(__FILE__) . 'template-parts/header.php';
// Load the default Twenty Twenty-Four header template part
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
?>
<div id="courses-grid" class="content-area">
    <main id="main-course-grid" class="site-main">

        <header class="page-header">
            <h1 class="entry-title"><?php post_type_archive_title(); ?></h1>

            <!-- Div rojo agregado -->
            <div style="background-color: red; height: 50px; width: 100%; text-align: center; color: white; font-weight: bold;">
                <p>Nuevo contenido aquí</p>
            </div>
        </header><!-- .page-header -->

        <?php if ( have_posts() ) : ?>

            <div class="courses-container">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php 
                        global $wpdb;
                        $course_id = get_the_ID(); // Get course ID
                        $user_id = get_current_user_id(); // Get logged-in user ID

                        // Obtener el ID de la Primera Evaluación
                        $first_quiz_id = get_post_meta($course_id, '_first_quiz_id', true);

                        // Obtener el ID de la Evaluación Final (último quiz en el curso)
                        $final_quiz_id = 0;
                        $course_steps = learndash_course_get_steps_by_type($course_id, 'sfwd-quiz');
                        if (!empty($course_steps)) {
                            $final_quiz_id = end($course_steps); // Último quiz en el curso
                        }

                        // Valores por defecto
                        $first_quiz_score = 0;
                        $final_quiz_score = 0;

                        if ($user_id) {
                            // Obtener el último intento del usuario en la Primera Evaluación
                            if ($first_quiz_id) {
                                $latest_attempt = $wpdb->get_row($wpdb->prepare(
                                    "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
                                    WHERE user_id = %d 
                                    AND post_id = %d 
                                    AND activity_type = 'quiz' 
                                    ORDER BY activity_completed DESC 
                                    LIMIT 1",
                                    $user_id,
                                    $first_quiz_id
                                ));

                                if ($latest_attempt) {
                                    $first_quiz_score = $wpdb->get_var($wpdb->prepare(
                                        "SELECT activity_meta_value FROM {$wpdb->prefix}learndash_user_activity_meta 
                                        WHERE activity_id = %d 
                                        AND activity_meta_key = 'percentage'",
                                        $latest_attempt->activity_id
                                    ));
                                }
                            }

                            // Obtener el último intento del usuario en la Evaluación Final
                            if ($final_quiz_id) {
                                $latest_attempt_final = $wpdb->get_row($wpdb->prepare(
                                    "SELECT activity_id FROM {$wpdb->prefix}learndash_user_activity 
                                    WHERE user_id = %d 
                                    AND post_id = %d 
                                    AND activity_type = 'quiz' 
                                    ORDER BY activity_completed DESC 
                                    LIMIT 1",
                                    $user_id,
                                    $final_quiz_id
                                ));

                                if ($latest_attempt_final) {
                                    $final_quiz_score = $wpdb->get_var($wpdb->prepare(
                                        "SELECT activity_meta_value FROM {$wpdb->prefix}learndash_user_activity_meta 
                                        WHERE activity_id = %d 
                                        AND activity_meta_key = 'percentage'",
                                        $latest_attempt_final->activity_id
                                    ));
                                }
                            }
                        }

                        // Asegurar valores numéricos válidos
                        $first_quiz_score = ($first_quiz_score !== null) ? round($first_quiz_score) : 0;
                        $final_quiz_score = ($final_quiz_score !== null) ? round($final_quiz_score) : 0;
                    ?>

                    <article id="post-<?php echo $course_id; ?>" <?php post_class('course-item'); ?>>
                        <a href="<?php the_permalink(); ?>" class="course-thumbnail">
                            <?php if (has_post_thumbnail()) {
                                the_post_thumbnail('medium');
                            } ?>
                        </a>

                        <div id="bottom-course-card">
                            <header class="entry-header">
                                <h2 class="entry-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                            </header><!-- .entry-header -->

                            <div class="entry-content">
                                <?php the_excerpt(); ?>
                            </div><!-- .entry-content -->

                            <!-- Evaluaciones -->
                            <div class="course-post-evaluations">
                                <!-- Prueba Inicial -->
                                <div class="evaluation-row">
                                    <?php if (!is_user_logged_in()) : ?>
                                        <a class="evaluation-title" href="/mi-cuenta/?redirect_to=<?php echo urlencode(get_permalink($first_quiz_id)); ?>">Prueba Inicial</a>
                                    <?php else : ?>
                                        <a class="evaluation-title" href="<?php echo get_permalink($first_quiz_id); ?>">Prueba Inicial</a>
                                    <?php endif; ?>
                                    <div class="progress-bar" id="progress-first">
                                        <div class="progress" style="width: <?php echo $first_quiz_score; ?>%;"></div>
                                    </div>
                                    <span class="evaluation-percentage"><?php echo $first_quiz_score; ?>%</span>
                                </div>

                                <!-- Prueba Final -->
                                <div class="evaluation-row">
                                    <?php if (!is_user_logged_in()) : ?>
                                        <a class="evaluation-title" href="/mi-cuenta/?redirect_to=<?php echo urlencode(get_permalink($final_quiz_id)); ?>">Prueba Final</a>
                                    <?php elseif ($first_quiz_score === 0) : ?>
                                        <span class="evaluation-title" style="opacity: 0.5; cursor: not-allowed;">Prueba Final</span>
                                    <?php else : ?>
                                        <?php
                                            // Verificar si el usuario completó el curso
                                            $completed = function_exists('learndash_is_user_complete') 
                                                ? learndash_is_user_complete($user_id, $course_id) 
                                                : false;
                                            $final_link = $completed ? get_permalink($final_quiz_id) : get_permalink($course_id);
                                        ?>
                                        <a class="evaluation-title" href="<?php echo esc_url($final_link); ?>">Prueba Final</a>
                                    <?php endif; ?>
                                    <div class="progress-bar" id="progress-final">
                                        <div class="progress" style="width: <?php echo $final_quiz_score; ?>%;"></div>
                                    </div>
                                    <span class="evaluation-percentage"><?php echo $final_quiz_score; ?>%</span>
                                </div>
                            </div>
                            <footer class="entry-footer">
                                <a href="<?php the_permalink(); ?>" class="btn">Ver Curso</a>
                            </footer>
                        </div>
                    </article><!-- #post-## -->
                <?php endwhile; ?>
            </div>

            <?php the_posts_navigation(); ?>
        <?php else : ?>
            <p class="no-courses">No hay cursos disponibles en este momento.</p>
        <?php endif; ?>
    
    </main><!-- #main -->
</div><!-- #primary -->

<!--FOOTER -->
<?php 
// Load the default Twenty Twenty-Four footer template part
echo do_blocks('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');

// Add custom JavaScript for hamburger menu
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find all hamburger menu buttons
    const menuButtons = document.querySelectorAll('.wp-block-navigation__responsive-container-open');
    
    // Initialize click handlers for each button
    menuButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Find the target menu container
            const targetId = button.getAttribute('aria-controls');
            const menuContainer = targetId ? document.getElementById(targetId) : 
                                 document.querySelector('.wp-block-navigation__responsive-container');
            
            if (menuContainer) {
                // Toggle the is-menu-open class
                menuContainer.classList.add('is-menu-open');
                menuContainer.setAttribute('aria-hidden', 'false');
                
                // Find and setup the close button in the opened menu
                const closeButton = menuContainer.querySelector('.wp-block-navigation__responsive-container-close');
                if (closeButton) {
                    closeButton.addEventListener('click', function() {
                        menuContainer.classList.remove('is-menu-open');
                        menuContainer.setAttribute('aria-hidden', 'true');
                    });
                }
            }
        });
        
        // Add keyboard event handlers
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                button.click();
            }
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>