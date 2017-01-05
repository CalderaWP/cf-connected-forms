<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 1/4/17
 * Time: 8:27 PM
 */

namespace calderawp\conform;


class control {


	protected $form;

	protected $rendering;

	protected $steps;

	public function __construct() {

		add_filter( 'caldera_forms_get_form', array( $this, 'setup' ) );
		add_filter( 'caldera_forms_submit_get_form', array( $this, 'setup' ) );

		add_filter( 'caldera_forms_render_entry_id', array( $this, 'change_entry', 10, 2 ) );
	}

	public function setup( $form ){
		if( $this->is_connected_form( $form ) ){
			$this->form = $form;
			$this->setup_processors();
			$this->setup_progress();
			return $this->form;
		}

		return $form;
	}

	public function change_entry( $entry_id, $form ){

	}



	protected function setup_processors(){

	}

	protected function setup_progress(){

	}

	public function is_connected_form( $form ){
		return ! empty( $form[ 'is_connected_form' ] );
	}


	protected function add_processor(){

	}
}