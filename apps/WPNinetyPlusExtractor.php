<?php
/**
 * Plugin Name: WP Ninety Plus Asset Optimizer
 * Description: Automatically strips non-critical head assets, defers them, and supports optimized preload/noscript patterns.
 * Version:     1.0.3
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/
 */

namespace WPNinetyPlus;


if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Class WPNinetyPlusAssetExtractor
 * 
 * Specialized parsing engine responsible for identifying, filtering, 
 * and extracting non-critical CSS, scripts, preloads, and inline styles from HTML.
 *
 * @since 1.0.3
 */
class WPNinetyPlusExtractor {

/**
     * Parses the HTML buffer and extracts non-critical assets while keeping critical references intact.
     *
     * @since 1.0.3
     * @param string $html The raw HTML output buffer passed by reference.
     * @return array       An associative array containing all categorized extracted assets.
     */
    public function extract_assets( &$html ) {
        $extracted = array(
            'css'          => array(),
            'inline_css'   => array(),
            'js'           => array(),
            'inline_js'    => array(),
            'preloads'     => array(),
        );

        // 1. Identify and extract non-critical external stylesheet link tags.
        // Skips the core 'style.css' file and any explicitly excluded layout asset IDs.
        $pattern_css      = '/<link\b[^>]*rel\s*=\s*(["\'])stylesheet\1[^>]*>/i';
        $excluded_css_ids = array( 'layout', 'WPNinetyPlusMerge' );

        if ( preg_match_all( $pattern_css, $html, $matches_css ) ) {
            foreach ( $matches_css[0] as $full_tag ) {
                $css_basename = '';
                if ( preg_match( '/href\s*=\s*(["\'])([^"\']+)\1/i', $full_tag, $url_match ) ) {
                    $css_basename = basename( parse_url( $url_match[2], PHP_URL_PATH ) );
                }

                if ( 'style.css' === $css_basename ) {
                    continue;
                }

                $skip = false;
                foreach ( $excluded_css_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                if ( ! $skip ) {
                    $extracted['css'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 2. Scan and extract all early link preloads and preconnect resource hints.
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $extracted['preloads'][] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // 3. Scan and extract inline <style> blocks from the document head.
        // Ignores critical styling blocks matching designated exclusion IDs.
        $pattern_style      = '/<style\b[^>]*>([\s\S]*?)<\/style>/i';
        $excluded_style_ids = array( 'layout', 'WPNinetyPlusMerge' );

        if ( preg_match_all( $pattern_style, $html, $matches_style ) ) {
            foreach ( $matches_style[0] as $index => $full_tag ) {
                $skip = false;
                foreach ( $excluded_style_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }
                
                if ( ! $skip ) {
                    $extracted['inline_css'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 4. Scan and extract external JavaScript files.
        // Protects core operational scripts like 'vanilla.js' and specified interactive IDs.
        $pattern_script      = '/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>[\s\S]*?<\/script>/i';
        $excluded_script_ids = array( 'interactive', 'WPNinetyPlusInteractive' );

        if ( preg_match_all( $pattern_script, $html, $matches_script ) ) {
            foreach ( $matches_script[0] as $index => $full_tag ) {
                $js_url      = $matches_script[2][$index];
                $js_basename = basename( parse_url( $js_url, PHP_URL_PATH ) );

                if ( 'vanilla.js' === $js_basename ) {
                    continue;
                }

                $skip = false;
                foreach ( $excluded_script_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                if ( ! $skip ) {
                    $extracted['js'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 5. Scan and extract inline JavaScript blocks, explicitly bypassing interactive elements.
        $pattern_inline_js = '/<script\b[^>]*>(?![\s\S]*src=)([\s\S]*?)<\/script>/i';
        if ( preg_match_all( $pattern_inline_js, $html, $matches_inline_js ) ) {
            foreach ( $matches_inline_js[0] as $index => $full_tag ) {
                if ( false !== strpos( $full_tag, 'id="interactive"' ) || false !== strpos( $full_tag, "id='interactive'" ) ) {
                    continue;
                }

                $extracted['inline_js'][] = $full_tag; 
                $html = str_replace( $full_tag, '', $html );
            }
        }

        return $extracted;
    }
}

/**
 * Class WPNinetyPlusAssetManager
 * 
 * Orchestration controller that coordinates asset extraction, constructs the hidden 
 * deferred asset container block, manages dynamic device-based loading timers via 
 * filter hooks, and injects the final optimized payload right before the closing HTML body tag.
 *
 * @since 1.0.3
 */
class WPNinetyPlusAssetManager {

/**
     * Parses the HTML buffer and extracts non-critical assets while keeping critical references intact.
     *
     * @since 1.0.3
     * @param string $html The raw HTML output buffer passed by reference.
     * @return array       An associative array containing all categorized extracted assets.
     */
    public function extract_assets( &$html ) {
        $extracted = array(
            'css'          => array(),
            'inline_css'   => array(),
            'js'           => array(),
            'inline_js'    => array(),
            'preloads'     => array(),
        );

        // 1. Identify and extract non-critical external stylesheet link tags.
        // Skips the core 'style.css' file and any explicitly excluded layout asset IDs.
        $pattern_css      = '/<link\b[^>]*rel\s*=\s*(["\'])stylesheet\1[^>]*>/i';
        $excluded_css_ids = array( 'layout', 'WPNinetyPlusMerge' );

        if ( preg_match_all( $pattern_css, $html, $matches_css ) ) {
            foreach ( $matches_css[0] as $full_tag ) {
                $css_basename = '';
                if ( preg_match( '/href\s*=\s*(["\'])([^"\']+)\1/i', $full_tag, $url_match ) ) {
                    $css_basename = basename( parse_url( $url_match[2], PHP_URL_PATH ) );
                }

                if ( 'style.css' === $css_basename ) {
                    continue;
                }

                $skip = false;
                foreach ( $excluded_css_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                if ( ! $skip ) {
                    $extracted['css'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 2. Scan and extract all early link preloads and preconnect resource hints.
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $extracted['preloads'][] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // 3. Scan and extract inline <style> blocks from the document head.
        // Ignores critical styling blocks matching designated exclusion IDs.
        $pattern_style      = '/<style\b[^>]*>([\s\S]*?)<\/style>/i';
        $excluded_style_ids = array( 'layout', 'WPNinetyPlusMerge' );

        if ( preg_match_all( $pattern_style, $html, $matches_style ) ) {
            foreach ( $matches_style[0] as $index => $full_tag ) {
                $skip = false;
                foreach ( $excluded_style_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }
                
                if ( ! $skip ) {
                    $extracted['inline_css'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 4. Scan and extract external JavaScript files.
        // Protects core operational scripts like 'vanilla.js' and specified interactive IDs.
        $pattern_script      = '/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>[\s\S]*?<\/script>/i';
        $excluded_script_ids = array( 'interactive', 'WPNinetyPlusInteractive' );

        if ( preg_match_all( $pattern_script, $html, $matches_script ) ) {
            foreach ( $matches_script[0] as $index => $full_tag ) {
                $js_url      = $matches_script[2][$index];
                $js_basename = basename( parse_url( $js_url, PHP_URL_PATH ) );

                if ( 'vanilla.js' === $js_basename ) {
                    continue;
                }

                $skip = false;
                foreach ( $excluded_script_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                if ( ! $skip ) {
                    $extracted['js'][] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 5. Scan and extract inline JavaScript blocks, explicitly bypassing interactive elements.
        $pattern_inline_js = '/<script\b[^>]*>(?![\s\S]*src=)([\s\S]*?)<\/script>/i';
        if ( preg_match_all( $pattern_inline_js, $html, $matches_inline_js ) ) {
            foreach ( $matches_inline_js[0] as $index => $full_tag ) {
                if ( false !== strpos( $full_tag, 'id="interactive"' ) || false !== strpos( $full_tag, "id='interactive'" ) ) {
                    continue;
                }

                $extracted['inline_js'][] = $full_tag; 
                $html = str_replace( $full_tag, '', $html );
            }
        }

        return $extracted;
    }
}