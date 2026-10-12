<?php
/**
 * Original SEO data (title, description, canonical, Open Graph) for the theme-served routes,
 * from parts/seo.json. When an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) is active it owns the
 * homepage tags; the theme still titles the routes it serves itself (none yet: the original site is a single page).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tff_seo_data( $route ) {
    static $data = null;
    if ( null === $data ) {
        $file = get_theme_file_path( 'parts/seo.json' );
        $data = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $data[ $route ] ?? array();
}

function tff_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function tff_route_url( $route ) {
    $paths = array( 'home' => '/' );
    $path = $paths[ $route ] ?? '/';
    // Match the address WordPress actually serves (trailing slash per the permalink setting), so canonical never points at a redirect.
    return '/' === $path ? home_url( '/' ) : home_url( user_trailingslashit( $path ) );
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $route = tff_current_route();
    $virtual = false;
    if ( ! $route || ( tff_seo_plugin_active() && ! $virtual ) ) { return $title; }
    $seo = tff_seo_data( $route );
    return ! empty( $seo['title'] ) ? $seo['title'] : $title;
}, 99 );

add_action( 'wp_head', function () {
    $route = tff_current_route();
    if ( ! $route || tff_seo_plugin_active() ) { return; }
    $seo = tff_seo_data( $route );
    if ( ! $seo ) { return; }
    $url = tff_route_url( $route );
    $title = $seo['title'] ?? get_bloginfo( 'name' );
    echo '<meta name="description" content="' . esc_attr( $seo['description'] ?? '' ) . '">' . "\n";
    if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; }
    echo '<meta property="og:type" content="website"><meta property="og:site_name" content="The Fallow Few"><meta property="og:title" content="' . esc_attr( $title ) . '"><meta property="og:description" content="' . esc_attr( $seo['description'] ?? '' ) . '"><meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    $image = get_theme_file_uri( 'assets/tff-share.jpg' );
    echo '<meta property="og:image" content="' . esc_url( $image ) . '"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . esc_attr( $title ) . '"><meta name="twitter:description" content="' . esc_attr( $seo['description'] ?? '' ) . '"><meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
    if ( 'home' === $route ) {
        echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'The Fallow Few', 'url' => home_url( '/' ) ), JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}, 3 );

/** Add the theme-served original URLs to WordPress's XML sitemap. */
add_action( 'init', function () {
    if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) { return; }
    wp_register_sitemap_provider( 'tff', new class() extends WP_Sitemaps_Provider {
        public function __construct() { $this->name = 'tff'; $this->object_type = 'tff'; }
        public function get_url_list( $page_num, $object_subtype = '' ) {
            $urls = array( array( 'loc' => home_url( '/' ) ) );
            return $urls;
        }
        public function get_max_num_pages( $object_subtype = '' ) { return 1; }
    } );
} );
