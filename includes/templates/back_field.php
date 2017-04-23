<input type="hidden" id="<?php echo $field_base_id; ?>_input" name="<?php echo $field_name; ?>" value="0">

<?php echo $wrapper_before; ?>

	<?php echo $field_before; ?>
		<input data-field="<?php echo $field_base_id; ?>" class="cffld_backnav_btn <?php echo $field['config']['class']; ?>" type="button" name="<?php echo $field_name; ?>" value="<?php echo esc_attr( $field['label'] ); ?>" id="<?php echo $field_id; ?>" role="button">
	<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>
