<?php
/**
 * Plugin Name: HTML Cured Executable Minifier
 * Package:     Wpninetyplus
 * Since:       1.0.0
 * Description: Utility class to minify HTML, inline CSS, and inline JavaScript on the fly via output buffering.
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class HtmlCuredExe
 * 
 * Manages output buffer-based HTML minification, inline style optimization, 
 * and secure JavaScript handling with filter support for excluded assets.
 *
 * @since 1.0.0
 */
class HtmlCuredExe {

    /**
     * Main HTML Minifier wrapper.
     * Processes raw HTML output, delegating script and style blocks to their respective parsers 
     * while respecting the 'wpninetyplus_cured_assets' filter.
     *
     * @since 1.0.0
     * @param string $html The raw page output captured from the output buffer.
     * @return string      The cleaned and minified HTML string.
     */
    public function cured_client_html( string $html ) : string {
        // Return early if the buffer payload is completely empty.
        if ( empty( $html ) ) {
            return $html;
        }

        /**
         * Filters the list of assets to ignore during optimization.
         *
         * @since 1.0.0
         * @param array $default_ids Default empty array of IDs.
         */
        $ignore_ids = apply_filters( 'wpninetyplus_cured_assets', [] );

        // 1. Locate and minify all inline CSS content encapsulated inside <style> tags.
        $html = preg_replace_callback( '~<style\b[^>]*>([\s\S]*?)<\/style>~i', function( $matches ) {
            return '<style>' . $this->minify_css( $matches[1] ) . '</style>';
        }, $html );

        // 2. Locate and minify all inline JS content encapsulated inside <script> tags, respecting ignored IDs.
        $html = preg_replace_callback( '~<script\b([^>]*)>([\s\S]*?)<\/script>~i', function( $matches ) use ( $ignore_ids ) {
            $attrs     = $matches[1];
            $jsContent = $matches[2];

            // Skip external scripts containing 'src=' or script tags that are completely empty.
            if ( false !== stripos( $attrs, 'src=' ) || '' === trim( $jsContent ) ) {
                return $matches[0];
            }

            // Check if any of the ignored IDs are present in the script's attributes (e.g., id="my-custom-script-id").
            foreach ( $ignore_ids as $id ) {
                if ( preg_match( '/id=["\']' . preg_quote( $id, '/' ) . '["\']/i', $attrs ) ) {
                    return $matches[0]; // Skip minification for this script!
                }
            }

            // Return the script tag with attributes intact and the minified JS body.
            return '<script' . $attrs . '>' . $this->minify_js( $jsContent ) . '</script>';
        }, $html );

        // 3. Strip out HTML comments, collapse unnecessary spaces between tags, and reduce multi-whitespace blocks.
        $html = preg_replace( '~<!--[\s\S]*?-->~', '', $html ); // Remove HTML comments
        $html = preg_replace( '~>\s+<~', '><', $html );        // Remove whitespace gaps between HTML tags
        $html = preg_replace( '~\s{2,}~', ' ', $html );           // Condense consecutive white spaces into a single space

        return trim( $html );
    }

    /**
     * CSS Minifier engine.
     * Strips block comments, collapses spacing, and removes redundant syntax characters.
     *
     * @since 1.0.0
     * @param string $css Raw CSS block code.
     * @return string     Minified CSS block code.
     */
    private function minify_css( string $css ) : string {
        $css = preg_replace( '~/\*[\s\S]*?\*/~', '', $css );       // Strip CSS block comments (/* ... */)
        $css = preg_replace( '~\s+~', ' ', $css );                 // Collapse multiple spaces/tabs/newlines into a single space
        $css = preg_replace( '~\s*([{}:;,>+~])\s*~', '$1', $css ); // Remove spaces surrounding major CSS symbols/selectors
        $css = str_replace( ';}', '}', $css );                   // Clean up redundant semicolons right before closing brackets
        return trim( $css );
    }

    /**
     * JS Minifier engine.
     * Strips block/inline comments, compresses whitespace, and normalizes operator spacing.
     *
     * @since 1.0.0
     * @param string $js Raw JavaScript block code.
     * @return string    Minified JavaScript block code.
     */
    private function minify_js( string $js ) : string {
        $js = preg_replace( '~/\*[\s\S]*?\*/~', '', $js );       // Strip JS multi-line comments (/* ... */)
        $js = preg_replace( '~//.*~', '', $js );                 // Strip JS single-line comments (// ...)
        $js = preg_replace( '~\s+~', ' ', $js );                 // Collapse multiple spaces/tabs/newlines into a single space
        $js = preg_replace( '~\s*([=+\-*/%<>!&|,?:;()\[\]{}])\s*~', '$1', $js ); // Strip spacing around operators and punctuation
        return trim( $js );
    }
}