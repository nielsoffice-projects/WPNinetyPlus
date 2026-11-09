<?php
/**
 * Plugin Name: WPNinetyPlus LayoutExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured CSS Optimized static cross pages and devices cured html!
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class LayoutExe
 * 
 * Handles the processing, cleaning, and minification of raw CSS style content 
 * for layout optimization.
 *
 * @since 1.0.0
 */
class LayoutExe {

    /**
     * Cleans, strips comments from, and minifies raw CSS code strings.
     *
     * @since 1.0.0
     * @param string $layout Raw CSS code string to be optimized.
     * @return string        Minified and optimized CSS string.
     */
    public function css_cured_ninety_plus( string $layout ) : string {
        if ( empty( $layout ) ) {
            return $layout;
        }

        // 1. Strip CSS block comments (/* ... */) completely, including text inside.
        $layout = preg_replace( '~/\*[\s\S]*?\*/~', '', $layout );

        // 2. Collapse multiple spaces, tabs, and newlines into a single space.
        $layout = preg_replace( '~\s+~', ' ', $layout );

        // 3. Remove spaces surrounding major CSS symbols, operators, and selectors.
        $layout = preg_replace( '~\s*([{}:;,>+~])\s*~', '$1', $layout );

        // 4. Clean up redundant semicolons right before closing brackets (;} -> }).
        $layout = str_replace( ';}', '}', $layout );

        // 5. Clean up any leftover empty rulesets (e.g., selector{}).
        $layout = preg_replace( '~[^{}]+\{\}~', '', $layout );

        // Return the clean, trimmed CSS layout string.
        return trim( $layout );
    }
}