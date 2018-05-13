<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Payment class.
 *
 * Payment class is a API for a single payment in Paytium.
 *
 * @class          PT_Payment
 * @version        1.0.0
 * @author         Jeroen Sormani
 */
class PT_Payment {

	/**
	 * @since 1.0.0
	 * @var int Payment
	 */
	public $id;

	/**
	 * @since 1.0.0
	 * @var int Payment amount in cents.
	 */
	public $payment_amount;

	/**
	 * @since 1.0.0
	 * @var string  Payment status slug.
	 */
	public $status;

	/**
	 * @since 1.0.0
	 * @var string Order status.
	 */
	public $order_status;

	/**
	 * @since 1.0.0
	 * @var string  Payment date.
	 */
	public $payment_date;

	/**
	 * @since 1.0.3
	 * @var string  Mollie transaction ID.
	 */
	public $transaction_id;

	/**
	 * @since 1.0.0
	 * @var string Payment method.
	 */
	public $payment_method;

	/**
	 * @since 1.0.0
	 * @var string  Payment description
	 */
	public $description;

	/**
	 * @since 1.3.0
	 * @var string  Subscription
	 */
	public $subscription;

	/**
	 * @since 1.3.0
	 * @var string  Subscription
	 */
	public $subscription_id;

	/**
	 * @since 1.3.0
	 * @var string  Subscription interval
	 */
	public $subscription_interval;

	/**
	 * @since 1.3.0
	 * @var string  Subscription times
	 */
	public $subscription_times;

	/**
	 * @since 2.1.0
	 * @var string  Subscription first amount
	 */
	public $subscription_first_payment;

	/**
	 * @since 2.1.0
	 * @var string  Subscription recurring amount
	 */
	public $subscription_recurring_payment;

	/**
	 * @since 1.4.0
	 * @var string  Subscription start date
	 */
	public $subscription_start_date;

	/**
	 * @since 1.4.0
	 * @var string  Subscription payment status
	 */
	public $subscription_payment_status;

	/**
	 * @since 1.4.0
	 * @var string  Subscription webhook
	 */
	public $subscription_webhook;

	/**
	 * @since 1.4.0
	 * @var string  Subscription error
	 */
	public $subscription_error;

	/**
	 * @since 2.0.0
	 * @var string  Subscription status
	 */
	public $subscription_status;

	/**
	 * @since 2.0.0
	 * @var string  Subscription cancelled date
	 */
	public $subscription_cancelled_date;

	/**
	 * @since 1.3.0
	 * @var string  Customer ID
	 */
	public $customer_id;

	/**
	 * @since 1.3.0
	 * @var string  mode
	 */
	public $mode;

	/**
	 * @since 1.5.0
	 * @var string  no_payment
	 */
	public $no_payment;

	/**
	 * @since 1.1.0
	 * @var string  Field data
	 */
	public $field_data = array();


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id pt_payment Post ID.
	 */
	public function __construct( $post_id ) {

		$this->id = absint( $post_id );

		if ( 'pt_payment' != get_post_type( $this->id ) ) {
			return false;
		}

		$this->populate();
		return null;

	}


