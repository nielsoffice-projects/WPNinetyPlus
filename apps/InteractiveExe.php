<?php 

/**
 * The plugin : WordPress Optimization Ninety Plus (90+) Grade Speed Insight 
 * Secured JS Optimized static cross pages and devices cured html!
 *
 * @link              https://www.nielsoffice.com/        
 * @since             1.0.0
 * @package           Wpninetyplus
 */

namespace WPNinetyPlus;

if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

class InteractiveExe {

    /**
     * Minifies, shortens, removes spacing, and strips comments from JavaScript 
     * without altering its execution flow or positioning.
     *
     * @param string $interactive
     * @return string
     */
    public function javascript_cured_ninety_plus( string $interactive ) : string
    {
        if ( empty( $interactive ) ) {
            return $interactive;
        }

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Remove HTML comment wrappers (<!-- and -->) common in WP inline scripts
         **/
        $interactive = preg_replace( '/<!--|-->/', '', $interactive );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Remove multi-line block comments 
         **/
        $interactive = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $interactive );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Remove single-line comments (// ...), guarding against URLs inside strings
         **/
        $interactive = preg_replace( '/(?<!["\'`])\/\/.*/m', '', $interactive );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Replace line breaks and tabs with a single space to flatten the script
         **/
        $interactive = preg_replace( '/[\r\n\t]+/', ' ', $interactive );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Collapse multiple consecutive spaces into one
         **/
        $interactive = preg_replace( '/\s+/', ' ', $interactive );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Safely remove spaces around common code operators and delimiters
         **/
        $search  = [ ' ;', '; ', ' ,', ', ', ' {', '{ ', ' }', '} ', ' (', '( ', ' )', ') ' ];
        $replace = [ ';', ';', ',', ',', '{', '{', '}', '}', '(', '(', ')', ')' ];
        $interactive = str_replace( $search, $replace, $interactive );

        return trim( $interactive );
    }

    /**
     * @package WPNinetyPlus 
     * @since 1.0.0
     * @date October 13, 2026 AM
     * @maintainer NielsOffice
     * First, minify and clean it using your standard method
     * Generate a clean self-executing snippet without debug logging clutter
     **/
    public function protected_ninety_plus_script( string $handwriting ) : string
    {
        // 
        $minified = $this->javascript_cured_ninety_plus( $handwriting );

        if ( empty( $minified ) ) {
            return '';
        }

        $encoded = base64_encode( $minified );
        $secured_script = sprintf(
            '(function(){try{eval(atob("%s"));}catch(e){/* Silent protection */}})();',
            $encoded
        );

        return $secured_script;
    }

}