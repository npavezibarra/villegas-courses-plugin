<?php
/**
 * Custom Banner for Courses / Products
 * File: banner-course.php
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}

if ( ! defined( 'WP_DEBUG_LOG' ) ) {
    define( 'WP_DEBUG_LOG', true );
}

// Asegurarnos de tener un $post disponible (en el Loop o global)
global $post;
if ( ! $post ) {
    return; // Si no hay $post, no hacemos nada
}

$post_id   = $post->ID;
$course_id = absint( $post_id );

// Obtener URL de imagen destacada o un placeholder
$thumbnail_url = has_post_thumbnail( $post_id ) 
    ? get_the_post_thumbnail_url( $post_id, 'full' ) 
    : 'https://via.placeholder.com/1920x1080';

// Obtener título y autor
$title = get_the_title( $post_id );

$author_id   = get_post_field( 'post_author', $post_id );
$first_name  = get_the_author_meta( 'first_name', $author_id );
$last_name   = get_the_author_meta( 'last_name', $author_id );
$author_name = trim( esc_html( $first_name . ' ' . $last_name ) );

// Generar el banner
?>
<div id="body-content" style="position: relative; background-image: url('<?php echo esc_url( $thumbnail_url ); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: 40px 20px; margin-bottom: 20px; z-index: 1;">

    <!-- Gradiente negro en la parte inferior -->
    <div class="banner-gradient-overlay" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 65%; background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent); pointer-events: none;"></div>

    <div id="datos-generales-curso" style="position: relative; z-index: 1; color: white;">
        <h1><?php echo esc_html( $title ); ?></h1>
        <?php
        $matched_order = null;
        $user          = wp_get_current_user();
        $user_name     = esc_html( $user->display_name );

        if ( function_exists( 'wc_get_orders' ) && is_user_logged_in() ) {
            $current_user_id = get_current_user_id();

            if ( $current_user_id ) {
                $orders = wc_get_orders(
                    array(
                        'customer_id' => $current_user_id,
                        'status'      => array( 'on-hold' ),
                        'limit'       => -1,
                    )
                );

                error_log( 'DEBUG: Checking on-hold orders for user ' . $current_user_id . ' and course ' . $post_id );

                if ( ! empty( $orders ) ) {
                    foreach ( $orders as $order ) {
                        foreach ( $order->get_items() as $item ) {
                            $product = $item->get_product();

                            if ( ! $product ) {
                                continue;
                            }

                            $product_ids = array_filter(
                                array_map(
                                    'absint',
                                    array( $product->get_id(), $product->get_parent_id() )
                                )
                            );

                            foreach ( $product_ids as $product_id_candidate ) {
                                $candidate_course_ids = array();

                                foreach ( array( '_related_course', '_linked_woocommerce_product' ) as $meta_key ) {
                                    $meta_value = get_post_meta( $product_id_candidate, $meta_key, true );

                                    if ( empty( $meta_value ) ) {
                                        continue;
                                    }

                                    if ( is_serialized( $meta_value ) ) {
                                        $meta_value = maybe_unserialize( $meta_value );
                                    }

                                    $meta_ids              = array_map( 'absint', (array) $meta_value );
                                    $candidate_course_ids = array_merge( $candidate_course_ids, $meta_ids );
                                }

                                $candidate_course_ids = array_filter( $candidate_course_ids );

                                if ( in_array( $course_id, $candidate_course_ids, true ) && 'on-hold' === $order->get_status() ) {
                                    $matched_order = $order;

                                    error_log( 'DEBUG: Found on-hold order #' . $order->get_id() . ' for course ' . $post_id );
                                    break 2;
                                }
                            }
                        }

                        if ( $matched_order ) {
                            break;
                        }
                    }
                }
            }
        }

        if ( $matched_order && is_a( $matched_order, 'WC_Order' ) ) {
            $amount_value = $matched_order->get_total();
            $amount       = function_exists( 'wc_price' ) ? wc_price( $amount_value ) : esc_html( number_format_i18n( (float) $amount_value, 2 ) );
            $order_id     = (int) $matched_order->get_id();

            if ( ! function_exists( 'did_action' ) || ! did_action( 'villegas_rendered_payment_modal' ) ) {
                if ( function_exists( 'do_action' ) ) {
                    do_action( 'villegas_rendered_payment_modal' );
                }

                add_action(
                    'wp_footer',
                    function () use ( $user_name, $order_id, $amount ) {
                        $greeting_name = $user_name ? $user_name : esc_html__( 'estudiante', 'villegas-courses' );
                        ?>
        <div id="payment-overlay" style="
    position:fixed; inset:0; display:flex; justify-content:center; align-items:center;
    background: rgba(0, 0, 0, 0.3); z-index:999999;">
  <div style="
      background:#fff; color:#222; border-radius:14px; padding:40px 36px;
      width:min(500px,92vw); text-align:center; box-shadow:0 20px 40px rgba(0,0,0,.3);
      line-height:1.6;">

    <h2 style="margin-bottom:12px;">Hola <?php echo esc_html( $greeting_name ); ?> 👋</h2>
    <p style="font-size:16px;margin:0 auto 18px;max-width:420px;">
      Tu compra está en proceso y estamos esperando la confirmación de tu transferencia bancaria
      para la orden <strong>#<?php echo esc_html( $order_id ); ?></strong>.
    </p>

    <p style="font-size:16px;margin:0 auto 22px;max-width:420px;">
      Por favor deposita <strong><?php echo wp_kses_post( $amount ); ?></strong> en la siguiente cuenta:
    </p>

    <!-- Datos bancarios en tabla (sin bordes) -->
    <table style="width:100%; border-collapse:collapse; margin: 20px auto 25px; max-width:380px;">
      <tbody style="text-align:left;">
        <tr>
          <td style="padding:6px 4px;">🏦 <strong>Villegas y Compañía SpA</strong></td>
          <td style="text-align:right;">
            <button class="copy-btn" data-copy="Villegas y Compañía SpA" style="border:none;background:none;cursor:pointer;">📋</button>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 4px;">RUT: 77.593.240-6</td>
          <td style="text-align:right;">
            <button class="copy-btn" data-copy="77593240-6" style="border:none;background:none;cursor:pointer;">📋</button>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 4px;">Banco Itaú</td>
          <td style="text-align:right;">
            <button class="copy-btn" data-copy="Banco Itaú" style="border:none;background:none;cursor:pointer;">📋</button>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 4px;">Cuenta Corriente: 0224532529</td>
          <td style="text-align:right;">
            <button class="copy-btn" data-copy="0224532529" style="border:none;background:none;cursor:pointer;">📋</button>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 4px;">Monto: <?php echo wp_kses_post( $amount ); ?></td>
          <td style="text-align:right;">
            <button class="copy-btn" data-copy="<?php echo esc_attr( wp_strip_all_tags( $amount ) ); ?>" style="border:none;background:none;cursor:pointer;">📋</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Texto final centrado y estilizado -->
    <p style="font-size:15px; margin: 0 auto 10px; max-width:420px; text-align:center;">
      Envía tu <strong>comprobante de transferencia bancaria</strong><br>
      (indicando tu nombre y número de orden) a:
    </p>

    <p style="font-size:17px; font-weight:700; color:#000; margin: 0 auto 12px; max-width:420px; text-align:center;">
      villeguistas@gmail.com
    </p>

    <p style="font-size:15px; margin: 0 auto; max-width:420px; text-align:center;">
      Una vez confirmado el pago, tendrás acceso completo al contenido del curso.
    </p>
  </div>
</div>

<script>
// Copy buttons only — overlay cannot be closed.
(function(){
  var overlay = document.getElementById('payment-overlay');
  if(!overlay){return;}

  document.body.style.overflow = 'hidden';

  overlay.querySelectorAll('.copy-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (!navigator.clipboard || !navigator.clipboard.writeText) {
        return;
      }

      navigator.clipboard.writeText(btn.dataset.copy || '');
      btn.textContent = '✅';
      setTimeout(function(){ btn.textContent = '📋'; }, 1200);
    });
  });

  document.addEventListener('click', function(e){
    if(!e.target.closest('#payment-overlay')){
      e.stopPropagation();
    }
  }, true);

  document.addEventListener('keydown', function(e){
    e.stopPropagation();
    e.preventDefault();
  });
})();
</script>
        <?php
                    }
                );
            }
        } else {
            error_log( 'DEBUG: No on-hold orders found for course ' . $post_id );
        }
        ?>
        <?php if ( ! empty( $author_name ) ) : ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <?php
        $user_photo_url = get_user_meta($author_id, 'profile_picture', true);

        if ($user_photo_url) {
            echo '<img src="' . esc_url($user_photo_url) . '" alt="Foto del profesor" class="foto-profesor" style="width:35px;height:35px;border-radius:50%;">';
        } else {
            // Si no hay foto personalizada, usar inicial
            $first_name = get_the_author_meta('first_name', $author_id);
            echo '<span class="user-initial" style="width:30px;height:30px;border-radius:50%;background:#ccc;color:#fff;display:flex;align-items:center;justify-content:center;">' . esc_html(strtoupper(substr($first_name, 0, 1))) . '</span>';
        }

        $author_id   = get_post_field('post_author', get_the_ID());
        $author_data = get_user_by('ID', $author_id);
        $author_slug = $author_data->user_nicename;

        $author_url   = home_url("/autor/{$author_slug}/");
        $display_name = $author_data->display_name;
        ?>
        <h4 style="margin: 0;">
            Profesor 
            <a href="<?php echo esc_url($author_url); ?>" style="color: inherit; text-decoration: none;">
                <?php echo esc_html($display_name); ?>
            </a>
        </h4>
    </div>
<?php endif; ?>

    </div>
</div>

