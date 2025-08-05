<?php

/*
Plugin Name: SimplePaste
Plugin URI: https://github.com/sadewadee/the-paste
Description: A powerful plugin to supercharge your WordPress editor, allowing you to paste images, clean up HTML, and much more.
Author: sadewadee
Version: 2.1.2
Author URI: https://github.com/sadewadee
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 7.4
Text Domain: simple-paste
Domain Path: /languages
*/

/*  Copyright 2019-2023 Jörn Lund (Original Author)
    Copyright 2025 sadewadee (Fork Author)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


namespace SimplePaste;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';

Core\Core::instance( __FILE__ );

if ( is_admin() || defined( 'DOING_AJAX' ) ) {
	add_action( 'init', function() {
        $plugin = Core\Core::instance();
		Admin\Admin::instance();
		Admin\UserOptions::instance();
		Admin\WritingOptions::instance();
        Admin\Gutenberg\Gutenberg::instance();
        Media\Optimizer::instance();
        Media\Renamer::instance();
        Admin\Settings::instance();
        Core\Updater::instance( $plugin->get_wp_plugin() );
	});
}
