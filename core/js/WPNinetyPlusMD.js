export function WPNinetyPlusMetaData() {
    "use strict";

    const startedAt = performance.now();

    // SEO data storage structure
    const seoData = {
        basic: {},
        openGraph: {},
        twitter: {},
        structuredData: []
    };

    // Helper to safely fetch meta content or attribute values
    function getMetaContent(selector) {
        const el = document.querySelector(selector);
        if (!el) return "n/a";
        return el.getAttribute("content") || el.getAttribute("href") || "n/a";
    }

    // =====================================================
    // 1. BASIC SEO METADATA
    // =====================================================
    seoData.basic = {
        title: document.title || "n/a",
        canonical: getMetaContent("link[rel='canonical']"),
        description: getMetaContent("meta[name='description']"),
        keywords: getMetaContent("meta[name='keywords']"),
        robots: getMetaContent("meta[name='robots']"),
        viewport: getMetaContent("meta[name='viewport']"),
        author: getMetaContent("meta[name='author']"),
        language: document.documentElement.lang || "n/a"
    };

    // =====================================================
    // 2. OPEN GRAPH (SOCIAL MEDIA) TAGS
    // =====================================================
    const ogTags = document.querySelectorAll("meta[property^='og:']");
    if (ogTags.length > 0) {
        ogTags.forEach(tag => {
            const property = tag.getAttribute("property");
            const content = tag.getAttribute("content") || "n/a";
            seoData.openGraph[property] = content;
        });
    } else {
        seoData.openGraph = "n/a";
    }

    // =====================================================
    // 3. TWITTER CARD TAGS
    // =====================================================
    const twitterTags = document.querySelectorAll("meta[name^='twitter:']");
    if (twitterTags.length > 0) {
        twitterTags.forEach(tag => {
            const name = tag.getAttribute("name");
            const content = tag.getAttribute("content") || "n/a";
            seoData.twitter[name] = content;
        });
    } else {
        seoData.twitter = "n/a";
    }

    // =====================================================
    // 4. STRUCTURED DATA (JSON-LD)
    // =====================================================
    const jsonLdScripts = document.querySelectorAll("script[type='application/ld+json']");
    jsonLdScripts.forEach((script, index) => {
        try {
            const parsed = JSON.parse(script.textContent);
            seoData.structuredData.push({ index: index + 1, data: parsed });
        } catch (error) {
            seoData.structuredData.push({ index: index + 1, error: "Invalid JSON-LD Syntax" });
        }
    });

    if (seoData.structuredData.length === 0) {
        seoData.structuredData = "n/a";
    }

    // =====================================================
    // 5. UBUNTU PRO DARK TERMINAL CONSOLE REPORTING
    // =====================================================
    const darkBox = "background: #1e1e1e; font-family: monospace; padding: 4px 8px; border-radius: 3px;";
    const orangeText = "color: #E95420; font-weight: bold;";
    const purpleText = "color: #ad52af; font-weight: bold;";
    const greenText = "color: #38B000; font-weight: bold;";
    const yellowText = "color: #FFB703; font-weight: bold;";
    const cyanText = "color: #7a8b6b; font-weight: bold;";
    const mutedText = "color: #8c8c8c; font-family: monospace;";

    console.group(
        "%c[✦] WPNINETYPLUS METADATA AUDITOR ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c Scan completed in %c${(performance.now() - startedAt).toFixed(2)} ms`,
        `${darkBox} color: #7a8b6b; font-weight: bold;`,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    // Basic SEO Group
    console.groupCollapsed(`%c[✦] BASIC SEO ELEMENTS`, `${darkBox} ${orangeText}`);
    console.table(seoData.basic);
    console.groupEnd();

    // Open Graph Group
    console.groupCollapsed(`%c[✦] OPEN GRAPH (SOCIAL MEDIA) TAGS`, `${darkBox} ${purpleText}`);
    if (typeof seoData.openGraph === "object" && Object.keys(seoData.openGraph).length > 0) {
        console.table(seoData.openGraph);
    } else {
        console.log("%c[WARN] Open Graph Tags: n/a", `${darkBox} ${yellowText}`);
    }
    console.groupEnd();

    // Twitter Cards Group
    console.groupCollapsed(`%c[✦] TWITTER CARD TAGS`, `${darkBox} ${orangeText}`);
    if (typeof seoData.twitter === "object" && Object.keys(seoData.twitter).length > 0) {
        console.table(seoData.twitter);
    } else {
        console.log("%c[WARN] Twitter Card Tags: n/a", `${darkBox} ${yellowText}`);
    }
    console.groupEnd();

    // Structured Data Group
    console.groupCollapsed(`%c[✦] STRUCTURED DATA (JSON-LD)`, `${darkBox} ${greenText}`);
    if (Array.isArray(seoData.structuredData)) {
        console.log(`%c[OK] Found ${seoData.structuredData.length} JSON-LD block(s):`, `${darkBox} ${greenText}`);
        seoData.structuredData.forEach(item => {
            console.log(`%c ├── [Block ${item.index}]`, `${darkBox} ${cyanText}`, item.data || item.error);
        });
    } else {
        console.log("%c[WARN] JSON-LD Structured Data: n/a", `${darkBox} ${yellowText}`);
    }
    console.groupEnd();

    console.log("%c--------------------------------------------------------------------------------", mutedText);
    console.log("%c[SYSTEM] Audit stream closed safely. Returning metadata object...", mutedText);
    console.groupEnd();

    return seoData;
}

window.WPNinetyPlusMetaData = WPNinetyPlusMetaData;