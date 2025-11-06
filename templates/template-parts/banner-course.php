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
                        $order_id     = $order->get_id();
                        $order_status = $order->get_status();

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
                                if ( ! empty( $related_course_meta ) && is_serialized( $related_course_meta ) ) {
                                    $related_course_meta = maybe_unserialize( $related_course_meta );
                                }

                                $related_course_ids = array_map( 'absint', (array) $related_course_meta );
                                $linked_course_meta = get_post_meta( $product_id_candidate, '_linked_woocommerce_product', true );
                                $linked_course_id   = absint( $linked_course_meta );

                                $matches_related = in_array( $course_id, $related_course_ids, true );
                                $matches_linked  = ( $linked_course_id === $course_id );

                                if ( $matches_related || $matches_linked ) {
                                    $matched_order = array(
                                        'id'     => $order_id,
                                        'status' => $order_status,
                                    );

                                    error_log( 'DEBUG: Found on-hold order #' . $order_id . ' for course ' . $post_id );
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
            $order_id = esc_html( $matched_order['id'] );
            $status   = esc_html( $matched_order['status'] );
            echo '<div class="payment-warning" style="';
            echo 'background-color:#c00; color:#fff; font-weight:600;';
            echo 'padding:10px 20px; text-align:center; border-radius:4px;';
            echo 'margin-top:10px; letter-spacing:0.3px;">';
            echo "Order #{$order_id} — Status: {$status}<br>";
            echo 'We are still waiting for your bank transfer confirmation. ';
            echo 'Please email your payment receipt including the order number and your name to ';
            echo '<strong>villeguistas@gmail.com</strong>. Once confirmed, you’ll gain full access to the course.';
            echo '</div>';
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

