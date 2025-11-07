<?php
/**
 * Shortcode to render the FAQ accordion block.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function () {
    add_shortcode( 'villegas_faq_accordion', 'villegas_render_faq_accordion_shortcode' );
} );

/**
 * Render the FAQ accordion.
 *
 * @return string
 */
function villegas_render_faq_accordion_shortcode() {
    static $assets_printed = false;
    static $instance       = 0;

    $instance++;

    $accordion_id       = 'villegas-faq-accordion-' . $instance;
    $tailwind_handle    = 'villegas-faq-tailwind';
    $is_first_instance  = ! $assets_printed;

    if ( ! wp_script_is( $tailwind_handle, 'registered' ) ) {
        wp_register_script( $tailwind_handle, 'https://cdn.tailwindcss.com', array(), null, true );
    }

    wp_enqueue_script( $tailwind_handle );

    if ( $is_first_instance ) {
        $tailwind_config = <<<'JS'
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#4f46e5',
                            'primary-light': '#818cf8',
                            secondary: '#1f2937',
                            background: '#f9fafb',
                        },
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                        },
                    },
                },
            };
        JS;

        wp_add_inline_script( $tailwind_handle, $tailwind_config, 'before' );
    }

    $ready_script = sprintf(
        'const villegasFaqInit = function () {
            var detailsElements = document.querySelectorAll("#%1$s details");

            detailsElements.forEach(function (targetDetail) {
                targetDetail.addEventListener("toggle", function () {
                    if (targetDetail.open) {
                        detailsElements.forEach(function (detail) {
                            if (detail !== targetDetail && detail.open) {
                                detail.open = false;
                            }
                        });
                    }
                });
            });
        };

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", villegasFaqInit);
        } else {
            villegasFaqInit();
        }
        ',
        esc_js( $accordion_id )
    );

    wp_add_inline_script( $tailwind_handle, $ready_script );

    ob_start();
    ?>
    <?php if ( ! $assets_printed ) : ?>
        <style>
            summary::-webkit-details-marker {
                display: none;
            }

            summary::marker {
                display: none;
            }

            .faq-chevron {
                transition: transform 0.3s ease;
            }

            details[open] .faq-chevron {
                transform: rotate(180deg);
            }
        </style>
    <?php endif; ?>

    <div class="bg-background min-h-screen p-4 sm:p-8 font-sans">
        <div class="max-w-4xl mx-auto bg-white p-6 sm:p-10 border border-black">
            <div id="<?php echo esc_attr( $accordion_id ); ?>" class="space-y-4">
                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Puedo compartir el curso con otra persona?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí, pero al haber completado las lecciones y las evaluaciones, estas no estarán disponibles. Por lo tanto, la otra persona no podrá medir su progreso en conocimiento ni ganar puntos.</p>
                        <p class="mt-2 font-medium">Lo ideal es que uses tu cuenta como tu perfil único que mantiene registro de tu evolución personal.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Debo tener cuenta en el sitio El Villegas?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí, para acceder al contenido de los cursos es necesario asociarlo a una cuenta que es tu email y una clave secreta. Una vez registrado, podrás comprar el curso.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Solo aceptan tarjeta para pagar?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>También puedes hacer transferencia bancaria. Debes realizar la compra del curso y cuando te pregunte el método de pago, selecciona "Transferencia Bancaria", lo que te proporcionará la información del banco para el depósito.</p>
                        <p class="mt-2">Nos envías el comprobante a <a href="mailto:villeguistas@gmail.com" class="text-primary hover:text-primary-light font-medium">villeguistas@gmail.com</a> indicando el número de orden. Durante el día se verificará la compra y tendrás acceso al curso.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Puedo resetear o borrar los datos de mi progreso?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí, escríbenos a <a href="mailto:villeguistas@gmail.com" class="text-primary hover:text-primary-light font-medium">villeguistas@gmail.com</a> solicitando borrar toda la información del curso.</p>
                        <p class="mt-2 font-bold text-red-600">Este proceso es irreversible.</p>
                        <p>Si lo solicitas, perderás tus puntos, progreso y certificado.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Qué validez tiene el Certificado de Academia Villegas?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Esta plataforma es de educación y entretenimiento, y no adscribimos a ninguna institución educacional formal. Por lo tanto, el certificado no está pensado para tener validez fuera de esta misma plataforma.</p>
                        <p class="mt-2">Nuestros planes incluyen sumar cursos que pueden requerir la completación de un curso anterior para avanzar al siguiente.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>
    <?php

    $assets_printed = true;

    return ob_get_clean();
}
