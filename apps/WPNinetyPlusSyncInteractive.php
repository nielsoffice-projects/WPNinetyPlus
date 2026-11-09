<?php 

/**
 * Plugin Name: WPNinetyPlus WPNinetyPlusSyncInteractive Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured CSS Optimized static cross pages and devices cured html!
 */

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Plugin Name: WP Ninety Plus WPNinetyPlusSyncInteractive Asset Optimizer (JS & Fonts Only)
 * Description: Automatically strips non-critical head JavaScript, inline scripts, and preloads, deferring execution via hooks and responsive delays.
 * Version: 1.1.0
 */

class WPNinetyPlusSyncInteractive {

    /**
     * Core handler to parse HTML, strip target JS scripts via filters, and build the dynamic loader script.
     *
     * @param string $html
     * @return string
     */
    public function cured_assets_syncinter( $html ) {
        
        // Skip optimization in admin panels, feeds, or trackbacks
        if ( is_admin() || is_feed() || is_trackback() ) {
            return $html;
        }

        $theme_uri = get_template_directory_uri();
        $version   = defined( 'WPNINETYPLUS_VERSION' ) ? WPNINETYPLUS_VERSION : '1.0.0';

        $wpninetypluscured_js       = array();
        $extracted_inline_js        = array();
        $extracted_preloads         = array();

        // --- 1. FONT PRELOADS & PRECONNECTS ---

        // Grab explicit preloads/preconnects from head if any exist naturally
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $extracted_preloads[] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // Font Preloads via Filter (Appends directly to <head>)
        $preload_block = apply_filters( 'wpninetyplus_sync_font_preloads_interactive', '', $theme_uri );
        if ( ! empty( $preload_block ) && false !== strpos( $html, '</head>' ) ) {
            $html = str_replace( '</head>', $preload_block . '</head>', $html );
        }

        // --- 2. GRAB TARGET JAVASCRIPT ASSETS VIA EXCLUSIONS & FILTERS ---

        // Keywords/paths filter for JS files to delay
        $keywords_to_delay = apply_filters( 'wpninetyplus_sync_assets_interactive', array() );
        
        // NEW: Dedicated CDN keywords/domains filter
        $cdn_keywords_to_delay = apply_filters( 'wpninetyplus_sync_cdn_assets_interactive', array(
            'cdnjs.cloudflare.com',
            'jsdelivr.net',
            'unpkg.com',
            'googletagmanager.com' // Example default defaults or leave empty array()
        ) );
        
       // Merge them or handle them separately
        $all_keywords = array_merge( $keywords_to_delay, $cdn_keywords_to_delay );
        $excluded_script_ids = array( 'interactive', 'WPNinetyPlusInteractive' );

        // A. Grab external script tags matching keywords (Local + CDN)
        if ( ! empty( $all_keywords ) ) {
            foreach ( $all_keywords as $keyword ) {
                $pattern_script = '/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']*' . preg_quote( $keyword, '/' ) . '[^"\']*)\1[^>]*>[\s\S]*?<\/script>/i';
                if ( preg_match_all( $pattern_script, $html, $matches_script ) ) {
                    foreach ( $matches_script[0] as $index => $full_tag ) {
                        $skip = false;
                        foreach ( $excluded_script_ids as $id ) {
                            if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                                $skip = true;
                                break;
                            }
                        }

                        if ( !$skip && !in_array( $full_tag, $wpninetypluscured_js, true ) ) {
                            $wpninetypluscured_js[] = $full_tag;
                            $html = str_replace( $full_tag, '', $html );
                        }
                    }
                }
            }
        }

        // B. Grab inline script blocks (EXPLICITLY SKIPPING excluded IDs like id="interactive")
        $pattern_inline_js = '/<script\b[^>]*>(?![\s\S]*src=)([\s\S]*?)<\/script>/i';
        if ( preg_match_all( $pattern_inline_js, $html, $matches_inline_js ) ) {
            foreach ( $matches_inline_js[0] as $index => $full_tag ) {
                $skip = false;
                foreach ( $excluded_script_ids as $id ) {
                    if ( false !== strpos( $full_tag, 'id="' . $id . '"' ) || false !== strpos( $full_tag, "id='" . $id . "'" ) ) {
                        $skip = true;
                        break;
                    }
                }

                if ( !$skip ) {
                    $extracted_inline_js[] = $full_tag; 
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }


        // --- 3. BUILD THE OPTIMIZED FOOTER HIDDEN CONTAINER ---
        $assets_block  = "\n<!-- WPNinetyPlus JS Execution Block -->\n";
        $assets_block .= "<div id=\"wpninetyplus-delayed-assets\"WPNinetyPlusSync style=\"display:none;\">\n";

        // A. Preloads collected from head
        if ( ! empty( $extracted_preloads ) ) {
            $assets_block .= implode( "\n", $extracted_preloads ) . "\n";
        }

        // B. External JS Tags converted to data attributes
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

        // C. Inline JS Blocks (Converted to text/plain for safe delayed injection)
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


        // --- 4. BUILD THE JAVASCRIPT INJECTION SCRIPT ---
        $assets_block .= '<script defer>';
        $assets_block .= 'window.addEventListener("load", function() {';
        
        // Loading Period Logic via Filter (Responsive Timeout)
        $default_loading_js = 'var loadingPeriod = window.innerWidth <= 680 ? 2000 : (window.innerWidth <= 2014 ? 2500 : 2000);';
        $loading_period_js  = apply_filters( 'wpninetyplus_sync_loading_period_interactive', $default_loading_js );
        
        $assets_block .= $loading_period_js;
        $assets_block .= 'setTimeout(function() {';

        // Conditional Asset Loading & Custom JS Snippets via Filter
        $conditional_logic_block = apply_filters( 'wpninetyplus_sync_assets_conditional_interactive', '', $theme_uri, $version );
        $assets_block .= $conditional_logic_block;

        // Inject Intercepted Delayed Scripts from Container
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets script[data-delayed-src]").forEach(function(b){';
        $assets_block .= '  var c = document.createElement("script"); c.src = b.getAttribute("data-delayed-src"); c.defer = true; document.body.appendChild(c);';
        $assets_block .= '});';

        // Inject Inline Scripts (Restoring from text/plain)
        $assets_block .= 'document.querySelectorAll("#wpninetyplus-delayed-assets script:not([data-delayed-src])").forEach(function(b){';
        $assets_block .= '  var c = document.createElement("script"); c.text = b.textContent; Array.from(b.attributes).forEach(function(attr){ if(attr.name !== "type"){ c.setAttribute(attr.name, attr.value); } }); document.body.appendChild(c);';
        $assets_block .= '});';

        // Temporary Class Removals via Filter (Prevents FOUC / layout shifts)
        $selectors_to_clean = apply_filters( 'wpninetyplus_sync_temporary_interactive', array() );
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


        // --- 5. COLLAPSE WHITESPACE & INJECT BEFORE </body> ---
        $html = preg_replace( "/[\r\n]{3,}/", "\n\n", $html );

        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $assets_block . "\n</body>", $html );
        } else {
            $html .= $assets_block; 
        }

        return $html;
    }
}

