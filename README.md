<div align="center">

# 🚀 WPNinetyPlus (90+)
**WordPress Optimization & Speed Insights Plugin / PHP Library**

[![License: GNU GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-Plugin%20%2F%20Library-blue?logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?logo=php)](https://php.net)

*Achieve a 90+ Grade Speed Insight score effortlessly.*

</div>

---

## 📌 Table of Contents
- [Overview](#-overview)
- [Core Functions & Audit Features](#-core-functions--audit-features)
- [Configuration (`.env`)](#-configuration-env)
- [Hooks & Filters Reference](#-hooks--filters-reference)
- [License](#-license)
- [Support & Donations](#-support--donations)

---

## 🧐 Overview
**WPNinetyPlus** is a high-performance WordPress optimization plugin and PHP library designed to supercharge your website's speed, core web vitals, and asset delivery pipelines. 

---

## ⚙️ Configuration (`.env`)

For the Free Version (no GUI), place your configuration in the plugin directory using an `.env` file:

```env
WPNINETYPLUS_MANAGER = true
WPNINETYPLUS_SYNCASSETS = true

WPNINETYPLUS_ISOLATE = true
WPNINETYPLUS_INIT = false

# Warning: Full Paint requires a standard HTML document structure 
# using proper semantic tags (e.g., <head>, <body>, <header>, <main>, <section>, <footer>).
WPNINETYPLUS_FULLPAINT = true

OPTIMIZED_CLIENT_HTML = true
OPTIMIZED_CLIENT_JAVASCRIPT = true
OPTIMIZED_CLIENT_CSS = true
OPTIMIZED_CLIENT_LMERGE = true

# Warning: These advanced options increase file complexity and can 
# negatively impact website performance. Use only for specific edge cases.
WPNINETYPLUS_CUREDCLIENTHTML = true
WPNINETYPLUS_INTERINLINE = true
WPNINETYPLUS_INTERMERGE = true
WPNINETYPLUS_LAYMERGEINLINE = true

```


| Function | Description | Focus Area |
| :--- | :--- | :--- |
| **`WPNinetyPlus()`** | Executes a comprehensive, full-site performance audit to identify bottlenecks and elevate overall Core Web Vitals. | Full Website Audit |
| **`WPNinetyPlusAssetsInteractive()`** | Deep-dives into JavaScript execution, script loading behavior, and interactivity bottlenecks to minimize Total Blocking Time (TBT). | JavaScript Optimization |
| **`WPNinetyPlusAsstesLayout()`** | Analyzes CSS delivery, uncovers render-blocking stylesheets, and optimizes layout shifts for smoother rendering. | CSS & Layout Performance |
| **`WPNinetyPlusAssetsPaint()`** | Audits typography delivery, web fonts, and favicons to ensure fast First Contentful Paint (FCP) and prevent flash of unstyled text. | Fonts & Visual Elements |
| **`WPNinetyPlusGetImages()`** | Catalogs and analyzes all image assets, verifying next-gen format usage, proper sizing, and lazy-loading implementation. | Image Optimization |
| **`WPNinetyPlusGetVideos()`** | Scans, inspects, and audits embedded and hosted video content, reviewing compression, streaming efficiency, and lazy-loading policies to eliminate bandwidth bloat. | Video & Media Optimization |
| **`WPNinetyPlusMetaData()`** | Scans and validates meta tags, Open Graph data, and technical SEO markers to ensure search engine readiness and social share efficiency. | SEO & Metadata |
