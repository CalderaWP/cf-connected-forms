<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 1/4/17
 * Time: 7:23 PM
 */

namespace calderawp\conform\progress;


class step implements \JsonSerializable, \calderawp\conform\progress {

	protected $form;

	protected $entry;


	public function __construct() {

	}


	public function get_form_id(){
		return $this->form[ 'ID' ];
	}

	public function jsonSerialize() {
		return $this->toArray();
	}

	public function toArray() {
		return array(
			'form' => $this->get_form_id(),
			'entry' => $this->entry
		);
	}


	public function get_entry(){

	}

	public function set_entry( \Caldera_Forms_Entry $entry ){

	}

	public function add_field_to_entry( \Caldera_Forms_Entry_Field $field ){

	}
}