	/**
	 * Populate payment.
	 *
	 * Populate the payment class with the related data.
	 *
	 * @since 1.0.0
	 */
	public function populate() {

		$meta = get_post_meta( $this->id, null, true );

		$this->payment_amount = isset( $meta['_amount'] ) ? reset( $meta['_amount'] ) : '';
		$this->status         = isset( $meta['_status'] ) ? reset( $meta['_status'] ) : '';
		$this->order_status   = isset( $meta['_order_status'] ) ? reset( $meta['_order_status'] ) : '';
		// Mollie transaction ID is called "payment_id" in DB, that's not correct, its the transaction ID
		$this->transaction_id = isset( $meta['_payment_id'] ) ? reset( $meta['_payment_id'] ) : '';
		$this->payment_date   = get_post_field( 'post_date', $this->id );
		$this->payment_method = isset( $meta['_method'] ) ? reset( $meta['_method'] ) : '';
		$this->description    = isset( $meta['_description'] ) ? reset( $meta['_description'] ) : '';

		$this->subscription                   = isset( $meta['_subscription'] ) ? reset( $meta['_subscription'] ) : '';
		$this->subscription_id                = isset( $meta['_subscription_id'] ) ? reset( $meta['_subscription_id'] ) : '';
		$this->subscription_interval          = isset( $meta['_subscription_interval'] ) ? reset( $meta['_subscription_interval'] ) : '';
		$this->subscription_times             = isset( $meta['_subscription_times'] ) ? reset( $meta['_subscription_times'] ) : '';
		$this->subscription_first_payment     = isset( $meta['_subscription_first_payment'] ) ? reset( $meta['_subscription_first_payment'] ) : '';
		$this->subscription_recurring_payment = isset( $meta['_subscription_recurring_payment'] ) ? reset( $meta['_subscription_recurring_payment'] ) : '';
		$this->subscription_start_date        = isset( $meta['_subscription_start_date'] ) ? reset( $meta['_subscription_start_date'] ) : '';
		$this->subscription_payment_status    = isset( $meta['_subscription_payment_status'] ) ? reset( $meta['_subscription_payment_status'] ) : '';
		$this->subscription_status            = isset( $meta['_subscription_status'] ) ? reset( $meta['_subscription_status'] ) : '';
		$this->subscription_cancelled_date    = isset( $meta['_subscription_cancelled_date'] ) ? reset( $meta['_subscription_cancelled_date'] ) : '';
		$this->subscription_webhook           = isset( $meta['_subscription_webhook'] ) ? reset( $meta['_subscription_webhook'] ) : '';
		$this->subscription_error             = isset( $meta['_subscription_error'] ) ? reset( $meta['_subscription_error'] ) : '';

		$this->customer_id = isset( $meta['_pt-customer-id'] ) ? reset( $meta['_pt-customer-id'] ) : '';
		$this->mode        = isset( $meta['_mode'] ) ? reset( $meta['_mode'] ) : '';

		$this->no_payment = isset( $meta['_pt_no_payment'] ) ? reset( $meta['_pt_no_payment'] ) : '';

		$this->field_data = $meta;

	}


	/**
	 * Get the payment amount.
	 *
	 * Get the payment amount in a nice format with decimals, without currency symbol.
	 *
	 * @since 1.0.0
	 *
	 * @return float Payment amount.
	 */
	public function get_amount() {
		return $this->payment_amount;
	}


	/**
	 * Get tax total.
	 *
	 * @since 1.5.0
	 *
	 * @return float
	 */
	public function get_tax_total() {
		$tax_total = 0;

		foreach ( $this->get_items() as $item ) {
			$tax_total += $item->get_tax_amount();
		}

		return $tax_total;
	}

	/**
	 * Get tax total.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_taxes_per_percentage() {
		$taxes = array();

		foreach ( $this->get_items() as $item ) {
			if ( ! isset( $taxes[ $item->get_tax_percentage() ] ) ) {
				$taxes[ $item->get_tax_percentage() ] = 0;
			}
			$taxes[ $item->get_tax_percentage() ] += $item->get_tax_amount();
		}

		return $taxes;
	}


	/**
	 * Get total amount.
	 *
	 * @since 1.5.0
	 *
	 * @return int|mixed
	 */
	public function get_total() {
		$total = 0;

		foreach ( $this->get_items() as $item ) {
			$total += $item->get_total_amount();
		}

		return $total;
	}


	/**
	 * Get purchased items.
	 *
	 * Get the items the customer has purchased. Returns PT_Item objects.
	 *
	 * @since 1.5.0
	 *
	 * @return PT_Item[] List of items purchased.
	 */
	public function get_items() {

		$items = array();

		$i = 1;
		while ( isset( $this->field_data['_item-' . $i . '-amount'] ) ) {
			$items[] = new PT_Item( $this, $i );
			$i++;
		}

		// BC for the old format
		if ( empty( $items ) ) {
			$item = new PT_Item( $this, 0 );
			$items[] = $item
				->set_label( $this->field_data['_description'][0] )
				->set_amount( $this->field_data['_amount'][0] )
				->set_tax_percentage( null )
				->set_tax_amount( null )
				->set_total_amount( $this->field_data['_amount'][0] );
		}

		return $items;

	}


