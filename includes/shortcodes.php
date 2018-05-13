<?php
/**
 * Plugin shortcode functions
 *
 * @package   PT
 * @author    David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $counter;
$counter = 1;

/**
 * Function to process the [paytium] shortcode
 *
 * @since   1.0.0
 */
function pt_paytium_shortcode( $attr, $content = null ) {

	global $pt_script_options;

	// Variable to hold each form's data-pt-id attribute.
	static $pt_id = 0;

	// Increment variable for each iteration.
	$pt_id ++;

	extract( shortcode_atts( array (
		'name'                      => get_option( 'paytium_name', get_bloginfo( 'title' ) ),
		'description'               => '',
		'amount'                    => '',
		'image_url'                 => get_option( 'paytium_image_url', '' ),
		'customer_details'          => ( ! empty( $pt_options['customer_details'] ) ? 'true' : 'false' ),
		// true or false
		'pt_redirect_url'           => get_option( 'pt_redirect_url', get_permalink() ),
		'prefill_email'             => 'false',
		'test_mode'                 => 'live',
		'id'                        => null,
		'tax'                       => '',
		'payment_details_placement' => 'above',
	), $attr, 'paytium' ) );

	// Generate custom form id attribute if one not specified.
	// Rename var for clarity.
	$form_id = $id;
	if ( $form_id === null || $form_id == false ) {
		$form_id = 'pt_checkout_form_' . $pt_id;
	}

	Paytium_Shortcode_Tracker::set_as_base( 'paytium', $attr );

	$test_mode = ( isset( $_GET['test_mode'] ) ? 'test' : $test_mode );
	$data_key  = pt_get_api_key( $test_mode );

	// Check if key's are entered at all, otherwise throw error message
	if ( empty( $data_key ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<h6>' . __( 'You must enter your Mollie API keys in Paytium before payments are possible!', 'paytium' ) . '</h6>';
		}

		return '';
	}

	// Add Parsley JS form validation attribute here.
	$html = '<form method="POST" action="" class="pt-checkout-form" id="' . esc_attr( $form_id ) . '" data-pt-id="' . $pt_id . '" data-parsley-validate>';

	// Check if pt-amount-validation-failed is set (from pt_process_payment), and if so show an alert
	if ( ! empty( $_REQUEST['pt-amount-validation-failed'] ) ) {
		$html .= '<div class="pt-form-alert">' . __( 'No (valid) amount entered or amount is too low!', 'paytium' ) . '</div>';
	}

	// Check if pt-validation-failed is set (from pt_process_payment), and if so show an alert
	if ( ! empty( $_REQUEST['pt-js-validation-failed'] ) && ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
		$html .= '<div class="pt-form-alert">' . sprintf( __( 'Paytium\'s JavaScript isn\'t loaded! <a href="%s">Please read this FAQ.</a>', 'paytium' ), 'https://www.paytium.nl/handleiding/veelgestelde-vragen/#javascript-problemen-met-paytium-of-extra-velden-worden-niet-opgeslagen' ) . '</div>';
	}

	// Check that JavaScript is enabled, otherwise show an alert.
	$html .= '<noscript><div class="pt-form-alert">' . __( 'This form requires JavaScript, enable it in your browser.', 'paytium' ) . '</div></noscript>';

	// Save all of our options to an array so others can run them through a filter if they need to
	// David: Used for the disabled tab in settings, can be removed if I don't re-enable the tab.
	$pt_script_options = array (
		'script' => array (
			'key'              => $data_key,
			'name'             => html_entity_decode( $name ),
			'description'      => html_entity_decode( $description ),
			'amount'           => $amount,
			'customer-details' => $customer_details,
			'label'            => html_entity_decode( $button_label ),
			'email'            => sanitize_email( pt_prefill_email() ),
		),
		'other'  => array (
			'pt_redirect_url'           => $pt_redirect_url,
			'payment_details_placement' => ( $payment_details_placement == 'below' ? 'below' : 'above' ),
		),
	);

	$html .= do_shortcode( $content );

	// BC - When 'amount' attribute is set at the main shortcode, add a hidden 'label' field
	if ( ! empty( $amount ) ) {
		$html .= pt_cf_label( $attr );
	}

	$pt_script_options = apply_filters( 'pt_modify_script_options', $pt_script_options );

	$name            = $pt_script_options['script']['name'];
	$description     = $pt_script_options['script']['description'];
	$pt_redirect_url = $pt_script_options['other']['pt_redirect_url'];

	$html .= '<input type="hidden" name="pt-name" value="' . esc_attr( $name ) . '" />';
	$html .= '<input type="hidden" name="pt-description" value="' . esc_attr( $description ) . '" />';
	$html .= '<input type="hidden" name="pt-amount" class="pt_amount" value="" />';
	$html .= '<input type="hidden" name="pt_redirect_url" value="' . esc_attr( ( ! empty( $pt_redirect_url ) ? $pt_redirect_url : get_permalink() ) ) . '" />';

	if ( $test_mode == 'true' ) {
		$html .= '<input type="hidden" name="pt_test_mode" value="true" />';
	}

	// TODO David - Replace with custom field template?
	// Add customer details fields if it is enabled
	if ( $customer_details === 'true' ) {
		$html .= '<label for="pt-customer-details-name">Naam:</label><input type="text" name="pt-customer-details-name" class="pt-customer-details-name" value="" />';
		$html .= '<label for="pt-customer-details-street">Straatnaam:</label><input type="text" name="pt-customer-details-street" class="pt-customer-details-street" value="" />';
		$html .= '<label for="pt-customer-details-house_number">Huisnummer:</label><input type="text" name="pt-customer-details-house_number" class="pt-customer-details-house_number" value="" />';
		$html .= '<label for="pt-customer-details-city">Plaatsnaam:</label><input type="text" name="pt-customer-details-city" class="pt-customer-details-city" value="" />';
		$html .= '<label for="pt-customer-details-postcode">Postcode:</label><input type="text" name="pt-customer-details-postcode" class="pt-customer-details-postcode" value="" />';
		$html .= '<label for="pt-customer-details-country">Land:</label><input type="text" name="pt-customer-details-country" class="pt-customer-details-country" value="" />';
	}

	$html .= '</form>';

	$error_count = Paytium_Shortcode_Tracker::get_error_count();

	Paytium_Shortcode_Tracker::reset_error_count();

	if ( $error_count > 0 ) {
		if ( current_user_can( 'manage_options' ) ) {
			return Paytium_Shortcode_Tracker::print_errors();
		}

		return '';
	}

	return $html;

}

