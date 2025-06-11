<?php
/**
 * Custom Banner for Courses / Products
 * File: banner-course.php
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Asegurarnos de tener un $post disponible (en el Loop o global)
global $post;
if ( ! $post ) {
    return; // Si no hay $post, no hacemos nada
}

$post_id = $post->ID;

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

