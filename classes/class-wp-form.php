<?php

class WP_Form {

  /**
   * Plugin slug for translations
   * @var string
   */
  private $plugin_slug = 'WP_Form';

  /**
   * Stores all user-registered form fields
   * @var array
   */
  private $fields = array();

  /**
   * Default values for new fields
   * @var array
   */
  protected $all_fields_attrs = array(
    'wrap_tag'    => 'div',
    'wrap_class'  => null,
    'wrap_id'     => null,
    'wrap_style'  => null
  );

  /**
   * Stores fields, default attributes, and the form
   * itself in instance variables
   * @param string $name name of this form
   * @param array  $form see register_form() function
   */
  public function __construct( $name, $form ) {

    if(!isset($form)) {
      return new WP_Error(
        __('No $form', $this->plugin_slug),
        __('No $form variable passed to get_form().', $this->plugin_slug)
      );
    } elseif(empty($form) || !is_array($form)) {
      return new WP_Error(
        __('Invalid $form', $this->plugin_slug),
        __('Form not registered properly with register_form().', $this->plugin_slug)
      );
    }

    // We want to pull 'fields' out of the form array, and store it in its own array
    // We also want to pull out any 'wrap_' keys, because these are common attributes
    // set on all fields and not on the form itself.
    foreach($form as $key => $value) {
      if($key === 'fields') {
        $this->fields = $value;
        unset($form[$key]);
      } elseif(strpos($key, 'wrap_') !== false) {
        $this->all_fields_attrs[$key] = $value;
        unset($form[$key]);
      }
    }

    // Everything left over are form attributes. Merge them with some defaults.
    $this->form = array_merge(array(
      'name'              => $name,
      'action'            => $_SERVER['REQUEST_URI'],
      'method'            => 'POST',
      'enctype'           => 'application/x-www-form-urlencoded',
      'add_honeypot'      => true,
      'data-wp-form-ajax' => (true == $form['ajax']) ? '1' : '0'
    ), $form);

  }

  /**
   * Returns the full HTML for our form, including any validation errors.
   * @return string HTML of form
   */
  public function build() {
    global $wp_form;

    $output = $this->open_html_tag('form', $this->form, array('method', 'enctype', 'action', 'id', 'class', 'data-wp-form-ajax'));

    // If we have a respond message, we'll want to return that message and only that
    // message inside the form. Respond Messages are set in WP_Form_Validator.
    if($wp_form['respond_message'][$this->form['name']]) {
      $message   = $wp_form['respond_message'][$this->form['name']][0];
      $class     = $wp_form['respond_message'][$this->form['name']][1];
      $output   .= '<div class="' . esc_attr($class) . '">' . esc_attr($message) . '</div>';
      $output   .= '</form>';
      return $output;
    }

    // Make nonce just happen.
    $output .= wp_nonce_field( $this->form['name'] . '_action', $this->form['name'], true, false );

    // Extra field so we can easily check if our form was posted.
    // check with add_action('init', function() { if($_POST['wp_form'] === 'this_forms_name') ... })
    $output .= '<input type="hidden" name="wp_form" value="' . $this->form['name'] . '" />';

    $output .= $this->messages();

    // Build fieldset and fields
    foreach($this->fields as $name => $args) {

      if($name === 'fieldset') {

        $fields = $args['fields'];
        unset($args['fields']);

        $output .= $this->open_html_tag('fieldset', $args, array('disabled','name','form','id','class'));

        foreach($fields as $key => $value) {
          $output .= $this->field_markup($key, $value);
        }

        $output .= '</fieldset>';

      } else {
        $output .= $this->field_markup($name, $args);
      }

    }

    // Boo to spam
    if($this->form['add_honeypot']) {
      $output .= '<input type="text" name="wp_form_hp" style="display:none" value="" />';
    }

    $output .= "</form>";

    return $output;
  }

  /**
   * Opens an HTML tag, inserting any passed attributes.
   * @param  string $tag       HTML tag we're rendering (div, ul, li, etc.)
   * @param  array  $attrs     key => value pairs of the tag's attributes
   * @param  array  $whitelist An array of whitelisted attributes for the opening tag
   * @return string            HTML for the opening tag, i.e. <div class='a_class' id='an_id'>
   * @todo   Are whitelists necessary? Why not let people add <div stop='its_hammer_time'> if they want?
   */
  protected function open_html_tag($tag, $attrs, $whitelist) {

    $output = "<$tag";

    foreach($whitelist as $key) {

      if(!isset($attrs[$key]) || $attrs[$key] === '') {
        continue;
      }

      if(is_array($attrs[$key])) {
        $attrs[$key] = implode(" ", $attrs[$key]);
      }

      if($key === 'required' || $key === 'disabled') {
        if(true == $value) {
          $output .= ' ' . $key;
        }
      } else {
        $output .= ' ' . $key . '="' . $attrs[$key] .'"';
      }

    }

    if($tag === 'form' && $attrs['novalidate']) {
      $output .= ' novalidate';
    }

    $output .= '>';

    return $output;
  }

