<?php

/**
 * Main Paytium class
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paytium {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'paytium';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	public $session;

	/**
	 * @since 1.0.0
	 * @var $api PT_API API class.
	 */
	public $api;

	/**
	 * @since 1.0.0
	 * @var $post_types  PT_Post_Types class.
	 */
	public $post_types;


	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Set current version
		$this->version = PT_VERSION;

		add_action( 'init', array ( $this, 'includes' ), 1 );

		// Add the options page and menu item.
		add_action( 'admin_menu', array ( $this, 'add_plugin_admin_menu' ), 10 );

		// Add to the WP toolbar.
		add_action( 'admin_bar_menu', array ( $this, 'add_toolbar_link' ), 999 );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array ( $this, 'enqueue_admin_styles' ) );

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array ( $this, 'enqueue_admin_scripts' ) );

		// Add admin notice after plugin activation, tip to use Setup Wizard. Also check if should be hidden.
		add_action( 'admin_notices', array ( $this, 'admin_notice_setup_wizard' ) );

		// Add admin notice that asks for newsletter sign-up. Also check if should be hidden.
		add_action( 'admin_notices', array ( $this, 'admin_notice_newsletter' ) );

		// Add admin notice that reminds users to view extensions page. Also check if should be hidden.
		add_action( 'admin_notices', array ( $this, 'admin_notice_extensions' ) );

		// Add admin notice when site already received live payments & completing the Setup Wizard is not necessary
		add_action( 'admin_notices', array ( $this, 'admin_notice_has_live_payments' ) );

		// Add admin notice when site is in Mollie test mode
		add_action( 'admin_notices', array ( $this, 'admin_notice_switch_to_live_mode' ) );

		// Add plugin listing "Settings" action link.
		add_filter( 'plugin_action_links_' . plugin_basename( PT_PATH . $this->plugin_slug . '.php' ), array (
			$this,
			'paytium_action_links'
		) );

		// Check WP version
		add_action( 'admin_init', array ( $this, 'check_wp_version' ) );

		// Add public JS
		add_action( 'wp_loaded', array ( $this, 'enqueue_public_scripts' ) );

		// Add public CSS
		add_action( 'wp_loaded', array ( $this, 'enqueue_public_styles' ) );

		// Load scripts when posts load so we know if we need to include them or not
		add_filter( 'the_posts', array ( $this, 'load_scripts' ) );

		// Paytium TinyMCE button
		add_action( 'init', array ( $this, 'paytium_add_mce_button' ) );
		add_action( 'admin_enqueue_scripts', array ( $this, 'paytium_mce_css' ) );
		add_action( 'wp_enqueue_scripts', array ( $this, 'paytium_mce_css' ) );

		// Paytium toolbar link
		add_action( 'admin_enqueue_scripts', array ( $this, 'paytium_toolbar_css' ) );
		add_action( 'wp_enqueue_scripts', array ( $this, 'paytium_toolbar_css' ) );

	}


	function load_scripts( $posts ) {

		if ( empty( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $post ) {
			if ( ( strpos( $post->post_content, '[paytium' ) !== false ) || true == get_option( 'paytium_always_enqueue' ) ) {
				// Load CSS
				wp_enqueue_style( $this->plugin_slug . '-public' );

				// Load JS
				wp_enqueue_script( $this->plugin_slug . '-public' );
				wp_enqueue_script( $this->plugin_slug . '-parsley' );
				wp_enqueue_script( $this->plugin_slug . '-parsley-nl' );

				// Localize the site script with new language strings
				wp_localize_script( $this->plugin_slug . '-public', 'paytium_localize_script_vars', array (
						'amount_too_low' => __( 'No (valid) amount entered or amount is too low!', 'paytium' ),
						'no_valid_email' => __( 'The email address entered in \'%s\' is incorrect!', 'paytium' ),
						'subscription_first_payment' => __( 'First payment:', 'paytium' ),
						'field_is_required' => __( 'Field \'%s\' is required!', 'paytium' ),
						'processing_please_wait' => __( 'Processing, please wait...', 'paytium' ),
					)
				);

				break;
			}
		}

		return $posts;

	}

	/**
	 * Load public facing CSS
	 *
	 * @since 1.0.0
	 */
	function enqueue_public_styles() {
		wp_register_style( $this->plugin_slug . '-public', PT_URL . 'public/css/public.css', array (), $this->version );
	}

	/**
	 * Find user's browser language
	 *
	 * @since 1.1.0
	 */

	function paytium_browser_language() {

		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {

			$languages = explode( ",", $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			$language  = strtolower( $languages[0] );
		} else {
			$language = '';
		}

		return $language;
	}

	/**
	 * Load scripts based on environment and language
	 *
	 * @since 1.1.0
	 *
	 * @param $environment
	 * @param $language
	 */

	function paytium_register_scripts( $environment, $language ) {

		$dependencies = array ( 'jquery', $this->plugin_slug . '-parsley' );

		if ( $environment == 'development' ) {

			wp_register_script( $this->plugin_slug . '-public', PT_URL . 'public/js/public.js', $dependencies, time(), true );
			wp_register_script( $this->plugin_slug . '-parsley', PT_URL . 'public/js/parsley.min.js', array ( 'jquery' ), time(), true );

		}

		if ( $environment == 'production' ) {

			wp_register_script( $this->plugin_slug . '-public', PT_URL . 'public/js/public.js', $dependencies, $this->version, true );
			wp_register_script( $this->plugin_slug . '-parsley', PT_URL . 'public/js/parsley.min.js', array ( 'jquery' ), $this->version, true );

		}

		// Add Dutch translation for Parsley if browser language is set to Dutch
		if ( $language == 'nl' ) {

			wp_register_script( $this->plugin_slug . '-parsley-nl', PT_URL . 'public/js/parsley-nl.js', $dependencies, time(), true );

		}

		return;
	}

	/**
	 * Load public facing JS
	 *
	 * @since 1.0.0
	 */
	public function enqueue_public_scripts() {

		// What's the user's browser language?
		$language = $this->paytium_browser_language();

		// Is this Paytium plugin on a production or development site?
		$environment = get_option( 'pt_environment', 'production' );

		if ( $language == 'nl' || $language == 'nl-nl' || $language == 'nl-be' ) {

			$this->paytium_register_scripts( $environment, 'nl' );

		} else {

			$this->paytium_register_scripts( $environment, '' );

		}

		wp_localize_script( $this->plugin_slug . '-public', 'pt', array (
			'currency_symbol' => '&euro;',
			'decimals' => apply_filters( 'paytium_amount_decimals', 2 ),
			'thousands_separator' => apply_filters( 'paytium_thousands_separator', '.' ),
			'decimal_separator' => apply_filters( 'paytium_decimal_separator', ',' ),
			'debug'           => ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ),
		) );

	}

	/**
	 * Load admin scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts() {

		wp_register_style( 'pt-select2', plugins_url( 'admin/css/select2/select2.min.css', PT_MAIN_FILE ), array (), '4.0.3' );

		if ( ! $this->viewing_this_plugin() ) {
			return false;
		}

		// Is this Paytium plugin on a production or development site?
		$environment = get_option( 'pt_environment', 'production' );

		if ( $environment == 'development' ) {

			wp_enqueue_script( $this->plugin_slug . '-admin', PT_URL . 'admin/js/admin.js', array ( 'jquery' ), time(), true );
			wp_enqueue_script( 'paytium-setup-wizard', PT_URL . 'admin/js/setup-wizard.js', array ( 'jquery' ), time(), true );

		}

		if ( $environment == 'production' ) {

			wp_enqueue_script( $this->plugin_slug . '-admin', PT_URL . 'admin/js/admin.js', array ( 'jquery' ), $this->version, true );
			wp_enqueue_script( 'paytium-setup-wizard', PT_URL . 'admin/js/setup-wizard.js', array ( 'jquery' ), $this->version, true );

		}

		wp_localize_script( 'paytium-setup-wizard', 'paytium', array (
			'nonce' => wp_create_nonce( 'paytium-ajax-nonce' ),
		) );


	}


	/**
	 * Enqueue admin-specific style sheets for this plugin's admin pages only.
	 *
	 * @since     1.0.0
	 */
	public function enqueue_admin_styles() {

		wp_register_script( 'pt-select2', plugins_url( 'admin/js/select2/select2.min.js', PT_MAIN_FILE ), array ( 'jquery' ), '4.0.3', true );

		if ( ! $this->viewing_this_plugin() ) {
			return false;
		}

		// Is this Paytium plugin on a production or development site?
		$environment = get_option( 'pt_environment', 'production' );

		if ( $environment == 'development' ) {

			wp_enqueue_style( $this->plugin_slug . '-admin-styles', PT_URL . 'admin/css/admin.css', array (), time() );
			wp_enqueue_style( $this->plugin_slug . '-toggle-switch', PT_URL . 'admin/css/toggle-switch.css', array (), time() );

		}

		if ( $environment == 'production' ) {

			wp_enqueue_style( $this->plugin_slug . '-admin-styles', PT_URL . 'admin/css/admin.css', array (), $this->version );
			wp_enqueue_style( $this->plugin_slug . '-toggle-switch', PT_URL . 'admin/css/toggle-switch.css', array (), $this->version );
		}


		if ( get_current_screen()->post_type == 'pt_payment' ) {
			wp_enqueue_style( $this->plugin_slug . '-admin-notice-newsletter-style', PT_URL . 'admin/css/admin-notice-newsletter.css', array (), $this->version );
		}

		if ( get_current_screen()->base == 'paytium_page_pt-extensions' ) {
			wp_enqueue_style( $this->plugin_slug . '-admin-extensions', PT_URL . 'admin/css/admin-extensions.css', array (), $this->version );
		}

	}


	/**
	 * Make sure user has the minimum required version of WordPress installed to use the plugin
	 *
	 * @since 1.0.0
	 */
	public function check_wp_version() {

		global $wp_version;
		$required_wp_version = '3.6.1';

		if ( version_compare( $wp_version, $required_wp_version, '<' ) ) {
			deactivate_plugins( PT_MAIN_FILE );
			wp_die( sprintf( __( $this->get_plugin_title() . ' requires WordPress version <strong>' . $required_wp_version . '</strong> to run properly. ' .
			                     'Please update WordPress before reactivating this plugin. <a href="%s">Return to Plugins</a>.', 'paytium' ), get_admin_url( '', 'plugins.php' ) ) );
		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		global $submenu;

		$this->plugin_screen_hook_suffix[] = add_menu_page(
			$this->get_plugin_title() . ' ' . __( 'Settings', 'paytium' ),
			$this->get_plugin_title(),
			'edit_posts',
			$this->plugin_slug,
			array ( $this, 'display_plugin_admin_page' ),
			plugins_url( '/assets/ideal-16x16.png', __FILE__ )
		);

		// Settings page
		add_submenu_page( 'paytium', __( 'Paytium settings', 'paytium' ), __( 'Settings', 'paytium' ), 'manage_options', 'paytium', array (
			$this,
			'display_plugin_admin_page'
		) );

		// Setup wizard
		if ( false == get_option( 'paytium_enable_live_key' ) ) {
			add_submenu_page( 'paytium', 'Setup wizard', __( 'Setup wizard', 'paytium' ), 'manage_options', 'pt-setup-wizard', array (
				$this,
				'setup_wizard_page'
			) );
		}

		// Add links about pro versions/features to free version
		if ( PT_PACKAGE == 'paytium' ) {

			// Extensions
			add_submenu_page( 'paytium', 'Extra features', __( 'Extra features', 'paytium' ), 'manage_options', 'pt-extensions', array (
				$this,
				'paytium_extensions_page'
			) );

			// Pro versions
			$submenu['paytium'][] = array ( '<span style="color: #3db634;">' . __( 'Pro versions', 'paytium' ) . '</span>', 'manage_options', 'https://www.paytium.nl/prijzen/' );
		}

	}

	function add_toolbar_link( $wp_admin_bar ) {

		$icon = "<span class='pt-icon' style='background: url(\" /wp-content/plugins/" . PT_PACKAGE . "/assets/ideal-16x16.png\") no-repeat center;'> </span>";
		$args = array(
			'id'    => 'paytium',
			'title' => $icon . __('Payments', 'paytium'),
			'href' => esc_url( admin_url( 'edit.php?post_type=pt_payment' ) ),
			'meta'  => array( 'class' => 'pt-toolbar' )
		);
		$wp_admin_bar->add_node( $args );
	}


	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

		include_once( PT_PATH . 'admin/views/admin-settings.php' );

	}


	/**
	 * Setup wizard admin page.
	 *
	 * Display the setup wizard on a separate page for the best UX.
	 *
	 * @since 1.0.0
	 */
	public function setup_wizard_page() {

		require_once PT_PATH . 'admin/views/setup-wizard.php';

	}

	/**
	 * Extensions admin page.
	 *
	 * Display extensions for Paytium.
	 *
	 * @since 1.2.0
	 */
	public function paytium_extensions_page() {

		require_once PT_PATH . 'admin/views/admin-extensions.php';

	}


	/**
	 * Include required files (admin and frontend).
	 *
	 * @since     1.0.0
	 */
	public function includes() {

		global $pt_mollie;

		// TODO Check for curl -- function_exists( 'curl_version' )
		// TODO Check for sites on localhost?
		// TODO Check for PHP 5.3.3 (or whatever Mollie API currently requires).

		if ( ! class_exists( 'Mollie_API_Client' ) ) {
			require_once( PT_PATH . 'libraries/Mollie/API/Autoloader.php' );
		}

		$pt_mollie = new Mollie_API_Client;

		/**
		 * Include functions
		 */
		include_once( PT_PATH . 'includes/misc-functions.php' );

		include_once( PT_PATH . 'includes/process-payment-functions.php' );
		include_once( PT_PATH . 'includes/webhook-url-functions.php' );
		include_once( PT_PATH . 'includes/redirect-url-functions.php' );

		include_once( PT_PATH . 'includes/shortcodes.php' );
		include_once( PT_PATH . 'includes/register-settings.php' );
		include_once( PT_PATH . 'includes/payment-functions.php' );
		include_once( PT_PATH . 'includes/tax-functions.php' );

		include_once( PT_PATH . 'includes/user-data-functions.php' );

		// Include classes
		include_once( PT_PATH . 'includes/class-pt-item.php' );
		include_once( PT_PATH . 'includes/class-pt-payment.php' );

		/**
		 * Post types class
		 */
		include_once( PT_PATH . 'includes/class-pt-post-types.php' );
		$this->post_types = new PT_Post_Types();

		/**
		 * Admin includes
		 */
		if ( is_admin() ) {
			require_once PT_PATH . 'admin/class-pt-admin.php';
			$this->admin = new PT_Admin();

			require_once PT_PATH . 'includes/class-pt-api.php';
			$this->api = new PT_API();

		}

	}


	/**
	 * Return localized base plugin title.
	 *
	 * @since     1.0.0
	 *
	 * @return string
	 */
	public static function get_plugin_title() {

		return __( 'Paytium', 'paytium' );

	}


	/**
	 * Add Settings action link to left of existing action links on plugin listing page.
	 *
	 * @since   1.0.0
	 *
	 * @param  array $links Default plugin action links
	 *
	 * @return array $links Amended plugin action links
	 */
	public function paytium_action_links( $links ) {

		// Setup wizard
		if ( false == get_option( 'paytium_enable_live_key' ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=pt-setup-wizard' ) ) . '">' . __( 'Setup wizard', 'paytium' ) . '</a>';
		}

		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=paytium' ) ) . '">' . __( 'Settings', 'paytium' ) . '</a>';

		if ( PT_PACKAGE == 'paytium' ) {
			$links[] = '<a href="' . esc_url( 'https://www.paytium.nl/prijzen/' ) . '" style="color: #3db634;">' . __( 'Pro versions', 'paytium' ) . '</a>';
		}

		return $links;

	}


	/**
	 * Check if viewing this plugin's admin page.
	 *
	 * @since   1.0.0
	 *
	 * @return bool
	 */
	private function viewing_this_plugin() {

		$screen = get_current_screen();

		if ( ! empty( $this->plugin_screen_hook_suffix ) && in_array( $screen->id, $this->plugin_screen_hook_suffix ) ) {
			return true;
		}

		if ( 'paytium_page_pt-extensions' == $screen->id ) {
			return true;
		}

		if ( 'paytium_page_pt-setup-wizard' == $screen->id ) {
			return true;
		}

		if ( 'pt_payment' == get_post_type() || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'pt_payment' ) ) {
			return true;
		}

		$page_ids = array( 'paytium-export' );
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $page_ids ) ) {
			return true;
		}

		return false;

	}


	/**
	 * Show notice after plugin install/activate in admin dashboard.
	 * Hide after first viewing.
	 *
	 * @since   1.0.0
	 */
	public function admin_notice_setup_wizard() {

		// Exit all of this is stored value is false/0 or not set.
		if ( true == get_option( 'paytium_enable_live_key' ) ) {
			return;
		}

		// Exit all of this is stored value is false/0 or not set.
		if ( false == get_option( 'pt_show_admin_notice_setup_wizard' ) ) {
			return;
		}

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-install-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_setup_wizard' );

			return;
		}

		// At this point show install notice. Show it only on the plugin screen.
		if ( get_current_screen()->id == 'plugins' ) {
			include_once( PT_PATH . 'admin/views/admin-notice-setup-wizard.php' );
		}

	}

	/**
	 * Use admin notice to ask for newsletter sign-up.
	 * Hide after first viewing.
	 *
	 * @since   1.2.0
	 */
	public function admin_notice_newsletter() {

		// Exit all of this is stored value is false/0 or not set.
		if ( get_option( 'pt_hide_admin_notice_newsletter' ) == true ) {
			return;
		}

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-newsletter-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_newsletter', true );
			update_option( 'pt_hide_admin_notice_newsletter', true );
			set_transient( 'pt_wait_to_show_admin_notice_extensions', '1', '30' );
			return;
		}

		// At this point show newsletter notice.
		if ( get_current_screen()->post_type == 'pt_payment' ) {
			include_once( PT_PATH . 'admin/views/admin-notice-newsletter.php' );
		}
	}

	/**
	 * Use admin notice to remind users to view extensions page.
	 * Hide after first viewing.
	 *
	 * @since   1.2.0
	 */
	public function admin_notice_extensions() {

		// Exit all of this is stored value is false/0 or not set.
		if ( false == get_option( 'pt_show_admin_notice_extensions' ) ) {
			return;
		}

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-extensions-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_extensions' );
			update_option( 'pt_hide_admin_notice_extensions', true );

			return;
		}

		// At this point show extensions notice.
		if ( get_current_screen()->post_type == 'pt_payment' &&
		     true == get_option( 'pt_hide_admin_notice_newsletter' ) &&
		     false === get_transient( 'pt_wait_to_show_admin_notice_extensions' )
		) {
			include_once( PT_PATH . 'admin/views/admin-notice-extensions.php' );
		}
	}

	/**
	 * Add admin notice when site already received live payments &
	 * completing the Setup Wizard is not necessary
	 *
	 * @since   1.5.0
	 */
	public function admin_notice_has_live_payments() {

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-has-live-payments-nag'] ) ) {
			delete_option( 'pt_show_admin_notice_has_live_payments' );

			return;
		}

		// Check if there are live payments in this site
		if ( false == pt_has_live_payments() ) {
			return;
		}

		// At this point show "has live payments" notice.
		if ( get_current_screen()->id == 'paytium_page_pt-setup-wizard' ) {
			include_once( PT_PATH . 'admin/views/admin-notice-has-live-payments.php' );
		}
	}

	/**
	 * Add admin notice when site in Mollie test mode
	 *
	 * @since   2.1.0
	 */
	public function admin_notice_switch_to_live_mode() {

		// Delete stored value if "hide" button click detected (custom querystring value set to 1).
		if ( ! empty( $_REQUEST['pt-dismiss-switch-to-live-mode-nag'] ) ) {
			update_option( 'pt_admin_notice_switch_to_live_mode', 1 );

			return;
		}

		// Exit all of this is stored value is false/0 or not set.
		if ( ( false == get_option( 'pt_hide_admin_notice_extensions' ) ) &&
		     ( false == get_option( 'pt_hide_admin_notice_newsletter' ) ) ) {
			return;
		}

		// Check if site is not on live mode
		if ( get_option( 'paytium_enable_live_key' ) == 1 ) {
			return;
		}

		// At this point show "test mode" notice.
		if ( get_current_screen()->post_type == 'pt_payment' &&
		     false == get_option( 'pt_admin_notice_switch_to_live_mode' )) {
			include_once( PT_PATH . 'admin/views/admin-notice-switch-to-live-mode.php' );
		}
	}

	/**
	 * Code for including a TinyMCE button
	 *
	 * @since   1.0.0
	 */
	function paytium_add_mce_button() {

		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_external_plugins', array ( $this, 'paytium_add_buttons' ) );
			add_filter( 'mce_buttons', array ( $this, 'paytium_register_buttons' ) );
		}

	}


	function paytium_add_buttons( $plugin_array ) {

		$plugin_array['paytiumshortcodes'] = plugin_dir_url( __FILE__ ) . '/public/js/paytium-tinymce-button.js';

		return $plugin_array;

	}


	function paytium_register_buttons( $buttons ) {

		array_push( $buttons, 'separator', 'paytiumshortcodes' );

		return $buttons;

	}


	function paytium_mce_css() {

		wp_enqueue_style( 'paytium_shortcodes-tc', plugins_url( '/public/css/paytium_tinymce_style.css', __FILE__ ) );

	}


	function paytium_toolbar_css() {

		wp_enqueue_style( 'paytium-toolbar-css', plugins_url( '/public/css/paytium_toolbar.css', __FILE__ ) );

	}

}

