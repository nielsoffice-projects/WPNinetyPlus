WPNinetyPlus (90+)
WordPress Performance Optimization Library

Optimize your WordPress website. Improve loading performance. Aim for a 90+ PageSpeed Insights score.

WPNinetyPlus is a WordPress optimization plugin and PHP library designed to help developers analyze website performance, optimize asset delivery, and improve the overall user experience.

It provides tools for auditing JavaScript, CSS, images, videos, fonts, metadata, and other performance-related resources, alongside configurable hooks for fine-grained optimization.

Goal: Help developers identify performance bottlenecks and build faster, more efficient WordPress websites.
Table of Contents
Overview
Key Features
Installation and Configuration
Asset Merging and Customization
Configuration Reference
Core Functions
Advanced Hooks and Filters
Recommended Usage
Acknowledgments
License
Support the Project
Overview

WPNinetyPlus is built to support WordPress performance optimization through asset analysis, configurable resource handling, and client-side optimization techniques.

Depending on your configuration and website architecture, it can help you:

Analyze website performance and identify potential bottlenecks.
Merge selected CSS and JavaScript assets.
Customize script placement and inline script handling.
Configure asset synchronization and conditional loading.
Review image, video, font, and metadata resources.
Investigate rendering performance and layout-related issues.
Fine-tune optimization behavior through WordPress hooks and filters.

Important: Performance results depend on your theme, plugins, hosting environment, caching configuration, and website content. A 90+ PageSpeed Insights score is a target, not a guaranteed result.

Key Features
Feature	Description
Website Performance Auditing	Analyze performance-related resources and identify potential bottlenecks.
JavaScript Optimization	Inspect script loading, execution behavior, and interactivity-related performance.
CSS Optimization	Review stylesheet delivery, render-blocking resources, and layout behavior.
Image Optimization Analysis	Inspect image assets, sizing, formats, and lazy-loading opportunities.
Video Optimization Analysis	Review embedded and hosted video resources for potential performance improvements.
Font and Paint Analysis	Inspect web fonts, typography resources, and visual rendering considerations.
SEO and Metadata Analysis	Review metadata, Open Graph information, and technical SEO markers.
Custom Asset Management	Configure stylesheet and script merging through WordPress filters.
Advanced Configuration	Adjust optimization behavior to suit specific themes and website architectures.
Installation and Configuration
1. Install the Plugin

Install WPNinetyPlus in your WordPress environment using your preferred deployment method.

For the free version without a graphical configuration interface, settings can be managed through the .env file located in the plugin directory.

2. Configure the Environment

The following is an example configuration based on the available WPNinetyPlus constants:

php
/**
 * WPNinetyPlus Configuration
 *
 * Configure these values according to your website's requirements.
 */

WPNINETYPLUS_MANAGER = true;
WPNINETYPLUS_SYNCASSETS = true;

WPNINETYPLUS_ISOLATE = true;
WPNINETYPLUS_INIT = false;

/**
 * Full Paint Optimization
 *
 * Requires a standard HTML document structure with appropriate
 * semantic elements, including <head> and <body>.
 */
WPNINETYPLUS_FULLPAINT = true;

/**
 * Client-Side Optimization
 */
OPTIMIZED_CLIENT_HTML = true;
OPTIMIZED_CLIENT_JAVASCRIPT = true;
OPTIMIZED_CLIENT_CSS = true;
OPTIMIZED_CLIENT_LMERGE = true;

/**
 * Advanced Optimization Options
 *
 * These options may increase implementation complexity or
 * introduce compatibility and performance issues.
 * Enable them only when required and thoroughly tested.
 */
WPNINETYPLUS_CUREDCLIENTHTML = true;
WPNINETYPLUS_INTERINLINE = true;
WPNINETYPLUS_INTERMERGE = true;
WPNINETYPLUS_LAYMERGEINLINE = true;


Configuration note: The snippet above preserves your original setting names and values. If these are intended to be literal PHP code, bare assignments such as WPNINETYPLUS_MANAGER = true; are not valid PHP constant declarations. If they are intended as .env settings, use the format expected by your configuration loader, typically WPNINETYPLUS_MANAGER=true, without PHP semicolons. Define PHP constants using define() when appropriate.

Compatibility warning: Advanced HTML, CSS, and JavaScript transformations may affect themes, plugins, inline scripts, and dynamically generated content. Test configuration changes in a staging environment before applying them to a production website.

Asset Merging and Customization

WPNinetyPlus provides WordPress filters that let developers customize which stylesheets and scripts are selected for merging, where inline scripts are placed, and which assets are excluded from optimization.

Add the following examples to your active theme's functions.php file or, preferably, a site-specific plugin.

1. Merge Custom Stylesheets

Use the wpninetyplus_merge_style filter to register stylesheets or external CSS resources for merging.

php
/**
 * Add stylesheets to the WPNinetyPlus merge process.
 */
