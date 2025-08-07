<?php

namespace SimplePaste\Admin;

use SimplePaste\Asset;
use SimplePaste\Core;

class Admin extends Core\Singleton {

	/** @var TinyMce */
	private $mce;

	/** @var Asset\Asset */
	private $js;

	/** @var Asset\Asset */
	private $css;

	/** @var string */
	private $ajax_action_preferfiles = 'simple_paste_tinymce_preferfiles';

	/** @var string */
	private $ajax_action_onoff = 'simple_paste_tinymce_onoff';

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		if ( wp_is_mobile() ) {
			return;
		}

		// TinyMCE Advanced Plugin
		if ( $this->get_options()->tinymce_enabled ) {
			add_filter( 'tadv_allowed_buttons', function( $tadv_buttons ) {

				$tadv_buttons['simplepaste_onoff']       = __( 'Use Simple Paste', 'simple-paste' );
				$tadv_buttons['simplepaste_preferfiles'] = __( 'Paste as file', 'simple-paste' );
				add_action( 'admin_footer', [ $this, 'print_media_templates' ] );

				return $tadv_buttons;
			});
		}

		add_action( 'admin_init', [ $this, 'register_assets' ] );
		add_action( 'wp_enqueue_media', [ $this, 'enqueue_assets' ] );
		add_action( 'print_media_templates',  [ $this, 'print_media_templates' ] );
		add_action( 'wp_enqueue_editor', [ $this, 'enqueue_assets' ] );
		add_action( "wp_ajax_{$this->ajax_action_onoff}", [ $this, 'ajax_tinymce_enable' ] );
		add_action( "wp_ajax_{$this->ajax_action_preferfiles}", [ $this, 'ajax_tinymce_enable' ] );
/*

*/
		// block editor
		// add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_assets' ] );

	}

	/**
	 *	@action wp_ajax_simple_paste_tinymce_onoff
	 *	@action wp_ajax_simple_paste_tinymce_preferfiles
	 */
	public function ajax_tinymce_enable() {

		$action = wp_unslash( $_REQUEST['action'] );

		check_ajax_referer( $action );

		$enabled = isset( $_REQUEST['enabled'] )
			? (bool) wp_unslash( $_REQUEST['enabled'] )
			: false;

		$user = UserOptions::instance();
		if ( $action === $this->ajax_action_preferfiles ) {
			$user->tinymce         = $enabled;
		} else if ( $action === $this->ajax_action_onoff ) {
			$user->tinymce_enabled = $enabled;
		}
		$user->save();

		wp_send_json( [ 'success' => true ] );
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	public function register_assets() {

		$options = (object) $this->get_options();
		$user    = UserOptions::instance();

		$this->mce = TinyMce\TinyMce::instance();

		$current_user = wp_get_current_user();

		$this->css = Asset\Asset::get('css/admin/simple-paste.css')->register();

		$this->js = Asset\Asset::get('js/admin/simple-paste.js')
			->deps( [ 'jquery', 'media-editor' ] )
			->localize( [
				'l10n'    => [
					'upload_pasted_images' => __( 'Upload pasted images', 'simple-paste' ),
					'upload_image'         => __( 'Upload image', 'simple-paste' ),
					'simple_paste'         => __( 'Simple Paste', 'plugin name', 'simple-paste' ),
					'copy_paste'           => __( 'Copy & Paste', 'simple-paste' ),
					'paste_onoff'          => __( 'Use Simple Paste', 'simple-paste' ),
					'paste_files'          => __( 'Prefer pasting files', 'simple-paste' ),
				],
				'options' => [
					'editor'           => [
						// 'auto_upload'       => true,
						'debugMode'            => false,
						'preferfiles'          => $user->tinymce,
						'enabled'              => $user->tinymce_enabled,
						'preferfiles_ajax_url' => add_query_arg( [
							'action'      => $this->ajax_action_preferfiles,
							'_ajax_nonce' => wp_create_nonce( $this->ajax_action_preferfiles ),
						], admin_url( 'admin-ajax.php' ) ),
						'onoff_ajax_url'       => add_query_arg( [
							'action'      => $this->ajax_action_onoff,
							'_ajax_nonce' => wp_create_nonce( $this->ajax_action_onoff ),
						], admin_url( 'admin-ajax.php' ) ),
					],
					'mime_types'        => $this->get_mimetype_mapping() + [ 'svg' => 'image/svg+xml' ],
					'filename_values'   => [
						'username'  => $current_user->display_name,
						'userlogin' => $current_user->user_login,
						'userid'    => $current_user->ID,
					],
					'jpeg_quality'     => apply_filters( 'jpeg_quality', $options->image_quality, 'edit_image' ),
					/**
					 *	Filters the default filename
					 *
					 *	@param String $filename	The Filename. There are some placeholders:
					 *							Placeholders:
					 *								<postname> Name of current post
					 *								<username> Display name of current user
					 *								<userlogin> Login name of current user
					 *								<userid> Current user ID
					 *							Date and Time placeholders (a subset of php's strftime() format characters):
					 *								%Y Four-digit year
					 *								%y Two-digit year
					 *								%m Number of month with leading zero (01 to 12)
					 *								%d Day of month with leading zero (01 to 31)
					 *								%e Day of month (1 to 31)
					 *								%H Two digit hour in 24-hour format
					 *								%I Two digit hour in 12-hour format
					 *								%M Two digit minute
					 *								%S Two digit second
					 *								%s Unix timestamp
					 *								%x Date based on locale
					 *								%X Time based on locale
					 */
					'default_filename' => apply_filters( 'simple_paste_default_filename', $user->default_filename ),
				],
			], 'simple_paste' )
			->register();
	}

	/**
	 *	@return AbstractOptions|object
	 */
	private function get_options() {
		if ( (bool) get_option( 'simple_paste_enable_profile' ) ) {
			return UserOptions::instance()->options;
		} else {
			// Use new Settings options instead of WritingOptions
			return (object) [
				'tinymce_enabled' => get_option( 'simple_paste_tinymce_enabled', true ),
				'tinymce' => get_option( 'simple_paste_tinymce', true ),
				'image_quality' => get_option( 'simple_paste_image_quality', 90 ),
				'default_filename' => get_option( 'simple_paste_default_filename', 'Pasted' ),
			];
		}
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	public function enqueue_assets() {
		if ( current_user_can( 'upload_files' ) ) {
			if ( $this->css ) {
				$this->css->enqueue();
			}
			if ( $this->js ) {
				$this->js->enqueue();
			}
		}
	}

	/**
	 *	@action 'print_media_templates'
	 */
	public function print_media_templates() {
		if ( current_user_can( 'upload_files' ) ) {
			$rp = Core\Core::instance()->get_plugin_dir() . '/include/template/*.php';
			foreach ( glob( $rp ) as $template_file ) {
				include $template_file;
			}
		}
	}

	/**
	 *	@return array
	 */
	private function get_mimetype_mapping() {

		$mime_mapping = [];

		foreach( get_allowed_mime_types() as $extensions => $mime ) {
			foreach( explode( '|', $extensions ) as $extension ) {
				$mime_mapping[$extension] = $mime;
			}
		}
		uksort( $mime_mapping, function($a,$b) {
			// handle ambigous file extensions: put prefered suffix o front
			if ( in_array($a,['jpg','gz','tif','mov','mpeg','m4v','3gp','3g2','txt','html','m4a','ra','ogg','mid','ppt','xls'])) {
				return -1;
			}
			return 0;
		});
		return $mime_mapping;
	}
}
