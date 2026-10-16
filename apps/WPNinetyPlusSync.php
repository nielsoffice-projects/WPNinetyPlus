<?php 

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Plugin Name: WP Ninety Plus Sync Asset Optimizer
 * Description: Automatically strips non-critical head assets, preloads fonts, and defers execution via hooks.
 * Version: 1.0.0
 */


class WPNinetyPlusSync {

    /**
     * Core handler to parse HTML, strip targets via hooks, and build the dynamic loader script.
     *
     * @param string $html
     * @return string
     */
    public function cured_assets_sync( $html ) {
        
        $theme_uri = get_template_directory_uri();
        $version   = WPNINETYPLUS_VERSION ?? '1.0.0';
        $assets_block = '';

        // 1. Fetch Keywords to Delay via Filter
        $keywords_to_delay = apply_filters( 'wpninetyplus_sync_assets', array() );

        $delayed_css = array();
        $delayed_js  = array();

        // Process External Files (CSS & JS)
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

        // 1B. Font Preloads via Filter
        $preload_block = apply_filters( 'wpninetyplus_sync_font_preloads', '', $theme_uri );
        
        if ( ! empty( $preload_block ) && false !== strpos( $html, '</head>' ) ) {
            $html = str_replace( '</head>', $preload_block . '</head>', $html );
        }

        // 2. BUILD THE JAVASCRIPT INJECTION BLOCK
        $assets_block .= '<script>';
        $assets_block .= 'window.addEventListener("load", function() {';
        
        // Loading Period Logic via Filter
        $loading_period_js = apply_filters( 'wpninetyplus_sync_loading_period', '' );
        $assets_block .= $loading_period_js;
        
        $assets_block .= 'setTimeout(function() {';

        // Conditional Asset Loading & Custom JS Snippets via Filter
        $conditional_logic_block = apply_filters( 'wpninetyplus_sync_assets_conditional', '', $theme_uri, $version );
        $assets_block .= $conditional_logic_block;

        // Inject Intercepted Core/Plugin Scripts
        foreach ( $delayed_js as $d_js ) {
            $assets_block .= 'var dJs = document.createElement("script"); dJs.src = "' . $d_js . '"; document.body.appendChild(dJs);';
        }

        // Inject Intercepted Plugin Styles
        foreach ( $delayed_css as $d_css ) {
            $assets_block .= 'var dCss = document.createElement("link"); dCss.rel = "stylesheet"; dCss.href = "' . $d_css . '"; document.head.appendChild(dCss);';
        }

        // Temporary Class Removals via Filter
        $selectors_to_clean = apply_filters( 'wpninetyplus_sync_temporary', array() );
        if ( ! empty( $selectors_to_clean ) ) {
            $selectors_json = json_encode( $selectors_to_clean );

            $assets_block .= ' ' . $selectors_json . '.forEach(selector => {
                document.querySelectorAll(selector).forEach(element => {
                    element.classList.remove("r-temporary");
                });
            });';
        }

        $assets_block .= '}, loadingPeriod);'; 
        $assets_block .= '});';
        $assets_block .= '</script>';

        // Inject the block right before the closing </body> tag
        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $assets_block . '</body>', $html );
        } else {
            $html .= $assets_block; 
        }

        return $html;
    }
}
