<?php
/**
 * The plugin : WordPress Optimization Ninety Plus (90+) Grade Speed Insight 
 *
 * Secured HTML Optimized static cross pages and devices cured html!
 *
 * @link              https://www.nielsoffice.com/        
 * @since             1.0.0
 * @package           Wpninetyplus
 */

namespace WPNinetyPlus;

if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

use WPNinetyPlus\HtmlExe;
use WPNinetyPlus\LayoutMergeExe;
use WPNinetyPlus\WPNinetyPlusManager;
use WPNinetyPlus\WPNinetyPlusSync;

class Init {

    public function __construct() {

        /**
         * @package WPNinetyPlus 
         * @since 1.0.0
         * @date October 13, 2026 AM
         * @maintainer NielsOffice
         * Register action hooks
         * Start output buffering early at priority 1 on template_redirect
         **/
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        add_action( 'template_redirect', [ $this, 'ninety_plus_layout' ], 1 );        
       
  
    }

    /**
     * @package WPNinetyPlus 
     * @since 1.0.0
     * @date October 13, 2026 AM
     * @maintainer NielsOffice
     * Avoid minifying admin pages, AJAX requests, or REST API responses
     * Start output buffering for HTML curing if OCH constant evaluates to true
     **/
    public function init() {

        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }
        
         $och_enabled = defined('OCH') && OCH;
        if ( $och_enabled && class_exists( HtmlExe::class ) ) {
            ob_start( [ ( new HtmlExe() ), 'html_cured_ninety_plus' ] );
        }


        }
        


    /**
     * @package WPNinetyPlus 
     * @since 1.0.0
     * @date October 13, 2026 AM
     * @maintainer NielsOffice
     * Avoid running on admin pages, AJAX requests, or REST API responses
     * Check if stylesheet merging is enabled
     **/
    public function ninety_plus_layout() {

        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        $wpnia_enabled = defined( 'WNPIA' ) && WNPIA;
        if ($wpnia_enabled) {
            ob_start(function ($html) {
                $manager = new WPNinetyPlusManager();
                return $manager->cured_assets_injection($html);
            });
        }

        $wpnsa_enabled = defined( 'WPNSA' ) && WPNSA;
        if ($wpnsa_enabled) {
            ob_start(function ($html) {
                $manager = new WPNinetyPlusSync();
                return $manager->cured_assets_sync($html);
            });
        }

        $oclm_enabled = defined( 'OCLM' ) && OCLM;
        if ( $oclm_enabled ) {
            ob_start( [ (new LayoutMergeExe()), 'layout_merge_one' ] );
        }

        
    }


 
}