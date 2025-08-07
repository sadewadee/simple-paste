# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

### [2.2.27-beta] - 2025-01-27

### Fixed
- Fixed remaining GD function calls without global namespace prefix in function_exists() checks and error logging

### [2.2.26] - 2025-01-27

### Added
- Force GD Library setting for watermarking (useful for testing GD implementation)
- Toggle option in settings to disable Imagick and force use of GD library

### Fixed
- Fixed namespace issues with GD functions causing fatal errors (added global namespace prefix \)
- Enhanced Imagick watermark alpha channel handling for more reliable opacity application
- Simplified opacity logic by using CHANNEL_ALL instead of CHANNEL_ALPHA for better compatibility
- Added automatic alpha channel initialization for watermarks that don't have one
- Improved error logging for alpha channel operations

## [2.2.24] - 2025-01-27

### Added
- Force GD Library setting for watermarking (useful for testing GD implementation)
- Toggle option in settings to disable Imagick and force use of GD library

### Improved
- Enhanced Imagick watermark alpha channel handling for more reliable opacity application
- Simplified opacity logic by using CHANNEL_ALL instead of CHANNEL_ALPHA for better compatibility
- Added automatic alpha channel initialization for watermarks that don't have one
- Improved error logging for alpha channel operations

## [2.2.23] - 2025-01-27

### Fixed
- Fixed watermark opacity not working for both Imagick and GD libraries
- Added multiple fallback methods for Imagick opacity: evaluateImage, setImageAlpha, setImageOpacity, and compositeImage with DISSOLVE
- Improved GD opacity handling by using imagecopymerge for transparent blending
- Added proper alpha blending configuration for GD watermark application
- Enhanced error logging with SimplePaste prefix for better debugging

## [2.2.22] - 2025-08-07

### Fixed
- Fixed watermark opacity not working correctly for transparent PNGs in GD library. Replaced `imagecopymerge` with a custom pixel-by-pixel function that correctly handles the alpha channel, ensuring opacity is applied without losing transparency.

## [2.2.21] - 2025-01-27

### Fixed
- Fixed WordPress 6.6 deprecation warning for `core/edit-post` store
- Replaced deprecated `openGeneralSidebar` with `enableComplementaryArea` from `core/interface` store
- Ensured compatibility with WordPress 6.6+ editor interface changes

## [2.2.20] - 2025-01-27

### Fixed
- Fixed watermark and file renaming not working in Gutenberg editor
- Corrected option name in rename_rest_attachment_file method (simple_paste_filename_pattern → simple_paste_file_renaming_pattern)
- Fixed hook priority order to ensure Optimizer runs before Renamer in REST API uploads
- Ensured proper execution sequence for watermark application and file renaming in Gutenberg

## [2.2.19] - 2025-01-27

### Added
- Watermark caching system to avoid reloading same watermark file multiple times
- Memory usage monitoring and automatic memory limit raising for image processing
- Performance metrics logging for watermark processing (execution time and memory usage)
- File size validation before watermark processing (50MB limit)
- Optimized GD watermark function to reduce temporary image creation

### Fixed
- Multiple performance bottlenecks in watermarking process causing slow image processing
- Excessive memory usage during watermark application
- Unnecessary temporary image creation for PNG opacity blending
- Missing error handling for image resource creation failures
- Memory leaks from improper resource cleanup

## [2.2.18] - 2025-01-27

### Fixed
- PHP Fatal error due to incorrect namespace usage for DateTime class in Renamer.php
- Fixed autoloader issue by using `\DateTime()` instead of `DateTime()` to reference PHP built-in class

## [2.2.17] - 2025-01-27

### Fixed
- Watermark and file renaming features not working in Gutenberg editor
- Added REST API hook support (rest_insert_attachment) for Gutenberg uploads in Optimizer and Renamer classes
- Implemented process_rest_uploaded_image() method in Optimizer.php for REST API image processing
- Implemented rename_rest_attachment_file() method in Renamer.php for REST API file renaming

## [2.2.16] - 2025-01-27

### Fixed
- Empty try-catch block in Optimizer.php watermark_with_imagick method that silently suppressed exceptions
- Added proper error logging for watermark application failures to improve debugging

## [2.2.15] - 2025-01-27

### Added
- Dedicated SimplePaste Settings page under Settings > SimplePaste for centralized configuration
- Automatic migration system to transfer settings from options-writing.php to new settings page
- Migration notice on options-writing.php directing users to new settings location
- Classic Editor settings section with TinyMCE options, image quality, and default filename configuration
- User Options section for profile-based settings management

### Changed
- Moved all SimplePaste settings from options-writing.php to dedicated settings page
- Updated Admin.php to use new settings structure instead of WritingOptions
- Simplified WritingOptions.php to show migration notice only

### Enhanced
- Better organization of settings with categorized sections (Classic Editor, Image Features, User Options, Watermarking)
- Improved user experience with dedicated settings page and clear migration path

## [2.2.14] - 2025-01-27

### Added
- SEO-optimized file renaming with 16 new placeholders including `{alt_text}`, `{title}`, `{year}`, `{month}`, `{day}`, `{time}`, `{site_name}`, `{author}`, `{post_id}`, `{category}`, and `{random_short}`
- Advanced SEO sanitization: automatic lowercase conversion, special character removal, space-to-hyphen conversion, multiple hyphen consolidation, filename length limitation (50 characters), and auto-generation for empty filenames
- Comprehensive documentation for File Renaming Pattern in settings page with visual guide, placeholder explanations, SEO features overview, and practical examples

