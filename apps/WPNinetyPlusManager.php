<?php

namespace WPNinetyPlus;

use WPNinetyPlus\WPNinetyPlusExtractor;

/**
 * Class WPNinetyPlusManager
 *
 * Orchestration controller that coordinates asset extraction and DOM injection.
 *
 * @since 1.0.7
 */
class WPNinetyPlusManager {

    /**
     * Injects cured and optimized assets into the HTML response.
     *
     * @since  1.0.7
     * @param  string $html The original HTML content.
     * @return string Modified HTML content with optimized assets.
     */
    public function cured_assets_injection( $html ) {
        // Skip execution on administrative screens, feeds, or trackbacks.
        if ( is_admin() || is_feed() || is_trackback() ) {
            return $html;
        }

        $extractor = new WPNinetyPlusExtractor();
        $assets    = $extractor->extract_assets( $html );

        // Build the optimized assets container wrapper.
        $optimized_block  = "\n<!-- WPNinetyPlus Execution -->\n";
        $optimized_block .= "<div id=\"wpninetyplus-wpninetypluscured-assets\" style=\"display:none;\">\n";

        // ---------------------------------------------------------------------
        // 1. External & CDN Stylesheets Optimization
        // ---------------------------------------------------------------------
        if ( ! empty( $assets['css'] ) ) {
            foreach ( $assets['css'] as $css_tag ) {
                if ( preg_match( '/href\s*=\s*(["\'])([^"\']+)\1/i', $css_tag, $url_match ) ) {
                    $href_val     = $url_match[2];
                    $preload_tag  = sprintf(
                        '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" data-wpninetypluscured-href="%s">',
                        esc_url( $href_val ),
                        esc_url( $href_val )
                    );
                    $noscript_tag = sprintf( '<noscript><link rel="stylesheet" href="%s"></noscript>', esc_url( $href_val ) );

                    $optimized_block .= $preload_tag . "\n" . $noscript_tag . "\n";
                } else {
                    $modified_tag     = preg_replace( '/\bhref\s*=\s*(["\'])/i', 'data-wpninetypluscured-href=$1', $css_tag );
                    $optimized_block .= $modified_tag . "\n";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 2. Preloads, Preconnects, and Inline Styles
        // ---------------------------------------------------------------------
        if ( ! empty( $assets['preloads'] ) ) {
            $optimized_block .= implode( "\n", $assets['preloads'] ) . "\n";
        }

        if ( ! empty( $assets['inline_css'] ) ) {
            foreach ( $assets['inline_css'] as $style_tag ) {
                $optimized_block .= $style_tag . "\n";
            }
        }

        // ---------------------------------------------------------------------
        // 3. JavaScript Deferral & Type Neutralization
        // ---------------------------------------------------------------------
        if ( ! empty( $assets['js'] ) ) {
            foreach ( $assets['js'] as $js_tag ) {
                $modified_tag = preg_replace( '/\bsrc\s*=\s*(["\'])/i', 'data-wpninetypluscured-src=$1', $js_tag );
                if ( false === strpos( $modified_tag, 'defer' ) ) {
                    $modified_tag = str_replace( '<script', '<script defer', $modified_tag );
                }
                $optimized_block .= $modified_tag . "\n";
            }
        }

        if ( ! empty( $assets['inline_js'] ) ) {
            foreach ( $assets['inline_js'] as $js_tag ) {
                $modified_tag = preg_replace( '/\btype\s*=\s*(["\'])text\/javascript\1/i', 'type="text/plain"', $js_tag );
                if ( false === strpos( $modified_tag, 'type=' ) ) {
                    $modified_tag = str_replace( '<script', '<script type="text/plain"', $modified_tag );
                }
                $optimized_block .= $modified_tag . "\n";
            }
        }

        $optimized_block .= "</div>\n";

        // ---------------------------------------------------------------------
        // 4. Client-Side DOM Injection Engine & Dynamic Loading Parameters
        // ---------------------------------------------------------------------
        $default_loading_js        = "window.innerWidth <= 680 ? 4000 : (window.innerWidth <= 2014 ? 3500 : 2500)";
        $loading_period_expression = apply_filters( 'wpninetyplus_loading_period', $default_loading_js );

        $selectors_to_clean        = apply_filters( 'wpninetyplus_temporary', array() );
        $selectors_js              = ! empty( $selectors_to_clean ) ? json_encode( $selectors_to_clean ) : '[]';

        // Single-line client script executing the layout paint and asset restoration engine
        $optimized_block .= "<script>!function(a,e){setTimeout(function(){document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets link[data-wpninetypluscured-href]').forEach(function(b){var c=document.createElement('link');c.rel='stylesheet';c.href=b.getAttribute('data-wpninetypluscured-href');Array.from(b.attributes).forEach(function(attr){if(attr.name!=='data-wpninetypluscured-href'&&attr.name!=='href'){c.setAttribute(attr.name,attr.value)}});document.head.appendChild(c)});document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets style').forEach(function(b){document.head.appendChild(b)});setTimeout(function(){document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets script[data-wpninetypluscured-src]').forEach(function(b){var c=document.createElement('script');c.src=b.getAttribute('data-wpninetypluscured-src');c.async=false;Array.from(b.attributes).forEach(function(attr){if(attr.name!=='data-wpninetypluscured-src'&&attr.name!=='src'){c.setAttribute(attr.name,attr.value)}});document.body.appendChild(c)});document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets script:not([data-wpninetypluscured-src])').forEach(function(b){var c=document.createElement('script');if(b.type){c.type=b.type;}c.text=b.textContent;Array.from(b.attributes).forEach(function(attr){if(attr.name!=='type'){c.setAttribute(attr.name,attr.value)}});document.body.appendChild(c)})},50);e.forEach(function(selector){document.querySelectorAll(selector).forEach(function(element){element.classList.remove('r-temporary')})});var d=document.getElementById('wpninetyplus-wpninetypluscured-assets');if(d)d.remove()},a)}( " . $loading_period_expression . ", " . $selectors_js . " );</script>\n";

        // Normalize blank lines and inject payload cleanly into the output buffer
        $html = preg_replace( "/[\r\n]{3,}/", "\n\n", $html );

        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $optimized_block . "\n</body>", $html );
        } else {
            $html .= $optimized_block;
        }

        return $html;
    }
}