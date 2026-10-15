<?php 

/**
 * @since Oct 13, 2026
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WPNINETYPLUS_VERSION', '1.0.0' );

/**
 * @since Oct 115, 2026
 * WPNinetyPlus Style CSS Merge in client side
 */
define('WPNPI', env('WPNINETYPLUS_ISOLATE') === true ? false : true );

/**
 * @since Oct 13, 2026
 * Protect JS Script in client side
 */
define('PST', env('PRIVATE_SCRIPT_TAG') === true ? false : true );

/**
 * @since Oct 13, 2026
 * WPNinetyPlus HTML in client side
 */
define('OCH', env('OPTIMIZED_CLIENT_HTML') === true ? false : true );

/**
 * @since Oct 13, 2026
 * WPNinetyPlus JS Script in client side
 */
define('OCJ', env('OPTIMIZED_CLIENT_JAVASCRIPT') === true ? false : true );

/**
 * @since Oct 13, 2026
 * WPNinetyPlus Style CSS in client side
 */
define('OCC', env('OPTIMIZED_CLIENT_CSS') === true ? false : true );

/**
 * @since Oct 13, 2026
 * WPNinetyPlus Style CSS Merge in client side
 */
define('OCLM', env('OPTIMIZED_CLIENT_LMERGE') === true ? false : true );


