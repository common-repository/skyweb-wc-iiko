(function ($) {
    $(document).ready(function () {

        /**
         * Check street field on blur (focus away from the field).
         */
        let street = $('#billing_address_1');

        if (street.length) {
            street.on('blur', function () { // change keydown input paste

                let selectedStreetName = $(this).val(),
                    selectedStreet = $('#iiko_streets_datalist option[value="' + selectedStreetName + '"]'), // obj
                    selectedStreetId = selectedStreet.attr('data-streetid');

                // Set selected street ID to hidden field.
                if (selectedStreetId) {
                    $('#billing_iiko_street_id').val(selectedStreetId);
                } else {
                    $('#billing_iiko_street_id').val('');
                }

                // Basic validation.
                if (selectedStreet.length === 0) {
                    $('#street_validation_text').removeClass('hidden');
                } else {
                    $('#street_validation_text').addClass('hidden');
                }

            });
        }

    }); /* $(document).ready */

})(jQuery);
