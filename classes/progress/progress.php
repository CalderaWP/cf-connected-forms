<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 1/4/17
 * Time: 7:12 PM
 */

namespace calderawp\conform\progress;


abstract class progress implements \JsonSerializable, \calderawp\conform\progress {

	protected $form;

	/**
	 * @var step
	 */
	protected $current_step;

	protected $completed;

	protected $steps;

	public function jsonSerialize() {
		return $this->toArray();
	}

	public function toArray(){
		return array(
			'connected_form_id' => $this->form[ 'ID' ],
			'current_step' => $this->get_current_step(),
			'completed' => $this->is_completed(),
			'steps' => $this->steps
		);
	}

	public function get_current_step(){
		return $this->current_step->get_form_id();
	}

	public function is_completed(){
		$this->completed;
	}

	public function complete(){
		return $this->completed;
	}

	public function get_step( $form_id ){
		if( isset( $this->steps[ $form_id ] ) ){
			return $this->steps[ $form_id ];
		}

		return false;

	}

	public function add_step( step $step ){
		$this->steps[ $step->get_form_id() ] = $step;
		return true; //maybe return $this;
	}




}