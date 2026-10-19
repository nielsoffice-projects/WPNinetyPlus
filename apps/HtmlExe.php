<?php
/**
 * Plugin Name: WPNinetyPlus HtmlExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured JS Optimized static cross pages and devices cured HTML!
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

use WPNinetyPlus\LayoutExe;
use WPNinetyPlus\InteractiveExe;

/**
 * Class HtmlExe
 * 
 * Manages the core HTML parsing, optimization pipeline, layout/script hooks, 
 * and minification processes for the plugin.
 *
 * @since 1.0.0
 */
class HtmlExe {

    /**
     * Flag to determine whether to apply secure JavaScript optimization.
     * 
     * @var bool
     */
    private bool $secure_js = PST;

    /**
     * Retrieves an array of asset IDs that should be ignored/excluded from optimization.
     *
     * @since 1.0.0
     * @return array Array of asset IDs to ignore.
     */
    private function get_ignore_assets() : array {
        $default_ids = [];
        
        /**
         * Filters the list of assets to ignore during optimization.
         *
         * @since 1.0.0
         * @param array $default_ids Default empty array of IDs.
         */
        return apply_filters( 'wpninetyplus_cured_assets', $default_ids );
    }

    /**
     * HtmlExe constructor.
     *
     * @since 1.0.0
     * @param ?bool $secure_js Optional override for the secure JavaScript flag.
     */
    public function __construct( ?bool $secure_js = null ) {
        if ( null !== $secure_js ) {
            $this->secure_js = $secure_js;
        }
    }

    /**
     * Safely strips JavaScript comments without breaking strings or URLs.
     *
     * @since 1.0.0
     * @param string $js Raw JavaScript string content.
     * @return string    JavaScript string with comments removed.
     */
    private function remove_js_comments( string $js ) : string {
        // Removes multi-line comments (/* ... */) and single-line comments (// ...)
        // while preserving URLs (http://) inside strings.
        $res = preg_replace( '!/\*.*?\*/!s', '', $js );
        $res = preg_replace( '~//(?=[^"]*(?:"[^"]*"[^"]*)*$)~m', '', $res );
        return $res;
    }

    /**
     * Main pipeline method to cure and optimize the HTML skeleton output.
     *
     * @since 1.0.0
     * @param string $skeleton The raw HTML output string.
     * @return string          The fully optimized, minified, and cured HTML string.
     */
    public function html_cured_ninety_plus( string $skeleton ) : string {

        // Skip processing if the skeleton is empty or if WordPress is generating a feed.
        if ( empty( $skeleton ) || ( function_exists( 'is_feed' ) && is_feed() ) ) {
            return $skeleton;
        }  

        $ignore_object_raw = $this->get_ignore_assets();
        $object_layout     = new LayoutExe();
        
        // Check if Output CSS Curing (OCC) is enabled.
        $occ_enabled       = defined( 'OCC' ) & OCC;

        if ( $occ_enabled ) :
            // Process and optimize inline style tags within the skeleton.
            $skeleton = wpninetyplus_skeliton_replace_optimization( $skeleton, $ignore_object_raw, $object_layout );
        endif;

        // Check if Output JS Curing (OCJ) is enabled.
        $ocj_enabled = defined( 'OCJ' ) & OCJ;

        if ( $ocj_enabled ) :
            $js_cured  = new InteractiveExe();
            // Process and optimize inline script tags within the skeleton.
            $skeleton = wpninetyplus_script_replace_optimization( $this->secure_js, $skeleton, $ignore_object_raw, $js_cured );
        endif;

        // Define search patterns for HTML minification.
        $search = [
            // 1. Remove HTML comments (preserving IE conditional comments).
            '/<!--(?!\s*(?:\[if [^\]]>|<!|>))(?:(?!-->).)*-->/s', 
            // 2. Collapse all whitespaces, tabs, and newlines into a single space.
            '/\s+/'                                              
        ];
        
        // Define replacement values.
        $replace = [
            '',
            ' '
        ];

        // Safely strip comments/whitespace, trim edges, and return the final HTML string.
        return trim( preg_replace( $search, $replace, $skeleton ) );
    }
}