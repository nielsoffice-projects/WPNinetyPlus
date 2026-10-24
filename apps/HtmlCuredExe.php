<?php
/**
 * Plugin Name: HTML Cured Executable Minifier
 * Description: Utility class to minify HTML, inline CSS, and inline JavaScript on the fly via output buffering.
 * Class HTMLCuredExe
 */
class HTMLCuredExe {

    /**
     * Constructor.
     * Hooks the output buffer initialization into WordPress execution lifecycle.
     */
    public function __construct() {
        // Hook into 'template_redirect' to capture theme output before it's sent to the client browser
        add_action('template_redirect', [$this, 'start_buffer']);
    }

    /**
     * Start the output buffer and pass the final HTML buffer to the minifier callback.
     * 
     * @return void
     */
    public function start_buffer() {
        // Bypass minification on admin screens, AJAX calls, REST API endpoints, feeds, and embeds to prevent corruption
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_feed() || (function_exists('is_embed') && is_embed())) {
            return;
        }

        // Initialize output buffering with a callback targeting our minification method
        ob_start([$this, 'minify_html']);
    }

    /**
     * Main HTML Minifier wrapper.
     * Processes raw HTML output, delegating script and style blocks to their respective parsers.
     *
     * @param string $html The raw page output captured from the output buffer.
     * @return string      The cleaned and minified HTML string.
     */
    public function minify_html($html) {
        // Return early if the buffer payload is completely empty
        if (empty($html)) {
            return $html;
        }

        // 1. Locate and minify all inline CSS content encapsulated inside <style> tags
        $html = preg_replace_callback('~<style\b[^>]*>([\s\S]*?)<\/style>~i', function($matches) {
            return '<style>' . $this->minify_css($matches[1]) . '</style>';
        }, $html);

        // 2. Locate and minify all inline JS content encapsulated inside <script> tags
        $html = preg_replace_callback('~<script\b([^>]*)>([\s\S]*?)<\/script>~i', function($matches) {
            $attrs = $matches[1];
            $jsContent = $matches[2];

            // Skip external scripts containing 'src=' or script tags that are completely empty
            if (stripos($attrs, 'src=') !== false || trim($jsContent) === '') {
                return $matches[0];
            }

            // Return the script tag with attributes intact and the minified JS body
            return '<script' . $attrs . '>' . $this->minify_js($jsContent) . '</script>';
        }, $html);

        // 3. Strip out HTML comments, collapse unnecessary spaces between tags, and reduce multi-whitespace blocks
        $html = preg_replace('~<!--[\s\S]*?-->~', '', $html); // Remove HTML comments
        $html = preg_replace('~>\s+<~', '><', $html);         // Remove whitespace gaps between HTML tags
        $html = preg_replace('~\s{2,}~', ' ', $html);           // Condense consecutive white spaces into a single space

        return trim($html);
    }

    /**
     * CSS Minifier engine.
     * Strips block comments, collapses spacing, and removes redundant syntax characters.
     *
     * @param string $css Raw CSS block code.
     * @return string     Minified CSS block code.
     */
    private function minify_css($css) {
        $css = preg_replace('~/\*[\s\S]*?\*/~', '', $css);   // Strip CSS block comments (/* ... */)
        $css = preg_replace('~\s+~', ' ', $css);              // Collapse multiple spaces/tabs/newlines into a single space
        $css = preg_replace('~\s*([{}:;,>+~])\s*~', '$1', $css); // Remove spaces surrounding major CSS symbols/selectors
        $css = str_replace(';}', '}', $css);                 // Clean up redundant semicolons right before closing brackets
        return trim($css);
    }

    /**
     * JS Minifier engine.
     * Strips block/inline comments, compresses whitespace, and normalizes operator spacing.
     *
     * @param string $js Raw JavaScript block code.
     * @return string    Minified JavaScript block code.
     */
    private function minify_js($js) {
        $js = preg_replace('~/\*[\s\S]*?\*/~', '', $js);   // Strip JS multi-line comments (/* ... */)
        $js = preg_replace('~//.*~', '', $js);               // Strip JS single-line comments (// ...)
        $js = preg_replace('~\s+~', ' ', $js);              // Collapse multiple spaces/tabs/newlines into a single space
        $js = preg_replace('~\s*([=+\-*/%<>!&|,?:;()\[\]{}])\s*~', '$1', $js); // Strip spacing around operators and punctuation
        return trim($js);
    }
}

