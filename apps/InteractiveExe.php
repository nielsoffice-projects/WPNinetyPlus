<?php
/**
 * Plugin Name: WPNinetyPlus InteractiveExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured JS Optimized static cross pages and devices cured html!
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class InteractiveExe
 * 
 * Handles the processing, cleaning, minification, and protection of JavaScript 
 * content and inline script tags.
 *
 * @since 1.0.0
 */
class InteractiveExe {

    /**
     * Cleans and minifies inline JavaScript code safely by stripping comments 
     * and collapsing excessive whitespace.
     *
     * @since 1.0.0
     * @param string $interactive Raw JavaScript code string.
     * @return string             Minified and cleaned JavaScript string.
     */
    public function javascript_cured_ninety_plus( string $interactive ) : string {
        if ( empty( $interactive ) ) {
            return $interactive;
        }

        // Remove multi-line block comments (/* ... */).
        $interactive = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $interactive );

        // Remove single-line comments (// ...), guarding against URLs inside string literals.
        $interactive = preg_replace( '/(?<!["\'`]|[:])\/\/.*/m', '', $interactive );

        // Collapse excessive whitespace, tabs, and newlines into a single space.
        $interactive = preg_replace( '/\s+/', ' ', $interactive );

        return trim( $interactive );
    }

    /**
     * Processes HTML content, cleaning inline scripts while leaving 
     * HTML layout, attributes, and external <script src="..."> tags completely untouched.
     *
     * @since 1.0.0
     * @param string $html_content The HTML content or block containing script tags.
     * @return string              The processed HTML with minified inline scripts.
     */
    public function protected_ninety_plus_script( string $html_content ) : string {
        if ( empty( $html_content ) ) {
            return '';
        }

        // Match all <script> tags (both inline and external) across multiple lines.
        $pattern = '/(<script\b[^>]*>)(.*?)(<\/script>)/is';

        $processed_html = preg_replace_callback( $pattern, function( $matches ) {
            $open_tag   = $matches[1]; // e.g., <script> or <script src="..." defer>
            $js_content = $matches[2]; // The inner JavaScript content
            $close_tag  = $matches[3]; // </script>

            // If it's an external script (contains a 'src' attribute), skip modifying its inner content.
            if ( false !== stripos( $open_tag, 'src=' ) ) {
                return $open_tag . $js_content . $close_tag;
            }

            // Otherwise, safely minify the inline JavaScript using the internal helper method.
            $minified_js = $this->javascript_cured_ninety_plus( $js_content );

            return $open_tag . $minified_js . $close_tag;
        }, $html_content );

        return $processed_html;
    }
}