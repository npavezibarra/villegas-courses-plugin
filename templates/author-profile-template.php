<?php
/**
 * Template Name: Author Profile Template
 * Description: Static monochrome author profile layout with dummy content.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'Autor | Fernando Villegas', 'villegas-course-plugin' ); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-inter: 'Inter', sans-serif;
            --bg-base: #f5f5f3;
            --surface: #ffffff;
            --border-soft: #d7d7d2;
            --border-strong: #b5b5af;
            --text-primary: #1d1d1b;
            --text-secondary: #4f4f4c;
            --text-muted: #868683;
            --pill-bg: #ececea;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-inter);
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
            background: var(--bg-base);
        }

        .page-header {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }

        .page-header h1 {
            margin: 8px 0 0;
            font-size: 2.75rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            text-transform: none;
            color: var(--text-primary);
        }

        .profile-section {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: 28px;
            padding: 40px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 36px;
            align-items: center;
        }

        .profile-portrait {
            width: 100%;
            border-radius: 24px;
            border: 1px solid var(--border-soft);
            background: #f1f1ef;
        }

        .profile-details h2 {
            margin: 0;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
        }

        .profile-details h3 {
            margin: 12px 0 0;
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .profile-details .subtitle {
            margin-top: 6px;
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .profile-details p {
            margin-top: 16px;
            color: var(--text-secondary);
            max-width: 620px;
        }

        .meta-list {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px 24px;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .upload-controls {
            margin-top: 32px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }

        .upload-button {
            appearance: none;
            border: 1px solid var(--border-strong);
            border-radius: 999px;
            background: transparent;
            padding: 10px 28px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            color: var(--text-primary);
            transition: background 0.2s ease, color 0.2s ease;
        }

        .upload-button.is-active {
            background: var(--text-primary);
            color: #fff;
        }

        .upload-button.is-uploaded {
            border-color: var(--text-primary);
        }

        .upload-hint {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .about-section {
            margin-top: 40px;
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: 28px;
            padding: 32px 36px;
        }

        .about-section h2 {
            font-size: 1.2rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .about-section p {
            margin-bottom: 16px;
            color: var(--text-secondary);
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
            border-radius: 28px;
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .section-header span {
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .section-header h2 {
            font-size: 1.8rem;
            margin: 8px 0 12px;
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
            border: 1px solid var(--border-soft);
            border-radius: 24px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .course-card img {
            border-radius: 18px;
            border: 1px solid var(--border-soft);
        }

        .course-meta {
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .course-card h3 {
            font-size: 1.25rem;
            margin: 8px 0;
        }

        .course-card p {
            color: var(--text-secondary);
            flex: 1;
        }

        .section-footer {
            margin-top: 32px;
            display: flex;
            justify-content: flex-end;
        }

        .section-footer a {
            font-size: 0.95rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--text-primary);
            border-bottom: 1px solid currentColor;
            padding-bottom: 4px;
        }

        .columns-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 20px;
        }

        .column-item {
            display: flex;
            gap: 16px;
            align-items: center;
            border: 1px solid var(--border-soft);
            border-radius: 20px;
            padding: 16px;
        }

        .column-item img {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            object-fit: cover;
        }

        .column-item a {
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
        }

        .column-item p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .books-section {
            margin-top: 48px;
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: 28px;
            padding: 36px 40px;
        }

        .books-section h2 {
            font-size: 1.9rem;
            margin-bottom: 8px;
        }

        .books-description {
            color: var(--text-secondary);
            margin-bottom: 28px;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 24px;
        }

        .book-item {
            border: 1px solid var(--border-soft);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .book-item img {
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            background: #f0f0ed;
        }

        .book-item h3 {
            font-size: 1rem;
            margin: 0;
        }

        .book-price {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        @media (max-width: 1024px) {
            .profile-section {
                grid-template-columns: minmax(0, 1fr);
                text-align: left;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .author-profile-page {
                padding: 32px 20px 72px;
            }

            .profile-section,
            .about-section,
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
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'author-profile-monochrome' ); ?>>
<div class="author-profile-page">
    <header class="page-header">
        <span>Perfil del autor</span>
        <h1>Fernando Villegas</h1>
    </header>

    <section class="profile-section">
        <img class="profile-portrait" src="https://placehold.co/520x640/ededeb/1a1a1a?text=Retrato" alt="Retrato del autor" />
        <div class="profile-details">
            <h2>Autor invitado</h2>
            <h3>FERNANDO VILLEGAS</h3>
            <p class="subtitle">Director Académico Villegas</p>
            <p>
                Esta biografía es una representación ficticia utilizada únicamente como borrador visual.
                El objetivo es mostrar el flujo completo del perfil del autor manteniendo una estética monocromática.
            </p>
            <div class="meta-list">
                <span>15+ años experiencia</span>
                <span>45 publicaciones</span>
                <span>10 cursos activos</span>
            </div>
            <div class="upload-controls">
                <button type="button" class="upload-button" data-upload-toggle>Subir nueva foto</button>
                <span class="upload-hint upload-status">Sin archivo seleccionado</span>
                <input type="file" id="author-upload-input" class="upload-input" hidden>
            </div>
        </div>
    </section>

    <section class="about-section">
        <h2>Sobre Fernando</h2>
        <p>
            Fernando Villegas es reconocido por sus investigaciones sobre historia política chilena y análisis comparado de
            procesos sociales. Este bloque únicamente contiene texto de prueba para la maqueta.
        </p>
        <p>
            En esta sección se podrán detallar hitos biográficos, proyectos recientes y su vínculo con la comunidad
            académica del Villegas Institute. Todo el contenido será reemplazado posteriormente por datos reales.
        </p>
    </section>

    <section class="content-grid">
        <div class="section-card courses-section">
            <div class="section-header">
                <span>Programas</span>
                <h2>Cursos</h2>
            </div>
            <p class="section-description">Selección de cursos destacados dictados por Fernando.</p>
            <div class="courses-grid">
                <article class="course-card">
                    <img src="https://placehold.co/640x360/f2f2f0/111111?text=Curso+01" alt="Curso destacado 1">
                    <div class="course-meta">Historia contemporánea</div>
                    <h3>Maestría en Historia Política</h3>
                    <p>Programa intensivo que repasa los principales hitos republicanos desde una mirada crítica.</p>
                </article>
                <article class="course-card">
                    <img src="https://placehold.co/640x360/f2f2f0/111111?text=Curso+02" alt="Curso destacado 2">
                    <div class="course-meta">Pensamiento crítico</div>
                    <h3>Taller de Análisis Cultural</h3>
                    <p>Sesiones prácticas centradas en el desarrollo de habilidades interpretativas para medios contemporáneos.</p>
                </article>
            </div>
            <div class="section-footer">
                <a href="#" aria-label="Ver todos los cursos">Ver todos (+5)</a>
            </div>
        </div>

        <div class="section-card columns-section">
            <div class="section-header">
                <span>Artículos</span>
                <h2>Columnas</h2>
            </div>
            <p class="section-description">Últimas columnas de opinión en medios asociados.</p>
            <div class="columns-list">
                <article class="column-item">
                    <img src="https://placehold.co/140x140/efefed/1d1d1b?text=Columna+01" alt="Columna 1">
                    <div>
                        <a href="#">El regreso de la República Cívica</a>
                        <p>Columna de opinión — Demo</p>
                    </div>
                </article>
                <article class="column-item">
                    <img src="https://placehold.co/140x140/efefed/1d1d1b?text=Columna+02" alt="Columna 2">
                    <div>
                        <a href="#">Democracia y Cultura Popular</a>
                        <p>Ensayo semanal — Demo</p>
                    </div>
                </article>
            </div>
            <div class="section-footer">
                <a href="#" aria-label="Ver todas las columnas">Ver todos (+5)</a>
            </div>
        </div>
    </section>

    <section class="books-section">
        <h2>Libros Fernando Villegas</h2>
        <p class="books-description">Catálogo referencial con publicaciones impresas.</p>
        <div class="books-grid">
            <article class="book-item">
                <img src="https://placehold.co/320x480/ededeb/111111?text=Libro+01" alt="Libro 1">
                <h3>Historia de las Ideas Chilenas</h3>
                <p class="book-price">$19.990</p>
            </article>
            <article class="book-item">
                <img src="https://placehold.co/320x480/ededeb/111111?text=Libro+02" alt="Libro 2">
                <h3>Cartas a una República</h3>
                <p class="book-price">$15.990</p>
            </article>
            <article class="book-item">
                <img src="https://placehold.co/320x480/ededeb/111111?text=Libro+03" alt="Libro 3">
                <h3>El Ciclo de la Ciudadanía</h3>
                <p class="book-price">$17.990</p>
            </article>
            <article class="book-item">
                <img src="https://placehold.co/320x480/ededeb/111111?text=Libro+04" alt="Libro 4">
                <h3>Memorias del Debate Público</h3>
                <p class="book-price">$14.990</p>
            </article>
            <article class="book-item">
                <img src="https://placehold.co/320x480/ededeb/111111?text=Libro+05" alt="Libro 5">
                <h3>Atlas de la Política Chilena</h3>
                <p class="book-price">$21.990</p>
            </article>
            <article class="book-item">
                <img src="https://placehold.co/320x480/ededeb/111111?text=Libro+06" alt="Libro 6">
                <h3>Ensayos de Cultura Cívica</h3>
                <p class="book-price">$18.990</p>
            </article>
        </div>
    </section>
</div>
<script>
    (function() {
        function simulateAuthorUpload(file) {
            return new Promise(function(resolve) {
                setTimeout(function() {
                    resolve(file);
                }, 900);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var uploadBtn = document.querySelector('[data-upload-toggle]');
            var uploadInput = document.getElementById('author-upload-input');
            var uploadStatus = document.querySelector('.upload-status');

            if (!uploadBtn || !uploadInput) {
                return;
            }

            uploadBtn.addEventListener('click', function() {
                uploadBtn.classList.toggle('is-active');
                if (uploadBtn.classList.contains('is-active')) {
                    uploadBtn.textContent = 'Seleccionar archivo';
                    uploadInput.click();
                } else {
                    uploadBtn.textContent = 'Subir nueva foto';
                }
            });

            uploadInput.addEventListener('change', function() {
                if (!uploadInput.files.length) {
                    uploadStatus.textContent = 'Sin archivo seleccionado';
                    uploadBtn.classList.remove('is-uploaded');
                    uploadBtn.textContent = 'Subir nueva foto';
                    return;
                }

                uploadStatus.textContent = 'Subiendo…';
                simulateAuthorUpload(uploadInput.files[0]).then(function() {
                    uploadStatus.textContent = 'Imagen subida correctamente (simulado)';
                    uploadBtn.classList.add('is-uploaded');
                    uploadBtn.textContent = 'Cambiar imagen';
                });
            });
        });
    })();
</script>
<?php wp_footer(); ?>
</body>
</html>
