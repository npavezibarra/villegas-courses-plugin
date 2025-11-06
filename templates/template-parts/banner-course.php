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
<div id="body-content" style="position: relative; background-image: url('<?php echo esc_url( $thumbnail_url ); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: 40px 20px; margin-bottom: 20px;z-index: -9999999;">

    <!-- Gradiente negro en la parte inferior -->
    <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 65%; background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent); pointer-events: none;"></div>

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
            $cookie_name  = 'vil_pay_dismiss_' . $order_id;

            if ( empty( $_COOKIE[ $cookie_name ] ) ) {
                if ( ! function_exists( 'did_action' ) || ! did_action( 'villegas_rendered_payment_modal' ) ) {
                    if ( function_exists( 'do_action' ) ) {
                        do_action( 'villegas_rendered_payment_modal' );
                    }

                    add_action(
                        'wp_footer',
                        function () use ( $user_name, $order_id, $amount, $cookie_name ) {
                            $greeting_name = $user_name ? $user_name : esc_html__( 'there', 'villegas-courses' );
                            ?>
        <div id="vil-pay-overlay" class="vil-pay-overlay" aria-hidden="false" role="dialog" aria-modal="true">
          <div class="vil-pay-modal" role="document">
            <button type="button" class="vil-pay-close" aria-label="<?php esc_attr_e( 'Close', 'villegas-courses' ); ?>">×</button>

            <h2 class="vil-pay-title">Hi <?php echo esc_html( $greeting_name ); ?> 👋</h2>
            <p class="vil-pay-intro">
              We’re still waiting for your bank transfer for order <strong>#<?php echo esc_html( $order_id ); ?></strong>.<br>
              Please deposit <strong><?php echo wp_kses_post( $amount ); ?></strong> using the following details:
            </p>

            <ul class="vil-pay-list">
              <li>
                <span>Villegas y Compañía SpA</span>
                <button type="button" class="vil-copy" data-copy="Villegas y Compañía SpA" aria-label="<?php esc_attr_e( 'Copy account name', 'villegas-courses' ); ?>">📋</button>
              </li>
              <li>
                <span>RUT: 77593240-6</span>
                <button type="button" class="vil-copy" data-copy="77593240-6" aria-label="<?php esc_attr_e( 'Copy RUT', 'villegas-courses' ); ?>">📋</button>
              </li>
              <li>
                <span>Banco Itaú</span>
                <button type="button" class="vil-copy" data-copy="Banco Itaú" aria-label="<?php esc_attr_e( 'Copy bank', 'villegas-courses' ); ?>">📋</button>
              </li>
              <li>
                <span>Cuenta Corriente: 0224532529</span>
                <button type="button" class="vil-copy" data-copy="0224532529" aria-label="<?php esc_attr_e( 'Copy account number', 'villegas-courses' ); ?>">📋</button>
              </li>
              <li>
                <span>Amount: <?php echo wp_kses_post( $amount ); ?></span>
                <button type="button" class="vil-copy" data-copy="<?php echo esc_attr( wp_strip_all_tags( $amount ) ); ?>" aria-label="<?php esc_attr_e( 'Copy amount', 'villegas-courses' ); ?>">📋</button>
              </li>
            </ul>

            <p class="vil-pay-note">
              Send your receipt (with name &amp; order number) to <strong>villeguistas@gmail.com</strong>.
            </p>

            <button type="button" class="vil-pay-okay"><?php esc_html_e( 'Got it', 'villegas-courses' ); ?></button>
          </div>
        </div>

        <style>
          .vil-pay-overlay{
            position:fixed; inset:0; background:rgba(0,0,0,.6);
            display:flex; align-items:center; justify-content:center;
            z-index:100000;
          }
          .vil-pay-modal{
            width:min(500px, 92vw); background:#fff; color:#222;
            border-radius:14px; padding:26px 22px;
            box-shadow:0 18px 40px rgba(0,0,0,.25);
            position:relative;
          }
          .vil-pay-close{
            position:absolute; top:10px; right:12px;
            background:none; border:none; font-size:22px; line-height:1;
            cursor:pointer;
          }
          .vil-pay-title{ margin:0 0 10px; font-size:22px; text-align:center; }
          .vil-pay-intro{ margin:0 0 14px; text-align:center; }
          .vil-pay-list{ list-style:none; padding:0; margin:10px 0 14px; }
          .vil-pay-list li{
            display:flex; align-items:center; justify-content:space-between;
            gap:10px; padding:10px 12px; border:1px solid #eee; border-radius:8px; margin-bottom:8px;
          }
          .vil-copy{
            background:none; border:none; cursor:pointer; font-size:16px;
          }
          .vil-copy:focus{ outline:2px solid #c00; outline-offset:2px; }
          .vil-pay-note{ margin:10px 0 0; text-align:center; }
          .vil-pay-okay{
            margin:14px auto 0; display:block;
            background:#c00; color:#fff; border:none; border-radius:8px;
            padding:10px 16px; cursor:pointer; font-weight:600;
          }
        </style>

        <script>
          (function(){
            var overlay = document.getElementById('vil-pay-overlay');
            if(!overlay){return;}

            var body = document.body;
            var prevOverflow = body ? body.style.overflow : '';
            var onEsc;
            if(body){ body.style.overflow = 'hidden'; }

            overlay.querySelectorAll('.vil-copy').forEach(function(btn){
              btn.addEventListener('click', function(){
                if (!navigator.clipboard || !navigator.clipboard.writeText) {
                  return;
                }

                navigator.clipboard.writeText(btn.dataset.copy || '').then(function(){
                  var old = btn.textContent;
                  btn.textContent = '✅';
                  setTimeout(function(){ btn.textContent = old; }, 1200);
                });
              });
            });

            function closeModal(){
              if(overlay){ overlay.remove(); }
              if(body){ body.style.overflow = prevOverflow || ''; }
              document.cookie = '<?php echo esc_js( $cookie_name ); ?>=1;path=/;max-age=7200';
              if(onEsc){ document.removeEventListener('keydown', onEsc); }
            }

            overlay.addEventListener('click', function(e){
              if(e.target === overlay){ closeModal(); }
            });

            var closeBtn = overlay.querySelector('.vil-pay-close');
            if(closeBtn){ closeBtn.addEventListener('click', closeModal); }

            var okBtn = overlay.querySelector('.vil-pay-okay');
            if(okBtn){ okBtn.addEventListener('click', closeModal); }

            onEsc = function(e){
              if(e.key === 'Escape'){
                closeModal();
              }
            };
            document.addEventListener('keydown', onEsc);

            var focusables = overlay.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
            if(focusables.length){ focusables[0].focus(); }
          })();
        </script>
        <?php
                        }
                    );
                }
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
        ?>
        <h4 style="margin: 0;">Profesor <?php echo esc_html($author_name); ?></h4>
    </div>
<?php endif; ?>

    </div>
</div>

