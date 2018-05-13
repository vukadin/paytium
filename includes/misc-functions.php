<?php

/**
 * Misc plugin functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Common method to set Mollie API key from options.
 *
 * @since 1.0.0
 */
function pt_set_paytium_key( $test_mode = 'false' ) {

	global $pt_mollie;

	// Check whether existing payment mode is sent and convert to true or false
	if ( $test_mode == 'live' ) {
		$test_mode = 'false';
	}

	if ( $test_mode == 'test' ) {
		$test_mode = 'true';
	}

	// Check first if in live or test mode.
	if ( get_option( 'paytium_enable_live_key', false ) == 1 && $test_mode != 'true' ) {
		$key = get_option( 'paytium_live_api_key', '' );
	} else {
		$key = get_option( 'paytium_test_api_key', '' );
	}

	try {
		$pt_mollie->setApiKey( $key );
	}
	catch ( Mollie_API_Exception $e ) {
		echo 'Your API key is incorrect or missing! Please check the Paytium settings! Error message(s): ' . htmlspecialchars( $e->getMessage() ) . '<br />';
	}

}

/**
 * Get the Mollie API key.
 *
 * @since 1.5.0
 *
 * @param string $mode
 * @return mixed|void
 */
function pt_get_api_key( $mode = 'live' ) {
	// Check if in test mode or live mode
	if ( get_option( 'paytium_enable_live_key' ) == 1 && $mode === 'live' ) {
		$data_key = get_option( 'paytium_live_api_key', '' );
	} else {
		$data_key = get_option( 'paytium_test_api_key', '' );
	}
	return $data_key;
}

/**
 * Log debug messages for Paytium
 *
 * @since   1.0.0
 */
function pt_log_me( $message ) {

	if ( WP_DEBUG === true ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( print_r( $message, true ) );
		} else {
			error_log( $message );
		}
	}

}


/**
 * Convert amount to float.
 *
 * Convert a amount to a valid float amount. Used for storing in the DB for example.
 *
 *
 * @since 1.5.0
 *
 * @param string|float $amount
 * @return float Converted amount.
 */
function pt_user_amount_to_float( $amount ) {
	$decimals = apply_filters( 'paytium_amount_decimals', 2 );
	$amount = floatval( str_replace( ',', '.', $amount ) );
	$amount = round( $amount, $decimals );

	return $amount;
}


/**
 * Currency formatted amount.
 *
 * Get the passed amount as a currency formatted amount.
 *
 * @since 1.5.0
 *
 * @param float $amount Amount to format.
 * @return string Formatted amount.
 */
function pt_float_amount_to_currency( $amount ) {
	$decimals = apply_filters( 'paytium_amount_decimals', 2 );
	$thousands_separator = apply_filters( 'paytium_thousands_separator', '.' );
	$decimal_separator = apply_filters( 'paytium_decimal_separator', ',' );

	$amount = pt_user_amount_to_float( $amount );
	$amount = number_format( $amount, $decimals, $decimal_separator, $thousands_separator );

	return '&euro; ' . $amount;
}

/**
 * Google Analytics campaign URL.
 *
 * @since   1.0.0
 *
 * @param  string $base_url Plain URL to navigate to
 * @param  string $source   GA "source" tracking value
 * @param  string $medium   GA "medium" tracking value
 * @param  string $campaign GA "campaign" tracking value
 *
 * @return string $url      Full Google Analytics campaign URL
 */
function pt_ga_campaign_url( $base_url, $source, $medium, $campaign ) {

	// $medium examples: 'sidebar_link', 'banner_image'

	$url = esc_url( add_query_arg( array (
		'utm_source'   => $source,
		'utm_medium'   => $medium,
		'utm_campaign' => $campaign
	), $base_url ) );

	return $url;

}

/**
 * Filters the content to remove any extra paragraph or break tags
 * caused by shortcodes.
 *
 * @since 1.0.0
 *
 * @param  string $content String of HTML content.
 *
 * @return string $content Amended string of HTML content.
 *
 * REF: https://thomasgriffin.io/remove-empty-paragraph-tags-shortcodes-wordpress/
 */
