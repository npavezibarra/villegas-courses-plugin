<?php
/**
 * Author archive template.
 *
 * Provides the base HTML structure for the author landing page and exposes
 * useful WordPress template tags so that the theme can render dynamic data.
 */

get_header();

$author        = get_queried_object();
$author_id     = $author instanceof WP_User ? $author->ID : (int) get_query_var( 'author' );
$author_id     = $author_id ?: get_the_author_meta( 'ID' );
$display_name  = get_the_author_meta( 'display_name', $author_id );
$tagline       = get_user_meta( $author_id, 'titulo_personal', true );
$acf_bio       = function_exists( 'get_field' ) ? get_field( 'author_bio', 'user_' . $author_id ) : '';
$bio           = $acf_bio ?: get_the_author_meta( 'description', $author_id );
$location      = get_user_meta( $author_id, 'author_location', true );
$email         = get_the_author_meta( 'user_email', $author_id );
$website       = get_the_author_meta( 'user_url', $author_id );
$facebook      = get_user_meta( $author_id, 'facebook_profile', true );
$instagram     = get_user_meta( $author_id, 'instagram_profile', true );
$linkedin      = get_user_meta( $author_id, 'linkedin_profile', true );
$profile_photo = get_user_meta( $author_id, 'profile_picture', true );
$acf_photo     = function_exists( 'get_field' ) ? get_field( 'author_photo', 'user_' . $author_id ) : '';
$has_contact   = $email || $website || $facebook || $instagram || $linkedin;
$course_post_type = post_type_exists( 'course' ) ? 'course' : ( post_type_exists( 'sfwd-courses' ) ? 'sfwd-courses' : 'course' );

$author_photo_html = '';
if ( is_array( $acf_photo ) && isset( $acf_photo['ID'] ) ) {
    $author_photo_html = wp_get_attachment_image( $acf_photo['ID'], 'medium', false, array( 'class' => 'author-avatar__img' ) );
} elseif ( is_numeric( $acf_photo ) ) {
    $author_photo_html = wp_get_attachment_image( (int) $acf_photo, 'medium', false, array( 'class' => 'author-avatar__img' ) );
} elseif ( is_string( $acf_photo ) && $acf_photo ) {
    $author_photo_html = sprintf( '<img class="author-avatar__img" src="%s" alt="%s" />', esc_url( $acf_photo ), esc_attr( $display_name ) );
}

if ( ! $author_photo_html && $profile_photo ) {
    $author_photo_html = sprintf( '<img class="author-avatar__img" src="%s" alt="%s" />', esc_url( $profile_photo ), esc_attr( $display_name ) );
}

if ( ! $author_photo_html ) {
    $author_photo_html = get_avatar( $author_id, 200, '', $display_name, array( 'class' => 'author-avatar__img' ) );
}

$can_edit_photo = is_user_logged_in() && (int) get_current_user_id() === (int) $author_id;
$upload_photo_url = '';

if ( $can_edit_photo ) {
    if ( function_exists( 'wc_get_endpoint_url' ) ) {
        $upload_photo_url = wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) );
    }

    if ( ! $upload_photo_url ) {
        $upload_photo_url = get_edit_profile_url( $author_id );
    }
}
?>

