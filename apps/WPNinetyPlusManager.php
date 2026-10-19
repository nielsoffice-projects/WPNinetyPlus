<?php 

namespace WPNinetyPlus;

use WPNinetyPlus\WPNinetyPlusExtractor;

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Plugin Name: WP Ninety Plus Asset Optimizer
 * Description: Automatically strips non-critical head assets, defers them, and supports optimized preload/noscript patterns.
 * Version: 1.0.3
 */

class WPNinetyPlusManager {

    /**
     * Main execution pipeline method to process, wrap, and inject optimized assets into the DOM.
     *
     * @since 1.0.3
     * @param string $html The raw HTML output buffer content.
     * @return string      The fully optimized HTML buffer ready for browser delivery.
     */
    public function cured_assets_injection( $html ) {
        // Bypass optimization routines entirely if processing administrative screens, RSS feeds, or trackbacks.
        if ( is_admin() || is_feed() || is_trackback() ) {
            return $html;
        }

        // Delegate parsing tasks to the asset extractor engine.
        $extractor = new WPNinetyPlusExtractor();
        $assets    = $extractor->extract_assets( $html );

        // Initialize the hidden wrapper container block designated for deferred asset restoration.
        $optimized_block  = "\n<!-- WPNinetyPlus Execution -->\n";
        $optimized_block .= "<div id=\"wpninetyplus-wpninetypluscured-assets\" style=\"display:none;\">\n";

        // A. Convert extracted external CSS stylesheets into asynchronous preload links with fallback noscript declarations.
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

        // Append collected link preloads and preconnect resource declarations.
        if ( ! empty( $assets['preloads'] ) ) {
            $optimized_block .= implode( "\n", $assets['preloads'] ) . "\n";
        }

        // B. Reinsert extracted inline CSS style blocks into the deferred storage block.
        if ( ! empty( $assets['inline_css'] ) ) {
            foreach ( $assets['inline_css'] as $style_tag ) {
                $optimized_block .= $style_tag . "\n";
            }
        }

        // C. Process external script tags by injecting data attributes and enforcing 'defer' execution rules.
        if ( ! empty( $assets['js'] ) ) {
            foreach ( $assets['js'] as $js_tag ) {
                $modified_tag = preg_replace( '/\bsrc\s*=\s*(["\'])/i', 'data-wpninetypluscured-src=$1', $js_tag );
                if ( false === strpos( $modified_tag, 'defer' ) ) {
                    $modified_tag = str_replace( '<script', '<script defer', $modified_tag );
                }
                $optimized_block .= $modified_tag . "\n";
            }
        }

        // D. Convert inline script block mime types to text/plain to prevent premature parsing by the browser.
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

        /**
         * Filter the deferred activation loading period expression.
         * Dynamically adjusts timing based on screen width dimensions (e.g., mobile vs. desktop).
         *
         * @since 1.0.3
         * @param string $default_loading_js JavaScript ternary layout calculating delay milliseconds.
         */
        $default_loading_js        = "window.innerWidth <= 680 ? 4000 : (window.innerWidth <= 2014 ? 3500 : 2500)";
        $loading_period_expression = apply_filters( 'wpninetyplus_loading_period', $default_loading_js );

        /**
         * Filter temporary layout classes or element selectors to be cleaned up after execution.
         *
         * @since 1.0.3
         * @param array $selectors Array of CSS selector strings.
         */
        $selectors_to_clean        = apply_filters( 'wpninetyplus_temporary', array() );
        $selectors_js              = ! empty( $selectors_to_clean ) ? json_encode( $selectors_to_clean ) : '[]';

        /**
         * Construct the client-side vanilla JavaScript timer activation script.
         * This script waits for the calculated delay period, dynamically unpacks all deferred 
         * styles, scripts, and preloads from the hidden container, appends them to the live DOM, 
         * strips temporary placeholder CSS classes, and safely destroys the container node.
         */
        $optimized_block          .= "<script>!function(a,e){setTimeout(function(){document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets link[data-wpninetypluscured-href]').forEach(function(b){var c=document.createElement('link');c.rel='stylesheet';c.href=b.getAttribute('data-wpninetypluscured-href');Array.from(b.attributes).forEach(function(attr){if(attr.name!=='data-wpninetypluscured-href'&&attr.name!=='href'){c.setAttribute(attr.name,attr.value)}});document.head.appendChild(c)});document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets script[data-wpninetypluscured-src]').forEach(function(b){var c=document.createElement('script');c.src=b.getAttribute('data-wpninetypluscured-src');c.defer=true;Array.from(b.attributes).forEach(function(attr){if(attr.name!=='data-wpninetypluscured-src'&&attr.name!=='src'){c.setAttribute(attr.name,attr.value)}});document.body.appendChild(c)});document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets script:not([data-wpninetypluscured-src])').forEach(function(b){var c=document.createElement('script');c.text=b.textContent;Array.from(b.attributes).forEach(function(attr){if(attr.name!=='type'){c.setAttribute(attr.name,attr.value)}});document.body.appendChild(c)});document.querySelectorAll('#wpninetyplus-wpninetypluscured-assets style').forEach(function(b){document.head.appendChild(b)});e.forEach(function(selector){document.querySelectorAll(selector).forEach(function(element){element.classList.remove('r-temporary')})});var d=document.getElementById('wpninetyplus-wpninetypluscured-assets');if(d)d.remove()},a)}( " . $loading_period_expression . ", " . $selectors_js . " );</script>\n";

        // Clean up excessive multi-line gaps in the HTML output buffer.
        $html = preg_replace( "/[\r\n]{3,}/", "\n\n", $html );

        // Securely inject the finalized optimized block right before the closing </body> tag.
        if ( false !== strpos( $html, '</body>' ) ) {
            $html = str_replace( '</body>', $optimized_block . "\n</body>", $html );
        } else {
            $html .= $optimized_block;
        }

        return $html;
    }
}