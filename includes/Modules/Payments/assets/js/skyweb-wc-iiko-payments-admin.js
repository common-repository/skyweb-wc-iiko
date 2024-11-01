(function ($) {
    $(document).ready(function () {

        if ($('#iikoPage').length) {

            /**
             * Common variables.
             */
            let skywebWCIikoNonce = $('#skyweb_wc_iiko_import_nonce').val(),
                iikoFormButtons = $('.iiko_form_submit'),
                preloader = $('#iikoPreloader'),
                terminal = $('#iikoTerminal');

            // TODO - common functions.
            /**
             * Add a record to the terminal.
             */
            function add_record_to_terminal(text, type = undefined, title = undefined) {

                if (undefined === text) return;

                let messageType = 'error' === type ? 'terminal_error' : 'notice' === type ? 'terminal_notice' : 'terminal_data';

                terminal.prepend($('<p></p>')
                    .attr('class', messageType)
                    .text(text)
                );

                if (undefined !== title) {
                    terminal.prepend($('<p></p>')
                        .attr('class', 'terminal_title')
                        .text(title)
                    );
                }
            }

            /**
             * Add logs to the terminal.
             */
            function add_logs_to_terminal(notices, errors) {

                if (undefined === notices || undefined === errors) return;

                // Output notices.
                if (null !== notices) {

                    $.each(notices, function (index, value) {
                        add_record_to_terminal(value, 'notice');
                    });
                }

                // Output errors.
                if (null !== errors) {

                    $.each(errors, function (index, value) {
                        add_record_to_terminal(value, 'error');
                    });
                }
            }

            /**
             * Show preloader and disable get buttons.
             */
            function request_start() {

                preloader.removeClass('hidden');

                iikoFormButtons.prop('disabled', true);
            }

            /**
             * Hide preloader and enable get buttons.
             */
            function request_finish(status) {

                preloader.addClass('hidden');

                iikoFormButtons.prop('disabled', false);

                // Disable get nomenclature button if the nomenclature wasn't get.
                if (true === status) {
                    $('#getIikoNomenclature').prop('disabled', true);
                }
            }

            /**
             * iiko form handler.
             */
            iikoFormButtons.click(function (e) {

                e.preventDefault();

                request_start();

                // Get iiko payment types.
                if ($(this).attr('name') === 'get_iiko_payment_types') {

                    let organizationId = $('#iikoOrganizations').val();

                    if (organizationId === null || organizationId === undefined || organizationId.length === 0) {

                        add_record_to_terminal('Organization is empty.', 'error');

                    } else {

                        $.ajax({
                            type: 'post',
                            url: skyweb_wc_iiko.ajax_url,
                            data: {
                                action: 'skyweb_ajax_wc_iiko__get_payment_types_ajax',
                                skyweb_wc_iiko_import_nonce: skywebWCIikoNonce,
                                organizationId: organizationId,
                            },

                            success: function (response) {

                                if (response === '0') {

                                    add_record_to_terminal('AJAX error: 0.', 'error');

                                } else {

                                    let responseObj = JSON.parse(response),
                                        notices = responseObj['notices'],
                                        errors = responseObj['errors'],
                                        paymentTypes = responseObj['paymentTypes'];

                                    if (undefined === paymentTypes) {

                                        add_logs_to_terminal(notices, errors);

                                    } else {

                                        // Output payment types info in the terminal.
                                        $.each(paymentTypes, function (index, value) {
                                            add_record_to_terminal(value['name'] + ' - ' + value['id'], 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'PAYMENT TYPES');
                                    }
                                }

                                request_finish(false);
                            },

                            error: function (xhr, textStatus, errorThrown) {
                                add_record_to_terminal(errorThrown ? errorThrown : xhr.status, 'error');
                            }
                        });
                    }
                }
            });

        } /* If is iiko page */

    }); /* $(document).ready */

})(jQuery);
