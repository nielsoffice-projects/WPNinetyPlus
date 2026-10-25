<?php
/**
 * Plugin Name: WPNinetyPlus InteractiveMerge Core
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
 * Class InteractiveMerge
 * 
 * Handles finding, reading, fetching CDN, and merging local JS, remote CDN JS, 
 * and inline JS blocks (by ID) into an optimized inline script block.
 *
 * @since 1.0.0
 */
class InteractiveMergeExe {

    /**
     * Finds target external, CDN, and inline script tags in the HTML buffer, 
     * combines them, and injects them right before the closing body tag.
     *
     * @since 1.0.0
     * @param string $buffer The raw HTML output buffer.
     * @return string        The modified HTML buffer with merged inline scripts.
     */
    public function interactive_merge( string $buffer ) : string {

        if ( empty( $buffer ) ) {
            return $buffer;
        }

        // Find all script tags in the buffer (captures attributes and inner content).
        preg_match_all( '/<script\b([^>]*)>([\s\S]*?)<\/script>/i', $buffer, $script_matches, PREG_SET_ORDER );

        if ( empty( $script_matches ) ) {
            return $buffer;
        }

        /**
         * Filters the list of target scripts to be merged.
         * Supports local filenames, full CDN URLs/keywords, or inline script IDs.
         *
         * @since 1.0.0
         * @param array Default target script identifiers.
         */
        $target_scripts = apply_filters( 'wpninetyplus_merge_script', [] );

        if ( empty( $target_scripts ) ) {
            return $buffer;
        }

        $combined_js       = '';
        $scripts_to_remove = [];

        foreach ( $script_matches as $match ) {
            $full_tag    = $match[0];
            $attributes  = $match[1];
            $inline_code = $match[2];

            $is_target  = false;
            $js_content = '';

            // 1. Check if it's an external or local script with a 'src' attribute
            if ( preg_match( '/src=["\']([^"\']+)["\']/i', $attributes, $src_match ) ) {
                $js_url = $src_match[1];

                foreach ( $target_scripts as $target ) {
                    if ( ! empty( $target ) && strpos( $js_url, $target ) !== false ) {
                        $is_target = true;
                        break;
                    }
                }

                if ( $is_target ) {
                    if ( preg_match( '/^https?:\/\//i', $js_url ) ) {
                        $js_content = $this->fetch_cdn_js( $js_url );
                    } else {
                        $file_path = $this->skeleton_url_to_path( $js_url );
                        if ( $file_path && file_exists( $file_path ) ) {
                            $js_content = file_get_contents( $file_path );
                        }
                    }
                }
            } 
            // 2. Check if it's an inline script matched by its 'id' attribute
            elseif ( preg_match( '/id=["\']([^"\']+)["\']/i', $attributes, $id_match ) ) {
                $script_id = $id_match[1];

                foreach ( $target_scripts as $target ) {
                    if ( ! empty( $target ) && $script_id === $target ) {
                        $is_target = true;
                        break;
                    }
                }

                if ( $is_target ) {
                    $js_content = $inline_code;
                }
            }

            // If matched and content exists, append and queue original tag for removal
            if ( $is_target && ! empty( trim( $js_content ) ) ) {
                $combined_js       .= "\n" . trim( $js_content ) . ";\n";
                $scripts_to_remove[] = $full_tag;
            }
        }

        // If scripts were successfully combined, inject them into the HTML document.
        if ( ! empty( $combined_js ) ) {
            $inline_script_tag = '<script id="WPNinetyPlusMergeJS">' . $combined_js . '</script>';
            
            // 1. Remove the original individual script tags from the buffer.
            if ( ! empty( $scripts_to_remove ) ) {
                $buffer = str_replace( $scripts_to_remove, '', $buffer );
            }

            // 2. Inject the merged script block right before </body> (standard JS practice).
            if ( false !== stripos( $buffer, '</body>' ) ) {
                $buffer = str_ireplace( '</body>', $inline_script_tag . '</body>', $buffer );
            } else {
                $buffer .= $inline_script_tag;
            }
        }

        return $buffer;
    }

    /**
     * Safely fetches remote CDN JavaScript using WordPress HTTP API with transient caching.
     *
     * @since 1.0.0
     * @param string $url The CDN URL.
     * @return string     The raw JS content or empty string on failure.
     */
    private function fetch_cdn_js( string $url ) : string {
        $clean_url     = strtok( $url, '?' );
        $transient_key = 'wp90_cdn_js_' . md5( $clean_url );
        
        $cached_js = get_transient( $transient_key );
        if ( false !== $cached_js ) {
            return $cached_js;
        }

        $response = wp_remote_get( $url, [
            'timeout'    => 5,
            'user-agent' => 'WPNinetyPlus-Optimizer/1.0',
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return '';
        }

        $js_content = wp_remote_retrieve_body( $response );
        if ( ! empty( $js_content ) ) {
            set_transient( $transient_key, $js_content, 12 * HOUR_IN_SECONDS );
        }

        return $js_content;
    }

    /**
     * Helper method to convert a local asset URL to an absolute server file path.
     *
     * @since 1.0.0
     * @param string $url The script URL.
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

        if ( defined( 'WP_CONTENT_URL' ) && defined( 'WP_CONTENT_DIR' ) && strpos( $url, WP_CONTENT_URL ) !== false ) {
            $relative_content = str_replace( WP_CONTENT_URL, '', $url );
            return WP_CONTENT_DIR . $relative_content;
        }

        return false;
    }
}
