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


	

}
