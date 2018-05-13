
<div class="pt-alert pt-alert-info" id="pt-account-details-found-continue-wizard" style="display: none;">
	<?php echo __('Mollie account found, you can ', 'paytium' )  ?>
    <a href="javascript:void(0);" class="tab-button"
       data-target="create-profile"><?php echo __( 'continue to step 2', 'paytium' ); ?></a>
	<?php echo __(' or connect a new account below.', 'paytium' )  ?>
</div>

<p><img class="author-image" src="<?php echo PT_URL . 'admin/img/daviddeboer.png'; ?>"/>
    <?php _e( 'I\'m David de Boer, online payments expert since 2008 and developer of the Paytium plugin. I\'ll be guiding you while you setup payments on this website.', 'paytium' ); ?>
</p>

<p><?php _e( 'To accept payments on any website, you will need an account at a \'payment provider\'. Paytium works with Mollie.com, because in my experience they are the best, with over 45.000 customers!', 'paytium' ); ?>
</p>

<p><?php _e( 'Mollie is a certified payments specialist and permanently supervised by the Dutch central bank, \'De Nederlandsche Bank\'. They will process your payments and send them to your bank account on a daily basis.', 'paytium' ); ?>
</p>

<ul>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo  __( 'Creating a Mollie account is risk and cost free', 'paytium'); ?>
    </li>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo  __( 'There are no setup, monthly or hidden costs', 'paytium'); ?>
    </li>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo  __( 'You only pay a small fee for completed transactions', 'paytium'); ?>
    </li>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo sprintf( __( 'Questions? Get in touch via the %s support forum %s', 'paytium'), '<a
                href="' . esc_url( 'https://wordpress.org/support/plugin/paytium' ) . '" target="_blank">', '</a>' ); ?>
    </li>
</ul>

<h3><?php _e( 'Connecting Mollie to your website', 'paytium' ); ?></h3>
<p><?php _e( 'A Mollie account needs to be connected to this website. You can create a new Mollie account or connect an existing one. If you are not sure what to do, choose to create a new account. You can always switch to another account or change details later, so there is no risk in getting started with a new account today.', 'paytium' ); ?></p>

<div style="text-align: center; margin-bottom: 10px;">

	<a href="javascript:void(0);" class="button button-primary target-button"
	   data-target="no-mollie-account"><?php _e( 'I don\'t have a Mollie account', 'paytium' ); ?></a>
    <a href="javascript:void(0);" class="button button-secondary target-button"
       data-target="have-mollie-account"><?php _e( 'I have a Mollie account', 'paytium' ); ?></a>&nbsp;
</div>

<div id="have-mollie-account" class="boxed target-area" style="display: none;">

	<h3><?php _e( 'Login with Mollie', 'paytium' ); ?></h3>

	<p><span class="dashicons dashicons-lock"></span>
        <?php _e( 'Your details will be sent to Mollie over a secure and encrypted connection!', 'paytium' ); ?></p>

	<div class="ajax-response"></div>

	<form method="">

		<div class="form-group">
			<label><?php _e( 'Username', 'paytium' ); ?>:
                <input type="text" name="username" class="">
			</label>
		</div>
		<div class="form-group">
			<label><?php _e( 'Password', 'paytium' ); ?>:
                <input type="password" name="password" class="">
			</label>
		</div>
		<a href="javascript:void(0);" id="login-mollie" class="button button-primary"
		   style="margin-top: 10px;"><?php _e( 'Continue', 'paytium' ); ?></a>

		<div class="spinner" style="margin-top: 14px; float: none;"></div>

	</form>

	<a href="javascript:void(0);" class="button button-primary continue-button tab-button" data-target="create-profile"
	   style="display: none;"><?php _e( 'Go to the next step', 'paytium' ); ?> &rarr;</a>

</div>

<div id="no-mollie-account" class="boxed target-area" style="display: none;">

	<h3><?php _e( 'Register with Mollie', 'paytium' ); ?></h3>

	<p><span class="dashicons dashicons-lock"></span>
        <?php _e( 'Your details will be sent to Mollie over a secure and encrypted connection!', 'paytium' ); ?></p>

    <p><span class="dashicons dashicons-warning"></span>
		<?php _e( 'All fields are required.', 'paytium' ); ?>
    </p>

	<div class="ajax-response"></div>

	<form method="">
		<div class="form-group">
			<label><?php _e( 'Username', 'paytium' );
                ?>:</label>
            <input type="text" name="username" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Name', 'paytium' );
                ?>:</label>
            <input type="text" name="name" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Company name', 'paytium' );
                ?>:</label>
            <input type="text" name="company_name" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Email', 'paytium' );
                ?>:</label>
            <input type="text" name="email" class="" value="<?php echo get_option( 'admin_email' ); ?>">

		</div>
		<div class="form-group">
			<label><?php _e( 'Address', 'paytium' );
                ?>:</label>
            <input type="text" name="address" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Zip code', 'paytium' );
                ?>:</label>
            <input type="text" name="zipcode" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'City', 'paytium' );
                ?>:</label>
            <input type="text" name="city" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Country', 'paytium' );
                ?>:</label>
            <select name="country">
					<option value="NL"><?php _e( 'Netherlands', 'paytium' ); ?></option>
					<option value="BE"><?php _e( 'Belgium', 'paytium' ); ?></option>
				</select>

		</div>
		<a href="javascript:void(0);" id="create-mollie-account" class="button button-primary"
		   style="margin-top: 10px;"><?php _e( 'Continue', 'paytium' ); ?></a>

		<div class="spinner" style="margin-top: 14px; float: none;"></div>

	</form>

	<a href="javascript:void(0);" class="button button-primary continue-button tab-button" data-target="create-profile"
	   style="display: none;"><?php _e( 'Go to the next step', 'paytium' ); ?> &rarr;</a>

</div>
