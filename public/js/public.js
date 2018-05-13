/**
 * Paytium Public JS
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

/* global jQuery, pt_script */

(function ($) {
    'use strict';

    function debug_log( message ) {
        if ( pt.debug == true ) {
            console.log( message );
        }
    }

    $(function () {

        var $body = $('body');
        var ptFormList = $body.find('.pt-checkout-form');

        // Make sure each checkbox change sets the appropriate hidden value (Yes/No) to record
        // to Paytium payment records.
        var ptCheckboxFields = ptFormList.find('.pt-field-checkbox');
        ptCheckboxFields.change(function () {
            var checkbox = $(this);
            var checkboxId = checkbox.prop('id');
            var hiddenField = $body.find('#' + checkboxId + '_hidden'); // Hidden ID field is simply "_hidden" appended to checkbox ID field.

            hiddenField.val(checkbox.is(':checked') ? 'Yes' : 'No'); // Change to "Yes" or "No" depending on checked or not.
        });

        // Process the form(s)
        ptFormList.each(function () {
            var ptForm = $(this);

            // Add field that allows Paytium processing to know that JS was enabled on form
            $("<input>", {type: "hidden", name: 'pt-paytium-js-enabled', value: 1}).appendTo(ptForm);

            // Enable form button with javascript, so it doesn't show to users that don't have JS enabled
            ptForm.find('.pt-payment-btn')
                .show();

            //
            // START - Paytium No Payment
            //

            function isPaytiumNoPayment() {

                var noPaymentFound = false;

                ptForm.find("[id^=pt-paytium-no-payment]").each(function () {
                    noPaymentFound = true;
                });

                return noPaymentFound;
            }

            //
            // END - Paytium No Payment
            //

            //
            // START - Show a warning about prefilled fields
            //

            ptForm.find(".pt-field-prefill-warning-hint").click(function () {
                var ptPrefillWarningCounter = $(this).attr('data-pt-prefill-warning-counter');
                ptForm.find("#pt-prefill-warning-counter-" + ptPrefillWarningCounter).toggle("slow");
            });

            //
            // END - Show a warning about prefilled fields
            //

            //
            // START - Add subscription first payment to [paytium_total /] shortcode
            //

            // If there is a subscription first payment, adjust form to allow that
            if (ptForm.find("[id^=pt-subscription-first-payment]").length > 0) {
                // Add "First payment" text and amount after the [paytium_total /] Shortcode
                var ptFirstPayment = ptForm.find("[id^=pt-subscription-first-payment]").val();
                ptForm.find('.pt-total-amount').after('<div>' + paytium_localize_script_vars.subscription_first_payment + ' ' + currencyFormattedAmount(ptFirstPayment) + '</div>');
            }

            //
            // END - Add subscription first payment to [paytium_total /] shortcode
            //

            //
            // START - Add option amount labels to field label
            // Add option amount labels for dropdown/radio to field label, so users can see what customers selected
            //

            // Checkbox with amounts, get all already checked checkboxes and process amounts and labels
            ptForm.find('input[type=checkbox]:checked').filter('.pt-cf-amount').each(function () {

                //
                // Update amount
                //

                // Get id/name for checkbox form group amount
                var ptCheckboxFormGroupAmountId = $(this).attr('name').replace('[amount]', '[amount][total]');

                // Get selected value
                var ptCheckboxFieldValue = $(this).val();

                // Get total amount for this checkbox group
                var ptCheckboxTotalValue = ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").val();

                // Add selected value to total amount (don't remove those +'s)
                var ptCheckboxNewTotalValue = +ptCheckboxTotalValue + +ptCheckboxFieldValue;

                // Update the total value in value attribute
                ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('value', parseAmount(ptCheckboxNewTotalValue));

                // Update total value in pt-price data attribute
                ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('data-pt-price', parseAmount(ptCheckboxNewTotalValue));

                //
                // Update label
                //

                // Get/convert checkbox group label ID
                var ptCheckboxFormGroupLabelId = $(this).attr('name').replace('[amount]', '[label]');

                // Get previously checked options
                var ptCheckboxCurrentOptions = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options');

                ptCheckboxCurrentOptions = JSON.parse(ptCheckboxCurrentOptions);

                // If previously checked options are not an array, this means it's empty
                if (Object.keys(ptCheckboxCurrentOptions).length === 0) {
                    ptCheckboxCurrentOptions = {};
                }

                ptCheckboxCurrentOptions[$(this).attr('data-pt-checkbox-id')] = $(this).parent().text();

                // Start string with current group label
                var ptCheckboxCurrentOptionsString = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-original-label');

                // Convert current options to a string for HTML hidden field
                for (var key in ptCheckboxCurrentOptions) {
                    ptCheckboxCurrentOptionsString += ', ' + ptCheckboxCurrentOptions[key];
                }

                // Convert to a format that is save for HTML fields
                ptCheckboxCurrentOptions = JSON.stringify(ptCheckboxCurrentOptions);

                // Add updated options to data-pt-checked-options attribute
                ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options', ptCheckboxCurrentOptions);

                // Add current options string to hidden HTML field
                ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('value', ptCheckboxCurrentOptionsString);

            });

            // Radio amounts, try to get first option in radio buttons to use as default if an option is not selected yet
            ptForm.find('input[type=radio]:checked').filter('.pt-cf-amount').each(function () {

                var ptRadioCustomOption = $(this).parent().text();

                var ptRadioFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                var ptRadioFormGroupLabel = ptForm.find("[name='" + ptRadioFormGroupId + "']").attr('data-pt-original-label');

                ptForm.find("[name='" + ptRadioFormGroupId + "']").val(function () {
                    return ptRadioFormGroupLabel + ' ' + ptRadioCustomOption;
                });
            });

            // Get selected option for radio and dropdown amounts, when user selects them
            ptForm.find('.pt-cf-amount').on('change', function () {

                // Process radio buttons amount labels
                if ($(this).is('input[type="radio"]')) {

                    var ptRadioCustomOption = $(this).parent().text();

                    var ptRadioFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                    var ptRadioFormGroupLabel = ptForm.find("[name='" + ptRadioFormGroupId + "']").attr('data-pt-original-label');

                    ptForm.find("[name='" + ptRadioFormGroupId + "']").val(function () {
                        return ptRadioFormGroupLabel + ' ' + ptRadioCustomOption;
                    });

                }

                // Process dropdown amount labels
                if ($(this).is('select')) {

                    var ptDropdownCustomOption = $(this).find(':selected').text();

                    var ptDropdownFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                    var ptDropdownFormGroupLabel = ptForm.find("[name='" + ptDropdownFormGroupId + "']").attr('data-pt-original-label');

                    ptForm.find("[name='" + ptDropdownFormGroupId + "']").val(function () {
                        return ptDropdownFormGroupLabel + ' ' + ptDropdownCustomOption;
                    });

                }

            });

            //
            // END - Option label to field label
            //

            //
            // START - Update total when a paid field changes
            //

            ptForm.find( '.pt-uea-custom-amount' ).on('keyup', update_totals );
            ptForm.find( ':checkbox' ).change(update_checkbox_field);
            ptForm.find( '.pt-cf-amount' ).on( 'change', update_totals );

            //
            // END - Update total when user enters amount (custom amount)
            //

            //
            // START - UEA, Update individual open amounts (uea) user changes amount
            //

            ptForm.find("[id^=pt_uea_custom_amount_]").on('keyup', update_open_field );

            //
            // END - UEA, Update individual open amounts (uea) user changes amount
            //

            function get_form_paid_fields() {
                var fields = [];

                ptForm.find( '.pt-cf-label-amount' ).each( function( index, element ) { fields.push( element ); }); // Label fields
                ptForm.find( '.pt-uea-custom-amount' ).each( function( index, element ) { fields.push( element ); }); // Open amount fields
                ptForm.find( 'select.pt-cf-amount' ).each( function( index, element ) { fields.push( element ); }); // Select fields
                ptForm.find( 'input[type=radio].pt-cf-amount:checked' ).each( function( index, element ) { fields.push( element ); }); // Radio fields
                ptForm.find( 'input[type=checkbox].pt-cf-amount:checked' ).each( function( index, element ) { fields.push( element ); }); // Checkbox fields

                return fields;
            }

            function get_form_total() {

                // Get all fields/amounts
                var total = 0;
                var fields = get_form_paid_fields();

                // Loop through each amount field
                $( fields ).each( function( index, element ) {
                    var fieldAmount = 0;

                    switch ( $( element ).prop( 'nodeName' ) ) {
                        case 'SELECT' :
                            fieldAmount = $( element).find( 'option:selected' ).attr( 'data-pt-price' );
                            break;

                        default:
                        case 'INPUT' :
                            var type = $( element ).attr( 'type' );
                            if ( type == 'hidden' || type == 'text' ) {
                                fieldAmount = $( element ).val();
                            } else if ( type == 'radio' || type == 'checkbox' ) {
                                fieldAmount = $( element ).data( 'pt-price' );
                            }
                            break;
                    }

                    total = parseFloat( total ) + parseAmount( fieldAmount );
                });

                return total;
            }

            function update_open_field() {

                var ptUEAFieldName = $(this).attr("name");
                var ptUEAFieldID = $(this).attr("id");
                var ptUEAFieldValue = $(this).val();

                ptForm.find("[id^="+ptUEAFieldID+"]").attr('value', parseAmount(ptUEAFieldValue));
                ptForm.find("[name*='"+ptUEAFieldName+"']").val(ptUEAFieldValue);
                ptForm.find("[name*='"+ptUEAFieldName+"']").attr('data-pt-price', parseAmount(ptUEAFieldValue));
                ptForm.find('.pt-uea-custom-amount-formatted').val(parseAmount(ptUEAFieldValue));

            }
            update_open_field();

            // When checkbox with amount is checked or unchecked, process that change
            function update_checkbox_field() {

                // Make sure it's an amount checkbox by checking data attribute 'data-pt-price' is defined, otherwise abort.
                if (typeof $(this).attr('data-pt-price') === 'undefined') {
                    return false;
                }

                // If checkbox was checked, add to total, else deduct
                if ($(this).is(':checked')) {

                    //
                    // Update amount
                    //

                    // Get selected value
                    var ptCheckboxFieldValue = $(this).val();

                    // Get id/name for checkbox form group amount
                    var ptCheckboxFormGroupAmountId = $(this).attr('name').replace('[amount]', '[amount][total]');

                    // Get total amount for this checkbox group
                    var ptCheckboxTotalValue = ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").val();

                    // Add selected value to total amount (don't remove those +'s)
                    var ptCheckboxNewTotalValue = +ptCheckboxTotalValue + +ptCheckboxFieldValue;

                    // Update the total value in value attribute
                    ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('value', parseAmount(ptCheckboxNewTotalValue));

                    // Update total value in pt-price data attribute
                    ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('data-pt-price', parseAmount(ptCheckboxNewTotalValue));

                    //
                    // Update label
                    //

                    // Get/convert checkbox group label ID
                    var ptCheckboxFormGroupLabelId = $(this).attr('name').replace('[amount]', '[label]');

                    // Get previously checked options
                    var ptCheckboxCurrentOptions = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options');
                    ptCheckboxCurrentOptions = JSON.parse(ptCheckboxCurrentOptions);

                    // If previously checked options are not an array, this means it's empty
                    if ( Object.keys(ptCheckboxCurrentOptions).length === 0 ) {
                        ptCheckboxCurrentOptions = {};
                    }

                    // Get selected label, convert currency symbol to HTML entity
                    ptCheckboxCurrentOptions[$(this).attr('data-pt-checkbox-id')] = $(this).parent().text();

                    // Start string with current group label
                    var ptCheckboxCurrentOptionsString = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-original-label');

                    // Convert current options to a string for HTML hidden field
                    for (var key in ptCheckboxCurrentOptions) {
                        ptCheckboxCurrentOptionsString += ', ' + ptCheckboxCurrentOptions[key];
                    }

                    // Convert to a format that is save for HTML fields
                    ptCheckboxCurrentOptions = JSON.stringify(ptCheckboxCurrentOptions);

                    // Add updated options to data-pt-checked-options attribute
                    ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options', ptCheckboxCurrentOptions);


                    // Add current options string to hidden HTML field
                    ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('value', ptCheckboxCurrentOptionsString);

                } else if (($(this).not(':checked'))) {

                    //
                    // Update amount
                    //

                    // Get selected value
                    var ptCheckboxFieldValue = $(this).val();

                    // Get id/name for checkbox form group amount
                    var ptCheckboxFormGroupAmountId = $(this).attr('name').replace('[amount]', '[amount][total]');

                    // Get total amount for this checkbox group
                    var ptCheckboxTotalValue = ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").val();

                    if (ptCheckboxFieldValue == null) {
                        ptCheckboxFieldValue = 0;
                    }

                    // Remove selected value from total amount (don't remove those +'s)
                    var ptCheckboxNewTotalValue = +ptCheckboxTotalValue - +ptCheckboxFieldValue;

                    // Check that the total is not less than zero, otherwise set to zero.
                    if (isNaN(ptCheckboxNewTotalValue) || ptCheckboxNewTotalValue <= 0) {
                        ptCheckboxNewTotalValue = 0;
                    }

                    // Update the total value in value attribute
                    ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('value', parseAmount(ptCheckboxNewTotalValue));

                    // Update total value in pt-price data attribute
                    ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('data-pt-price', parseAmount(ptCheckboxNewTotalValue));

                    //
                    // Update label
                    //

                    // Get/convert checkbox group label ID
                    var ptCheckboxFormGroupLabelId = $(this).attr('name').replace('[amount]', '[label]');

                    // Get previously checked options
                    var ptCheckboxCurrentOptions = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options');
                    ptCheckboxCurrentOptions = JSON.parse(ptCheckboxCurrentOptions);

                    // If previously checked options are an array, remove the selected label
                    if (typeof ptCheckboxCurrentOptions === 'object') {

                        // Only add the selected label if it's not in previously checked options array already

                        var ptCheckboxSelectedLabelId = $(this).attr('data-pt-checkbox-id');

                        delete ptCheckboxCurrentOptions[ptCheckboxSelectedLabelId];

                        // Start string with current group label
                        var ptCheckboxCurrentOptionsString = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-original-label');

                        // Convert current options to a string for HTML hidden field
                        for (var key in ptCheckboxCurrentOptions) {
                            ptCheckboxCurrentOptionsString += ', ' + ptCheckboxCurrentOptions[key];
                        }

                        // Convert to a format that is save for HTML fields
                        ptCheckboxCurrentOptions = JSON.stringify(ptCheckboxCurrentOptions);

                        // Add updated options to data-pt-checked-options attribute
                        ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options', ptCheckboxCurrentOptions);

                        // Add current options string to hidden HTML field
                        ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('value', ptCheckboxCurrentOptionsString);

                    }

                }
            }

            function update_totals() {
                var total = get_form_total();
                ptForm.find('.pt-total-amount').html( currencyFormattedAmount( total ) );
                ptForm.find('.pt_amount').val( total );
            }
            update_totals();

            function submitFormProcessing() {

                debug_log('click.ptPaymentBtn fired');
                debug_log('Check form valid:', ptForm.parsley().validate());

                if (ptForm.parsley().validate()) {
                    debug_log("finalAmount: " + get_form_total() );

                    // Run totals one more time to ensure the total amount is accurate
                    update_totals();

                    //
                    // START - Process (custom) fields
                    //

                    // Process individual fields
                    $(ptForm.find("[id^=pt-field-]")).each(function (index, element) {

                        var ptFieldValue = $(element).val(); // Get the field value
                        var ptUserLabel = document.getElementById(this.id).dataset.ptUserLabel; // Get the user defined field label
                        var ptFieldType = document.getElementById(this.id).dataset.ptFieldType; // Get the field type

                        // Get required attribute
                        var required = $(element).attr("required");

                        // Validate that required fields are filled
                        if ((required == 'required') && ptFieldValue == '') {

                            window.alert(paytium_localize_script_vars.field_is_required.replace('%s', ptUserLabel));
                            debug_log('ProcessFailed');
                            return false;
                        }

                        // Validate email fields
                        if (ptFieldType == 'email' && ptFieldValue !== '' ) {
                            var ptEmailreg = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,15})+$/;

                            if ((ptEmailreg.test(ptFieldValue) == false)) {
                                window.alert(paytium_localize_script_vars.no_valid_email.replace('%s', ptUserLabel));
                                debug_log('ProcessFailed');
                                return false;
                            }
                        }

                        // Log everything to Console when troubleshooting
                        debug_log($(element));
                        debug_log('Processing field (type, label, value, id): ' + ptFieldType + ', ' + ptUserLabel + ', ' + ptFieldValue + ', ' + this.id);

                        //
                        // Add the user's field label to form post data, so it can be used as user-facing identifier for that field
                        //

                        // Create unique field ID for the user's field label
                        var ptUserLabelLabel = this.id + "-label";

                        // Add the unique field ID and user's label to the form post data
                        $("<input>", {type: "hidden", name: ptUserLabelLabel, value: ptUserLabel}).appendTo(ptForm);

                        //
                        // Check if field is set as user_data="true" and store the preference if so
                        //
                        var ptUserData = document.getElementById(this.id).dataset.ptUserData; // Get the field type

                        if (ptUserData == 'true') {
                            // Create unique field ID for the user's field label
                            var ptUserDataLabel = this.id + "-user-data";

                            // Add the unique field ID and user's label to the form post data
                            $("<input>", {type: "hidden", name: ptUserDataLabel, value: ptUserData}).appendTo(ptForm);
                        }

                    });

                    //
                    // END - Process (custom) fields
                    //

                    //
                    // START - Process subscription(s)
                    //

                    // Process subscription fields
                    $(ptForm.find("[id^=pt-subscription-]")).each(function (index, element) {

                        // Get the field value
                        var ptFieldValue = $(element).val();

                        // Get the field type
                        // this.id is field type

                        // Log everything to Console when troubleshooting
                        debug_log($(element));
                        debug_log('Processing ' + this.id + ', ' + ptFieldValue);

                        // Add the unique field ID and user's label to the form post data
                        $("<input>", {type: "hidden", name: this.id, value: ptFieldValue}).appendTo(ptForm);

                    });

                    // Process subscription first payment
                    if (ptForm.find("[id^=pt-subscription-first-payment]").length > 0) {

                        // Get recurring amount/form total before first payment
                        var ptRecurringTotal = ptForm.find(".pt_amount").val();

                        // Add recurring amount
                        $("<input>", {
                            type: "hidden",
                            name: 'pt-subscription-recurring-payment',
                            value: ptRecurringTotal
                        }).appendTo(ptForm);

                        // Add "First payment" text and amount after the [paytium_total /] Shortcode
                        var ptFirstPayment = ptForm.find("[id^=pt-subscription-first-payment]").val();

                        // Update total amount to first payment
                        ptForm.find('.pt_amount').val(ptFirstPayment);

                    }

                    //
                    // END - Process subscription fields
                    //

                    // If there is no amount entered or amount is too low to be processed by Mollie
                    // block execution of script and show an alert. Why 1 euro? Lower amounts
                    // are just not logical! https://api.mollie.nl/v1/methods
                    if ((get_form_total() <= '1') && (isPaytiumNoPayment() == false)) {
                        window.alert(paytium_localize_script_vars.amount_too_low);
                        return false;
                    }

                    // Enable the below line if you want to process the form without redirecting
                    // The form data is not stored in Paytium at this point
                    // Also see line 24 in /paytium/includes/process-payment-functions.php
                    //return false;

                    // Unbind original form submit trigger before calling again to "reset" it and submit normally.
                    ptForm.unbind('submit');
                    ptForm.submit();

                    // Disable original payment button and change text for UI feedback while POST-ing to Mollie
                    ptForm.find('.pt-payment-btn')
                        .prop('disabled', true)
                        .find('span')
                        .text(paytium_localize_script_vars.processing_please_wait);

                }

                event.preventDefault();
            }

            //
            // START - Paytium Links
            //

            ptForm.find("[id^=pt-paytium-links]").each(function () {

                // Create an object with all data
                function getSearchParameters() {
                    var prmstr = window.location.search.substr(1);
                    prmstr = decodeURIComponent(prmstr);
                    return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
                }

                function transformToAssocArray(prmstr) {
                    var params = {};
                    var prmarr = prmstr.split("&");
                    for (var i = 0; i < prmarr.length; i++) {
                        var tmparr = prmarr[i].split("=");
                        params[tmparr[0]] = tmparr[1];
                    }
                    return params;
                }

                var params = getSearchParameters();

                $.each(params, function (key, valueObj) {

                    $(ptForm.find("[id^=pt-field-]")).each(function (index, element) {

                        // Get the user defined field label
                        var ptUserLabel = document.getElementById(this.id).dataset.ptUserLabel;


                        if (ptUserLabel == key) {
                            $(element).val(valueObj);
                        }

                    });

                    if (key.toLowerCase() == 'bedrag' || key.toLowerCase() == 'amount') {
                        ptForm.find("[name*='pt-amount']").val(valueObj);
                        ptForm.find('.pt-uea-custom-amount').val(valueObj);

                        ptForm.find('.pt-uea-custom-amount-formatted').val(parseAmount(valueObj));

                        ptForm.find("[name*='pt-amount']").attr('data-pt-price', parseAmount(valueObj));
                        ptForm.find('.pt-uea-custom-amount').attr('data-pt-price', parseAmount(valueObj));

                        update_totals();
                    }

                });

                ptForm.find("[id^=pt-paytium-links-auto-redirect]").each(function () {
                    ptForm.find('.pt-payment-btn').click(submitFormProcessing());
                });

            });

            //
            // END - Paytium Links
            //

            ptForm.find('.pt-payment-btn').on('click.ptPaymentBtn', submitFormProcessing);

        });

        // Convert to formatted amount
        function currencyFormattedAmount(amount) {
            amount = parseAmount( amount ) + ''; // Convert to string
            amount = Number(Math.round(amount+'e2')+'e-2').toFixed(2);
            amount = amount.replace( '.', pt.decimal_separator );
            return pt.currency_symbol + " " + amount;
        }

		/**
		 * Parse to a valid amount.
         * @returns float Valid number.
         */
        function parseAmount( amount ) {
            if ( typeof amount == 'string' ) {
                amount = amount.replace( ',', '.' );
            }

            if ( isNaN( amount ) || amount == '' ) {
                amount = 0;
            }

            amount = parseFloat( amount );
            return roundToTwo( amount );
        }

        // https://stackoverflow.com/a/18358056/3389968
        function roundToTwo(num) {
            return +(Math.round(num + "e+2")  + "e-2");
        }
    });

}(jQuery));
