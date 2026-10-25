<?php
/**
 * Plugin Constants Configuration
 *
 * Defines core configuration flags, feature toggles, and versioning 
 * for the WordPress Optimization Ninety Plus plugin, leveraging environment 
 * variables where available.
 *
 * @package Wpninetyplus
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Plugin version identifier.
 * Rename this for your plugin and update it as you release new versions.
 *
 * @since 1.0.0
 */
define( 'WPNINETYPLUS_VERSION', '1.0.0' );

/**
 * Enables or disables the WPNinetyPlus Manager / Cured Assets Injection feature.
 *
 * @since Oct 16, 2026
 */
define( 'WNPIA', env( 'WPNINETYPLUS_MANAGER' ) === true ? false : true );

/**
 * Enables or disables the WPNinetyPlus Cured Assets Synchronization feature.
 *
 * @since Oct 16, 2026
 */
define( 'WPNSA', env( 'WPNINETYPLUS_SYNCASSETS' ) === true ? false : true );

/**
 * Enables or disables the WPNinetyPlus Initialization feature flag.
 *
 * @since Oct 16, 2026
 */
define( 'WPNPD', env( 'WPNINETYPLUS_INIT' ) === true ? false : true );

/**
 * Enables or disables the WPNinetyPlus HTML/Asset Isolation feature.
 *
 * @since Oct 15, 2026
 */
define( 'WPNPI', env( 'WPNINETYPLUS_ISOLATE' ) === true ? false : true );

/**
 * Protects and handles Private Script Tags on the client side.
 *
 * @since Oct 13, 2026
 */
define( 'PST', env( 'PRIVATE_SCRIPT_TAG' ) === true ? false : true );

/**
 * Enables or disables Optimized Client HTML buffering.
 *
 * @since Oct 13, 2026
 */
define( 'OCH', env( 'OPTIMIZED_CLIENT_HTML' ) === true ? false : true );

/**
 * Enables or disables Optimized Client JavaScript processing.
 *
 * @since Oct 13, 2026
 */
define( 'OCJ', env( 'OPTIMIZED_CLIENT_JAVASCRIPT' ) === true ? false : true );

/**
 * Enables or disables Optimized Client CSS handling.
 *
 * @since Oct 13, 2026
 */
define( 'OCC', env( 'OPTIMIZED_CLIENT_CSS' ) === true ? false : true );

/**
 * Enables or disables Optimized Client Layout Merge.
 *
 * @since Oct 13, 2026
 */
define( 'OCLM', env( 'OPTIMIZED_CLIENT_LMERGE' ) === true ? false : true );

/**
 * Enables or disables WPNinetyPlus Full Paint execution.
 *
 * @since Oct 19, 2026
 */
define( 'WPNPFP', env( 'WPNINETYPLUS_FULLPAINT' ) === true ? false : true );

/**
 * Enables or disables WPNinetyPlus Cured HTML on the client side.
 *
 * @since Oct 25, 2026
 */
define( 'WPNPCCH', env( 'WPNINETYPLUS_CUREDCLIENTHTML' ) === true ? false : true );

/**
 * Enables or disables WPNinetyPlus Interactive Inline script execution on the client side.
 *
 * @since Oct 25, 2026
 */
define( 'WPNPIIE', env( 'WPNINETYPLUS_INTERINLINE' ) === true ? false : true );

/**
 * Enables or disables WPNinetyPlus Interactive Merge functionality on the client side.
 *
 * @since Oct 25, 2026
 */
define( 'WPNPIM', env( 'WPNINETYPLUS_INTERMERGE' ) === true ? false : true );

/**
 * Enables or disables WPNinetyPlus Layout Merge Inline feature on the client side.
 *
 * @since Oct 25, 2026
 */
define( 'WPNPMI', env( 'WPNINETYPLUS_MERGEINLINE' ) === true ? false : true );