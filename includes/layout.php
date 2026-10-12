<?php
/** Theme-owned layout parts generated from the archived The Fallow Few site (tools/build_tff_family_theme.py). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Resolve our controlled /assets/ tokens and root-relative links, on any (sub)directory install. */
function tff_theme_fragment( $html ) {
    $html = tff_media_swap( $html );
    $html = preg_replace_callback( '~(src|href)="(/assets/[^"<>]+)"~', function ( $m ) {
        return $m[1] . '="' . esc_url( get_theme_file_uri( ltrim( $m[2], '/' ) ) ) . '"';
    }, $html );
    $html = preg_replace_callback( '~url\((/assets/[^)"\'<>]+)\)~', function ( $m ) {
        return 'url(' . esc_url( get_theme_file_uri( ltrim( $m[1], '/' ) ) ) . ')';
    }, $html );
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $html );
}

/** Path of a generated part. Only fixed names are accepted, never user-controlled paths. */
function tff_part_file( $name ) {
    if ( ! preg_match( '~^(?:header|footer|home)$~D', $name ) ) { return ''; }
    $file = get_theme_file_path( 'parts/' . $name . '.html' );
    return is_readable( $file ) ? $file : '';
}

function tff_theme_part( $name, $return = false ) {
    $file = tff_part_file( $name );
    if ( ! $file ) { return ''; }
    $html = tff_theme_fragment( (string) file_get_contents( $file ) );
    // Copyright year is always the current year (never a stale year copied from the old site).
    $html = str_replace( '<!--tff:year-->', esc_html( wp_date( 'Y' ) ), $html );
    if ( $return ) { return $html; }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme HTML, not user input.
    return '';
}

/** Simple heading band for any ordinary WordPress page or post the owner adds later. */
function tff_page_hero( $title, $trail = '' ) {
    echo '<section class="inner-hero"><div class="wrap"><div class="crumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a>' . ( $trail ? ' / ' . esc_html( $trail ) : '' ) . '</div><h1>' . esc_html( $title ) . '</h1></div></section>';
}

function tff_current_route() {
    return is_front_page() ? 'home' : '';
}
