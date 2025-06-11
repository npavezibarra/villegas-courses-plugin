<!-- HEADER -->
<?php
include plugin_dir_path(__FILE__) . 'template-parts/header.php';
// Load the default Twenty Twenty-Four header template part
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
?>

<body <?php body_class(); ?>>

<!-- LOGIN-LOGOUT -->
<?php
$post_id = get_the_ID(); // Get the current post ID
include plugin_dir_path(__FILE__) . 'template-parts/login-message.php';
?>

<!--COURSE BANNER -->
<?php include plugin_dir_path(__FILE__) . 'template-parts/banner-course.php'; ?>


<!--STAT QUIZZES WIDGET -->
<div id="buy-button-stats">
    <?php
    if (function_exists('mostrar_comprar_stats')) {
        mostrar_comprar_stats();
    }
    ?>
</div>

<!--ABOUT COURSE -->
<?php include plugin_dir_path(__FILE__) . 'template-parts/about-course.php'; ?>

<!--AUTHOR BOX -->
<?php include plugin_dir_path(__FILE__) . 'template-parts/author.php'; ?>

<!--FOOTER -->
<?php 
// Load the default Twenty Twenty-Four footer template part
echo do_blocks('<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->');

wp_footer(); 
?>
</body>
</html>
