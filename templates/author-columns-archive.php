<?php
/* Template: Author Columns Archive */

if (!defined('ABSPATH')) exit;

$author = get_user_by('slug', get_query_var('author_name'));
if (!$author) wp_die('Autor no encontrado.');

$author_id       = $author->ID;
$author_first    = get_the_author_meta('first_name', $author_id);
$author_last     = get_the_author_meta('last_name', $author_id);
$author_name     = trim($author_first . ' ' . $author_last);
$author_name     = $author_name ?: $author->display_name;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Archivo: <?php echo esc_html($author_name); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cardo&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<?php wp_head(); ?>
</head>

<body <?php body_class(['author-profile-monochrome', 'author-columns-archive']); ?>>

<?php
// LOAD THE SAME EXACT HEADER USED IN YOUR AUTHOR PROFILE PAGE
include plugin_dir_path(__FILE__) . 'template-parts/header.php';
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
?>

<div class="author-profile-page">

    <h1>Archivo: <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"><?php echo esc_html($author_name); ?></a></h1>

    <div class="columns-archive-grid">
        <?php
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'author'         => $author_id,
            'post_status'    => 'publish',
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) :
            while ($query->have_posts()) :
                $query->the_post();
                ?>

                <article class="column-item-large">

                    <a href="<?php the_permalink(); ?>">
                        <?php 
                        if (has_post_thumbnail()) {
                            the_post_thumbnail('large');
                        } else {
                            echo '<img src="https://placehold.co/380x380/eee/111?text=No+Image">';
                        }
                        ?>
                    </a>

                    <h3>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>

                    <p class="date">
                        <?php echo get_the_date('F j, Y'); ?>
                    </p>

                    <p class="excerpt">
                        <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                    </p>

                </article>

            <?php endwhile;
            wp_reset_postdata();
        else :
            echo '<p>No hay columnas de este autor.</p>';
        endif;
        ?>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
