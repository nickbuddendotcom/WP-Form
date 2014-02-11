### Lightweight API for creating/validating forms in WordPress.

#### Index

* Usage
* Hooks
* WP_Form
* WP_Form Validator
* Contributing

### Usage

To register a form, hook into `register_forms` with a function to register your forms.

```

    // Hook into 'register_forms'
    add_action('register_forms', 'register_my_forms');

    function register_my_forms() {

      // Register a form
     register_form('foo', array(
       'ajax'          => '1',
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
    }

```php

Now that you've registered your form, you can print it with the get_form() function:

```
    <?php echo get_form('foo'); ?>
```php

To validate your form, hook into your validation function. Ajax is handled automatically if you set ajax => 1 when you called register_form.

```
    // AJAX for logged in users wp_ajax_[FORM_NAME]
    add_action('wp_ajax_foo', 'validate_foo');

    // AJAX for not logged in users wp_ajax_nopriv_[FORM_NAME]
    add_action('wp_ajax_nopriv_foo', 'validate_foo');

    // No AJAX fallback
    add_action('init', 'validate_foo');

    function validate_foo() {
      // Contents of this function are shown in the next code block
    }

```php

Your validation function should create a new isntance of WP_Form_Validator, and there are a few basic functions you'll want to call.

```

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

```php