add_filter( 'wpninetyplus_merge_style', function( $stylesheets ) {

    // Local stylesheet filenames.
    $stylesheets[] = 'two.css';
    $stylesheets[] = 'three.css';

    // External CDN URL or matching asset keyword.
    $stylesheets[] = 'cdnjs.cloudflare.com/ajax/libs/font-awesome';

    return $stylesheets;
} );

2. Merge Custom JavaScript

Use the wpninetyplus_merge_script filter to register local JavaScript files, external scripts, or supported inline script IDs.

php
/**
 * Add JavaScript assets to the WPNinetyPlus merge process.
 */
add_filter( 'wpninetyplus_merge_script', function( $scripts ) {

    // Local JavaScript file.
    $scripts[] = 'custom-script.js';

    // External CDN URL or matching asset keyword.
    $scripts[] = 'cdnjs.cloudflare.com/ajax/libs/jquery';

    // ID of a supported inline script.
    $scripts[] = 'inline_ids';

    return $scripts;
} );


Note: According to the intended behavior of this configuration, merged scripts are placed near the closing </body> tag. Verify the plugin's merging rules and dependencies before merging scripts that rely on execution order.

3. Configure Inline Script Placement

Use wpninetyplus_inline_script to select the intended placement of general inline scripts when WPNINETYPLUS_INTERMERGE is disabled.

Place inline scripts in the document head:

php
/**
 * Place general inline scripts in the document head.
 */
add_filter( 'wpninetyplus_inline_script', function( $position ) {
    return 'head';
} );


Place inline scripts in the footer:

php
/**
 * Place general inline scripts in the document footer.
 */
add_filter( 'wpninetyplus_inline_script', function( $position ) {
    return 'footer';
} );


Use one configuration at a time. If both callbacks are registered, the later callback may override the earlier result, depending on WordPress filter execution order.

4. Exclude Specific Assets from Optimization

Use wpninetyplus_cured_assets to add asset identifiers that should be excluded from the applicable optimization process.

php
/**
 * Exclude specific assets from optimization.
 */
add_filter( 'wpninetyplus_cured_assets', function( $ignore_ids ) {

    $ignore_ids[] = 'my-custom-script-id';

    return $ignore_ids;
} );


Replace my-custom-script-id with the actual asset identifier recognized by WPNinetyPlus.

Configuration Reference

The following table summarizes the configuration constants and switches provided in the supplied example.

Constant	Example Value	Purpose
WPNINETYPLUS_MANAGER	true	Enables the manager configuration option.
WPNINETYPLUS_SYNCASSETS	true	Enables the asset synchronization option.
WPNINETYPLUS_ISOLATE	true	Enables the isolation option.
WPNINETYPLUS_INIT	false	Controls the initialization option.
WPNINETYPLUS_FULLPAINT	true	Enables the full-paint optimization option.
OPTIMIZED_CLIENT_HTML	true	Enables the client HTML optimization option.
OPTIMIZED_CLIENT_JAVASCRIPT	true	Enables the client JavaScript optimization option.
OPTIMIZED_CLIENT_CSS	true	Enables the client CSS optimization option.
OPTIMIZED_CLIENT_LMERGE	true	Enables the client-side merge option.
WPNINETYPLUS_CUREDCLIENTHTML	true	Enables the advanced client HTML transformation option.
WPNINETYPLUS_INTERINLINE	true	Enables the advanced inline-processing option.
WPNINETYPLUS_INTERMERGE	true	Enables the advanced inter-merge option.
WPNINETYPLUS_LAYMERGEINLINE	true	Enables the advanced layout/inline merge option.
Important: The descriptions above reflect the apparent intent of the names and your supplied comments. Confirm the implementation of each option in the plugin source code before relying on its exact behavior.
Core Functions

WPNinetyPlus exposes the following function names for performance analysis and resource inspection.

Function	Description	Focus Area
WPNinetyPlus()	Performs a comprehensive website performance audit to help identify optimization opportunities.	Website Performance
WPNinetyPlusAssetsInteractive()	Examines JavaScript loading and execution behavior, with a focus on interactivity and potential Total Blocking Time (TBT) improvements.	JavaScript Optimization
WPNinetyPlusAsstesLayout()	Analyzes stylesheet delivery, potential render-blocking CSS, and layout-related performance issues.	CSS and Layout
WPNinetyPlusAssetsPaint()	Inspects fonts, typography resources, and favicons that may affect visual rendering.	Fonts and Rendering
WPNinetyPlusGetImages()	Collects and analyzes image resources for sizing, formats, and lazy-loading opportunities.	Image Optimization
WPNinetyPlusGetVideos()	Inspects video resources for potential improvements in loading behavior, compression, and bandwidth usage.	Video Optimization
WPNinetyPlusMetaData()	Examines metadata, Open Graph information, and technical SEO-related markers.	SEO and Metadata

