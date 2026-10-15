export function WPNinetyPlusAsstesLayout() {
    "use strict";

    const startedAt = performance.now();

    // Layout storage structure categorized by section
    const layoutCSS = {
        Header: { external: [], inline: [] },
        Body: { external: [], inline: [] },
        Footer: { external: [], inline: [] },
        Other: { external: [], inline: [] }
    };

    // Helper to determine layout section based on DOM positioning
    function getSectionKey(element) {
        if (!element) return "Other";

        const closestHead = element.closest("head");
        const closestHeader = element.closest("header, .header, #header");
        const closestFooter = element.closest("footer, .footer, #footer");

        if (closestHead) return "Header";
        if (closestHeader) return "Header";
        if (closestFooter) return "Footer";

        return "Body";
    }

    // Helper to build a readable element descriptor string
    function describeElement(el) {
        if (!el) return "";
        return el.tagName.toLowerCase() +
            (el.id ? "#" + el.id : "") +
            (el.classList?.length ? "." + [...el.classList].join(".") : "");
    }

    // =====================================================
    // 1. EXTERNAL STYLESHEETS (<link rel="stylesheet">)
    // =====================================================
    document.querySelectorAll("link[rel='stylesheet']").forEach(link => {
        const href = link.getAttribute("href");
        if (!href) return;

        try {
            const absoluteURL = new URL(href, document.baseURI).href;
            const section = getSectionKey(link);

            layoutCSS[section].external.push({
                url: absoluteURL,
                element: describeElement(link)
            });
        } catch (error) { }
    });

    // =====================================================
    // 2. INLINE STYLE TAGS (<style>...</style>)
    // =====================================================
    document.querySelectorAll("style").forEach(styleEl => {
        const cssText = styleEl.innerHTML || styleEl.textContent || "";
        if (!cssText.trim()) return;

        const section = getSectionKey(styleEl);

        // Formatted exactly as requested: <style> content </style>
        const formattedTag = `<style>\n${cssText.trim()}\n</style>`;

        layoutCSS[section].inline.push({
            tagString: formattedTag,
            element: describeElement(styleEl.parentElement) || "head"
        });
    });

    // =====================================================
    // 3. UBUNTU PRO DARK TERMINAL CONSOLE REPORTING
    // =====================================================

    let totalExternal = 0;
    let totalInline = 0;

    for (const data of Object.values(layoutCSS)) {
        totalExternal += data.external.length;
        totalInline += data.inline.length;
    }

    const darkBox = "background: #1e1e1e; font-family: monospace; padding: 4px 8px; border-radius: 3px;";
    const orangeText = "color: #E95420; font-weight: bold;";
    const purpleText = "color: #ad52af; font-weight: bold;";
    const greenText = "color: #38B000; font-weight: bold;";
    const yellowText = "color: #FFB703; font-weight: bold;";
    const cyanText = "color: #7a8b6b; font-weight: bold;";
    const mutedText = "color: #8c8c8c; font-family: monospace;";

    console.group(
        "%c[✦] WPNINETYPLUS CSS LAYOUT AUDIT ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c External: %c${totalExternal}%c | Inline: %c${totalInline}%c | Scan completed in %c${(performance.now() - startedAt).toFixed(2)} ms`,
        `${darkBox} color: #38B000; font-weight: bold;`,
        mutedText,
        orangeText,
        mutedText,
        purpleText,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    for (const [sectionName, data] of Object.entries(layoutCSS)) {
        if (data.external.length === 0 && data.inline.length === 0) continue;

        console.groupCollapsed(`%c[✦] SECTION: ${sectionName} (External: ${data.external.length}, Inline <style>: ${data.inline.length})`, `${darkBox} ${orangeText}`);

        if (data.external.length > 0) {
            console.groupCollapsed("%cExternal Stylesheets", `${darkBox} ${purpleText}`);
            console.table(data.external);
            console.groupEnd();
        }

        if (data.inline.length > 0) {
            console.groupCollapsed("%cInline <style> Tags", `${darkBox} ${greenText}`);
            data.inline.forEach((item, index) => {
                console.log(`%c--- [${index + 1}] Location/Parent: ${item.element} ---`, `${darkBox} ${cyanText}`);
                console.log(item.tagString);
            });
            console.groupEnd();
        }

        console.groupEnd();
    }

    console.log("%c--------------------------------------------------------------------------------", mutedText);
    console.log(`%c[SUMMARY]%c External Stylesheets: ${totalExternal} | Inline <style> Tags: ${totalInline}`, `${darkBox} ${greenText}`, mutedText);
    console.log("%c[SYSTEM] Audit stream closed safely. Returning layoutCSS object...", mutedText);
    console.groupEnd();

    return layoutCSS;
};

window.WPNinetyPlusAsstesLayout = window.WPNinetyPlusAsstesLayout;