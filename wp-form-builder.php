<?php

/*
	partially based on https://github.com/joshcanhelp/php-form-builder
*/

class WP_Form {

	// Stores all form inputs
	protected $inputs = array();

	// Let people define their own input types
	protected $custom_inputs = array();

	// Stores all form attributes
	protected $form = array();

	public function __construct($name, $action = '', $args = array()) {

		$args = array_merge(array(
      'action' 				=> ($action) ? $action : "",
      'method' 				=> 'POST',
      'enctype' 			=> 'application/x-www-form-urlencoded',
      'add_nonce' 		=> false,
      'add_honeypot' 	=> true
    ), $args);

    $this->set_attr($args);
    $this->name = $name;
	}

	private function get_value($field) {
		$field = ($this->form['method'] === 'POST') ? $_POST[$field] : $_GET[$field];
		return ($field) ? $field : false;
	}

	// TODO: combine these with a properly self calling function....

	public function set_attr($key, $value = '') {
		if(is_array($key)) {
			foreach($key as $single_key => $single_value) {
				$this->set_one_attr($single_key, $single_value);
			}
		} else {
			$this->set_one_attr($key, $value);
		}
	}

	public function set_one_attr($key, $val) {

    switch ($key) :

      case 'method':
      	$val = strtoupper($val);
        if (!in_array( $val, array('POST', 'GET')))
        	return false;
        break;

      case 'enctype':
        if (!in_array($val, array('application/x-www-form-urlencoded', 'multipart/form-data')))
        	return false;
        break;

      case 'class':
      	if(is_array($val))
      		$val = implode(" ", $val);
      	break;

      case 'novalidate':
      case 'add_honeypot':
        if (!is_bool($val))
        	return false;
        break;

    endswitch;

    $this->form[$key] = $val;
	}

	/**
	 * Possible args:
	 * 'type' => 'input'
	 * 'wrap_tag' => 'div'
	 * 'wrap_class' => '' | array
	 * 'wrap_id' => ''
	 * 'wrap_style' => '' | array
	 * Wrap_id also can take an array, but one should never need to give an element
	 * multiple ids.
	 * @param string|array $name
	 * @param array  $args 
	 * @param string $type
	 */
	public function add_field($name, $args = array(), $type = '') {
		$args['type'] 	= ($type) ? $type : 'input';
		$value 					= $this->get_value($name);

		if($value && $type !== 'password') {
			$args['value'] = esc_attr($value);
		}

		$this->inputs[$name] = $args;
	}

	public function input($name, $args = array()) {
		$this->add_field($name, $args, 'input');
	}

	public function button($name, $args = array()) {
		$this->add_field($name, $args, 'button');
	}

	public function hidden($name, $args = array()) {
		$this->add_field($name, $args, 'hidden');
	}

	public function password($name, $args = array()) {
		$this->add_field($name, $args, 'password');
	}

	public function email($name, $args = array()) {
		$this->add_field($name, $args, 'email');
	}

	// TODO: HTML5'S DEFAULT IMPLEMENTATION OF NUMBERS DOESN'T
	// ALLOW FOR THINGS LIKE DECIMALS FOR FORMATTING. NEED TO PASS
	// A PATTERN ATTRIBUTE TO HANDLE THIS, SO, THAT PATTERN ATTRIBUTE
	// IS ON THE TODO LIST
	public function number($name, $args = array()) {
		$this->add_field($name, $args, 'number');
	}

	// Good for mobile (at least iPhone). Displays a special keyboard.
	public function tel($name, $args = array()) {
		$this->add_field($name, $args, 'tel');
	}

	public function select($name, $args = array()) {
		$this->add_field($name, $args, 'select');
	}

	public function radio($name, $args = array()) {
		$this->add_field($name, $args, 'radio');
	}

	public function textarea($name, $args = array()) {
		$this->add_field($name, $args, 'textarea');
	}

	public function submit($name = '', $args = array()) {
		$this->add_field($name, $args, 'submit');
	}

