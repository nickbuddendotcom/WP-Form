<?php

class WP_Form {

  /**
   * Plugin slug for translations
   * @var string
   */
  private $plugin_slug = 'WP_Form';

  /**
   * Count HTML entries, since these don't have slugs
   *
   * @var integer
   */
  private $html_count = 0;

  /**
   * Stores fields, default attributes, and the form
   * itself in instance variables
   * @param string $name name of this form
   * @param array  $form see register_form() function
   */
  public function __construct( $slug, $form ) {
    global $wp_forms;

    $this->slug = $slug;

    if(!is_array($wp_forms))
      $wp_forms = array();

    $wp_forms[$slug] = $form;
    $wp_forms[$slug]['all_fields_attrs'] = array();
    $wp_forms[$slug]['fields'] = array();
    $wp_forms[$slug]['fieldsets'] = array();

    // We want to pull 'fields' out of the form array, and store it in its own array
    // We also want to pull out any 'wrap_' keys, because these are common attributes
    // set on all fields and not on the form itself.
    foreach($wp_forms[$slug] as $key => $value) {
      if(strpos($key, 'wrap_') !== false) {
        $wp_forms[$slug]['all_fields_attrs'][$key] = $value;
        unset($wp_forms[$slug][$key]);
      }
    }

    // Everything left over are form attributes. Merge them with some defaults.
    $wp_forms[$slug] = wp_parse_args($wp_forms[$slug], array(
      'action'            => $_SERVER['REQUEST_URI'],
      'method'            => 'POST',
      'enctype'           => 'application/x-www-form-urlencoded',
      'add_honeypot'      => true,
      'data-wp-form-ajax' => (true == $form['ajax']) ? '1' : '0'
    ));

  }

  /**
   * Adds a field to the list that we'll build
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   * @param void
   */
  public function add_field($slug, $args, $type = 'text') {
    global $wp_forms;

    $args['type'] = $type;

    $value = (strtoupper($wp_forms[$this->slug]['method']) === 'GET') ? $_GET[$slug] : $_POST[$slug];
    if(isset($value)) {
      $args['value'] = $value;
    }

    $wp_forms[$this->slug]['fields'][$slug] = $args;
  }

  /**
   * Wrapper for adding a text field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function text($slug, $args) {
    $this->add_field($slug, $args);
  }

  /**
   * Wrapper for adding an email field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function email($slug, $args) {
    $this->add_field($slug, $args, 'email');
  }

  /**
   * Wrapper for adding a tel field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function tel($slug, $args) {
    $this->add_field($slug, $args, 'tel');
  }

  /**
   * Wrapper for adding a number field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function number($slug, $args) {
    $this->add_field($slug, $args, 'number');
  }

  /**
   * Wrapper for adding a textarea field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function textarea($slug, $args) {
    $this->add_field($slug, $args, 'textarea');
  }

  /**
   * Wrapper for adding a hidden field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function hidden($slug, $args) {
    $this->add_field($slug, $args, 'hidden');
  }

  /**
   * Wrapper for adding a checkbox field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function checkbox($slug, $args) {
    if(!$args['value']) $args['value'] = 1;
    if(!$args['checked']) unset($args['checked']);
    $this->add_field($slug, $args, 'checkbox');
  }

  /**
   * Wrapper for adding a radio button field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function radio($slug, $args) {
    $this->add_field($slug, $args, 'radio');
  }

  /**
   * Wrapper for adding a date field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function date($slug, $args) {
    $this->add_field($slug, $args, 'date');
  }

  /**
   * Wrapper for adding a select field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function select($slug, $args) {
    $this->add_field($slug, $args, 'select');
  }

  /**
   * Wrapper for adding a submit field
   *
   * @param string $slug Field's slug
   * @param array  $args Arguments for field
   *
   * @param void
   */
  public function submit($slug, $args) {
    $this->add_field($slug, $args, 'submit');
  }

  /**
   * Adds arbitrary HTML...invents a slug so
   * that it fits the format of other fields
   *
   * @param array  $content HTML content
   *
   * @return void
   */
  public function html($content) {
    $this->html_count++;
    $this->add_field('html_'.$this->html_count, array('content' => $content), 'html');
  }

  /**
   * Stores a fieldset and its attributes. It is only necessary
   * to declare a fieldset explicitly in this way if you're
   * adding attributes to it.
   *
   * @param  string $slug Slug for fieldset
   * @param  array  $args Attributes for fieldset
   * @return void
   */
  public function fieldset($slug, $args) {
    global $wp_forms;

    $wp_forms[$this->slug]['fieldsets'][$slug] = $args;
  }

}