<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


?>

    <div class="submitbox" id="submitpost">
        <div id="minor-publishing">

			<?php
			if ( $payment->subscription == 1 && $payment->subscription_payment_status == 'pending' ) {

				?>
                <div id="misc-publishing-actions">
                    <div class="inside-options">
						<?php

						echo __( 'Subscription not created yet.', 'paytium' );

						?>
                    </div> <!-- END INSIDE OPTIONS -->
                    <div class="clear"></div>
                </div> <!-- END MISC PUBLISHING ACTIONS -->

				<?php

			} elseif ( $payment->subscription == 1 && $payment->subscription_payment_status == 'failed' ) {

				?>
                <div id="misc-publishing-actions">
                    <div class="inside-options">
						<?php

						echo __( 'Creating subscription failed:', 'paytium' ) . '<br />' . strtolower( $payment->subscription_error );

						?>
                    </div> <!-- END INSIDE OPTIONS -->
                    <div class="clear"></div>
                </div> <!-- END MISC PUBLISHING ACTIONS -->
				<?php

			} elseif ( $payment->subscription == 1 && ( $payment->subscription_payment_status == 'active' || 'pending' ) ) {

				$subscription_cancelled = null;

				// BC
				try {
					$subscription = $pt_mollie->customers_subscriptions->withParentId( $payment->customer_id )->get( $payment->subscription_id );
				}
				catch ( Mollie_API_Exception $e ) {

					if ( strpos( $e->getMessage(), 'The subscription has been cancelled' ) !== false ) {
						$subscription_cancelled       = 1;
						$payment->subscription_status = 'cancelled';
					}
				}

				?>
                <div id="misc-publishing-actions">
                    <div class="inside-options">

						<?php
						if ( $payment->subscription_status != null ) {
							?>
                            <div class="option-group-subscription">
                                <label for="claimer">
									<?php echo __( 'Status', 'paytium' ) ?>:
                                </label>
                                <span class="option-value" id="option-value-subscription-status">
                                    <?php echo ucfirst( __( $payment->subscription_status, 'paytium' ) ) ?>
                                </span>
                            </div>
							<?php
						}
						?>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Payment', 'paytium' ) ?>:
                            </label>
                            <span class="option-value" id="option-value-subscription-payment-status">
                                    <?php
                                    $payment_type = ( $payment->subscription_payment_status == 'completed' ? 'initial' : $payment->subscription_payment_status );
                                    echo __( ucfirst( $payment_type ), 'paytium' );
                                    ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Interval', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo ucfirst( __( $payment->subscription_interval, 'paytium' ) ) ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Times', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo ucfirst( __( $payment->subscription_times, 'paytium' ) ) ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Amount', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">

                                <?php
                                if ( $payment->subscription_first_payment !== '' ) {
	                                $subscription_amount = $payment->subscription_recurring_payment;
                                } else {
	                                $subscription_amount = $payment->payment_amount;
                                }
                                echo esc_html( pt_float_amount_to_currency( __( $subscription_amount, 'paytium' ) ) )
                                ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'ID', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo __( $payment->subscription_id, 'paytium' ) ?>
                                </span>
                        </div>

                        <div class="option-group-subscription">
                            <label for="claimer">
								<?php echo __( 'Customer', 'paytium' ) ?>:
                            </label>
                            <span class="option-value">
                                    <?php echo __( $payment->customer_id, 'paytium' ) ?>
                                </span>
                        </div>

						<?php
						if ( $payment->subscription_start_date != null ) {
							?>
                            <div class="option-group-subscription">
                                <label for="claimer">
									<?php echo __( 'Start date', 'paytium' ) ?>:
                                </label>
                                <span class="option-value">
                                    <?php echo preg_replace( '/T.*/', '', __( $payment->subscription_start_date, 'paytium' ) ) ?>
                                </span>
                            </div>

							<?php
						}

						$visibility = ( $payment->subscription_cancelled_date == null ) ? 'none' : 'block';
						?>

                        <div class="option-group-subscription option-group-subscription-cancelled"
                             style="display:<?php echo $visibility ?>">
                            <label for="claimer">
								<?php echo __( 'Cancelled', 'paytium' ) ?>:
                            </label>
                            <span class="option-value option-value-cancelled">
                                    <?php echo preg_replace( '/T.*/', '', __( $payment->subscription_cancelled_date, 'paytium' ) ) ?>
                                </span>
                        </div>

                    </div> <!-- END INSIDE OPTIONS -->
                    <div class="clear"></div>
                </div> <!-- END MISC PUBLISHING ACTIONS -->

				<?php
				if ( $subscription_cancelled == null ) {
					?>

                    <div id="major-publishing-actions">

                        <div id="publishing-action">
                            <span class="spinner"></span>

                            <input type="hidden" id="payment_id" name="payment_id"
                                   value="<?php echo $payment->id ?>">
                            <input type="hidden" id="subscription_id" name="subscription_id"
                                   value="<?php echo $payment->subscription_id ?>">
                            <input type="hidden" id="customer_id" name="customer_id"
                                   value="<?php echo $payment->customer_id ?>">
                            <input type="submit"
                                   class="button button-secondary button-large paytium-cancel-subscription"
                                   id="paytium-cancel-subscription-button" value="Cancel subscription">
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php
				}

			}

			?>
        </div>
    </div>
<?php




