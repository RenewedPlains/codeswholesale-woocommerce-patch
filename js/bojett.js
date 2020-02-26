jQuery(document).ready(function($){
    var mediaUploader;
    $('#upload_image_button').click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            }, multiple: false });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#background_image').val(attachment.url);
        });
        mediaUploader.open();
    });

    $('.margin_switch input').on('change', function() {
        if($(this).is(':checked')) {
        var margin = $(this).val();
        if( margin == 'a' || margin == '' ) {
            $('.margin_val').fadeOut(function() {
                var current_currency = $('select[name="main_currency"]').val();
                $('.margin_val').html(current_currency).fadeIn();
            });
        } else {
            $('.margin_val').fadeOut(function() {
                $('.margin_val').html('%').fadeIn();
            });
        }
        }
    });
    $('select[name="main_currency"]').on('change', function() {
        var margin = $('.margin_switch input:checked').val();
        if(margin == 'a') {
            $('.margin_val').fadeOut(function() {
                var current_currency = $('select[name="main_currency"]').val();
                $('.margin_val').html(current_currency).fadeIn();
            });
        }
    });
    $('select[name="main_currency"], .margin_switch input').trigger('change');
});