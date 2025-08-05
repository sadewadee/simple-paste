<?php

namespace ThePaste\Admin\Gutenberg;

use ThePaste\Asset\Asset;
use ThePaste\Core\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles Gutenberg integration.
 */
class Gutenberg extends Singleton {

    /**
     * Constructor.
     */
    protected function __construct() {
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
    }

    /**
     * Enqueues the necessary assets for the Gutenberg editor.
     *
     * @action enqueue_block_editor_assets
     */
    public function enqueue_editor_assets() {
        $settings = [
            'quickAttributes' => (bool) get_option( 'the_paste_quick_attributes', true ),
            'htmlCleanup'     => (bool) get_option( 'the_paste_html_cleanup', true ),
            'smartUrl'        => (bool) get_option( 'the_paste_smart_url', true ),
            'codePasting'     => (bool) get_option( 'the_paste_code_pasting', true ),
            'tablePasting'    => (bool) get_option( 'the_paste_table_pasting', true ),
        ];

        Asset::get( 'js/admin/gutenberg-paste.js' )
            ->deps( [ 'wp-blocks', 'wp-data', 'wp-i18n', 'wp-media-utils', 'wp-notices' ] )
            ->localize( $settings, 'thePasteGutenberg' )
            ->enqueue();
    }
}