	public function build() {

		$output = '<form method="' . $this->form['method'] . '"';

		foreach(array('enctype', 'action', 'id', 'class') as $key) {
	    if (isset($this->form[$key])) {
	    	$output .= ' ' . $key . '="' . $this->form[$key] .'"';
	    }
		}

    if ($this->form['novalidate']) {
    	$output .= ' novalidate';
    }

    $output .= '>';

		// Make nonce just happen.
		// TODO: Add support for defining your own nonce if you wanna...
		$output .= wp_nonce_field( $this->name . '_action', $this->name, true, false );

		// So we can easily check if our form was posted.
		// check w/ add_action('init', function() { if($_POST['wp_form'] === 'this_forms_name') ... })
		$output .= '<input type="hidden" name="wp_form" value="' . $this->name . '" />';

		$output .= $this->messages();

		foreach($this->inputs as $name => $args) {
			$output .= $this->input_markup($name, $args);
		}

		// TODO: this should use a less generic name to avoid conflicts.
		// When I change it, I'll have to change the reference to it in
		// the validator as well
		if($this->form['add_honeypot']) {
			$output .= '<input type="text" name="hp" style="display:none" value="" />';
		}

		$output .= "</form>";

		return $output;
	}

	private function messages() {
		global $wp_form;

		if(!$wp_form) {
			$wp_form = array();
		}

		$possible_attrs = array(
			'id',
			'class',
			'style'
		);

		if($wp_form[$this->name]['messages']) {

			foreach($wp_form[$this->name]['messages'] as $message) {
				$output .= '<div';

				foreach($possible_attrs as $key) {
			    if (isset($message[$key])) {

			    	if(is_array($message[$key])) {
			    		$message[$key] = implode(" ", $message[$key]);
			    	}

			    	$output .= ' ' . $key . '="' . $message[$key] .'"';
			    }
				}

				$output .= '>';

				$output .= esc_attr($message['message']);

				$output .= '</div>';

			}
		}

		return $output;
	}

	private function input_markup($name, $args) {

		global $wp_form_errors;

		if(!$wp_form_errors) {
			$wp_form_errors = array();
		}

		$args = array_merge(array(
			'type' 			=> 'text',
			'wrap_tag' 	=> 'div'
		), $args);

		// Open the field wrapper
		$output = '<' . $args['wrap_tag'];

		// Set field wrapper attributes
		foreach(array('wrap_class', 'wrap_id', 'wrap_style') as $key) {
	    if(isset($args[$key])) {

	    	if(is_array($args[$key]))
	    		$args[$key] = implode(" ", $args[$key]);

	    	$output .= ' ' . substr($key, strpos($key, "_") + 1) . '="' . $args[$key] .'"';

	    }
		}

		$output .= ">";

		if($args['type'] === 'select') {
			$output .= $this->select_html($name, $args);
		} elseif($args['type'] === 'checkbox') {
			$output .= $this->checkbox_html($name, $args);
		} elseif($args['type'] === 'radio') {
			$output .= $this->radio_html($name, $args);
		} elseif($args['type'] === 'button') {
			$output .= $this->button_html($name, $args);
		} elseif($args['type'] === 'textarea') {
			$output .= $this->textarea_html($name, $args);
		} elseif($args['type'] === 'submit') {
			$output .= $this->submit_html($args);
		} elseif(in_array($args['type'], $this->custom_inputs)) {
			$output .= $this->custom_html($name, $args['type'], $args);
		} else {
			$output .= $this->input_html($name, $args);
		}

		if( $wp_form_errors[$this->name][$name] ) {
			$output .= '<small class="wp-form-error">' . esc_attr($wp_form_errors[$this->name][$name]) . '</small>';
		} else {
			// Empty hidden errors, so you can populate them via AJAX if needed
			$output .= '<small style="display:none" class="wp-form-error"></small>';
		}

		// Print a label UNLESS this is a button
		if($args['label'] && $args['type'] !== 'button') {
			$output .= '<label for="' . esc_attr($name) . '">' . esc_attr($args['label']) . '</label>';
		}

		// Close the field wrapper
		$output .= "</" . $args['wrap_tag'] . '>';

		return $output;
	}

