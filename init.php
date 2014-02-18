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
if(
  !class_exists('WP_Form') &&
  !class_exists('WP_Form_Builder') &&
  !class_exists('WP_Form_Validator')
) {

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

  // I don't remember how to add these actions...come back to it...s
  // Leaving off: adding an action for handling forms... should pass an arg for these three...
  // add_action('init', 'handle_test_form');
  // add_action('wp_ajax_test_form', 'handle_test_form');
  // add_action('wp_ajax_nopriv_test_form', 'handle_test_form');

  /**
   * Enqueue the form script for handling AJAX $_POST's
   * @return void
   * @todo   we don't need this script if non of our forms are ajax-ified. Check that.
   */
  if(!function_exists('wp_form_enqueue_scripts')) {
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