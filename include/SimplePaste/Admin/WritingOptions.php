<?php
/**
 *	@package SimplePaste\Admin
 *	@version 1.0.0
 *	2018-09-22
 */

namespace SimplePaste\Admin;

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

use SimplePaste\Asset;
use SimplePaste\Core;

class WritingOptions extends AbstractOptions {

	private $optionset = 'writing';

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		parent::__construct();

		add_action( 'admin_init', [ $this, 'register_settings' ] );

		add_option( 'simple_paste_enable_profile', '1', '', true );

		$this->load();

	}

	/**
	 *	@inheritdoc
	 */
	public function load() {
		$this->_options = get_option( $this->option_name, $this->defaults );

		if ( ! is_array( $this->_options ) ) {
			$this->_options = [];
		}
		$this->_options = wp_parse_args( $this->_options, $this->defaults );
	}

	/**
	 *	@inheritdoc
	 */
	public function save() {
		update_option( $this->option_name, (array) $this->options );
	}

	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {
		// Settings have been moved to SimplePaste Settings page
		// This method is kept for backward compatibility but does nothing
		
		// Add a notice to redirect users to the new settings page
		add_action( 'admin_notices', [ $this, 'show_migration_notice' ] );
	}

	/**
	 *	Show migration notice on options-writing.php page
	 *
	 *	@action admin_notices
	 */
	public function show_migration_notice() {
		$screen = get_current_screen();
		
		// Only show on options-writing.php page
		if ( $screen && $screen->id === 'options-writing' ) {
			$settings_url = admin_url( 'options-general.php?page=simple-paste-settings' );
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<strong><?php esc_html_e( 'SimplePaste Settings Moved!', 'simple-paste' ); ?></strong><br>
					<?php esc_html_e( 'SimplePaste settings have been moved to a dedicated settings page for better organization.', 'simple-paste' ); ?>
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary" style="margin-left: 10px;">
						<?php esc_html_e( 'Go to SimplePaste Settings', 'simple-paste' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}
}