Developer note: The function name WPNinetyPlusAsstesLayout() is preserved exactly as provided. If it is a typo in the implementation, consider correcting it to WPNinetyPlusAssetsLayout() and updating all existing references before release.

Advanced Hooks and Filters

WPNinetyPlus includes additional filters for asset synchronization, font preloading, loading-period logic, conditional resource handling, and temporary CSS class cleanup.

1. Define Assets for Synchronization

Use wpninetyplus_sync_assets to specify the asset paths or strings targeted by the synchronization process.

php
add_filter( 'wpninetyplus_sync_assets', function( $keywords ) {

    return array(
        // Add target asset paths or matching strings here.
        // Example: '/assets/js/custom-script.js',
    );
} );

2. Customize Font Preloads

Use wpninetyplus_sync_font_preloads to customize the generated font preload block.

php
add_filter(
    'wpninetyplus_sync_font_preloads',
    function( $preload_block, $theme_uri ) {

        // Customize or return the generated preload block.
        return $preload_block;

    },
    10,
    2
);


Use appropriate preload declarations and prioritize only fonts needed for the initial viewport to avoid unnecessary network requests.

3. Customize the Loading Period

Use wpninetyplus_loading_period to adjust the default loading-period expression.

php
add_filter( 'wpninetyplus_loading_period', function( $default_expression ) {

    // Preserve the default expression.
    return $default_expression;

} );


The wpninetyplus_sync_loading_period filter can be used to provide a custom JavaScript expression for responsive loading timing.

php
add_filter( 'wpninetyplus_sync_loading_period', function( $js_string ) {

    return 'var loadingPeriod = window.innerWidth <= 680 ? 2000 : (window.innerWidth <= 2014 ? 2500 : 2000);';

} );


The timing values above are examples, not universal performance recommendations. Measure the results on your target devices and network conditions.

4. Define Conditional Page Assets

Use wpninetyplus_sync_assets_conditional to customize conditional asset handling.

php
add_filter(
    'wpninetyplus_sync_assets_conditional',
    function( $block, $theme_uri, $version ) {

        // Customize or return the generated conditional asset block.
        return $block;

    },
    10,
    3
);

5. Configure Temporary CSS Class Cleanup

Use wpninetyplus_sync_temporary to specify CSS selectors from which the .r-temporary class should be removed.

php
add_filter( 'wpninetyplus_sync_temporary', function( $selectors ) {

    return array(
        // Add selectors that require temporary class cleanup.
        // Example: '.content-blocks .content-blocks__item',
    );
} );


Advanced usage warning: These filters may affect when resources load, how the browser renders the page, or how dynamic content behaves. Validate the generated HTML and test interactive components after making changes.

Recommended Usage

For the best results when integrating WPNinetyPlus into a WordPress website:

Start with a baseline. Run a performance test before enabling additional optimization options.
Enable features incrementally. Introduce one optimization at a time so that any regressions are easier to identify.
Respect dependencies. Avoid merging scripts that require a specific loading order unless the merge process preserves it.
Optimize critical resources. Prioritize the CSS, JavaScript, images, and fonts required for the initial viewport.
Test dynamic functionality. Verify navigation, forms, menus, third-party integrations, and interactive components.
Measure real results. Compare PageSpeed Insights and Core Web Vitals before and after each significant change.
Keep a rollback path. Maintain a tested configuration and backup before enabling advanced transformations in production.

Remember that aggressive optimization is not always better. The objective is to improve real-world performance while preserving website functionality and compatibility.

Acknowledgments

WPNinetyPlus is made possible by the tools, documentation, and open-source ecosystem that support WordPress development.

Special thanks to:

GitHub — For providing a platform to host, maintain, and share this project.
PHP — For the language and documentation that support PHP development.
WordPress — For the publishing platform, plugin architecture, and developer documentation that make WordPress extensions possible.

Thank you to everyone who contributes to open-source development and website performance optimization.

License

This project is distributed under the GNU General Public License v3.0 (GPL-3.0), subject to the terms of the project's accompanying license file.

The software is provided "AS IS," without warranty of any kind, express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, and non-infringement, to the extent permitted by applicable law.

In no event shall the authors, developers, contributors, or copyright holders be liable for any claim, damages, or other liability arising from the software or its use, subject to the applicable license terms and law.

For the complete license terms, see the GNU General Public License v3.0.

Support the Project

☕ Would you like to treat me to a coffee or a cake?

Your support helps make continued development, maintenance, and new optimization features possible.

Every contribution, no matter the size, is greatly appreciated. Together, we can build better tools for the WordPress community.

Donation Options
GCash: +639650332900
PayPal: syncdevprojects@gmail.com

Thank you for supporting independent development and helping WPNinetyPlus grow.

<p align="center">
<strong>WPNinetyPlus (90+)</strong><br>
Built for developers. Focused on performance. Inspired by open source.
</p>