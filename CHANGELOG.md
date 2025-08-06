# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.2.1] - 2025-08-06

### Fixed

*   **Fatal Error:** Resolved a "Class not found" error in `Plugin.php` by adding the correct `use` statement for the `Singleton` class.
*   **Gutenberg ReferenceError:** Corrected a `ReferenceError: thePasteGutenberg is not defined` in the Gutenberg paste script by updating the localization object to `simplePasteGutenberg`.
*   **TinyMCE ReferenceError:** Fixed a `ReferenceError: simplepaste is not defined` in the TinyMCE plugin by correcting all instances of the localization object to `simple_paste`.

## [2.2.0] - 2025-08-06

### Changed

*   **Template Migration:** Migrated from legacy `the-paste` templates to new `simple-paste` templates to align with the new branding and improve code consistency.
*   **JavaScript Refactoring:** Updated all JavaScript files to use the new `simple-paste` template names and localization objects, removing outdated references.

## [2.1.5] - 2025-08-06

### Fixed

*   **JavaScript ReferenceError:** Corrected a `ReferenceError: simple_paste is not defined` by ensuring the JavaScript object is localized with the correct name (`simple_paste` instead of `simplepaste`).
*   **TinyMCE ReferenceError:** Fixed a `ReferenceError: simplepaste is not defined` in the TinyMCE plugin by correcting the localization logic to match the expected variable name.

## [2.1.4] - 2025-08-06

### Fixed

*   **JavaScript Errors:** Resolved critical JavaScript errors by replacing all instances of the old `thepaste` object with `simple_paste` and fixing an initialization issue in the TinyMCE plugin.

## [2.1.3] - 2025-08-06

### Fixed

*   **Fatal Error:** Resolved a "Class not found" error in `Core.php` by adding the correct `use` statement for the `Plugin` class.

## [2.1.2] - 2025-08-06

### Fixed

*   **Watermark Preview:** Replaced the external placeholder image in the watermark preview with a local asset to prevent loading failures when offline.

## [2.1.1] - 2025-08-06

### Fixed

*   **Watermark Toggle:** Fixed a critical bug where the watermark setting would not save correctly when disabled. The toggle switch now properly submits its off state, ensuring the feature can be deactivated.

## [2.1.0] - 2025-08-05

### Added

*   **Watermark Customization:** Added new settings for watermark size, opacity, and position.
*   **Watermark Preview:** Implemented a dynamic preview of the watermark on the settings page.

## [2.0.1] - 2025-08-05

### Fixed

*   **Settings Script:** Fixed a 404 Not Found error for `settings.js` by correcting the file path used in the `wp_enqueue_script` function.
*   **Watermark Uploader:** Created the `settings.js` file and implemented the necessary JavaScript to handle the watermark image uploader on the new settings dashboard.

## [2.0.0] - 2025-08-05

### Changed

*   **Settings Page Overhaul:** Completely redesigned the settings page into a modern dashboard, following a new design system.
*   **Consolidated Settings:** All plugin options are now managed in a single, unified settings page for a clearer user experience.

### Removed

*   Removed the old, fragmented settings implementation and legacy UI code.

## [1.9.4] - 2025-08-05

### Fixed

*   **Version Sync:** Synchronized version numbers across `index.php`, `plugin.php`, and `CHANGELOG.md`.
*   **Updater Stability:** Added a check in the `Updater` class to prevent errors if the plugin slug is not found.
*   **Watermarking:** Implemented the previously missing watermarking logic for both Imagick and GD libraries.

## [1.9.3] - 2025-08-05

### Fixed

*   **Image Optimization:** Fixed a fatal error caused by calling an undefined method `optimize_with_imagick` in the `Optimizer` class. Added the missing optimization functions.

## [1.9.2] - 2025-08-05

### Fixed

*   **File Renaming:** Fixed a fatal parse error in `include/ThePaste/Media/Renamer.php` caused by a syntax error in a ternary operator.

## [1.9.1] - 2025-08-05

### Fixed

*   **Autoloader:** Fixed a fatal parse error in `include/autoload.php` caused by an incorrect `spl_autoload_register` implementation.

## [1.9.0] - 2025-08-05

### Added

*   **Automatic Updates:** Implemented a custom update checker to provide automatic plugin updates from the GitHub repository.

## [1.8.1] - 2025-08-05

### Fixed

*   **PHP Compatibility:** Replaced the deprecated `__autoload` function with `spl_autoload_register` to ensure compatibility with PHP 8.0+.
*   **Version Sync:** Corrected the version number in `index.php` to match the latest release.

