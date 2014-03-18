<?php

/* TODO: I've replaced all the $wp_form with $wp_forms but I dont know that it'll actually work */

class WP_Form_Validator {

  /**
   * Plugin slug for translations
   * @var string
   */
  private $plugin_slug = 'WP_Form';

  /**
   * We only want to run register validations
   * once, this boolean tells us if we've already
   * run them for this instance or not.
   * @var boolean
   */
  private $already_checked = false;

  /**
   * Stores form validation errors
   * @var array
   */
  private $errors = array();

  /**
   * Stores form messages
   * @var array
   */
  private $messages = array();

  /**
   * Stores a form's $_POST'd or $_GET'd data
   * when the form is submitted
   * @var
   */
  private $data = array();

  /**
   * Stores the form name, form itself, and an ajax boolean
   * into instance variables
   * @param string $form_name The name of this form
   */
  public function __construct($form_name) {
    global $wp_forms;

    // if($form_name === 'edit_team') {
      // echo json_encode('wp_form_validator register ' . $form_name);
    // }

    if(!isset($wp_forms[$form_name])) {
      $this->reprint_form();
    }

    $this->name  = $form_name;
    $this->form  = $wp_forms[$form_name];

    if(defined('DOING_AJAX') && DOING_AJAX) {
      $this->is_ajax = true;
      parse_str($_POST["data"], $this->data);
    } else {
      $this->is_ajax = false;
      $this->data = (strtoupper($this->form['method']) === 'GET') ? $_GET : $_POST;
    }

  }

  /**
   * Get the $_POST'd or $_GET'd value for a given field
   * @param  string $field Name of field
   * @return mixed         Field value or false
   */
  public function get_value($field) {
    return ($this->data[$field]) ? trim($this->data[$field]) : false;
  }

  /**
   * Return all $_POST'd/$_GET'd as an array. Only
   * returns $_POST'd/$_GET'd values that the user
   * registered to the form fields.
   * @return array Array of $_POST'd/$_GET'd values
   */
  public function get_values() {
    $return = array();

    foreach($this->form['fields'] as $name => $field) {

      if($name === 'fieldset') {
        foreach($field['fields'] as $single_name => $single_field) {
          $return[$single_name] = $this->get_value($single_name);
        }
      } else {
        $return[$name] = $this->get_value($name);
      }
    }

    return $return;
  }

  /**
   * Check if the current form passed all validations.
   * @return boolean True if valid, false if some field has an error
   */
  public function valid() {
    global $wp_forms;

    // We only need to run user-registered validations once, so we
    // tie this to the already_checked variable
    if(!$this->already_checked) {
      $this->already_checked  = true;
      $submitted              = $this->get_value('wp_form');
      $nonce                  = $this->get_value($this->name);
      $honeypot               = $this->get_value('wp_form_hp');

      if(!$submitted || $submitted !== $this->name || $honeypot !== false) {
        return false;
      }

      if(!wp_verify_nonce($nonce, $this->name . "_action")) {
        die(__('Security Check, Nonce Check Failed.', $this->plugin_slug));
      }

      $this->validate_fields();
    }

    // Users can add their own errors after we've checked built-in errors,
    // so we need to double check $this->errors every time valid is called
    if(empty($wp_forms['errors'][$this->name])) {
      return true;
    } else {
      $this->reprint_form();
      return false;
    }
  }

  /**
   * Basically a wrapp for validate_field(). In a function so that
   * we can call it recursively on itself if we hit a fieldset.
   * @return void
   */
  private function validate_fields() {
    foreach($this->form['fields'] as $name => $field) {

      if($name === 'fieldset') {
        foreach($field['fields'] as $single_name => $single_field) {
          $this->validate_field($single_name, $single_field);
        }
      } else {
        $this->validate_field($name, $field);
      }
    }
  }

  /**
   * Check if a field passed all of its user-registered
   * validations.
   * @param  string $name  Name of field
   * @param  array  $field Array of field's array attributes
   * @return void          An error will be set if invalid, no return.
   */
  private function validate_field($name, $field) {

    // Required should always be run first, no matter what order it was added
    $ordered_validations = array();
    if($field['validation']){
      $ordered_validations = array_merge(array_flip(array('required')), $field['validation']);
    }

    foreach($ordered_validations as $validation => $value) {
      if(method_exists($this, $validation)) {

        $message = ($field['messages'][$validation]) ? $field['messages'][$validation] : false;
        $valid = call_user_func_array(array($this, $validation), array($name, $message, $value));

        // If we hit any errors at all, set this. We won't
        // continue validing this field
        if(!$valid) {
          break;
        }
      }
    }
  }

  /**
   * Validation: A required field has been filled out.
   * @param  string $name    Field name
   * @param  string   $message Custom error message if invalid
   * @return boolean         true if valid
   */
  protected function required($name, $message = '') {
    $value = $this->get_value($name);

    if(isset($value) && strlen($value) > 0) {
      return true;
    }

    $message = ($message) ? $message : __('This field is required', $this->plugin_slug);
    $this->set_error($name, $message);

    return false;
  }

