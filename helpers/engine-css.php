<?php
/**
 * Plugin Name: WPNinetyPlus Skeleton Optimization CSS
 * Description: Optimizes and filters inline style blocks within HTML output using WordPress hooks.
 * Version: 1.2.0
 * Author: WPNinetyPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Parses an HTML string, finds inline <style> tags, processes their CSS, 
 * and allows modification via filters while respecting excluded IDs.
 *
 * @since 1.2
 * 
 * @param string   $skeleton The HTML content containing <style> tags.
 * @param array    $ids      An array of style tag IDs that should be excluded from optimization.
 * @param object   $handler  An object containing optimization methods (e.g., CSS curing).
 * @return string            The modified HTML string with optimized style blocks.
 */
function wpninetyplus_skeliton_replace_optimization( string $skeleton, array $ids, object $handler ) : string {

    // Find and iterate through all <style> tags in the HTML skeleton.
    $skeleton = preg_replace_callback( '/<style\b[^>]*>(.*?)<\/style>/is', function( $matches ) use ( $ids, $handler ) {
        
        // Extract the attributes of the current <style> tag.
        preg_match( '/<style\b([^>]*)>/i', $matches[0], $attr_match );
        $attributes = isset( $attr_match[1] ) ? $attr_match[1] : '';
        
        // Check if the current style tag's ID is in the exclusion array.
        foreach ( $ids as $id ) {
            if ( preg_match( '/id=["\']' . preg_quote( $id, '/' ) . '["\']/i', $attributes ) ) {
                // Return the original, unmodified style tag if matched.
                return $matches[0];
            }
        }

        /**
         * Filters the individual style tag attributes and processed CSS content.
         *
         * @since 1.2
         * 
         * @param string $attributes The HTML attributes of the style tag.
         * @param string $css_cured  The optimized/cured CSS content.
         */
        return apply_filters( 'wpninetyplus_skeleton_optimization_layout', $attributes, $handler->css_cured_ninety_plus( $matches[1] ) ); 

    }, $skeleton );

    /**
     * Filters the final rendered skeleton HTML after all style tags have been processed.
     *
     * @since 1.2
     * 
     * @param string $skeleton The fully processed HTML skeleton string.
     * @param array  $ids      The array of excluded style IDs.
     */
    return apply_filters( 'wpninetyplus_skeleton_optimization_rendered', $skeleton, $ids );    
}

// Register the layout modification filter.
add_filter( 'wpninetyplus_skeleton_optimization_layout', 'skeleton_optimization_modifier_layout', 10, 2 );

/**
 * Rebuilds the <style> tag using the filtered attributes and processed CSS.
 *
 * @since 1.2
 * 
 * @param string $attr    The HTML attributes for the style tag.
 * @param mixed  $handler The processed CSS string (passed from the filter call).
 * @return string         The reconstructed <style> tag.
 */
function skeleton_optimization_modifier_layout( string $attr, mixed $handler ) : string {
    $skeleton = '<style' . $attr . '>' . $handler . '</style>';
    return $skeleton;    
}

// Register the final render modification filter.
add_filter( 'wpninetyplus_skeleton_optimization_rendered', 'skeleton_optimization_modifier', 10, 2 );

/**
 * Appends the list of excluded IDs to the end of the rendered skeleton output.
 *
 * @since 1.2
 * 
 * @param string $skeleton The rendered HTML skeleton.
 * @param array  $ids      The array of excluded style IDs.
 * @return string          The modified skeleton with appended ID information.
 */
function skeleton_optimization_modifier( string $skeleton, array $ids ) : string {
    $skeleton .= implode( ', ', $ids );
    return $skeleton;    
}