<main id="primary" class="site-main author-template">
    <section class="author-hero">
        <div class="author-hero__media">
            <div class="author-avatar">
                <?php echo $author_photo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <?php if ( $can_edit_photo && $upload_photo_url ) : ?>
                <a class="author-upload-photo" href="<?php echo esc_url( $upload_photo_url ); ?>">
                    <?php esc_html_e( 'Subir o actualizar foto', 'villegas-courses' ); ?>
                </a>
            <?php endif; ?>
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
            <?php if ( $has_contact ) : ?>
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
            <?php else : ?>
                <p class="author-contact__empty"><?php esc_html_e( 'El autor aún no ha añadido datos de contacto.', 'villegas-courses' ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $courses_per_page  = 5;
    $courses_query_args = array(
        'post_type'           => $course_post_type,
        'posts_per_page'      => $courses_per_page,
        'author'              => $author_id,
        'post_status'         => 'publish',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    );

    $courses_query = new WP_Query( $courses_query_args );

    $course_taxonomy = 'category';
    if ( taxonomy_exists( 'course_category' ) ) {
        $course_taxonomy = 'course_category';
    } elseif ( taxonomy_exists( 'ld_course_category' ) ) {
        $course_taxonomy = 'ld_course_category';
    }

    $courses_archive_url = get_post_type_archive_link( $course_post_type );
    if ( ! $courses_archive_url ) {
        $courses_archive_url = home_url( '/' );
    }
    $courses_archive_url = add_query_arg(
        array(
            'post_type' => $course_post_type,
            'author'    => $author_id,
        ),
        $courses_archive_url
    );

    $total_courses      = count_user_posts( $author_id, $course_post_type, true );
    $remaining_courses  = max( 0, (int) $total_courses - $courses_per_page );
    $courses_cta_label  = __( 'Ver todos', 'villegas-courses' );

    if ( $remaining_courses > 0 ) {
        /* translators: %d: Remaining courses beyond the first five. */
        $courses_cta_label = sprintf( __( 'Ver todos (+%d)', 'villegas-courses' ), $remaining_courses );
    }
    ?>

    <section class="author-grid">
        <header class="author-grid__header">
            <div>
                <h2><?php esc_html_e( 'Cursos del autor', 'villegas-courses' ); ?></h2>
                <p><?php esc_html_e( 'Explora los programas publicados por este especialista.', 'villegas-courses' ); ?></p>
            </div>
            <a class="author-grid__cta" href="<?php echo esc_url( $courses_archive_url ); ?>">
                <?php echo esc_html( $courses_cta_label ); ?>
            </a>
        </header>
        <div class="author-grid__items">
            <?php if ( $courses_query->have_posts() ) : ?>
                <?php
                while ( $courses_query->have_posts() ) :
                    $courses_query->the_post();
                    $course_terms = get_the_terms( get_the_ID(), $course_taxonomy );
                    $course_term  = is_array( $course_terms ) && ! is_wp_error( $course_terms ) ? array_shift( $course_terms ) : null;
                    ?>
                    <article <?php post_class( 'author-grid__item' ); ?>>
                        <a class="author-grid__link" href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <figure class="author-grid__thumbnail">
                                    <?php the_post_thumbnail( 'medium_large' ); ?>
                                </figure>
                            <?php else : ?>
                                <div class="author-grid__thumbnail author-grid__thumbnail--empty">
                                    <?php esc_html_e( 'Sin imagen', 'villegas-courses' ); ?>
                                </div>
                            <?php endif; ?>
                            <div class="author-grid__content">
                                <p class="author-grid__category">
                                    <?php
                                    echo $course_term
                                        ? esc_html( $course_term->name )
                                        : esc_html__( 'Sin categoría', 'villegas-courses' );
                                    ?>
                                </p>
                                <h3 class="author-grid__title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="author-grid__empty"><?php esc_html_e( 'Todavía no hay cursos publicados por este autor.', 'villegas-courses' ); ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php wp_reset_postdata(); ?>

    <?php
    $articles_per_page = 5;
    $articles_query    = new WP_Query(
        array(
            'post_type'           => 'post',
            'posts_per_page'      => $articles_per_page,
            'author'              => $author_id,
            'post_status'         => 'publish',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        )
    );

    $author_posts_url  = get_author_posts_url( $author_id );
    $total_articles    = count_user_posts( $author_id, 'post', true );
    $remaining_posts   = max( 0, (int) $total_articles - $articles_per_page );
    $view_all_articles = __( 'Ver todos', 'villegas-courses' );

    if ( $remaining_posts > 0 ) {
        /* translators: %d: Remaining number of posts beyond the first five. */
        $view_all_articles = sprintf( __( 'Ver todos (+%d)', 'villegas-courses' ), $remaining_posts );
    }
    ?>

    <section class="author-articles">
        <header class="author-articles__header">
            <div>
                <h2><?php esc_html_e( 'Columnas y artículos', 'villegas-courses' ); ?></h2>
                <p><?php esc_html_e( 'Últimas publicaciones del autor en el blog.', 'villegas-courses' ); ?></p>
            </div>
            <a class="author-articles__cta" href="<?php echo esc_url( $author_posts_url ); ?>">
                <?php echo esc_html( $view_all_articles ); ?>
            </a>
        </header>
        <div class="author-articles__list">
            <?php if ( $articles_query->have_posts() ) : ?>
                <?php
                while ( $articles_query->have_posts() ) :
                    $articles_query->the_post();
                    ?>
                    <article <?php post_class( 'author-articles__item' ); ?>>
                        <a class="author-articles__link" href="<?php the_permalink(); ?>">
                            <div class="author-articles__media">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'thumbnail' ); ?>
                                <?php else : ?>
                                    <div class="author-articles__placeholder">
                                        <?php esc_html_e( 'Sin imagen', 'villegas-courses' ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="author-articles__content">
                                <h3 class="author-articles__title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="author-articles__empty"><?php esc_html_e( 'Este autor aún no tiene artículos publicados.', 'villegas-courses' ); ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php wp_reset_postdata(); ?>
</main>

<?php get_footer(); ?>

