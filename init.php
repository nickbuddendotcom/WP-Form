<?php
/*
 * WP_Form Init
 * Version: 1.0
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
 * If these classes haven't already been included elsewhere,
 * initialize the WP_Form classes and actions
 */
if(!class_exists('WP_Form') && !class_exists('WP_Form_Builder') && !class_exists('WP_Form_Validator')) {

  /**
   * Required Classes
   */
  require_once( 'classes/class-wp-form.php' );
  require_once( 'classes/class-wp-form-builder.php' );
  require_once( 'classes/class-wp-form-validator.php' );

  /**
   * We need to hook into init for admin, and wp for frontend. Admin
   * must be init so forms are available to AJAX callbacks, and frontend
   * must be wp so that $post variables are available when building our
   * forms
   */
  if(is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
    add_action('init', function() { do_action('register_forms'); });
  } else {
    add_action('wp', function() { do_action('register_forms'); });
  }

  /**
   * Enqueue the form script for handling AJAX $_POST's
   * @return void
   * @todo   we don't need this script if non of our forms are ajax-ified. Check that.
   */
  if(!function_exists('wp_form_enqueue_scripts')) {

    function wp_form_enqueue_scripts() {

      // Default Styles
      wp_enqueue_style(
        'wp-form',
        get_template_directory_uri()  . "/WP_Form/assets/css/wp-form.css",
        '',
        '1.0'
      );

      // Select2
      wp_enqueue_script(
        'select2',
        get_template_directory_uri()  . "/WP_Form/assets/js/select2/select2.js",
        array('jquery'),
        '3.4.5',
        true
      );

      // Script
      wp_enqueue_script(
        'wp_form',
        get_template_directory_uri()  . "/WP_Form/assets/js/wp-form.js",
        array('jquery', 'jquery-ui-datepicker', 'select2', 'json2'),
        '1.0',
        true
      );
      wp_localize_script( 'wp_form', 'WP_Form_Ajax', array('ajaxurl' => admin_url( 'admin-ajax.php' )));

    }

    // Hook it
    add_action('wp_enqueue_scripts', 'wp_form_enqueue_scripts');
  }

  /**
   * Adds a helper function for getting a form's HTML
   * @param  string $form_name name of the form you'd like to get
   * @return string            HTML of form
   */
  if(!function_exists('get_wp_form')) {
    function get_wp_form( $slug ) {
      global $wp_forms;

      if(!$wp_forms[$slug]) {
        return false;
      }

      $wp_form_builder = new WP_Form_Builder($slug);
      return $wp_form_builder->build();
    }
  }

}