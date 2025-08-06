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
