<?php 


add_filter( 'wpninetyplus_paint_optimization_rendered', 'wpninetyplus_paint_modifier', 10, 2 );

/**
 * Optional renderer filter hook modifier.
 * 
 * @since 1.3.0
 * @param string $skeleton The rendered HTML skeleton.
 * @param array  $ids      The array of excluded IDs.
 * @return string          Modified skeleton output.
 */
function wpninetyplus_paint_modifier( string $skeleton, array $ids ) : string {
    return $skeleton;
}