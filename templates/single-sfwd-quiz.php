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

<body class="<?php echo esc_attr( $body_class ); ?>">

  <div class="custom-quiz-layout">
    <div id="quiz-card">

      <?php
      // Título del quiz
      the_title('<h1>', '</h1>');

      // Fecha actual en español
      setlocale(LC_TIME, 'es_ES.UTF-8');
      echo strftime('%e de %B de %Y');
      ?>

      <?php
      // (Opcional) detectar si es Prueba Inicial o Final
      $quiz_type = 'final';
      $terms     = wp_get_post_terms( $quiz_id, 'ld_quiz_category' );
      foreach ( $terms as $term ) {
          if ( strtolower( $term->name ) === 'primera' ) {
              $quiz_type = 'first';
              break;
          }
      }
      ?>

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

