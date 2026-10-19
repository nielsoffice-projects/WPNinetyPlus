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

        // Wipe out all CSS multi-line comments (/* ... */).
        $layout = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $layout );

        // Normalize all line breaks, tabs, and multiple spaces into a single space.
        $layout = preg_replace( '/\s+/', ' ', $layout );

        // Remove unnecessary spaces around core CSS syntax characters and delimiters.
        $search  = [ ' ;', '; ', ' {', '{ ', ' }', '} ', ' :', ': ', ' ,', ', ' ];
        $replace = [ ';', ';', '{', '{', '}', '}', ':', ':', ',', ',' ];
        $layout  = str_replace( $search, $replace, $layout );

        // Return the clean, trimmed CSS layout string.
        return trim( $layout );
    }
}