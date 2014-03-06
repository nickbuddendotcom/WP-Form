<?php

class WP_Form_Builder {

  /**
   * Plugin slug for translations
   * @var string
   */
  private $plugin_slug = 'WP_Form';

  /**
   * Stores the fieldset we're currently adding, empty if none.
   *
   * @var string
   */
  private $open_fieldset;

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
   * Get our form array and put its parts into instance
   * attributes.
   *
   * @param string $slug Form slug
   */
  public function __construct($slug) {
    global $wp_forms;

    $this->slug = $slug;

    $this->fields = $wp_forms[$slug]['fields'];
    unset($wp_forms[$slug]['fields']);

    $this->fieldsets = $wp_forms[$slug]['fieldsets'];
    unset($wp_forms[$slug]['fieldsets']);

    $this->all_fields_attrs = wp_parse_args($wp_forms[$slug]['all_fields_attrs'], $this->all_fields_attrs);
    unset($wp_forms[$slug]['all_fields_attrs']);

    $this->form = $wp_forms[$slug];

  }

  /**
   * Returns the full HTML for our form, including any validation errors.
   * @return string HTML of form
   */
  public function build() {

    $output = $this->open_html_tag('form', $this->form, array('method', 'enctype', 'action', 'id', 'class', 'data-wp-form-ajax'));

    // If we have a respond message, we'll want to return that message and only that
    // message inside the form. Respond Messages are set in WP_Form_Validator.

    // TODO: get messages on construct
    // if($wp_form['respond_message'][$this->form['name']]) {
    //   $message   = $wp_form['respond_message'][$this->form['name']][0];
    //   $class     = $wp_form['respond_message'][$this->form['name']][1];
    //   $output   .= '<div class="' . esc_attr($class) . '">' . esc_attr($message) . '</div>';
    //   $output   .= '</form>';
    //   return $output;
    // }

    // Make nonce just happen.
    $output .= wp_nonce_field( $this->slug . '_action', $this->slug, true, false );

    // Extra field so we can easily check if our form was posted.
    // check with add_action('init', function() { if($_POST['wp_form'] === 'this_forms_name') ... })
    $output .= '<input type="hidden" name="wp_form" value="' . $this->slug . '" />';

    // TODO: double check that this is working how it should...
    // $output .= $this->messages();

    // Build fieldset and fields
    foreach($this->fields as $slug => $args) {

      // If a different fieldset is still open, close it first
      if(isset($this->open_fieldset) && (!isset($args['fieldset']) || $this->open_fieldset !== $args['fieldset'])) {
        $output .= '</fieldset>';
        unset($this->open_fieldset);
      }

      // If we should open a new fieldset
      if(isset($args['fieldset']) && $this->open_fieldset !== $args['fieldset']) {
        $output .= $this->open_html_tag('fieldset', $this->fieldsets[$args['fieldset']], array('disabled','name','form','id','class'));

        if($this->fieldsets[$args['fieldset']] && $this->fieldsets[$args['fieldset']]['legend']) {
          $output .= '<legend>' . esc_attr($this->fieldsets[$args['fieldset']]['legend']) . '</legend>';
        }

        $this->open_fieldset = $args['fieldset'];
      }

      // Markup for field itself
      $output .= $this->field_markup($slug, $args);

    }

    // Close a fieldset we might've left open on the last iteration of the loop
    if(isset($this->open_fieldset)) {
      $output .= '</fieldset>';
      unset($this->open_fieldset);
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
   *
   * @param  string $tag       HTML tag we're rendering (div, ul, li, etc.)
   * @param  array  $attrs     key => value pairs of the tag's attributes
   * @return string            HTML for the opening tag, i.e. <div class='a_class' id='an_id'>
   */
  protected function open_html_tag($tag, $attrs) {

    $output = "<$tag";

    // What we should add as attributes
    $whitelist = array(
      'action',
      'method',
      'enctype',
      'novalidate',
      'data-wp-form-ajax',
      'name',
      'type',
      'id',
      'class',
      'value',
      'placeholder',
      'style',
      'autofocus',
      'required',
      'min',
      'max',
      'checked'
    );

    // Add data attributes to whitelist
    foreach($attrs['data'] as $data => $value) {
      $whitelist[] = 'data-' . $data;
      $attrs['data-' . $data] = $value;
    }

    foreach($whitelist as $key) {

      if(!isset($attrs[$key]) || $attrs[$key] === '') {
        continue;
      }

      if(is_array($attrs[$key])) {
        $attrs[$key] = implode(" ", $attrs[$key]);
      }

      if($key === 'required' || $key === 'disabled' || $key === 'checked') {
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

  /**
   * Builds HTML for messages set in WP_Form_Validator
   * @return string HTML for message
   */
  private function messages() {
    global $wp_forms;

    // TODO: rework messages to use $wp->forms...I don't even need this method, just look it inline...

    // if($wp_forms['messages'][$this->form['name']]) {
    //   foreach($wp_form['messages'][$this->form['name']] as $message) {
    //     $output .= '<div class="' . esc_attr($message[1]) . '">' . esc_attr($message[0]) . '</div>';
    //   }
    // }

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

    global $wp_forms;

    $wrap_tag = (isset($args['wrap_tag'])) ? $args['wrap_tag'] : $this->all_fields_attrs['wrap_tag'];

    // if wrap attributes haven't been set on this field, they fallback to $this->all_fields_attrs
    $wrap_attrs = array(
      'class'    => (isset($args['wrap_class'])) ? $args['wrap_class'] : $this->all_fields_attrs['wrap_class'],
      'id'       => (isset($args['wrap_id'])) ? $args['wrap_id'] : $this->all_fields_attrs['wrap_id'],
      'style'    => (isset($args['wrap_style'])) ? $args['wrap_style'] : $this->all_fields_attrs['wrap_style']
    );

    if($args['type'] !== 'hidden' && $args['type'] !== 'html') {
      $output = $this->open_html_tag($wrap_tag, $wrap_attrs, array('class', 'id', 'style'));
    }

    if($args['label'] && $args['type'] !== 'checkbox') {
      $output .= '<label for="' . esc_attr($name) . '">' . esc_attr($args['label']) . '</label><br />';
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
    if( $wp_forms['errors'][$this->slug][$name] ) {
      $output .= '<div class="wp-form-error">' . esc_attr($wp_forms['errors'][$this->slug][$name]) . '</div>';
    } else {
      // Empty hidden errors let us populate them via AJAX
      $output .= '<div class="wp-form-error" style="display:none"></div>';
    }

    // Close the field wrapper
    if($args['type'] !== 'hidden' && $args['type'] !== 'html') {
      $output .= "</" . $wrap_tag . ">";
    }

    return $output;

  }

  /**
   * Prints arbitrary HTML
   *
   * @return string HTML
   */
  protected function html_html($args = array()) {
    return $args['content'];
  }

  /**
   * Builds HTML for input[type=text], and also for
   * any field without a [field_type]_html() method set.
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  protected function text_html($args = array()) {

    $output = $this->open_html_tag('input', $args);
    return $output;
  }

  /**
   * Builds HTML for input[type=hidden].
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  protected function hidden_html($args = array()) {
    $output = $this->open_html_tag('input', $args);
    return $output;
  }

  /**
   * Utility method to push a class onto $args['class']
   *
   * @param array   $args   Arguments array we want to push onto
   * @param string  $class  Class to push
   *
   * @return Array Array with pushed class
   */
  private function push_class($args, $class) {
    if(!is_array($args['class']) && isset($args['class'])) {
      $args['class'] = explode(',', $args['class']);
    } elseif(!isset($args['class'])) {
      $args['class'] = array();
    }

    array_push($args['class'], $class);
    return $args;
  }

  /**
   * Build a jQuery UI date widget
   *
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  protected function date_html($args = array()) {

    if($args['enhanced']) {
      $args = $this->push_class($args, 'wp-form-date');
      $args['type'] = 'text';
    }

    $output = $this->open_html_tag('input', $args);
    return $output;
  }

  /**
   * Builds HTML for select
   * @param  array $args field attributes
   * @return string      HTML for field
   */
  protected function select_html($args = array()) {

    if($args['enhanced']) {
      $args = $this->push_class($args, 'wp-form-selectize');
    }

    $output = $this->open_html_tag('select', $args, array('name', 'id', 'required', 'class', 'style', 'placeholder'));

    if($args['placeholder']) {
      $output .= '<option></option>';
    }

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

    $for = (isset($args['id'])) ? $args['id'] : $args['name'];

    $output  = '<label for="' . esc_attr($for) . '">';
    $output .= $this->open_html_tag('input', $args);
    $output .= esc_attr($args['label']);
    $output .= '</label>';

    return $output;
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