### Removed

*   Removed the outdated and irrelevant `readme.txt` file.

## [1.8.0] - 2025-08-05

### Added

*   **Table Pasting:** Implemented a feature to automatically convert pasted tables from spreadsheets or websites into `core/table` blocks.
    *   Added a new setting to enable/disable this feature.
    *   Updated the Gutenberg paste script to detect `<table>` elements in pasted HTML and create native table blocks.

## [1.7.0] - 2025-08-05

### Added

*   **Code Pasting:** Implemented a feature to automatically detect and convert pasted text into a code block.
    *   Added a new setting to enable/disable this feature.
    *   Updated the Gutenberg paste script to detect code patterns and insert `core/code` blocks.

## [1.6.0] - 2025-08-05

### Added

*   **Smart URL Pasting:** Implemented a feature to automatically convert pasted URLs from supported services (YouTube, Vimeo, Twitter) into embed blocks.
    *   Added a new setting to enable/disable this feature.
    *   Updated the Gutenberg paste script to detect and handle embeddable URLs.

## [1.5.0] - 2025-08-05

### Added

*   **HTML Cleanup on Paste:** Implemented a feature to automatically clean messy HTML (e.g., from Word, Google Docs) when pasted into the editor.
    *   Added a new setting to enable/disable this feature.
    *   Updated the Gutenberg paste script to handle HTML content, remove inline styles, and strip unnecessary tags.

## [1.4.0] - 2025-_08-05

### Added

*   **Automatic Watermarking:** Implemented a feature to automatically apply a watermark to uploaded images.
    *   Added a new section to the settings page to enable the feature, upload a watermark image, and set its position and opacity.
    *   Extended the `Optimizer` class to apply the watermark after image optimization.

## [1.3.0] - 2025-08-05

### Added

*   **Smart File Renaming:** Implemented a feature to automatically rename uploaded files based on a customizable pattern for better SEO and organization.
*   **Settings Page:** Created a new settings page under "Settings > The Paste" to control plugin features.
*   **Quick Image Attributes:** Added an option to automatically open the image block sidebar after pasting, encouraging users to add `alt text`.

### Changed

*   Updated `index.php` to initialize the new `Renamer` and `Settings` modules.
*   The Gutenberg paste script now checks for the "Quick Image Attributes" setting before acting.

## [1.2.0] - 2025-08-05

### Added

*   **Automatic Image Optimization:** Implemented a new feature to automatically optimize uploaded JPEG and PNG images.
    *   Created a new `Optimizer` class (`ThePaste\Media\Optimizer`) that hooks into `wp_handle_upload`.
    *   The optimizer uses `Imagick` if available for the best quality, with a fallback to the `GD Library`.

### Changed

*   Updated `index.php` to initialize the new `Optimizer` module.

## [1.1.0] - 2025-08-05

### Added

*   **Gutenberg Support:** Implemented native image pasting for the Gutenberg Block Editor.
    *   Created a new JavaScript file (`js/admin/gutenberg-paste.js`) to handle paste events and image uploads using the modern WordPress media API.
    *   Created a new PHP class (`ThePaste\Admin\Gutenberg\Gutenberg`) to enqueue the script on the correct hook (`enqueue_block_editor_assets`).

### Changed

*   Updated the main plugin file (`index.php`) to initialize the Gutenberg module and reflect the new forked status and version.

## [1.0.1] - 2025-08-05

### Changed

*   **Internal Refactoring:** Centralized the plugin version into a class constant (`Plugin::VERSION`) for better performance and easier maintenance.
*   **Security Hardening:** Added a check to prevent direct access to PHP files.

### Removed

*   Removed the redundant `include/version.php` file.

## [1.0.0] - 2025-08-05

### Added

*   **Initial Fork:** This is the first version of the forked plugin, based on the original "The Paste".
*   **GitHub Integration:** The project has been moved to [github.com/sadewadee/the-paste](https://github.com/sadewadee/the-paste) for all future development, issue tracking, and releases.
*   **Project Documentation:** Created `README.md` with a detailed project overview and future roadmap.
*   **Changelog:** Created this `CHANGELOG.md` file to track all future changes.

### Changed

*   **Update Mechanism:** The plugin's update process is now handled via GitHub, moving away from the WordPress.org SVN repository.
*   **Versioning:** Established a new versioning scheme starting from `1.0.0`.

### Planned

*   Full integration with the Gutenberg Block Editor.
*   Implementation of new features: image optimization, smart file renaming, HTML cleanup, and more as outlined in the README.
