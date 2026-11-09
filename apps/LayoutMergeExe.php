<?php
/**
 * Plugin Name: WPNinetyPlus LayoutMergeExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.3
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
 * @since 1.0.3
 */
class LayoutMergeExe {

    /**
     * Finds target external and CDN stylesheets in the HTML buffer, combines them, 
     * minifies the CSS, and injects them into the document header.
     *
     * @since 1.0.3
     * @param string $buffer The raw HTML output buffer.
     * @return string        The modified HTML buffer with merged inline styles.
     */
    public function layout_merge( string $buffer ) : string {
        try {
            if ( empty( $buffer ) ) {
                return $buffer;
            }

            // Prevent running multiple times if already merged
            if ( strpos( $buffer, 'id="WPNinetyPlusMerge"' ) !== false ) {
                return $buffer;
            }

            // Find all stylesheet link tags automatically in the buffer
            preg_match_all( '/<link\b[^>]*\b(?:rel=["\'](?:stylesheet|preload)["\']|as=["\']style["\'])[^>]*>/i', $buffer, $link_matches );

            if ( empty( $link_matches[0] ) ) {
                preg_match_all( '/<link\b[^>]*\bhref=["\'][^"\']+\.css[^"\']*["\'][^>]*>/i', $buffer, $link_matches );
            }

            if ( empty( $link_matches[0] ) ) {
                return $buffer;
            }

            $target_stylesheets = apply_filters( 'wpninetyplus_merge_style', [
                'style.css',
            ] );

            $combined_css    = '';
            $links_to_remove = [];

            foreach ( $link_matches[0] as $link_tag ) {
                // PROTECTION: Never touch or remove anything containing WPNinetyPlusInlineCSSMerge
                if ( strpos( $link_tag, 'WPNinetyPlusInlineCSSMerge' ) !== false ) {
                    continue;
                }

                preg_match( '/href=["\']([^"\']+)["\']/i', $link_tag, $href_match );
                if ( empty( $href_match[1] ) ) {
                    continue;
                }

                $css_url   = $href_match[1];
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

                $css_content = '';

                if ( preg_match( '/^https?:\/\//i', $css_url ) || strpos( $css_url, '//' ) === 0 ) {
                    if ( strpos( $css_url, '//' ) === 0 && ! preg_match( '/^https?:\/\//i', $css_url ) ) {
                        $css_url = ( is_ssl() ? 'https:' : 'http:' ) . $css_url;
                    }
                    $css_content = $this->fetch_cdn_css( $css_url );
                } else {
                    $file_path = $this->skeleton_url_to_path( $css_url );
                    if ( $file_path && file_exists( $file_path ) && is_readable( $file_path ) ) {
                        $css_content = file_get_contents( $file_path );
                    }
                }
                
                if ( ! empty( $css_content ) && is_string( $css_content ) ) {
                    // 1. Safe non-backtracking CSS comment stripper
                    $css_content = preg_replace( '/\/\*[\s\S]*?\*\//', '', $css_content );
                    
                    // 2. Safe spacing normalization around syntax selectors
                    $css_content = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css_content );
                    
                    // 3. Collapse multiple spaces and newlines
                    $css_content = trim( preg_replace( '/\s+/', ' ', $css_content ) );
                
                    $combined_css      .= $css_content;
                    $links_to_remove[] = $link_tag;
                }
            }

            if ( ! empty( $combined_css ) ) {
                // Restored original ID: WPNinetyPlusMerge
                $inline_style_tag = '<style id="WPNinetyPlusMerge">' . '.wpninetyplus-lframe{background-color:#fff!important}.wpninetyplus-lframe *{color:transparent!important}.wpninetyplus-lframe img{opacity:0}.wpninetyplus-lframe .examinest-sk-frame{position:relative;background:#e2e8f0;border-radius:6px;overflow:hidden}.wpninetyplus-lframe .wpninetyplus-sk-frame::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#e2e8f0 25%,#f8fafc 50%,#e2e8f0 75%);background-size:200% 100%;animation:examinest-shimmer 1.4s infinite linear}@keyframes examinest-shimmer{100%{background-position:-200% 0}}.r-temporary{display:none!important}' . $combined_css . '</style>';
                
                if ( ! empty( $links_to_remove ) ) {
                    $buffer = str_replace( $links_to_remove, '', $buffer );
                }

                if ( preg_match( '/<\/title>/i', $buffer, $title_match, PREG_OFFSET_CAPTURE ) ) {
                    $insert_offset = $title_match[0][1] + strlen( $title_match[0][0] );
                    $buffer        = substr_replace( $buffer, "\n" . $inline_style_tag, $insert_offset, 0 );
                } else {
                    $buffer = str_ireplace( '</head>', $inline_style_tag . '</head>', $buffer );
                }
            }

            return $buffer;

        } catch ( \Throwable $e ) {
            return $buffer;
        }
    }

    /**
     * Safely fetches remote CDN CSS using WordPress HTTP API with transient caching.
     *
     * @since 1.0.3
     * @param string $url The CDN URL.
     * @return string     The raw CSS content or empty string on failure.
     */
    private function fetch_cdn_css( string $url ) : string {
        $clean_url     = strtok( $url, '?' );
        $transient_key = 'wp90_cdn_' . md5( $clean_url );
        
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
     *
     * @since 1.0.3
     * @param string $url The stylesheet URL.
     * @return string|false Absolute server file path, or false on failure.
     */
    public function skeleton_url_to_path( string $url ) {
        $url = strtok( $url, '?' );  
        
        if ( defined( 'WP_CONTENT_URL' ) && defined( 'WP_CONTENT_DIR' ) && strpos( $url, WP_CONTENT_URL ) !== false ) {
            $relative_content = str_replace( WP_CONTENT_URL, '', $url );
            $server_path      = WP_CONTENT_DIR . $relative_content;
            if ( file_exists( $server_path ) ) {
                return $server_path;
            }
        }

        $home_url = function_exists( 'home_url' ) ? home_url() : '';
        if ( ! empty( $home_url ) ) {
            $relative_path = str_replace( $home_url, '', $url );
            $server_path   = defined( 'ABSPATH' ) ? ABSPATH . ltrim( $relative_path, '/' ) : '';
            if ( file_exists( $server_path ) ) {
                return $server_path;
            }
        }

        if ( defined( 'ABSPATH' ) && strpos( $url, 'wp-content' ) !== false ) {
            $parsed_path = parse_url( $url, PHP_URL_PATH );
            if ( $parsed_path ) {
                $server_path = ABSPATH . ltrim( $parsed_path, '/' );
                if ( file_exists( $server_path ) ) {
                    return $server_path;
                }
            }
        }

        return false;
    }
}