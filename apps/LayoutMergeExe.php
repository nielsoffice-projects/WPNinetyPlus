<?php
/**
 * Plugin Name: WPNinetyPlus LayoutMergeExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured CSS Optimized static cross pages and devices cured html!
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class LayoutMergeExe
 * 
 * Handles finding, reading, minifying, and merging external & local stylesheets 
 * into an optimized inline style block within the HTML head.
 *
 * @since 1.0.0
 */
class LayoutMergeExe {

    /**
     * Finds target external and CDN stylesheets in the HTML buffer, combines them, 
     * minifies the CSS, and injects them into the document header.
     *
     * @since 1.0.0
     * @param string $buffer The raw HTML output buffer.
     * @return string        The modified HTML buffer with merged inline styles.
     */
    public function layout_merge_one( string $buffer ) : string {

        if ( empty( $buffer ) ) {
            return $buffer;
        }

        // Find all stylesheet link tags automatically in the buffer (handles standard stylesheet and preload/onload patterns).
        preg_match_all( '/<link\b[^>]*\b(?:rel=["\'](?:stylesheet|preload)["\']|as=["\']style["\'])[^>]*>/i', $buffer, $link_matches );

        if ( empty( $link_matches[0] ) ) {
            // Fallback to broader link grab if specific rel/as combination misses anything
            preg_match_all( '/<link\b[^>]*\bhref=["\'][^"\']+\.css[^"\']*["\'][^>]*>/i', $buffer, $link_matches );
        }

        if ( empty( $link_matches[0] ) ) {
            return $buffer;
        }

        /**
         * Filters the list of target stylesheets to be merged (supports local filenames or full CDN URLs/keywords).
         *
         * @since 1.0.0
         * @param array Default target stylesheet identifiers.
         */
        $target_stylesheets = apply_filters( 'wpninetyplus_merge_style', [
            'style.css',
        ] );

        $combined_css    = '';
        $links_to_remove = [];

        // Iterate through all discovered stylesheet link tags.
        foreach ( $link_matches[0] as $link_tag ) {
        
            // Extract the 'href' attribute value from the link tag.
            preg_match( '/href=["\']([^"\']+)["\']/i', $link_tag, $href_match );
            if ( empty( $href_match[1] ) ) {
                continue;
            }

            $css_url   = $href_match[1];
            $is_target = false;

            // Check if the current stylesheet URL matches any targets in our array (supports partial filenames or full CDN strings).
            foreach ( $target_stylesheets as $target_file ) {
                if ( ! empty( $target_file ) && strpos( $css_url, $target_file ) !== false ) {
                    $is_target = true;
                    break;
                }
            }

            if ( ! $is_target ) {
                continue; 
            }

            $css_content = '';

            // Check if it's an external CDN link or a local asset path
            if ( preg_match( '/^https?:\/\//i', $css_url ) ) {
                $css_content = $this->fetch_cdn_css( $css_url );
            } else {
                $file_path = $this->skeleton_url_to_path( $css_url );
                if ( $file_path && file_exists( $file_path ) ) {
                    $css_content = file_get_contents( $file_path );
                }
            }
            
            if ( ! empty( $css_content ) ) {
                // 1. Remove all CSS comments (/* ... */).
                $css_content = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_content );
                
                // 2. Remove unnecessary spaces around selectors, braces, colons, semicolons, and commas.
                $css_content = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css_content );
                
                // 3. Collapse remaining multiple spaces and newlines into a single line.
                $css_content = trim( preg_replace( '/\s+/', ' ', $css_content ) );
            
                // Append minified CSS content and track the link tag for removal.
                $combined_css     .= $css_content;
                $links_to_remove[] = $link_tag;
            }
        }

        // If stylesheets were successfully combined, inject them into the HTML head.
        if ( ! empty( $combined_css ) ) {
            $inline_style_tag = '<style id="WPNinetyPlusMerge">' . '.wpninetyplus-lframe{background-color:#fff!important}.wpninetyplus-lframe *{color:transparent!important}.wpninetyplus-lframe img{opacity:0}.wpninetyplus-lframe .examinest-sk-frame{position:relative;background:#e2e8f0;border-radius:6px;overflow:hidden}.wpninetyplus-lframe .wpninetyplus-sk-frame::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#e2e8f0 25%,#f8fafc 50%,#e2e8f0 75%);background-size:200% 100%;animation:examinest-shimmer 1.4s infinite linear}@keyframes examinest-shimmer{100%{background-position:-200% 0}}.r-temporary{display:none!important}' . $combined_css . '</style>';
            
            // 1. Remove the original individual link tags from the HTML buffer.
            if ( ! empty( $links_to_remove ) ) {
                $buffer = str_replace( $links_to_remove, '', $buffer );
            }

            // 2. Locate the closing </title> tag and inject the merged style right after it.
            if ( preg_match( '/<\/title>/i', $buffer, $title_match, PREG_OFFSET_CAPTURE ) ) {
                $insert_offset = $title_match[0][1] + strlen( $title_match[0][0] );
                $buffer        = substr_replace( $buffer, "\n" . $inline_style_tag, $insert_offset, 0 );
            } else {
                // Fallback: inject right before </head> if no title tag exists.
                $buffer = str_ireplace( '</head>', $inline_style_tag . '</head>', $buffer );
            }
        }

        return $buffer;
    }

    /**
     * Safely fetches remote CDN CSS using WordPress HTTP API with transient caching.
     *
     * @since 1.0.0
     * @param string $url The CDN URL.
     * @return string     The raw CSS content or empty string on failure.
     */
    private function fetch_cdn_css( string $url ) : string {
        $clean_url  = strtok( $url, '?' );
        $transient_key = 'wp90_cdn_' . md5( $clean_url );
        
        // Cache CDN requests for 12 hours to prevent slow page load timeouts
        $cached_css = get_transient( $transient_key );
        if ( false !== $cached_css ) {
            return $cached_css;
        }

        $response = wp_remote_get( $url, [
            'timeout'     => 5,
            'user-agent'  => 'WPNinetyPlus-Optimizer/1.0',
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return '';
        }

        $css_content = wp_remote_retrieve_body( $response );
        if ( ! empty( $css_content ) ) {
            set_transient( $transient_key, $css_content, 12 * HOUR_IN_SECONDS );
        }

        return $css_content;
    }

    /**
     * Helper method to convert a local asset URL to an absolute server file path.
     * Removes any query strings or version parameters automatically.
     *
     * @since 1.0.0
     * @param string $url The stylesheet URL.
     * @return string|false Absolute server file path, or false on failure.
     */
    public function skeleton_url_to_path( string $url ) {
        $url = strtok( $url, '?' );  
        
        $home_url      = function_exists( 'home_url' ) ? home_url() : '';
        $relative_path = str_replace( $home_url, '', $url );
        $server_path   = defined( 'ABSPATH' ) ? ABSPATH . ltrim( $relative_path, '/' ) : '';

        if ( $server_path && file_exists( $server_path ) ) {
            return $server_path;
        }

        // Fallback check for custom content directories (WP_CONTENT_URL).
        if ( defined( 'WP_CONTENT_URL' ) && defined( 'WP_CONTENT_DIR' ) && strpos( $url, WP_CONTENT_URL ) !== false ) {
            $relative_content = str_replace( WP_CONTENT_URL, '', $url );
            return WP_CONTENT_DIR . $relative_content;
        }

        return false;
    }
}
