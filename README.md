# SimplePaste

**Plugin Name:** SimplePaste
**Version:** 2.1.2
**Author:** sadewadee
**Author URI:** https://github.com/sadewadee
**Requires at least:** 5.0
**Tested up to:** 6.5
**Requires PHP:** 7.4
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

> A powerful plugin to supercharge your WordPress editor, allowing you to paste images, clean up HTML, and much more.

---

## Overview

**SimplePaste** enhances the WordPress editor. It started as a fork of the original "The Paste" plugin and has since evolved into a comprehensive tool for content creators. It modernizes the pasting workflow, enhances core functionality, and ensures compatibility with the latest web standards and WordPress features, including the Gutenberg Block Editor.

Development and updates for this version are managed exclusively through our [GitHub repository](https://github.com/sadewadee/the-paste).

## Installation

1.  Download the latest release from the [GitHub repository](https://github.com/sadewadee/the-paste/releases).
2.  In your WordPress dashboard, go to **Plugins > Add New**.
3.  Click **Upload Plugin** and select the `.zip` file you downloaded.
4.  Activate the plugin.

## Features

### Modern Workflow & UI
*   **Redesigned Settings Dashboard:** All plugin options are managed in a single, unified, and modern settings page for a clearer and more intuitive user experience.
*   **Automatic Updates:** Receive update notifications directly in your WordPress dashboard. Updates are delivered seamlessly from the official GitHub repository.

### Advanced Image Handling
*   **Gutenberg Support:** Seamlessly paste images directly into the Gutenberg Block Editor, creating native `core/image` blocks instantly.
*   **Automatic Image Optimization:** To improve site performance, all pasted and uploaded JPEG/PNG images are automatically compressed using `Imagick` (with a fallback to GD Library).
*   **Automatic Watermarking:** Apply a custom watermark to all uploaded images for branding and copyright protection.
    *   **Full Customization:** Control watermark size, opacity, and position (e.g., center, bottom-right).
    *   **Live Preview:** A dynamic preview on the settings page shows you exactly how your watermark will look.
*   **Smart File Renaming:** Automatically renames uploaded image files based on a customizable pattern for better SEO and media library organization.
*   **Quick Image Attributes:** After pasting an image, the block sidebar automatically opens, prompting you to add `alt text` and a `title` for improved accessibility and SEO.

### Intelligent Content Pasting
*   **HTML Cleanup on Paste:** Automatically sanitizes messy HTML (e.g., from Microsoft Word or Google Docs) when pasted, removing inline styles and unnecessary tags for clean, compliant code.
*   **Smart URL Pasting:** Automatically converts pasted URLs from supported services (YouTube, Vimeo, Twitter) into their respective `core/embed` blocks.
*   **Code Pasting:** Automatically detects and converts pasted text that looks like code into a `core/code` block, preserving all formatting and indentation.
*   **Table Pasting:** Instantly convert tables pasted from spreadsheets or websites into native `core/table` blocks.

## Changelog

All notable changes to this project are documented in the [CHANGELOG.md](CHANGELOG.md) file.

## Contributing

Contributions are welcome! Please feel free to fork the repository, make changes, and submit a pull request.
