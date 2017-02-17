<?php
/**
 * Admin Ui for partial submissions
 *
 * @package cf-connected-forms
 * Copyright 2017 Josh Pollock <Josh@CalderaWP.com
 */

?>
<div class="caldera-config-group">
	<fieldset>
		<legend>
			<?php esc_html_e( 'Active Partials', 'connected-forms' ); ?>
		</legend>

			<input type="checkbox" id="active_partial" class="field-config" value="1"
			       name="config[partial][active_partial]"
			       aria-describedby="active_partial-description" <?php if ( ! empty( $element[ 'partial' ][ 'active_partial' ] ) ) {
				echo 'checked="checked";';
			} ?> />
			<label for="active_partial" id="active_partial-label" class="screen-reader-text">

				<?php esc_html_e( 'Enable', 'connected-forms' ); ?>
			</label>
			<p id="active_partial-description" class="description">
						<?php esc_html_e( 'By default, entries in sequence will only be shown in entry viewer when sequence is completed. Check this option to show partial entries in the entry viewer.', 'caldera-forms' ); ?>
			</p>


	</fieldset>

</div>

