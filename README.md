# WPNinetyPlus WordPress Plugin/PHPLibrary
WordPress Optimization 90+ Grade Speed Insight 


```PHP
// Hook Add Style Merge  | theme directory functions.php

add_filter( 'wpninetyplus_merge_style', function( $stylesheets ) {
   
    $stylesheets[] = 'two.css';
    $stylesheets[] = 'three.css';
    
    return $stylesheets;
});

// Ignore Assets
add_filter( 'wpninetyplus_cured_assets', function( $ignore_ids ) {
    
    $ignore_ids[] = 'my-custom-script-id';
    
    return $ignore_ids;
});

```

```PHP
/**
 * .env | plugin directory 
 * Free Version 
 * */ 

OPTIMIZED_CLIENT_HTML = true
OPTIMIZED_CLIENT_JAVASCRIPT = true 
OPTIMIZED_CLIENT_CSS = true
OPTIMIZED_CLIENT_LMERGE = true

```
