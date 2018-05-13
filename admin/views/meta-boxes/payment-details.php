<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

wp_nonce_field( 'pt_payment_details', 'pt_payment_nonce' );

?>

<div class='option-group'>

	<label for='payment-id'><?php _e( 'Payment ID', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->id; ?></span>

</div>

<div class='option-group'>

	<label for='transaction-id'><?php _e( 'Transaction ID', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->get_transaction_id(); ?></span>

</div>

<div class='option-group'>

	<label for='payment-date'><?php _e( 'Payment time', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->get_payment_date(); ?></span>

</div>

<div class='option-group'>

	<label for='payment-status'><?php _e( 'Payment status', 'paytium' ); ?></label>
	<select class='' name='payment_status' id="payment-status"><?php
		foreach ( pt_get_payment_statuses() as $key => $value ) :

			?>
			<option <?php selected( $payment->status, $key ); ?> value='<?php echo esc_attr( $key ); ?>'><?php
			echo esc_html( $value );
			?></option><?php

		endforeach;
		?></select>
    <div class="option-description">
		<?php echo sprintf( __( 'Read more about %spayment statuses%s.', 'paytium' ), '<a href="https://www.paytium.nl/handleiding/betalingen-beheren/#betekenis-van-statussen" target="_blank">', '</a>' ); ?>
    </div>

</div>

<div class='option-group'>

	<label for='order-status'><?php _e( 'Order status', 'paytium' ); ?></label>
	<select class='' name='order_status' id="order-status"><?php
		foreach ( pt_get_order_statuses() as $key => $value ) :

			?>
			<option <?php selected( $payment->order_status, $key ); ?> value='<?php echo esc_attr( $key ); ?>'><?php
			echo esc_html( $value );
			?></option><?php

		endforeach;
		?></select>

</div>

<div class='option-group'>

	<label for='claimer'><?php _e( 'Amount', 'paytium' ); ?></label>
	<span class="option-value"><?php echo esc_html( pt_float_amount_to_currency( $payment->get_amount() ) ); ?></span>

</div>

<div class='option-group'>

	<label for='claimer'><?php _e( 'Description', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->get_description(); ?></span>

</div>
