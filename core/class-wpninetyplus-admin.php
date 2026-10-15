<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.nielsoffice.com
 * @since      1.0.0
 *
 * @package    Wpninetyplus
 * @subpackage Wpninetyplus/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpninetyplus
 * @subpackage Wpninetyplus/admin
 * @author     nielsoffice <buildproject@nielsoffice.com>
 */
class Wpninetyplus_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpninetyplus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpninetyplus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpninetyplus-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpninetyplus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpninetyplus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpninetyplus.js', array( 'jquery' ), $this->version, false );
		

	}

	/**
	 * Register the stylesheets for the public/client area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles_public() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpninetyplus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpninetyplus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpninetyplus-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public/client area.
	 *
	 * @since    1.0.0
	 */
public function enqueue_scripts_public() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpninetyplus.js', array( 'jquery' ), $this->version, false );

        /**
         * This function is provided for demonstration purposes only.
         * ...
         */

        $wpnpi_enabled = defined('WPNPI') && WPNPI;
        $handle = $this->plugin_name . '-audit';

        if( $wpnpi_enabled ) :

            // 1. Enqueue your script as usual
            wp_enqueue_script( 
                $handle, 
                plugin_dir_url( __FILE__ ) . 'js/WPAudit.js', 
                array(), 
                $this->version, 
                false 
            );

            // Filter script tag to add type="module" for the enabled script
            add_filter( 'script_loader_tag', function( $tag, $handle_name, $src ) use ( $handle ) {
                if ( $handle === $handle_name ) {
                    $tag = str_replace( 'src=', 'type="module" src=', $tag );
                }
                return $tag;
            }, 10, 3 );

        else :
            
            // Register the script with $in_footer = true so it prints at the bottom of the footer
            $script_src = plugin_dir_url( __FILE__ ) . 'js/init.js';
            wp_register_script( $handle, $script_src, array(), $this->version, true );
            wp_enqueue_script( $handle );

            // Use a custom inline script block printed right after to handle the 6-second delay post-page load
            $inline_js = "window.addEventListener('load',function(){setTimeout(function(){var s=document.createElement('script');s.src='" . esc_url_raw( $script_src ) . "';s.id='" . esc_attr( $handle . '-js' ) . "';document.body.appendChild(s);},6000);});";
            wp_add_inline_script( $handle, $inline_js, 'after' );

        endif;

    }


}
