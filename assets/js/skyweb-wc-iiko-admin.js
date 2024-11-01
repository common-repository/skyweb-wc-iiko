(function ($) {
    $(document).ready(function () {

        if ($('#iikoPage').length) {

            /**
             * Common variables.
             */
            let skywebWCIikoNonce = $('#skyweb_wc_iiko_import_nonce').val(),
                iikoFormButtons = $('.iiko_form_submit'),
                iikoTerminalsWrap = $('#iikoTerminalsWrap'),
                iikoTerminals = $('#iikoTerminals'),
                iikoCitiesWrap = $('#iikoCitiesWrap'),
                iikoCities = $('#iikoCities'),
                iikoNomenclatureInfoWrap = $('#iikoNomenclatureInfoWrap'),
                iikoNomenclatureImportWrap = $('#iikoNomenclatureImportWrap'),
                iikoGroups = $('#iikoGroups'),
                iikoNomenclatureImportedWrap = $('#iikoNomenclatureImportedWrap'),
                iikoTempMessage = $('#iikoTempMessage'),
                preloader = $('#iikoPreloader'),
                terminal = $('#iikoTerminal'),
                isGetIikoNomenclatureDisable = true;

            /**
             * Clear terminal.
             */
            $('#iikoTerminalClear').click(function (e) {

                e.preventDefault();

                terminal.empty();
            });

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
             * Clear organization info.
             */
            function clear_organization_info() {

                // Hide terminals block.
                iikoTerminalsWrap.addClass('hidden');

                // Hide nomenclature general info block.
                iikoNomenclatureInfoWrap.addClass('hidden');

                // Hide groups block.
                iikoNomenclatureImportWrap.addClass('hidden');

                // Hide imported nomenclature info block.
                iikoNomenclatureImportedWrap.addClass('hidden');

                iikoTerminals.empty();
                iikoGroups.empty();

                // Empty common and added nomenclature info.
                $('.iiko_nomenclature_value').each(function () {
                    $(this).text('');
                });

                iikoTempMessage.removeClass('hidden');
            }

            /**
             * Build categories tree.
             */
            function groups_tree(groups, prefix = '') {

                $.each(groups, function (index, value) {

                    if (null === value.parentGroup) {
                        prefix = '';
                    }

                    iikoGroups.append($('<option></option>')
                        .attr('value', value.id)
                        .attr('class', true === value.isDeleted ? 'deleted' : '')
                        .attr('class', true === value.isGroupModifier ? 'modifier' : '')
                        .text(value.name)
                        .text(prefix + value.name)
                    );

                    if (null !== value.childGroups) {

                        prefix = prefix + '\u2014';

                        groups_tree(value.childGroups, prefix);
                    }
                });

            }

            /**
             * iiko form handler.
             */
            iikoFormButtons.click(function (e) {

                e.preventDefault();

                request_start();

                // Get iiko organizations.
                if ($(this).attr('name') === 'get_iiko_organizations') {

                    clear_organization_info();

                    $.ajax({
                        type: 'post',
                        url: skyweb_wc_iiko.ajax_url,
                        data: {
                            action: 'skyweb_ajax_wc_iiko__get_organizations_ajax',
                            skyweb_wc_iiko_import_nonce: skywebWCIikoNonce,
                        },

                        success: function (response) {

                            if (response === '0') {

                                add_record_to_terminal('AJAX error: 0.', 'error');

                            } else {

                                let responseObj = JSON.parse(response),
                                    notices = responseObj['notices'],
                                    errors = responseObj['errors'],
                                    organizations = responseObj['organizations'];

                                if (undefined === organizations) {

                                    add_logs_to_terminal(notices, errors);

                                } else {

                                    let iikoOrganizations = $('#iikoOrganizations');

                                    iikoOrganizations.empty();

                                    // Fill in organizations select and output organizations info in the terminal.
                                    $.each(organizations, function (index, value) {

                                        iikoOrganizations.append($('<option></option>')
                                            .attr('value', value.id)
                                            .text(value.name)
                                        );

                                        add_record_to_terminal(value.name + ' - ' + value.id, 'data');
                                    });
                                    add_record_to_terminal('', 'data', 'ORGANIZATIONS');

                                    $('#iikoOrganizationsWrap').removeClass('hidden');

                                    // Unblock get nomenclature button.
                                    isGetIikoNomenclatureDisable = false;
                                }
                            }

                            request_finish(isGetIikoNomenclatureDisable);
                        },

                        error: function (xhr, textStatus, errorThrown) {
                            add_record_to_terminal(errorThrown ? errorThrown : xhr.status, 'error');
                        }
                    });
                }

                // Get iiko terminals.
                if ($(this).attr('name') === 'get_iiko_terminals') {

                    let organizationId = $('#iikoOrganizations').val();

                    if (organizationId === null || organizationId === undefined || organizationId.length === 0) {

                        add_record_to_terminal('Organization is empty.', 'error');

                    } else {

                        iikoTerminals.empty();
                        iikoTerminalsWrap.addClass('hidden');

                        $.ajax({
                            type: 'post',
                            url: skyweb_wc_iiko.ajax_url,
                            data: {
                                action: 'skyweb_ajax_wc_iiko__get_terminals_ajax',
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
                                        terminals = responseObj['terminalGroups'];

                                    if (undefined === terminals) {

                                        add_logs_to_terminal(notices, errors);

                                    } else {

                                        iikoTerminals.empty();

                                        // Fill in terminals select and output terminals info in the terminal.
                                        $.each(terminals, function (index, value) {

                                            if (value.organizationId === organizationId) {

                                                $.each(value.items, function (index, value) {

                                                    iikoTerminals.append($('<option></option>')
                                                        .attr('value', value.id)
                                                        .text(value.name)
                                                    );

                                                    add_record_to_terminal(value.name + ' - ' + value.id, 'data');
                                                });
                                            }
                                        });
                                        add_record_to_terminal('', 'data', 'TERMINALS');

                                        iikoTerminalsWrap.removeClass('hidden');
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

                // Get iiko nomenclature.
                if ($(this).attr('name') === 'get_iiko_nomenclature') {

                    let organizationName = $('#iikoOrganizations option:selected').text(),
                        organizationId = $('#iikoOrganizations').val(),
                        terminalName = $('#iikoTerminals option:selected').text(),
                        terminalId = $('#iikoTerminals').val();

                    if (organizationId === null || organizationId === undefined || organizationId.length === 0) {

                        add_record_to_terminal('Organization is empty.', 'error');

                    } else {

                        $.ajax({
                            type: 'post',
                            url: skyweb_wc_iiko.ajax_url,
                            data: {
                                action: 'skyweb_ajax_wc_iiko__get_nomenclature_ajax',
                                skyweb_wc_iiko_import_nonce: skywebWCIikoNonce,
                                organizationName: organizationName,
                                organizationId: organizationId,
                                terminalName: terminalName,
                                terminalId: terminalId,
                            },

                            success: function (response) {

                                if (response === '0') {

                                    add_record_to_terminal('AJAX error: 0.', 'error');

                                } else {

                                    let responseObj = JSON.parse(response),
                                        notices = responseObj['notices'],
                                        errors = responseObj['errors'],
                                        groupsTree = responseObj['groupsTree'],
                                        productCategories = responseObj['productCategories'],
                                        sizes = responseObj['sizes'],
                                        revision = responseObj['revision'],
                                        simpleGroups = responseObj['simpleGroups'],
                                        simpleDishes = responseObj['simpleDishes'],
                                        simpleGoods = responseObj['simpleGoods'],
                                        simpleModifiers = responseObj['simpleModifiers'],
                                        simpleSizes = responseObj['simpleSizes'];

                                    if (undefined === groupsTree) {

                                        add_logs_to_terminal(notices, errors);
                                        add_record_to_terminal('Organization does not have nomenclature groups.', 'error');

                                    } else {

                                        iikoGroups.empty();

                                        // Output groups info in the terminal.
                                        $.each(simpleGroups, function (index, value) {
                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'GROUPS');

                                        // Output products info in the terminal.
                                        $.each(simpleDishes, function (index, value) {
                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'DISHES');

                                        $.each(simpleGoods, function (index, value) {
                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'GOODS');

                                        $.each(simpleModifiers, function (index, value) {
                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'MODIFIERS');

                                        // Output sizes info in the terminal.
                                        $.each(simpleSizes, function (index, value) {
                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'SIZES');

                                        // Output nomenclature general info.
                                        $('#iikoNomenclatureGroups').text(Object.keys(simpleGroups).length);
                                        $('#iikoNomenclatureProductCategories').text(productCategories.length);
                                        $('#iikoNomenclatureDishes').text(Object.keys(simpleDishes).length);
                                        $('#iikoNomenclatureGoods').text(Object.keys(simpleGoods).length);
                                        $('#iikoNomenclatureModifiers').text(Object.keys(simpleModifiers).length);
                                        $('#iikoNomenclatureSizes').text(sizes.length);
                                        $('#iikoNomenclatureRevision').text(revision);

                                        iikoNomenclatureInfoWrap.removeClass('hidden');

                                        // Fill in groups select.
                                        groups_tree(groupsTree);

                                        // Hide info message and show groups block.
                                        iikoTempMessage.addClass('hidden');
                                        iikoNomenclatureImportWrap.removeClass('hidden');
                                    }
                                }

                                request_finish(isGetIikoNomenclatureDisable);
                            },

                            error: function (xhr, textStatus, errorThrown) {
                                add_record_to_terminal(errorThrown ? errorThrown : xhr.status, 'error');
                            }
                        });
                    }
                }

                // Import groups and products to WooCommerce.
                if ($(this).attr('name') === 'import_iiko_groups_products') {

                    let iikoChosenGroups = $('#iikoGroups').val();

                    if (iikoChosenGroups === null || iikoChosenGroups === undefined || iikoChosenGroups.length === 0) {

                        add_record_to_terminal('Chose groups to import.', 'error');

                        // request_finish(isGetIikoNomenclatureDisable);

                    } else {

                        $.ajax({
                            type: 'post',
                            url: skyweb_wc_iiko.ajax_url,
                            data: {
                                action: 'skyweb_ajax_wc_iiko__import_nomenclature_ajax',
                                skyweb_wc_iiko_import_nonce: skywebWCIikoNonce,
                                iikoChosenGroups: iikoChosenGroups,
                            },

                            success: function (response) {

                                if (response === '0') {

                                    add_record_to_terminal('AJAX error: 0.', 'error');

                                } else {

                                    let responseObj = JSON.parse(response),
                                        notices = responseObj['notices'],
                                        errors = responseObj['errors'],
                                        importedGroups = undefined !== responseObj['importedGroups'] ? responseObj['importedGroups'] : 0,
                                        importedProducts = undefined !== responseObj['importedProducts'] ? responseObj['importedProducts'] : 0;

                                    // Output errors and notices.
                                    add_logs_to_terminal(notices, errors);

                                    // Output imported nomenclature info.
                                    $('#iikoNomenclatureImportedGroups').text(importedGroups);
                                    $('#iikoNomenclatureImportedProducts').text(importedProducts);

                                    iikoNomenclatureImportedWrap.removeClass('hidden');
                                }

                                request_finish(isGetIikoNomenclatureDisable);
                            },

                            error: function (xhr, textStatus, errorThrown) {
                                add_record_to_terminal(errorThrown ? errorThrown : xhr.status, 'error');
                            }
                        });
                    }
                }

                // Get iiko cities.
                if ($(this).attr('name') === 'get_iiko_cities') {

                    let organizationId = $('#iikoOrganizations').val();

                    if (organizationId === null || organizationId === undefined || organizationId.length === 0) {

                        add_record_to_terminal('Organization is empty.', 'error');

                    } else {

                        iikoCities.empty();
                        iikoCitiesWrap.addClass('hidden');

                        $.ajax({
                            type: 'post',
                            url: skyweb_wc_iiko.ajax_url,
                            data: {
                                action: 'skyweb_ajax_wc_iiko__get_cities_ajax',
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
                                        cities = responseObj['cities'];

                                    if (undefined === cities) {

                                        add_logs_to_terminal(notices, errors);

                                    } else {

                                        let iikoCities = $('#iikoCities');

                                        iikoCities.empty();

                                        // Fill in cities select and output cities info in the terminal.
                                        $.each(cities, function (index, value) {

                                            iikoCities.append($('<option></option>')
                                                .attr('value', index)
                                                .text(value)
                                            );

                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'CITIES');

                                        iikoCitiesWrap.removeClass('hidden');
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

                // Get iiko streets.
                if ($(this).attr('name') === 'get_iiko_streets') {

                    let organizationId = $('#iikoOrganizations').val(),
                        cityId = $('#iikoCities').val(),
                        cityName = $('#iikoCities option:selected').text();

                    if (organizationId === null || organizationId === undefined || organizationId.length === 0) {

                        add_record_to_terminal('Organization is empty.', 'error');

                    } else if (cityId === null || cityId === undefined || cityId.length === 0) {

                        add_record_to_terminal('City ID is empty.', 'error');

                    } else if (cityName === null || cityName === undefined || cityName.length === 0) {

                        add_record_to_terminal('City name is empty.', 'error');

                    } else {

                        $.ajax({
                            type: 'post',
                            url: skyweb_wc_iiko.ajax_url,
                            data: {
                                action: 'skyweb_ajax_wc_iiko__get_streets_ajax',
                                skyweb_wc_iiko_import_nonce: skywebWCIikoNonce,
                                organizationId: organizationId,
                                cityName: cityName,
                                cityId: cityId,
                            },

                            success: function (response) {

                                if (response === '0') {

                                    add_record_to_terminal('AJAX error: 0.', 'error');

                                } else {

                                    let responseObj = JSON.parse(response),
                                        notices = responseObj['notices'],
                                        errors = responseObj['errors'],
                                        streets = responseObj['streets'];

                                    if (undefined === streets) {

                                        add_logs_to_terminal(notices, errors);

                                    } else {

                                        // Output streets info in the terminal.
                                        $.each(streets, function (index, value) {
                                            add_record_to_terminal(value + ' - ' + index, 'data');
                                        });
                                        add_record_to_terminal('', 'data', 'STREETS');
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
