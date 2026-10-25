<?php 

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Plugin Name: WP Ninety Plus Sync Asset Optimizer
 * Description: Automatically strips non-critical head assets (CSS, JS, Inline styles/scripts, Preloads) and defers execution via hooks and responsive delays.
 * Version: 1.1.0
 */

class WPNinetyPlusSync {

    /**
     * Core handler to parse HTML, strip targets via exclusions/hooks, and build the dynamic loader script.
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
        $version   = defined( 'WPNINETYPLUS_VERSION' ) ? WPNINETYPLUS_VERSION : '1.0.0';

        $wpninetypluscured_css      = array();
        $extracted_inline_css       = array();
        $wpninetypluscured_js       = array();
        $extracted_inline_js        = array();
        $extracted_preloads         = array();

        // --- 1. GRAB ASSETS WITH EXCLUSIONS & FILTERS ---

        // A. Grab stylesheet tags (Except critical 'style.css' or excluded IDs)
        $pattern_css = '/<link\b[^>]*rel\s*=\s*(["\'])stylesheet\1[^>]*>/i';
        $excluded_css_ids = array( 'layout', 'WPNinetyPlusMerge' );

        if ( preg_match_all( $pattern_css, $html, $matches_css ) ) {
            foreach ( $matches_css[0] as $index => $full_tag ) {
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

                if ( !$skip ) {
                    $wpninetypluscured_css[] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // B. Grab all preloads and preconnects
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $extracted_preloads[] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // Font Preloads via Filter (Appends directly to <head>)
        $preload_block = apply_filters( 'wpninetyplus_sync_font_preloads', '', $theme_uri );
        if ( ! empty( $preload_block ) && false !== strpos( $html, '</head>' ) ) {
            $html = str_replace( '</head>', $preload_block . '</head>', $html );
        }

        // C. Grab inline <style> blocks (Except critical IDs)
        $pattern_style = '/<style\b[^>]*>([\s\S]*?)<\/style>/i';
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
                
                if ( !$skip ) {
                    $extracted_inline_css[] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // D. Grab external script tags (Except critical 'vanilla.js' or excluded IDs)
        $pattern_script = '/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>[\s\S]*?<\/script>/i';
        $excluded_script_ids = array( 'interactive', 'WPNinetyPlusInteractive' );

        if ( preg_match_all( $pattern_script, $html, $matches_script ) ) {
            foreach ( $matches_script[0] as $index => $full_tag ) {
                $js_url = $matches_script[2][$index];
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

                if ( !$skip ) {
                    $wpninetypluscured_js[] = $full_tag;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // E. Grab inline script blocks (EXPLICITLY SKIPPING id="interactive")
        $pattern_inline_js = '/<script\b[^>]*>(?![\s\S]*src=)([\s\S]*?)<\/script>/i';
        if ( preg_match_all( $pattern_inline_js, $html, $matches_inline_js ) ) {
            foreach ( $matches_inline_js[0] as $index => $full_tag ) {
                $js_content = $matches_inline_js[1][$index];
                
                if ( false !== strpos( $full_tag, 'id="interactive"' ) || false !== strpos( $full_tag, "id='interactive'" ) ) {
                    continue;
                }

                $extracted_inline_js[] = $full_tag; 
                $html = str_replace( $full_tag, '', $html );
            }
        }


        // --- 2. BUILD THE OPTIMIZED FOOTER HIDDEN CONTAINER ---
        $assets_block  = "\n<!-- WPNinetyPlus Execution Block -->\n";
        $assets_block .= "<div id=\"wpninetyplus-delayed-assets\" style=\"display:none;\">\n";

        // A. External CSS Tags (Converted to data attributes)
        if ( ! empty( $wpninetypluscured_css ) ) {
            foreach ( $wpninetypluscured_css as $css_tag ) {
                if ( preg_match( '/href\s*=\s*(["\'])([^"\']+)\1/i', $css_tag, $url_match ) ) {
                    $href_val = $url_match[2];
                    $assets_block .= sprintf(
                        '<link rel="stylesheet" data-delayed-href="%s">' . "\n",
                        esc_url( $href_val . '?ver=' . $version )
                    );
                }
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

        // D. External JS Tags
        if ( ! empty( $wpninetypluscured_js ) ) {
            foreach ( $wpninetypluscured_js as $js_tag ) {
                if ( preg_match( '/src\s*=\s*(["\'])([^"\']+)\1/i', $js_tag, $url_match ) ) {
                    $src_val = $url_match[2];
                    $assets_block .= sprintf(
                        '<script data-delayed-src="%s" defer></script>' . "\n",
                        esc_url( $src_val )
                    );
                }
            }
        }

        // E. Inline JS Blocks (Converted to text/plain for safe delayed injection)
        if ( ! empty( $extracted_inline_js ) ) {
            foreach ( $extracted_inline_js as $js_tag ) {
                $modified_tag = preg_replace( '/\btype\s*=\s*(["\'])text\/javascript\1/i', 'type="text/plain"', $js_tag );
                if ( false === strpos( $modified_tag, 'type=' ) ) {
                    $modified_tag = str_replace( '<script', '<script type="text/plain"', $modified_tag );
                }
                $assets_block .= $modified_tag . "\n";
            }
        }

        $assets_block .= "</div>\n";


        // --- 3. BUILD THE JAVASCRIPT INJECTION SCRIPT ---
        $assets_block .= '<script defer>';
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

        // Inject Inline Styles
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets style").forEach(function(b){';
        $assets_block .= '  document.head.appendChild(b);';
        $assets_block .= '});';

        // Inject Intercepted Delayed Scripts from Container
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets script[data-delayed-src]").forEach(function(b){';
        $assets_block .= '  var c = document.createElement("script"); c.src = b.getAttribute("data-delayed-src"); c.defer = true; document.body.appendChild(c);';
        $assets_block .= '});';

        // Inject Inline Scripts (Restoring from text/plain)
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets script:not([data-delayed-src])").forEach(function(b){';
        $assets_block .= '  var c = document.createElement("script"); c.text = b.textContent; Array.from(b.attributes).forEach(function(attr){ if(attr.name !== "type"){ c.setAttribute(attr.name, attr.value); } }); document.body.appendChild(c);';
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


        // --- 4. COLLAPSE WHITESPACE & INJECT BEFORE </body> ---
        $html = preg_replace( "/[\r\n]{3,}/", "\n\n", $html );

        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $assets_block . "\n</body>", $html );
        } else {
            $html .= $assets_block; 
        }

        return $html;
    }
}