### Enhanced
- `generate_name_from_pattern` function in `Renamer.php` with SEO-friendly sanitization and expanded placeholder support
- Settings page with detailed `render_file_renaming_field` method providing user-friendly documentation and examples

## [2.2.13] - 2025-01-27

### Added

*   **Dynamic File Renaming:** Enhanced file renaming to use Alt Text and Title from image block sidebar. The `{filename}` placeholder now dynamically updates when users modify Alt Text or Title in the Gutenberg image block sidebar.

### Changed

*   **Removed Logging:** Removed all error logging from Renamer class for cleaner operation and improved performance.

## [2.2.12] - 2025-08-07

### Added

*   **Extended Image Format Support:** Added support for modern image formats including WebP, AVIF, GIF, BMP, and TIFF in the image optimization process with appropriate compression settings for each format.

## [2.2.11] - 2025-08-07

### Fixed

*   **Error Handling:** Improved error handling in image optimization process with parameter validation, file existence checks, and comprehensive error logging to prevent fatal errors during image processing.

## [2.2.10] - 2025-08-07

### Fixed

*   **File Renaming Pattern:** Fixed File Renaming Pattern feature not working for pasted images. Added support for plupload/media uploader by implementing `add_attachment` hook and transient storage for filename patterns.

## [2.2.9] - 2025-08-07

### Added

*   **Multi-format Watermark Support:** Added support for JPEG and GIF watermark formats in addition to PNG in GD library implementation.

### Fixed

*   **Template Error:** Fixed "Template not found: #tmpl-simple-paste-instructions" error by creating the missing template file for paste instructions.
*   **Watermark Format Detection:** Improved watermark loading by detecting format automatically using `getimagesize()` and loading with appropriate function.

## [2.2.7] - 2025-08-07

### Added

*   **Info Tab:** Added a new tab in the settings page to display minimum requirements and system information.
*   **System Requirements Check:** Added visual indicators for WordPress version, PHP version, and image processing libraries (GD and Imagick) availability.


## [2.2.4] - 2025-08-06

### Fixed

*   **PHP Deprecated Warning:** Fixed PHP Deprecated warning about implicit conversion from float to int by adding explicit integer casting to watermark width calculations in both Imagick and GD implementations.

## [2.2.2] - 2025-08-06

### Fixed

*   **Namespace Error:** Fixed a critical error where `SimplePaste` was not defined by adding a class alias in `index.php` for backward compatibility.
*   **TinyMCE Button Error:** Fixed issues with TinyMCE buttons not working by ensuring the correct variable name (`simple_paste` instead of `simplepaste`) is used consistently in the TinyMCE plugin.
*   **TinyMCE Icon Display:** Fixed TinyMCE button icons not displaying by updating clipPath IDs in SVG definitions to match the IDs referenced in CSS.
*   **Template Consistency:** Updated template IDs, input names, and CSS class names in image-list.php and CSS files from 'the-paste' to 'simple-paste' for consistency with the new plugin name.
*   **JavaScript Consistency:** Updated HTML element IDs in JavaScript from 'the-pasted' to 'simple-pasted' for consistency with the new plugin name.
*   **Plugin URI Update:** Updated Plugin URI in index.php from 'the-paste' to 'simple-paste' for consistency with the new plugin name.
*   **GitHub URL Update:** Updated GitHub URL in CHANGELOG.md from 'the-paste' to 'simple-paste' for consistency with the new plugin name.
*   **CSS File Update:** Updated CSS file and source map references from 'the-paste-editor' to 'simple-paste-editor', 'the-paste-progress' to 'simple-paste-progress', and 'the-paste-toolbar' to 'simple-paste-toolbar' for consistency with the new plugin name.
*   **CSS Source Map:** Updated CSS class names in the toolbar source map from `thepaste` to `simplepaste` for consistency.
*   **File and Class Naming:** Updated file names, class names, and template IDs from 'the-paste' to 'simple-paste' for consistency with the new plugin name.

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

## [2.1.1] - 2025-08-06

### Fixed

*   **Watermark Toggle:** Fixed a critical bug where the watermark setting would not save correctly when disabled. The toggle switch now properly submits its off state, ensuring the feature can be deactivated.

## [2.0.1] - 2025-08-05

### Fixed

*   **Settings Script:** Fixed a 404 Not Found error for `settings.js` by correcting the file path used in the `wp_enqueue_script` function.

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

## [1.4.0] - 2025-08-05

### Added

*   **Image Processing:** Enhanced image processing capabilities for uploaded images.

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
*   **GitHub Integration:** The project has been moved to [github.com/sadewadee/simple-paste](https://github.com/sadewadee/simple-paste) for all future development, issue tracking, and releases.
*   **Project Documentation:** Created `README.md` with a detailed project overview and future roadmap.
*   **Changelog:** Created this `CHANGELOG.md` file to track all future changes.

### Changed

*   **Update Mechanism:** The plugin's update process is now handled via GitHub, moving away from the WordPress.org SVN repository.
*   **Versioning:** Established a new versioning scheme starting from `1.0.0`.

### Planned

*   Full integration with the Gutenberg Block Editor.
*   Implementation of new features: image optimization, smart file renaming, HTML cleanup, and more as outlined in the README.
