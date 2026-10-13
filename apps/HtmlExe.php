<?php
/**
 * @package           Wpninetyplus
 * @link              https://www.nielsoffice.com/        
 * @since             1.0.0
 * 
 * The plugin : WordPress Optimization Ninety Plus (90+) Grade Speed Insight 
 * Secured JS Optimized static cross pages and devices cured html!
 */

namespace WPNinetyPlus;

if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

use WPNinetyPlus\LayoutExe;
use WPNinetyPlus\InteractiveExe;

class HtmlExe {

    /**
     * @package WPNinetyPlus 
     * @since 1.0.0
     * @date October 13, 2026 AM
     * @maintainer NielsOffice
     * Set to true by default to enable script protection.
     **/
    private bool $secure_js = PST;

    public function __construct( ?bool $secure_js = null ) {

        if ( null !== $secure_js ) {
            $this->secure_js = $secure_js;
        }

    }

    /**
     * @package WPNinetyPlus 
     * @since 1.0.0
     * @date October 13, 2026 AM
     * @maintainer NielsOffice
     * Cure inline <style> blocks
     * Cure inline <script> blocks
     * Skip external scripts with a 'src' attribute
     * Check whether to use standard minification or the secure/encoded version
     * Regex patterns to strip comments and excessive whitespace for skeleton
     **/
    public function html_cured_ninety_plus( string $skeleton ) : string 
    {
        if ( empty( $skeleton ) || ( function_exists( 'is_feed' ) && is_feed() ) ) {
            return $skeleton;
        }  

        if ( OCC ) :
        $skeleton = preg_replace_callback( '/<style\b[^>]*>(.*?)<\/style>/is', function( $matches ) {
            preg_match( '/<style\b([^>]*)>/i', $matches[0], $attr_match );
            $attributes = isset( $attr_match[1] ) ? $attr_match[1] : '';
            
            return '<style' . $attributes . '>' . (new LayoutExe())->css_cured_ninety_plus( $matches[1] ) . '</style>';
            
        }, $skeleton );

        endif;

        if ( OCJ ) :
        $skeleton = preg_replace_callback( '/<script\b([^>]*)>(.*?)<\/script>/is', function( $matches ) {
            $attributes = $matches[1];
            $content    = $matches[2];

            if ( false !== stripos( $attributes, 'src=' ) ) {
                return $matches[0];
            }

            $js_cured = new InteractiveExe();

            if ( $this->secure_js ) {
                $processed_content = $js_cured->protected_ninety_plus_script( $content );
            } else {
                $processed_content = $js_cured->javascript_cured_ninety_plus( $content );
            }

            return '<script' . $attributes . '>' . $processed_content . '</script>';
            
        }, $skeleton );
        endif;

        $search = [
            '/<!--(?!\s*(?:\[if [^\]]>|<!|>))(?:(?!-->).)*-->/s', 
            '/\>[^\S ]+/s',                                         
            '/[^\S ]+\</s',                                        
            '/(\s)+/s'                                              
        ];
        
        $replace = [
            '',
            '>',
            '<',
            '\\1'
        ];

        return preg_replace( $search, $replace, $skeleton );
    }

}