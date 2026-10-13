# WPNinetyPlus WordPress Plugin/PHPLibrary
WordPress Optimization 90+ Grade Speed Insight 


```PHP
// Hook Add Style Merge  

add_filter( 'wpninetyplus_merge_style', function( $stylesheets ) {
   
    $stylesheets[] = 'two.css';
    $stylesheets[] = 'three.css';
    
    return $stylesheets;
});

```

```PHP
/**
 * .env | plugin directory 
 * Free Version 
 * */ 

PRIVATE_SCRIPT_TAG = true
OPTIMIZED_CLIENT_HTML = true
OPTIMIZED_CLIENT_JAVASCRIPT = true 
OPTIMIZED_CLIENT_CSS = true
OPTIMIZED_CLIENT_LMERGE = true

```
