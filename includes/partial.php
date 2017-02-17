<?php
/**
 * Functions for managing partial submissions
 *
 * @package cf-connected-forms
 * Copyright 2017 Josh Pollock <Josh@CalderaWP.com
 */


class CF_Con_Form_Partial {

	/**
	 * Get all settings related to partial forms
	 *
	 * @since 1.1.0
	 *
	 * @param array $connected_form Form config for connected form
	 *
	 * @return array
	 */
	public static function get_settings( array $connected_form ){
		if( empty( $connected_form[ 'partial' ]  ) ){
			return array();
		}

		return $connected_form[ 'partial' ];
	}

	/**
	 * Get a specific setting related to partial settings
	 *
	 * @param array $connected_form Form config for connected form
	 * @param string $setting The setting to get
	 *
	 * @return mixed
	 */
	public static function get_setting( $connected_form, $setting ){
		$settings = self::get_settings( $connected_form );
		if( isset( $settings[ $setting ] ) ){
			return $settings[ $setting ];
		}

		return false;

	}

	/**
	 * Changes status of entry if active partial mode is on
	 *
	 * @since 1.1.0
	 *
	 * @uses "cf_form_connector_sequence_started" action
	 *
	 * @param $connected_form_id
	 * @param $entry_id
	 */
	public static function status_hook( $connected_form_id, $entry_id ){
		$connected_form = Caldera_Forms_Forms::get_form( $connected_form_id );
		if( ! empty( $connected_form ) && $connected_form[ 'is_connected_form' ] ){
			$active = self::get_setting( $connected_form, 'active_partial' );
			if( $active ){
				Caldera_Forms_Entry_Update::update_entry_status( 'active', $entry_id );
			}

		}


	}

	/**
	 * Writes the partial submission data to main entry
	 *
	 * @since 1.1.0
	 *
	 * @uses "cf_form_connector_sequence_advanced" action
	 *
	 * @param $connected_form_id
	 * @param $current_form_id
	 * @param $entry_id
	 * @param $sequence_data
	 */
	public static function add_values_to_main_entry( $connected_form_id, $current_form_id, $entry_id, $sequence_data ){
		$connected_form = Caldera_Forms_Forms::get_form( $connected_form_id );
		$entry = new Caldera_Forms_Entry( $connected_form, $entry_id );
		$fields = Caldera_Forms_Forms::get_fields( $connected_form );
		$field_data = cf_form_connector_current_fields( $sequence_data, $current_form_id );
		$entry = cf_form_connector_add_fields_to_entry(  $entry, $fields, $field_data );
		$entry->save();

	}


}