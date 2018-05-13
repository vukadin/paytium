<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var PT_Payment $payment
 */
?><div class="payment-items-meta-box">

	<table class="widefat payment-items-table" style="width: 100%">
		<thead>
			<tr>
				<th><?php _e( 'Item', 'paytium' ); ?></th>
				<th><?php _e( 'Amount', 'paytium' ); ?></th><?php
				if ( $payment->get_tax_total() ) :
					?><th><?php _e( 'Taxes', 'paytium' ); ?></th><?php
				endif;
				?><th><?php _e( 'Total', 'paytium' ); ?></th>
			</tr>
		</thead>

		<tbody><?php
			foreach ( $payment->get_items() as $item ) :

				?><tr>
					<td style="width: 60%; padding-right: 40px;"><?php echo $item->get_label(); ?></td>
					<td><?php echo pt_float_amount_to_currency( $item->get_amount() ); ?></td><?php
					if ( $item->get_tax_amount() ) :
						?><td><?php echo pt_float_amount_to_currency( $item->get_tax_amount() ); ?> <small class="muted">(<?php echo absint( $item->get_tax_percentage() ); ?>%)</small></td><?php
					endif;
					?><td><?php echo pt_float_amount_to_currency( $item->get_total_amount() ); ?></td>
				</tr><?php

			endforeach;
		?></tbody>

		<tfoot>
		<tr>
			<td></td>
			<td><?php echo pt_float_amount_to_currency( $payment->get_total() - $payment->get_tax_total() ); ?></td><?php
			if ( $payment->get_tax_total() ) :
				?><td><?php echo pt_float_amount_to_currency( $payment->get_tax_total() ); ?></td><?php
			endif;
			?><td><?php echo pt_float_amount_to_currency( $payment->get_total() ); ?></td>
		</tr>
		</tfoot>

	</table>

</div>