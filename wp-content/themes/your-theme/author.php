<?php get_header(); ?>

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
                    <!-- PROFILE IMAGE (dynamic later) -->
                    <img class="w-full h-full object-cover rounded-full border-4 border-white shadow-md"
                        src="https://placehold.co/144x144"
                        alt="Author Photo">

                    <!-- Upload button appears later -->
                    <button class="hidden absolute inset-0 bg-black bg-opacity-50 text-white
                        flex items-center justify-center rounded-full opacity-0 group-hover:opacity-100
                        transition duration-300 text-sm font-semibold cursor-pointer">
                        Upload Photo
                    </button>
                </div>

                <!-- AUTHOR NAME -->
                <h1 class="text-2xl font-extrabold text-gray-900">
                    AUTHOR NAME HERE
                </h1>

                <!-- AUTHOR POSITION -->
                <p class="text-md text-blue-600 font-medium">
                    AUTHOR POSITION HERE
                </p>
            </div>

            <!-- RIGHT COLUMN: ABOUT AUTHOR -->
            <div class="md:col-span-2">
                <h2 class="section-title">Sobre el Autor</h2>

                <div class="text-gray-700 leading-relaxed space-y-4 text-justify">
                    <!-- BIO CONTENT -->
                    <p>
                        Author biography will be loaded here in Task 2.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================
         COURSES SECTION
    ============================= -->
    <section class="bg-white p-6 rounded-xl shadow-lg mb-10">
        <h2 class="section-title">Cursos</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Placeholder course items -->
            <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Course Title</h3>
                    <p class="text-sm text-gray-500">Category</p>
                </div>
            </div>

            <div class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Course Title</h3>
                    <p class="text-sm text-gray-500">Category</p>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a class="inline-block px-6 py-2 bg-blue-600 text-white font-medium rounded-full">
                VER TODOS
            </a>
        </div>
    </section>

    <!-- ============================
         COLUMNS / ARTICLES SECTION
    ============================= -->
    <section class="bg-white p-6 rounded-xl shadow-lg mb-10">
        <h2 class="section-title">Columnas</h2>

        <div class="space-y-4">
            <div class="flex items-center space-x-4 p-2 border-b border-gray-100">
                <div class="w-12 h-12 bg-gray-200 rounded-md"></div>
                <span class="text-base text-gray-800 font-medium">Article Title</span>
            </div>

            <div class="flex items-center space-x-4 p-2 border-b border-gray-100">
                <div class="w-12 h-12 bg-gray-200 rounded-md"></div>
                <span class="text-base text-gray-800 font-medium">Article Title</span>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a class="inline-block text-blue-600 font-medium">
                VER TODOS
            </a>
        </div>
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
