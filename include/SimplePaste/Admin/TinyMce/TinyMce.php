<?php

namespace SimplePaste\Admin\TinyMce;

use SimplePaste\Asset;
use SimplePaste\Core;

class TinyMce extends Core\Singleton {

	/**
	 *	Module name
	 *	lowercase string.
	 */
	protected $module_name = 'simplepaste';

	/**
	 *	Editor buttons
	 */
	protected $editor_buttons = [
		'mce_buttons_2'	=> [
			'simplepaste_onoff'       => 'pastetext',
			'simplepaste_preferfiles' => 'simplepaste_onoff',
		],
	];

	/**
	 *	Plugin params
	 */
	protected $plugin_params = [];

	/**
	 *	TinyMCE Settings
	 */
	protected $mce_settings = [
		'paste_data_images' => false,
	];

	/**
	 *	Load custom css for toolbar.
	 */
	protected $toolbar_css = true;

	/**
	 *	Load custom css for editor.
	 */
	protected $editor_css = true;

	private $plugin_js;
	private $prefix;

	/**
	 * Private constructor
	 */
	protected function __construct() {
		$this->plugin_js = Asset\Asset::get( 'js/admin/mce/simple-paste-plugin.js' );
		$this->editor_css = Asset\Asset::get( 'css/admin/mce/simple-paste-editor.css' );
		$this->toolbar_css = Asset\Asset::get( 'css/admin/mce/simple-paste-toolbar.css' );

		$this->prefix = str_replace( '-', '_', $this->module_name );

		// add tinymce buttons
		$this->editor_buttons = wp_parse_args( $this->editor_buttons, [
			'mce_buttons'   => false,
			'mce_buttons_2' => false,
		] );

		foreach ( $this->editor_buttons as $hook => $buttons ) {
			if ( $buttons !== false ) {
				add_filter( $hook, [ $this, 'add_buttons' ] );
			}
		}

		// add tinymce plugin parameters
		if ( !empty($this->plugin_params) ) {
			add_action( 'wp_enqueue_editor', [ $this, 'action_enqueue_editor' ] );
		}
		if ( !empty($this->mce_settings) ) {
			add_action( 'tiny_mce_before_init', [ $this, 'tiny_mce_before_init' ] );
		}

		if ( $this->editor_css ) {
			add_filter('mce_css', [ $this, 'mce_css' ] );
		}
		if ( $this->toolbar_css ) {
			add_action( "admin_print_scripts", [ $this, 'enqueue_toolbar_css' ] );
		}

		// will only work with default editor
		add_filter( 'mce_external_plugins', [ $this, 'add_plugin' ] );

		parent::__construct();
	}

	/**
	 *	Add MCE plugin
	 *
	 *	@filter mce_external_plugins
	 */
	public function add_plugin( $plugins_array ) {
		$plugins_array[ $this->prefix ] = $this->plugin_js->url;
		return $plugins_array;
	}

	/**
	 *	Add toolbar Buttons.
	 *
	 *	@filter mce_buttons, mce_buttons_2
	 */
	public function add_buttons( $buttons ) {
		$hook = current_filter();
		if ( isset( $this->editor_buttons[ $hook ] ) && is_array( $this->editor_buttons[ $hook ] ) ) {
			foreach ( $this->editor_buttons[ $hook ] as $button => $position ) {
				if ( in_array( $button, $buttons ) ) {
					continue;
				}
				if ( is_string( $position ) ) {
					$position = array_search( $position, $buttons, true );
					if ( false !== $position ) {
						$position++;
					}
				}
				if ( $position === false ) {
					$buttons[] = $button;
				} else if ( is_int( $position ) ) {
					array_splice( $buttons, $position, 0, $button );
				}
			}
		}

		return array_unique( $buttons);
	}


	/**
	 *	Enqueue toolbar css
	 *
	 *	@action admin_print_scripts
	 */
	public function enqueue_toolbar_css() {
		$asset_id = sprintf( 'tinymce-%s-toolbar-css', $this->module_name );
		wp_enqueue_style( $asset_id, $this->get_toolbar_css_url() );
	}

	/**
	 *	@return string URL to editor css
	 */
	 protected function get_toolbar_css_url() {
 		return $this->toolbar_css->url;
 	}

	/**
	 *	Add editor css
	 *
	 *	@filter mce_css
	 */
	public function mce_css( $styles ) {
		$styles .= ','. $this->get_mce_css_url();
		return $styles;
	}

	/**
	 *	@return string URL to editor css
	 */
	protected function get_mce_css_url() {
		return $this->editor_css->url;
	}
	/**
	 *	print plugin settings
	 *
	 *	@action wp_enqueue_editor
	 */
	public function tiny_mce_before_init( $settings ) {
    	return $this->mce_settings + $settings;
	}

	/**
	 *	print plugin settings
	 *
	 *	@action wp_enqueue_editor
	 */
	public function action_enqueue_editor( $to_load ) {
		if ( $to_load['tinymce'] ) {
			add_action( 'admin_footer', [ $this, 'mce_localize' ] );
		}
	}
	/**
	 *	print plugin settings
	 *
	 *	@action admin_footer
	 */
	public function mce_localize( $to_load ) {
		$params = json_encode($this->plugin_params );
		printf( '<script type="text/javascript"> var %s = %s;</script>', $this->prefix, $params );
	}
}
