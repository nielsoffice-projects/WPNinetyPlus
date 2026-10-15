export function WPNinetyPlusAssetsInteractive() {
    "use strict";

    const startedAt = performance.now();

    // Layout storage structure categorized by section
    const layoutJS = {
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
    // 1. EXTERNAL SCRIPTS (<script src="...">)
    // =====================================================
    document.querySelectorAll("script[src]").forEach(script => {
        const src = script.getAttribute("src");
        if (!src) return;

        try {
            const absoluteURL = new URL(src, document.baseURI).href;
            const section = getSectionKey(script);

            layoutJS[section].external.push({
                url: absoluteURL,
                async: script.hasAttribute("async"),
                defer: script.hasAttribute("defer"),
                element: describeElement(script)
            });
        } catch (error) { }
    });

    // =====================================================
    // 2. INLINE SCRIPTS (<script>...</script> without src)
    // =====================================================
    document.querySelectorAll("script:not([src])").forEach(scriptEl => {
        const jsText = scriptEl.innerHTML || scriptEl.textContent || "";
        if (!jsText.trim()) return;

        const section = getSectionKey(scriptEl);

        // Formatted exactly like your requested inline tag structure
        const formattedTag = `<script>\n${jsText.trim()}\n</script>`;

        layoutJS[section].inline.push({
            tagString: formattedTag,
            element: describeElement(scriptEl.parentElement) || "head"
        });
    });

    // =====================================================
    // 3. UBUNTU PRO DARK TERMINAL CONSOLE REPORTING
    // =====================================================
    const darkBox = "background: #1e1e1e; font-family: monospace; padding: 4px 8px; border-radius: 3px;";
    const orangeText = "color: #E95420; font-weight: bold;";
    const purpleText = "color: #ad52af; font-weight: bold;";
    const greenText = "color: #38B000; font-weight: bold;";
    const yellowText = "color: #FFB703; font-weight: bold;";
    const cyanText = "color: #7a8b6b; font-weight: bold;";
    const mutedText = "color: #fff; font-family: monospace;";

    console.group(
        "%c[✦] WPNINETYPLUS JAVASCRIPT AUDITOR ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c Scan completed in %c${(performance.now() - startedAt).toFixed(2)} ms`,
        `${darkBox} color: #7a8b6b; font-weight: bold;`,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    let totalExternal = 0;
    let totalInline = 0;

    for (const [sectionName, data] of Object.entries(layoutJS)) {
        if (data.external.length === 0 && data.inline.length === 0) continue;

        totalExternal += data.external.length;
        totalInline += data.inline.length;

        // Section Group with Ubuntu styling (using alternating accent colors)
        const sectionAccent = sectionName === "Header" ? orangeText : sectionName === "Body" ? purpleText : greenText;
        console.groupCollapsed(`%c[✦] SECTION: ${sectionName.toUpperCase()} (External: ${data.external.length}, Inline: ${data.inline.length})`, `${darkBox} ${sectionAccent}`);

        if (data.external.length > 0) {
            console.log(`%c [EXT] External Scripts (${data.external.length}):`, `${darkBox} ${cyanText}`);
            console.table(data.external);
        }

        if (data.inline.length > 0) {
            console.log(`%c [INL] Inline Scripts (${data.inline.length}):`, `${darkBox} ${yellowText}`);
            data.inline.forEach((item, index) => {
                console.log(`%c ├── [Block ${index + 1}] Parent: ${item.element}`, `${darkBox} ${mutedText}`);
                console.log(item.tagString);
            });
        }

        console.groupEnd();
    }

    console.log("%c--------------------------------------------------------------------------------", mutedText);
    console.log(`%c[SUMMARY]%c Total External Scripts: %c${totalExternal}%c | Total Inline Scripts: %c${totalInline}`, `${darkBox} ${greenText}`, mutedText, cyanText, mutedText, cyanText);
    console.log("%c[SYSTEM] Audit stream closed safely. Returning layout object...", mutedText);
    console.groupEnd();

    return layoutJS;
};

window.WPNinetyPlusAssetsInteractive = WPNinetyPlusAssetsInteractive;