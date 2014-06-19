(function ($) {

    $(document).ready(function () {
        $('[data-field=colorpicker]').each(function() {
            $(this).wpColorPicker();
        });
        $('[data-field=media]').each(function() {
            handleImageUploads('#' + $(this).attr('id'));
        });
        $('[data-clear]').click(function() {
            $('#' + $(this).attr('data-clear')).val('');
        });
    });

    /**
     * handleImageUploads - taps into WPs built-in media library overlay and lets the user select a custom image
     *
     * @params string css_identifier - trigger for overlay
     * @return void
     */
    
    function handleImageUploads(css_identifier) {

        var _custom_media = true;
        var _orig_send_attachment = wp.media.editor.send.attachment;

        $(css_identifier).click(function(e) {

            var send_attachment_bkp = wp.media.editor.send.attachment;
            var button = $(this);
            var id = button.attr('id').replace('_button', '');
            _custom_media = true;

            wp.media.editor.send.attachment = function(props, attachment) {

                if (_custom_media) {
                    $("#"+id).val(attachment.url);
                } else {
                    return _orig_send_attachment.apply(this, [props, attachment]);
                };
            }

            wp.media.editor.open(button);
            return false;
        });

        $('.add_media').on('click', function () {
            _custom_media = false;
        });     
    }

}(jQuery));