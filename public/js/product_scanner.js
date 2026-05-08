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
                        var $modal = $('#product_camera_scan_modal');
                        var scrollAfterHide = function() {
                            if (!targetSelector) return;
                            var $input = $(targetSelector);
                            if (!$input.length) return;
                            // The scanner can be invoked from the page itself OR from inside
                            // another modal (e.g. POS quick-add product). In the modal case,
                            // animating html/body does nothing — the modal-body is the scroll
                            // container. Find the nearest scrollable ancestor and animate that.
                            // Walk up to find the actual scrollable ancestor. Bootstrap 3
                            // scrolls the whole .modal on mobile (modal-body has no
                            // overflow), and .modal-body on desktop only when sized. Pick
                            // whichever ancestor is actually scrollable.
                            var $scroller = null;
                            $input.parents().each(function() {
                                var $p = $(this);
                                var oy = $p.css('overflow-y');
                                if ((oy === 'auto' || oy === 'scroll') && this.scrollHeight > this.clientHeight) {
                                    $scroller = $p;
                                    return false;
                                }
                            });
                            if (!$scroller) {
                                $scroller = $input.closest('.modal');
                            }
                            if (!$scroller || !$scroller.length) {
                                $scroller = $('html, body');
                            }
                            var isWindow = $scroller.is('html, body');
                            var inputTop = $input.offset().top;
                            var scrollerTop = isWindow ? 0 : $scroller.offset().top;
                            var currentScroll = isWindow ? $(window).scrollTop() : $scroller.scrollTop();
                            var target = currentScroll + (inputTop - scrollerTop) - 80;
                            if (target < 0) target = 0;
                            // Animate first; some mobile browsers ignore animate on a
                            // .modal element, so also set scrollTop directly as a fallback.
                            $scroller.stop(true).animate({ scrollTop: target }, 300);
                            setTimeout(function() {
                                if (isWindow) {
                                    window.scrollTo(0, target);
                                } else {
                                    $scroller[0].scrollTop = target;
                                }
                            }, 320);
                            try { $input.focus({ preventScroll: true }); } catch (e) {
                                try { $input.focus(); } catch (e2) {}
                            }
                        };
                        $modal.one('hidden.bs.modal', scrollAfterHide);
                        $modal.modal('hide');
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
