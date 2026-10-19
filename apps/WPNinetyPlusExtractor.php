<?php 
/**
 * Plugin Name: WP Ninety Plus Asset Optimizer
 * Description: Automatically strips non-critical head assets, defers them, and supports optimized preload/noscript patterns including CDN, third-party scripts, and custom disregard filters.
 * Version:     1.0.6
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/
 */

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Class WPNinetyPlusExtractor
 * 
 * Specialized parsing engine responsible for identifying, filtering, 
 * and extracting non-critical CSS, scripts, preloads, and inline styles from HTML,
 * while honoring user-defined disregard filters.
 *
 * @since 1.0.6
 */
class WPNinetyPlusExtractor {

    /**
     * Extracts and strips non-critical assets from the provided HTML reference.
     *
     * @since 1.0.6
     * @param string $html Reference to the current page HTML markup.
     * @return array Associative array containing categorized extracted assets.
     */
    public function extract_assets( &$html ) {
        // Initialize the master array to hold various types of extracted assets
        $extracted = array(
            'css'          => array(),
            'inline_css'   => array(),
            'js'           => array(),
            'inline_js'    => array(),
            'preloads'     => array(),
        );

        // Fetch user-defined keywords/assets that should be ignored or skipped by the optimizer via filter
        $disregarded_assets = apply_filters( 'wpninetyplus_disregard_assets', array() );

        // Define a closure helper to check if a specific tag or URL matches any disregard rules
        $is_disregarded = function( $tag, $url = '' ) use ( $disregarded_assets ) {
            // Return false early if no disregard rules are set or if the input isn't a valid array
            if ( empty( $disregarded_assets ) || ! is_array( $disregarded_assets ) ) {
                return false;
            }
            // Loop through each user-configured disregard keyword
            foreach ( $disregarded_assets as $keyword ) {
                if ( ! empty( $keyword ) ) {
                    // Check if the keyword exists inside the HTML tag string or the target URL string
                    if ( false !== strpos( $tag, $keyword ) || ( ! empty( $url ) && false !== strpos( $url, $keyword ) ) ) {
                        return true;
                    }
                }
            }
            return false;
        };

        // ==========================================
        // 1. EXTERNAL & CDN CSS STYLESHEETS
        // ==========================================
        // Regular expression pattern to match standard external stylesheet link tags
        $pattern_css      = '/<link\b[^>]*rel\s*=\s*(["\'])stylesheet\1[^>]*>/i';
        // Define specific stylesheet IDs that must be excluded from being stripped/deferred
        $excluded_css_ids = array( 'layout', 'WPNinetyPlusMerge' );

        // Perform global regex match to locate all matching stylesheet tags in the HTML
        if ( preg_match_all( $pattern_css, $html, $matches_css ) ) {
            foreach ( $matches_css[0] as $full_tag ) {
                $css_url      = '';
                $css_basename = '';
                
                // Extract the href URL value from the stylesheet tag
                if ( preg_match( '/href\s*=\s*(["\'])([^"\']+)\1/i', $full_tag, $url_match ) ) {
                    $css_url      = $url_match[2];
                    $parsed_url   = parse_url( $css_url );
                    // Extract the base filename to check against blacklists
                    $css_basename = isset( $parsed_url['path'] ) ? basename( $parsed_url['path'] ) : '';
                }

                // Skip processing if the file matches 'style.css' or triggers user disregard filters
                if ( 'style.css' === $css_basename || $is_disregarded( $full_tag, $css_url ) ) {
                    continue;
                }

                $skip = false;
                // Check if the stylesheet tag contains any of the protected ID attributes
                foreach ( $excluded_css_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                // If not skipped, store the tag into the extracted array and remove it from original HTML
                if ( ! $skip ) {
                    $extracted['css'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // ==========================================
        // 2. RESOURCE PRELOADS & PRECONNECTS
        // ==========================================
        // Regex pattern to match resource hints like rel="preload" or rel="preconnect"
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $preload_url = '';
                // Extract target href URL from the preload/preconnect link tag
                if ( preg_match( '/href\s*=\s*(["\'])([^"\']+)\1/i', $tag, $url_match ) ) {
                    $preload_url = $url_match[2];
                }

                // Skip if matching user disregard filters
                if ( $is_disregarded( $tag, $preload_url ) ) {
                    continue;
                }

                // Store preloads separately and strip them from the page markup
                $extracted['preloads'][] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // ==========================================
        // 3. INLINE CSS STYLE BLOCKS
        // ==========================================
        // Regex pattern to match inline <style> elements and capture their inner contents
        $pattern_style      = '/<style\b[^>]*>([\s\S]*?)<\/style>/i';
        $excluded_style_ids = array( 'layout', 'WPNinetyPlusMerge' );

        if ( preg_match_all( $pattern_style, $html, $matches_style ) ) {
            foreach ( $matches_style[0] as $index => $full_tag ) {
                // Skip if matching user disregard rules
                if ( $is_disregarded( $full_tag ) ) {
                    continue;
                }

                $skip = false;
                // Check for protected style element IDs
                foreach ( $excluded_style_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }
                
                // Add eligible inline styles to extraction array and remove from HTML
                if ( ! $skip ) {
                    $extracted['inline_css'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // ==========================================
        // 4. EXTERNAL & CDN JAVASCRIPT FILES
        // ==========================================
        // Regex pattern to identify external JavaScript files containing a src attribute
        $pattern_script      = '/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>[\s\S]*?<\/script>/i';
        $excluded_script_ids = array( 'interactive', 'WPNinetyPlusInteractive' );

        if ( preg_match_all( $pattern_script, $html, $matches_script ) ) {
            foreach ( $matches_script[0] as $index => $full_tag ) {
                $js_url       = $matches_script[2][$index];
                $parsed_url   = parse_url( $js_url );
                // Extract filename to check against explicit exclusion rules
                $js_basename  = isset( $parsed_url['path'] ) ? basename( $parsed_url['path'] ) : '';

                // Skip vanilla.js or any script matching global disregard settings
                if ( 'vanilla.js' === $js_basename || $is_disregarded( $full_tag, $js_url ) ) {
                    continue;
                }

                $skip = false;
                // Check if the script tag has protected ID attributes
                foreach ( $excluded_script_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                // Extract valid script tags and remove them from the original HTML string
                if ( ! $skip ) {
                    $extracted['js'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // ==========================================
        // 5. INLINE JAVASCRIPT BLOCKS
        // ==========================================
        // Regex pattern to match inline script blocks that do not possess a src attribute
        $pattern_inline_js = '/<script\b[^>]*>(?![\s\S]*src=)([\s\S]*?)<\/script>/i';
        if ( preg_match_all( $pattern_inline_js, $html, $matches_inline_js ) ) {
            foreach ( $matches_inline_js[0] as $index => $full_tag ) {
                // Skip if matching user disregard filters
                if ( $is_disregarded( $full_tag ) ) {
                    continue;
                }

                // Skip blocks designated with the interactive ID attribute
                if ( false !== strpos( $full_tag, 'id="interactive"' ) || false !== strpos( $full_tag, "id='interactive'" ) ) {
                    continue;
                }

                // Append valid inline script tags to extraction list and clear from HTML output
                $extracted['inline_js'][] = $full_tag; 
                $html = str_replace( $full_tag, '', $html );
            }
        }

        // Return the structured array containing all parsed and categorized asset nodes
        return $extracted;
    }
}