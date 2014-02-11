<?php
/*
 * Plugin Name: WP Form
 * Version: 1.0
 * Plugin URI: http://www.nickbudden.com/wp-form-wordpress-form-builder-plugin
 * Description: WordPress Form Builder and Validator Classes.
 * Author: Nick Budden
 * Author URI: http://www.nickbudden.com/
 * Requires at least: 3.0
 * Tested up to: 3.7.1
 *
 * @package WordPress
 * @author Nick Budden
 * @since 1.0.0
 * @todo localization
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_Form_Version', '1.0.0' );

/**
 * Require our form builder and validator classes
 */
require_once( 'classes/class-wp-form.php' );
require_once( 'classes/class-wp-form-validator.php' );

/**
 * We need to hook into init for admin, and wp for frontend. Admin
 * must be init so forms are available to AJAX callbacks, and frontend
 * must be wp so that $post variables are available when building our
 * forms
 */
if(is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
  add_action('init', function() {
    do_action('register_forms');
  });
} else {
  add_action('wp', function() {
    do_action('register_forms');
  });
}

if(!function_exists('wp_form_enqueue_scripts')) {

/**
 * Enqueue the form script for handling AJAX $_POST's
 * @return void
 * @todo   we don't need this script if non of our forms are ajax-ified. Check that.
 */
  function wp_form_enqueue_scripts() {

    wp_enqueue_script(
      'wp_form',
      trailingslashit(plugin_dir_url(  __FILE__ ))  . "assets/js/wp_form.js",
      array('jquery', 'json2'),
      '1.0',
      true
    );

    wp_localize_script( 'wp_form', 'WP_Form_Ajax', array('ajaxurl' => admin_url( 'admin-ajax.php' )));

  }

  add_action('wp_enqueue_scripts', 'wp_form_enqueue_scripts');

}

if(!function_exists('register_form')) {

  /**
   * Function for registering a new form in your theme's functions.php,
   * or in a plugin.
   *
   * Example:
   *
   * register_form('YOUR_FORM_NAME', array(
   *   'ajax'          => 1,     // 1 for AJAX, 0 for not
   *   'wrap_tag'      => '',      // tag for wrapping each form field, default: div
   *   'wrap_class'    => '',      // class for wrapping each form feild
   *   'wrap_styles'   => '',      // inline styles applied to each wrap class, if you're into that
   *   'add_honeypoy'  => true,    // Whether to add a honeypot field to the form, default: true
   *   'fields'        => array(   // defines your form fields
   *     'field_name'  => array(
   *       'type'        => '',      // field type (text, email, textarea, submit, etc.) default: text
   *       'wrap_tag'    => '',      // overwrites the wrap_tag set above
   *       'wrap_class'  => '',      // overwrites the wrap_class set above
   *       'wrap_style'  => '',      // overwrites the wrap_style set above
   *       'wrap_id'     => '',      // an id for the wrapper around the field
   *       'label'       => '',      // field's label
   *       'placeholder' => '',      // field's placeholder
   *       'class'       => '',      // class for input itself, not wrapper
   *       'id'          => '',      // id for input itself, not wrapper
   *       'autofocus'   => false,   // true/false, default: false
   *       'validation'     => array(   // what validations do you want to perform on this field?
   *         'required'     => true,    // if this field is required
   *         'email'        => true,    // if this should be an eamil
   *         'min_length'   => '',      // the minimum length for input
   *         'max_length'   => '',      // the maximum length for the input
   *         'number'       => true     // field should be a number
   *       ),
   *       'messages'       => array(   // custom error messages for the above validations
   *         'required'     => 'You should fill this out' // a custom error message
   *       )
   *     ),
   *     'fieldset'    => array(
   *       'name'       => '',     // name of fieldset
   *       'class'      => '',     // class for fieldset
   *       'id'         => '',     // id for fieldset
   *       'form'       => '',     // what form does this belong to
   *       'disabled'   => false,  // whether this fieldset should be disabled
   *       'fields'     => array(
   *         ...
   *       )
   *     )
   *   )
   * ));
   *
   *
   * @param  string $name form's name
   * @param  array  $form array of form's attributes, example above.
   * @return void
   */
  function register_form( $name, $form ) {
    global $wp_forms;

    if(!$wp_forms) {
      $wp_forms = array();
    }

    $wp_forms[$name] = $form;
  }
}

if(!function_exists('get_form')) {

  /**
   * Returns the HTML for a form
   * @param  string $form_name name of the form you'd like to get
   * @return string            HTML of form
   */
  function get_form( $form_name ) {
    global $wp_forms;

    if(!$wp_forms[$form_name]) {
      return false;
    }

    $form = new WP_Form($form_name, $wp_forms[$form_name]);
    return $form->build();
  }
}