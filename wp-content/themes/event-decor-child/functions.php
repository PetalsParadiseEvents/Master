// functions.php – child theme asset loading and initializations
<?php
function event_decor_child_assets() {
    // Parent theme stylesheet
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');

    // Child theme stylesheet (style.css)
    wp_enqueue_style('child-style', get_stylesheet_uri());

    // Swiper (carousel)
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', [], null, true);

    // GLightbox (lightbox for images & videos)
    wp_enqueue_style('glightbox-css', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css');
    wp_enqueue_script('glightbox-js', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], null, true);

    // AOS (animate on scroll)
    wp_enqueue_style('aos-css', 'https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css');
    wp_enqueue_script('aos-js', 'https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js', [], null, true);

    // Optional live‑chat placeholder – replace with actual provider ID later
    // wp_enqueue_script('tidio-chat', 'https://code.tidio.co/your‑tidio‑id.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'event_decor_child_assets');

// Initialize Swiper, GLightbox, and AOS after scripts are loaded
function event_decor_child_inline_scripts() {
    $inline = "
        document.addEventListener('DOMContentLoaded', function() {
            // Swiper init for hero carousel (class .hero-swiper)
            var heroSwiper = new Swiper('.hero-swiper', {
                loop: true,
                autoplay: { delay: 5000, disableOnInteraction: false },
                effect: 'fade',
                pagination: { el: '.swiper-pagination', clickable: true },
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
            });
            // Swiper init for testimonials carousel (class .testimonials-swiper)
            var testSwiper = new Swiper('.testimonials-swiper', {
                loop: true,
                autoplay: { delay: 7000 },
                effect: 'fade',
                pagination: { el: '.swiper-pagination', clickable: true }
            });
            // GLightbox init
            const lightbox = GLightbox({ selector: '.glightbox' });
            // AOS init
            AOS.init({ duration: 800, once: true });
        });
    ";
    wp_add_inline_script('swiper-js', $inline);
}
add_action('wp_enqueue_scripts', 'event_decor_child_inline_scripts', 20);
?>
