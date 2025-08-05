jQuery(document).ready(function($) {
    // Function to update the watermark preview
    function updateWatermarkPreview() {
        var watermarkId = $('input[name="simple_paste_watermark_id"]').val();
        var watermarkSize = $('input[name="simple_paste_watermark_size"]').val();
        var watermarkOpacity = $('input[name="simple_paste_watermark_opacity"]').val();
        var watermarkPosition = $('select[name="simple_paste_watermark_position"]').val();
        var watermarkOverlay = $('.watermark-overlay');
        var currentWatermarkImage = $('.current-watermark-image');

        if (watermarkId && currentWatermarkImage.length && currentWatermarkImage.attr('src')) {
            var imageUrl = currentWatermarkImage.attr('src');
            watermarkOverlay.attr('src', imageUrl);
            watermarkOverlay.show();

            // Apply size
            watermarkOverlay.css({
                'width': watermarkSize + '%',
                'height': 'auto',
                'object-fit': 'contain'
            });

            // Apply opacity
            watermarkOverlay.css('opacity', watermarkOpacity / 100);

            // Apply position
            watermarkOverlay.css({
                'top': 'auto',
                'bottom': 'auto',
                'left': 'auto',
                'right': 'auto',
                'transform': 'none'
            });

            switch (watermarkPosition) {
                case 'top-left':
                    watermarkOverlay.css({ 'top': '0', 'left': '0' });
                    break;
                case 'top-center':
                    watermarkOverlay.css({ 'top': '0', 'left': '50%', 'transform': 'translateX(-50%)' });
                    break;
                case 'top-right':
                    watermarkOverlay.css({ 'top': '0', 'right': '0' });
                    break;
                case 'middle-left':
                    watermarkOverlay.css({ 'top': '50%', 'left': '0', 'transform': 'translateY(-50%)' });
                    break;
                case 'middle-center':
                    watermarkOverlay.css({ 'top': '50%', 'left': '50%', 'transform': 'translate(-50%, -50%)' });
                    break;
                case 'middle-right':
                    watermarkOverlay.css({ 'top': '50%', 'right': '0', 'transform': 'translateY(-50%)' });
                    break;
                case 'bottom-left':
                    watermarkOverlay.css({ 'bottom': '0', 'left': '0' });
                    break;
                case 'bottom-center':
                    watermarkOverlay.css({ 'bottom': '0', 'left': '50%', 'transform': 'translateX(-50%)' });
                    break;
                case 'bottom-right':
                default:
                    watermarkOverlay.css({ 'bottom': '0', 'right': '0' });
                    break;
            }
        } else {
            watermarkOverlay.hide();
        }
    }

    // Event listener for watermark upload button
    $(document).on('click', '.upload-watermark-button', function(e) {
        e.preventDefault();

        var button = $(this);
        var frame = wp.media({
            title: 'Select or Upload Watermark',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var wrapper = button.closest('.image-uploader-wrapper');
            wrapper.find('input[name="simple_paste_watermark_id"]').val(attachment.id);
            wrapper.find('.image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; max-height: 100%;" class="current-watermark-image">');
            updateWatermarkPreview();
        });

        frame.open();
    });

    // Event listeners for other watermark settings
    $('input[name="simple_paste_watermark_size"], input[name="simple_paste_watermark_opacity"], select[name="simple_paste_watermark_position"]').on('input change', updateWatermarkPreview);

    // Range slider synchronization
    $(document).on('input', '.range-field-wrapper input[type="range"]', function() {
        $(this).next('input[type="number"]').val($(this).val());
    });

    $(document).on('input', '.range-field-wrapper input[type="number"]', function() {
        $(this).prev('input[type="range"]').val($(this).val());
    });

    // Initial preview update on page load
    updateWatermarkPreview();
});
