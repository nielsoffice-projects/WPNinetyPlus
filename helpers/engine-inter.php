<?php
/**
 * Plugin Name: WPNinetyPlus Script Optimization (v1.2.0)
 * Description: Optimizes, secures, and filters inline JavaScript blocks within HTML output.
 * Version: 1.2.0
 * Author: WPNinetyPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Parses an HTML string, locates inline <script> tags, strips comments, 
 * and processes/secures them based on configuration while ignoring specified IDs.
 *
 * @since 1.2.0
 * 
 * @param bool   $secure_js         Whether to apply protected security optimization to the scripts.
 * @param string $skeleton          The HTML content containing <script> tags.
 * @param array  $ignore_object_raw An array of script IDs or objects to ignore during optimization.
 * @param object $js_cured          An object containing JS optimization and protection methods.
 * @return string                   The modified HTML string with processed inline scripts.
 */
function wpninetyplus_script_replace_optimization( bool $secure_js, string $skeleton, array $ignore_object_raw, mixed $js_cured ) : string {

    // Find and iterate through all <script> tags in the HTML skeleton.
    $skeleton = preg_replace_callback( '/<script\b([^>]*)>(.*?)<\/script>/is', function( $matches ) use ( $secure_js, $ignore_object_raw, $js_cured ) {
        $attributes = $matches[1];
        $content    = $matches[2];

        // Skip external scripts (scripts containing a 'src' attribute).
        if ( false !== stripos( $attributes, 'src=' ) ) {
            return $matches[0];
        }

        // Check if the current script ID is in the ignored objects array.
        foreach ( $ignore_object_raw as $id ) {
            if ( preg_match( '/id=["\']' . preg_quote( $id, '/' ) . '["\']/i', $attributes ) ) {
                return $matches[0]; // Return original script tag untouched.
            }
        }

        // Remove JavaScript comments if the helper function is available globally.
        if ( function_exists( 'remove_js_comments' ) ) {
            $content = remove_js_comments( $content );
        }

        // Apply secure or standard script curing based on the $secure_js flag.
        if ( $secure_js ) {
            $processed_content = $js_cured->protected_ninety_plus_script( $content );
        } else {
            $processed_content = $js_cured->javascript_cured_ninety_plus( $content );
        }

        // Reconstruct and return the optimized inline script tag.
        return '<script' . $attributes . '>' . $processed_content . '</script>';
        
    }, $skeleton );

    /**
     * Filters the final rendered script output after all inline scripts have been processed.
     *
     * @since 1.2.0
     * 
     * @param string $skeleton          The fully processed HTML skeleton string.
     * @param array  $ignore_object_raw The array of ignored script IDs/objects.
     */
    return apply_filters( 'wpninetyplus_script_optimization_output', $skeleton, $ignore_object_raw );    
}

// Register the script optimization output filter.
add_filter( 'wpninetyplus_script_optimization_output', 'script_optimization_modifier', 10, 2 );

/**
 * Appends a list of the ignored script IDs to the end of the rendered skeleton output.
 *
 * @since 1.2.0
 * 
 * @param string $skeleton          The rendered HTML skeleton.
 * @param array  $ignore_object_raw The array of ignored script IDs/objects.
 * @return string                   The modified skeleton with appended ignored list.
 */
function script_optimization_modifier( string $skeleton, array $ignore_object_raw ): string {
    // Append a comma-separated list of ignored objects to the end of the HTML output.
    $skeleton .= implode( ', ', $ignore_object_raw );
    
    return $skeleton;    
}