  // TODO: whitelist of attributes for messages, instead of just hard-coding the class
  /**
   * Builds HTML for messages set in WP_Form_Validator
   * @return string HTML for message
   * @todo   whitelist attributes for opening tag
   */
  private function messages() {
    global $wp_form;

    if($wp_form['messages'][$this->form['name']]) {
      foreach($wp_form['messages'][$this->form['name']] as $message) {
        $output .= '<div class="' . esc_attr($message[1]) . '">' . esc_attr($message[0]) . '</div>';
      }
    }

    return $output;
  }

  /**
   * return the markup for a single field
   * @param  string $name field name we want markup for
   * @param  array  $args attributes for that field
   * @return string       HTML for the field
   * @todo   don't show empty hidden errors if this form isn't ajax-ified
   */
  public function field_markup($name, $args) {

    global $wp_form;

    $wrap_tag = ($args['wrap_tag']) ? $args['wrap_tag'] : $this->all_fields_attrs['wrap_tag'];

    // if wrap attributes haven't been set on this field, they fallback to $this->all_fields_attrs
    $wrap_attrs = array(
      'class'    => ($args['wrap_class']) ? $args['wrap_class'] : $this->all_fields_attrs['wrap_class'],
      'id'       => ($args['wrap_id']) ? $args['wrap_id'] : $this->all_fields_attrs['wrap_id'],
      'id'       => ($args['wrap_style']) ? $args['wrap_style'] : $this->all_fields_attrs['wrap_style']
    );

    $output = $this->open_html_tag($wrap_tag, $wrap_attrs, array('class', 'id', 'style'));

    if($args['label']) {
      $output .= '<label for="' . esc_attr($name) . '">' . esc_attr($args['label']) . '</label>';
    }

    // Get the markup for our field type
    $args['name']   = $name;
    $args['type']   = ($args['type']) ? $args['type'] : 'text';
    $args['value']  = ($args['value']) ? $args['value'] : $_POST[$name];
    $html_callback  = $args['type'] . "_html";

    if(method_exists($this, $html_callback)) {
      $output .= call_user_func(array($this, $html_callback), $args);
    } else {
      // text_html() is the default if we can't find an $args['type']_html method
      $output .= $this->text_html($args);
    }

    // Print Errors
    if( $wp_form['errors'][$this->form['name']][$name] ) {
      $output .= '<div class="wp-form-error">' . esc_attr($wp_form['errors'][$this->form['name']][$name]) . '</div>';
    } else {
      // Empty hidden errors let us populate them via AJAX
      $output .= '<div class="wp-form-error" style="display:none"></div>';
    }

    // Close the field wrapper
    $output .= "</" . $wrap_tag . ">";

    return $output;

  }

  /**
   * Builds HTML for input[type=text], and also for
   * any field without a [field_type]_html() method set.
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  protected function text_html($args = array()) {

    $whitelist = array('name','type','id','value','placeholder','class','style','autofocus','required');

    if($args['type'] === 'number') {
      $whitelist = array_push($whitelist, 'min', 'max');
    }

    $output = $this->open_html_tag('input', $args, $whitelist);
    return $output;
  }

  /**
   * Builds HTML for select
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  protected function select_html($args = array()) {
    $output = $this->open_html_tag('select', $args, array('name', 'id', 'required', 'class'));

    foreach($args['options'] as $key => $val) {
      $output .= '<option value="' . $key . '">' . $val . '</option>';
    }

    $output .= "</select>";
    return $output;
  }

  /**
   * Builds HTML for Builds HTML for select
   * @param  array $args field attributes
   * @return string      HTML for field
   * @todo   do this...
   */
  private function checkbox_html($args = array()) {
  }

  /**
   * Builds HTML for Builds HTML for radio buttons
   * @param  array $args field attributes
   * @return string      HTML for field
   * @todo   do this...
   */
  private function radio_html($args = array()) {
    // foreach($args['options'] as $value => $label) {
    //   $output .= '<input id="radio_' . esc_attr($value) . '" type="radio" value="' . esc_attr($value) . '" name="' . esc_attr($args['name']) . '"';

    //   if($value === $args['value']) {
    //     $output .= ' checked="checked"';
    //   }

    //   $output .= '>';
    //   $output .= '<label for="radio_' . esc_attr($value) . '">' . esc_attr($label) . '</label>';
    // }

    // return $output;
  }

  /**
   * Builds HTML for Builds HTML for textareas
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  private function textarea_html($args = array()) {
    $output = $this->open_html_tag('textarea', $args, array('name','id','placeholder', 'class', 'style', 'autofocus','required'));

    if(isset($args['value'])) {
      $output .= esc_textarea($args['value']);
    }

    $output .= '</textarea>';
    return $output;
  }

  /**
   * Builds HTML for Builds HTML for buttons
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  private function button_html($args = array()) {
    $output  = $this->open_html_tag('button', $args, array('name','id','class','style'));
    $output .= esc_attr($args['value']);
    $output .='</button>';
    return $output;
  }

  /**
   * Builds HTML for Builds HTML for input[type=submit]
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  private function submit_html($args = array()) {
    $args['value'] = ($args['value']) ? $args['value'] : __('Submit', $this->plugin_string);
    $output = $this->open_html_tag('input', $args, array('name','type','id','class','style','value'));
    return $output;
  }

}