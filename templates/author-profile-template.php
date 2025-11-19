<?php
/**
 * Template Name: Author Profile Template
 * Description: Static placeholder author profile layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Incluir header de tu tema o plantilla
include plugin_dir_path( __FILE__ ) . 'template-parts/header.php';
echo do_blocks('<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->');
?>

<div class="wp-site-blocks author-profile-template">
    <section class="author-header">
        <img src="https://via.placeholder.com/200x200.png?text=Autor" alt="Autor" />
        <div class="author-meta">
            <p class="has-small-font-size">PERFIL DEL AUTOR</p>
            <h1 class="wp-block-heading has-text-align-left">FERNANDO VILLEGAS</h1>
            <p class="has-medium-font-size">Director Académico Villegas</p>
            <p>Este es un borrador del perfil del autor. Toda la información es referencial.</p>
            <label class="upload-field">
                <span>Subir nueva foto</span>
                <input type="file" name="author-photo" />
            </label>
        </div>
    </section>

    <section class="about-box">
        <h2 class="wp-block-heading section-title">Sobre Fernando</h2>
        <p>Fernando Villegas es una figura destacada dentro de la comunidad académica Villegas. Este bloque es un texto de demostración que más adelante será reemplazado con contenido real y dinámico proveniente del perfil del autor.</p>
        <p>El objetivo del presente diseño es bosquejar la estructura de la página final. En esta sección podremos incorporar reseñas, descripciones detalladas, e hitos importantes de la trayectoria profesional.</p>
    </section>

    <section class="author-grid">
        <div class="courses-section">
            <div class="section-header">
                <h2 class="wp-block-heading section-title">Cursos</h2>
                <p>Selección destacada de cursos dictados por Fernando.</p>
            </div>
            <div class="courses-list">
                <article class="course-card">
                    <img src="https://via.placeholder.com/480x320.png?text=Curso+1" alt="Curso 1" />
                    <div class="card-content">
                        <span class="course-category">Categoría Demo</span>
                        <h3 class="wp-block-heading">Maestría en Historia Política</h3>
                        <p>Descripción breve del curso con datos ficticios.</p>
                    </div>
                </article>
                <article class="course-card">
                    <img src="https://via.placeholder.com/480x320.png?text=Curso+2" alt="Curso 2" />
                    <div class="card-content">
                        <span class="course-category">Categoría Demo</span>
                        <h3 class="wp-block-heading">Taller de Pensamiento Crítico</h3>
                        <p>Introducción al pensamiento crítico aplicado a la realidad nacional.</p>
                    </div>
                </article>
            </div>
            <div class="section-footer">
                <a href="#" class="wp-block-button__link">VER TODOS (+5)</a>
            </div>
        </div>

        <div class="columns-section">
            <h2 class="wp-block-heading section-title">Columnas</h2>
            <p>Artículos y columnas con publicaciones recientes.</p>
            <div class="columns-list">
                <article class="column-item">
                    <img src="https://via.placeholder.com/120x120.png?text=Columna+1" alt="Columna 1" />
                    <div>
                        <a href="#">El regreso de la República Cívica</a>
                        <p class="has-small-font-size">Columna de opinión — Demo</p>
                    </div>
                </article>
                <article class="column-item">
                    <img src="https://via.placeholder.com/120x120.png?text=Columna+2" alt="Columna 2" />
                    <div>
                        <a href="#">Democracia y Cultura Popular</a>
                        <p class="has-small-font-size">Columna de opinión — Demo</p>
                    </div>
                </article>
            </div>
            <div class="section-footer">
                <a href="#">VER TODOS (+5)</a>
            </div>
        </div>
    </section>

    <section class="books-section">
        <div class="section-header">
            <h2 class="wp-block-heading section-title">Libros Fernando Villegas</h2>
            <p>Repertorio de publicaciones destacadas en formato libro.</p>
        </div>
        <div class="books-grid">
            <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
                <article class="book-item">
                    <img src="https://via.placeholder.com/300x450.png?text=Libro+<?php echo $i; ?>" alt="Libro <?php echo $i; ?>" />
                    <h3 class="wp-block-heading">Título Libro <?php echo $i; ?></h3>
                    <p class="book-price">$<?php echo number_format(19990, 0, ',', '.'); ?></p>
                </article>
            <?php endfor; ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
