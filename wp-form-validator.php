<?php

class WP_Form_Validator {

	public function __construct($name, $method) {
		$method 			= strtoupper($method);
		$this->method = (in_array($method, array('GET', 'POST'))) ? $method : 'POST';
		$this->name 	= $name;
	}

	public function is_submitted() {
		$is_submitted = $this->get_value('wp_form');
		$nonce   			= $this->get_value($this->name);
		$honeypot 		= $this->get_value('hp');

		if(!$is_submitted || $is_submitted !== $this->name || $honeypot !== false) {
			return false;
		}

		// Make nonce just happen.
		// TODO: Add support for defining your own nonce if you wanna...
		if(!wp_verify_nonce($nonce, $this->name . "_action")) {
			die(__('Security Check, Nonce Check Failed.', 'WP_Form_Validator'));
		}

		return true;
	}

	public function required($field, $args = array()) {
		$value = $this->get_value($field);

		if($value) {
			return $value;
		}

		if(!$args['error_msg']) {
			$args['error_msg'] = __('This field is required', 'WP_Form_Validator');
		}

		$this->set_error($field, $args['error_msg']);
		return false;
	}

	public function email($field, $args = array()) {
		$value = $this->get_value($field);

		if(is_email($value)) {
			return $value;
		}

		if(!$args['error_msg']) {
			$args['error_msg'] = __('Please enter a valid email', 'WP_Form_Validator');
		}

		$this->set_error($field, $args['error_msg']);
		return false;
	}

	public function min_length($field, $args = array()) {
		$value = $this->get_value($field);

		if(!$args['length'] || strlen($value) >= (int) $args['length']) {
			return $value;
		}

		if(!$args['error_msg']) {
			$args['error_msg'] = sprintf(__('Minimum length: %d characters', 'WP_Form_Validator'), (int) $args['length']);
		}

		$this->set_error($field, $args['error_msg']);
		return false;
	}

	public function max_length($field, $args = array()) {
		$value = $this->get_value($field);

		if(!$args['length'] || strlen($value) <= (int) $args['length']) {
			return $value;
		}

		if(!$args['error_msg']) {
			$args['error_msg'] = sprintf(__('Maximum length: %d characters', 'WP_Form_Validator'), (int) $args['length']);
		}

		$this->set_error($field, $args['error_msg']);
		return false;
	}

	// TODO: radio buttons don't reprint their $_POST'd value as active...

	public function number($field, $args = array()) {
		$value = $this->get_value($field);
		$value = preg_replace("/[^0-9.]/", "", $value);

		if(strlen($value) > 0) {
			return $value;
		}

		if(!$args['error_msg']) {
			$args['error_msg'] = __('This must be a number', 'WP_Form_Validator');
		}

		$this->set_error($field, $args['error_msg']);
		return false;
	}




	// TODO: use a single global $wp_form variable, not these $wp_form and $wp_form_errors objects
	public function add_message($message, $args = array()) {
		global $wp_form;

		if(!$wp_form) {
			$wp_form = array();
		}

		if(!$wp_form[$this->name]['messages']) {
			$wp_form[$this->name]['messages'] = array();
		}

		$wp_form[$this->name]['messages'][] = $message;
	}

	private function get_value($field) {
		$field = ($this->method === 'POST') ? $_POST[$field] : $_GET[$field];
		return ($field) ? $field : false;
	}

	public function set_error($field, $error_msg = '') {
		global $wp_form_errors;

		if(!$wp_form_errors) {
			$wp_form_errors = array();
		}

		$wp_form_errors[$this->name][$field] = $error_msg;
	}

	// Optionally provide the name of the input you'd like to check errors
	// for. Returns the error or false if there isn't one.
	public function get_errors($field = '') {
		global $wp_form_errors;

		if(!$wp_form_errors) {
			$wp_form_errors = array();
		}

		if($field) {
			return ($wp_form_errors[$this->name][$field]) ? $wp_form_errors[$this->name][$field] : false;
		} else {
			return (!empty($wp_form_errors[$this->name])) ? $wp_form_errors[$this->name] : false;
		}
	}

}

/*
class WP_Form_Validator {

	// A new wp error object
	// protected $errors = new WP_Error();

	public function __construct($name, $method) {
		$method 			= strtoupper($method);
		$this->method = (in_array($method, array('GET', 'POST'))) ? $method : 'POST';
		$this->name 	= $name;
	}

	// You should ALWAYS run this method before you do validations.
	// If nothing else because it does your nonce
	public function is_submitted() {
		$is_submitted = $this->get_value('wp_form');
		$nonce   			= $this->get_value($this->name);
		$honeypot 		= $this->get_value('hp');

		if(!$is_submitted || $is_submitted !== $this->name || $honeypot !== false) {
			return false;
		}

		// Make nonce just happen.
		// TODO: Add support for defining your own nonce if you wanna...
		if(!wp_verify_nonce($nonce, $this->name . "_action")) {
			die(__('Security Check, Nonce Check Failed.', 'WP_Form_Validator'));
		}

		return true;
	}

	public function validate($field, $validations) {

		$has_errors = false;

		if(!isset($field) || !isset($validations)) {
			return false;
		}

		foreach($validations as $validate) {

			// Array's have validation name as first perameter,
			// and custom error msg as second
			if(is_array($validate)) {
				$callback = call_user_func(array($this, $validate[0]), $field, $validate[1]);
			} else {
				$callback = call_user_func(array($this, $validate), $field);
			}

			if(!$callback) {
				$has_errors = true;
			}
		}

		return (!$has_errors) ? $this->get_value($field) : false;
	}

	private function get_value($field) {
		$field = ($this->method === 'POST') ? $_POST[$field] : $_GET[$field];
		return ($field) ? $field : false;
	}

	public function required($field, $error_msg = '') {

		if(is_array($field)) {
			foreach($field as $single_field) {
				$this->required($single_field, $error_msg);
			}
			return;
		}

		$value = $this->get_value($field);

		if(!$error_msg) {
			$error_msg = __('This field is required', 'WP_Form_Validator');
		}

		if(!$value || $value === '') {
			return $this->set_error($field, $error_msg);
		}

		// TODO: some kind of validation for whether ALL passed fields were true
		return true;
	}

	protected function email($field, $error_msg = '') {

		if(!$error_msg) {
			$error_msg = __('Please provide a valid email', 'WP_Form_Validator');
		}

		if(is_array($field)) {
			foreach($field as $single_field) {
				$this->email($single_field, $error_msg);
			}
		}

		$value = $this->get_value($field);

		if(!is_email($value)) {
			return $this->set_error($field, $error_msg);
		}

		return true;
	}

	private function min_length($field, $error_msg = '', $length) {
		die('hit it');
	}

	public function set_error($field, $error_msg = '') {

		global $wp_form_errors;

		if(!$wp_form_errors) {
			$wp_form_errors = array();
		}

		$wp_form_errors[$this->name][$field] = $error_msg;
	}

	// Optionally provide the name of the input you'd like to check errors
	// for. Returns the error or false if there isn't one.
	public function get_errors($field = '') {
		global $wp_form_errors;

		if(!$wp_form_errors) {
			$wp_form_errors = array();
		}

		if($field) {
			return ($wp_form_errors[$this->name][$field]) ? $wp_form_errors[$this->name][$field] : false;
		} else {
			return (!empty($wp_form_errors[$this->name])) ? $wp_form_errors[$this->name] : false;
		}
	}


}