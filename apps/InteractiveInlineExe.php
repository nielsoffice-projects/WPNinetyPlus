<?php
/**
 * Plugin Name: WPNinetyPlus InteractiveInline Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured Inline JS Optimizer for cross pages and devices!
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class InteractiveInline
 * 
 * Handles finding all un-ID'd inline script blocks (<script>...</script>), 
 * stripping them from the document, merging them together, and injecting 
 * them either into the head or the footer based on configuration.
 *
 * @since 1.0.0
 */
class InteractiveInline {

    /**
     * Finds all generic inline script tags without a 'src' or 'id' attribute, 
     * combines them, and injects the consolidated script block into the document 
     * head or footer according to filter settings.
     *
     * @since 1.0.0
     * @param string $buffer The raw HTML output buffer.
     * @return string        The modified HTML buffer.
     */
    public function interactive_inline_one( string $buffer ) : string {

        if ( empty( $buffer ) ) {
            return $buffer;
        }

        // Find all script tags in the buffer
        preg_match_all( '/<script\b([^>]*)>([\s\S]*?)<\/script>/i', $buffer, $script_matches, PREG_SET_ORDER );

        if ( empty( $script_matches ) ) {
            return $buffer;
        }

        $combined_js       = '';
        $scripts_to_remove = [];

        foreach ( $script_matches as $match ) {
            $full_tag   = $match[0];
            $attributes = $match[1];
            $code       = $match[2];

            // Skip script tags that load external files (have a 'src' attribute)
            if ( preg_match( '/\bsrc\s*=/i', $attributes ) ) {
                continue;
            }

            // Skip script tags that have an 'id' attribute (letting your other class handle those)
            if ( preg_match( '/\bid\s*=/i', $attributes ) ) {
                continue;
            }

            // Skip JSON-LD / schema or template scripts if necessary (optional safeguard)
            if ( preg_match( '/type=["\'](application\/ld\+json|text\/template|x-template)["\']/i', $attributes ) ) {
                continue;
            }

            // Accumulate clean code if it's not empty
            if ( ! empty( trim( $code ) ) ) {
                $combined_js       .= "\n" . trim( $code ) . "\n";
                $scripts_to_remove[] = $full_tag;
            }
        }

        // If inline scripts were found and combined, proceed to injection
        if ( ! empty( $combined_js ) ) {
            $inline_script_tag = '<script id="WPNinetyPlusInlineMerge">' . $combined_js . '</script>';
            
            // 1. Remove original scattered inline script tags from buffer
            if ( ! empty( $scripts_to_remove ) ) {
                $buffer = str_replace( $scripts_to_remove, '', $buffer );
            }

            /**
             * Filter to choose placement location: 'head' or 'footer' (body)
             * Default: 'footer'
             * 
             * @since 1.0.0
             * @param string $location Placement position ('head' or 'footer')
             */
            $placement = apply_filters( 'wpninetyplus_inline_script_position', 'footer' );

            if ( 'head' === $placement ) {
                // Inject right before closing </head> tag
                if ( false !== stripos( $buffer, '</head>' ) ) {
                    $buffer = str_ireplace( '</head>', $inline_script_tag . '</head>', $buffer );
                } else {
                    $buffer = $inline_script_tag . $buffer;
                }
            } else {
                // Inject right before closing </body> tag (Default footer)
                if ( false !== stripos( $buffer, '</body>' ) ) {
                    $buffer = str_ireplace( '</body>', $inline_script_tag . '</body>', $buffer );
                } else {
                    $buffer .= $inline_script_tag;
                }
            }
        }

        return $buffer;
    }
}
