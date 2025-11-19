<?php get_header(); ?>

<?php 
    $author_id = get_queried_object_id();

    // WP core fields
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_bio  = get_the_author_meta('description', $author_id);

    // ACF custom fields (optional, recommended)
    $author_position = get_field('author_position', 'user_' . $author_id);
    $author_photo    = get_field('author_photo', 'user_' . $author_id);

    // Check if logged-in user is the same author → upload button visible
    $is_author = (get_current_user_id() == $author_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author Profile</title>

    <!-- Optional: Tailwind CDN (we can remove later if needed) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f7f9;
        }
        .section-title {
            position: relative;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #3b82f6;
            border-radius: 9999px;
        }
        .book-item:hover .book-cover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                        0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>

<main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

    <!-- ============================
         TOP PROFILE SECTION
    ============================= -->
    <section class="mb-10 bg-white p-6 rounded-xl shadow-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <!-- LEFT COLUMN: AUTHOR PROFILE -->
            <div class="md:col-span-1 flex flex-col items-center text-center">

                <div class="relative group w-36 h-36 mb-4">

                    <!-- PROFILE IMAGE -->
                    <img class="w-full h-full object-cover rounded-full border-4 border-white shadow-md"
                         src="<?php 
                            if ($author_photo) {
                                echo esc_url($author_photo['url']);
                            } else {
                                echo get_avatar_url($author_id, ['size' => 256]);
                            }
                         ?>"
                         alt="<?php echo esc_attr($author_name); ?>">

                    <!-- Upload button (only if logged-in user = this author) -->
                    <?php if ($is_author): ?>
                        <button onclick="document.getElementById('upload-photo-input').click()"
                            class="absolute inset-0 bg-black bg-opacity-50 text-white
                                   flex items-center justify-center rounded-full opacity-0
                                   group-hover:opacity-100 transition duration-300
                                   text-sm font-semibold cursor-pointer">
                            Upload Photo
                        </button>

                        <input id="upload-photo-input" type="file" accept="image/*"
                               class="hidden" />
                    <?php endif; ?>

                </div>

                <!-- AUTHOR NAME -->
                <h1 class="text-2xl font-extrabold text-gray-900">
                    <?php echo esc_html($author_name); ?>
                </h1>

                <!-- AUTHOR POSITION (ACF) -->
                <?php if ($author_position): ?>
                    <p class="text-md text-blue-600 font-medium">
                        <?php echo esc_html($author_position); ?>
                    </p>
                <?php endif; ?>

            </div>

            <!-- RIGHT COLUMN: ABOUT AUTHOR -->
            <div class="md:col-span-2">
                <h2 class="section-title">Sobre <?php echo esc_html($author_name); ?></h2>

                <div class="text-gray-700 leading-relaxed space-y-4 text-justify">

                    <!-- WP BIO OR ACF BIO -->
                    <p>
                        <?php 
                            echo nl2br(
                                esc_html(
                                    $author_bio ?: 'Este autor aún no ha agregado una biografía.'
                                )
                            ); 
                        ?>
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================
         COURSES SECTION (Dynamic)
    ============================= -->
    <section class="bg-white p-6 rounded-xl shadow-lg mb-10">

        <h2 class="section-title">Cursos</h2>

        <?php
            $courses = new WP_Query([
                'post_type'      => 'course',
                'posts_per_page' => 5,
                'author'         => $author_id,
            ]);
        ?>

        <?php if ($courses->have_posts()): ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <?php while ($courses->have_posts()): $courses->the_post(); ?>

                    <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg transition duration-200 hover:shadow-md">

                        <!-- Thumbnail -->
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('thumbnail', [
                                    'class' => 'w-16 h-16 rounded-lg object-cover'
                                ]); ?>
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-lg bg-gray-200"></div>
                            <?php endif; ?>
                        </a>

                        <!-- Title -->
                        <div class="flex-1">
                            <a href="<?php the_permalink(); ?>">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?php the_title(); ?>
                                </h3>
                            </a>

                            <!-- Optional Category -->
                            <?php
                                $terms = get_the_terms(get_the_ID(), 'course_category');
                                if ($terms && !is_wp_error($terms)):
                                    $first = $terms[0];
                            ?>
                                <p class="text-sm text-gray-500">
                                    <?php echo esc_html($first->name); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>

            </div>

            <!-- View All Button -->
            <div class="mt-6 text-center">
                <a href="/cursos?autor=<?php echo $author_id; ?>"
                   class="inline-block px-6 py-2 bg-blue-600 text-white font-medium rounded-full hover:bg-blue-700 transition duration-150 shadow-md">

                   VER TODOS (<?php echo $courses->found_posts; ?>)
                </a>
            </div>

        <?php else: ?>

            <p class="text-gray-600">Este autor aún no tiene cursos publicados.</p>

        <?php endif; ?>

    </section>

    <!-- ============================
         COLUMNAS SECTION (Dynamic)
    ============================= -->
    <section class="bg-white p-6 rounded-xl shadow-lg mb-10">

        <h2 class="section-title">Columnas</h2>

        <?php
            // Query Columns (Blog Posts)
            $columns = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => 5,
                'author'         => $author_id,
                // Uncomment the next line if “Columnas” is a category
                // 'category_name'  => 'columnas',
            ]);
        ?>

        <?php if ($columns->have_posts()): ?>

            <div class="space-y-4">

                <?php while ($columns->have_posts()): $columns->the_post(); ?>

                    <div class="flex items-center space-x-4 p-2 border-b border-gray-100">

                        <!-- Thumbnail -->
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('thumbnail', [
                                    'class' => 'w-12 h-12 rounded-md object-cover'
                                ]); ?>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-200 rounded-md"></div>
                            <?php endif; ?>
                        </a>

                        <!-- Title -->
                        <a href="<?php the_permalink(); ?>"
                           class="text-base text-gray-800 font-medium hover:text-blue-600 transition duration-150">
                           <?php the_title(); ?>
                        </a>

                    </div>

                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>

            </div>

            <!-- View All Link -->
            <div class="mt-6 text-center">
                <a href="/blog?autor=<?php echo $author_id; ?>"
                   class="inline-block text-blue-600 font-medium hover:text-blue-700 transition duration-150 text-sm">
                   VER TODOS (<?php echo $columns->found_posts; ?>)
                </a>
            </div>

        <?php else: ?>

            <p class="text-gray-600">Este autor aún no ha publicado columnas.</p>

        <?php endif; ?>

    </section>

    <!-- ============================
         BOOKS SECTION
    ============================= -->
    <section class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="section-title">Libros del Autor</h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">

            <div class="book-item text-center p-2 rounded-lg">
                <div class="book-cover h-48 w-full bg-gray-200 rounded-lg mb-2"></div>
                <p class="font-semibold text-gray-800">Book Title</p>
                <p class="text-sm text-red-600 font-bold">Price</p>
            </div>

            <div class="book-item text-center p-2 rounded-lg">
                <div class="book-cover h-48 w-full bg-gray-200 rounded-lg mb-2"></div>
                <p class="font-semibold text-gray-800">Book Title</p>
                <p class="text-sm text-red-600 font-bold">Price</p>
            </div>

        </div>
    </section>

</main>

</body>
</html>

<?php get_footer(); ?>