	/**
	 * Get payment status.
	 *
	 * Get the pretty payment status name.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function get_status() {

		$statuses = pt_get_payment_statuses();
		$status   = isset( $statuses[ $this->status ] ) ? $statuses[ $this->status ] : $this->status;

		return apply_filters( 'pt_payment_get_status', $status, $this->id );

	}


	/**
	 * Set payment status.
	 *
	 * Set the payment status and update the DB value.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $status New payment status slug.
	 *
	 * @return bool|string         False when the new status is invalid, the new status otherwise.
	 */
	public function set_status( $status ) {

		$old_status = $this->get_status();
		$statuses = pt_get_payment_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			$this->status = $status;
		} else {
			return false;
		}

		update_post_meta( $this->id, '_status', $this->status );

		do_action( 'pt_payment_after_set_status', $old_status, $status, $this );

		// Add a filter here to allow developers to process payment status update as well
		do_action( 'paytium_update_payment_status_from_admin', $this->id );

		return $this->get_status();

	}

	/**
	 * Hook to be called when payment is updated from WordPress admin
	 *
	 * @since 1.4.0
	 */
	public function update_status_from_admin( $payment_id ) {

		// Add a filter here to allow developers to process payment changes from admin as well
		do_action( 'paytium_after_update_payment_from_admin', $payment_id );

		return;

	}


	/**
	 * Get order status.
	 *
	 * Get the order status.
	 *
	 * @since 1.0.0
	 *
	 * @return string Order status.
	 */
	public function get_order_status() {

		$statuses = pt_get_order_statuses();
		$status   = isset( $statuses[ $this->order_status ] ) ? $statuses[ $this->order_status ] : $this->order_status;

		return apply_filters( 'pt_payment_get_order_status', $status, $this->id );

	}


	/**
	 * Set order status.
	 *
	 * Set the order status.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $status New order status.
	 *
	 * @return string         New order status
	 */
	public function set_order_status( $status ) {

		$statuses = pt_get_order_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			$this->order_status = $status;
		} else {
			return false;
		}

		update_post_meta( $this->id, '_order_status', $this->order_status );

		return $this->get_order_status();

	}


	/**
	 * Get payment date.
	 *
	 * Get the formatted payment date.
	 *
	 * @since 1.0.0
	 *
	 * @return string Formatted payment date.
	 */
	public function get_payment_date() {

		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$date_format = apply_filters( 'pt_payment_date_format', $date_format, $this->id );

		return date_i18n( $date_format, strtotime( $this->payment_date ) );

	}

	/**
	 * Get payment id (Mollie transaction ID).
	 *
	 * Get the Mollie transaction ID.
	 *
	 * @since 1.0.3
	 *
	 * @return string Mollie transaction ID.
	 */
	public function get_transaction_id() {

		$this->transaction_id = ! empty( $this->transaction_id ) ? $this->transaction_id : '-';

		return $this->transaction_id;

	}


	/**
	 * Get payment method.
	 *
	 * Get the used payment method for this payment.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}


	/**
	 * Set payment method.
	 *
	 * Set the used method for the payment
	 *
	 * @since 1.0.0
	 *
	 * @param  string $payment_method Used payment method.
	 *
	 * @return string                 Used payment method.
	 */
	public function set_payment_method( $payment_method ) {

		$payment_methods = pt_get_payment_methods();
		if ( isset( $payment_methods[ $payment_method ] ) ) {
			$this->payment_method = $payment_method;
		} else {
			return false;
		}

		update_post_meta( $this->id, '_method', $payment_method );

		return $this->get_payment_method();

	}


	/**
	 * Get description.
	 *
	 * Get the description of the payment. This should be something related to the product title for example.
	 *
	 * @since 1.0.0
	 *
	 * @return string Payment description.
	 */
	public function get_description() {

		return apply_filters( 'pt_payment_get_description', $this->description, $this->id );

	}

	/**
	 * Get subscription first payment
	 * @since 2.1.0
	 * @return string Subscription first payment.
	 */
	public function get_subscription_first_payment() {

		return $this->subscription_first_payment;

	}

	/**
	 * Get subscription recurring payment
	 * @since 2.1.0
	 * @return string Subscription recurring payment.
	 */
	public function get_subscription_recurring_payment() {

		return $this->subscription_recurring_payment;

	}

	/**
	 * Get all field data from fields in html format
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_field_data_html() {

		$html = '';

		$field_data = array ();

		foreach ( (array) $this->field_data as $key => $value ) {
			if ( strpos( $key, '_item' ) !== false ) continue; // Skip items

			// Add fields to custom data
			// Note: Every field has only one label, but two postmeta items in DB
			if ( strstr( $key, '-label' ) ) {
				// Update key/label for fields with user defined label
				$field_key               = ucfirst( str_replace( '-label', '', $key ) );
				$field_data[ $value[0] ] = isset( $this->field_data[ $field_key ] ) ? $this->field_data[ $field_key ] : array();
			}

			// Add customer details fields to custom data
			// If I merge customer details with fields, this can be removed (no users have this live?)
			if ( strstr( $key, 'pt-customer-details-' ) ) {
				$customer_details_key                = ucfirst( str_replace( 'housenumber', 'House number', str_replace( 'pt-customer-details-', '', str_replace( '_', '', $key ) ) ) );
				$field_data[ $customer_details_key ] = $value;
			}

			// Add customer details fields to custom data
			// Old format until June 2016
			if ( strstr( $key, '_customer_details_' ) ) {
				$customer_details_key                = ucfirst( str_replace( 'housenumber', 'House number', str_replace( '_customer_details_', '', str_replace( '_customer_details_fields_', '', $key ) ) ) );
				$field_data[ $customer_details_key ] = $value;
			}

		}

		foreach ( (array) $field_data as $key => $value ) {

			ob_start();

			?><div class='option-group'>

				<label for='claimer'><?php _e( $key, 'paytium' ); ?>:</label>
				<span class="option-value"><?php echo reset( $value ) ?></span>

			</div><?php

			$html .= ob_get_contents();
			ob_end_clean();
		}

		return $html;

	}

	/**
	 * Get all field data from fields in raw format
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */

	public function get_field_data_raw() {

		$field_data = array ();

		// Add the post/payment ID to the payment data array
		$field_data['payment-id'] = array ( $this->id );

		// Add the post/payment date to the payment data array
		$field_data['payment-date'] = array ( date_i18n( get_option( 'date_format' ), strtotime( $this->payment_date ) ) );

		foreach ( (array) $this->field_data as $key => $value ) {

			// Remove prefixing underscores
			$clean_key = ltrim( $key, '_' );

			//
			// Add custom tags - create a description without the payment ID at the end
			//
			if (  $key === '_description' ) {
				$field_data[ 'description-without-id' ] = str_replace( ' ' . $this->id, '', $value );
			}

			//
			// Add invoice key tag
			//
			if (  $key === '_invoice_key' ) {
				$field_data[ 'invoice-link' ] = array(get_site_url() . '?paytium-invoice=' . $value[0] );
			}

			//
			// Rename a few elements so they are more logical or users
			//
			if ( $key === '_status' ) {
				$clean_key                = 'payment-status';
				$this->field_data[ $key ] = array ( strtolower( $this->get_status() ) );
			}

			//
			// Convert format for subscription amounts
			//
			if ( $key === '_subscription_first_payment' ) {
				$this->field_data[ $key ][0] = pt_float_amount_to_currency( $value[0] );
			}
			if ( $key === '_subscription_recurring_payment' ) {
				$this->field_data[ $key ][0] = pt_float_amount_to_currency( $value[0] );
			}

			// Convert these clean keys (key is the existing, value being the cleaned version)
			$clean_keys = array(
				'_payment_id' => 'transaction-id',
				'_mode' => 'payment-mode',
				'_amount' => 'payment-amount',
				'_method' => 'payment-method',
				'_pt-field-amount' => 'payment-options-selected',
				'_pt-field-amount-label' => 'payment-options-label',
			);
			foreach ( $clean_keys as $k => $v ) {
				if ( $key === $k ) {
					$clean_key = $v;
				}
			}

			// Remove these elements, not needed for users/in emails
			$exclude_meta = array(
				'_edit_lock',
				'_edit_last',
				'_invoice_key',
				'_payment_key',
				'_paytium_emails_last_status',
				'mode',
				'_subscription_webhook',
				'_pt_emails_last_status',
				'_subscription_payment_status', // TODO: Make pretty version of this status/value and show in emails
				'_subscription_error', // TODO: show this if value is not empty (so actually error found)?
			);
			if ( in_array( $key, $exclude_meta ) ) {
				continue;
			}

			// Exclude keys that have one of these partial strings
			$exclude_partials = array(
				'pt_cf_',
				'pt-field-edd',
				'_pt_mailchimp_error',
				'_pt-field-mailchimp-',
				'_pt-field-mailpoet-',
				'_pt-field-activecampaign-',
				'_pt_no_payment',
				'_pt-field-form-emails',
			);
			$continue = false;
			foreach ( $exclude_partials as $v ) {
				if ( strpos( $key, $v ) !== false ) {
					$continue = true;
				}
			}
			if ( $continue ) continue;

			// TODO: Add subscription_renewal which should convert to Yes/No
            // TODO: Translate payment_status tag to pretty/translated names?

			//
			// Add all data to the field data array
			//
			$field_data[ str_replace( '_', '-', $clean_key  ) ] = str_replace( '_', '-', $this->field_data[ $key ] );

		}

		// Add additional tags
		$tax_total_split = '';
		foreach ( $this->get_taxes_per_percentage() as $percentage => $amount ) {
			if ( ! empty( $tax_total_split ) ) {
				$tax_total_split .= '<br />';
			}

			$tax_total_split .= pt_float_amount_to_currency( $amount ) . ' <small class="muted">(' . floatval( $percentage ) . '%)</small>';
		}

		ob_start();
			$payment = $this;
			require ( PT_PATH . 'admin/views/meta-boxes/payment-items.php' );
		$items_table = ob_get_clean();

		$items_table = apply_filters( 'paytium_items_table_emails_invoices', $items_table );

		$payment_data = array(
			'items-table' => array( $items_table ),
			'total' => array( pt_float_amount_to_currency( $this->get_total() ) ),
			'tax-total' => array( pt_float_amount_to_currency( $this->get_tax_total() ) ),
			'tax-total-split' => array( $tax_total_split ),
		);

		$field_data = $field_data + $payment_data;

		return $field_data;

	}

	/**
	 * Get all customer emails
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */

	public function get_field_data_customer_emails() {

		$field_data = array ();
		foreach ( (array) $this->field_data as $key => $value ) {
			if ( strpos( $key, '-label' ) === false && strstr( $key, 'pt-field-email' ) ) {
				$field_data[] = $value[0];
			}
		}

		return $field_data;

	}

	/**
	 * Get payment form emails (specific emails that should be sent for specific form)
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */

	public function get_payment_form_emails() {

		$form_emails = array ();

		foreach ( (array) $this->field_data as $key => $value ) {

			if ( strstr( $key, '-label' ) ) {
				continue;
			}

			if ( strstr( $key, 'pt-field-form-emails' ) ) {
				$form_emails = explode( ',', $value[0] );
			}

		}

		return $form_emails;

	}


	/**************************************************************
	 * Get different custom field data
	 *************************************************************/

	/**
	 * Get custom field data.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_custom_field_data() {

		$custom_field_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {
			if ( strpos( $k, '-label' ) !== false ) continue;
			if ( strpos( $k, '_pt-field' ) !== 0 ) continue;
			if ( strpos( $k, '_pt-field-mailchimp' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-mailpoet' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-activecampaign' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-amount' ) !== false ) continue;
			if ( strpos( $k, '-user-data' ) !== false ) continue;

			$key = ltrim( $k, '_' );
			$label = isset( $this->field_data[ $k . '-label' ] ) ? $this->field_data[ $k . '-label' ] : array();
			$custom_field_data[ $key ] = array(
				'label' => reset( $label ),
				'value' => reset( $v ),
			);

		}

		return $custom_field_data;

	}

	/**
	 * Get item data.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_item_data() {

		$item_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {
			if ( strpos( $k, '_item' ) !== 0 ) continue;
			if ( strpos( $k, '-type' ) == TRUE ) continue;

			// Remove prefixing underscores
			$clean_key = ltrim( $k, '_' );

			$label = preg_replace( '/item-\d+-/', '', $clean_key );
			$value = reset( $v );
			if ( in_array( $label, array( 'amount', 'tax-amount', 'total-amount' ) ) ) {
				$value = pt_float_amount_to_currency( $value );
			}

			$item_data[ $clean_key ] = array(
				'label' => $label,
				'value' => $value,
			);

		}

		return $item_data;

	}

	// BC field data
	public function get_customer_details_field_data() {

		$custom_field_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {
			if ( strpos( $k, '_pt-customer-details-' ) !== 0 ) continue;

			$key = str_replace( '_pt-customer-details-', '', $k );
			$custom_field_data[ $key ] = array(
				'label' => $key,
				'value' => $v[0],
			);

		}

		return $custom_field_data;

	}

}
