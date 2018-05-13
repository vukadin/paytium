<?php

/**
 * Paytium Users
 *
 * @package     PT/Users
 * @author      David de Boer <david@davdeb.com>
 * @license     GPL-2.0+
 * @link        http://www.paytium.nl
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create/update WordPress user and store user data as user meta in WordPress
 *
 *
 * @since 2.1.0
 */
function paytium_user_data_processing( $payment_post_id ) {

	if ( is_object( $payment_post_id ) ) {
		$payment_post_id = $payment_post_id->id;
	}

	// Get payment post meta
	$payment_field_data = get_post_meta( $payment_post_id, null, true );

	// Check if there are user data fields in the payment
	if ( count( preg_grep( '~\b-user-data\b~', array_keys( $payment_field_data ) ) ) < 1 ) {
		return;
	}

	// Only continue if status is paid
	$status = $payment_field_data['_status'][0];

	if ( ( $status != 'paid' ) ) {
		return;
	}

	// Start adding user data fields to user data array
	$user_data[ $payment_post_id ] = array ();

	// Store payment ID (a.k.a post ID) in payment data too
	$user_data[ 'payment_data' ]['paytium_payment_id'] = $payment_post_id;

	foreach ( $payment_field_data as $key => $value ) {

		// Get field group ID and use it as array key
		$group_id = preg_replace( '/[^0-9]+/', '', $key );

		if ( strstr( $key, '_pt-field-email' ) ) {
			$user_data['user-email'] = 'true';
		}

		// Add these elements to user data too
		$exclude_meta = array (
			'paytium_customer_id'        => '_pt-customer-id',
			'paytium_payment_id'         => 'payment_post_id',
			'paytium_mollie_transaction' => '_payment_id', // Mollie transaction ID
			'payment_amount'             => '_amount',
			'paytium_description'        => '_description',
			'paytium_payment_mode'       => '_payment_mode',
		);
		if ( in_array( $key, $exclude_meta ) ) {
			$user_data['payment_data'][ array_search($key, $exclude_meta) ] = $value[0];
		}

		// Add user data fields to user data array (for user meta)
		if ( strstr( $key, '_pt-field-' ) ) {

			// Convert label to a valid key that can be used for user meta (and prefix with paytium_)
			$label = 'paytium_' . sanitize_key( str_replace( ' ', '_', $value[0] ) );

			if ( $group_id !== '' ) {

				if ( strstr( $key, $group_id ) == $group_id ) {
					$user_data[ $group_id ]['key']   = $key;
					$user_data[ $group_id ]['value'] = $value[0];
				} elseif ( strstr( $key, $group_id ) == $group_id . '-label' ) {
					$user_data[ $group_id ]['label'] = $label;
				} elseif ( strstr( $key, $group_id ) == $group_id . '-user-data' ) {
					$user_data[ $group_id ]['user-data'] = $value[0];
				}
			}
		}

	}

	// If there is no user data in an array, what's the point? Unset it!
	if ( count( preg_grep( '~\buser-email\b~', array_keys( $user_data ) ) ) < 1 ) {
		unset( $user_data );
	} else {
		unset( $user_data['user-email'] );
	}

	// Validate and clean user data array
	foreach ( $user_data as $key => $value ) {

		if( $key == 'payment_data' ) {
			continue;
		};

		// If there is no user data in an array, what's the point? Unset it!
		if ( count( preg_grep( '~\buser-data\b~', array_keys( $user_data[ $key ] ) ) ) < 1 ) {
			unset( $user_data[ $key ] );
			continue;
		}

	}

	//
	// Process user data array and move to new user array
	//

	// Create a user array with all details
	$user = array ();

	foreach ( $user_data as $key => $value ) {

		if ( $key == 'payment_data' ) {
			$user['payment_data'] = $value;
			continue;
		}
		if ( strstr( $value['key'], '_pt-field-email' ) ) {
			$user['email'] = $value['value'];
			continue;
		}

		if ( strstr( $value['key'], '_pt-field-name' ) ) {
			$user['name'] = $value['value'];
			continue;
		}

		$user['user_meta'][ $key ] = $user_data[ $key ];

	}

	// Move payment data to bottom of user array
	$payment_data = $user['payment_data'];
	unset($user['payment_data']);
	$user['payment_data'] = $payment_data;

	//
	// Start creating WordPress user
	//

	// Check if there are any users with the billing email as username or email
	$email = email_exists( $user['email'] );

	// Create a user if email is null or false
	if ( $email === false ) {

		// Random password with 12 chars
		$random_password = wp_generate_password();

		// Create new user with email as username & newly created pw
		$user_id = wp_insert_user( array (
			'user_login' => $user['email'],
			'display_name' => $user['name'],
			'user_pass'   => $random_password,
			'user_email' => $user['email']
		) );

		if( is_wp_error( $user_id) ) {
			// TODO: add this message to the new logger for Paytium
			error_log( $user_id->get_error_message());
		} else {
			wp_new_user_notification( $user_id, null, 'both' );
		}

	} else {
		$user_id = email_exists( $user['email'] );
	}

	// Get WordPress user data
	$wp_user_data = get_userdata( $user_id );

	// Add form data as user meta
	foreach ( $user['user_meta'] as $key => $val ) {

		update_user_meta( $wp_user_data->ID, $val['label'], $val['value'] );

	}

	// Add payment data as user meta
	foreach ( $user['payment_data'] as $key => $val ) {

		update_user_meta( $wp_user_data->ID, $key, $val );

	}

}

add_action( 'paytium_after_full_payment_saved', 'paytium_user_data_processing', 10, 3 );
add_action( 'paytium_after_pt_payment_update_webhook', 'paytium_user_data_processing', 10, 3 );
add_action( 'paytium_after_update_payment_from_admin', 'paytium_user_data_processing', 10, 3);