<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 1/4/17
 * Time: 7:12 PM
 */

namespace calderawp\conform;


class form {

	protected $form;

	protected $sub_forms;

	public function __construct( array  $form ) {
		$this->form = $form;
		$this->set_sub_forms();
	}


	public function get_sub_forms(){
		return $this->sub_forms;
	}

	protected function set_sub_forms(){
		if( ! empty( $this->form[ 'node' ] ) ){
			$this->sub_forms = wp_list_pluck( $this->form[ 'node' ], 'form');
		}
	}


}