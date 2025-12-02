<?php
/**
 * Template Name: Author Profile Template
 * Description: Static monochrome author profile layout with dummy content.
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();
$author_first = get_the_author_meta('first_name', $author_id);
$author_last = get_the_author_meta('last_name', $author_id);
$author_name_raw = trim($author_first . ' ' . $author_last);
$author_name = $author_name_raw ? $author_name_raw : get_the_author_meta('display_name', $author_id);
$author_bio_raw = get_the_author_meta('description', $author_id);
$author_bio = trim(wp_strip_all_tags($author_bio_raw)) ? $author_bio_raw : __('Esta biografía es una representación ficticia utilizada únicamente como borrador visual. El objetivo es mostrar el flujo completo del perfil del autor manteniendo una estética monocromática.', 'villegas-course-plugin');
$author_title = trim((string) get_user_meta($author_id, 'user_title', true));
$author_avatar = (string) get_user_meta($author_id, 'profile_picture', true);

if (empty($author_avatar)) {
    $author_avatar = get_avatar_url($author_id, ['size' => 520]);
}

if (empty($author_avatar)) {
    $author_avatar = 'https://placehold.co/520x520/ededeb/1a1a1a?text=Retrato';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Autor | Fernando Villegas', 'villegas-course-plugin'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;700;800&family=Lexend:wght@300;400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --font-fraunces: 'Fraunces', serif;
            --font-lexend: 'Lexend', sans-serif;
            --bg-base: #f5f5f3;
            --surface: #ffffff;
            --border-soft: #d7d7d2;
            --border-strong: #b5b5af;
            --text-primary: #1d1d1b;
            --text-secondary: #4f4f4c;
            --text-muted: #868683;
            --pill-bg: #ececea;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-lexend);
            background: var(--bg-base);
            color: var(--text-primary);
            line-height: 1.6;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .author-profile-page {
            min-height: 100vh;
            padding: 48px min(6vw, 72px) 96px;
            width: 94%;
            margin: auto;
            max-width: 1420px;
        }

        .profile-section {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            padding: 40px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 36px;
            align-items: center;
        }

        .profile-media {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .profile-avatar {
            appearance: none;
            border: 1px solid var(--border-soft);
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            overflow: hidden;
            background: #f1f1ef;
            padding: 0;
            cursor: pointer;
            position: relative;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(12, 12, 12, 0.78);
            color: #fff;
            font-size: 0.9rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .profile-avatar:hover .avatar-overlay,
        .profile-avatar:focus-visible .avatar-overlay {
            opacity: 1;
        }

        .profile-avatar[style*="pointer-events: none;"] .avatar-overlay {
            display: none !important;
        }

        .profile-avatar[style*="pointer-events: none;"] {
            opacity: 1;
        }

        .upload-controls {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
        }

        .avatar-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }

        .avatar-modal.hidden {
            display: none;
        }

        .avatar-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
        }

        .cropper-area img {
            max-width: 100%;
        }

        /* Main Profile Name */
        .author-profile-page .profile-details h1 {
            margin: 0;
            font-family: var(--font-fraunces) !important;
            font-weight: 800 !important;
            font-size: 1.875rem !important;
            /* text-3xl */
            color: var(--text-primary);
        }

        @media (min-width: 640px) {
            .author-profile-page .profile-details h1 {
                font-size: 2.25rem !important;
                /* sm:text-4xl */
            }
        }

        /* Director Title */
        .author-title {
            color: var(--text-secondary);
            font-family: var(--font-lexend);
            font-weight: 500;
            font-size: 1.125rem;
            /* text-lg */
            margin-top: 0.25rem;
        }

        p.author-title {
            margin-top: 0px !important;
        }

        .profile-details p {
            margin-top: 16px;
            color: var(--text-secondary);
            max-width: 620px;
        }

        /* Main Bio Text */
        .author-bio {
            margin-top: 16px;
            color: var(--text-secondary);
            max-width: 620px;
            font-family: var(--font-lexend);
            font-weight: 300;
            font-size: 1rem;
            /* text-base */
        }

        .author-bio p {
            margin: 0 0 1em;
            font-family: var(--font-lexend);
            font-weight: 300;
            font-size: 1rem;
            /* text-base */
        }

        /* Stats/Meta Text */
        .meta-list {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px 24px;
            color: var(--text-muted);
        }

        .meta-list span {
            font-family: var(--font-lexend);
            font-weight: 500;
            font-size: 0.875rem;
            /* text-sm */
        }

        .upload-hint {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .content-grid {
            margin-top: 40px;
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 32px;
        }

        .section-card {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Section Headings */
        .section-header h2 {
            margin: 8px 0 12px;
            text-align: left;
            font-family: var(--font-fraunces);
            font-weight: 700;
            font-size: 1.5rem;
            /* text-2xl */
        }

        .section-description {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .course-card {
            padding: 0px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            border: none;
        }

        .course-meta {
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        /* Course Titles */
        .course-card h3 {
            margin: 0px;
            font-family: var(--font-lexend);
            font-weight: 400;
            font-size: 1.25rem;
            /* text-xl */
        }

        /* Course Descriptions */
        .course-card p {
            color: var(--text-secondary);
            flex: 1;
            margin-top: 0px;
            font-family: var(--font-lexend);
            font-weight: 300;
            font-size: 0.875rem;
            /* text-sm */
        }

        .course-title-link {
            text-decoration: none;
            color: inherit;
            font-family: inherit;
            font-size: 24px;
        }

        .course-title-link:hover {
            text-decoration: underline;
        }

        .section-footer {
            margin-top: 32px;
            display: flex;
            justify-content: flex-end;
        }

        .section-footer a {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--text-primary);
            border-bottom: 1px solid currentColor;
            padding-bottom: 4px;
            font-family: 'Lexend';
        }

        .columns-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 0px;
        }

        .column-item {
            display: flex;
            gap: 16px;
            align-items: start;
            padding: 0px;
            border: none;
        }

        .column-item img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 4px;
            background: #f0f0f0;
        }

        /* Column/Article Titles */
        .column-item h3 {
            margin: 0 !important;
            margin-top: 0px !important;
            font-family: var(--font-lexend);
            font-weight: 500;
            font-size: 18px;
            line-height: 1.4;
        }

        .column-item a {
            color: var(--text-primary);
            text-decoration: none;
            line-height: 9px !important;
            font-family: 'Lexend';
        }

        /* Meta Text (in columns) */
        .column-item p {
            color: var(--text-muted);
            margin-top: 6px;
            font-family: var(--font-lexend);
            font-weight: 400;
            font-size: 0.75rem;
            /* text-xs */
        }

        .books-section {
            margin-top: 48px;
            background: var(--surface);
            border: 1px solid var(--border-soft);
            padding: 36px 40px;
        }

        .books-section h2 {
            margin-bottom: 8px;
            text-align: left;
            font-family: var(--font-fraunces);
            font-weight: 700;
            font-size: 1.5rem;
            /* text-2xl */
        }

        .books-description {
            color: var(--text-secondary);
            margin-bottom: 28px;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
        }

        .book-item {
            padding: 0px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .book-item img {
            background: #f0f0ed;
        }

        .book-item h3 {
            font-family: var(--font-lexend) !important;
            font-weight: 400 !important;
            font-size: 1.25rem !important;
            margin: 0;
        }

        .book-item a {
            font-family: var(--font-lexend) !important;
        }

        .book-price {
            font-family: var(--font-lexend);
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0px;
        }

        @media (max-width: 1024px) {
            .profile-section {
                grid-template-columns: minmax(0, 1fr);
                text-align: left;
            }

            .profile-media {
                width: 190px;
                margin: auto;
            }

            .profile-details {
                margin: 0 auto;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .books-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .author-profile-page {
                padding: 32px 20px 72px;
            }

            .profile-section,
            .section-card,
            .books-section {
                padding: 24px;
            }

            .section-footer {
                justify-content: flex-start;
            }

            .column-item {
                align-items: flex-start;
            }

            .books-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php wp_head(); ?>
</head>

<body <?php body_class('author-profile-monochrome'); ?>>
    <?php
    // Incluir header de tu tema o plantilla
    include plugin_dir_path(__FILE__) . 'template-parts/header.php';
    echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
    ?>
    <div class="author-profile-page">
        <div id="avatar-cropper-modal" class="avatar-modal hidden">
            <div class="avatar-modal-content">
                <h3>Recorta tu foto</h3>

                <div class="cropper-area">
                    <img id="avatar-cropper-image" src="" alt="Imagen a recortar">
                </div>

                <div class="cropper-actions">
                    <button id="avatar-cropper-save">Guardar</button>
                    <button id="avatar-cropper-cancel">Cancelar</button>
                </div>
            </div>
        </div>
        <section class="profile-section">
            <div class="profile-media">
                <?php
                $current_user_id = get_current_user_id();
                $is_own_profile = ($current_user_id === $author_id);
                ?>

                <button type="button" class="profile-avatar" <?php if ($is_own_profile)
                    echo 'data-avatar-toggle'; ?>
                    style="<?php echo $is_own_profile ? '' : 'cursor: default; pointer-events: none;'; ?>">
                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>" />

                    <?php if ($is_own_profile): ?>
                        <span class="avatar-overlay">Subir foto</span>
                    <?php endif; ?>
                </button>

                <?php if ($is_own_profile): ?>
                    <div class="upload-controls">
                        <span class="upload-hint upload-status">Sin archivo seleccionado</span>
                        <input type="file" id="author-upload-input" class="upload-input"
                            accept="image/jpeg,image/png,image/webp" hidden>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h1><?php echo esc_html($author_name); ?></h1>
                <?php if (!empty($author_title)): ?>
                    <p class="author-title">
                        <?php echo esc_html($author_title); ?>
                    </p>
                <?php endif; ?>
                <div class="author-bio">
                    <?php echo wpautop(wp_kses_post($author_bio)); ?>
                </div>
                <?php
                $author_id = get_queried_object_id();
                $courses_count = villegas_count_learndash_courses_by_author($author_id);
                $columns_count = villegas_count_columns_by_author($author_id);
                ?>
                <div class="meta-list">
                    <span><?php echo esc_html($courses_count); ?> cursos</span>
                    <span><?php echo esc_html($columns_count); ?> columnas</span>
                </div>
            </div>
        </section>

        <section class="content-grid">
            <div class="section-card courses-section">
                <div class="section-header">
                    <h2>Cursos</h2>
                </div>
                <?php
                $author_id = get_the_author_meta('ID');

                $args = [
                    'post_type' => 'sfwd-courses',
                    'posts_per_page' => 2,
                    'author' => $author_id,
                    'post_status' => 'publish',
                ];

                $author_courses = new WP_Query($args);
                ?>

                <div class="courses-grid">

                    <?php if ($author_courses->have_posts()): ?>

                        <?php while ($author_courses->have_posts()):
                            $author_courses->the_post(); ?>

                            <article class="course-card">

                                <!-- Thumbnail -->
                                <a href="<?php the_permalink(); ?>" aria-label="Ir al curso <?php the_title(); ?>">
                                    <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://placehold.co/640x360/f2f2f0/111111?text=Sin+Imagen'; ?>"
                                        alt="<?php echo esc_attr(get_the_title()); ?>">
                                </a>

                                <!-- Course Title -->
                                <h3>
                                    <a href="<?php the_permalink(); ?>" class="course-title-link">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>

                                <!-- Course Excerpt or Dummy Fallback -->
                                <p>
                                    <?php
                                    $excerpt = get_the_excerpt();
                                    echo $excerpt ? esc_html($excerpt) : 'Curso disponible en esta plataforma.';
                                    ?>
                                </p>

                            </article>

                        <?php endwhile; ?>

                        <?php wp_reset_postdata(); ?>

                    <?php else: ?>

                        <?php
                        $author_id = get_queried_object_id();
                        $current_user = wp_get_current_user();
                        $is_owner = ($current_user->ID === $author_id);
                        $display_name = get_the_author_meta('display_name', $author_id);
                        ?>

                        <!-- Placeholder when no courses exist -->
                        <article class="course-card no-courses">
                            <div class="no-courses-inner">
                                <?php if ($is_owner): ?>

                                    <p>
                                        No tienes cursos publicados.<br><br>
                                        ¿Te gustaría publicar y vender cursos en nuestra plataforma?<br>
                                        Escríbenos en <strong>villeguistas@gmail.com</strong> detallando un plan de curso.
                                    </p>

                                <?php else: ?>

                                    <p>
                                        <?php echo esc_html($display_name); ?> no tiene cursos.
                                    </p>

                                <?php endif; ?>
                            </div>
                        </article>

                    <?php endif; ?>

                </div>
                <div class="section-footer">
                    <?php
                    $author_courses_url = add_query_arg(
                        [
                            'post_type' => 'sfwd-courses',
                            'author' => $author_id,
                        ],
                        home_url('/')
                    );
                    ?>

                    <a href="<?php echo esc_url($author_courses_url); ?>" aria-label="Ver todos los cursos">
                        Ver todos
                    </a>
                </div>
            </div>

            <div class="section-card columns-section">
                <div class="section-header">
                    <h2>Columnas</h2>
                </div>
                <?php
                $author_id = get_the_author_meta('ID');

                $args = [
                    'post_type' => 'post',
                    'posts_per_page' => 3,
                    'author' => $author_id,
                    'post_status' => 'publish',
                ];

                $author_posts = new WP_Query($args);
                ?>

                <div class="columns-list">

                    <?php if ($author_posts->have_posts()): ?>
                        <?php while ($author_posts->have_posts()):
                            $author_posts->the_post(); ?>

                            <article class="column-item">

                                <!-- Thumbnail -->
                                <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://placehold.co/140x140/efefed/1d1d1b?text=No+Image'; ?>"
                                    alt="<?php echo esc_attr(get_the_title()); ?>">

                                <div>
                                    <!-- Post Title -->
                                    <h3>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_title(); ?>
                                        </a>
                                    </h3>

                                    <!-- Post Meta -->
                                    <p><?php echo get_the_date(); ?> — Columna</p>
                                </div>

                            </article>

                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>

                    <?php else: ?>

                        <p>No hay columnas publicadas aún.</p>

                    <?php endif; ?>

                </div>
                <div class="section-footer">
                    <?php
                    $author_id = get_the_author_meta('ID');
                    $author_data = get_user_by('ID', $author_id);
                    $author_slug = $author_data->user_nicename;

                    $columns_archive_url = home_url("/autor/{$author_slug}/columnas/");
                    ?>
                    <a href="<?php echo esc_url($columns_archive_url); ?>" aria-label="Ver todas las columnas">
                        Ver todas
                    </a>
                </div>
            </div>
        </section>

        <?php echo villegas_render_user_books_section(); ?>
    </div>
    <?php wp_footer(); ?>
</body>

</html>