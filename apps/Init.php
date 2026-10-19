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
use WPNinetyPlus\FullPaintExe;

class Init {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        add_action( 'template_redirect', [ $this, 'ninety_plus_layout' ], 1 );        
    }

    /**
     * Initialize standard HTML minification buffering.
     */
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
     * Unified pipeline for layout painting, asset injection, sync, and merging.
     */
    public function ninety_plus_layout() {
        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        // Single unified output buffer callback to process everything sequentially without conflicts
        ob_start( function( $html ) {
            if ( empty( $html ) ) {
                return $html;
            }
            
             // 1. FullPaintExe (Images, Videos, Skeleton Frames)
            $wpnpfp_enabled = defined('WPNPFP') && WPNPFP;
            if($wpnpfp_enabled) {
                $full_paint = new FullPaintExe();
                $html = $full_paint->paint_cured_ninety_plus( $html );
            }

            // 2. Manager Asset Injection
            $wpnia_enabled = defined( 'WNPIA' ) && WNPIA;
            if ( $wpnia_enabled && class_exists( WPNinetyPlusManager::class ) ) {
                $manager = new WPNinetyPlusManager();
                $html = $manager->cured_assets_injection( $html );
            }

            // 3. Asset Sync
            $wpnsa_enabled = defined( 'WPNSA' ) && WPNSA;
            if ( $wpnsa_enabled && class_exists( WPNinetyPlusSync::class ) ) {
                $sync = new WPNinetyPlusSync();
                $html = $sync->cured_assets_sync( $html );
            }

            // 4. Layout Merge
            $oclm_enabled = defined( 'OCLM' ) && OCLM;
            if ( $oclm_enabled && class_exists( LayoutMergeExe::class ) ) {
                $layout_merge = new LayoutMergeExe();
                $html = $layout_merge->layout_merge_one( $html );
            }

            return $html;
        } );
    }
}