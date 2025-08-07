### Bug #025 - 2025-01-27
*Issue: Watermark opacity not working for both Imagick and GD libraries. For Imagick, the code only checked for evaluateImage method existence but didn't verify if required constants (EVALUATE_MULTIPLY, CHANNEL_ALPHA) were defined, causing silent failures. For GD, the code used imagecopy instead of imagecopymerge for opacity blending, and lacked proper alpha blending configuration.
*File: include/SimplePaste/Media/Optimizer.php
*Status: Fixed
*Recommendation: Implement multiple fallback methods for Imagick opacity (evaluateImage, setImageAlpha, setImageOpacity, compositeImage with DISSOLVE) with proper constant checking. For GD, use imagecopymerge with proper alpha blending configuration and apply_watermark_opacity_gd for pixel-level opacity control.
*Severity: High

### Bug #024 - 2025-08-07
*Issue: Watermark opacity does not work correctly for transparent PNGs when using the GD library. The `imagecopymerge` function fails to preserve the alpha channel, causing transparent areas of the watermark to appear black or distorted.
*File: include/SimplePaste/Media/Optimizer.php
*Status: Fixed
*Recommendation: Replace the `imagecopymerge` logic with a custom function that iterates through each pixel, calculates the new alpha value based on the desired opacity, and applies it while preserving the original RGB and alpha information. This ensures transparency is handled correctly.
*Severity: High

### Bug #023 - 2025-01-27
*Issue: WordPress 6.6 deprecation warning for core/edit-post store usage. The gutenberg-paste.js file uses deprecated dispatch('core/edit-post').openGeneralSidebar() which has been deprecated since WordPress 6.3 and should use core/interface store instead.
*File: js/admin/gutenberg-paste.js
*Status: Fixed
*Recommendation: Replace openGeneralSidebar with enableComplementaryArea from core/interface store for WordPress 6.6+ compatibility.
*Severity: Medium

### Bug #021 - 2025-01-27
*Issue: Multiple performance bottlenecks in watermarking process causing slow image processing and potential memory issues. Key issues: 1) No memory limit checks before processing large images, 2) Multiple image resource creation without proper cleanup in GD watermark function, 3) Unnecessary temporary image creation for PNG opacity blending, 4) No file size validation before watermark processing, 5) Watermark file loaded and processed for every image without caching.
*File: include/SimplePaste/Media/Optimizer.php
*Status: Fixed
*Recommendation: 1) Add memory usage monitoring and wp_raise_memory_limit() calls, 2) Implement watermark caching to avoid reloading same watermark file, 3) Add file size limits for watermark processing, 4) Optimize GD watermark function by reducing temporary image creation, 5) Add execution time monitoring for large images.
*Severity: High

### Bug #020 - 2025-01-15
*Issue: PHP Fatal error due to incorrect namespace usage for DateTime class in Renamer.php. The code uses `new DateTime()` without backslash, causing autoloader to look for SimplePaste\Media\DateTime instead of PHP built-in DateTime class.
*File: include/SimplePaste/Media/Renamer.php
*Status: Fixed
*Recommendation: Use `new \DateTime()` with leading backslash to reference PHP built-in DateTime class from within namespaced code.
*Severity: Critical

### Bug #022 - 2025-01-27
*Issue: Incorrect option name and hook priority causing watermark and file renaming to fail in Gutenberg editor. The rename_rest_attachment_file method used wrong option name 'simple_paste_filename_pattern' instead of 'simple_paste_file_renaming_pattern'. Also, both Optimizer and Renamer had same hook priority (10) causing unpredictable execution order.
*File: include/SimplePaste/Media/Renamer.php
*Status: Fixed
*Recommendation: Use correct option name and set proper hook priorities to ensure Optimizer runs before Renamer.
*Severity: High

### Bug #019 - 2025-01-15
*Issue: Watermark and file renaming features not working in Gutenberg editor. The wp.mediaUtils.uploadMedia function used by Gutenberg uploads files via REST API which triggers rest_insert_attachment hook instead of traditional wp_handle_upload and wp_handle_upload_prefilter hooks used by Optimizer and Renamer classes.
*File: include/SimplePaste/Media/Optimizer.php, include/SimplePaste/Media/Renamer.php
*Status: Fixed
*Recommendation: Add rest_insert_attachment hook support in both Optimizer and Renamer classes to handle Gutenberg uploads. Implement process_rest_uploaded_image() and rename_rest_attachment_file() methods to process images uploaded via REST API.
*Severity: High

### Bug #018 - 2025-01-15
*Issue: Empty try-catch block in Optimizer.php watermark_with_imagick method at line 246 that silently suppresses all exceptions, potentially hiding critical errors during watermark application.
*File: include/SimplePaste/Media/Optimizer.php
*Status: Fixed
*Recommendation: Add proper error logging in the catch block to track watermark application failures: `catch ( \Exception $e ) { error_log( 'SimplePaste Optimizer: Watermark application failed: ' . $e->getMessage() ); }`
*Severity: Medium

