<div class="pt-alert pt-alert-danger pt-no-account-details-restart-wizard" style="display: none;">
	<?php echo __('No Mollie username or password found!', 'paytium' )  ?>
    <a href="javascript:void(0);" class="tab-button"
       data-target="connect-mollie"><?php echo __( 'Go back to step 1', 'paytium' ); ?> &rarr;</a>
</div>

<div id="pt-setup-first-payment-form-box" style="display: none;">

    <h3><?php _e( 'Create your first payment form', 'paytium' ); ?></h3>
    <p><?php _e( 'For easily adding payment forms to your WordPress site, Paytium uses shortcodes. In the WordPress editor you will now find an iDEAL icon. You can use this icon to insert one of the default payment forms. You can then start changing the payment form to better fit your needs. The below video gives you an idea what adding a payment form looks like.', 'paytium' ); ?></p>

    <p><?php _e( 'Click on the below links to add a payment form in:', 'paytium' ); ?><br/>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" id="create-product"
           target="_blank"><?php _e( 'new page', 'paytium' ); ?></a>,
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" id="create-product"
           target="_blank"><?php _e( 'existing page', 'paytium' ); ?></a>
		<?php echo __( 'in an', 'paytium' ); ?>
        <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" id="create-product"
           target="_blank"><?php _e( 'existing post', 'paytium' ); ?></a>.
    </p>

    <br/>

    <iframe width="100%" height="500" src="https://www.youtube.com/embed/PQmmz2XxBcA" frameborder="0"
            allowfullscreen></iframe>
    <br/>

    <h3><?php _e( 'Optional tasks you can now perform:', 'paytium' ); ?></h3>

    <p>
    <ul>
        <li>
            <i class="dashicons dashicons-arrow-right-alt2"></i> <?php echo sprintf( __( 'Enable other %s payment methods %s besides iDEAL at Mollie.com', 'paytium' ), '<a
                href="https://www.mollie.com/nl/signup/335035" target="_blank">', '</a>' ); ?>
        </li>
        <li>
            <i class="dashicons dashicons-arrow-right-alt2"></i> <?php echo sprintf( __( 'Learn what\'s possible with payment forms in the %s Paytium manual %s', 'paytium' ), '<a
                href="https://www.paytium.nl/handleiding/" target="_blank">', '</a>' ); ?>
        </li>
        <li>
            <i class="dashicons dashicons-arrow-right-alt2"></i> <?php echo sprintf( __( 'Vote for new Paytium %s features and integrations %s', 'paytium' ), '<a
                href="' . esc_url( admin_url( 'admin.php?page=pt-extensions' ) ) . '" target="_blank">', '</a>' ); ?>
        </li>
        <li>
            <i class="dashicons dashicons-arrow-right-alt2"></i> <?php echo sprintf( __( 'Ask questions and advice on the %s support forum %s', 'paytium' ), '<a
                href="' . esc_url( 'https://wordpress.org/support/plugin/paytium' ) . '" target="_blank">', '</a>' ); ?>
        </li>
    </ul>
    </p>
</div>

