<?php
/**
 * Plantilla sobrescrita de LearnDash: single-sfwd-quiz.php
 * Ubicación original: wp-content/plugins/sfwd-lms/templates/single-sfwd-quiz.php
 *
 * - Usamos [ld_quiz] para que LearnDash inyecte:
 *     1) <div class="ld-tabs-content">…texto introductorio…</div>
 *     2) <div class="wpProQuiz_text">…botón Iniciar Evaluación…</div>
 *     3) Las preguntas del quiz dentro de la estructura de pestañas (ld-tabs)
 *
 * - En JavaScript:
 *     • Al hacer clic en el botón “Iniciar Evaluación” (input[name="startQuiz"]),
 *       ocultamos con .slideUp() el <div class="ld-tabs-content">.
 *     • Reducimos el tamaño del <h1> dentro de #quiz-card a 20px.
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Incluir header de tu tema o plantilla
include plugin_dir_path( __FILE__ ) . 'template-parts/header.php';
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');

global $post;
$quiz_id = $post->ID;

// (Opcional) cargar imagen de fondo si existe el meta "_quiz_style_image"
$image_id  = get_post_meta( $quiz_id, '_quiz_style_image', true );
$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
$body_class = 'quiz-style-' . $quiz_id;

?>

<?php if ( $image_url ): ?>
<style>
body.<?php echo esc_attr( $body_class ); ?> {
    background-image: url('<?php echo esc_url( $image_url ); ?>');
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: center center;
}
</style>
<?php endif; ?>
<?php
$quiz_description_raw = get_post_field('post_content', $quiz_id);
$quiz_description = $quiz_description_raw ? apply_filters('the_content', do_blocks($quiz_description_raw)) : '';

$quiz_description_json = json_encode($quiz_description, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

?>

<body class="<?php echo esc_attr( $body_class ); ?>">

  <div class="custom-quiz-layout">
    <div id="quiz-card">
      <?php
      /**
       * Codex Two-Line Quiz Header: muestra el tipo de evaluación, el curso y la fecha.
       */

      $quiz_id = (int) $quiz_id;
      $log_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

      if ( $log_enabled ) {
          error_log( "CODEX QUIZ HEADER: Rendering quiz_id={$quiz_id}" );
      }

      $course_id = 0;

      if ( function_exists( 'learndash_get_course_id' ) ) {
          $course_id = (int) learndash_get_course_id( $quiz_id );
          if ( $log_enabled ) {
              error_log( "CODEX QUIZ HEADER: learndash_get_course_id returned {$course_id}" );
          }
      }

      if ( ! $course_id ) {
          global $wpdb;
          $fallback_course_id = $wpdb->get_var(
              $wpdb->prepare(
                  "
                  SELECT post_id
                  FROM {$wpdb->postmeta}
                  WHERE meta_key IN ('_first_quiz_id', '_final_quiz_id')
                    AND meta_value = %d
                  LIMIT 1
                  ",
                  $quiz_id
              )
          );

          if ( $fallback_course_id ) {
              $course_id = (int) $fallback_course_id;
              if ( $log_enabled ) {
                  error_log( "CODEX QUIZ HEADER: fallback query found course_id={$course_id}" );
              }
          }
      }

      if ( ! $course_id && function_exists( 'learndash_get_courses_for_step' ) ) {
          $courses = learndash_get_courses_for_step( $quiz_id, true );
          if ( is_array( $courses ) && ! empty( $courses ) ) {
              $course_id = (int) array_key_first( $courses );
              if ( $log_enabled ) {
                  error_log( "CODEX QUIZ HEADER: learndash_get_courses_for_step fallback returned {$course_id}" );
              }
          }
      }

      $label = '';

      if ( $course_id ) {
          $first_quiz_id = (int) get_post_meta( $course_id, '_first_quiz_id', true );
          $final_quiz_id = (int) get_post_meta( $course_id, '_final_quiz_id', true );

          if ( $log_enabled ) {
              error_log( "CODEX QUIZ HEADER: course {$course_id} meta -> first={$first_quiz_id}, final={$final_quiz_id}" );
          }

          if ( $quiz_id === $first_quiz_id ) {
              $label = 'Evaluación Inicial';
              if ( $log_enabled ) {
                  error_log( 'CODEX QUIZ HEADER: matched as FIRST quiz' );
              }
          } elseif ( $quiz_id === $final_quiz_id ) {
              $label = 'Evaluación Final';
              if ( $log_enabled ) {
                  error_log( 'CODEX QUIZ HEADER: matched as FINAL quiz' );
              }
          } elseif ( $log_enabled ) {
              error_log( 'CODEX QUIZ HEADER: no match found for this quiz_id' );
          }
      } elseif ( $log_enabled ) {
          error_log( 'CODEX QUIZ HEADER: course_id not found; cannot compare IDs' );
      }

      $course_name = $course_id ? get_the_title( $course_id ) : '';
      $quiz_date   = get_the_date( 'j \d\e F \d\e Y', $quiz_id );
      ?>
      <div class="quiz-page-header" style="display:none;">
        <?php if ( $label && $course_name ) : ?>
          <h3><?php echo esc_html( $label ); ?></h3>
          <h2><?php echo esc_html( $course_name ); ?></h2>
        <?php else : ?>
          <h2><?php echo esc_html( get_the_title( $quiz_id ) ); ?></h2>
        <?php endif; ?>
        <p class="quiz-date"><?php echo esc_html( $quiz_date ); ?></p>
      </div>

      <!-- ─────────────────────────────────────────────────────────────────── -->
      <!-- Aquí LearnDash inyecta TODO el contenido del quiz dentro de <div class="ld-tabs">: -->
      <!--    1) <div class="ld-tabs-content">…texto introductorio…</div>             -->
      <!--    2) <div class="wpProQuiz_text">…botón Iniciar Evaluación…</div>         -->
      <!--    3) Bloques de preguntas/páginas posteriores                            -->
      <!-- ─────────────────────────────────────────────────────────────────── -->
      <div class="ld-tabs">
        <?php
          // El shortcode [ld_quiz] genera el quiz completo, incluido el botón “Iniciar Evaluación”
          echo do_shortcode( '[ld_quiz quiz_id="' . esc_attr( $quiz_id ) . '"]' );
        ?>
      </div>

    </div>
  </div>


