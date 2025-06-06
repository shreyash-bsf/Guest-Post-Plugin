/**
 * Guest Post Plugin JavaScript
 */
jQuery(document).ready(function($) {
    $('#guest-post-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'submit_guest_post');
        
        // Clear previous messages
        $('#form-response').removeClass('success error').html('');
        
        // Show loading indicator
        $('#form-response').html('Processing your submission...').show();
        
        $.ajax({
            type: 'POST',
            url: guest_post_ajax.ajax_url,
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    $('#form-response').addClass('success').html(response.data);
                    $('#guest-post-form')[0].reset();
                    if (typeof tinyMCE !== 'undefined') {
                        tinyMCE.activeEditor.setContent('');
                    }
                    // Reset reCAPTCHA if it exists
                    if (typeof grecaptcha !== 'undefined' && $('.g-recaptcha').length) {
                        grecaptcha.reset();
                    }
                } else {
                    $('#form-response').addClass('error').html(response.data);
                    // Reset reCAPTCHA if it exists
                    if (typeof grecaptcha !== 'undefined' && $('.g-recaptcha').length) {
                        grecaptcha.reset();
                    }
                }
            },
            error: function() {
                $('#form-response').addClass('error').html('An error occurred. Please try again later.');
                // Reset reCAPTCHA if it exists
                if (typeof grecaptcha !== 'undefined' && $('.g-recaptcha').length) {
                    grecaptcha.reset();
                }
            }
        });
    });
});