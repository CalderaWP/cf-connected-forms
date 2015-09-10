<?php
/**
 * Save forward and back entry IDs and set GET vars based on this processor.
 *
 * @package   Caldera_Forms_Connector
 * @author    Josh Pollock <Josh@CalderaWP.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP
 */
class CF_Form_Connector {

	/**
	 * ID of the current form.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $form_id;

	/**
	 * ID of the next form.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $next_form_id;

	/**
	 * ID of the previous form.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $previous_form_id;


	/*
	 * Whether or not the next button was clicked.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $next_button_clicked = false;

	/**
	 * Whether or not the previous button was clicked.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $previous_button_clicked = false;

	/**
	 * Holds option for current form.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $option;

	/**
	 * Prefix for options used by this class.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $option_prefix = 'cf_con_';

	/**
	 * Redirect messages.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Constructor for this class
	 *
	 * @since 0.1.0
	 *
	 * @param array $config Processor config
	 * @param array $form Form config
	 */
	public function __construct( $config, $form ) {
		$this->set_form_id( $form );

		if ( ! is_string( $this->form_id ) ) {
			return;
		}

		$this->set_messages( $config );
		$this->set_ids( $config );
		$this->set_option();
		$this->buttons_clicked( $config );

		add_filter( 'caldera_forms_redirect_url', array( $this, 'process_redirect' ), 10, 2 );


		
	}

	/**
	 * Process forward/back and rewrite URL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url Redirect URL
	 * @param array $form Form config
	 * @param string $processid ID of proccesss
	 *
	 * @return string
	 */
	public function process_redirect( $url, $form ) {
		var_dump( $form['connected_stage_id'] );
		die;
		if ( $form[ 'ID' ] == $this->form_id ) {
			$parsed_url = parse_url( $url );

			if ( ! isset( $parsed_url[ 'query' ] ) ) {
				return;
			}

			parse_str( $parsed_url[ 'query' ] );

			if ( ! isset( $cf_id ) || 0 == absint( $cf_id ) ) {
				return $url; //bail

			}else{
				$cf_id = (int) $cf_id;
			}

			$url = remove_query_arg( 'cf_id', $url );
			$url = remove_query_arg( 'cf_su', $url );

			if ( $this->previous_button_clicked ) {
				$url = $this->go_back( $cf_id, $url );
			}

			if ( $this->next_button_clicked ) {
				$url = $this->go_forward( $cf_id, $url );
			}

			$args = array(
				'cf_con' => 1,
				'cf_con_nonce' => wp_create_nonce( 'cf_con_nonce' )
			);

			$url = add_query_arg( $args, $url );
			$this->save_option();

			/**
			 * Fires after form connection is processed.
			 *
			 * Use this to record a partial form submission.
			 *
			 * @since 0.1.0
			 *
			 * @param string $cf_id Entry ID of form that was just submitted.
			 * @param string $form_id ID of form that was just submitted.
			 */
			do_action( 'cf_connector_form_connected', $cf_id, $this->form_id );

		}


		return $url;

	}


	/**
	 * Save options and begin rewriteing URL for stepping backwards.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @param int $cf_id Entry ID of form that was just submitted.
	 * @param string $url Current redirect URL.
	 *
	 * @return string
	 */
	protected function go_back( $cf_id, $url ) {
		if ( isset( $this->previous_form_id ) ){

			//in previous form save this entry/this ID
			$option_name_for_prev = $this->option_prefix . $this->previous_form_id;

			$option_prev = get_option( $option_name_for_prev, array() );
			if ( isset( $option_prev[ 'next' ][ $this->form_id ] ) ) {
				$option_next[ 'next' ][ $this->form_id ] = array();
			}

			$option_prev[ 'next' ][ $this->form_id ] = $cf_id;
			update_option( $option_name_for_prev, $option_prev );

			//put previous form ID in URL
			$args = array(
				'cf_con_form_id' => $this->previous_form_id
			);

			//see if we have an entry ID saved for previous form
			if ( isset( $this->option[ 'prev' ][ $this->previous_form_id ] ) ) {
				$previous = $this->option[ 'prev' ][ $this->previous_form_id ];
				if ( 0 < absint( $previous ) ) {
					$args[ 'cf_id' ] = $previous;
				}

			}

			//update URL
			$url = add_query_arg( $args, $url );

			//set redirect message
			if ( isset( $this->messages[ 'next_message' ] ) ) {
				add_filter( 'caldera_forms_render_notices', function ( $notices, $form ) {
					$notices['success']['note'] = $this->messages[ 'next_message' ];

					return $notices;
				}, 10, 2 );
			}

		}

		return $url;

	}