</body>

<!-- FOOTER -->
<?php
echo do_blocks('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');
wp_footer();
?>

<script>
jQuery(function($){
    // Al hacer clic en el botón “Iniciar Evaluación” (input[name="startQuiz"]), hacemos dos cosas:
    // 1) slideUp() sobre el <div class="ld-tabs-content"> para ocultar la descripción.
    // 2) Reducimos el tamaño del <h1> dentro de #quiz-card a 20px.
    $(document).on('click', '.wpProQuiz_button[name="startQuiz"]', function() {
        // Ocultamos la descripción:
        $('.ld-tabs-content').slideUp();
        // Reducimos el tamaño del título:
        $('#quiz-card h1').css('font-size', '20px');
    });
});

// Menú responsive (sin cambios)
document.addEventListener('DOMContentLoaded', function () {
    const openBtn  = document.querySelector('.wp-block-navigation__responsive-container-open');
    const closeBtn = document.querySelector('.wp-block-navigation__responsive-container-close');
    const container = document.querySelector('.wp-block-navigation__responsive-container');
    if ( openBtn && container ) {
        openBtn.addEventListener('click', function() {
            container.classList.add('is-menu-open');
        });
    }
    if ( closeBtn && container ) {
        closeBtn.addEventListener('click', function() {
            container.classList.remove('is-menu-open');
        });
    }
});
</script>

<script>
jQuery(document).ready(function($) {
    $('.reiniciar-quiz-btn').on('click', function() {
        const quiz_id = $(this).data('quiz-id');
        const msgDiv = $('#reiniciar-quiz-msg');

        $.post({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            data: {
                action: 'reiniciar_quiz',
                quiz_id: quiz_id
            },
            success: function(response) {
                msgDiv.html(response.data).css('color', response.success ? 'green' : 'red');
                if (response.success) location.reload();
            },
            error: function() {
                msgDiv.html('Error al procesar la solicitud.').css('color', 'red');
            }
        });
    });
});
</script>

