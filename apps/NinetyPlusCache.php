<?php 
/**
 * WordPress Optimization Ninety Plus (90+) - Cache Cleaner.
 *
 * Clears the WordPress object cache, plugin transients stored in the
 * database, and generated files in the plugin's dedicated cache directory.
 *
 * @package Wpninetyplus
 */

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class NinetyPlusCache {

    /**
     * Singleton instance.
     *
     * @var NinetyPlusCache|null
     */
    private static $instance = null;

    /**
     * Cache directory name inside the WordPress uploads directory.
     */
    private const CACHE_DIRECTORY = 'wpninetyplus-cache';

    /**
     * Transient prefix owned by this plugin.
     */
    private const TRANSIENT_PREFIX = '_transient_wpninetyplus_';

    /**
     * Transient timeout prefix owned by this plugin.
     */
    private const TIMEOUT_PREFIX = '_transient_timeout_wpninetyplus_';

    /**
     * Result transient lifetime in seconds.
     */
    private const RESULT_TTL = 120;

    /**
     * Get the singleton instance.
     *
     * @return NinetyPlusCache
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register hooks.
     */
    private function __construct() {
        add_action( 'admin_bar_menu', array( $this, 'add_toolbar_item' ), 100 );
        add_action( 'admin_post_admin_bar_clear_all_cache', array( $this, 'handle_cache_clear' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
        add_action( 'wp_footer', array( $this, 'show_frontend_notice' ) );
    }

    /**
     * Add the cache-clearing action to the toolbar.
     *
     * @param \WP_Admin_Bar $admin_bar WordPress admin bar.
     */
    public function add_toolbar_item( $admin_bar ) {
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
     * Process the cache-clearing request.
     */
    public function handle_cache_clear() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__( 'You are not allowed to clear the cache.', 'wpninetyplus' ),
                esc_html__( 'Permission denied', 'wpninetyplus' ),
                array( 'response' => 403 )
            );
        }

        check_admin_referer( 'admin_bar_clear_all_cache' );

        $results = array();

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
        $results['plugin_transients'] = $this->clear_plugin_transients();

        // 3. Clear generated CSS/JS files from our dedicated cache directory.
        $results['plugin_file_cache'] = $this->clear_plugin_cache_directory();

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
     * Delete database-stored transients belonging to this plugin.
     *
     * @return string Operation status.
     */
    private function clear_plugin_transients() {
        global $wpdb;

        $transient_pattern = $wpdb->esc_like( self::TRANSIENT_PREFIX ) . '%';
        $timeout_pattern   = $wpdb->esc_like( self::TIMEOUT_PREFIX ) . '%';

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                $transient_pattern,
                $timeout_pattern
            )
        );

        return false === $deleted ? 'failed' : 'success';
    }

    /**
     * Clear files in the plugin's dedicated cache directory.
     * Automatically creates the folder safely if it doesn't exist yet.
     *
     * @return string Operation status.
     */
    private function clear_plugin_cache_directory() {
        $uploads = wp_upload_dir();

        if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
            return 'failed';
        }

        $uploads_dir = realpath( $uploads['basedir'] );

        if ( false === $uploads_dir || ! is_dir( $uploads_dir ) ) {
            return 'failed';
        }

        $cache_dir = trailingslashit( $uploads_dir ) . self::CACHE_DIRECTORY;

        if ( is_link( $cache_dir ) ) {
            return 'failed';
        }

        // Automatically provision folder and index security file if missing
        if ( ! file_exists( $cache_dir ) ) {
            wp_mkdir_p( $cache_dir );
            @file_put_contents( $cache_dir . '/index.php', '<?php // Silence is golden.' );
            return 'no_files_found';
        }

        $real_cache_dir = realpath( $cache_dir );

        if (
            false === $real_cache_dir
            || ! is_dir( $real_cache_dir )
            || dirname( $real_cache_dir ) !== $uploads_dir
            || basename( $real_cache_dir ) !== self::CACHE_DIRECTORY
        ) {
            return 'failed';
        }

        $status = $this->empty_cache_directory( $real_cache_dir, true );

        if ( ! $status['success'] ) {
            return 'failed';
        }

        return $status['deleted'] ? 'success' : 'no_files_found';
    }

    /**
     * Recursively remove files and subdirectories.
     *
     * @param string $dir      Directory to clean.
     * @param bool   $is_root  Whether this is the cache root.
     * @return array{success: bool, deleted: bool}
     */
    private function empty_cache_directory( $dir, $is_root = false ) {
        $status = array(
            'success' => true,
            'deleted' => false,
        );

        if ( ! is_dir( $dir ) || is_link( $dir ) ) {
            $status['success'] = false;
            return $status;
        }

        $entries = scandir( $dir );

        if ( false === $entries ) {
            $status['success'] = false;
            return $status;
        }

        foreach ( $entries as $entry ) {
            if ( '.' === $entry || '..' === $entry ) {
                continue;
            }

            if ( $is_root && 'index.php' === $entry ) {
                continue;
            }

            $path = trailingslashit( $dir ) . $entry;

            if ( is_link( $path ) ) {
                if ( ! unlink( $path ) ) {
                    $status['success'] = false;
                } else {
                    $status['deleted'] = true;
                }
                continue;
            }

            if ( is_dir( $path ) ) {
                $child_status = $this->empty_cache_directory( $path, false );

                if ( ! $child_status['success'] ) {
                    $status['success'] = false;
                    continue;
                }

                if ( ! rmdir( $path ) ) {
                    $status['success'] = false;
                } else {
                    $status['deleted'] = true;
                }
                continue;
            }

            if ( is_file( $path ) ) {
                if ( ! unlink( $path ) ) {
                    $status['success'] = false;
                } else {
                    $status['deleted'] = true;
                }
                continue;
            }

            $status['success'] = false;
        }

        return $status;
    }

    public function show_admin_notice() {
        if ( ! is_admin() ) {
            return;
        }
        $this->render_cache_notice();
    }

    public function show_frontend_notice() {
        if ( is_admin() ) {
            return;
        }
        $this->render_cache_notice();
    }

    /**
     * Render the cache result notice using Acorn Blade views.
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

        // Render via Acorn Blade view safely
        echo \Roots\view(
            'wpninetyplus::partials.cache-notice',
            compact( 'results' )
        )->render();
    }
}