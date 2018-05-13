(function () {
    tinymce.PluginManager.add('paytiumshortcodes', function (editor, url) {
        editor.addButton('paytiumshortcodes', {
            title: 'Paytium shortcodes',
            type: 'menubutton',
            icon: 'icon paytium-shortcodes-icon',
            menu: [
                {
                    text: 'Simple product or donation, static amount',
                    onclick: function () {
                        editor.insertContent('[paytium name="Your webshop name" description="Your product or description" button_label="Pay"]' + '<br>' + '[paytium_field type="label" label="€19,95" amount="19,95" /]' + '<br>' + '[/paytium]');
                    }
                },
                {
                    text: 'Simple product or donation, open amount',
                    onclick: function () {
                        editor.insertContent('[paytium name="Your webshop name" description="Donations" button_label="Donate"]' + '<br>' + '[paytium_field type="open" label="Donation Amount:" default="25" /]' + '<br>' + '[paytium_total label="Donate" /]' + '<br>' + '[/paytium]');
                    }
                },
                {
                    text: 'Dropdown with multiple amounts',
                    onclick: function (e) {
                        e.stopPropagation();
                        editor.insertContent('[paytium name="Your webshop name" description="Your product or description" button_label="Pay"]' + '<br>' + '[paytium_field type="dropdown" label="Options" options="9,95/19,95/29,95" options_are_amounts="true" /]' + '<br>' + '[paytium_total /]' + '<br>' + '[/paytium]');
                    }
                },
                {
                    text: 'Radio buttons with multiple amounts',
                    onclick: function (e) {
                        e.stopPropagation();
                        editor.insertContent('[paytium name="Your webshop name" description="Your product or description" button_label="Pay"]' + '<br>' + '[paytium_field type="radio" label="Options" options="9,95/19,95/29,95" options_are_amounts="true" /]' + '<br>' + '[paytium_total /]' + '<br>' + '[/paytium]');
                    }
                },
                {
                    text: 'Simple form with required email address',
                    onclick: function () {
                        editor.insertContent('[paytium name="Your webshop name" description="Your product or description" button_label="Pay"]' + '<br>' + '[paytium_field type="email" label="Your email" required="true" /]' + '<br>' + '[paytium_field type="label" label="Product ABC for €19,95" amount="19,95" /]' + '<br />' + '[paytium_total /]' + '<br>' + '[/paytium]');
                    }
                },

                {
                    text: 'Extended form with name, email and address fields',
                    onclick: function () {
                        editor.insertContent('[paytium name="Your webshop name" description="Your product or description" button_label="Pay"]' + '<br>' + '[paytium_field type="email" label="Email" required="true" /]' + '<br>' + '[paytium_field type="name" label="Name" required="true" /]' + '<br>' + '[paytium_field type="text" label="Street name" required="true" /]' + '<br>' + '[paytium_field type="text" label="House number" required="true" /]' + '<br>' + '[paytium_field type="text" label="Postcode" required="true" /]' + '<br>' + '[paytium_field type="text" label="City" required="true" /]' + '<br>' + '[paytium_field type="text" label="Country" required="true" /]' + '<br>' + '[paytium_field type="label" label="Product ABC for €19,95" amount="19,95" /]' + '[paytium_total /]' + '<br>' + '[/paytium]');
                    }
                },
                {
                    text: 'Subscription/recurring payment',
                    onclick: function () {
                        editor.insertContent('[paytium name="Subscription store" description="Some subscription" button_label="Subscribe"]' + '<br>' + '[paytium_subscription interval="1 days" times="99" /]' + '<br>' + '[paytium_field type="name" label="Volledige naam" /]' + '<br>' + '[paytium_field type="email" label="Jouw email" required="true" /]' + '<br>' + '[paytium_field type="label" label="Subscription €99" amount="99" /]' + '[paytium_total /]' + '<br>' + '[/paytium]' + '<br><br>' + 'Parameter interval in the "paytium_subscription" shortcode is required and can be days, weeks, months. For example if you want charge the customer every 3 days, set it to "3 days".' + '<br><br>' + 'Parameter times in [paytium_subscription /] is not required. Times are the total number of charges for the subscription to complete. Leave empty for an on-going subscription.' + '<br><br>' + 'The fields with type name and email are also required.' + '<br><br>' + 'Make sure you enable payment methods that support recurring payments in your Mollie account, for example creditcard and SEPA Direct Debit.' + '<br><br>' + 'You can remove this paragraph when you are done. ;)');
                    }
                },
                {
                    text: 'View manual on paytium.nl >',
                    onclick: function () {
                        window.open("https://www.paytium.nl/handleiding/");
                    }
                }
            ]
        });
    });
})();