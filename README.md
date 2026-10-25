# WPNinetyPlus (90+) (WPPlugin/PHPLibrary)
WordPress Optimization 90+ Grade Speed Insight 

```PHP
// Hook Add Style Merge  | theme directory functions.php

add_filter( 'wpninetyplus_merge_style', function( $stylesheets ) {
   
    $stylesheets[] = 'two.css';
    $stylesheets[] = 'three.css';

   //  CDN by keyword or full URL
    $stylesheets[] = 'cdnjs.cloudflare.com/ajax/libs/font-awesome';
    
    return $stylesheets;
});

// Hook Add Script Merge | These always end of the </body Tag>
add_filter( 'wpninetyplus_merge_script', function( $scripts ) {
    
    // 1. Local JS file name
    $scripts[] = 'custom-script.js';
    
    // 2. External CDN JS URL or keyword
    $scripts[] = 'cdnjs.cloudflare.com/ajax/libs/jquery';
    
    // 3. Inline script ID (e.g., <script id="inline_ids">...)
    $scripts[] = 'inline_ids';
    
    return $scripts;
});

// inline scripts to the end of the <head> (General Inline)
// WPNINETYPLUS_INTERMERGE = false
add_filter( 'wpninetyplus_inline_script', function( $position ) {
    return 'head'; 
});

// inline scripts to the end of the <body> (Default | General Inline)
// WPNINETYPLUS_INTERMERGE = false
add_filter( 'wpninetyplus_inline_script', function( $position ) {
    return 'footer'; 
});

// Ignore Assets
add_filter( 'wpninetyplus_cured_assets', function( $ignore_ids ) {
    
    $ignore_ids[] = 'my-custom-script-id';
    
    return $ignore_ids;
});

```

```PHP
/**
 * .env | plugin directory for Free Version no GUI
 * 
 * */ 

WPNINETYPLUS_MANAGER = true 
WPNINETYPLUS_SYNCASSETS = true

WPNINETYPLUS_ISOLATE = true
WPNINETYPLUS_INIT = false

/**
 * Warning: Full Paint requires a standard HTML document structure 
 * using proper semantic tags (e.g., <head>, <body>, <header>, <main>, <section>, <footer>).
 */
WPNINETYPLUS_FULLPAINT = true 

OPTIMIZED_CLIENT_HTML = true
OPTIMIZED_CLIENT_JAVASCRIPT = true 
OPTIMIZED_CLIENT_CSS = true
OPTIMIZED_CLIENT_LMERGE = true

/**
 * Warning: These advanced options increase file complexity and can 
 * negatively impact website performance. They are not recommended 
 * for general use and are intended only for specific edge cases.
 */
WPNINETYPLUS_CUREDCLIENTHTML = true
WPNINETYPLUS_INTERINLINE = true
WPNINETYPLUS_INTERMERGE = true
WPNINETYPLUS_LAYMERGEINLINE = true

```

| Function | Description | Focus Area |
| :--- | :--- | :--- |
| **`WPNinetyPlus()`** | Executes a comprehensive, full-site performance audit to identify bottlenecks and elevate overall Core Web Vitals. | Full Website Audit |
| **`WPNinetyPlusAssetsInteractive()`** | Deep-dives into JavaScript execution, script loading behavior, and interactivity bottlenecks to minimize Total Blocking Time (TBT). | JavaScript Optimization |
| **`WPNinetyPlusAsstesLayout()`** | Analyzes CSS delivery, uncovers render-blocking stylesheets, and optimizes layout shifts for smoother rendering. | CSS & Layout Performance |
| **`WPNinetyPlusAssetsPaint()`** | Audits typography delivery, web fonts, and favicons to ensure fast First Contentful Paint (FCP) and prevent flash of unstyled text. | Fonts & Visual Elements |
| **`WPNinetyPlusGetImages()`** | Catalogs and analyzes all image assets, verifying next-gen format usage, proper sizing, and lazy-loading implementation. | Image Optimization |
| **`WPNinetyPlusGetVideos()`** | Scans, inspects, and audits embedded and hosted video content, reviewing compression, streaming efficiency, and lazy-loading policies to eliminate bandwidth bloat. | Video & Media Optimization |
| **`WPNinetyPlusMetaData()`** | Scans and validates meta tags, Open Graph data, and technical SEO markers to ensure search engine readiness and social share efficiency. | SEO & Metadata |

```PHP
Hooks/Filters: 


/**
 * 1. Define Keywords / Assets to Delay
 */
add_filter( 'wpninetyplus_sync_assets', function( $keywords ) {
    return array(
        // Add your target asset paths or strings here
    );
});

/**
 * 2. Define Font Preloads
 */
add_filter( 'wpninetyplus_sync_font_preloads', function( $preload_block, $theme_uri ) {
    return $preload_block;
}, 10, 2 );

/**
 * 3. Define Dynamic Loading Timeout Period Logic
 */
add_filter( 'wpninetyplus_loading_period', function( $default_expression ) {
   return $preload_block;
});

add_filter( 'wpninetyplus_sync_loading_period', function( $js_string ) {
    return 'var loadingPeriod = window.innerWidth <= 680 ? 2000 : (window.innerWidth <= 2014 ? 2500 : 2000);';
});

/**
 * 4. Define Conditional Page Assets, Styles, and Scripts
 */
add_filter( 'wpninetyplus_sync_assets_conditional', function( $block, $theme_uri, $version ) {
    return $block;
}, 10, 3 );

/**
 * 5. Define Selectors to Remove .r-temporary Class From
 */
add_filter( 'wpninetyplus_sync_temporary', function( $selectors ) {
    return array(
        // Add selectors here, e.g., '.content-blocks .content-blocks__item'
    );
});

```


<b>Thanks To:</b>
<br>Github : To allow me to upload my WordPress Plugin.
<br>php.net : The opportunity to develop using corePHP
<br>WordPress : The opportunity to develop plugins using corePHP/WPCodex - Plugin/Library
<br>
<br><b>LICENSE BY GNU 3.0</b>
<br>THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NONINFRINGEMENT. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO EVENT SHALL THE AUTHORS, DEVELOPERS, CONTRIBUTORS, OR COPYRIGHT HOLDERS OF THIS WORDPRESS PLUGIN BE LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT, OR OTHERWISE, ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE, THE USE OR OTHER DEALINGS IN THE SOFTWARE, OR ANY MODIFICATION, DISTRIBUTION, OR INTEGRATION OF THE PLUGIN WITH WORDPRESS OR OTHER SOFTWARE.

<br>Would you like me to treat a cake and coffee ?
<br>Become a donor, Because with you! We can build more...
<br><b>GCash</b> : +639650332900
<br><b>Paypal account</b>: syncdevprojects@gmail.com
