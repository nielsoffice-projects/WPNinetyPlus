<?php
/**
 * Plugin Name: WPNinetyPlus FullPaintExe Core
 * Package:     Wpninetyplus
 * Link:        https://www.nielsoffice.com/
 * Since:       1.3.1
 * Description: Automatic media optimization, poster preloading, and intelligent skeleton frame injection.
 */

namespace WPNinetyPlus;

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FullPaintExe
 * 
 * Manages in-place media optimization, automatic image preloading, 
 * robust video attribute configuration, and automated skeleton frame injection.
 * 
 * @since 1.3.1
 */
class FullPaintExe {

    /**
     * Flag to determine whether to apply aggressive media optimization.
     * 
     * @var bool
     */
    private bool $aggressive_paint;

    /**
     * FullPaintExe constructor.
     * 
     * @since 1.3.1
     * @param ?bool $aggressive_paint Optional override for aggressive optimization.
     */
    public function __construct( ?bool $aggressive_paint = null ) {
        $this->aggressive_paint = $aggressive_paint ?? true;
    }

    /**
     * Main pipeline method to cure, optimize media, and inject skeleton frames into the HTML skeleton.
     * 
     * @since 1.3.1
     * @param string $skeleton The raw HTML output string.
     * @param array  $ids      Array of media IDs or classes to ignore/exclude from optimization.
     * @return string          The fully processed HTML string.
     */
    public function paint_cured_ninety_plus( string $skeleton, array $ids = [] ) : string {

        // Skip processing if skeleton is empty.
        if ( empty( $skeleton ) ) {
            return $skeleton;
        }

        // 1. Process <img> tags and preloads
        $skeleton = $this->process_images( $skeleton, $ids );

        // 2. Process <video> tags in-place with proper attributes and poster preloads
        $skeleton = $this->process_videos( $skeleton, $ids );

        // 3. Automatically inject skeleton frame loading classes
        $skeleton = $this->inject_skeleton_frames( $skeleton );

        return $skeleton;
    }

    /**
     * Parses and optimizes <img> tags in-place.
     * 
     * @since 1.3.1
     * @param string $skeleton The HTML string.
     * @param array  $ids      Excluded IDs.
     * @return string          Processed HTML string.
     */
    private function process_images( string $skeleton, array $ids ) : string {
        
        return preg_replace_callback( '/<img\b([^>]*)>/i', function( $matches ) use ( $ids ) {
            $attributes = $matches[1];

            foreach ( $ids as $id ) {
                if ( preg_match( '/id=["\']' . preg_quote( $id, '/' ) . '["\']/i', $attributes ) ) {
                    return $matches[0];
                }
            }

            preg_match( '/src=["\']([^"\']+)["\']/i', $attributes, $src_match );
            $image_url = isset( $src_match[1] ) ? $src_match[1] : '';

            if ( false === stripos( $attributes, 'loading=' ) && false === stripos( $attributes, 'fetchpriority="high"' ) && $this->aggressive_paint ) {
                $attributes .= ' loading="lazy"';
            }

            if ( false === stripos( $attributes, 'decoding=' ) ) {
                $attributes .= ' decoding="async"';
            }

            // --- ADD AUTOMATIC ERROR FALLBACK ---
            if ( false === stripos( $attributes, 'onerror=' ) ) {
                // Base64-encoded light gray SVG with "IMG" text (avoids breaking HTML/JS quotes and special characters)
                $fallback_svg = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiB2aWV3Qm94PSIwIDAgMTUwIDE1MCI+PHJlY3Qgd2lkdGg9IjE1MCIgaGVpZ2h0PSIxNTAiIGZpbGw9IiNlMGUwZTAiLz48dGV4dCB4PSI1MCUiIHkgPSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM3Nzc3NzciIGZvbnQtZmFtaWx5PSJzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0Ij5JTUc8L3RleHQ+PC9zdmc+';
                
                $attributes .= ' onerror="this.onerror=null; this.src=\'' . $fallback_svg . '\';"';
            }
            // ------------------------------------

            $replacement = '<img ' . trim( $attributes ) . '>';

            if ( ! empty( $image_url ) && false !== stripos( $attributes, 'fetchpriority="high"' ) ) {
                $preload_tag = '<link rel="preload" href="' . esc_url_raw( $image_url ) . '" as="image" fetchpriority="high">';
                $replacement = $preload_tag . $replacement;
            }

            return $replacement;
        }, $skeleton );
    }

