<?php
/**
 * Plugin Name: WPNinetyPlus LayoutMergeInlineExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/        
 * Since:       1.0.0
 * Description: WordPress Optimization Ninety Plus (90+) Grade Speed Insight. 
 *              Secured Inline CSS Optimizer for cross pages and devices!
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Class LayoutMergeInlineExe
 * 
 * Handles finding all generic inline style blocks (<style>...</style>), 
 * minifying them, stripping them from the document, merging them together, 
 * and always injecting them right before the closing </head> tag.
 *
 * @since 1.0.0
 */
class LayoutMergeInlineExe {

    /**
     * Finds all inline style tags, combines and minifies them, and injects 
     * the consolidated style block right before the closing </head> tag.
     *
     * @since 1.0.0
     * @param string $buffer The raw HTML output buffer.
     * @return string        The modified HTML buffer.
     */
    public function layout_merge_inline( string $buffer ) : string {

        if ( empty( $buffer ) ) {
            return $buffer;
        }

        // Find all style tags in the buffer
        preg_match_all( '/<style\b([^>]*)>([\s\S]*?)<\/style>/i', $buffer, $style_matches, PREG_SET_ORDER );

        if ( empty( $style_matches ) ) {
            return $buffer;
        }

        $combined_css     = '';
        $styles_to_remove = [];

        foreach ( $style_matches as $match ) {
            $full_tag   = $match[0];
            $attributes = $match[1];
            $css_code   = $match[2];

            // Skip our own merged style tags to prevent re-processing loops
            if ( preg_match( '/id=["\'](WPNinetyPlusMerge|WPNinetyPlusInlineCSSMerge)["\']/i', $attributes ) ) {
                continue;
            }

            // Accumulate and minify CSS code if it's not empty
            if ( ! empty( trim( $css_code ) ) ) {
                // 1. Remove all CSS comments (/* ... */).
                $css_code = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_code );
                
                // 2. Remove unnecessary spaces around selectors, braces, colons, semicolons, and commas.
                $css_code = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css_code );
                
                // 3. Collapse remaining multiple spaces and newlines into a single line.
                $css_code = trim( preg_replace( '/\s+/', ' ', $css_code ) );

                $combined_css     .= $css_code;
                $styles_to_remove[] = $full_tag;
            }
        }

        // If inline styles were found and combined, proceed to injection
        if ( ! empty( $combined_css ) ) {
            $inline_style_tag = '<style id="WPNinetyPlusInlineCSSMerge">' . $combined_css . '</style>';
            
            // 1. Remove original scattered inline style tags from buffer
            if ( ! empty( $styles_to_remove ) ) {
                $buffer = str_replace( $styles_to_remove, '', $buffer );
            }

            // 2. Always inject right before the closing </head> tag
            if ( false !== stripos( $buffer, '</head>' ) ) {
                $buffer = str_ireplace( '</head>', $inline_style_tag . '</head>', $buffer );
            } else {
                $buffer = $inline_style_tag . $buffer;
            }
        }

        return $buffer;
    }
}
