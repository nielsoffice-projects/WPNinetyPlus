<?php 


add_action( 'init', function () {
    error_log( 'WPNinetyPlus: registering Blade views' );

    if ( function_exists( 'view' ) ) {
        $view_path = dirname( __DIR__ ) . '/resources/views';

        error_log( 'WPNinetyPlus view path: ' . $view_path );
        error_log(
            'WPNinetyPlus view directory exists: ' .
            ( is_dir( $view_path ) ? 'yes' : 'no' )
        );

        \Roots\view()->addNamespace( 'wpninetyplus', $view_path );
    } else {
        error_log( 'WPNinetyPlus: view() helper is unavailable' );
    }
}, 10 );