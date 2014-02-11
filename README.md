# WP Form

WP Form is a lightweight API for creating and validating WordPress forms. All methods handle AJAX out of the box with admin-ajax, and all methods include fallbacks.

Check out the documentation below, or read a tutorial: [creating a login form](http://nickbudden.com/wp-form-wordpress-form-builder-plugin).

***

## Usage

To register a form, hook into `register_forms` with a function to register your forms.

```php
// Hook into 'register_forms'
add_action('register_forms', 'register_my_forms');
```

Inside of your registration function, you can call ```register_form``` once for each form you want to register.

```php
function register_my_forms() {

  // Register a form
 register_form('foo', array(
   'ajax'          => 1,
   'fields'        => array(   // defines your form fields
     'field_name'  => array(
       'type'        => 'text',
       'label'       => __('Username',''),
       'placeholder' => __('Username',''),
       'autofocus'   => true,
       'validation'     => array(
         'required'     => true,
         'min_length'   => 10
       ),
       'messages'       => array(
         'required'     => __('Please enter your username', '')
       )
     )
   )
 ));

// repeat the above code to register more forms...

}
```

Now that you've registered your form, you can print it with the get_form() function:

```php
  <?php echo get_form('foo'); ?>
```

To validate your form, hook into your validation function. Ajax is handled automatically if you set ajax => 1 when you called register_form.

```php
// AJAX for logged in users wp_ajax_[FORM_NAME]
add_action('wp_ajax_foo', 'validate_foo');

// AJAX for not logged in users wp_ajax_nopriv_[FORM_NAME]
add_action('wp_ajax_nopriv_foo', 'validate_foo');

// No AJAX fallback
add_action('init', 'validate_foo');

function validate_foo() {
  // Contents of this function are shown in the next code block
}
```

Your validation function should create a new isntance of WP_Form_Validator, and there are a few basic functions you'll want to call.

```php
function validate_foo() {

  // In case the plugin hasn't been deactivated
  if(!class_exists('WP_Form_Validator')) {
    return;
  }

  // Instantiate WP_Form_Validator with your form's name
  $validator = new WP_Form_Validator('foo');

  // Running $validator->valid() does a few things. If the form's invalid it will
  // automatically reprint the form, including validation errors
  // = It checks whether the form was submitted
  // = It verifies the form's nonce
  // = It checks all validations you added in register_form
  if(!$validator->valid()) {
    return;
  }

  if($foo === $bar) {
    // You can set custom validation errors like this. If you set your
    $validator->set_error('field_name', 'your error message');
  }

  // You can set messages to appear at the top of the form like this
  $validator->set_message('your message text here', 'message_class');

  // If you set custom error messages, you should run valid() again
  // after you've set all your messages
  if(!$validator->valid()) {
    return;
  }

  // When you're finished validating the form, respond() will
  // handle common actions you might perform at the end of submitting
  // the form. See respond() docs for more details.
  $validator->respond('redirect', get_bloginfo('url'));

}
```

***

## Basic Functions

###### register_form($name, $form)

You should call this function from inside a ```register_forms``` hook. Give the name of the form followed by an array of the form's attributes.

```php
register_form('YOUR_FORM_NAME', array(
 'ajax'          => 1,     // 1 for AJAX, 0 for not
 'wrap_tag'      => '',      // tag for wrapping each form field, default: div
 'wrap_class'    => '',      // class for wrapping each form feild
 'wrap_styles'   => '',      // inline styles applied to each wrap class, if you're into that
 'add_honeypoy'  => true,    // Whether to add a honeypot field to the form, default: true
 'fields'        => array(   // defines your form fields
   'field_name'  => array(
     'type'        => '',      // field type (text, email, textarea, submit, etc.) default: text
     'wrap_tag'    => '',      // overwrites the wrap_tag set above
     'wrap_class'  => '',      // overwrites the wrap_class set above
     'wrap_style'  => '',      // overwrites the wrap_style set above
     'wrap_id'     => '',      // an id for the wrapper around the field
     'label'       => '',      // field's label
     'placeholder' => '',      // field's placeholder
     'class'       => '',      // class for input itself, not wrapper
     'id'          => '',      // id for input itself, not wrapper
     'autofocus'   => false,   // true/false, default: false
     'validation'     => array(   // what validations do you want to perform on this field?
       'required'     => true,    // if this field is required
       'email'        => true,    // if this should be an eamil
       'min_length'   => '',      // the minimum length for input
       'max_length'   => '',      // the maximum length for the input
       'number'       => true     // field should be a number
     ),
     'messages'       => array(   // custom error messages for the above validations
       'required'     => __('Your Custom Error Message','') // a custom error message
     )
   ),
   'fieldset'    => array(
     'name'       => '',     // name of fieldset
     'class'      => '',     // class for fieldset
     'id'         => '',     // id for fieldset
     'form'       => '',     // what form does this belong to
     'disabled'   => false,  // whether this fieldset should be disabled
     'fields'     => array()
   )
 )
));
```

###### get_form($name)

This function returns the HTML for the form you created with ```register_form()```.

```<?php echo get_form('YOUR_FORM_NAME'); ?>```

***

***

## Validating Your Forms

To validate your form, you'll need instantiate ```WP_Form_Validator($name)``` with the name of your form. This should be done after hooking into init, wp_ajax_[FORM_NAME], or wp_ajax_nopriv_[FORM_NAME].

###### is_valid()

Check's if the form is valid. If the form is invalid, it will automatically reprint the form with errors. This method does a few things:

* Checks that the form was actually submitted
* Checks the form's nonce
* Checks against built-in validations

If you've already set custom error messages with ```set_error()```, this method will also check for those.

###### get_value($field)

Returns the $_POST'd or $_GET'd value of the field.

###### get_values()

Returns an array of all $_POST'd or $_GET'd values from your form. It only returns values that were set as fields.

###### respond($type, $args)

Handles common actions you might want to take after having validating a form. Right now there are three responses, but you're welcome to add more:

Redirect:

Redirects the user to the given url

`$validator->respond('redirect', get_bloginfo('url'));`

Refresh:

Refreshes the current page. This is useful if you've done something in your validation function like log a user in.

`$validator->respond('refresh');`

Message:

This will hide all of the form fields, and display the message. This is useful for showing a success message.

`$validator->respond('message', array('message text', 'message_class'));`

***

## Questions

Open a GitHub issue, or email me directly at [http://nickbudden.com/contact](http://nickbudden.com/contact)