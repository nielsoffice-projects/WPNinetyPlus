<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.nielsoffice.com/
 * @since             1.0.0
 * @package           Wpninetyplus
 *
 * @wordpress-plugin
 * Plugin Name:       WPNinetyPlus
 * Plugin URI:        https://github.com/nielsoffice-projects/WPNinetyPlus
 * Description:       WP Ninety Plus Grade Google Speed Insight Fully Optimize cross devices Desktop, Tablet, and Mobile WordPress Website
 * Version:           1.0.0
 * Author:            nielsoffice
 * Author URI:        https://www.nielsoffice.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpninetyplus
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/***
 * WPNinetyPlus: Installed ENV
 * This action is: env('APP_NAME', 'My Theme') 
 **/
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Get Helpers Files
foreach (['config', 'apps'] as $directory) {
	
    $path = __DIR__ . '/' . $directory;

    if (! is_dir($path)) {
        continue;
    }

    foreach ((new RecursiveIteratorIterator(
        	new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS
        ) )) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getPathname();
        }
    }
}

// Safe Patch .ENV 
add_action('after_setup_theme', function () {
    if (class_exists(\Roots\Acorn\Application::class)) {
        \Roots\Acorn\Application::configure()->boot();
    }
}, 0);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpninetyplus-activator.php
 */
function activate_wpninetyplus() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpninetyplus-activator.php';
	Wpninetyplus_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpninetyplus-deactivator.php
 */
function deactivate_wpninetyplus() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpninetyplus-deactivator.php';
	Wpninetyplus_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpninetyplus' );
register_deactivation_hook( __FILE__, 'deactivate_wpninetyplus' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpninetyplus.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpninetyplus() {

	$plugin = new Wpninetyplus();
	$plugin->run();

}
run_wpninetyplus();
