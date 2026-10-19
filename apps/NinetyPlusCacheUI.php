<?php

namespace WPNinetyPlus;

use WPNinetyPlus\NinetyPlusCache;

/**
 * Class NinetyPlusCacheUI
 * 
 * UI and Controller layer managing WordPress hooks, admin bar toolbar integration, 
 * request processing, redirection, and notice rendering.
 *
 * @since 1.0.0
 */
final class NinetyPlusCacheUI {

    /**
     * Singleton instance of the class.
     *
     * @since 1.0.0
     * @var NinetyPlusCacheUI|null
     */
    private static $instance = null;

    /**
     * Result transient lifetime in seconds.
     *
     * @since 1.0.0
     * @var int
     */
    private const RESULT_TTL = 120;

    /**
     * Retrieves the singleton instance of the class.
     *
     * @since 1.0.0
     * @return NinetyPlusCacheUI The singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * NinetyPlusCacheUI constructor.
     * Registers required admin and frontend hooks.
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'admin_bar_menu', array( $this, 'add_toolbar_item' ), 100 );
        add_action( 'admin_post_admin_bar_clear_all_cache', array( $this, 'handle_cache_clear' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
        add_action( 'wp_footer', array( $this, 'show_frontend_notice' ) );
    }

    /**
     * Adds the cache-clearing action item to the WordPress admin toolbar.
     *
     * @since 1.0.0
     * @param \WP_Admin_Bar $admin_bar WordPress admin bar instance.
     */
    public function add_toolbar_item( $admin_bar ) {
        // Only display the toolbar item for users with administrative capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $admin_bar->add_node(
            array(
                'id'    => 'clear-all-site-cache',
                'title' => '🧹 90+ Clear Cache',
                'href'  => wp_nonce_url(
                    admin_url( 'admin-post.php?action=admin_bar_clear_all_cache' ),
                    'admin_bar_clear_all_cache'
                ),
                'meta'  => array(
                    'title' => 'Clear WordPress and Ninety Plus caches',
                ),
            )
        );
    }

    /**
     * Processes the cache-clearing request, validates permissions, 
     * delegates cache purging to NinetyPlusCache, saves status transients, and redirects back.
     *
     * @since 1.0.0
     */
    public function handle_cache_clear() {
        // Enforce security capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__( 'You are not allowed to clear the cache.', 'wpninetyplus' ),
                esc_html__( 'Permission denied', 'wpninetyplus' ),
                array( 'response' => 403 )
            );
        }

        // Verify the admin nonce request.
        check_admin_referer( 'admin_bar_clear_all_cache' );

        $results = array();
        $cache_engine = new NinetyPlusCache();

        // 1. Flush the WordPress object cache.
        $flush_object_cache = (bool) apply_filters(
            'wpninetyplus_clear_object_cache',
            ! is_multisite()
        );

        if ( $flush_object_cache && function_exists( 'wp_cache_flush' ) ) {
            $results['object_cache'] = wp_cache_flush() ? 'success' : 'failed';
        } else {
            $results['object_cache'] = 'skipped';
        }

        // 2. Delete database-stored transients belonging to this plugin.
        $results['plugin_transients'] = $cache_engine->clear_plugin_transients();

        // 3. Clear generated CSS/JS files from our dedicated cache directory.
        $results['plugin_file_cache'] = $cache_engine->clear_plugin_cache_directory();

        // 4. Save results for this user, then redirect.
        $result_key = 'wpninetyplus_result_' . get_current_user_id();

        set_transient( $result_key, $results, self::RESULT_TTL );

        $redirect_url = wp_get_referer() ? wp_get_referer() : admin_url();
        $redirect_url = remove_query_arg( 'cache_clear_result', $redirect_url );
        $redirect_url = add_query_arg( 'cache_clear_result', '1', $redirect_url );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Hook wrapper to show the cache notice in the WordPress administration backend.
     *
     * @since 1.0.0
     */
    public function show_admin_notice() {
        if ( ! is_admin() ) {
            return;
        }
        $this->render_cache_notice();
    }

    /**
     * Hook wrapper to show the cache notice on the public frontend view.
     *
     * @since 1.0.0
     */
    public function show_frontend_notice() {
        if ( is_admin() ) {
            return;
        }
        $this->render_cache_notice();
    }

    /**
     * Renders the cache result notice using Acorn Blade views.
     *
     * @since 1.0.0
     */
    private function render_cache_notice() {
        if (
            ! current_user_can( 'manage_options' )
            || ! isset( $_GET['cache_clear_result'] )
            || '1' !== sanitize_text_field( wp_unslash( $_GET['cache_clear_result'] ) )
        ) {
            return;
        }

        static $rendered = false;
        if ( $rendered ) {
            return;
        }
        $rendered = true;

        $result_key = 'wpninetyplus_result_' . get_current_user_id();
        $results    = get_transient( $result_key );
        delete_transient( $result_key );

        if ( ! is_array( $results ) ) {
            $results = array();
        }

        // Render via Acorn Blade view safely.
        echo \Roots\view(
            'wpninetyplus::partials.cache-notice',
            compact( 'results' )
        )->render();
    }
}