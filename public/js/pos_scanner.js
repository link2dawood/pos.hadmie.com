(function(window, document, $) {
    'use strict';

    function scanInput() {
        return $('#scan_product_code');
    }

    function scannerStatus(message, type) {
        var status = $('#pos_camera_scan_status');
        if (!status.length) {
            return;
        }

        status
            .removeClass('alert-info alert-success alert-danger alert-warning')
            .addClass('alert-' + (type || 'info'))
            .text(message);
    }

    function focusScanInput() {
        if ($('#pos_camera_scan_modal').hasClass('in')) {
            return;
        }

        if (scanInput().length && !scanInput().prop('disabled')) {
            scanInput().focus().select();
        }
    }

    function currentLocationId() {
        return $('#location_id').val();
    }

    function focusLocationSelector() {
        if ($('#select_location_id').length && !$('#select_location_id').prop('disabled')) {
            $('#select_location_id').focus();
            return;
        }

        focusScanInput();
    }

    function sharedScanLookup(code, sourceLabel) {
        var trimmedCode = $.trim(code || '');
        if (!trimmedCode.length) {
            focusScanInput();
            return;
        }

        if (!currentLocationId()) {
            toastr.error('Select a business location before scanning products.');
            focusLocationSelector();
            return;
        }

        var cartAction = null;
        var cartError = null;

        $.ajax({
            method: 'POST',
            url: '/sells/pos/scan-lookup',
            dataType: 'json',
            data: {
                _token: $('input[name="_token"]').first().val(),
                code: trimmedCode,
                location_id: $('#location_id').val(),
                customer_id: $('#customer_id').val() || '',
                price_group: $('#price_group').val() || $('#default_price_group').val() || '',
            },
            success: function(result) {
                if (result.success) {
                    var addedToCart = pos_product_row(result.variation_id, null, null, result.quantity || 1, {
                        forceIncrementExisting: true,
                        focusSelector: '#scan_product_code',
                        suppressErrorToast: true,
                        onResult: function(operationResult) {
                            cartAction = operationResult.action || null;
                            cartError = operationResult.msg || null;
                        },
                    });

                    if (addedToCart) {
                        if (cartAction === 'incremented') {
                            toastr.success((result.product_name || 'Item') + ' quantity increased.');
                        } else {
                            toastr.success(result.msg || ('Added from ' + sourceLabel + '.'));
                        }
                        scanInput().val('');
                    } else {
                        toastr.error(cartError || 'Unable to add that scanned item to the cart.');
                    }
                } else {
                    toastr.error(result.msg || 'No product matched that scan.');
                    scanInput().select();
                }

                focusScanInput();
            },
            error: function(xhr) {
                var responseMessage =
                    (xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) ||
                    'Unable to process that scan right now.';
                toastr.error(responseMessage);
                focusScanInput();
            },
        });
    }

    $(document).ready(function() {
        var html5QrCode = null;
        var scannerAttached = false;

        function attachHardwareScanner() {
            if (scannerAttached || typeof onScan === 'undefined' || !scanInput().length) {
                return;
            }

            onScan.attachTo(document, {
                suffixKeyCodes: [13],
                reactToPaste: true,
                minLength: 2,
                onScan: function(scannedCode) {
                    if ($('#weighing_scale_modal').hasClass('in') || $('#pos_camera_scan_modal').hasClass('in')) {
                        return;
                    }

                    if (!scanInput().length) {
                        return;
                    }

                    sharedScanLookup(scannedCode, 'scanner');
                },
            });

            scannerAttached = true;
        }

        function detachHardwareScanner() {
            if (!scannerAttached || typeof onScan === 'undefined') {
                return;
            }

            onScan.detachFrom(document);
            scannerAttached = false;
        }

        function stopCameraScan() {
            if (!html5QrCode) {
                return Promise.resolve();
            }

            return html5QrCode
                .stop()
                .catch(function() {
                    return null;
                })
                .then(function() {
                    return html5QrCode.clear().catch(function() {
                        return null;
                    });
                })
                .then(function() {
                    html5QrCode = null;
                });
        }

        function startCameraScan() {
            if (
                typeof Html5Qrcode === 'undefined' ||
                !window.isSecureContext ||
                !navigator.mediaDevices ||
                !navigator.mediaDevices.getUserMedia
            ) {
                scannerStatus('Camera scanning is unavailable on this device or connection.', 'warning');
                return;
            }

            scannerStatus('Requesting camera permission...', 'info');
            html5QrCode = new Html5Qrcode('pos_camera_reader');

            html5QrCode
                .start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: { width: 220, height: 220 },
                    },
                    function(decodedText) {
                        scannerStatus('Code detected. Adding product...', 'success');
                        stopCameraScan().then(function() {
                            $('#pos_camera_scan_modal').modal('hide');
                            sharedScanLookup(decodedText, 'camera');
                        });
                    }
                )
                .then(function() {
                    scannerStatus('Camera ready. Hold the code steady inside the frame.', 'success');
                })
                .catch(function(error) {
                    scannerStatus('Camera access failed: ' + error, 'danger');
                });
        }

        $(document).on('keydown', '#scan_product_code', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sharedScanLookup($(this).val(), 'scanner');
            }
        });

        $(document).on('show.bs.modal', '#weighing_scale_modal', function() {
            detachHardwareScanner();
        });

        $(document).on('hidden.bs.modal', '#weighing_scale_modal', function() {
            attachHardwareScanner();
            focusScanInput();
        });

        $(document).on('shown.bs.modal', '#pos_camera_scan_modal', function() {
            detachHardwareScanner();
            startCameraScan();
        });

        $(document).on('hidden.bs.modal', '#pos_camera_scan_modal', function() {
            stopCameraScan().then(function() {
                attachHardwareScanner();
                scannerStatus('Waiting for camera permission.', 'info');
                focusScanInput();
            });
        });

        attachHardwareScanner();
        focusScanInput();
    });
})(window, document, jQuery);
