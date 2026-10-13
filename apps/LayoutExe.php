<?php
/**
 * The plugin : WordPress Optimization Ninety Plus (90+) Grade Speed Insight 
 *
 * Secured CSS Optimized static cross pages and devices cured html!
 *
 * @link              https://www.nielsoffice.com/        
 * @since             1.0.0
 * @package           Wpninetyplus
 *
 */

namespace WPNinetyPlus;

if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

class LayoutExe {

    public function css_cured_ninety_plus( string $layout ) : string 
    {

        if ( empty( $layout ) ) {
            return $layout;
        }

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Remove CSS comments
         **/
        $layout = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $layout );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Normalize line breaks and tabs
         **/
        $layout = str_replace( [ "\r\n", "\r", "\n", "\t" ], '', $layout );

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Collapse multiple spaces into a single space
         **/
        $layout = preg_replace( '/\s+/', ' ', $layout );

       /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Remove unnecessary spaces around syntax characters
         **/
        $search = [ ' ;', '; ', ' {', '{ ', ' }', '} ', ' :', ': ', ' ,', ', ' ];
        $replace = [ ';', ';', '{', '{', '}', '}', ':', ':', ',', ',' ];
        $layout = str_replace( $search, $replace, $layout );

        return trim( $layout );

    }
}