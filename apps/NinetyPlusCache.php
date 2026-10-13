<?php 
/**
 * The plugin : WordPress Optimization Ninety Plus (90+) Grade Speed Insight 
 *
 * Clear / Flushing Cache WP 
 *
 * @link              https://www.nielsoffice.com/        
 * @since             1.0.0
 * @package           Wpninetyplus
 *
 */

namespace WPNinetyPlus;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class NinetyPlusCache {

    /**
     * Instance of this class.
     *
     * @var NinetyPlusCache
     */
    private static $instance = null;

    /**
     * Get singleton instance.
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
     * Constructor.
     */
    private function __construct() {
        add_action( 'admin_bar_menu', array( $this, 'add_toolbar_item' ), 100 );
        add_action( 'admin_post_admin_bar_clear_all_cache', array( $this, 'handle_cache_clear' ) );
        
        // Hook into both admin and front-end notices/heads to display everywhere
        add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
        add_action( 'wp_footer', array( $this, 'show_admin_notice' ) );
    }

    /**
     * Add toolbar item for administrators.
     *
     * @param WP_Admin_Bar $admin_bar
     */
    public function add_toolbar_item( $admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $admin_bar->add_node( array(
            'id'    => 'clear-all-site-cache',
            'title' => '🧹90+ ClearCache',
            'href'  => wp_nonce_url(
                admin_url( 'admin-post.php?action=admin_bar_clear_all_cache' ),
                'admin_bar_clear_all_cache'
            ),
            'meta'  => array(
                'title' => 'Clear object cache, transients, and 90+ plugin files'
            ),
        ) );
    }

    /**
     * Clear core WordPress and native plugin caches.
     */
    public function handle_cache_clear() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized.', '', array( 'response' => 403 ) );
        }

        check_admin_referer( 'admin_bar_clear_all_cache' );

        $results = array();

        // 1. Clear the WordPress object cache.
        $results['object_cache'] = wp_cache_flush() ? 'success' : 'failed';

        // 2. Delete database-stored transient records only.
        if ( ! wp_using_ext_object_cache() ) {
            global $wpdb;

            $like_value   = $wpdb->esc_like( '_transient_' ) . '%';
            $like_timeout = $wpdb->esc_like( '_transient_timeout_' ) . '%';

            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options}
                     WHERE option_name LIKE %s
                        OR option_name LIKE %s",
                    $like_value,
                    $like_timeout
                )
            );

            $results['database_transients'] = ( false === $deleted ) ? 'failed' : 'success';
        } else {
            $results['database_transients'] = 'skipped_external_object_cache';
        }

        // 3. Clear your plugin's native transient reports/data.
        global $wpdb;
        $plugin_transients_deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpninetyplus_%' OR option_name LIKE '_transient_timeout_wpninetyplus_%'"
        );
        $results['plugin_reports_cache'] = ( false !== $plugin_transients_deleted ) ? 'success' : 'failed';

        // 4. Clear your plugin's local generated files or folders inside uploads.
        $upload_dir = wp_upload_dir();
        $plugin_cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'wpninetyplus-cache/';
        
        if ( file_exists( $plugin_cache_dir ) ) {
            $results['plugin_file_cache'] = $this->clear_directory_files( $plugin_cache_dir ) ? 'success' : 'no_files_found';
        } else {
            $results['plugin_file_cache'] = 'no_cache_folder';
        }

        // Store results in a transient tied to the user ID before redirecting.
        set_transient(
            'admin_bar_cache_result_' . get_current_user_id(),
            $results,
            60
        );

        // Redirect back to the exact page they clicked from
        $redirect_url = wp_get_referer() ? wp_get_referer() : admin_url();
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Helper method to recursively empty files inside your custom cache directory.
     *
     * @param string $dir
     * @return bool
     */
    private function clear_directory_files( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return false;
        }

        $files = glob( $dir . '*', GLOB_MARK );
        foreach ( $files as $file ) {
            if ( is_dir( $file ) ) {
                $this->clear_directory_files( $file );
                @rmdir( $file );
            } else {
                @unlink( $file );
            }
        }
        return true;
    }

    /**
     * Show a detailed completion notice breaking down each cache layer (anywhere on site) and fade out.
     */
    public function show_admin_notice() {
        // Ensure only target users with a pending cache result flag trigger this
        if (
            ! current_user_can( 'manage_options' )
            || ! isset( $_GET['cache_clear_result'] )
            || '1' !== sanitize_text_field( wp_unslash( $_GET['cache_clear_result'] ) )
        ) {
            return;
        }

        // Prevent duplicate rendering if both admin_notices and wp_footer fire
        static $rendered = false;
        if ( $rendered ) {
            return;
        }
        $rendered = true;

        $key     = 'admin_bar_cache_result_' . get_current_user_id();
        $results = get_transient( $key );
        delete_transient( $key );

        // Build output markup wrapper with a unique ID for fading
        echo '<div id="ninetyplus-cache-notice" class="notice notice-success" style="position:fixed; top:32px; right:20px; z-index:999999; background:#fff; border-left:4px solid #46b450; box-shadow:0 2px 5px rgba(0,0,0,0.2); padding:12px 15px; max-width:350px;">';
        echo '<p style="margin:0 0 5px 0;"><strong>Ninety Plus Cache Clear Status:</strong></p>';
        
        if ( ! is_array( $results ) ) {
            echo '<p style="margin:0;">Cache cleared successfully!</p>';
        } else {
            echo '<ul style="list-style-type: disc; margin:0 0 0 20px; padding:0;">';
            foreach ( $results as $layer => $status ) {
                echo '<li><strong>' . esc_html( ucwords( str_replace( '_', ' ', $layer ) ) ) . ':</strong> ' . esc_html( $status ) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';

        // JavaScript snippet to fade out and remove the element after 2 seconds
        ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    var notice = document.getElementById('ninetyplus-cache-notice');
                    if (notice) {
                        notice.style.transition = "opacity 0.8s ease";
                        notice.style.opacity = "0";
                        setTimeout(function() {
                            notice.remove();
                        }, 800);
                    }
                }, 2000); // Wait 2 seconds before starting fade
            });
        </script>
        <?php
    }
}

