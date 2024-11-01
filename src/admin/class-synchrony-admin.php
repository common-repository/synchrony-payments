<?php
/**
 * Admin functionality class
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Gateway\Synchrony_Payment;
use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Gateway;
use Synchrony\Payments\Admin\Synchrony_Promotag_Configurator;
use Synchrony\Payments\Admin\Synchrony_Helper;
use Synchrony\Payments\Admin\Synchrony_Tracker_Connection;
use WC_Admin_Settings;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Smb_Helper;
use Synchrony\Payments\Helper\Synchrony_Widget_Helper;

/**
 * Class Synchrony_Admin
 */
class Synchrony_Admin extends Synchrony_Payment {

	/**
	 * ID
	 *
	 * @var string
	 */
	public const ID = 'synchrony-unifi-payments';

	/**
	 * FLAG
	 *
	 * @var int
	 */
	public const FLAG = 1;

	/**
	 * Upload File Size
	 *
	 * @var int
	 */
	public const UPLOAD_FILE_SIZE = 122880;

	/**
	 * File Size Label
	 *
	 * @var string
	 */
	public const UPLOAD_FILE_SIZE_LABEL = '120KB';

	/**
	 * SYF_MANUAL_CAPTURE
	 *
	 * @var string
	 */
	public const SYNCHRONY_MANUAL_CAPTURE = '_syf_manual_capture';

	/**
	 * Logger
	 *
	 * @var Synchrony_Logger $logger
	 */
	private $logger;

	/**
	 * Config helper
	 *
	 * @var Synchrony_Config_Helper $config_helper
	 */
	private $config_helper;

	/**
	 * Config helper
	 *
	 * @var Synchrony_Common_Config_Helper $common_config_helper
	 */
	private $common_config_helper;

	/**
	 * Synchrony_Smb_Helper
	 *
	 * @var Synchrony_Smb_Helper $smb_helper
	 */
	private $smb_helper;

	/**
	 * Synchrony_Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;

	/**
	 * Syf logo
	 *
	 * @var mixed
	 */
	private $syf_logo;