  /**
   * Validation: is a valid email
   * @param  string   $name    Field name
   * @param  string   $message Custom error message if invalid
   * @return boolean           true if valid
   */
  protected function email($name, $message) {
    $value = $this->get_value($name);

    if(is_email($value)) {
      return true;
    }

    $message = ($message) ? $message : __('Please enter a valid email', $this->plugin_slug);
    $this->set_error($name, $message);

    return false;
  }

  /**
   * A minimum date for date fields.
   *
   * @param string   $name    Field name
   * @param string   $message Custom error message if invalid
   * @param string   $value   Minimum date value
   *
   * @return boolean          true if valid
   */
  protected function min_date($name, $message, $min) {
    $value  = $this->get_value($name);
    return (strtotime($value) > strtotime($min)) ? true : false;
  }

  /**
   * A maximum date for date fields.
   *
   * @param string   $name    Field name
   * @param string   $message Custom error message if invalid
   * @param string   $value   Maximum date value
   *
   * @return boolean          true if valid
   */
  protected function max_date($name, $message, $max) {
    $value  = $this->get_value($name);
    return (strtotime($value) < strtotime($max)) ? true : false;
  }

  /**
   * Validation: has at least minimum length
   * @param  string   $name    Field name
   * @param  string   $message Custom error message if invalid
   * @param  int      $length  minimum length
   * @return boolean           true if valid
   */
  protected function min_length($name, $message = '', $length) {
    $value  = $this->get_value($name);
    $length = (int) $length;

    if(strlen($value) > $length) {
      return true;
    }

    $message = ($message) ? $message : sprintf(__('Minimum length: %d characters', $this->plugin_slug), $length);
    $this->set_error($name, $message);

    return false;
  }

  /**
   * Validation: has no more than maximum length
   * @param  string   $name    Field name
   * @param  string   $message Custom error message if invalid
   * @param  int      $length  maximum length
   * @return boolean           true if valid
   */
  protected function max_length($name, $message = '', $length) {
    $value  = $this->get_value($name);
    $length = (int) $length;

    if(strlen($value) < $length) {
      return true;
    }

    $message = ($message) ? $message : sprintf(__('Maximum length: %d characters', $this->plugin_slug), $length);
    $this->set_error($name, $message);

    return false;
  }

  /**
   * Validation: input is a number
   * @param  string   $name    Field name
   * @param  string   $message Custom error message if invalid
   * @return boolean           true if valid
   */
  protected function number($name, $message = '') {
    $value = $this->get_value($name);
    $value = preg_replace("/[^0-9]/", "", $value);

    if(strlen($value) > 0) {
      return true;
    }

    $message = ($message) ? $message : __('This must be a number.', $this->plugin_slug);
    $this->set_error($name, $message);

    return false;
  }

  /**
   * When we've finished validating a form, we usually want to trigger one
   * of several common actions (show a success message, redirect the user,
   * refresh the page). This method lets you trigger a reponse directly on
   * the validation object.
   * @param  string $type Type of response. redirect, message, or refresh.
   * @param  mixed  $args Arguments for response
   * @return mixed        Depends which response was selected
   */
  public function respond($type, $args = '') {
    global $wp_forms;

    switch ($type) {
      case 'redirect':
        if($this->is_ajax) {
          echo json_encode(array('respond' => array('redirect' => $args)));
          exit;
        } else {
          die(wp_redirect($args));
        }
      case 'message':
        $args[1] = (is_array($args[1])) ? implode(" ", $args[1]) : $args[1];
        if($this->is_ajax) {
          echo json_encode(array('respond' => array('message' => $args)));
          exit;
        } else {
          if(!$wp_forms) {
            $wp_forms = array();
            if(!$wp_forms['respond_message']) {
              $wp_forms['respond_message'][$this->name] = array();
            }
          }
          $wp_forms['respond_message'][$this->name] = $args;
        }
      case 'refresh':
        // You don't need to refresh if it was posted via server...only via ajax
        if($this->is_ajax) {
          echo json_encode(array('respond' => array('refresh' => true)));
          exit;
        }
    }

    return false;
  }

  public function set_error($name, $message) {
    global $wp_forms;
    $wp_forms['errors'][$this->name][$name] = $message;
  }

  // TODO: so far there's no way to unset a message...
  public function set_message($message, $class = '') {
    $class = (is_array($class)) ? implode(" ", $class) : $class;
    global $wp_forms;
    $wp_forms['messages'][$this->name][$name] = array($message, $class);
  }

  public function reprint_form() {
    global $wp_forms;

    if(empty($wp_forms['errors'][$this->name])) {
      return;
    }

    // If this is AJAX just echo them...
    if($this->is_ajax) {
      echo json_encode( array('errors' => $wp_forms['errors'][$this->name], 'messages' => $wp_forms['messages'][$this->name][$name] ));
      exit;
    }

    if(!$wp_forms) {
      $wp_forms = array();

      if(!$wp_forms['errors']) {
        $wp_forms['errors'] = array();
      }

      if(!$wp_forms['messages']) {
        $wp_forms['messages'] = array();
      }
    }

    // $wp_forms['errors'][$this->name] = $this->errors;
    $wp_forms['messages'][$this->name] = $this->messages;

    return;
  }

}