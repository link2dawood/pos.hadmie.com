(function(window, document, $) {
    'use strict';

    var html5QrCode = null;
    var targetSelector = null;

    function status(message, type) {
        $('#product_camera_scan_status')
            .removeClass('alert-info alert-success alert-danger alert-warning')
            .addClass('alert-' + (type || 'info'))
            .text(message);
    }

    function supportedFormats() {
        if (typeof Html5QrcodeSupportedFormats === 'undefined') return [];
        // Restricted to the formats our app actually generates — fewer formats = faster decoding.
        return [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
        ];
    }

    function stopScan() {
        if (!html5QrCode) return Promise.resolve();
        return html5QrCode.stop()
            .catch(function() { return null; })
            .then(function() {
                try {
                    var c = html5QrCode.clear();
                    if (c && typeof c.catch === 'function') return c.catch(function() { return null; });
                } catch (e) {}
                return null;
            })
            .then(function() { html5QrCode = null; });
    }

    function startScan() {
        if (typeof Html5Qrcode === 'undefined' || !window.isSecureContext ||
            !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            status('Camera scanning is unavailable on this device or connection.', 'warning');
            return;
        }

        status('Requesting camera permission...', 'info');
        html5QrCode = new Html5Qrcode('product_camera_reader');
        var config = {
            fps: 20,
            qrbox: { width: 220, height: 220 },
            videoConstraints: {
                facingMode: 'environment',
                advanced: [{ focusMode: 'continuous' }]
            }
        };
        var formats = supportedFormats();
        if (formats.length) config.formatsToSupport = formats;

        html5QrCode
            .start(
                { facingMode: 'environment' },
                config,
                function(decodedText) {
                    status('Code detected: ' + decodedText, 'success');
                    if (targetSelector) {
                        var $input = $(targetSelector);
                        if ($input.length) {
                            $input.val(decodedText).trigger('change').trigger('input');
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Scanned: ' + decodedText);
                            }
                        }
                    }
                    stopScan().then(function() {
                        $('#product_camera_scan_modal').modal('hide');
                    });
                }
            )
            .then(function() { status('Camera ready. Hold the code steady inside the frame.', 'success'); })
            .catch(function(err) { status('Camera access failed: ' + err, 'danger'); });
    }

    $(document).on('click', '.js-camera-scan-btn', function() {
        targetSelector = $(this).data('input-target');
        $('#product_camera_scan_modal').modal('show');
    });

    $(document).on('shown.bs.modal', '#product_camera_scan_modal', function() {
        startScan();
    });

    $(document).on('hidden.bs.modal', '#product_camera_scan_modal', function() {
        stopScan().then(function() {
            status('Waiting for camera permission.', 'info');
        });
    });
})(window, document, jQuery);