    /**
     * Parses <video> tags in-place and auto-injects poster preloads.
     * 
     * @since 1.3.1
     * @param string $skeleton The HTML string.
     * @param array  $ids      Excluded IDs.
     * @return string          Processed HTML string.
     */
    private function process_videos( string $skeleton, array $ids ) : string {
        
        return preg_replace_callback( '/<video\b([^>]*)>(.*?)<\/video>/is', function( $matches ) use ( $ids ) {
            $attributes = $matches[1];
            $content    = $matches[2];

            foreach ( $ids as $id ) {
                if ( preg_match( '/id=["\']' . preg_quote( $id, '/' ) . '["\']/i', $attributes ) ) {
                    return $matches[0];
                }
            }

            preg_match( '/poster=["\']([^"\']+)["\']/i', $attributes, $poster_match );
            $poster_url = isset( $poster_match[1] ) ? $poster_match[1] : '';

            if ( false === stripos( $attributes, 'autoplay' ) ) {
                $attributes .= ' autoplay';
            }
            if ( false === stripos( $attributes, 'muted' ) ) {
                $attributes .= ' muted';
            }
            if ( false === stripos( $attributes, 'loop' ) ) {
                $attributes .= ' loop';
            }
            if ( false === stripos( $attributes, 'playsinline' ) ) {
                $attributes .= ' playsinline';
            }

            // --- ADD VIDEO ERROR / POSTER FALLBACK ---
            if ( false === stripos( $attributes, 'onerror=' ) ) {
                // Base64-encoded light gray SVG with a centered Play Icon
                $fallback_video_svg = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0idHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjIwMCIgaGVpZ2h0PSIyMDAiIHZpZXdCb3g9IjAgMCAyMDAgMjAwIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2UwZTBlMCIvPjxwb2x5Z29uIHBvaW50cz0iNzUsNjUgMTM1LDEwMCA3NSwxMzUiIGZpbGw9IiM3Nzc3NzciLz48L3N2Zz4=';
                
                $attributes .= ' onerror="this.style.display=\'none\'; if(!this.nextElementSibling || !this.nextElementSibling.classList.contains(\'wpninetyplus-video-fallback\')) { var fb = document.createElement(\'img\'); fb.src=\'' . $fallback_video_svg . '\'; fb.className=\'wpninetyplus-video-fallback\'; fb.style.width=\'100%%\'; fb.style.height=\'auto\'; this.parentNode.insertBefore(fb, this.nextSibling); }"';
            }
            // ------------------------------------------

            $video_tag = '<video ' . trim( $attributes . ' ' . $content ) . '</video>';

            if ( ! empty( $poster_url ) ) {
                $preload_tag = '<link rel="preload" href="' . esc_url_raw( $poster_url ) . '" as="image" fetchpriority="high">';
                return $preload_tag . $video_tag;
            }

            return $video_tag;
        }, $skeleton );
    }

    /**
     * Automatically injects skeleton framework classes:
     * - Parent tags (header, section, nav, article, aside, footer) get 'wpninetyplus-lframe'
     * - Child tags get 'wpninetyplus-sk-frame' safely, skipping if already present.
     * 
     * @since 1.3.1
     * @param string $skeleton The HTML string.
     * @return string          Processed HTML string with skeleton classes.
     */
    private function inject_skeleton_frames( string $skeleton ) : string {

        // 1. Process Parent Containers: header, section, nav, article, aside, footer
        $parent_tags = '(header|section|nav|article|aside|footer)';
        $skeleton = preg_replace_callback( '/<' . $parent_tags . '\b([^>]*)>/i', function( $matches ) {
            $tag_name   = $matches[1];
            $attributes = $matches[2];
            return $this->append_class_to_attributes( $tag_name, $attributes, 'wpninetyplus-lframe' );
        }, $skeleton );

        // 2. Process Child Elements: h1, h2, img, nav, p, div, ul, ol, a, figure, table, caption, thead, tbody, tfoot, tr
        $child_tags = '(h1|h2|img|video|iframe|nav|p|div|ul|ol|a|figure|table|caption|thead|tbody|tfoot|tr)';
        $skeleton = preg_replace_callback( '/<' . $child_tags . '\b([^>]*)>/i', function( $matches ) {
            $tag_name   = $matches[1];
            $attributes = $matches[2];
            return $this->append_class_to_attributes( $tag_name, $attributes, 'wpninetyplus-sk-frame' );
        }, $skeleton );

        return $skeleton;
    }

    /**
     * Helper method to safely append a class to an HTML tag's attributes string.
     * 
     * @since 1.3.1
     * @param string $tag_name     The HTML tag name.
     * @param string $attributes   Existing attributes string.
     * @param string $target_class The class to append.
     * @return string              Reconstructed opening HTML tag.
     */
    private function append_class_to_attributes( string $tag_name, string $attributes, string $target_class ) : string {
        
        // If the exact target class already exists, return the original tag untouched
        if ( preg_match( '/class\s*=\s*["\'][^"\']*\b' . preg_quote( $target_class, '/' ) . '\b[^"\']*["\']/i', $attributes ) ) {
            return '<' . $tag_name . $attributes . '>';
        }

        // Check if a class attribute already exists
        if ( preg_match( '/class\s*=\s*(["\'])(.*?)\1/i', $attributes, $match ) ) {
            $quote            = $match[1];
            $existing_classes = $match[2];
            $updated_classes  = trim( $existing_classes . ' ' . $target_class );
            
            // Replace old class attribute with the updated one
            $attributes = preg_replace( '/class\s*=\s*(["\']).*?\1/i', 'class=' . $quote . $updated_classes . $quote, $attributes, 1 );
        } else {
            // No class attribute exists, add a new one
            $attributes .= ' class="' . $target_class . '"';
        }

        return '<' . $tag_name . $attributes . '>';
    }
}