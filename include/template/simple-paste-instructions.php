<?php

if ( ! defined('ABSPATH') ) {
	die();
}

?>
<script type="text/html" id="tmpl-simple-paste-instructions">
	<div class="upload-instructions">
		<# if ( navigator.platform.indexOf('Mac') > -1 ) { #>
			<span class="upload-instructions-drop">
				<?php esc_html_e( 'Press <kbd>⌘</kbd>+<kbd>V</kbd> to paste', 'simple-paste' ); ?>
			</span>
		<# } else { #>
			<span class="upload-instructions-drop">
				<?php esc_html_e( 'Press <kbd>ctrl</kbd>+<kbd>V</kbd> to paste', 'simple-paste' ); ?>
			</span>
		<# } #>
	</div>
</script>