<?php 


namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Plugin Name: WP Ninety Plus Asset Optimizer
 * Description: Automatically strips non-critical head assets and moves them to the footer.
 * Version: 1.0.0
 */


class WPNinetyPlusManager {

    public function cured_assets_injection( $html ) {
        // Skip optimization in admin panels or feeds
        if ( is_admin() || is_feed() || is_trackback() ) {
            return $html;
        }

        $delayed_css           = array();
        $extracted_inline_css  = array();
        $delayed_js            = array();
        $extracted_inline_js   = array();
        $extracted_preloads    = array();

        // 1. Grab stylesheet URLs (Except critical 'style.css')
        $pattern_css = '/<link\b[^>]*rel\s*=\s*(["\'])stylesheet\1[^>]*href\s*=\s*(["\'])([^"\']+)\2[^>]*>/i';
        if ( preg_match_all( $pattern_css, $html, $matches_css ) ) {
            foreach ( $matches_css[0] as $index => $full_tag ) {
                $css_url = $matches_css[3][$index];
                $css_basename = basename( parse_url( $css_url, PHP_URL_PATH ) );
                if ( 'style.css' !== $css_basename ) {
                    $delayed_css[] = $css_url;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 2. Grab all preloads and preconnects
        $pattern_preload = '/<link\b[^>]*rel\s*=\s*(["\'])(preload|preconnect)\1[^>]*>/i';
        if ( preg_match_all( $pattern_preload, $html, $matches_preload ) ) {
            foreach ( $matches_preload[0] as $tag ) {
                $extracted_preloads[] = $tag;
                $html = str_replace( $tag, '', $html );
            }
        }

        // 3. Grab inline <style> blocks (Except critical id="layout")
        $pattern_style = '/<style\b[^>]*>([\s\S]*?)<\/style>/i';
        if ( preg_match_all( $pattern_style, $html, $matches_style ) ) {
            foreach ( $matches_style[0] as $index => $full_tag ) {
                $style_content = $matches_style[1][$index];
                if ( false === strpos( $full_tag, 'id="layout"' ) && false === strpos( $full_tag, "id='layout'" ) ) {
                    $clean_css = trim( preg_replace( '/<!--[\s\S]*?-->/', '', $style_content ) );
                    $extracted_inline_css[] = "<style class=\"delayed-style\">\n    " . trim( $clean_css ) . "\n</style>";
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 4. FLEXIBLE: Grab external script URLs (Except critical 'vanilla.js')
        $pattern_script = '/<script\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>[\s\S]*?<\/script>/i';
        if ( preg_match_all( $pattern_script, $html, $matches_script ) ) {
            foreach ( $matches_script[0] as $index => $full_tag ) {
                $js_url = $matches_script[2][$index];
                $js_basename = basename( parse_url( $js_url, PHP_URL_PATH ) );

                var_dump($js_basename);

                if ( 'vanilla.js' !== $js_basename ) {
                    $delayed_js[] = $js_url;
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }

        // 5. Grab inline script blocks (Except critical id="interactive")
        $pattern_inline_js = '/<script\b[^>]*>(?![\s\S]*src=)([\s\S]*?)<\/script>/i';
        if ( preg_match_all( $pattern_inline_js, $html, $matches_inline_js ) ) {
            foreach ( $matches_inline_js[0] as $index => $full_tag ) {
                $js_content = $matches_inline_js[1][$index];
                if ( false === strpos( $full_tag, 'id="interactive"' ) && false === strpos( $full_tag, "id='interactive'" ) ) {
                    $clean_js = trim( preg_replace( '/<!--[\s\S]*?-->/', '', $js_content ) );
                    $extracted_inline_js[] = "<script type=\"text/plain\" class=\"delayed-js-block\">\n    " . trim( $clean_js ) . "\n</script>";
                    $html = str_replace( $full_tag, '', $html );
                }
            }
        }


        // --- BUILD THE OPTIMIZED FOOTER BLOCK WITH FILTERED DELAY ---
        $optimized_block  = "\n<!-- WPNinetyPlus Execution -->\n";
        $optimized_block .= "<div id=\"wpninetyplus-delayed-assets\" style=\"display:none;\">\n";

        // A. External CSS URLs
        if ( ! empty( $delayed_css ) ) {
            foreach ( $delayed_css as $css_url ) {
                $optimized_block .= '<link rel="stylesheet" data-delayed-href="' . esc_url( $css_url ) . '">' . "\n";
            }
        }

        if ( ! empty( $extracted_preloads ) ) {
            $optimized_block .= implode( "\n", $extracted_preloads ) . "\n";
        }

        // B. Inline CSS Follows External CSS
        if ( ! empty( $extracted_inline_css ) ) {
            $optimized_block .= implode( "\n", $extracted_inline_css ) . "\n";
        }

        // C. External JS URLs
        if ( ! empty( $delayed_js ) ) {
            foreach ( $delayed_js as $js_url ) {
                $optimized_block .= '<script data-delayed-src="' . esc_url( $js_url ) . '" defer></script>' . "\n";
            }
        }

        // D. Inline JS Follows External JS
        if ( ! empty( $extracted_inline_js ) ) {
            $optimized_block .= implode( "\n", $extracted_inline_js ) . "\n";
        }

        $optimized_block .= "</div>\n";

        $default_loading_js = "window.innerWidth <= 680 ? 4000 : (window.innerWidth <= 2014 ? 3500 : 2500)";
        $loading_period_expression = apply_filters( 'wpninetyplus_loading_period', $default_loading_js );

        $optimized_block .= "<script>!function(a){setTimeout(function(){document.querySelectorAll('#wpninetyplus-delayed-assets link[data-delayed-href]').forEach(function(b){var c=document.createElement('link');c.rel='stylesheet';c.href=b.getAttribute('data-delayed-href');document.head.appendChild(c)});document.querySelectorAll('#wpninetyplus-delayed-assets script[data-delayed-src]').forEach(function(b){var c=document.createElement('script');c.src=b.getAttribute('data-delayed-src');c.defer=true;document.body.appendChild(c)});document.querySelectorAll('script.delayed-js-block').forEach(function(b){var c=document.createElement('script');c.text=b.textContent;document.body.appendChild(c)});document.querySelectorAll('style.delayed-style').forEach(function(b){document.head.appendChild(b)});var d=document.getElementById('wpninetyplus-delayed-assets');if(d)d.remove()},a)}( " . $loading_period_expression . " );</script>\n";

        $html = preg_replace( "/[\r\n]{3,}/", "\n\n", $html );

        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $optimized_block . "\n</body>", $html );
        } else {
            $html .= $optimized_block;
        }

        return $html;
    }
}
