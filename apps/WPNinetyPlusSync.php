<?php 

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Plugin Name: WP Ninety Plus Sync Asset Optimizer
 * Description: Automatically strips non-critical head assets (CSS, JS, Inline styles/scripts, Preloads) and defers execution via hooks.
 * Version: 1.1.0
 */

class WPNinetyPlusSync {

    /**
     * Core handler to parse HTML, strip targets via hooks/keywords, and build the dynamic loader script.
     *
     * @param string $html
     * @return string
     */
    public function cured_assets_sync( $html ) {
        
        // Skip optimization in admin panels or feeds
        if ( is_admin() || is_feed() || is_trackback() ) {
            return $html;
        }

        $theme_uri = get_template_directory_uri();
        $version   = WPNINETYPLUS_VERSION ?? '1.0.0';

        $delayed_css          = array();
        $extracted_inline_css = array();
        $delayed_js           = array();
        $extracted_inline_js  = array();
        $extracted_preloads   = array();

        // 1. Fetch Keywords to Delay via Filter
        $keywords_to_delay = apply_filters( 'wpninetyplus_sync_assets', array() );

        // Process External Files (CSS & JS) based on Keywords
        if ( ! empty( $keywords_to_delay ) ) {
            foreach ( $keywords_to_delay as $keyword ) {
                // Regex for CSS (<link>)
                $pattern_css = '/<link\b[^>]*href\s*=\s*(["\'])([^"\']*' . preg_quote( $keyword, '/' ) . '[^"\']*)\1[^>]*>/i';
                if ( preg_match_all( $pattern_css, $html, $matches_css ) ) {
                    foreach ( $matches_css[2] as $index => $css_url ) {
                        $delayed_css[] = esc_url_raw( $css_url );
                        $html = str_replace( $matches_css[0][$index], '', $html );
                    }
                }

                // Regex for JS (<script>)
                $pattern_js = '/<script\b[^>]*src\s*=\s*(["\'])([^"\']*' . preg_quote( $keyword, '/' ) . '[^"\']*)\1[^>]*>[\s\S]*?<\/script>/i';
                if ( preg_match_all( $pattern_js, $html, $matches_js ) ) {
                    foreach ( $matches_js[2] as $index => $js_url ) {
                        $delayed_js[] = esc_url_raw( $js_url );
                        $html = str_replace( $matches_js[0][$index], '', $html );
                    }
                }
            }
        }

        // 2. Grab all preloads and preconnects automatically
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $extracted_preloads[] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // 2B. Font Preloads via Filter (Appends directly to <head>)
        $preload_block = apply_filters( 'wpninetyplus_sync_font_preloads', '', $theme_uri );
        if ( ! empty( $preload_block ) && false !== strpos( $html, '</head>' ) ) {
            $html = str_replace( '</head>', $preload_block . '</head>', $html );
        }

        // --- 3. BUILD THE OPTIMIZED FOOTER HIDDEN CONTAINER ---
        $assets_block  = "\n<!-- WPNinetyPlus Execution Block -->\n";
        $assets_block .= "<div id=\"wpninetyplus-delayed-assets\" style=\"display:none;\">\n";

        // A. External CSS URLs
        if ( ! empty( $delayed_css ) ) {
            foreach ( $delayed_css as $css_url ) {
                $assets_block .= '<link rel="stylesheet" data-delayed-href="' . esc_url( $css_url . '?ver=' . $version ) . '">' . "\n";
            }
        }

        // B. Preloads collected from head
        if ( ! empty( $extracted_preloads ) ) {
            $assets_block .= implode( "\n", $extracted_preloads ) . "\n";
        }

        // C. Inline CSS Blocks
        if ( ! empty( $extracted_inline_css ) ) {
            $assets_block .= implode( "\n", $extracted_inline_css ) . "\n";
        }

        // D. External JS URLs
        if ( ! empty( $delayed_js ) ) {
            foreach ( $delayed_js as $js_url ) {
                $assets_block .= '<script data-delayed-src="' . esc_url( $js_url ) . '" defer></script>' . "\n";
            }
        }

        // E. Inline JS Blocks
        if ( ! empty( $extracted_inline_js ) ) {
            $assets_block .= implode( "\n", $extracted_inline_js ) . "\n";
        }

        $assets_block .= "</div>\n";

        // --- 4. BUILD THE JAVASCRIPT INJECTION SCRIPT ---
        $assets_block .= '<script>';
        $assets_block .= 'window.addEventListener("load", function() {';
        
        // Loading Period Logic via Filter (Responsive Timeout)
        $default_loading_js = 'var loadingPeriod = window.innerWidth <= 680 ? 2000 : (window.innerWidth <= 2014 ? 2500 : 2000);';
        $loading_period_js  = apply_filters( 'wpninetyplus_sync_loading_period', $default_loading_js );
        
        $assets_block .= $loading_period_js;
        $assets_block .= 'setTimeout(function() {';

        // Conditional Asset Loading & Custom JS Snippets via Filter
        $conditional_logic_block = apply_filters( 'wpninetyplus_sync_assets_conditional', '', $theme_uri, $version );
        $assets_block .= $conditional_logic_block;

        // Inject Intercepted Delayed Styles from Container
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets link[data-delayed-href]").forEach(function(b){';
        $assets_block .= '  var c = document.createElement("link"); c.rel = "stylesheet"; c.href = b.getAttribute("data-delayed-href"); document.head.appendChild(c);';
        $assets_block .= '});';

        // Inject Intercepted Delayed Scripts from Container
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets script[data-delayed-src]").forEach(function(b){';
        $assets_block .= '  var c = document.createElement("script"); c.src = b.getAttribute("data-delayed-src"); c.defer = true; document.body.appendChild(c);';
        $assets_block .= '});';

        // Temporary Class Removals via Filter (Prevents FOUC / layout shifts)
        $selectors_to_clean = apply_filters( 'wpninetyplus_sync_temporary', array() );
        if ( ! empty( $selectors_to_clean ) ) {
            $selectors_json = json_encode( $selectors_to_clean );
            $assets_block .= ' ' . $selectors_json . '.forEach(selector => {';
            $assets_block .= '    document.querySelectorAll(selector).forEach(element => {';
            $assets_block .= '        element.classList.remove("r-temporary");';
            $assets_block .= '    });';
            $assets_block .= '});';
        }

        // Clean up temporary container element
        $assets_block .= 'var d = document.getElementById("wpninetyplus-delayed-assets"); if(d) d.remove();';

        $assets_block .= '}, loadingPeriod);'; 
        $assets_block .= '});';
        $assets_block .= '</script>';

        // --- 5. INJECT BEFORE </body> ---
        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $assets_block . "\n</body>", $html );
        } else {
            $html .= $assets_block; 
        }

        return $html;
    }
}