	/**
	 * Admin constructor
	 */
	public function __construct() {
		global $current_section;
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->smb_helper            = new Synchrony_Smb_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$get_option                  = $this->common_config_helper->fetch_synchrony_config_option();
		$this->syf_logo              = $this->common_config_helper->fetch_synchrony_logo();
		if ( isset( $get_option['logo'] ) ) {
			$logo_url = $get_option['logo'];
		} else {
			$logo_url = $this->syf_logo;
		}
		$this->id                 = self::ID;
		$this->icon               = $logo_url;
		$this->method_title       = 'Synchrony';
		$this->method_description = 'Synchrony is an online payment platform that makes accepting Synchrony Financial (SYF) issued credit cards easy and intuitive for customers.';
		$this->logger             = new Synchrony_Logger();

		// Method with all the options fields.
		$this->init_form_fields();

		$this->save_settings();

		// Load the settings.
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->supports    = array(
			'refunds',
		);
		add_filter( 'woocommerce_get_sections_checkout', array( $this, 'synchrony_promotion_tab' ) );
		add_filter( 'woocommerce_get_settings_checkout', array( $this, 'synchrony_api_all_settings' ), 10, 2 );

		if ( is_admin() && 'synchrony-unifi-payments' === $current_section ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'synchrony_custom_script' ) );
		}
		add_action( 'woocommerce_settings_checkout', array( $this, 'action_woocommerce_settings_checkout' ), 10 );
		add_action( 'woocommerce_update_options_checkout', array( $this, 'action_woocommerce_settings_save_checkout' ), 10 );
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) && \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'custom_order_tables' ) ) {
				add_action( 'woocommerce_process_shop_order_meta', array( $this, 'synchrony_save_meta_box' ), 999, 1 );
		} else {
			add_action( 'save_post', array( $this, 'synchrony_save_meta_box' ), 999, 1 );
		}

		add_action( 'woocommerce_generate_view_syflogo_html', array( $this, 'generate_view_syflogo_html' ) );
		add_action( 'woocommerce_generate_view_logo_html', array( $this, 'generate_view_logo_html' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'custom_post_back_button' ) );
	}

	/**
	 * Whether the gateway is available or not.
	 *
	 * @return bool
	 */
	public function is_available() {
		$deployed_smb_partner_id = $this->common_config_helper->fetch_syf_option( 'synchrony_deployed_digitalbuy_api_smb_partner_id' );
		$test_smb_partner_id     = $this->common_config_helper->fetch_syf_option( 'synchrony_test_digitalbuy_api_smb_partner_id' );
		$deployed_partner_id     = $this->common_config_helper->fetch_syf_option( 'synchrony_deployed_digitalbuy_api_partner_id' );
		$test_partner_id         = $this->common_config_helper->fetch_syf_option( 'synchrony_test_digitalbuy_api_partner_id' );

		if ( $deployed_smb_partner_id || $test_smb_partner_id || $deployed_partner_id || $test_partner_id ) {
			return true;
		}
		return false;
	}

	/**
	 * Admin tab for synchrony promotion.
	 *
	 * @param array $sections These are the sections in admin.
	 *
	 * @return array
	 */
	public function synchrony_promotion_tab( array $sections ) {
		$this->sychrony_upgrade_init();
		global $current_section;
		$client_id = $this->setting_config_helper->fetch_client_id();
		if ( $client_id ) {
			$syf_sections = array( 'synchrony-unifi-payments', 'synchrony_apis', 'synchrony_promotions', 'mpp_banner' );
			if ( in_array( $current_section, $syf_sections, true ) ) {
				$sections                             = array();
				$sections['synchrony-unifi-payments'] = __( 'Synchrony Settings', 'synchrony-payments' );
				$sections['synchrony_apis']           = __( 'Synchrony APIs', 'synchrony-payments' );
				$sections['synchrony_promotions']     = __( 'Synchrony Promotions', 'synchrony-payments' );
				$widget_helper                        = new Synchrony_Widget_Helper();
				if ( $widget_helper->enable_mppbanner() ) {
					$sections['mpp_banner'] = __( 'MPP Banner', 'synchrony-payments' );
				}
			}
			return $sections;
		}
		$syf_sections = array( 'synchrony-unifi-payments', 'synchrony_apis' );
		if ( in_array( $current_section, $syf_sections, true ) ) {
			$sections                             = array();
			$sections['synchrony-unifi-payments'] = __( 'Synchrony Settings', 'synchrony-payments' );
			$sections['synchrony_apis']           = __( 'Synchrony APIs', 'synchrony-payments' );
		}
		return $sections;
	}
	/**
	 *  Add Custom Back Button for MPP.
	 *
	 * @param mixed $post This is post data.
	 *
	 * @return void
	 */
	public function custom_post_back_button( $post ) {
		if ( 'mpp-banner' === $post->post_type ) {
			echo '<div class="misc-pub-section misc-pub-section-last">
					<a href="' . esc_html( admin_url( 'edit.php?post_type=mpp-banner' ) ) . '" class="alignright button button-primary button-large" style="margin-bottom: 5px;">Back</a>
				</div>';
		}
	}
	/**
	 * Save Settings Tab
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	}

	/**
	 * Initializing API form fields
	 *
	 * @return void
	 */
	public function action_woocommerce_settings_checkout() {
		global $current_section;
		// Call settings function.
		if ( 'synchrony_apis' === $current_section ) {
			$settings = $this->init_api_form_fields();
			WC_Admin_Settings::output_fields( $settings );
		}
	}


	/**
	 * Save Config Settings and Calling Tracker API
	 *
	 * @return void
	 */
	public function process_admin_options() {
		$helper                  = new Synchrony_Helper();
		$tracker_connection      = new Synchrony_Tracker_Connection();
		$config_data_old         = $this->common_config_helper->fetch_synchrony_config_option();
		$data                    = ! empty( $_POST ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-settings' ) ? wp_unslash( $_POST ) : array();
		$has_validation_errors   = $this->check_validations( $data );
		$partner_code_validation = $this->partner_code_validation( $data );

		if ( ! $has_validation_errors || ! $partner_code_validation ) {
			parent::process_admin_options();
		}

		$config_data_new = $this->common_config_helper->fetch_synchrony_config_option();
		$config_array    = $helper->check_difference( $config_data_old, $config_data_new );
		if ( ! empty( $config_array ) ) {
			$this->logger->debug( 'configChangeDetails: ' . wp_json_encode( $config_array ) );
			$tracker_connection->retrieve_tracker_call( self::FLAG, $config_array );
		}
	}
	/**
	 * Check partner code validations.
	 *
	 * @param array $data This is post data.
	 *
	 * @return bool
	 */
	public function partner_code_validation( $data ) {
		$test_merchant_id      = ( isset( $data['woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_child_merchant_id'] ) ) ? $data['woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_child_merchant_id'] : '';
		$test_partner_code     = ( isset( $data['woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_child_partner_code'] ) ) ? $data['woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_child_partner_code'] : '';
		$deployed_merchant_id  = ( isset( $data['woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_child_merchant_id'] ) ) ? $data['woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_child_merchant_id'] : '';
		$deployed_partner_code = ( isset( $data['woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_child_partner_code'] ) ) ? $data['woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_child_partner_code'] : '';

		if ( ( $test_merchant_id && ! $test_partner_code ) || ( $deployed_merchant_id && ! $deployed_partner_code ) ) {
			$message = 'Please enter partner code.';
			WC_Admin_Settings::add_error( $message );
			return true;
		}
		return false;
	}

	/**
	 * Process/save the api settings
	 *
	 * @return void
	 */
	public function action_woocommerce_settings_save_checkout() {
		global $current_section;
		// Call settings function.
		if ( 'synchrony_apis' === $current_section ) {
			$helper             = new Synchrony_Helper();
			$tracker_connection = new Synchrony_Tracker_Connection();

			$endpoint_data_old = $this->config_helper->fetch_api_endpoint_list();

			$settings = $this->init_api_form_fields();
			WC_Admin_Settings::save_fields( $settings );

			$endpoint_data_new = $this->config_helper->fetch_api_endpoint_list();
			$endpoint_array    = $helper->check_difference( $endpoint_data_old, $endpoint_data_new );

			if ( ! empty( $endpoint_array ) ) {
				$this->logger->debug( 'endpointChangeDetails: ' . wp_json_encode( $endpoint_array ) );
				$tracker_connection->retrieve_tracker_call( self::FLAG, $endpoint_array );
			}
		}
		if ( $current_section ) {
			do_action( 'woocommerce_update_options_checkout_' . $current_section );
		}
	}

	/**
	 * Initializes the config form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$helper            = new Synchrony_Helper();
		$this->form_fields = $helper->config_form();
	}

	/**
	 * Initializes the api form fields.
	 *
	 * @return array
	 */
	public function init_api_form_fields() {
		$helper = new Synchrony_Helper();
		return $helper->apis_form();
	}

	/**
	 * Add settings to the specific section we created before.
	 *
	 * @param array  $settings This is admin settings details.
	 * @param string $current_section This is current section.
	 *
	 * @return mixed
	 */
	public function synchrony_api_all_settings( $settings, $current_section ) {
		if ( 'synchrony_promotions' === $current_section ) {
			$tag_config = new Synchrony_Promotag_Configurator();
			$settings   = $tag_config->retrieve_synchrony_promotions();
		}
		if ( 'mpp_banner' === $current_section ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=mpp-banner' ) );
		}
		return $settings;
	}


	/**
	 * Load custom script.
	 *
	 * @param mixed $hook Parameter passed to the function.
	 *
	 * @return void
	 */
	public function synchrony_custom_script( $hook ) {
		wp_enqueue_script( 'syf_custom_script', plugin_dir_url( __DIR__ ) . '../assets/js/syfcustomscript.js', array(), '3.0.0', false );
		$client_smb = new \Synchrony\Payments\Gateway\Synchrony_Smb();
		$domains    = $client_smb->retrieve_smb_domains();
		wp_localize_script(
			'syf_custom_script',
			'php_vars',
			array(
				'site_url'  => get_site_url(),
				'domains'   => $domains,
				'syf_nonce' => esc_html( wp_create_nonce( 'syf_transient_nonce' ) ),
				'is_admin'  => esc_html( current_user_can( 'manage_options' ) ),
			)
		);
	}

	/**
	 * Save meta box.
	 *
	 * @param mixed $post_id This is post id.
	 *
	 * @return void
	 */
	public function synchrony_save_meta_box( $post_id ) {
		$this->logger->info( 'start Save meta box process' );
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			$this->logger->info( 'Doing autosave defined so from here make return' );
			return;
		}
		$parent_id = wp_is_post_revision( $post_id );
		if ( true === $parent_id ) {
			$post_id = $parent_id;
		}
		$field = 'syf_manual_capture';
		// Verify nonce, check POST value if manual capture is selected.
		if ( isset( $_REQUEST['syf_manual_capture_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['syf_manual_capture_nonce'] ) ), 'syf_manual_capture_nonce' ) && isset( $_POST[ $field ] ) ) {
				$field_value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			if ( '-1' !== $field_value && 'online' === $field_value ) { // -1 to avoid empty selection.
				$this->logger->info( 'Synchrony Manual Capture Process is going to start' );
				$this->synchrony_manual_capture( $post_id, $field_value );
			} elseif ( 'offline' === $field_value ) {
				update_post_meta( $post_id, self::SYNCHRONY_MANUAL_CAPTURE, $field_value );
				$this->logger->info( 'offline Capture Process is going to start' );
				$order = wc_get_order( $post_id );
				$order->add_order_note( 'Manual capture done through offline' );
				$order->set_status( 'processing' );
				$order->save();
			}
		}
	}
	/**
	 * Manual Capture by API.
	 *
	 * @param mixed $post_id This is post id.
	 * @param mixed $field_value to set the online payment status.
	 *
	 * @return void
	 */
	private function synchrony_manual_capture( $post_id, $field_value ) {
		$this->logger->info( 'Inside Manual Capture by API function' );
		$token_id       = get_post_meta( $post_id, '_syf_order_token_id', true );
		$gateway        = new Synchrony_Gateway();
		$order          = wc_get_order( $post_id );
		$manual_capture = $gateway->manual_capture( $token_id, $order );
		if ( $manual_capture ) {
			update_post_meta( $post_id, self::SYNCHRONY_MANUAL_CAPTURE, $field_value );
			$order->add_order_note( 'Manual capture done through online by Synchrony' );
		} else {
			$order->add_order_note( 'Error in capturing payment' );
		}
		$order->save();
	}

	/**
	 * Custom form field for Display Synchrony logo on top section.
	 *
	 * @param int   $key This is key.
	 * @param array $data This is array of data.
	 *
	 * @return string
	 */
	public function generate_view_syflogo_html( $key, $data ) {
		return '<tr valign="top">
                  <th scope="row" class="titledesc"><img src="' . $data['value'] . '"/></th>
                  <td class="forminp"></td>
                </tr>';
	}

	/**
	 * Custom form field for Display Upload logo.
	 *
	 * @param int   $key This is key.
	 * @param array $data This is array of data.
	 *
	 * @return string
	 */
	public function generate_view_logo_html( $key, $data ) {
		$logo_url = ( isset( $data['value'] ) ) ? $data['value'] : $this->syf_logo;
		return '<tr valign="top">
                <th scope="row" class="titledesc"></th>
                <td class="forminp">
                  <fieldset>
                  ' . ( ( $logo_url ) ? '<img height="40" src="' . $logo_url . '"/>' : '' ) . '
                  <div><small>Get a logo image url from WordPress Media Library.</small></div>
                  <div><small>Max. upload file size is ' . self::UPLOAD_FILE_SIZE_LABEL . ', Allowed file types: JPG, JPEG, PNG. Recommend logo dimensions 57 x 36 pixels.</small></div>
                  </fieldset>
                </td>
              </tr>';
	}

	/**
	 * Checking configuration fields validations.
	 *
	 * @param array $request_data This is post data.
	 *
	 * @return bool
	 */
	public function check_validations( $request_data ) {
		$static_client_id      = $this->smb_helper->fetch_static_client_id();
		$sandbox_enable        = ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_test'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_test'] : '';
		$config_activation_key = $this->smb_helper->fetch_activation_key( $sandbox_enable );
		$has_validation_errors = 0;
		if ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_test'] ) && 'yes' === $request_data['woocommerce_synchrony-unifi-payments_synchrony_test'] ) {
			$environment    = 2;
			$activation     = ( isset( $request_data['woocommerce_synchrony-unifi-payments_test_enable_activation'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_test_enable_activation'] : '';
			$activation_key = $this->common_config_helper->config_test_activation_key( $request_data );
			$domain         = ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_test_smb_domain'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_test_smb_domain'] : '';
			$client_id      = $this->common_config_helper->config_test_client_id( $request_data );
		} else {
			$environment    = 1;
			$activation     = ( isset( $request_data['woocommerce_synchrony-unifi-payments_deployed_enable_activation'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_deployed_enable_activation'] : '';
			$activation_key = $this->common_config_helper->config_deployed_activation_key( $request_data );
			$domain         = ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_deployed_smb_domain'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_deployed_smb_domain'] : '';
			$client_id      = $this->common_config_helper->config_deployed_client_id( $request_data );
		}
		if ( 'yes' === $activation ) {
			$client_smb            = new \Synchrony\Payments\Helper\Synchrony_Smb_Helper();
			$getrows               = $client_smb->retrieve_columns_from_table( 'synchrony_partner_auth', '*', $environment );
			$has_validation_errors = $this->check_fields_validations( $activation_key, $domain, $getrows, $config_activation_key, $client_id, $static_client_id );
		}
		return $has_validation_errors;
	}
	/**
	 * Checking configuration fields validations.
	 *
	 * @param string $activation_key This is activation key.
	 * @param string $domain This is domain.
	 * @param array  $getrows This is getrows.
	 * @param string $config_activation_key This is config activation key.
	 * @param string $client_id This is client_id.
	 * @param string $static_client_id This is static client id from file.
	 * @return bool
	 */
	public function check_fields_validations( $activation_key, $domain, $getrows, $config_activation_key, $client_id, $static_client_id ) {
		$valid = false;
		if ( empty( $activation_key ) ) {
			$valid   = true;
			$message = 'Please check all the data is correct or not.';
		}
		if ( empty( $getrows ) && ! empty( $activation_key ) ) {
			$valid   = true;
			$message = 'Please activate the key first.';
		}
		if ( ! empty( $activation_key ) && ! empty( $getrows ) && $config_activation_key !== $activation_key ) {
			$valid   = true;
			$message = 'You have added new activation key, please activate again.';
		}
		if ( $valid ) {
			WC_Admin_Settings::add_error( $message );
			return true;
		}
		return false;
	}

	/**
	 * Check for upgrade.
	 *
	 * @return void
	 */
	public function sychrony_upgrade_init() {
		$config_settings = get_option( $this->common_config_helper::SYNCHRONY_PAYMENT_OPTION_KEY );
		if ( empty( $config_settings ) ) {
			$config_settings = array();
		}
		$app_version = $this->common_config_helper->app_version;
		if ( $config_settings['module_version'] !== $app_version ) {
			$config_settings['module_version'] = $app_version;
			update_option( $this->common_config_helper::SYNCHRONY_PAYMENT_OPTION_KEY, $config_settings );
		}
	}
}