add_shortcode( 'paytium', 'pt_paytium_shortcode' );

/**
 * Function to process [paytium_button] shortcode
 *
 * @since 2.1.0
 */
function pt_paytium_button( $attr ){

	$attr = shortcode_atts( array (
		'style' => get_option( 'paytium_button_style', '' ),
		'label' => get_option( 'paytium_button_label', __( 'Pay', 'paytium' ) ),
		'class' => "paytium-button-el"
	), $attr );

	// Add a filter here to allow developers to hook into the form
	$filter_html = '';
	$html = apply_filters( 'pt_before_payment_button', $filter_html );

	$html .= sprintf(
		'<button class="pt-payment-btn %s" %s ><span>%s</span></button>',
		$attr["class"],
		!empty( $attr["style"] ) ? 'style="'.$attr["style"].'"' : "",
		$attr["label"]
	);

	return $html;
}

add_shortcode( 'paytium_button', 'pt_paytium_button' );


/**
 * Function to process [paytium_total] shortcode
 *
 * @since 1.0.0
 */
function pt_paytium_total( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'label' => __( 'Total:', 'paytium' )
	), $attr, 'paytium_total' );

	extract( $attr );

	$label = get_option( 'paytium_total_label', $attr['label'] );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_total_' . $counter, 'paytium_total', $attr, false );

	$html = $label . ' <span class="pt-total-amount">&euro; -</span>';

	$args = pt_get_args( '', $attr );
	$counter++;

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_total', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_total', 'pt_paytium_total' );

/**
 * Shortcode to output a checkbox - [paytium_checkbox]
 *
 * @since 1.0.0
 */
function pt_cf_checkbox( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'id'       => '',
		'label'    => '',
		'required' => 'false',
		'default'  => 'false'
	), $attr, 'paytium_date' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_checkbox_' . $counter, 'paytium_checkbox', $attr, false );

	$id = 'pt_cf_checkbox_' . $counter;

	$html = '<label>';
	$html .= '<input type="checkbox" id="' . esc_attr( $id ) . '" class="pt-cf-checkbox" name="pt_form_field[' . esc_attr( $id ) . ']" ';
	$html .= ( ( $required === 'true' ) ? 'required' : '' ) . ' ' . checked( ( $default === 'true' || $default === 'checked' ) ) . ' value="Yes" data-parsley-errors-container="#pt_cf_checkbox_error_' . $counter . '">';
	$html .= $label . '</label>';

	// Hidden field to hold a value to pass to Paytium payment record.
	$html .= '<input type="hidden" id="' . esc_attr( $id ) . '_hidden" class="pt-cf-checkbox-hidden" name="pt_form_field[' . esc_attr( $id ) . ']" value="' . ( ( 'true' === $default || 'checked' === $default ) ? 'Yes' : 'No' ) . '">';
	$html .= '<div id="pt_cf_checkbox_error_' . $counter . '"></div>';

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_checkbox', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_checkbox', 'pt_cf_checkbox' );


/**
 * Shortcode to output a number box - [paytium_checkbox]
 *
 * @since 2.1.0
 */
