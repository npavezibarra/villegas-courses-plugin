<?php
/**
 * Author archive template.
 *
 * Provides the base HTML structure for the author landing page and exposes
 * useful WordPress template tags so that the theme can render dynamic data.
 */

get_header();

$author      = get_queried_object();
$author_id   = $author instanceof WP_User ? $author->ID : (int) get_query_var( 'author' );
$author_id   = $author_id ?: get_the_author_meta( 'ID' );
$display_name = get_the_author_meta( 'display_name', $author_id );
$tagline      = get_user_meta( $author_id, 'titulo_personal', true );
$acf_bio      = function_exists( 'get_field' ) ? get_field( 'author_bio', 'user_' . $author_id ) : '';
$bio          = $acf_bio ?: get_the_author_meta( 'description', $author_id );
$location     = get_user_meta( $author_id, 'author_location', true );
$email        = get_the_author_meta( 'user_email', $author_id );
$website      = get_the_author_meta( 'user_url', $author_id );
$facebook     = get_user_meta( $author_id, 'facebook_profile', true );
$instagram    = get_user_meta( $author_id, 'instagram_profile', true );
$linkedin     = get_user_meta( $author_id, 'linkedin_profile', true );
?>

<main id="primary" class="site-main author-template">
    <section class="author-hero">
        <div class="author-hero__media">
            <?php echo get_avatar( $author_id, 200, '', $display_name ); ?>
        </div>
        <div class="author-hero__content">
            <p class="author-eyebrow"><?php esc_html_e( 'Autor destacado', 'villegas-courses' ); ?></p>
            <h1 class="author-name"><?php echo esc_html( $display_name ); ?></h1>
            <?php if ( $tagline ) : ?>
                <p class="author-tagline"><?php echo esc_html( $tagline ); ?></p>
            <?php endif; ?>
            <?php if ( $location ) : ?>
                <p class="author-location"><?php echo esc_html( $location ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="author-bio">
        <div class="author-bio__column">
            <h2><?php esc_html_e( 'Sobre el autor', 'villegas-courses' ); ?></h2>
            <?php if ( $bio ) : ?>
                <div class="author-bio__content">
                    <?php echo wpautop( wp_kses_post( $bio ) ); ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e( 'El autor aún no ha compartido su biografía.', 'villegas-courses' ); ?></p>
            <?php endif; ?>
        </div>
        <div class="author-bio__column author-contact">
            <h3><?php esc_html_e( 'Contacto', 'villegas-courses' ); ?></h3>
            <ul class="author-contact__list">
                <?php if ( $email ) : ?>
                    <li><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
                <?php endif; ?>
                <?php if ( $website ) : ?>
                    <li><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website ); ?></a></li>
                <?php endif; ?>
                <?php if ( $facebook ) : ?>
                    <li><a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Facebook', 'villegas-courses' ); ?></a></li>
                <?php endif; ?>
                <?php if ( $instagram ) : ?>
                    <li><a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Instagram', 'villegas-courses' ); ?></a></li>
                <?php endif; ?>
                <?php if ( $linkedin ) : ?>
                    <li><a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LinkedIn', 'villegas-courses' ); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </section>

    <section class="author-grid">
        <header class="author-grid__header">
            <h2><?php esc_html_e( 'Cursos del autor', 'villegas-courses' ); ?></h2>
            <p><?php esc_html_e( 'Explora los programas publicados por este especialista.', 'villegas-courses' ); ?></p>
        </header>
        <div class="author-grid__items">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <article <?php post_class( 'author-grid__item' ); ?>>
                        <a class="author-grid__link" href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <figure class="author-grid__thumbnail">
                                    <?php the_post_thumbnail( 'medium_large' ); ?>
                                </figure>
                            <?php endif; ?>
                            <div class="author-grid__content">
                                <h3 class="author-grid__title"><?php the_title(); ?></h3>
                                <p class="author-grid__excerpt"><?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="author-grid__empty"><?php esc_html_e( 'Todavía no hay publicaciones para este autor.', 'villegas-courses' ); ?></p>
            <?php endif; ?>
        </div>

        <div class="author-pagination">
            <?php the_posts_pagination(); ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>

