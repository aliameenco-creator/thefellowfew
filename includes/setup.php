<?php
/** One-click setup: pretty permalinks (needed for the original URLs) and the original site title. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tff_setup_missing() {
    $missing = array();
    if ( '' === (string) get_option( 'permalink_structure' ) ) { $missing['permalinks'] = 'turn on pretty permalinks'; }
    if ( 'The Fallow Few' !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "The Fallow Few" (the original title)'; }
    if ( tff_media_pending() ) { $missing['media'] = 'add the website images to the Media Library with descriptive file names and alt text (SEO)'; }
    return $missing;
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $missing = tff_setup_missing();
    if ( ! $missing ) { return; }
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=tff_setup' ), 'tff_setup' );
    echo '<div class="notice notice-warning"><p><strong>The Fallow Few setup:</strong> ' . esc_html( implode( '; ', $missing ) ) . '.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">Complete The Fallow Few setup</a></p></div>';
} );

add_action( 'admin_post_tff_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'tff_setup' );
    if ( '' === (string) get_option( 'permalink_structure' ) ) {
        update_option( 'permalink_structure', '/%postname%/' );
        flush_rewrite_rules( true );
    }
    if ( 'The Fallow Few' !== get_option( 'blogname' ) ) {
        update_option( 'blogname', 'The Fallow Few' );
        update_option( 'blogdescription', '' );
    }
    if ( tff_media_pending() ) { wp_safe_redirect( tff_media_import_url() ); exit; }
    wp_safe_redirect( home_url( '/' ) );
    exit;
} );
