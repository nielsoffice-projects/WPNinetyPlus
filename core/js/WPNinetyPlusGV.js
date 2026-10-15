export function WPNinetyPlusGetVideos() {
    "use strict";

    const startedAt = performance.now();
    const videos = new Map();
    const warnings = [];

    // Allowed video extensions
    const ALLOWED_EXTENSIONS = /\.(mp4|webm|ogg|mov|avi|mkv|m4v|wmv|flv|3gp)(?:[?#].*)?$/i;

    // Check if the URL ends with one of the allowed video extensions
    function isAllowedVideoType(urlStr) {
        try {
            const urlObj = new URL(urlStr, document.baseURI);
            return ALLOWED_EXTENSIONS.test(urlObj.pathname);
        } catch {
            return ALLOWED_EXTENSIONS.test(urlStr);
        }
    }

    // Resolve relative URLs to absolute URLs.
    function normalizeURL(value) {
        if (!value || typeof value !== "string") return null;

        value = value.trim();

        if (
            !value ||
            value.startsWith("#") ||
            value.startsWith("data:") ||
            value.startsWith("blob:")
        ) {
            return value || null;
        }

        try {
            const absolute = new URL(value, document.baseURI).href;
            if (!isAllowedVideoType(absolute)) return null;
            return absolute;
        } catch {
            if (!isAllowedVideoType(value)) return null;
            return value;
        }
    }

    // Extract url(...) values from CSS if background/mask uses video (rare, but supported for consistency)
    function extractCSSURLs(cssText) {
        const urls = [];
        const regex = /url\s*\(\s*(?:"([^"]*)"\vert{}'([^']*)'\vert{}([^)]*?))\s*\)/gi;
        let match;

        while ((match = regex.exec(cssText || "")) !== null) {
            const value = (match[1] ?? match[2] ?? match[3] ?? "").trim();
            if (value) urls.push(value);
        }

        return urls;
    }

    // Store each unique video URL with its sources and elements.
    function addVideo(url, source, element = null) {
        const normalized = normalizeURL(url);
        if (!normalized) return;

        if (!videos.has(normalized)) {
            videos.set(normalized, {
                url: normalized,
                sources: new Set(),
                elements: new Set()
            });
        }

        const item = videos.get(normalized);
        item.sources.add(source);

        if (element) {
            const description = element.tagName.toLowerCase() +
                (element.id ? "#" + element.id : "") +
                (element.classList?.length
                    ? "." + [...element.classList].join(".")
                    : "");

            item.elements.add(description);
        }
    }

    // Extract video URLs from srcset if applicable
    function scanSrcset(value, source, element) {
        if (!value) return;

        const seenInThisSet = new Set();

        for (const entry of value.split(",")) {
            const url = entry.trim().split(/\s+/)[0];
            const normalized = normalizeURL(url);
            if (normalized && !seenInThisSet.has(normalized)) {
                seenInThisSet.add(normalized);
                addVideo(url, source, element);
            }
        }
    }

    // =====================================================
    // 1. HTML VIDEO ELEMENTS & SOURCES
    // =====================================================

    document.querySelectorAll("video").forEach(video => {
        [
            ["src", "video[src]"],
            ["data-src", "video[data-src]"],
            ["data-lazy-src", "video[data-lazy-src]"],
            ["data-original", "video[data-original]"]
        ].forEach(([attr, source]) => {
            const value = video.getAttribute(attr);
            if (value) addVideo(value, source, video);
        });

        if (video.currentSrc) {
            addVideo(video.currentSrc, "video.currentSrc", video);
        }
    });

    document.querySelectorAll("video source, source").forEach(source => {
        const parent = source.parentElement;
        const isVideoSource =
            parent?.tagName.toLowerCase() === "video" ||
            source.type?.startsWith("video/");

        if (!isVideoSource) return;

        if (source.getAttribute("src")) {
            addVideo(source.getAttribute("src"), "source[src]", source);
        }

        scanSrcset(source.getAttribute("srcset"), "source[srcset]", source);
        scanSrcset(source.getAttribute("data-srcset"), "source[data-srcset]", source);
    });

    // =====================================================
    // 2. EMBEDDED FRAMES (IFRAMES - YouTube, Vimeo, etc.)
    // =====================================================

    document.querySelectorAll("iframe").forEach(iframe => {
        const src = iframe.getAttribute("src") || iframe.getAttribute("data-src");
        if (src) {
            try {
                const absolute = new URL(src, document.baseURI).href;
                // Capture popular embedded video platforms or general URLs inside iframes
                if (/youtube|vimeo|dailymotion|player/i.test(absolute)) {
                    addVideo(absolute, "iframe embed", iframe);
                }
            } catch {
                if (/youtube|vimeo|dailymotion|player/i.test(src)) {
                    addVideo(src, "iframe embed", iframe);
                }
            }
        }
    });

    // =====================================================
    // 3. OBJECT AND EMBED TAGS
    // =====================================================

    document.querySelectorAll("object, embed").forEach(el => {
        const data = el.getAttribute("data") || el.getAttribute("src");
        if (data) {
            addVideo(data, el.tagName.toLowerCase() + " tag", el);
        }
    });

    // =====================================================
    // 4. INLINE CSS AND STYLESHEETS
    // =====================================================

    document.querySelectorAll("*").forEach(element => {
        const inlineStyle = element.getAttribute("style") || "";
        extractCSSURLs(inlineStyle).forEach(url => {
            if (ALLOWED_EXTENSIONS.test(url)) {
                addVideo(url, "inline style", element);
            }
        });
    });

    for (const sheet of document.styleSheets) {
        try {
            const scanRules = (rules) => {
                for (const rule of rules) {
                    if (rule.cssRules) scanRules(rule.cssRules);
                    if (rule.style) {
                        extractCSSURLs(rule.style.cssText || "").forEach(url => {
                            if (ALLOWED_EXTENSIONS.test(url)) {
                                addVideo(url, "stylesheet", null);
                            }
                        });
                    }
                }
            };
            if (sheet.cssRules) scanRules(sheet.cssRules);
        } catch {
            warnings.push("Could not read stylesheet due to cross-origin restrictions.");
        }
    }

    // =====================================================
    // 5. UBUNTU PRO DARK TERMINAL CONSOLE REPORTING
    // =====================================================

    const results = [...videos.values()].map(item => ({
        url: item.url,
        sources: [...item.sources],
        elements: [...item.elements]
    }));

    const darkBox = "background: #1e1e1e; font-family: monospace; padding: 4px 8px; border-radius: 3px;";
    const orangeText = "color: #E95420; font-weight: bold;";
    const purpleText = "color: #ad52af; font-weight: bold;";
    const greenText = "color: #38B000; font-weight: bold;";
    const yellowText = "color: #FFB703; font-weight: bold;";
    const cyanText = "color: #7a8b6b; font-weight: bold;";
    const mutedText = "color: #8c8c8c; font-family: monospace;";

    console.group(
        "%c[✦] WPNINETYPLUS FILTERED VIDEO AUDIT ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c Total unique filtered master videos: %c${results.length}%c | Scan completed in %c${(performance.now() - startedAt).toFixed(2)} ms`,
        `${darkBox} color: #7a8b6b; font-weight: bold;`,
        mutedText,
        orangeText,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    // Unique Filtered Video URLs Group
    console.groupCollapsed(`%c[✦] ALL UNIQUE FILTERED VIDEO URLS`, `${darkBox} ${orangeText}`);
    results.forEach((item, index) => {
        console.log(`%c ├── [${index + 1}]`, `${darkBox} ${cyanText}`, item.url);
    });
    console.groupEnd();

    // Detailed Results Group
    console.groupCollapsed(`%c[✦] DETAILED RESULTS`, `${darkBox} ${purpleText}`);
    console.table(
        results.map((item, index) => ({
            "#": index + 1,
            URL: item.url,
            Sources: item.sources.join(", "),
            Elements: item.elements.join(", ")
        }))
    );
    console.groupEnd();

    // Warnings Group (if any)
    if (warnings.length) {
        console.groupCollapsed(`%c[✦] AUDIT WARNINGS`, `${darkBox} ${yellowText}`);
        [...new Set(warnings)].forEach(warning => {
            console.warn(`%c[WARN] ${warning}`, `${darkBox} ${yellowText}`);
        });
        console.groupEnd();
    }

    console.log("%c--------------------------------------------------------------------------------", mutedText);
    console.log("%c[SYSTEM] Audit stream closed safely. Returning results array...", mutedText);
    console.groupEnd();

    return results;
}

window.WPNinetyPlusGetVideos = WPNinetyPlusGetVideos;