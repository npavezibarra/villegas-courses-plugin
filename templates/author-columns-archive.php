<?php
/* Template: Author Columns Archive */

if (!defined('ABSPATH')) exit;

// Retrieve author based on URL
$author = get_user_by('slug', get_query_var('author_name'));

if (!$author) {
    wp_die('Author not found.');
}

// --------------------------------------------------------------
// ADD THE HEADER SNIPPET YOU REQUESTED
// --------------------------------------------------------------
include plugin_dir_path( __FILE__ ) . 'template-parts/header.php';
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
// --------------------------------------------------------------
?>

<div class="author-columns-archive">
    <h1>Archivo: <?php echo esc_html($author->display_name); ?></h1>

    <div class="columns-grid">
        <?php
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'author'         => $author->ID,
            'post_status'    => 'publish'
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()):
            while ($query->have_posts()):
                $query->the_post();
                ?>
                <article class="column-item">

                    <!-- Thumbnail -->
                    <a href="<?php the_permalink(); ?>">
                        <?php 
                        if (has_post_thumbnail()) {
                            the_post_thumbnail('medium');
                        } else {
                            echo '<img src="https://placehold.co/320x320/efefed/1d1d1b?text=No+Image">';
                        }
                        ?>
                    </a>

                    <!-- Title -->
                    <h3>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>

                    <!-- Excerpt -->
                    <p class="column-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                    </p>

                    <!-- Date -->
                    <p class="column-date">
                        <?php echo get_the_date('F j, Y'); ?>
                    </p>

                </article>
                <?php
            endwhile;
        else:
            echo "<p>No hay columnas de este autor.</p>";
        endif;

        wp_reset_postdata();
        ?>
    </div>
</div>

<?php
// Include footer if needed
// get_footer();
?>
