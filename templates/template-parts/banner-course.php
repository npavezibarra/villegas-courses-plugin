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
                                $related_course_meta = get_post_meta( $product_id_candidate, '_related_course', true );

                                if ( empty( $related_course_meta ) ) {
                                    continue;
                                }

                                if ( is_serialized( $related_course_meta ) ) {
                                    $related_course_meta = maybe_unserialize( $related_course_meta );
                                }

                                $related_course_ids = array_map( 'absint', (array) $related_course_meta );

                                if ( in_array( $course_id, $related_course_ids, true ) && 'on-hold' === $order->get_status() ) {
                                    $matched_order = array(
                                        'id'    => $order->get_id(),
                                        'total' => $order->get_total(),
                                    );

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

        if ( $matched_order ) {
            $amount_value = isset( $matched_order['total'] ) ? $matched_order['total'] : 0;
            $amount       = function_exists( 'wc_price' ) ? wc_price( $amount_value ) : esc_html( number_format_i18n( (float) $amount_value, 2 ) );
            $amount_plain = wp_strip_all_tags( $amount );
            $order_id     = esc_html( $matched_order['id'] );
            ?>
            <div id="payment-overlay" style="
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.7); display: flex; justify-content: center;
                align-items: center; z-index: 9999;">
              <div style="
                  background: white; color: #222; border-radius: 12px;
                  padding: 30px 25px; max-width: 500px; width: 90%;
                  text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,0.2); position: relative;">

                  <button id="close-overlay" style="
                      position: absolute; top: 10px; right: 10px;
                      background: transparent; border: none; font-size: 22px;
                      cursor: pointer;">×</button>

                  <h2 style="margin-bottom:10px;">Hi <?php echo $user_name ? $user_name : esc_html__( 'there', 'villegas-courses' ); ?> 👋</h2>
                  <p style="margin-bottom:15px;">
                    We’re still waiting for your bank transfer for order <strong>#<?php echo $order_id; ?></strong>.<br>
                    Please deposit <strong><?php echo wp_kses_post( $amount ); ?></strong> using the following details:
                  </p>

                  <div style="text-align:left; line-height:1.8; margin-top:10px;">
                    <div>🏦 Villegas y Compañía SpA <button class="copy-btn" data-copy="<?php echo esc_attr( 'Villegas y Compañía SpA' ); ?>">📋</button></div>
                    <div>RUT: 77593240-6 <button class="copy-btn" data-copy="77593240-6">📋</button></div>
                    <div>Banco Itaú <button class="copy-btn" data-copy="<?php echo esc_attr( 'Banco Itaú' ); ?>">📋</button></div>
                    <div>Cuenta Corriente: 0224532529 <button class="copy-btn" data-copy="0224532529">📋</button></div>
                    <div>Amount: <?php echo wp_kses_post( $amount ); ?> <button class="copy-btn" data-copy="<?php echo esc_attr( $amount_plain ); ?>">📋</button></div>
                  </div>

                  <p style="margin-top:15px;">
                    Please send your payment receipt including your name and order number to<br>
                    <strong>villeguistas@gmail.com</strong>
                  </p>
                  <button id="understood-btn" style="
                      background:#c00; color:#fff; border:none; border-radius:6px;
                      padding:10px 20px; margin-top:10px; cursor:pointer;">
                      <?php esc_html_e( 'Got it', 'villegas-courses' ); ?>
                  </button>
              </div>
            </div>

            <script>
            document.querySelectorAll('#payment-overlay .copy-btn').forEach(btn => {
              btn.addEventListener('click', () => {
                if (navigator?.clipboard?.writeText) {
                  navigator.clipboard.writeText(btn.dataset.copy).then(() => {
                    btn.textContent = '✅';
                    setTimeout(() => {
                      btn.textContent = '📋';
                    }, 1200);
                  });
                }
              });
            });
            document.getElementById('close-overlay')?.addEventListener('click', () => {
              document.getElementById('payment-overlay')?.remove();
            });
            document.getElementById('understood-btn')?.addEventListener('click', () => {
              document.getElementById('payment-overlay')?.remove();
            });
            </script>
            <?php
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

