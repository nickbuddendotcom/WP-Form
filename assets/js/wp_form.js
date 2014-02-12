jQuery(document).ready(function($) {

  $('form[data-wp-form-ajax="1"]').submit(function(e) {

    e.preventDefault();

    var $this   = $(this),
        $submit = $this.find('input[type=submit]'),
        resp,
        fieldName,
        $message,
        $error;

    // Prevent multiple submits
    if($this.hasClass('wp-form-is-submitted')) {
      return false;
    } else {
      $this.addClass('wp-form-is-submitted');
      $submit.attr('disabled', 'disabled');
    }

    $.post(
      WP_Form_Ajax.ajaxurl, {
        action  : $this.find('input[name=wp_form]').val(),
        data    : $this.serialize()
      },
      function( response ) {

        resp = JSON.parse( response );

        if(resp.respond) {
          if(resp.respond.refresh) {
            window.location.reload(true);
          } else if(resp.respond.redirect) {
            window.location = resp.respond.redirect;
          } else if(resp.respond.message) {
            $message = $('<div />').addClass(resp.respond.message[1]).text(resp.respond.message[0]).hide();
            $this.find("*").slideUp();
            $this.before( $message );
            $message.slideDown();
          }
          return;
        }

        // Remove our submit blocks
        $this.removeClass('wp-form-is-submitted');
        $submit.removeAttr('disabled');

        if(resp.messages) {
          $.each(resp.messages, function(i, message) {
            $message = $('<div />').addClass(message[1]).text(message[0]).hide();
            $this.before( $message );
            $message.slideDown();
          });
        }

        if(resp.errors) {

          $this.find('input, textarea, select').each(function() {

            fieldName = $(this).attr('name');
            $error    = $(this).next('.wp-form-error');

            if(fieldName in resp.errors) {
              if($error.is(":visible")) {
                // flash the error again if it's already visible
                $error.text(resp.errors[fieldName]).animate({opacity:'0.3'}, 400).animate({opacity:'1'},400);
              } else {
                $error.text(resp.errors[fieldName]).slideDown();
              }
            } else {
              $error.slideUp();
            }

          });

        }

      }
    );

  });
});