export function WPNinetyPlusGetImages() {
    "use strict";

    const startedAt = performance.now();
    const images = new Map();
    const warnings = [];

    // Allowed image extensions
    const ALLOWED_EXTENSIONS = /\.(jpg|jpeg|png|webp|avif|svg|gif)(?:[?#].*)?$/i;

    // Check if the URL ends with one of the allowed image extensions
    function isAllowedImageType(urlStr) {
        try {
            const urlObj = new URL(urlStr, document.baseURI);
            return ALLOWED_EXTENSIONS.test(urlObj.pathname);
        } catch {
            return ALLOWED_EXTENSIONS.test(urlStr);
        }
    }

    // Strip WordPress dimension suffixes (e.g., -1024x683) to get the original master image.
    function getOriginalWordPressURL(urlStr) {
        try {
            const urlObj = new URL(urlStr, document.baseURI);
            urlObj.pathname = urlObj.pathname.replace(/-\d+x\d+(?=\.[a-z0-9]+$)/i, '');
            return urlObj.href;
        } catch {
            return urlStr.replace(/-\d+x\d+(?=\.[a-z0-9]+$)/i, '');
        }
    }

    // Resolve relative URLs to absolute URLs and map to original master.
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
            if (!isAllowedImageType(absolute)) return null;
            return getOriginalWordPressURL(absolute);
        } catch {
            if (!isAllowedImageType(value)) return null;
            return getOriginalWordPressURL(value);
        }
    }

    // Extract url(...) values from CSS.
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

    // Store each unique original URL with its sources.
    function addImage(url, source, element = null) {
        const normalized = normalizeURL(url);
        if (!normalized) return;

        if (!images.has(normalized)) {
            images.set(normalized, {
                url: normalized,
                sources: new Set(),
                elements: new Set()
            });
        }

        const item = images.get(normalized);
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

    // Extract image URLs from srcset, ensuring unique URLs.
    function scanSrcset(value, source, element) {
        if (!value) return;

        const seenInThisSet = new Set();

        for (const entry of value.split(",")) {
            const url = entry.trim().split(/\s+/)[0];
            const normalized = normalizeURL(url);
            if (normalized && !seenInThisSet.has(normalized)) {
                seenInThisSet.add(normalized);
                addImage(url, source, element);
            }
        }
    }

    // =====================================================
    // 1. HTML IMAGE ELEMENTS
    // =====================================================

    document.querySelectorAll("img").forEach(img => {
        [
            ["src", "img[src]"],
            ["data-src", "img[data-src]"],
            ["data-lazy-src", "img[data-lazy-src]"],
            ["data-original", "img[data-original]"],
            ["data-original-src", "img[data-original-src]"],
            ["data-lazy", "img[data-lazy]"],
            ["data-actualsrc", "img[data-actualsrc]"]
        ].forEach(([attr, source]) => {
            const value = img.getAttribute(attr);
            if (value) addImage(value, source, img);
        });

        [
            ["srcset", "img[srcset]"],
            ["data-srcset", "img[data-srcset]"],
            ["data-lazy-srcset", "img[data-lazy-srcset]"]
        ].forEach(([attr, source]) => {
            scanSrcset(img.getAttribute(attr), source, img);
        });

        if (img.currentSrc) {
            addImage(img.currentSrc, "img.currentSrc", img);
        }
    });

    // =====================================================
    // 2. PICTURE AND SOURCE ELEMENTS
    // =====================================================

    document.querySelectorAll("picture source, source").forEach(source => {
        const parent = source.parentElement;

        const isImageSource =
            parent?.tagName.toLowerCase() === "picture" ||
            source.type?.startsWith("image/");

        if (!isImageSource) return;

        if (source.getAttribute("src")) {
            addImage(source.getAttribute("src"), "source[src]", source);
        }

        scanSrcset(source.getAttribute("srcset"), "source[srcset]", source);
        scanSrcset(source.getAttribute("data-srcset"), "source[data-srcset]", source);
    });

    // =====================================================
    // 3. INLINE CSS AND COMPUTED CSS
    // =====================================================

    document.querySelectorAll("*").forEach(element => {
        const inlineStyle = element.getAttribute("style") || "";

        extractCSSURLs(inlineStyle).forEach(url => {
            addImage(url, "inline style", element);
        });

        try {
            const style = getComputedStyle(element);

            [
                "backgroundImage",
                "borderImageSource",
                "listStyleImage",
                "content",
                "maskImage",
                "webkitMaskImage"
            ].forEach(property => {
                extractCSSURLs(style[property]).forEach(url => {
                    addImage(url, "computed CSS: " + property, element);
                });
            });
        } catch (error) { }
    });

    // =====================================================
    // 4. STYLESHEET CSS RULES
    // =====================================================

    function scanCSSRules(rules, stylesheetURL) {
        for (const rule of rules) {
            try {
                if (rule.cssRules) {
                    scanCSSRules(rule.cssRules, stylesheetURL);
                }

                if (rule.style) {
                    const cssText = rule.style.cssText || "";

                    extractCSSURLs(cssText).forEach(url => {
                        addImage(url, "stylesheet: " + (stylesheetURL || "inline CSS"));
                    });
                }
            } catch (error) { }
        }
    }

    for (const sheet of document.styleSheets) {
        try {
            scanCSSRules(sheet.cssRules, sheet.href || "inline CSS");
        } catch (error) {
            warnings.push(
                "Could not read stylesheet: " +
                (sheet.href || "inline CSS") +
                " (possibly blocked by cross-origin restrictions)"
            );
        }
    }

    // =====================================================
    // 5. SVG IMAGE REFERENCES
    // =====================================================

    document.querySelectorAll("svg image").forEach(element => {
        const href =
            element.getAttribute("href") ||
            element.getAttributeNS("http://www.w3.org/1999/xlink", "href");

        if (href) addImage(href, "svg image", element);
    });

    // =====================================================
    // 6. VIDEO POSTERS
    // =====================================================

    document.querySelectorAll("video[poster]").forEach(video => {
        addImage(video.getAttribute("poster"), "video poster", video);
    });

    // =====================================================
    // 7. UBUNTU PRO DARK TERMINAL CONSOLE REPORTING
    // =====================================================

    const results = [...images.values()].map(item => ({
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
        "%c[✦] WPNINETYPLUS FILTERED IMAGE AUDIT ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c Total unique filtered master images: %c${results.length}%c | Scan completed in %c${(performance.now() - startedAt).toFixed(2)} ms`,
        `${darkBox} color: #7a8b6b; font-weight: bold;`,
        mutedText,
        orangeText,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    // Unique Filtered Image URLs Group
    console.groupCollapsed(`%c[✦] ALL UNIQUE FILTERED IMAGE URLS`, `${darkBox} ${orangeText}`);
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
};

window.WPNinetyPlusGetImages = window.WPNinetyPlusGetImages;