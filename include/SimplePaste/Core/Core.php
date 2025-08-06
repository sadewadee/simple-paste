<?php
/**
 *	@package SimplePaste\Core
 *	@version 1.0.1
 *	2018-09-22
 */

namespace SimplePaste\Core;

use SimplePaste\Asset;
use SimplePaste\Core\Plugin;

class Core extends Plugin implements CoreInterface {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_filter( 'kses_allowed_protocols', [ $this, 'add_data_protocol' ] );

		$args = func_get_args();
		parent::__construct( ...$args );
	}

	/**
	 *	Init hook.
	 *
	 *  @filter kses_allowed_protocols
	 */
	public function add_data_protocol( $protocols ) {
		$protocols[] = 'data';
		return $protocols;
	}
}
