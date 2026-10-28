<?php 

add_action('wp_head', fn() => print('<script id="interactive" defer>document.addEventListener("DOMContentLoaded",()=>setTimeout(()=>document.querySelectorAll(".wpninetyplus-lframe").forEach(e=>e.classList.remove("wpninetyplus-lframe")),200));' . do_action('wpninetyplus_safe_js') . '</script>'), 5);

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

