export function WPNinetyPlusAssetsPaint() {
    "use strict";

    const startedAt = performance.now();
    const assets = new Map();
    const warnings = [];

    // Allowed font extensions and font domains/patterns
    const FONT_EXTENSIONS = /\.(woff|woff2|ttf|otf|eot)(?:[?#].*)?$/i;

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
            return new URL(value, document.baseURI).href;
        } catch {
            return value;
        }
    }

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

    function addAsset(url, source, element = null) {
        const normalized = normalizeURL(url);
        if (!normalized) return;

        if (!assets.has(normalized)) {
            assets.set(normalized, {
                url: normalized,
                sources: new Set(),
                elements: new Set()
            });
        }

        const item = assets.get(normalized);
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

    // =====================================================
    // 1. FAVICONS & SITE ICONS (Head link elements)
    // =====================================================

    document.querySelectorAll("link[rel*='icon'], link[rel*='shortcut icon'], link[rel*='apple-touch-icon']").forEach(link => {
        const href = link.getAttribute("href");
        if (href) {
            addAsset(href, `link[rel="${link.getAttribute('rel')}"]`, link);
        }
    });

    // =====================================================
    // 2. FONTS (Preloads, Font Stylesheets, & @font-face)
    // =====================================================

    // Capture explicit font preloads or font link tags
    document.querySelectorAll("link[as='font'], link[rel*='font']").forEach(link => {
        const href = link.getAttribute("href");
        if (href) {
            addAsset(href, "link[as='font']", link);
        }
    });

    // Capture Google Fonts or external font stylesheet links
    document.querySelectorAll("link[rel='stylesheet']").forEach(link => {
        const href = link.getAttribute("href");
        if (href && (href.includes("fonts.googleapis.com") || href.includes("font") || href.includes("typography"))) {
            addAsset(href, "font stylesheet link", link);
        }
    });

    // Capture inline styles referencing fonts
    document.querySelectorAll("*").forEach(element => {
        const inlineStyle = element.getAttribute("style") || "";

        extractCSSURLs(inlineStyle).forEach(url => {
            if (FONT_EXTENSIONS.test(url) || url.includes("font")) {
                addAsset(url, "inline style (font)", element);
            }
        });
    });

    // Scan accessible stylesheet rules and inline <style> tags
    function scanCSSRules(rules, stylesheetURL) {
        for (const rule of rules) {
            try {
                if (rule.cssRules) {
                    scanCSSRules(rule.cssRules, stylesheetURL);
                }

                const isFontFace =
                    (rule.constructor && rule.constructor.name === "CSSFontFaceRule") ||
                    (rule.type === CSSRule.FONT_FACE_RULE);

                if (rule.style) {
                    const cssText = rule.style.cssText || "";
                    extractCSSURLs(cssText).forEach(url => {
                        if (isFontFace || FONT_EXTENSIONS.test(url) || url.includes("font")) {
                            addAsset(url, "stylesheet font: " + (stylesheetURL || "inline CSS"));
                        }
                    });
                } else if (rule.cssText) {
                    extractCSSURLs(rule.cssText).forEach(url => {
                        if (isFontFace || FONT_EXTENSIONS.test(url) || url.includes("font")) {
                            addAsset(url, "stylesheet font rule: " + (stylesheetURL || "inline CSS"));
                        }
                    });
                }
            } catch (error) { }
        }
    }

    for (const sheet of document.styleSheets) {
        try {
            // If the sheet is accessible without CORS block
            if (sheet.cssRules) {
                scanCSSRules(sheet.cssRules, sheet.href || "inline CSS");
            }
        } catch (error) {
            // Fallback for cross-origin stylesheets (like Google Fonts) whose rules can't be read directly
            if (sheet.href && (sheet.href.includes("fonts.googleapis.com") || sheet.href.includes("font"))) {
                addAsset(sheet.href, "external font stylesheet (CORS protected)");
            } else {
                warnings.push(
                    "Could not read stylesheet: " +
                    (sheet.href || "inline CSS") +
                    " (blocked by cross-origin restrictions)"
                );
            }
        }
    }

    // =====================================================
    // 3. UBUNTU PRO DARK TERMINAL CONSOLE REPORTING
    // =====================================================

    const results = [...assets.values()].map(item => ({
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
        "%c[✦] WPNINETYPLUS FONTS & FAVICONS AUDIT ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c Total unique fonts & favicons: %c${results.length}%c | Scan completed in %c${(performance.now() - startedAt).toFixed(2)} ms`,
        `${darkBox} color: #38B000; font-weight: bold;`,
        mutedText,
        orangeText,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    // All Unique Asset URLs Group
    console.groupCollapsed(`%c[✦] ALL UNIQUE ASSET URLS`, `${darkBox} ${orangeText}`);
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

window.WPNinetyPlusAssetsPaint = window.WPNinetyPlusAssetsPaint;