function pt_shortcode_fix( $content ) {

	$array = array (
		'<p>['    => '[',
		']</p>'   => ']',
		']<br />' => ']'
	);

	return strtr( $content, $array );

}


add_filter( 'the_content', 'pt_shortcode_fix' );

/**
 * Is WordPress currently on localhost?
 *
 * @since   1.0.0
 * @author  David de Boer
 */
function pt_is_localhost() {

	$whitelist = array ( '127.0.0.1', '::1' );
	if ( in_array( $_SERVER['REMOTE_ADDR'], $whitelist ) ) {
		return true;
	}

}

/**
 * Prefill email field if user is logged in
 *
 * @since   1.1.0
 * @author  David de Boer
 */

function pt_prefill_email() {

	if ( is_user_logged_in() ) {
		$prefill = get_userdata( get_current_user_id() )->user_email;
	} else {
		$prefill = '';
	}

	return $prefill;
}

/**
 * Prefill name field if user is logged in
 *
 * @since   1.5.0
 * @author  David de Boer
 */

function pt_prefill_name() {

	if ( is_user_logged_in() ) {
		$prefill = get_userdata( get_current_user_id() )->user_firstname . ' ' . get_userdata( get_current_user_id() )->user_lastname;
	} else {
		$prefill = '';
	}

	return $prefill;
}

/**
 * Prefill first name field if user is logged in
 *
 * @since   1.5.0
 * @author  David de Boer
 */

function pt_prefill_first_name() {

	if ( is_user_logged_in() ) {
		$prefill = get_userdata( get_current_user_id() )->user_firstname;
	} else {
		$prefill = '';
	}

	return $prefill;
}

/**
 * Prefill last name field if user is logged in
 *
 * @since   1.5.0
 * @author  David de Boer
 */

function pt_prefill_last_name() {

	if ( is_user_logged_in() ) {
		$prefill = get_userdata( get_current_user_id() )->user_lastname;
	} else {
		$prefill = '';
	}

	return $prefill;
}

/**
 * Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
 *
 * @since   2.1.0
 * @author  David de Boer
 */

function pt_prefill_warning( $counter ) {

	if ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) {
		$html = '<span class="pt-field-prefill-warning pt-field-prefill-warning-hint" data-pt-prefill-warning-counter="' . $counter . '">' . __( 'Why do I see my own name/email?', 'paytium' ) . '</span>';
		$html .= '<span class="pt-field-prefill-warning pt-field-prefill-warning-explanation" id="pt-prefill-warning-counter-' . $counter . '">' . __( 'If a user is logged in to WordPress (like you are now), the above field will automatically fill in the name/email of that user. Others will not see your name/email, only their own, and only if they are logged in. This text is only shown to editors and administrators.', 'paytium' ) . '</span>';

		return $html;
	}

}

/**
 * Get a list of payments.
 *
 * Get a list with payments from the database.
 *
 * @since 1.0.0
 *
 * @param  array $args List of WP_Query arguments.
 *
 * @return array       WP_Query result.
 */
function pt_get_payments( $args = array () ) {

	$payment_args = wp_parse_args( $args, array (
		'post_type'     => 'pt_payment',
		'post_status'   => 'publish',
		'posts_per_page' => -1,
		'fields'        => 'ids',
	) );

	$posts         = new WP_Query( $payment_args );
	$payment_posts = $posts->posts;

	return $payment_posts;

}

/**
 * Check if site has live payments.
 *
 * @since 1.5.0
 *
 * @param  array $args List of WP_Query arguments.
 *
 * @return bool       true or false
 */
function pt_has_live_payments( $args = array () ) {

	$payment_args = wp_parse_args( $args, array (
		'post_type'   => 'pt_payment',
		'post_status' => 'publish',
		'orderby' => 'id',
		'order'   => 'DESC',

		'meta_query' => array (
			array (
				'key'     => '_mode',
				'value'   => 'live',
				'compare' => '='
			)
		),

		'fields'        => 'ids',
		'posts_per_page' => 1,
	) );

	$posts = new WP_Query( $payment_args );

	return $posts->have_posts();

}