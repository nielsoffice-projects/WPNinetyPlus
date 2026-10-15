import { WPNinetyPlusGetImages } from './WPNinetyPlusGM.js';
import { WPNinetyPlusAssetsPaint } from './WPNinetyPlusAP.js';
import { WPNinetyPlusAssetsInteractive } from './WPNinetyPlusAI.js';
import { WPNinetyPlusAsstesLayout } from './WPNinetyPlusAL.js';
import { WPNinetyPlusMetaData } from './WPNinetyPlusMD.js';


window.WPNinetyPlus = async function () {
    "use strict";

    const auditStart = performance.now();
    const metrics = {};
    const warnings = [];

    const round = (n, d = 2) => Number(n.toFixed(d));
    const toKB = bytes => `${(bytes / 1024).toFixed(2)} KB`;
    const toMB = bytes => `${(bytes / 1024 / 1024).toFixed(2)} MB`;

    function median(values) {
        const sorted = [...values].sort((a, b) => a - b);
        const mid = Math.floor(sorted.length / 2);
        return sorted.length % 2
            ? sorted[mid]
            : (sorted[mid - 1] + sorted[mid]) / 2;
    }

    // =====================================================
    // 1. WAIT FOR PAGE LOAD
    // =====================================================

    if (document.readyState !== "complete") {
        await new Promise(resolve =>
            window.addEventListener("load", resolve, { once: true })
        );
    }

    // =====================================================
    // 2. PAINT, LCP AND CLS METRICS
    // =====================================================

    function collectPaint() {
        for (const entry of performance.getEntriesByType("paint")) {
            metrics[entry.name] = round(entry.startTime);
        }
    }

    collectPaint();

    let lcpValue = null;
    let clsValue = 0;
    let lcpObserver = null;
    let clsObserver = null;

    try {
        if ("PerformanceObserver" in window) {
            if (PerformanceObserver.supportedEntryTypes?.includes(
                "largest-contentful-paint"
            )) {
                lcpObserver = new PerformanceObserver(list => {
                    for (const entry of list.getEntries()) {
                        lcpValue = entry.startTime;
                    }
                });
                lcpObserver.observe({
                    type: "largest-contentful-paint",
                    buffered: true
                });
            }

            if (PerformanceObserver.supportedEntryTypes?.includes(
                "layout-shift"
            )) {
                clsObserver = new PerformanceObserver(list => {
                    for (const entry of list.getEntries()) {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    }
                });
                clsObserver.observe({
                    type: "layout-shift",
                    buffered: true
                });
            }
        }
    } catch (error) {
        warnings.push(`Performance observers: ${error.message}`);
    }

    // =====================================================
    // 3. DOWNLOAD SPEED TEST
    // =====================================================

    async function measureDownloadSpeed() {
        const endpoint = "https://speed.cloudflare.com/__down";
        const testBytes = 5_000_000;
        const results = [];

        async function download(bytes) {
            const url =
                `${endpoint}?bytes=${bytes}&_=${Date.now()}-${Math.random()}`;

            const start = performance.now();

            const response = await fetch(url, {
                cache: "no-store",
                credentials: "omit"
            });

            if (!response.ok) {
                throw new Error(`Download HTTP ${response.status}`);
            }

            const data = await response.arrayBuffer();
            const seconds = (performance.now() - start) / 1000;

            if (!data.byteLength || seconds <= 0) {
                throw new Error("Invalid download measurement");
            }

            return {
                mbps: data.byteLength * 8 / seconds / 1_000_000,
                bytes: data.byteLength,
                seconds
            };
        }

        try {
            // Warm-up: not included in the score.
            await download(1_000_000);

            for (let i = 0; i < 3; i++) {
                results.push(await download(testBytes));
            }

            return {
                success: true,
                mbps: round(median(results.map(r => r.mbps))),
                trials: results.map((r, i) => ({
                    Test: i + 1,
                    Size: toMB(r.bytes),
                    "Time (s)": round(r.seconds, 3),
                    "Speed (Mbps)": round(r.mbps)
                }))
            };
        } catch (error) {
            warnings.push(`Download test: ${error.message}`);
            return {
                success: false,
                mbps: null,
                trials: [],
                error: error.message
            };
        }
    }

    // =====================================================
    // 4. UPLOAD SPEED TEST
    // =====================================================

    async function measureUploadSpeed() {
        const endpoint = "https://speed.cloudflare.com/__up";
        const testBytes = 5_000_000;
        const results = [];

        function createPayload(size) {
            const data = new Uint8Array(size);

            for (let offset = 0; offset < size; offset += 65_536) {
                const length = Math.min(65_536, size - offset);
                crypto.getRandomValues(data.subarray(offset, offset + length));
            }

            return data;
        }

        async function upload(data) {
            const start = performance.now();

            const response = await fetch(endpoint, {
                method: "POST",
                cache: "no-store",
                credentials: "omit",
                headers: {
                    "Content-Type": "application/octet-stream"
                },
                body: data
            });

            if (!response.ok) {
                throw new Error(`Upload HTTP ${response.status}`);
            }

            await response.arrayBuffer();

            const seconds = (performance.now() - start) / 1000;

            if (seconds <= 0) {
                throw new Error("Invalid upload measurement");
            }

            return {
                mbps: data.byteLength * 8 / seconds / 1_000_000,
                bytes: data.byteLength,
                seconds
            };
        }

        try {
            await upload(createPayload(250_000));

            for (let i = 0; i < 3; i++) {
                results.push(await upload(createPayload(testBytes)));
            }

            return {
                success: true,
                mbps: round(median(results.map(r => r.mbps))),
                trials: results.map((r, i) => ({
                    Test: i + 1,
                    Size: toMB(r.bytes),
                    "Time (s)": round(r.seconds, 3),
                    "Speed (Mbps)": round(r.mbps)
                }))
            };
        } catch (error) {
            warnings.push(
                `Upload test failed: ${error.message}. ` +
                "The endpoint may block uploads or browser requests."
            );

            return {
                success: false,
                mbps: null,
                trials: [],
                error: error.message
            };
        }
    }

    // =====================================================
    // 5. NAVIGATION TIMINGS
    // =====================================================

    const nav = performance.getEntriesByType("navigation")[0];

    if (nav) {
        metrics["DNS Lookup"] = round(
            nav.domainLookupEnd - nav.domainLookupStart
        );

        metrics["TCP Connection Setup"] = round(
            nav.connectEnd - nav.connectStart
        );

        metrics["Server Response (TTFB)"] = round(
            nav.responseStart - nav.requestStart
        );

        metrics["HTML Response End"] = round(nav.responseEnd);
        metrics["DOM Interactive"] = round(nav.domInteractive);

        metrics["DOMContentLoaded"] = round(
            nav.domContentLoadedEventEnd
        );

        metrics["Page Load Complete"] = round(nav.loadEventEnd);
    }

    // Run both tests one after the other to reduce interference.
    console.log("Testing download speed...");
    const download = await measureDownloadSpeed();

    console.log("Testing upload speed...");
    const upload = await measureUploadSpeed();

    // =====================================================
    // 6. NETWORK PROFILE
    // =====================================================

    const connection =
        navigator.connection ||
        navigator.mozConnection ||
        navigator.webkitConnection;

    const networkInfo = {
        "Download Speed": download.success
            ? `${download.mbps} Mbps`
            : "Unavailable",

        "Upload Speed": upload.success
            ? `${upload.mbps} Mbps`
            : "Unavailable",

        "Browser Effective Type":
            connection?.effectiveType?.toUpperCase() || "Unknown",

        "Browser Estimated Downlink":
            Number.isFinite(connection?.downlink)
                ? `${connection.downlink} Mbps`
                : "Unknown",

        "Browser Estimated RTT":
            Number.isFinite(connection?.rtt)
                ? `${connection.rtt} ms`
                : "Unknown",

        "Online Status": navigator.onLine ? "Online" : "Offline"
    };

    // =====================================================
    // 7. RESOURCE AND PAYLOAD AUDIT
    // =====================================================

    const encoder = new TextEncoder();
    let inlineJsBytes = 0;
    let inlineCssBytes = 0;

    document.querySelectorAll("script:not([src])").forEach(el => {
        inlineJsBytes += encoder.encode(el.textContent || "").length;
    });

    document.querySelectorAll("style").forEach(el => {
        inlineCssBytes += encoder.encode(el.textContent || "").length;
    });

    document.querySelectorAll("[style]").forEach(el => {
        inlineCssBytes += encoder.encode(
            el.getAttribute("style") || ""
        ).length;
    });

    const summary = {
        Images: { count: 0, bytes: 0 },
        VideoAudio: { count: 0, bytes: 0 },
        JavaScript: { count: 0, bytes: 0 },
        CSS: { count: 0, bytes: 0 },
        FetchXHR: { count: 0, bytes: 0 },
        Other: { count: 0, bytes: 0 }
    };

    let externalJsBytes = 0;
    let externalCssBytes = 0;
    let externalJsCount = 0;
    let externalCssCount = 0;

    for (const res of performance.getEntriesByType("resource")) {
        const type = (res.initiatorType || "").toLowerCase();
        const url = res.name.split("?")[0].split("#")[0];
        const size = res.transferSize || res.decodedBodySize || 0;

        let category = "Other";

        if (["img", "image"].includes(type)) {
            category = "Images";
        } else if (["video", "audio"].includes(type)) {
            category = "VideoAudio";
        } else if (type === "script" || /\.m?js$/i.test(url)) {
            category = "JavaScript";
            externalJsBytes += size;
            externalJsCount++;
        } else if (
            ["css", "stylesheet"].includes(type) ||
            /\.css$/i.test(url)
        ) {
            category = "CSS";
            externalCssBytes += size;
            externalCssCount++;
        } else if (["fetch", "xmlhttprequest"].includes(type)) {
            category = "FetchXHR";
        }

        summary[category].count++;
        summary[category].bytes += size;
    }

    const byteMetrics = {
        "HTML Transfer Size": toKB(nav?.transferSize || 0),
        "HTML Decoded Size": toKB(nav?.decodedBodySize || 0),

        [`External JavaScript (${externalJsCount} resources)`]:
            toKB(externalJsBytes),

        "Inline JavaScript": toKB(inlineJsBytes),

        "Total JavaScript (Estimated)":
            toKB(externalJsBytes + inlineJsBytes),

        [`External CSS (${externalCssCount} resources)`]:
            toKB(externalCssBytes),

        "Inline CSS": toKB(inlineCssBytes),

        "Total CSS (Estimated)":
            toKB(externalCssBytes + inlineCssBytes),

        "Image Payload": toKB(summary.Images.bytes),
        "Video / Audio Payload": toKB(summary.VideoAudio.bytes),
        "Fetch / XHR Payload": toKB(summary.FetchXHR.bytes),
        "Other Resource Payload": toKB(summary.Other.bytes)
    };

    if (performance.memory) {
        metrics["JS Heap Used (MB)"] = round(
            performance.memory.usedJSHeapSize / 1024 / 1024
        );
    }

    // =====================================================
    // 8. FINAL METRICS AND UBUNTU PRO CONSOLE REPORTING
    // =====================================================

    collectPaint();

    if (lcpValue !== null) {
        metrics["Largest Contentful Paint (LCP)"] = round(lcpValue);
    }

    metrics["Cumulative Layout Shift (CLS)"] = round(clsValue, 4);

    const darkBox = "background: #1e1e1e; font-family: monospace; padding: 4px 8px; border-radius: 3px;";
    const orangeText = "color: #E95420; font-weight: bold;";
    const purpleText = "color: #ad52af; font-weight: bold;";
    const greenText = "color: #38B000; font-weight: bold;";
    const yellowText = "color: #FFB703; font-weight: bold;";
    const cyanText = "color: #7a8b6b; font-weight: bold;";
    const mutedText = "color: #8c8c8c; font-family: monospace;";

    console.group(
        "%c[✦] WPNINETYPLUS COMPLETE WEB PERFORMANCE AUDIT ",
        "background: #111111; color: #E95420; font-family: monospace; font-size: 13px; font-weight: bold; padding: 8px 12px; border-left: 4px solid #77216F; border-radius: 2px;"
    );

    console.log(
        `%c[INFO]%c Main Audit completed in %c${(performance.now() - auditStart).toFixed(2)} ms`,
        `${darkBox} color: #7a8b6b; font-weight: bold;`,
        mutedText,
        cyanText
    );
    console.log("%c--------------------------------------------------------------------------------", mutedText);

    // Network Profile Group
    console.groupCollapsed(`%c[✦] INTERNET SPEED & NETWORK PROFILE`, `${darkBox} ${orangeText}`);
    console.table([networkInfo]);

    if (download.trials.length) {
        console.log(`%c [DL] Individual Download Tests`, `${darkBox} ${cyanText}`);
        console.table(download.trials);
    }

    if (upload.trials.length) {
        console.log(`%c [UL] Individual Upload Tests`, `${darkBox} ${purpleText}`);
        console.table(upload.trials);
    }
    console.groupEnd();

    // Payload Breakdown Group
    console.groupCollapsed(`%c[✦] PAYLOAD BREAKDOWN`, `${darkBox} ${yellowText}`);
    console.table(
        Object.entries(byteMetrics).map(([item, size]) => ({
            "Asset / Component": item,
            Size: size
        }))
    );
    console.groupEnd();

    // Performance Metrics Group
    console.groupCollapsed(`%c[✦] PERFORMANCE METRICS & TIMINGS`, `${darkBox} ${greenText}`);
    console.table(
        Object.entries(metrics).map(([metric, value]) => ({
            Metric: metric,
            Value: value,
            "Seconds (if timing)": metric.includes("(CLS)") ||
                metric.includes("(MB)") ? "N/A" : (value / 1000).toFixed(3)
        }))
    );
    console.groupEnd();

    // Resource Summary Group
    console.groupCollapsed(`%c[✦] RESOURCE SUMMARY`, `${darkBox} ${cyanText}`);
    console.table(
        Object.entries(summary).map(([type, data]) => ({
            Type: type,
            Count: data.count,
            Size: toKB(data.bytes)
        }))
    );
    console.groupEnd();

    if (warnings.length) {
        console.groupCollapsed(`%c[!] AUDIT WARNINGS (${warnings.length})`, `${darkBox} ${yellowText}`);
        console.warn(warnings);
        console.groupEnd();
    }

    lcpObserver?.disconnect();
    clsObserver?.disconnect();

    console.log("%c--------------------------------------------------------------------------------", mutedText);
    console.log("%c[SYSTEM] Main audit stream closed successfully. Triggering module audits...", mutedText);
    console.groupEnd();

    // Trigger sub-audits
    WPNinetyPlusMetaData();
    WPNinetyPlusGetImages();
    WPNinetyPlusAssetsPaint();
    WPNinetyPlusAssetsInteractive();
    WPNinetyPlusAsstesLayout();

    return {
        metrics,
        networkInfo,
        byteMetrics,
        download,
        upload,
        warnings,
    };
};

window.WPNinetyPlusGetImages = WPNinetyPlusGetImages;
window.WPNinetyPlusAssetsPaint = WPNinetyPlusAssetsPaint;
window.WPNinetyPlusAssetsInteractive = WPNinetyPlusAssetsInteractive;
window.WPNinetyPlusAsstesLayout = WPNinetyPlusAsstesLayout;
window.WPNinetyPlusMetaData = WPNinetyPlusMetaData;
window.WPNinetyPlus = window.WPNinetyPlus;