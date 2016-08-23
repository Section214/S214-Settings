/*global jQuery, document, window, wp, CodeMirror, s214_settings_vars*/
jQuery(document).ready(function ($) {
    'use strict';

    // Setup color picker
    if ($('.s214-color-picker').length) {
        $('.s214-color-picker').wpColorPicker();
    }

    // Setup select2
    if ($('.s214-select2').length) {
        $('.s214-select2').select2();
    }

    // Setup CodeMirror
    if ($('.s214-html').length) {
        $('.s214-html').each(function (index, elem) {
            CodeMirror.fromTextArea(elem, {
                lineNumbers: true,
                mode: 'text/html',
                showCursorWhenSelecting: true
            });
        });
    }

    // Setup tooltips
    $('.s214-help-tip').tooltip({
        content: function () {
            return $(this).prop('title');
        },
        position: {
            my: 'center top',
            at: 'center bottom+10',
            collision: 'flipfit'
        },
        hide: {
            duration: 200
        },
        show: {
            duration: 200
        }
    });

    // Setup uploaders
    if ($('.' + s214_settings_vars.func + '_settings_upload_button').length) {
        var file_frame;

        $('body').on('click', '.' + s214_settings_vars.func + '_settings_upload_button', function (e) {
            e.preventDefault();

            var button = $(this);

            window.formfield = $(this).parent().prev();

            // If the media frame already exists, reopen it
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame
            wp.media.frames.file_frame = wp.media({
                frame: 'post',
                state: 'insert',
                title: button.data('uploader_title'),
                button: {
                    text: button.data('uploader_button_text')
                },
                multiple: false
            });

            file_frame = wp.media.frames.file_frame;

            file_frame.on('menu:render:default', function (view) {
                // Store our views in an object
                var views = {};

                // Unset default menu items
                view.unset('library-separator');
                view.unset('gallery');
                view.unset('featured-image');
                view.unset('embed');

                // Initialize the views in our object
                view.set(views);
            });

            // Run a callback on select
            file_frame.on('insert', function () {
                var selection = file_frame.state().get('selection');

                selection.each(function (attachment) {
                    attachment = attachment.toJSON();
                    window.formfield.val(attachment.url);
                });
            });

            // Open the modal
            file_frame.open();
        });

        window.formfield = '';
    }
});
