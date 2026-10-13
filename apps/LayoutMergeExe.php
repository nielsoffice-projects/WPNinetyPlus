<?php
/**
 * The plugin : WordPress Optimization Ninety Plus (90+) Grade Speed Insight 
 *
 * Secured CSS Optimized static cross pages and devices cured html!
 *
 * @link              https://www.nielsoffice.com/        
 * @since             1.0.0
 * @package           Wpninetyplus
 *
 */

namespace WPNinetyPlus;

if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

class LayoutMergeExe {

   public function layout_merge_one( string $buffer ) : string {

        if ( empty( $buffer ) ) {
            return $buffer;
        }

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Find all stylesheet link tags automatically
         **/
        preg_match_all( '/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/i', $buffer, $link_matches );

        if ( empty( $link_matches[0] ) ) {
            return $buffer;
        }

       /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Allow developers to extend or modify the target stylesheets via an array filter
         **/
        $target_stylesheets = apply_filters( 'wpninetyplus_merge_style', [
            'style.css',
        ] );

        $combined_css    = '';
        $links_to_remove = [];

       /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Extract the 'href' attribute
         * Check if the current URL matches any stylesheet in our dynamic array
         * Convert URL to a local server file path automatically
         **/
        foreach ( $link_matches[0] as $link_tag ) {
        
            preg_match( '/href=["\']([^"\']+)["\']/i', $link_tag, $href_match );
            if ( empty( $href_match[1] ) ) {
                continue;
            }

            $css_url = $href_match[1];

            $is_target = false;
            foreach ( $target_stylesheets as $target_file ) {
                if ( ! empty( $target_file ) && strpos( $css_url, $target_file ) !== false ) {
                    $is_target = true;
                    break;
                }
            }

            if ( ! $is_target ) {
                continue; 
            }

            $file_path = $this->skeleton_url_to_path( $css_url );

            if ( $file_path && file_exists( $file_path ) ) {
                $css_content = file_get_contents( $file_path );
                

                /**
                 * @package WPNinetyPlus 
                 * @since 1.0.0
                 * @date October 13, 2026 AM
                 * @maintainer NielsOffice
                 * Remove all CSS comments
                 * Remove unnecessary spaces around selectors, braces, colons, semicolons, and commas
                 * Collapse remaining multiple spaces and newlines into a single line
                 **/
                if ( $css_content ) {
                    $css_content = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_content );
                    $css_content = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css_content );
                    $css_content = trim( preg_replace( '/\s+/', ' ', $css_content ) );
                
                    $combined_css     .= $css_content;
                    $links_to_remove[] = $link_tag;
                }
            }
        }

        if ( ! empty( $combined_css ) ) {
            $inline_style_tag = '<style id="wp-ninety-plus-merged">' . $combined_css . '</style>';

            // Remove the original individual link tags from the HTML
            $buffer = str_replace( $links_to_remove, '', $buffer );

            // Inject the single merged style block right before </head>
            $buffer = str_ireplace( '</head>', $inline_style_tag . '</head>', $buffer );
        }

        return $buffer;
    }

    /**
     * @package WPNinetyPlus 
     * @since 1.0.0
     * @date October 13, 2026 AM
     * @maintainer NielsOffice
     * Helper to convert a local URL to an absolute server path.
     * Remove query parameters
     **/
    public function skeleton_url_to_path( string $url ) {
        $url = strtok( $url, '?' );  
        
        $home_url      = function_exists( 'home_url' ) ? home_url() : '';
        $relative_path = str_replace( $home_url, '', $url );
        $server_path   = defined( 'ABSPATH' ) ? ABSPATH . ltrim( $relative_path, '/' ) : '';

        if ( $server_path && file_exists( $server_path ) ) {
            return $server_path;
        }

        if ( defined( 'WP_CONTENT_URL' ) && defined( 'WP_CONTENT_DIR' ) && strpos( $url, WP_CONTENT_URL ) !== false ) {
            $relative_content = str_replace( WP_CONTENT_URL, '', $url );
            return WP_CONTENT_DIR . $relative_content;
        }

        return false;
    }

}