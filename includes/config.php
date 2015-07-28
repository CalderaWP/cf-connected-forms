<?php
/**
 * Form Connector Config template
 *
 * @package   Caldera_Forms_Connector
 * @author    Josh Pollock <Josh@CalderaWP.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP
 */

?>

<?php //next ?>
<div class="caldera-config-group">
	<label for="next_form_button">
		<?php _e( 'Next Button', 'cf-form-connector' ); ?>
	</label>
	{{{_field slug="next_form_button" type="button" exclude="system,variables"}}}
	<p class="description">
		<?php _e( 'Must be a submit button.', 'cf-form-connector' ); ?>
	</p>
</div>


<div class="caldera-config-group">
	<label for="next_form_id">
		<?php _e( 'Next Form ID', 'cf-form-connector' ); ?>
	</label>
	<div class="caldera-config-field">
		<input type="text" class="block-input field-config magic-tag-enabled" id="next_form_id" name="{{_name}}[next_form_id]" value="{{next_form_id}}" >
	</div>
	<p class="description">
		<?php _e( 'Enter the ID of the next form.', 'cf-form-connector' ); ?>
	</p>
</div>

<div class="caldera-config-group">
	<label for="next_message">
		<?php _e( 'Next Message', 'cf-form-connector' ); ?>
	</label>
	<div class="caldera-config-field">
		<input type="text" class="block-input field-config" id="next_message" name="{{_name}}[next_message]" value="{{next_message}}" >
	</div>
	<p class="description">
		<?php _e( 'Message to show when loading next form.', 'cf-form-connector' ); ?>
	</p>
</div>


<?php //previous ?>
<div class="caldera-config-group">
	<label for="previous_form_button">
		<?php _e( 'Back Button', 'cf-form-connector' ); ?>
	</label>
	{{{_field slug="previous_form_button" type="button" exclude="system,variables"}}}
	<p class="description">
		<?php _e( 'Must be a submit button.', 'cf-form-connector' ); ?>
	</p>
</div>

<div class="caldera-config-group">
	<label for="previous_form_id">
		<?php _e( 'Previous Form ID', 'cf-form-connector' ); ?>
	</label>
	<div class="caldera-config-field">
		<input type="text" class="block-input field-config magic-tag-enabled" id="previous_form_id" name="{{_name}}[previous_form_id]" value="{{previous_form_id}}" >
	</div>
	<p class="description">
		<?php _e( 'Enter the ID of the previous form.', 'cf-form-connector' ); ?>
	</p>
</div>

<div class="caldera-config-group">
	<label for="previous_message">
		<?php _e( 'Previous Message', 'cf-form-connector' ); ?>
	</label>
	<div class="caldera-config-field">
		<input type="text" class="block-input field-config" id="previous_message" name="{{_name}}[previous_message]" value="{{previous_message}}" >
	</div>
	<p class="description">
		<?php _e( 'Message to show when loading previous form.', 'cf-form-connector' ); ?>
	</p>
</div>
