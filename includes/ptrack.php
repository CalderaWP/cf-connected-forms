<?php

/**
 * Class CF_Con_Form_PTrack
 *
 * Tracks the 'form-connector' processor added dynamically to control next form load.
 *
 * Added to deal with backwards nagivagtion not working and getting rid of global $form use.
 *
 * SEE: https://github.com/CalderaWP/cf-connected-forms/issues/43
 *
 */
class CF_Con_Form_PTrack {

	/**
	 * Processor congifuration
	 *
	 * @since 1.1.1
	 *
	 * @var array
	 */
	protected static $config;

	/**
	 * Get processors configuration
	 *
	 * @since 1.1.1
	 *
	 * @return array|null
	 */
	public static function get_config(){
		if ( is_array( static::$config ) ) {
			return static::$config;
		}
		return null;
	}

	/**
	 * Set processor configuration
	 *
	 * @since 1.1.1
	 *
	 * @param array $config
	 */
	public static function set_config( $config ){
		self::$config = $config;
	}

	/**
	 * Add 'form_configurations' to form if not set
	 *
	 * @since 1.1.1
	 *
	 * @param array $form Form config
	 *
	 * @return array
	 */
	public static function maybe_add_connections( $form ){

		if ( empty( $form[ 'form_connection' ] ) && ! empty( self::$config ) ) {
			$form[ 'form_connection' ] = self::$config;
		}

		return $form;
	}
}