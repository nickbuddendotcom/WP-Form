# WordPress Form Builder

Build WordPress forms quickly and programatically.

### Getting Started

Copy or clone the two source files into your project, then require them where needed. Require the files in your `functions.php` file if you want WP_Form class available project-wide:

```php
<?php
// functions.php
require_once('path/to/wp-form-builder.php');
require_once('path/to/wp-form-validator.php');
```

Next, create a new instance of WP_Form and add fields to it:

```php
$form = new WP_Form($name = 'form-name', $action = 'form-action', $options = array());

// Arbitrarily add fields
$form->add_field('first-name', array(
  'type' => 'text',
  'label' => 'First Name'
  ), 'input');

// Shorthand functions as you would expect. Inputs default to type text.
$form->input('last-name', array( 'label' => 'Last Name' ));

// The name attribute is optional but useful if you want to check for 
// submissions using $_POST.
$form->submit('submit-the-form');
```

The example above outputs:

```html
<form method="POST" enctype="application/x-www-form-urlencoded" action="form-action">

  <!-- Hidden Fields Omitted --> 

  <div>
    <input name="first-name" type="input" />
    <small style="display:none" class="wp-form-error"></small>
    <label for="first-name">First Name</label>
  </div>

  <div>
    <input name="last-name" type="input" />
    <small style="display:none" class="wp-form-error"></small>
    <label for="last-name">Last Name</label>
  </div>

  <div>
    <input type="submit" name="submit-the-form" value="Submit" />
    <small style="display:none" class="wp-form-error"></small>
  </div>

</form>
```

See the source code for more complete instructions.