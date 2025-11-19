<?php
/**
 * Template Name: Author Profile Template
 * Description: Static placeholder author profile layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<style>
    .author-profile-template {
        padding: clamp(2rem, 4vw, 4rem);
        gap: clamp(1.5rem, 3vw, 3rem);
    }

    .author-profile-template section {
        margin-bottom: clamp(1.5rem, 3vw, 3rem);
    }

    .author-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: clamp(1rem, 2vw, 2.5rem);
    }

    .author-header img {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 100%;
        border: 4px solid #fff;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .author-header .author-meta {
        flex: 1 1 300px;
    }

    .author-header .upload-field {
        border: 1px dashed #999;
        padding: 0.75rem 1rem;
        border-radius: 999px;
        background: #fff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .author-header .upload-field input {
        opacity: 0;
        position: absolute;
        pointer-events: none;
    }

    .about-box {
        background: #fff;
        padding: clamp(1.5rem, 3vw, 2.5rem);
        border-radius: 20px;
        box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
        line-height: 1.7;
    }

    .author-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: clamp(1.5rem, 3vw, 3rem);
    }

    @media (max-width: 992px) {
        .author-grid {
            grid-template-columns: 1fr;
        }
    }

    .courses-section,
    .columns-section {
        background: #fff;
        border-radius: 20px;
        padding: clamp(1.5rem, 3vw, 2.5rem);
        box-shadow: 0 20px 30px rgba(0, 0, 0, 0.06);
    }

    .courses-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .course-card {
        border: 1px solid #e6e6e6;
        border-radius: 16px;
        overflow: hidden;
        background: #fafafa;
        display: flex;
        flex-direction: column;
    }

    .course-card img {
        width: 100%;
        height: 160px;
        object-fit: cover;
    }

    .course-card .card-content {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .course-card .course-category {
        font-size: 0.9rem;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .columns-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-top: 1.25rem;
    }

    .column-item {
        display: grid;
        grid-template-columns: 70px 1fr;
        gap: 1rem;
        align-items: center;
    }

    .column-item img {
        width: 70px;
        height: 70px;
        border-radius: 12px;
        object-fit: cover;
    }

    .books-section {
        background: #fff;
        padding: clamp(1.5rem, 3vw, 2.5rem);
        border-radius: 20px;
        box-shadow: 0 20px 30px rgba(0, 0, 0, 0.06);
    }

    .books-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1.25rem;
        margin-top: 1.5rem;
    }

    .book-item {
        border: 1px solid #ececec;
        border-radius: 16px;
        padding: 1rem;
        text-align: center;
        background: #fafafa;
    }

    .book-item img {
        width: 100%;
        height: 210px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 0.75rem;
    }

    .book-item .book-price {
        font-weight: 600;
        color: #111;
        margin-top: 0.25rem;
    }

    .section-title {
        margin: 0;
    }

    .section-footer {
        margin-top: 1.5rem;
    }

    .section-footer a {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.85rem;
        color: #111;
    }
</style>

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