	private function select_html($args = array()) {
	  // Input markup
		$output .= '<select name="' . $name . '"';

		$possible_input_attrs = array(
			'name',
			'id',
			'autofocusNew',
			'required',
			'class',
			'size'
		);

		foreach($possible_input_attrs as $key) {
	    if (isset($args[$key])) {
	    	if(is_array($args[$key]))
	    		$args[$key] = implode(" ", $args[$key]);
	    	$output .= ' ' . $key . '="' . $args[$key] .'"';
	    }
		}

		$output .= ">";

		foreach($args['options'] as $key => $val) {
			$output .= '<option value="' . $key . '">' . $val . '</option>';
		}

		$output .= "</select>";
		return $output;
	}

	private function checkbox_html($name, $args = array()) {
		// TODO: this
	}

	private function radio_html($name, $args = array()) {

		foreach($args['options'] as $value => $label) {
			$output .= '<input id="radio_' . esc_attr($value) . '" type="radio" value="' . esc_attr($value) . '" name="' . esc_attr($name) . '"';

			if($value === $args['value']) {
				$output .= ' checked="checked"';
			}

			$output .= '>';
    	$output .= '<label for="radio_' . esc_attr($value) . '">' . esc_attr($label) . '</label>';
		}

		return $output;
	}

	private function textarea_html($name, $args = array()) {
		$output .= '<textarea name="' . $name . '" type="' . $args['type'] . '"';

		$possible_input_attrs = array(
			'name',
			'id',
			'placeholder',
			'class',
			'min',
			'max',
			'style',
			'autofocus',
			'required'
		);

		foreach($possible_input_attrs as $key) {
	    if (isset($args[$key])) {
	    	if(is_array($args[$key]))
	    		$args[$key] = implode(" ", $args[$key]);
	    	$output .= ' ' . $key . '="' . $args[$key] .'"';
	    }
		}

		$output .= ">";

		if(isset($args['value'])) {
			$output .= esc_textarea($args['value']);
		}

		$output .= '</textarea>';

		return $output;
	}

	private function input_html($name, $args = array()) {
		$output .= '<input name="' . $name . '" type="' . $args['type'] . '"';

		$possible_input_attrs = array(
			'name',
			'id',
			'value',
			'placeholder',
			'class',
			'min',
			'max',
			'style',
			'autofocus',
			'required'
		);

		foreach($possible_input_attrs as $key) {
	    if (isset($args[$key])) {
	    	if(is_array($args[$key]))
	    		$args[$key] = implode(" ", $args[$key]);
	    	$output .= ' ' . $key . '="' . $args[$key] .'"';
	    }
		}

		$output .= " />";

		return $output;
	}

	private function button_html($name, $args = array()) {
		$output .= '<button name="' . $name . '" type="' . $args['type'] . '"';

		$possible_input_attrs = array(
			'name',
			'id',
			'placeholder',
			'class',
			'min',
			'max',
			'style',
			'autofocus',
			'required'
		);

		foreach($possible_input_attrs as $key) {
	    if (isset($args[$key])) {
	    	if(is_array($args[$key]))
	    		$args[$key] = implode(" ", $args[$key]);
	    	$output .= ' ' . $key . '="' . $args[$key] .'"';
	    }
		}

		$output .= ">";

		if ($args['value']) {
			$output .= $args['value'];
		} elseif ($args['label']) {
			$output .= $args['label'];
		} else {
			$output .= $args['type'];
		}

		$output .= '</button>';

		return $output;
	}

	private function submit_html($args = array()) {

		if(!$args['value']) {
			$args['value'] = __('Submit', 'WP_Form');
		}

		$output .= '<input type="submit"';

		$possible_input_attrs = array(
			'name',
			'id',
			'style',
			'value',
			'class'
		);

		foreach($possible_input_attrs as $key) {
	    if (isset($args[$key])) {
	    	if(is_array($args[$key]))
	    		$args[$key] = implode(" ", $args[$key]);
	    	$output .= ' ' . $key . '="' . $args[$key] .'"';
	    }
		}

		$output .= " />";

		return $output;
	}

	// Overide this in child classes
	protected function custom_html($type, $args) {}

}

?>