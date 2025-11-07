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

    $accordion_id      = 'villegas-faq-accordion-' . $instance;
    $tailwind_handle   = 'villegas-faq-tailwind';
    $is_first_instance = ! $assets_printed;

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

    <div class="p-4 sm:p-8 font-sans">
        <div class="max-w-4xl mx-auto p-6 sm:p-10">
            <div id="<?php echo esc_attr( $accordion_id ); ?>" class="space-y-4">
                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Puedo compartir mi curso con otra persona?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Puedes compartir el curso, pero las lecciones y evaluaciones que ya hayas completado no estarán disponibles para la otra persona. En consecuencia, no podrá registrar su propio progreso ni acumular puntos.</p>
                        <p class="mt-2 font-medium">Te recomendamos mantener tu cuenta como un perfil personal, que refleje de forma precisa tu propio avance y desempeño.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Necesito una cuenta en el sitio de El Villegas para acceder al curso?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí. Para acceder al contenido de los cursos es necesario crear una cuenta utilizando tu correo electrónico y una contraseña. Una vez registrada, podrás adquirir los cursos disponibles y acceder a ellos desde tu perfil.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Puedo pagar de otra forma que no sea con tarjeta?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí. También puedes realizar una transferencia bancaria. Al momento de la compra, selecciona la opción <strong>"Transferencia Bancaria"</strong>; el sistema te mostrará los datos del banco para efectuar el depósito.</p>
                        <p class="mt-2">Luego, envía el comprobante a <a href="mailto:villeguistas@gmail.com" class="text-primary hover:text-primary-light font-medium">villeguistas@gmail.com</a> indicando el número de orden. Una vez verificado el pago, tu acceso al curso será habilitado durante el mismo día hábil.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Es posible reiniciar o eliminar mi progreso en el curso?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí. Si deseas eliminar toda la información de tu curso, puedes solicitarlo escribiendo a <a href="mailto:villeguistas@gmail.com" class="text-primary hover:text-primary-light font-medium">villeguistas@gmail.com</a>.</p>
                        <p class="mt-2 font-bold text-red-600">Ten en cuenta que este proceso es irreversible.</p>
                        <p>Una vez completado, perderás tus puntos, tu progreso y tu certificado asociado al curso.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Cuál es la validez del certificado otorgado por Academia Villegas?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>El certificado emitido por Academia Villegas acredita la finalización del curso dentro de nuestra plataforma. No está asociado a instituciones educativas formales, por lo que no tiene validez oficial fuera de este entorno.</p>
                        <p class="mt-2">Sin embargo, es un reconocimiento de tu participación y aprendizaje, y servirá como base para futuros cursos que puedan requerir la aprobación de módulos anteriores.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Por cuánto tiempo tendré acceso al curso después de comprarlo?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>El acceso al curso es permanente. Podrás ingresar siempre que exista el sitio <strong>elvillegas.cl</strong>, el cual esperamos mantener activo por mucho tiempo.</p>
                    </div>
                </details>

                <details class="group border border-black overflow-hidden transition duration-300">
                    <summary class="flex justify-between items-center w-full py-4 px-6 cursor-pointer select-none bg-white transition duration-300 group-hover:bg-gray-50">
                        <span class="text-lg font-semibold text-secondary">
                            ¿Puedo acceder al curso desde distintos dispositivos?
                        </span>
                        <svg class="faq-chevron w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </summary>
                    <div class="p-6 bg-gray-50 text-gray-700 leading-relaxed border-t border-black">
                        <p>Sí. Puedes acceder al curso desde cualquier dispositivo con conexión a internet, ya sea un smartphone, iPad, laptop o computador de escritorio.</p>
                        <p class="mt-2">El sistema está optimizado para ofrecer una buena experiencia tanto en pantallas grandes como en móviles.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>
    <?php

    $assets_printed = true;

    return ob_get_clean();
}