function pt_cf_checkbox_new( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'id'                     => '',
		'label'                  => '',
		'default'                => '',
		'required'               => '',
		'options'                => '',
		'options_are_quantities' => 'false',
		'amounts'                => '',
		'options_are_amounts'    => 'false',
		'tax'                    => '',
	), $attr, 'paytium_checkbox' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_checkbox_' . $counter, 'paytium_checkbox', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$options = explode( '/', $options );

	if ( ! empty( $amounts ) ) {
		$amounts = explode( '/', str_replace( ' ', '', $amounts ) );//

		if ( count( $options ) != count( $amounts ) ) {
			Paytium_Shortcode_Tracker::update_error_count();

			if ( current_user_can( 'manage_options' ) ) {
				Paytium_Shortcode_Tracker::add_error_message( '<h6>' . __( 'Your number of options and amounts are not equal.', 'paytium' ) . '</h6>' );
			}

			return '';
		}
	}

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );
	$amount_class = ( ( ! $amounts == false || $options_are_amounts == 'true' ) ? ' pt-cf-amount' : '' );
	$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';

	$html = ( ! empty( $label ) ? '<label id="pt-cf-checkbox-label">' . $label . ':</label>' : '' );

	$html .= '<div class="pt-checkbox-group">';

	$i = 0;
	foreach ( $options as $option ) {

		$option = trim( $option );
		$value  = $option;

		if ( $options_are_amounts == 'true' ) {
			$amount = $option;
			$option_name = '&euro; ' . $amount;
			$value = pt_user_amount_to_float( $value );
		} elseif ( ! empty( $amounts ) ) {
			$amount = $amounts[ $i - 1 ];
			$option_name = $option . ' - &euro; ' . $amount;
			$value = pt_user_amount_to_float( $amount );
		}

		// Don't use built-in checked() function here for now since we need "checked" in double quotes.
		$html .= '<label title="' . esc_attr( $option ) . '" >';
		$html .= '<input type="checkbox" name="' . esc_attr( $id ) . '[amount]" value="' . $value . '" ' . checked( $default, $option, false ) .
		         ' class="' . esc_attr( $id ) . '_' . $i . $quantity_class . $amount_class . ' pt-checkbox-amount" data-pt-price="'. $value . '" data-pt-checkbox-id="'. $i . '" data-parsley-multiple="checkbox" data-parsley-errors-container=".pt-checkbox-group-errors"' . $required . ' data-parsley-errors-container=".pt-form-group" ' . $quantity_html . '>';
		$html .= '<span>' . ( isset( $option_name ) ? $option_name : $option ) . '</span>';
		$html .= '</label>';

		$i ++;
	}

	$html .= '<div class="pt-checkbox-group-errors"></div>';
	$html .= '<input type="hidden" name="' . $id . '[amount]" id="' . $id . '[amount][total]" value="" data-pt-price="' . esc_attr( pt_user_amount_to_float($default) ) . '" />';
	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '" data-pt-original-label="' . wp_kses_post( $attr['label'] ) . '" data-pt-checked-options="[]">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="checkbox">';

	$html .= '</div>'; //pt-checkbox-group

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_checkbox', $html, $args ) . '</div>';

}

/**
 * Shortcode to output a number box - [paytium_number]
 *
 * @since 1.0.0
 */
function pt_cf_number( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'id'                     => '',
		'label'                  => '',
		'required'               => 'false',
		'placeholder'            => '',
		'default'                => '',
		'min'                    => '',
		'max'                    => '',
		'step'                   => '',
		'options_are_quantities' => 'false'
	), $attr, 'paytium_date' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_number_' . $counter, 'paytium_number', $attr, false );

	// Check for ID and if it doesn't exist then we will make our own
	if ( $id == '' ) {
		$id = 'pt_cf_number_' . $counter;
	}

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" data-parsley-min="1" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );

	$min  = ( ! empty( $min ) ? 'min="' . $min . '" ' : '' );
	$max  = ( ! empty( $max ) ? 'max="' . $max . '" ' : '' );
	$step = ( ! empty( $step ) ? 'step="' . $step . '" ' : '' );

	$html = ( ! empty( $label ) ? '<label for="' . esc_attr( $id ) . '">' . $label . '</label>' : '' );

	// No Parsley JS number validation yet as HTML5 number type takes care of it.
	$html .= '<input type="number" data-parsley-type="number" class="pt-form-control pt-cf-number' . $quantity_class . '" id="' . esc_attr( $id ) . '" name="pt_form_field[' . $id . ']" ';
	$html .= 'placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $default ) . '" ';
	$html .= $min . $max . $step . ( ( $required === 'true' ) ? 'required' : '' ) . $quantity_html . '>';

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_number', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_number', 'pt_cf_number' );


/**
 * Function to add fields with different types - [paytium_field]
 *
 * @since  1.1.0
 * @author David de Boer
 */
