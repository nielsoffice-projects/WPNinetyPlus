<?php
/**
 * Plugin Name: WPNinetyPlus NinetyPlusCache Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Split cache engine and UI controller classes with singleton support.
 */

namespace WPNinetyPlus;

use WPNinetyPlus\NinetyPlusCacheUI;

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NinetyPlusCache
 * 
 * Core cache operations engine responsible for clearing database transients 
 * and purging files in the dedicated cache directory.
 *
 * @since 1.0.0
 */
final class NinetyPlusCache {

    /**
     * Cache directory name created inside the WordPress uploads directory.
     *
     * @since 1.0.0
     * @var string
     */
    private const CACHE_DIRECTORY = 'wpninetyplus-cache';

    /**
     * Transient prefix owned and used by this plugin.
     *
     * @since 1.0.0
     * @var string
     */
    private const TRANSIENT_PREFIX = '_transient_wpninetyplus_';

    /**
     * Transient timeout prefix owned and used by this plugin.
     *
     * @since 1.0.0
     * @var string
     */
    private const TIMEOUT_PREFIX = '_transient_timeout_wpninetyplus_';

    /**
     * Singleton instance of the class.
     *
     * @since 1.0.0
     * @var NinetyPlusCache|null
     */
    private static $instance = null;

    /**
     * Retrieves the singleton instance of the class.
     *
     * @since 1.0.0
     * @return NinetyPlusCache The singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        // Also ensure UI controller initializes its hooks when the core engine is called.
        NinetyPlusCacheUI::get_instance();

        return self::$instance;
    }

    /**
     * Deletes database-stored transients belonging specifically to this plugin.
     *
     * @since 1.0.0
     * @return string Operation status ('success' or 'failed').
     */
    public function clear_plugin_transients() {
        global $wpdb;

        $transient_pattern = $wpdb->esc_like( self::TRANSIENT_PREFIX ) . '%';
        $timeout_pattern   = $wpdb->esc_like( self::TIMEOUT_PREFIX ) . '%';

        // Query database options table to bulk delete plugin transients and timeouts.
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
     * Clears cached files in the plugin's dedicated cache directory.
     * Automatically provisions the folder and an index security file safely if missing.
     *
     * @since 1.0.0
     * @return string Operation status ('success', 'failed', or 'no_files_found').
     */
    public function clear_plugin_cache_directory() {
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

        // Automatically provision folder and index security file if missing.
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
     * Recursively removes files and subdirectories from the cache directory.
     *
     * @since 1.0.0
     * @param string $dir     Directory path to clean.
     * @param bool   $is_root Whether this is the root cache directory.
     * @return array{success: bool, deleted: bool} Operation success status array.
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

            // Protect root security index file from deletion.
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
}
