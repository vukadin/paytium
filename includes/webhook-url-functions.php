<?php

/**
 * Webhook URL functions
 *
 * @since   1.5.0
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Webhook request.
 *
 * When the current request is a Mollie webhook request, process accordingly and bail early.
 *
 * @since 1.0.0
 *
 * @param  mixed $request WP request.
 *
 * @return mixed          WP request when the current request isn't a PT request.
 */
function pt_payment_update_webhook( $request ) {

	if ( isset( $_GET['pt-webhook'] ) ) {

		global $pt_mollie;
		try {

			$mollie_payment_id = preg_replace('/[^\w]/', '', $_POST['id']);

			error_log( 'Webhook call for ' . $mollie_payment_id );

			pt_set_paytium_key( false );
			$mollie = $pt_mollie->payments->get( $mollie_payment_id );

			// TODO: is payment known by ID
			$payment = pt_get_payment_by_payment_id( $mollie_payment_id );


			//
			// START UNKNOWN PAYMENT PROCESSING - maybe it's a renewal payment for a subscription?
			//

			if ( $payment == null ) {

				// Is there a subscription ID?

				if ( $mollie->subscriptionId == null ) {
					error_log( 'Webhook call for ' . $mollie->id . '. No known payment, no subscriptionId!' );
					exit();
				}

				$first_payment = pt_get_payment_by_subscription_id( $mollie->subscriptionId );

				if ( $first_payment == null ) {
					error_log( 'No payment or subscription found for Mollie payment ' . $mollie->id . '.' );
					exit();
				}

				// TODO: one single function to copy payment, or create payment from subscription

				$customer = $pt_mollie->customers->get( $mollie->customerId );

				// TODO: Get subscription_payment_status and add (in case of failed payments?), add to first payment? Wait for Mollie answer 2017-02-08
				$old_payment_post_id = pt_create_payment( array (
					'subscription'                => $first_payment->subscription,
					'subscription_interval'       => $first_payment->subscription_interval,
					'subscription_times'          => $first_payment->subscription_times,
					'subscription_payment_status' => 'renewal',
					'subscription_status'         => $mollie->status,
					'order_status'                => 'new',
					'amount'                      => $mollie->amount,
					'status'                      => $mollie->status,
					'payment_id'                  => $mollie->id,
					'payment_key'                 => null,
					'subscription_id'             => $mollie->subscriptionId,
					'method'                      => $mollie->method,
					'description'                 => $mollie->description,
					'pt-customer-id'              => $mollie->customerId,
					'pt-customer-email'           => $customer->email,
					'pt-customer-name'            => $customer->name,
					'mode'                        => $mollie->mode,
					'meta'                        => array ()
				) );

				$meta = get_post_meta( $first_payment->id, null, true );
				$meta = pt_copy_field_data( $meta );

				foreach ( $meta as $meta_key => $meta_value ) {
					update_post_meta( $old_payment_post_id, $meta_key, $meta_value );
				}

				// David - Paytium 1.4.1
				// Get latest payment details with the updated payment status
				$payment_latest = pt_get_payment_by_payment_id( $mollie->id );
				// Add hook after webhook processing of subscription renewal payments
				do_action( 'paytium_webhook_subscription_renewal_payment', $payment_latest );

				exit( header( 'Status: 200 OK' ) );
			}

			//
			// END UNKNOWN PAYMENT PROCESSING - maybe it's a renewal payment for a subscription?
			//

			//
			// START REGULAR PAYMENT PROCESSING - Update status and payment method for regular payments.
			//
			$payment->set_status( $mollie->status );
			$payment->set_payment_method( $mollie->method );

			//
			// END REGULAR PAYMENT PROCESSING - Update status and payment method for regular payments.
			//

			//
			// START SUBSCRIPTION PROCESSING - Try to create a subscription if all conditions are met
			//

			try {
				if ( $payment->subscription == 1 ) {

					// If payment is not paid, do not process subscription,
					// even is there is a valid mandate.
					// That's because Paytium uses the first amount and start date as a workaround
					// to get a quick payment for the first installment of the subscription.
					// So no paid first payment, means no subscription!

					// If payment is not paid, don't continue, don't create subscription
					if ( $mollie->status != 'paid' ) {
						throw new Mollie_API_Exception ('Not a first payment or status not paid, don\'t create a subscription.');
					}

					// Check that payment doesn't belong to a subscription (at Mollie) (charged_back)
					if ( $mollie->subscriptionId != null ) {
						error_log( 'Payment' . $mollie->id . ': has a subscriptionId (' . $mollie->subscriptionId . '), don\'t create a new subscription.' );
						exit();
					}

					// Check and set payment mode
					$test_mode = ( $payment->mode == 'test' ) ? 'true' : 'false';
					pt_set_paytium_key( $test_mode );

					// Check if the customer has a valid mandate
					$valid_mandate = pt_does_customer_have_valid_mandate( $payment->customer_id );

					// Deviate from Mollie instructions (process mandate with status pending or valid)
					// Because in Paytium, customers always place a first payment, to get a valid mandate
					// Otherwise they don't get a subscription at all.
					// Paytium does not accept pending mandates or subscriptions.
					if ( $valid_mandate == false ) {
						throw new Mollie_API_Exception ('Mandate was invalid, subscription not created.');
					}

					error_log( 'valid mandate: ' . $valid_mandate );
					error_log( 'subscription_interval: ' . $payment->subscription_interval );
					error_log( 'subscription_times: ' . $payment->subscription_times );

					$webhook_url  = add_query_arg( 'pt-webhook', 'paytium', home_url( '/' ) );

					// Get correct amount
					if ( $payment->subscription_first_payment !== '' ) {
						$subscription_recurring_payment = $payment->subscription_recurring_payment;
					} else {
						$subscription_recurring_payment = $payment->payment_amount;
					}

					// Create the subscription at Mollie
					$subscription = $pt_mollie->customers_subscriptions->withParentId( $payment->customer_id )->create( array (
						"amount"      => $subscription_recurring_payment,
						"times"       => $payment->subscription_times,
						"interval"    => $payment->subscription_interval,
						"startDate"   => $payment->subscription_start_date,
						"description" => $payment->description,
						"webhookUrl"  => $webhook_url
					) );

					error_log( 'New subscription created, ID via webhook: ' . $subscription->id );

					// Set $subscription_webhook to 1 if the webhook is registered at Mollie
					$subscription_webhook = 0;
					if ( $subscription->links->webhookUrl != null ){
						$subscription_webhook = 1;
					}

					if ( $subscription->status == 'active' ) {
						$subscription_payment_status = 'initial';
					} else {
						$subscription_payment_status = $subscription->status;
					}

					// Save new subscription details to Payment post meta
					$new_payment_details = ( array (
						'subscription_id'             => $subscription->id,
						'subscription_webhook'        => $subscription_webhook,
						'subscription_payment_status' => $subscription_payment_status,
						'subscription_status'         => $subscription->status
					) );
					pt_update_payment_meta( $payment->id, $new_payment_details );

					// David - Paytium 1.4.1
					// Get latest payment details with the updated payment status
					$payment_latest = pt_get_payment_by_payment_id( $mollie->id );
					// Add hook after webhook processing of subscription first payment
					do_action( 'paytium_webhook_subscription_first_payment', $payment_latest );

				}

			} catch ( Mollie_API_Exception $e ) {

				// Save error for failed subscription to database with other payment details
				$new_payment_details = ( array (
					'subscription_error' => str_replace( 'Error executing API call (request): ', '', htmlspecialchars( $e->getMessage() )),
					'subscription_payment_status' => 'failed'

				) );
				pt_update_payment_meta( $payment->id, $new_payment_details );

				error_log( 'Processing subscription failed: ' . htmlspecialchars( $e->getMessage() ) );
			}

			//
			// END SUBSCRIPTION PROCESSING - Try to create a subscription if all conditions are met
			//

			// David - Paytium 1.4.0
			// Get latest payment details with the updated payment status
			$payment_latest = pt_get_payment_by_payment_id( $mollie->id );

			// Add hook after webhook processing of any payment
			do_action( 'paytium_after_pt_payment_update_webhook', $payment_latest );

			// Finish and tell Mollie it all succeeded.
			exit( header( 'Status: 200 OK' ) );

		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
			exit( header( 'Status: 400 Bad Request' ) );
		}

	}

	return $request;

}

add_filter( 'request', 'pt_payment_update_webhook' );

/**
 * In the field data, find the field name and email to use a customer for Mollie Customers API
 *
 * @since 1.2.0
 *
 */
function pt_does_customer_have_valid_mandate( $customer_id ) {

	global $pt_mollie;

	$mandates = (array) $pt_mollie->customers_mandates->withParentId( $customer_id )->all();

	$valid_mandate = false;

	foreach ( $mandates as $key => $mandate ) {

		if ( $mandate->status == 'valid' ) {
			$valid_mandate = true;
			break;

		} else {
			$valid_mandate = false;
		}
	}

	return $valid_mandate;
}