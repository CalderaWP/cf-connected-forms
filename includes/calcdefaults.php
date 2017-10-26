<?php
/**
 * Functions for ensuring that calcDefaults works with magic tags from Connected Forms Properly
 *
 * @package cf-connected-forms
 * Copyright 2017 Josh Pollock <Josh@CalderaWP.com
 */

class CF_Con_Form_CalcDefaults {

	/**
	 * Apply checks/updates to all fields of a form
	 *
	 * @since 1.2.2
	 *
	 * @param array $form Form config
	 *
	 * @return array
	 */
	public static function filter_form( $form ){
		foreach ( $form[ 'fields'] as &$field ){
			$field = self::filter_field( $field );
		}
		return $form;
	}

	/**
	 * Apply checks/updates to a specific field.
	 *
	 * @uses "caldera_forms_render_get_field" filter
	 *
	 * @since 1.2.2
	 *
	 * @param array $field Field config
	 *
	 * @return array
	 */
	public static function filter_field( $field ){

			//filer select options
			if ( ! empty( $field[ 'config' ][ 'option' ] ) ) {
				foreach ( $field[ 'config' ][ 'option' ] as $opt_id => $opt ) {
					foreach (
						array(
							'default',
							'value',
							'calc_default'
						) as $key
					) {
						if ( self::is_prev_magic( $opt[ $key ] ) ) {
							$field[ 'config' ][ 'option' ][ $opt_id ][ $key ] = Caldera_Forms::do_magic_tags( $field[ 'config' ][ 'option' ][ $opt_id ][ $key ] );

						}
					}
				}

			}

			//filter .config options
			foreach (
				array(
					'default',
					'value',
				) as $key
			) {
				if ( isset( $field[ 'config' ][ $key ] ) && self::is_prev_magic(  $field[ 'config' ][ $key ]  ) ) {
					$field[ 'config' ][ $key ]  = Caldera_Forms::do_magic_tags(  $field[ 'config' ][ $key ]  );

				}
			}



		return $field;
	}

	/**
	 * Check if a value is a '{prev:}' magic tag
	 *
	 * @since 1.2.2
	 *
	 * @param string $test
	 *
	 * @return bool
	 */
	protected static function is_prev_magic( $test ){
		return 0 === strpos( $test, '{prev:' );
	}

}
