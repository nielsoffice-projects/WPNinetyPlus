<div align="center">

# 🚀 WPNinetyPlus (90+)
**WordPress Optimization & Speed Insights Plugin / PHP Library**

[![License: GNU GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-Plugin%20%2F%20Library-blue?logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP8.5%2B-purple?logo=php)](https://php.net)

*Achieve a 90+ Grade Speed Insight score effortlessly.*

</div>

---

## 📌 Table of Contents
- [🚀 WPNinetyPlus (90+)](#-wpninetyplus-90)
  - [📌 Table of Contents](#-table-of-contents)
  - [🧐 Overview](#-overview)
  - [⚙️ Configuration (`.env`)](#️-configuration-env)
    - [Hooks](#hooks)
    - [Hook](#hook)
    - [Hook](#hook-1)
  - [Enable Debugging Mode](#enable-debugging-mode)
    - [⚠️ Advanced Usage Warning](#️-advanced-usage-warning)
    - [Recommended Usage](#recommended-usage)
    - [Acknowledgments](#acknowledgments)
    - [License](#license)
    - [Support the Project](#support-the-project)
      - [Donation Options](#donation-options)

---

## 🧐 Overview
**WPNinetyPlus** is a high-performance WordPress optimization plugin and PHP library designed to supercharge your website's speed, core web vitals, and asset delivery pipelines. 

---

## ⚙️ Configuration (`.env`)

For the Free Version (no GUI), place your configuration in the plugin directory using an `.env` file:

```env

# Warning: Manager applicable to use for FullStackDevelopment
# Build Assets, Tailwind, Webpack, etc. 
WPNINETYPLUS_MANAGER = true

WPNINETYPLUS_SYNCASSETS = true

WPNINETYPLUS_ISOLATE = true
WPNINETYPLUS_INIT = false

# Warning: Full Paint requires a standard HTML document structure 
# using proper semantic tags (e.g., <head>, <body>, <header>, <main>, <aside> <section>, <footer>).
WPNINETYPLUS_FULLPAINT = true

OPTIMIZED_CLIENT_HTML = true
OPTIMIZED_CLIENT_JAVASCRIPT = true
OPTIMIZED_CLIENT_CSS = true
OPTIMIZED_CLIENT_LMERGE = true

# Warning: These advanced options increase file complexity and can 
# negatively impact website performance. Use only for specific edge cases.
WPNINETYPLUS_CUREDCLIENTHTML = true
WPNINETYPLUS_INTERINLINE = true
WPNINETYPLUS_INTERMERGE = true
WPNINETYPLUS_LAYMERGEINLINE = true

```

<details>
<summary><b>WPNINETYPLUS_SYNCASSETS</b></summary>

### Hooks

```
WPNINETYPLUS_SYNCASSETS = false
```

```PHP
/**
 * Define Keywords / Assets to Delay
 */
add_filter( 'wpninetyplus_sync_assets', function( $keywords ) {
    return array(
        // Add your target asset paths or strings here
    );
});

/**
 * 2. Define Font Preloads
 */
add_filter( 'wpninetyplus_sync_font_preloads', function( $preload_block,$theme_uri ) {
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
 *  4. Define Conditional Page Assets, Styles, and Scripts
 */
add_filter( 'wpninetyplus_sync_assets_conditional', function( $block, $theme_uri,$version ) {
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

</details>


<details>
<summary><b>OPTIMIZED_CLIENT_LMERGE</b></summary>

### Hook

```
OPTIMIZED_CLIENT_LMERGE = false
```

```PHP
// Hook Add Style Merge  | theme directory functions.php

add_filter( 'wpninetyplus_merge_style', function( $stylesheets ) {
   
    $stylesheets[] = 'two.css';
    $stylesheets[] = 'three.css';

   //  CDN by keyword or full URL
    $stylesheets[] = 'cdnjs.cloudflare.com/ajax/libs/font-awesome';
    
    return $stylesheets;
});
```

</details>


<details>
<summary><b>WPNINETYPLUS_INTERMERGE</b></summary>

### Hook

```
WPNINETYPLUS_INTERMERGE = false
```

```PHP
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

```

</details>

```PHP


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

## Enable Debugging Mode

| Function | Description | Focus Area |
| :--- | :--- | :--- |
| **`WPNinetyPlus()`** | Executes a comprehensive, full-site performance audit to identify bottlenecks and elevate overall Core Web Vitals. | Full Website Audit |
| **`WPNinetyPlusAssetsInteractive()`** | Deep-dives into JavaScript execution, script loading behavior, and interactivity bottlenecks to minimize Total Blocking Time (TBT). | JavaScript Optimization |
| **`WPNinetyPlusAsstesLayout()`** | Analyzes CSS delivery, uncovers render-blocking stylesheets, and optimizes layout shifts for smoother rendering. | CSS & Layout Performance |
| **`WPNinetyPlusAssetsPaint()`** | Audits typography delivery, web fonts, and favicons to ensure fast First Contentful Paint (FCP) and prevent flash of unstyled text. | Fonts & Visual Elements |
| **`WPNinetyPlusGetImages()`** | Catalogs and analyzes all image assets, verifying next-gen format usage, proper sizing, and lazy-loading implementation. | Image Optimization |
| **`WPNinetyPlusGetVideos()`** | Scans, inspects, and audits embedded and hosted video content, reviewing compression, streaming efficiency, and lazy-loading policies to eliminate bandwidth bloat. | Video & Media Optimization |
| **`WPNinetyPlusMetaData()`** | Scans and validates meta tags, Open Graph data, and technical SEO markers to ensure search engine readiness and social share efficiency. | SEO & Metadata |



---

### ⚠️ Advanced Usage Warning
These filters may affect when resources load, how the browser renders the page, or how dynamic content behaves. Validate the generated HTML and test interactive components after making changes.

---

### Recommended Usage
For the best results when integrating WPNinetyPlus into a WordPress website:
1. **Start with a baseline.** Run a performance test before enabling additional optimization options.
2. **Enable features incrementally.** Introduce one optimization at a time so that any regressions are easier to identify.
3. **Respect dependencies.** Avoid merging scripts that require a specific loading order unless the merge process preserves it.
4. **Optimize critical resources.** Prioritize the CSS, JavaScript, images, and fonts required for the initial viewport.
5. **Test dynamic functionality.** Verify navigation, forms, menus, third-party integrations, and interactive components.
6. **Measure real results.** Compare PageSpeed Insights and Core Web Vitals before and after each significant change.
7. **Keep a rollback path.** Maintain a tested configuration and backup before enabling advanced transformations in production.

> Remember that aggressive optimization is not always better. The objective is to improve real-world performance while preserving website functionality and compatibility.

---

### Acknowledgments
WPNinetyPlus is made possible by the tools, documentation, and open-source ecosystem that support WordPress development. 

Special thanks to:
* **GitHub** — For providing a platform to host, maintain, and share this project.
* **PHP** — For the language and documentation that support PHP development.
* **WordPress** — For the publishing platform, plugin architecture, and developer documentation that make WordPress extensions possible.

Thank you to everyone who contributes to open-source development and website performance optimization.

---

### License
**GNU General Public License v3.0**

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NONINFRINGEMENT. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO EVENT SHALL THE AUTHORS, DEVELOPERS, CONTRIBUTORS, OR COPYRIGHT HOLDERS OF THIS WORDPRESS PLUGIN BE LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT, OR OTHERWISE, ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE, THE USE OR OTHER DEALINGS IN THE SOFTWARE, OR ANY MODIFICATION, DISTRIBUTION, OR INTEGRATION OF THE PLUGIN WITH WORDPRESS OR OTHER SOFTWARE.

---

### Support the Project
☕ Would you like to treat me to a coffee or a cake?

Your support helps make continued development, maintenance, and new optimization features possible. Every contribution, no matter the size, is greatly appreciated. Together, we can build better tools for the WordPress community.

#### Donation Options
* **GCash:** `+639650332900`
* **PayPal:** `syncdevprojects@gmail.com`

Thank you for supporting independent development and helping WPNinetyPlus grow.