function pt_field( $attributes ) {

	global $counter;

	$attr = shortcode_atts( array (
		'type'             => '',
		'label'            => get_option( 'pt_paytium_field', '' ),
		'placeholder'      => '',
		'amount'           => '',
		'amounts'           => '',
		'tax'              => '',
		'default'          => '',
		'required'         => '',
		'options'          => '',
		'link'             => '',
		'newsletter'       => '',
		'newsletter_label' => '',
		'newsletter_list'  => '',
		'newsletter_group' => '',
		'newsletter_tags' => '',
		'newsletter_after' => '',
		'newsletter_hide_checkbox' => '',
		'user_data' => ''
	), $attributes, 'paytium_field' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_field_' . $counter, 'paytium_field', $attr, false );

	$html = '';

	switch ( $attr['type'] ) {

		default:
		case 'text':

			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Text';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$html .= '<div class="pt-form-group">';
			$html .= '<label for="pt-field-text">' . $label . ':</label><input type="text" id="pt-field-text-' . $counter . '" name="pt-field-text-' . $counter . '" class="pt-field pt-field-text" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' />';
			$html .= '</div>'; // pt-form-group

			break;

		case 'open' :
			$html .= pt_uea_amount( $attributes );
			break;

		case 'label' :
		case 'fixed' :
			$html .= pt_cf_label( $attributes );
			break;

		case 'name':

			$default  = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_name();
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Full name';
			$required = 'required';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$html .= '<div class="pt-form-group">';
			$html .= '<label for="pt-field-name">' . $label . ':</label><input type="text" id="pt-field-name-' . $counter . '" name="pt-field-name-' . $counter . '" class="pt-field pt-field-name" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' />';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
			$html .= '</div>'; // pt-form-group

			break;

		case 'firstname':

			$default  = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_first_name();
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'First name';
			$required = 'required';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$html .= '<div class="pt-form-group">';
			$html .= '<label for="pt-field-firstname">' . $label . ':</label><input type="text" id="pt-field-firstname-' . $counter . '" name="pt-field-firstname-' . $counter . '" class="pt-field pt-field-firstname" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' />';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
			$html .= '</div>'; // pt-form-group

			break;

		case 'lastname':

			$default  = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_last_name();
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Last name';
			$required = 'required';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$html .= '<div class="pt-form-group">';
			$html .= '<label for="pt-field-lastname">' . $label . ':</label><input type="text" id="pt-field-lastname-' . $counter . '" name="pt-field-lastname-' . $counter . '" class="pt-field pt-field-lastname" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' />';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
			$html .= '</div>'; // pt-form-group

			break;

		case 'email':

			$default  = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_email();
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Email';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$newsletter       = $attr['newsletter'];
			$newsletter_label = $attr['newsletter_label'];

			$newsletter_list     = '';
			$newsletter_segments = '';

			// If List is set in shortcode
			if ( ! empty( $attr['newsletter_list'] ) ) {
				$newsletter_list = $attr['newsletter_list'];
			}

			// If Group is set in shortcode
			if ( ! empty( $attr['newsletter_group'] ) ) {
				$newsletter_segments = $attr['newsletter_group'];
			}

			// If Tags are set in shortcode
			if ( ! empty( $attr['newsletter_tags'] ) ) {
				$newsletter_segments = $attr['newsletter_tags'];
			}

			// Option names defined newsletter
			$default_list_id          = 'paytium_' . $newsletter . '_default_list_id';
			$default_group_id         = 'paytium_' . $newsletter . '_default_group_id';
			$default_tags             = 'paytium_' . $newsletter . '_default_tags';
			$after_successful_payment = 'paytium_' . $newsletter . '_after_successful_payment';
			$hide_checkbox            = 'paytium_' . $newsletter . '_hide_checkbox';

			// If List is NOT set in shortcode
			if ( empty( $attr['newsletter_list'] ) ) {
				$newsletter_list = get_option( $default_list_id );
			}

			// If Group is NOT set in shortcode
			if ( empty( $attr['newsletter_group'] ) && $newsletter == 'mailchimp' ) {
				$newsletter_segments = get_option( $default_group_id );
			}

			// If Tags are NOT set in shortcode
			if ( empty( $attr['newsletter_tags'] ) && $newsletter == 'activecampaign' ) {
				$newsletter_segments = get_option( $default_tags );
			}

			// Check for 'subscribe after payment' in shortcode (and convert to integer), otherwise get global option
			if ( ! empty( $attr['newsletter_after'] ) ) {
				$newsletter_after = $attr['newsletter_after'];
				$newsletter_after = ( $newsletter_after === 'true' ) ? 1 : 0; // Convert $newsletter_after to integer for later comparisons
			} else {
				$newsletter_after = get_option( $after_successful_payment );
			}

			// Check for hide checkbox in shortcode (and convert to integer), otherwise get global option
			if ( ! empty( $attr['newsletter_hide_checkbox'] ) ) {
				$newsletter_hide_checkbox = $attr['newsletter_hide_checkbox'];
				$newsletter_hide_checkbox = ( $newsletter_hide_checkbox === 'true' ) ? 1 : 0; // Convert $newsletter_after to integer for later comparisons
			} else {
				$newsletter_hide_checkbox = get_option( $hide_checkbox );
			}

			$html .= '<div class="pt-form-group">';
			$html .= '<label for="pt-field-email">' . $label . ':</label>';
			$html .= '<input type="email" id="pt-field-email-' . $counter . '" name="pt-field-email-' . $counter . '" class="pt-field pt-field-email" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' data-parsley-errors-container="#parsley-errors-list-email-' . $counter . '"  />';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)

			// Add a filter here to allow developers to hook into the form
			$filter_html = '';
			$html .= apply_filters( 'pt_after_email_field', $filter_html, $newsletter, $newsletter_label, $newsletter_list, $newsletter_segments, $newsletter_after, $newsletter_hide_checkbox, $counter );

			$html .= '<div id="parsley-errors-list-email-' . $counter . '"></div>';
			$html .= '</div>'; // pt-form-group

			break;

		case 'textarea' :

			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Comments';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$html .= '<div class="pt-form-group">';
			$html .= '<label for="pt-field-textarea">' . $label . ':</label><textarea id="pt-field-textarea-' . $counter . '" name="pt-field-textarea-' . $counter . '" class="pt-field pt-field-textarea" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' >' . $default . '</textarea>';
			$html .= '</div>'; // pt-form-group

			break;

		case 'radio' :

			// Paid field
			if ( ( isset( $attributes['amounts'] ) && ! empty( $attributes['amounts'] ) ) || ( isset( $attributes['options_are_amounts'] ) && 'true' == $attributes['options_are_amounts'] ) ) {
				$html .= pt_cf_radio( $attributes );
				break;
			}

			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Options';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';
			$options  = ( ! empty( $attr['options'] ) ) ? $attr['options'] : 'No options found.';
			$options = explode( '/', $options );

			$html .= '<div class="pt-form-group">';
			$html .= ( ! empty( $label ) ? '<label for="pt-field-radio">' . $label . ':</label>' : '' );
			$html .= '<div class="pt-radio-group">';


			$i = 1;
			foreach ( $options as $option ) {

				$option = trim( $option );
				$value  = $option;

				if ( empty( $default ) ) {
					$default = $option;
				}

				$html .= '<label title="' . esc_attr( $option ) . '">';
				$html .= '<input type="radio" id="pt-field-radio-' . $counter . '" name="pt-field-radio-' . $counter . '" class="pt-field pt-field-radio" value="' . $option . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required .
				         ( $default == $option ? ' checked="checked"' : ' ' ) . ' >';
				$html .= '<span>' . ( isset( $option_name ) ? $option_name : $option ) . '</span>';
				$html .= '</label>';

				$i ++;
			}

			$html .= '</div>'; //pt-radio-group
			$html .= '</div>'; //pt-form-group

			break;

		case 'checkbox' :

			// Paid field
			if ( ( isset( $attributes['amounts'] ) && ! empty( $attributes['amounts'] ) ) || ( isset( $attributes['options_are_amounts'] ) && 'true' == $attributes['options_are_amounts'] ) ) {
				$html .= pt_cf_checkbox_new( $attributes );
				break;
			}

			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Options';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';
			$options  = ( ! empty( $attr['options'] ) ) ? $attr['options'] : 'No options found.';
			$options = explode( '/', $options );

			$html .= '<div class="pt-form-group">';
			$html .= ( ! empty( $label ) ? '<label for="pt-field-checkbox">' . $label . ':</label>' : '' );
			$html .= '<div class="pt-checkbox-group">';


			$i = 1;
			foreach ( $options as $option ) {

				$option = trim( $option );

				$html .= '<label><input type="checkbox" id="pt-field-checkbox-' . $counter . '" name="pt-field-checkbox-' . $counter . '[]" class="pt-field pt-field-checkbox" value="' . $option;
				$html .= '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" data-parsley-multiple="checkbox" data-parsley-errors-container=".pt-checkbox-group-errors" " ' . $required;
				$html .= ' value="' . $option . '" >';

				$html .= $option;
				$html .= '</label>';

				$i ++;
			}

			$html .= '<div class="pt-checkbox-group-errors"></div>'; //pt-radio-group
			$html .= '</div>'; //pt-checkbox-group
			$html .= '</div>'; //pt-form-group

			break;

		case 'select' :
		case 'dropdown' :

			// Paid field
			if ( ( isset( $attributes['amounts'] ) && ! empty( $attributes['amounts'] ) ) || ( isset( $attributes['options_are_amounts'] ) && 'true' == $attributes['options_are_amounts'] ) ) {
				$html .= pt_cf_dropdown( $attributes );
				break;
			}

			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Options';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';
			$options  = ( ! empty( $attr['options'] ) ) ? $attr['options'] : 'No options found.';
			$options = explode( '/', $options );

			$html .= '<div class="pt-form-group">';
			$html .= ( ! empty( $label ) ? '<label for="pt-field-dropdown">' . $label . ':</label>' : '' );
			$html .= '<select id="pt-field-dropdown-' . $counter . '" name="pt-field-dropdown-' . $counter . '" class="pt-field pt-field-dropdown" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" ' . $required . '> data-pt-user-data="' . $user_data . '"';

			$i = 1;
			foreach ( $options as $option ) {

				$option = trim( $option );

				$html .= '<option';
				$html .= ( ( $required === 'true' ) ? 'required' : '' ) . ' value="' . $option . '" >';

				$html .= $option;

				$i ++;
			}

			$html .= '</select>';
			$html .= '</div>'; //pt-form-group

			break;

		case 'terms' :

			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Terms & Conditions';
			$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
			$link     = ( ! empty( $attr['link'] ) ) ? $attr['link'] : 'No link found.';
			$user_data = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

			$html .= '<div class="pt-form-group">';

			$html .= '<input type="checkbox" id="pt-field-checkbox-' . $counter . '" name="pt-field-checkbox-' . $counter . '[]" class="pt-field pt-field-checkbox" value="' . $label . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ';
			$html .= 'required="" value="' . $label . '" >';

			if ( $link != 'No link found.') {
				$html .= '<a href="' . $link . '" target="_blank">';
				$html .= $label;
				$html .= '</a>';
			} else {
				$html .= $label;
			}

			$html .= '</div>'; //pt-form-group

			break;

	}

	$args = pt_get_args( '', $attr, $counter );

	$counter++;

	return apply_filters( 'pt_paytium_field', $html, $args );

}
add_shortcode( 'paytium_field', 'pt_field' );

/**
 * Function to add the custom user amount textbox via shortcode - [paytium_amount]
 *
 * @since 1.0.0
 */
function pt_uea_amount( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'label'       => get_option( 'pt_uea_label', '' ),
		'placeholder' => '',
		'default'     => '',
		'tax'         => '',
	), $attr, 'paytium_amount' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_amount_' . $counter, 'paytium_amount', $attr, false );

	$html = '';
	$html .= ( ! empty( $label ) ? '<label for="pt_uea_custom_amount_' . $counter . '">' . $label . '</label>' : '' );
	$html .= '<div class="pt-uea-container">';
	$html .= '<span class="pt-uea-currency pt-uea-currency-before"> &euro; </span> ';

	$id = 'pt_items[' . absint( $counter ) . ']';

	// Include inline Parsley JS validation data attributes.
	// http://parsleyjs.org/doc/index.html#psly-validators-list
	$html .= '<input type="text" class="pt-field pt-uea-custom-amount" autocomplete="off" name="' . $id . '[amount]" ';
	$html .= 'id="pt_uea_custom_amount_' . $counter . '" value="' . esc_attr( $default ) . '" placeholder="' . esc_attr( $placeholder ) . '" ';

	// Point to custom container for errors so we can place the non-USD currencies on the right of the input box.
	$html .= 'data-parsley-errors-container="#pt_uea_custom_amount_errors_' . $counter . '">';

	$html .= '<input type="hidden" class="pt-field pt-uea-custom-amount-formatted" name="' . esc_attr( $id ) . '[amount]" value="' . esc_attr(  pt_user_amount_to_float($default) ). '" data-pt-price="' . esc_attr( pt_user_amount_to_float($default) ) . '" />';
	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="open">';

	// Custom validation errors container for UEA.
	// Needs counter ID specificity to match input above.
	$html .= '<div id="pt_uea_custom_amount_errors_' . $counter . '"></div>';

	$html .= '</div>'; //pt-uea-container

	$args = pt_get_args( '', $attr, $counter );
	$counter++;

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_amount', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_amount', 'pt_uea_amount' );


