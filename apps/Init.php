<?php
/**
 * The plugin: WordPress Optimization Ninety Plus (90+) Grade Speed Insight
 *
 * Secured HTML Optimized static cross pages and devices cured html!
 *
 * @link             https://www.nielsoffice.com/        
 * @since            1.0.0
 * @package          Wpninetyplus
 */

namespace WPNinetyPlus;

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

use WPNinetyPlus\HtmlExe;
use WPNinetyPlus\LayoutMergeExe;
use WPNinetyPlus\WPNinetyPlusManager;
use WPNinetyPlus\WPNinetyPlusSync;
use WPNinetyPlus\FullPaintExe;
use WPNinetyPlus\HtmlCuredExe;
use WPNinetyPlus\InteractiveInlineExe;
use WPNinetyPlus\InteractiveMergeExe;
use WPNinetyPlus\LayoutMergeInlineExe;

/**
 * Class Init
 *
 * Handles core plugin hook registrations, output buffering routines, 
 * and orchestrates the optimization execution pipeline for frontend pages.
 *
 * @package WPNinetyPlus
 * @since   1.0.0
 */
class Init {

    /**
     * Initializes the class by registering WordPress action hooks.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Initialize early optimization tasks when plugins are fully loaded.
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        
        // Hook early into template redirection to capture and process final output buffers.
        add_action( 'template_redirect', [ $this, 'ninety_plus_layout' ], 1 );        
    }

    /**
     * Initializes standard HTML minification/curing buffering if OCH is enabled.
     *
     * Bypasses execution in admin areas, AJAX requests, and REST API calls.
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
        // Skip optimization for backend, AJAX, or REST requests.
        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }
        
        $och_enabled = defined( 'OCH' ) && OCH;
        if ( $och_enabled && class_exists( HtmlExe::class ) ) {
            ob_start( [ ( new HtmlExe() ), 'html_cured_ninety_plus' ] );
        }
    }
        
    /**
     * Unified pipeline for layout painting, asset injection, sync, and merging.
     *
     * Captures the page output buffer and executes modular transformation classes 
     * sequentially based on active configuration constants.
     *
     * @since 1.0.0
     * @return void
     */
    public function ninety_plus_layout() {
        // Skip optimization for backend, AJAX, or REST requests.
        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        // Single unified output buffer callback to process everything sequentially without conflicts.
        ob_start( function( $html ) {

            if ( empty( $html ) ) {
                return $html;
            }

            // 1. Client HTML Curing Pipeline
            $wpnp_cch_enabled = defined( 'WPNPCCH' ) && WPNPCCH;
            if ( $wpnp_cch_enabled ) {
                $HtmlCuredExe = new HtmlCuredExe();
                $html         = $HtmlCuredExe->cured_client_html( $html );
            }

            // 2. Interactive Inline Execution Pipeline
            $wpnp_iie_enabled = defined( 'WPNPIIE' ) && WPNPIIE;
            if ( $wpnp_iie_enabled ) {
                $InteractiveInlineExe = new InteractiveInlineExe();
                $html                 = $InteractiveInlineExe->interactive_inline( $html );
            }

            // 3. Interactive Merge Pipeline
            $wpnp_im_enabled = defined( 'WPNPIM' ) && WPNPIM;
            if ( $wpnp_im_enabled ) {
                $InteractiveMergeExe = new InteractiveMergeExe();
                $html                = $InteractiveMergeExe->interactive_merge( $html );
            }

            // 4. Layout Merge Pipeline
            $oclm_enabled = defined( 'OCLM' ) && OCLM;
            if ( $oclm_enabled ) {
                $layout_merge = new LayoutMergeExe();
                $html         = $layout_merge->layout_merge( $html );
            }

            // 5. Layout Merge Inline Pipeline
            $wpnpmi_enabled = defined( 'WPNPMI' ) && WPNPMI;
            if ( $wpnpmi_enabled ) {
                $layout_merge = new LayoutMergeInlineExe();
                $html         = $layout_merge->layout_merge_inline( $html );
            }

            // 6. Full Paint Execution Pipeline
            $wpnpfp_enabled = defined( 'WPNPFP' ) && WPNPFP;
            if ( $wpnpfp_enabled ) {
                $full_paint = new FullPaintExe();
                $html       = $full_paint->paint_cured_ninety_plus( $html );
            }

            // 7. Cured Assets Asset Injection Pipeline
            $wpnia_enabled = defined( 'WNPIA' ) && WNPIA;
            if ( $wpnia_enabled && class_exists( WPNinetyPlusManager::class ) ) {
                $manager = new WPNinetyPlusManager();
                $html    = $manager->cured_assets_injection( $html );
            }

            // 8. Cured Assets Synchronization Pipeline
            $wpnsa_enabled = defined( 'WPNSA' ) && WPNSA;
            if ( $wpnsa_enabled && class_exists( WPNinetyPlusSync::class ) ) {
                $sync = new WPNinetyPlusSync();
                $html = $sync->cured_assets_sync( $html );
            }

            return $html;
        } );
    }
}