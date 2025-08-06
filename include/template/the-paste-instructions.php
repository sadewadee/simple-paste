<?php

if ( ! defined('ABSPATH') ) {
	die();
}

?>
<script type="text/html" id="tmpl-simple-paste-instructions">
	<p class="upload-instructions drop-instructions">
		<?php _ex( 'or', 'Uploader: Drop files here - or - Select Files' ); ?>
	</p>
	<p class="simple-paste-instructions">
		<?php
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'Mac OS' ) !== false ) {
			echo wp_kses( __( 'Press <kbd>⌘</kbd>+<kbd>V</kbd> to paste', 'simple-paste' ), [ 'kbd'=>[] ] );
		} else {
			echo wp_kses( __( 'Press <kbd>ctrl</kbd>+<kbd>V</kbd> to paste', 'simple-paste' ), [ 'kbd'=>[] ] );
		}
		?>
	</p>
</script>