### Bug #017 - 2025-08-07
*Issue: Fatal error in SimplePaste\Media\Optimizer->process_uploaded_image due to insufficient parameter validation and error handling. The function did not validate input parameters properly and lacked comprehensive error handling for image processing operations.
*File: include/SimplePaste/Media/Optimizer.php
*Status: Fixed
*Recommendation: Add parameter validation to check if $upload is array with required keys, validate file existence, and wrap image processing operations in try-catch blocks with proper error logging.
*Severity: Critica
### Bug #016 - 2025-08-07
*Issue: File Renaming Pattern feature not working for pasted images. The `wp_handle_upload_prefilter` hook is not called for files uploaded via plupload/media uploader (used by SimplePaste), only for traditional form uploads.
*File: include/SimplePaste/Media/Renamer.php
*Status: Fixed
*Recommendation: Implement additional hooks for plupload uploads using `add_attachment` action and store filename pattern in transients during `wp_ajax_upload-attachment` to handle media uploader file renaming.
*Severity: High

### Bug #013 - 2025-08-06
*Issue: PHP Deprecated warning about implicit conversion from float to int loses precision in watermark width calculations.
*File: include/SimplePaste/Media/Optimizer.php
*Status: Fixed
*Recommendation: Add explicit integer casting to watermark width calculations in both Imagick and GD implementations to prevent PHP Deprecated warnings.
*Severity: Low

### Bug #011 - 2025-08-06
*Issue: CSS class names in toolbar source map still using old plugin name ('thepaste' instead of 'simplepaste').
*File: css/admin/mce/simple-paste-toolbar.css.map
*Status: Fixed
*Recommendation: Ensure all CSS class names in source maps are updated to match the new plugin name for consistency.
*Severity: Low

### Bug #009 - 2025-08-06
*Issue: CSS file and source map still using old plugin name references ('the-paste-editor' instead of 'simple-paste-editor' and 'the-paste-progress' instead of 'simple-paste-progress').
*File: css/admin/mce/simple-paste-editor.css, css/admin/mce/simple-paste-editor.css.map
*Status: Fixed
*Recommendation: Ensure all CSS files and source maps are updated to match the new plugin name for consistency.
*Severity: Low

### Bug #010 - 2025-08-06
*Issue: CSS map for toolbar still using old plugin name references ('the-paste-toolbar' instead of 'simple-paste-toolbar').
*File: css/admin/mce/simple-paste-toolbar.css.map
*Status: Fixed
*Recommendation: Ensure all CSS maps are updated to match the new plugin name for consistency.
*Severity: Low

### Bug #008 - 2025-08-06
*Issue: GitHub URL in CHANGELOG.md still using old plugin name ('the-paste' instead of 'simple-paste').
*File: CHANGELOG.md
*Status: Fixed
*Recommendation: Ensure all GitHub URLs are updated to match the new plugin name for consistency.
*Severity: Low

### Bug #007 - 2025-08-06
*Issue: Plugin URI in index.php still using old plugin name ('the-paste' instead of 'simple-paste').
*File: index.php
*Status: Fixed
*Recommendation: Ensure the Plugin URI is updated to match the new plugin name for consistency.
*Severity: Low

### Bug #006 - 2025-08-06
*Issue: HTML element IDs in JavaScript still using old plugin name ('the-pasted' instead of 'simple-pasted').
*File: js/admin/mce/simple-paste-plugin.js
*Status: Fixed
*Recommendation: Ensure all HTML element IDs in JavaScript are updated to match the new plugin name for consistency.
*Severity: Medium

### Bug #005 - 2025-08-06
*Issue: Template IDs and CSS class names in image-list.php and CSS files still using old plugin name ('the-paste' instead of 'simple-paste').
*File: include/template/image-list.php, css/admin/mce/simple-paste-editor.css
*Status: Fixed
*Recommendation: Ensure all template IDs, input names, and CSS class names are updated to match the new plugin name for consistency.
*Severity: Medium

### Bug #004 - 2025-08-06
*Issue: TinyMCE button icons for SimplePaste not displaying due to incorrect clipPath IDs in SVG definitions.
*File: include/template/icons.php
*Status: Fixed
*Recommendation: Ensure the clipPath IDs in SVG definitions match the IDs referenced in CSS files.
*Severity: Medium

### Bug #003 - 2025-08-06
*Issue: TinyMCE buttons for SimplePaste not working due to incorrect variable name in TinyMCE plugin initialization.
*File: include/SimplePaste/Admin/TinyMce/TinyMce.php
*Status: Fixed
*Recommendation: Ensure the TinyMCE plugin uses the correct variable name (`simple_paste` instead of `simplepaste`) consistently throughout the code.
*Severity: Critical

### Bug #002 - 2025-08-06
*Issue: A `ReferenceError: simple_paste is not defined` occurs in the browser console, preventing the plugin's JavaScript from executing. This is caused by localizing the script with the wrong variable name (`simplepaste` instead of `simple_paste`).
*File: include/SimplePaste/Admin/Admin.php
*Status: Fixed
*Recommendation: Ensure the `wp_localize_script` function (or its wrapper in the `Asset` class) uses the correct variable name that matches what the JavaScript file expects.
*Severity: Critical

### Bug #001 - 2025-08-06
*Issue: Found a console.log statement in a minified JavaScript file that might indicate debugging or an unhandled error during migration.
*File: js/admin/mce/simple-paste-plugin.js
*Status: Fixed
*Recommendation: Investigate the purpose of the console.log in the minified file. If it's for debugging, remove it. If it indicates an error, address the underlying issue.
*Severity: Low