/**
 * Shortcode to output a dropdown list - [paytium_dropdown]
 *
 * @since 1.0.0
 */
function pt_cf_dropdown( $attr ) {

	global $counter;

	global $pt_script_options;

	$attr = shortcode_atts( array (
		'label'                  => '',
		'default'                => '',
		'options'                => '',
		'options_are_quantities' => 'false',
		'amounts'                => '',
		'options_are_amounts'    => 'false', // For backwards compatibility
		'first_option'           => '',
		'first_option_text'      => '',
		'tax'                    => '',
	), $attr, 'paytium_dropdown' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_dropdown_' . $counter, 'paytium_dropdown', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );

	$amount_class = ( ( ! $amounts == false || $options_are_amounts == 'true' ) ? ' pt-cf-amount' : '' );

	$options = explode( '/', $options );

	if ( ! empty( $amounts ) ) {
		$amounts = explode( '/', $amounts );
	}

	if ( ! empty( $amounts ) ) {

		if ( count( $options ) != count( $amounts ) ) {
			Paytium_Shortcode_Tracker::update_error_count();

			if ( current_user_can( 'manage_options' ) ) {
				Paytium_Shortcode_Tracker::add_error_message( '<h6>' . __( 'Your number of options and amounts are not equal.', 'paytium' ) . '</h6>' );
			}

			return '';
		}
	}

	$html = ( ! empty( $label ) ? '<label id="pt-cf-dropdown-label" for="' . esc_attr( $id ) . '">' . $label . ':</label>' : '' );
	$html .= '<select class="pt-form-control pt-cf-dropdown' . $quantity_class . $amount_class . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '[amount]" ' . $quantity_html . '>';

	// Allow users to configure what the first option in an amount dropdown should be

	$first_option_text  = ( ( $first_option_text != '' ) ? $first_option_text : 'Select an amount' );

	if ( $first_option == '' ) {
		$html .= '<option value="' . __( 'Select an amount', 'paytium' ) . '" selected>' . __( 'Select an amount', 'paytium' ) . '</option>';
	}

	if ( $first_option == 'text' ) {
		$html .= '<option value="' . __( $first_option_text, 'paytium' ) . '" selected>' . $first_option_text . '</option>';
	}

	if ( $first_option == 'amount' ) {} // Don't show any extra option in the dropdown

	$i = 1;
	foreach ( $options as $option ) {

		$option = trim( $option );
		$value  = $option;

		if ( $options_are_amounts == 'true' ) {
			$amount = $option;
			$option_name = '&euro; ' . $amount;
			$value = pt_user_amount_to_float( $value );
		} elseif ( ! empty( $amounts ) ) {
			$amount = $amounts[ $i - 1 ];
			$option_name = $option . ' - &euro; ' . $amount;
			$value = pt_user_amount_to_float( $amount );
		}

		if ( empty( $default ) ) {
			$default = $option;
		}

		if ( $default == $option && $options_are_quantities != 'true' && ! empty( $amounts ) ) {
			$pt_script_options['script']['amount'] = $value;
		}

		$html .= '<option value="' . $value . '" data-pt-price="' . esc_attr( $value ) . '">' . ( isset( $option_name ) ? $option_name : $option ) . '</option>';
		$i ++;
	}

	$html .= '</select>';

	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '" data-pt-original-label="' . wp_kses_post( $attr['label'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="select">';

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_dropdown', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_dropdown', 'pt_cf_dropdown' );

/**
 * Shortcode to set subscription details - [paytium_subscription /]
 *
 * @since 1.3.0
 */
function pt_subscription( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'interval'      => '',
		'times'         => '',
		'first_payment' => ''
	), $attr, 'paytium_subscription' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_subscription_' . $counter, 'paytium_subscription', $attr, false );

	$interval      = ( ! empty( $attr['interval'] ) ) ? $attr['interval'] : '';
	$times         = ( ! empty( $attr['times'] ) ) ? $attr['times'] : '';
	$first_payment = ( ! empty( $attr['first_payment'] ) ) ? $attr['first_payment'] : '';

	// TODO: convert Dutch "maanden, weken, dagen" in interval to English
	// TODO: check for correct formats for times and interval, if null but shortcode used, also warn, eg. "times value 12 maand" not recognized

	$html = '<input type="hidden" id="pt-subscription-interval" name="pt-subscription-interval" class="pt-subscription-interval" value="' . $interval . '" data-pt-field-type="pt-subscription-interval" />';
	$html .= '<input type="hidden" id="pt-subscription-times" name="pt-subscription-times" class="pt-subscription-times" value="' . $times . '" data-pt-field-type="pt-subscription-times" />';

	if ( !empty($first_payment) ) {
		$html .= '<input type="hidden" id="pt-subscription-first-payment" name="pt-subscription-first-payment" class="pt-subscription-first-payment" value="' . $first_payment . '" data-pt-field-type="pt-subscription-first-payment" />';
	}

	$args = pt_get_args( '', $attr, $counter );

	$counter++; // Increment static counter

	return apply_filters( 'pt_subscription', $html, $args );

}
add_shortcode( 'paytium_subscription', 'pt_subscription' );

/**
 * Shortcode to output a number box - [paytium_radio]
 *
 * @since 1.0.0
 */
function pt_cf_radio( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'id'                     => '',
		'label'                  => '',
		'default'                => '',
		'options'                => '',
		'options_are_quantities' => 'false',
		'amounts'                => '',
		'options_are_amounts'    => 'false',
		'tax'                    => '',
	), $attr, 'paytium_radio' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_radio_' . $counter, 'paytium_radio', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$options = explode( '/', $options );

	if ( ! empty( $amounts ) ) {
		$amounts = explode( '/', str_replace( ' ', '', $amounts ) );//

		if ( count( $options ) != count( $amounts ) ) {
			Paytium_Shortcode_Tracker::update_error_count();

			if ( current_user_can( 'manage_options' ) ) {
				Paytium_Shortcode_Tracker::add_error_message( '<h6>' . __( 'Your number of options and amounts are not equal.', 'paytium' ) . '</h6>' );
			}

			return '';
		}
	}

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );
	$amount_class = ( ( ! $amounts == false || $options_are_amounts == 'true' ) ? ' pt-cf-amount' : '' );

	$html = ( ! empty( $label ) ? '<label id="pt-cf-radio-label">' . $label . ':</label>' : '' );

	$html .= '<div class="pt-radio-group">';

	$i = 1;
	foreach ( $options as $option ) {

		$option = trim( $option );
		$value  = $option;

		if ( $options_are_amounts == 'true' ) {
			$amount = $option;
			$option_name = '&euro; ' . $amount;
			$value = pt_user_amount_to_float( $value );
		} elseif ( ! empty( $amounts ) ) {
			$amount = $amounts[ $i - 1 ];
			$option_name = $option . ' - &euro; ' . $amount;
			$value = pt_user_amount_to_float( $amount );
		}

		if ( empty( $default ) ) {
			$default = $option;
		}

		if ( $default == $option && $options_are_quantities != 'true' && ! empty( $amounts ) ) {
			$pt_script_options['script']['amount'] = $value;
		}

		// Don't use built-in checked() function here for now since we need "checked" in double quotes.
		$html .= '<label title="' . esc_attr( $option ) . '" >';
		$html .= '<input type="radio" name="' . esc_attr( $id ) . '[amount]" value="' . $value . '" ' .
		         'data-pt-price="' . esc_attr( $value ) . '" ' . checked( $default, $option, false ) .
		         ' class="' . esc_attr( $id ) . '_' . $i . $quantity_class . $amount_class . '" data-parsley-errors-container=".pt-form-group" ' . $quantity_html . '>';
		$html .= '<span>' . ( isset( $option_name ) ? $option_name : $option ) . '</span>';
		$html .= '</label>';
    
		$i ++;
	}

	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '" data-pt-original-label="' . wp_kses_post( $attr['label'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="radio">';

	$html .= '</div>'; //pt-radio-group

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_radio', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_radio', 'pt_cf_radio' );


/**
 * Shortcode to output a label- [paytium_field type='label']
 *
 * @since 1.0.0
 */
function pt_cf_label( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (
		'label'  => '',
		'amount' => '',
		'tax'    => '',
	), $attr, 'paytium_type' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_label_' . $counter, 'paytium_label', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';
	$html = ( ! empty( $attr['label'] ) ? '<label for="' . esc_attr( $id ) . '[value]">' . wp_kses_post( $attr['label'] ) . '</label>' : '' );

	// Paid label
	if ( ! empty( $amount ) ) {
		$html .= '<input type="hidden" class="pt-cf-amount pt-cf-label-amount" name="' . esc_attr( $id ) . '[amount]" value="' . pt_user_amount_to_float( $attr['amount'] ) . '" ' . 'data-pt-price="' . pt_user_amount_to_float( $attr['amount'] ) . '" />';
		$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '">';
		$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
		$html .= '<input type="hidden" name="' . $id . '[type]" value="label">';
	}

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

	return '<div class="pt-form-group">' . apply_filters( 'pt_paytium_label', $html, $args ) . '</div>';

}

/**
 * Shortcode to enable Paytium Links - [paytium_links /]
 *
 */
function pt_paytium_links( $attr ) {

	global $counter;

	extract( shortcode_atts( array(), $attr ) );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_links_' . $counter, 'paytium_links', $attr, false );

	$auto_redirect = ( ! empty( $attr[0] ) ) ? $attr[0] : '';

	// Add if Paytium Links is set/used
	$html = '<input type="hidden" id="pt-paytium-links" name="pt-paytium-links" class="pt-paytium-links" value="" data-pt-field-type="pt-paytium-links" />';

	// Add if Paytium Links Auto Redirect is set/used
	if ( $auto_redirect === 'auto_redirect' ) {
		$html = '<input type="hidden" id="pt-paytium-links-auto-redirect" name="pt-paytium-links-auto-redirect" class="pt-paytium-links-auto-redirect" value="" data-pt-field-type="pt-paytium-links-auto-redirect" />';

	}

	$args = pt_get_args( '', $attr, $counter );

	$counter++; // Increment static counter

	return apply_filters( 'pt_paytium_links', $html, $args );

}
add_shortcode( 'paytium_links', 'pt_paytium_links' );

/**
 * Shortcode to create 'regular' forms without payment - [paytium_no_payment /]
 *
 */
function pt_paytium_no_payment( $attr ) {

	global $counter;

	$attr = shortcode_atts( array (), $attr, 'paytium_no_payment' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_no_payment_' . $counter, 'paytium_no_payment', $attr, false );

	$html = '<input type="hidden" id="pt-paytium-no-payment" name="pt-paytium-no-payment" class="pt-paytium-no-payment" value="1" data-pt-field-type="pt-paytium-no-payment" />';

	$args = pt_get_args( '', $attr, $counter );

	$counter++; // Increment static counter

	return apply_filters( 'pt_paytium_no_payment', $html, $args );

}
add_shortcode( 'paytium_no_payment', 'pt_paytium_no_payment' );

/**
 * Function to set the id of the args array and return the modified array
 */
function pt_get_args( $id = '', $args = array (), $counter = '' ) {

	if ( ! empty( $id ) ) {
		$args['id'] = $id;
	}

	if ( ! empty( $counter ) ) {
		$args['unique_id'] = $counter;
	}

	return $args;

}