	/**
	 * Save options and begin rewriteing URL for stepping forwards.
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @param int $cf_id Entry ID of form that was just submitted.
	 * @param string $url Current redirect URL.
	 *
	 * @return string
	 */
	protected function go_forward( $cf_id, $url ) {
		if ( isset( $this->next_form_id ) ){

			//in next form save this entry/this ID
			$option_name_for_next = $this->option_prefix . $this->next_form_id;
			$option_next = get_option( $option_name_for_next, array() );
			if ( isset( $option_next[ 'prev' ][ $this->form_id ] ) ) {
				$option_next[ 'prev' ][ $this->form_id ] = array();
			}


			$option_next[ 'prev' ][ $this->form_id ] = $cf_id;
			update_option( $option_name_for_next, $option_next );

			//put next form ID in URL
			$args = array(
				'cf_con_form_id' => $this->next_form_id
			);

			//see if we have an entry ID saved for next form
			if ( isset( $this->option[ 'next' ][ $this->next_form_id ] ) ) {
				$next = $this->option[ 'next' ][ $this->next_form_id ];
				if ( 0 < $next ) {
					$args[ 'cf_id' ] = $next;
				}

			}

			//set redirect message
			if ( isset( $this->messages[ 'previous_message' ] ) ) {
				add_filter( 'caldera_forms_render_notices', function ( $notices, $form ) {
					$notices['success']['note'] = $this->messages[ 'previous_message' ];

					return $notices;
				}, 10, 2 );
			}

			//update URL
			$url = add_query_arg( $args, $url );

		}

		return $url;

	}


	/**
	 * Get the value of the option for this form and set in option property
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 */
	protected function set_option() {
		$this->option = get_option( 'cf_con_' . $this->form_id, false );
		if ( false == $this->option ) {
			$this->option = array(
				'next' => array(),
				'prev' => array()
			);
		}
	}

	/**
	 * Save the current value of the option property
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 */
	private function save_option() {
		update_option(  $this->option_prefix . $this->form_id, $this->option );
	}

	/**
	 * Set previous and next form IDs, if possible, in their properties.
	 *
	 * @since 0.1.0
	 *
	 * @access private
	 *
	 * @param array $config Processor config
	 */
	private function set_ids( $config ) {
		foreach( array( 'previous_form_id', 'next_form_id' ) as $key ) {
			if ( isset( $config[ $key ] ) &&  ! empty( $config[ $key ] ) ) {
					$this->$key = $config[ $key ];
			}

		}

	}

	/**
	 * Determine if a forward or back button was clicked, and if so set property.
	 *
	 * @since 0.1.0
	 *
	 * @access private
	 *
	 * @param array $config Processor config
	 */
	private function buttons_clicked( $config ) {
		if ( isset( $config[ 'next_form_button' ] ) ) {
			$next = $config[ 'next_form_button' ];
			if ( isset( $_POST[ $next ] ) && 'click' == $_POST[ $next ] ) {
				$this->next_button_clicked = true;
			}

		}

		if ( isset( $config[ 'previous_form_button' ] ) ) {
			$previous = $config[ 'previous_form_button' ];
			if ( isset( $_POST[ $previous ] ) && 'click' == $_POST[ $previous ] ) {
				$this->previous_button_clicked = true;
			}

		}

	}

	/**
	 * Set form_id property.
	 *
	 * @since 0.1.0
	 *
	 * @access private
	 *
	 * @param array $config Processor config
	 */
	private function set_form_id( $form ) {
		$this->form_id = $form[ 'ID' ];
	}

	/**
	 * Set messages property with the redirect messages.
	 *
	 * @since 0.1.0
	 *
	 * @access private
	 *
	 * @param array $config Processor config
	 */
	private function set_messages( $config ) {
		foreach( array( 'next_message', 'previous_message' ) as $key ) {
			if ( isset( $config[ $key ] ) &&  ! empty( $config[ $key ] ) ) {
				$this->messages[ $key ] = $config[ $key ];
			}

		}

	}

}
