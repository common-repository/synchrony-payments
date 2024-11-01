<?php
/**
 * Helper Admin
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Admin\Synchrony_Tooltip;
use Synchrony\Payments\Logs\Synchrony_Logger;

/**
 * Class Synchrony_Helper
 */
class Synchrony_Helper {

	/**
	 * Cache timeout value.
	 *
	 * @var int
	 */
	public const CACHE_TIMEOUT = 86400;

	/**
	 * Partner Id.
	 *
	 * @var string
	 */
	public const PARTNER_ID = 'Partner Id';

	/**
	 * Client Id.
	 *
	 * @var string
	 */
	public const CLIENT_ID = 'Client Id';

	/**
	 * Build the config form.
	 */
	public function config_form() {
		$tooltips      = new Synchrony_Tooltip();
		$tooltip_value = $tooltips->retrieve_tooltips();

		$form1 = $this->default_options_fields( $tooltip_value );
		$form2 = $this->deployed_options_fields( $tooltip_value );
		$form3 = $this->test_options_fields( $tooltip_value );
		$form4 = $this->default_options_bottom_section( $tooltip_value );
		$form5 = $this->marketing_setting_fields( $tooltip_value );
		$form6 = $this->advanced_setting_fields( $tooltip_value );
		$form7 = $this->common_setting_fields( $tooltip_value );
		$form8 = $this->enable_promotion_fields( $tooltip_value );

		return array_merge( $form1, $form2, $form3, $form4, $form5, $form6, $form7, $form8 );
	}
	/**
	 * Return form fields(default)
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function default_options_fields( $tooltip_value ) {
		$common_config_helper = new Synchrony_Common_Config_Helper();
		$synchrony_logo       = $common_config_helper->fetch_synchrony_logo();
		$enabled_desc         = $tooltip_value['enable_syf_payment_gateway'];
		$title_desc           = $tooltip_value['title'];
		$gateway_mode_desc    = $tooltip_value['gateway_mode'];

		return array(
			'view_syflogo'   => array(
				'title' => __( 'Synchrony Logo', 'synchrony-payments' ),
				'type'  => 'view_syflogo',
				'value' => $synchrony_logo,
			),
			'enabled'        => array(
				'title'    => __( 'Enable Synchrony Payment', 'synchrony-payments' ),
				'label'    => __( 'Enable this payment gateway', 'synchrony-payments' ),
				'type'     => 'checkbox',
				'desc_tip' => $enabled_desc,
				'default'  => 'no',
			),
			'title'          => array(
				'title'    => __( 'Title', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => $title_desc,
				'default'  => __( 'Synchrony Financing – Pay Over Time', 'synchrony-payments' ),
			),
			'synchrony_test' => array(
				'title'       => __( 'Use Synchrony Sandbox', 'synchrony-payments' ),
				'label'       => __( 'Enable Test Mode', 'synchrony-payments' ),
				'type'        => 'select',
				'desc_tip'    => $gateway_mode_desc,
				'description' => __( 'This is the test mode of gateway.', 'synchrony-payments' ),
				'default'     => 'yes',
				'options'     => array(
					'yes' => 'yes',
					'no'  => 'no',
				),
			),
		);
	}

	/**
	 * Return form fields(production)
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function deployed_options_fields( $tooltip_value ) {
		$common_config_helper                  = new Synchrony_Common_Config_Helper();
		$is_synchrony_test                     = $common_config_helper->does_test_mode();
		$synchrony_deployed_partner_id_desc    = $tooltip_value['synchrony_deployed_partner_id'];
		$synchrony_deployed_client_id_desc     = $tooltip_value['synchrony_deployed_client_id'];
		$synchrony_deployed_client_secret_desc = $tooltip_value['synchrony_deployed_client_secret'];
		$is_production_activation              = $common_config_helper->does_production_activation();
		return array(
			'unifi_synchrony_deployed'                     => array(
				'title' => __( 'Production', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => ( ! $is_synchrony_test ) ? 'deployed_div' : 'hide_deployed deployed_div',
			),
			'deployed_enable_activation'                   => array(
				'title'   => __( 'Do you have activation key ?', 'synchrony-payments' ),
				'type'    => 'select',
				'default' => 'yes',
				'options' => array(
					'yes' => 'yes',
					'no'  => 'no',
				),
			),
			'deployed_activation'                          => array(
				'title' => __( 'Production Activation', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => ( ! $is_synchrony_test && $is_production_activation ) ? 'deployed_smb_div' : 'hide_deployed_smb deployed_smb_div',
			),
			'synchrony_deployed_activation_key'            => array(
				'title' => __( 'Activation Key', 'synchrony-payments' ),
				'type'  => 'text',
			),
			'synchrony_deployed_smb_domain'                => array(
				'title'    => __( 'Online Shop Domain', 'synchrony-payments' ),
				'type'     => 'text',
				'class'    => 'smbn_domain',
				'desc_tip' => __( '10 Domains are allowed', 'synchrony-payments' ),
			),
			'deployed_traditional'                         => array(
				'title' => __( 'Prod Traditional', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => ( ! $is_synchrony_test && ! $is_production_activation ) ? 'deployed_traditional_div' : 'hide_deployed_traditional deployed_traditional_div',
			),
			'synchrony_deployed_digitalbuy_api_partner_id' => array(
				'title'    => self::PARTNER_ID,
				'type'     => 'text',
				'desc_tip' => $synchrony_deployed_partner_id_desc,
			),
			'synchrony_deployed_digitalbuy_api_smb_partner_id' => array(
				'title'    => self::PARTNER_ID,
				'type'     => 'text',
				'desc_tip' => $synchrony_deployed_partner_id_desc,
				'class'    => 'smb_element',
			),
			'synchrony_deployed_digitalbuy_api_child_merchant_id' => array(
				'title'    => __( 'Child Merchant Id', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => '',
			),
			'synchrony_deployed_digitalbuy_api_child_partner_code' => array(
				'title'    => __( 'Partner Code', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => '',
			),
			'synchrony_deployed_digitalbuy_api_client_id'  => array(
				'title'    => self::CLIENT_ID,
				'type'     => 'password',
				'desc_tip' => $synchrony_deployed_client_id_desc,
			),
			'synchrony_deployed_digitalbuy_api_smb_client_id' => array(
				'title'    => self::CLIENT_ID,
				'type'     => 'password',
				'desc_tip' => $synchrony_deployed_client_id_desc,
				'class'    => 'smb_element',
			),
			'synchrony_deployed_digitalbuy_api_client_secret' => array(
				'title'    => __( 'Client Secret', 'synchrony-payments' ),
				'type'     => 'password',
				'desc_tip' => $synchrony_deployed_client_secret_desc,
			),

		);
	}
	/**
	 * Return form fields(test)
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function test_options_fields( $tooltip_value ) {
		$common_config_helper              = new Synchrony_Common_Config_Helper();
		$is_synchrony_test                 = $common_config_helper->does_test_mode();
		$synchrony_test_partner_id_desc    = $tooltip_value['synchrony_test_partner_id'];
		$synchrony_test_client_id_desc     = $tooltip_value['synchrony_test_client_id'];
		$synchrony_test_client_secret_desc = $tooltip_value['synchrony_test_client_secret'];
		$is_sandbox_activation             = $common_config_helper->does_sandbox_activation();
		return array(
			'unifi_sandobx'                                => array(
				'title' => __( 'Sandbox', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => ( $is_synchrony_test ) ? 'synchrony_test_div' : 'hide_synchrony_test synchrony_test_div',
			),
			'test_enable_activation'                       => array(
				'title'   => __( 'Do you have activation key ?', 'synchrony-payments' ),
				'type'    => 'select',
				'default' => 'yes',
				'options' => array(
					'yes' => 'yes',
					'no'  => 'no',
				),
			),
			'activation'                                   => array(
				'title' => __( 'Sandbox Activation', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => ( $is_synchrony_test && $is_sandbox_activation ) ? 'test_smb_div' : 'hide_test_smb test_smb_div',
			),
			'synchrony_test_activation_key'                => array(
				'title' => __( 'Activation Key', 'synchrony-payments' ),
				'type'  => 'text',
			),
			'synchrony_test_smb_domain'                    => array(
				'title'    => __( 'Online Shop Domain', 'synchrony-payments' ),
				'type'     => 'text',
				'class'    => 'smbn_domain',
				'desc_tip' => __( '10 Domains are allowed', 'synchrony-payments' ),
			),
			'traditional'                                  => array(
				'title' => __( 'Sandbox Traditional', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => ( $is_synchrony_test && ! $is_sandbox_activation ) ? 'test_traditional_div' : 'hide_test_traditional test_traditional_div',
			),
			'synchrony_test_digitalbuy_api_partner_id'     => array(
				'title'    => self::PARTNER_ID,
				'type'     => 'text',
				'desc_tip' => $synchrony_test_partner_id_desc,
			),
			'synchrony_test_digitalbuy_api_smb_partner_id' => array(
				'title'    => self::PARTNER_ID,
				'type'     => 'text',
				'desc_tip' => $synchrony_test_partner_id_desc,
				'class'    => 'smb_element',
			),
			'synchrony_test_digitalbuy_api_child_merchant_id' => array(
				'title'    => __( 'Child Merchant Id', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => '',
			),
			'synchrony_test_digitalbuy_api_child_partner_code' => array(
				'title'    => __( 'Partner Code', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => '',
			),
			'synchrony_test_digitalbuy_api_client_id'      => array(
				'title'    => self::CLIENT_ID,
				'type'     => 'password',
				'desc_tip' => $synchrony_test_client_id_desc,
			),
			'synchrony_test_digitalbuy_api_smb_client_id'  => array(
				'title'    => self::CLIENT_ID,
				'type'     => 'password',
				'desc_tip' => $synchrony_test_client_id_desc,
				'class'    => 'smb_element',
			),
			'synchrony_test_digitalbuy_api_client_secret'  => array(
				'title'    => __( 'Client Secret', 'synchrony-payments' ),
				'type'     => 'password',
				'desc_tip' => $synchrony_test_client_secret_desc,
			),
		);
	}


	/**
	 * Return form fields(default section 2)
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function default_options_bottom_section( $tooltip_value ) {
		$common_config_helper = new Synchrony_Common_Config_Helper();
		$get_option           = $common_config_helper->fetch_synchrony_config_option();
		$synchrony_logo       = $common_config_helper->fetch_synchrony_logo();
		$logo_desc            = $tooltip_value['logo'];
		$logo_url             = ( isset( $get_option['logo'] ) ) ? $get_option['logo'] : $synchrony_logo;
		$payment_action_desc  = $tooltip_value['payment_action'];

		return array(
			'default_bottom_section' => array(
				'title' => '',
				'type'  => 'title',
			),
			'logo'                   => array(
				'title'    => __( 'Logo', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => $logo_desc,
			),
			'view_logo'              => array(
				'title' => __( 'Synchrony Logo', 'synchrony-payments' ),
				'type'  => 'view_logo',
				'value' => $logo_url,
			),
			'payment_action'         => array(
				'title'       => __( 'Payment Action', 'synchrony-payments' ),
				'label'       => __( 'Select Payment Action', 'synchrony-payments' ),
				'type'        => 'select',
				'desc_tip'    => $payment_action_desc,
				'description' => __( 'Select Payment Action.', 'synchrony-payments' ),
				'default'     => 'authorize-capture',
				'options'     => array(
					'authorize'         => 'Authorize',
					'authorize-capture' => 'Authorize & Capture',
				),
			),
		);
	}

	/**
	 * Return form fields(production & test)
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function marketing_setting_fields( $tooltip_value ) {
		$display_area_desc               = $tooltip_value['display_area'];
		$widget_location_on_pdp          = $tooltip_value['pdp_widget_location'];
		$parent_price_class_selector_pdp = $tooltip_value['parent_price_class_selector_pdp'];
		$price_class_selector_pdp        = $tooltip_value['price_class_selector_pdp'];
		$default_varient_price           = $tooltip_value['default_varient_price'];
		return array(
			'unifi_marketing'                 => array(
				'title' => __( 'Synchrony Marketing', 'synchrony-payments' ),
				'type'  => 'title',
			),
			'show_unify_widget'               => array(
				'title'             => __( 'Display Area', 'synchrony-payments' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'options'           => array(
					'all'      => __( 'All', 'synchrony-payments' ),
					'product'  => __( 'Product Page', 'synchrony-payments' ),
					'cart'     => __( 'Cart Page', 'synchrony-payments' ),
					'checkout' => __( 'Checkout Page', 'synchrony-payments' ),
					'plp'      => __( 'Product Listing Page', 'synchrony-payments' ),
				),
				'desc_tip'          => $display_area_desc,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select Pages to display unify widgets', 'synchrony-payments' ),
				),
			),
			'widget_location_on_pdp'          => array(
				'title'    => __( 'PDP page Widget Hook', 'synchrony-unifi-payments' ),
				'type'     => 'select',
				'desc_tip' => $widget_location_on_pdp,
				'default'  => 'woocommerce_single_product_summary',
				'options'  => array(
					'woocommerce_before_single_product'    => 'Woocommerce before single product',
					'woocommerce_before_single_product_summary' => 'Woocommerce before single product summary',
					'woocommerce_single_product_summary'   => 'Woocommerce single product summary',
					'woocommerce_before_add_to_cart_form'  => 'Woocommerce before add to cart form',
					'woocommerce_before_variations_form'   => 'Woocommerce before variations form',
					'woocommerce_before_add_to_cart_button' => 'Woocommerce before add to cart button',
					'woocommerce_before_single_variation'  => 'Woocommerce before single variation',
					'woocommerce_single_variation'         => 'Woocommerce single variation',
					'woocommerce_before_add_to_cart_quantity' => 'Woocommerce before add to cart quantity',
					'woocommerce_after_add_to_cart_quantity' => 'Woocommerce after add to cart quantity',
					'woocommerce_after_single_variation'   => 'Woocommerce after single variation',
					'woocommerce_after_add_to_cart_button' => 'Woocommerce after add to cart button',
					'woocommerce_after_variations_form'    => 'Woocommerce after variations form',
					'woocommerce_product_meta_start'       => 'Woocommerce product meta start',
					'woocommerce_product_meta_end'         => 'Woocommerce product meta end',
					'woocommerce_after_single_product_summary' => 'Woocommerce after single product summary',
					'woocommerce_after_single_product'     => 'Woocommerce after single product',
				),
			),
			'default_varient_price'           => array(
				'title'    => __( 'Enable Default Product Variable Price for Multiwidget', 'synchrony-unifi-payments' ),
				'type'     => 'select',
				'desc_tip' => $default_varient_price,
				'default'  => 0,
				'options'  => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),

			'parent_price_class_selector_pdp' => array(
				'title'    => __( 'Custom Parent Price Selector Class/ Id Product Page', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => $parent_price_class_selector_pdp,
			),
			'price_class_selector_pdp'        => array(
				'title'    => __( 'Custom Price Selector Class/ Id Product Page', 'synchrony-payments' ),
				'type'     => 'text',
				'desc_tip' => $price_class_selector_pdp,
			),
			'cart_button_enabled'             => array(
				'title'       => __( 'Digital Buy Available in Cart', 'synchrony-payments' ),
				'type'        => 'select',
				'default'     => 0,
				'description' => '',
				'options'     => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),
			'cart_button_image_url'           => array(
				'title'   => __( 'Cart Button Image URL', 'synchrony-payments' ),
				'type'    => 'text',
				'default' => 'https://shop.mysynchrony.com/v2/public/img/default_cart_button_image.png',
			),
			'cart_button_image_text'          => array(
				'title'   => __( 'Cart Button Image Alt Text', 'synchrony-payments' ),
				'type'    => 'text',
				'default' => 'Pay with Synchrony',
			),
		);
	}

	/**
	 * Return form fields
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function advanced_setting_fields( $tooltip_value ) {
		$common_config_helper = new Synchrony_Common_Config_Helper();
		$app_version          = $common_config_helper->app_version;
		$module_version       = ( isset( $tooltip_value['module_version'] ) ) ? $tooltip_value['module_version'] : '';
		$api_request_timeout  = ( isset( $tooltip_value['api_request_timeout'] ) ) ? $tooltip_value['api_request_timeout'] : '';
		return array(
			'advanced_setting' => array(
				'title' => __( 'Advanced Settings', 'synchrony-payments' ),
				'type'  => 'title',
			),
			'module_version'   => array(
				'title'             => __( 'Module Version', 'synchrony-payments' ), // Translators : This placeholder represents module version.
				'label'             => '',
				'type'              => 'text',
				'desc_tip'          => $module_version,
				'default'           => $app_version,
				'custom_attributes' => array( 'readonly' => 'readonly' ),
			),
			'time_out'         => array(
				'title'             => __( 'API Request Timeout', 'synchrony-payments' ), // Translators: This placeholder represents API request timeout.
				'label'             => __( 'API Request Timeout', 'synchrony-payments' ),
				'type'              => 'number',
				'desc_tip'          => $api_request_timeout,
				'custom_attributes' => array( 'maxlength' => '5' ),
				'description'       => __( 'This is for the timeout.', 'synchrony-payments' ),
				'default'           => '',
			),
		);
	}

	/**
	 * Common Setting Form Fields
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function common_setting_fields( $tooltip_value ) {
		$debug_mode           = ( isset( $tooltip_value['debug_mode'] ) ) ? $tooltip_value['debug_mode'] : '';
		$address_type_to_pass = ( isset( $tooltip_value['address_type_to_pass'] ) ) ? $tooltip_value['address_type_to_pass'] : '';
		$logging_type         = ( isset( $tooltip_value['send_error_to_syf'] ) ) ? $tooltip_value['send_error_to_syf'] : '';
		$cache_time_out       = ( isset( $tooltip_value['cache_time_out'] ) ) ? $tooltip_value['cache_time_out'] : '';
		$address_on_file      = ( isset( $tooltip_value['address_on_file'] ) ) ? $tooltip_value['address_on_file'] : '';
		return array(
			'common_payment_settings' => array(
				'title' => __( 'Common Payment Settings', 'synchrony-payments' ), // Translators: This placeholder represents Common payment settings.
				'type'  => 'title',
			),
			'debug'                   => array(
				'title'       => __( 'Debug Mode', 'synchrony-payments' ),
				'type'        => 'select',
				'desc_tip'    => $debug_mode,
				'default'     => 1,
				'description' => '',
				'options'     => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),
			'address_type_to_pass'    => array(
				'title'       => __( 'Address Passed to Modals', 'synchrony-payments' ), // Translators: This placeholder represents Address Passed to UNIFI Modals.
				'type'        => 'select',
				'desc_tip'    => $address_type_to_pass,
				'default'     => 'billing',
				'description' => '',
				'options'     => array(
					'billing'  => 'Billing Address',
					'shipping' => 'Shipping Address',
				),
			),
			'logging_type'            => array(
				'title'       => __( 'Send Error Log into Synchrony Server', 'synchrony-payments' ), // Translators: This placeholder represents Send error log into Synchrony Server.
				'type'        => 'select',
				'desc_tip'    => $logging_type,
				'default'     => 1,
				'description' => __( 'All Error Logs will be sent to synchrony server', 'synchrony-payments' ),
				'options'     => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),
			'widget_display_approach' => array(
				'title'       => __( 'Widget Display in collection page (PLP)', 'synchrony-payments' ),
				'type'        => 'select',
				'desc_tip'    => $tooltip_value['plp_page'],
				'default'     => 0,
				'description' => __( 'MultiWidget Tag Approach', 'synchrony-payments' ),
				'options'     => array(
					'1' => 'Remote rule execution',
					'2' => 'Local rule execution',
				),

			),

			'cache_time_out'          => array(
				'title'    => __( 'Cache Time Out', 'synchrony-payments' ), // Translators: This placeholder represents Cache time out.
				'type'     => 'text',
				'desc_tip' => $cache_time_out,
				'default'  => self::CACHE_TIMEOUT,
			),
			'enable_savelater'        => array(
				'title'    => __( 'Allow customer to save card', 'synchrony-payments' ),
				'class'    => 'synchrony-save-card-field',
				'type'     => 'select',
				'desc_tip' => '',
				'default'  => 0,
				'options'  => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),

			'pop_up'                  => array(
				'title'       => __( 'Checkout Modal Presentation', 'synchrony-payments' ),
				'type'        => 'select',
				'desc_tip'    => $tooltip_value['pop_up'],
				'default'     => 0,
				'description' => '',
				'options'     => array(
					'1' => 'Overlay',
					'2' => 'Redirect',
				),
			),
			'enable_mppbanner'        => array(
				'title'    => __( 'Enable Mpp Banner', 'synchrony-payments' ),
				'type'     => 'select',
				'desc_tip' => '',
				'default'  => 0,
				'options'  => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),
			'address_on_file'         => array(
				'title'       => __( 'Notify Address Failures', 'synchrony-payments' ),
				'type'        => 'select',
				'desc_tip'    => $address_on_file,
				'default'     => 0,
				'description' => '',
				'options'     => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),
			'syf_custom_css'          => array(
				'title'    => __( 'Custom CSS', 'synchrony-payments' ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Add custom css', 'synchrony-payments' ),
			),
		);
	}

	/**
	 * Return form fields
	 *
	 * @param  array $tooltip_value This is for tooltip text.
	 *
	 * @return array
	 */
	public function enable_promotion_fields( $tooltip_value ) {
		$enable_syf_promotions_desc = $tooltip_value['enable_syf_promotions'];
		return array(
			'synchrony_promotions_settings' => array(
				'title' => __( 'Synchrony Promotions Settings', 'synchrony-payments' ),
				'type'  => 'title',
				'class' => 'synchrony-promo-field',
			),
			'tag_rules_option'              => array(
				'title'    => __( 'Enable Synchrony Promotions', 'synchrony-payments' ),
				'type'     => 'select',
				'desc_tip' => $enable_syf_promotions_desc,
				'default'  => 0,
				'options'  => array(
					'1' => 'Yes',
					'0' => 'No',
				),
			),
		);
	}

	/**
	 * API Form Fields
	 *
	 * @return array
	 */
	public function apis_form() {
		global $current_section;
		$settings = array();
		if ( 'synchrony_apis' === $current_section ) {
			$settings[] = array(
				'name' => __( 'Synchrony API', 'synchrony-payments' ),
				'type' => 'title',
				'id'   => 'unifiapi',
			);
			$settings[] = $this->settings_field_value( 'Production Authentication API Endpoint', 'synchrony_deployed_authentication_api_endpoint' );
			$settings[] = $this->settings_field_value( 'Sandbox Authentication API Endpoint', 'synchrony_test_authentication_api_endpoint' );
			$settings[] = $this->settings_field_value( 'Production Token API Endpoint', 'synchrony_deployed_token_api_endpoint' );
			$settings[] = $this->settings_field_value( 'Sandbox Token API Endpoint', 'synchrony_test_token_api_endpoint' );
			$settings[] = $this->settings_field_value( 'Production Synchrony Script Endpoint', 'synchrony_deployed_unifi_script_endpoint' );
			$settings[] = array(
				'name' => __( 'Sandbox Synchrony Script Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_unifi_script_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Transact-API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_transactapi_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Transact-API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_transactapi_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Logger API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_logger_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Logger API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_logger_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Configuration Monitoring API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_moduletracking_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Configuration Monitoring API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_moduletracking_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Find Status-API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_findstatus_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Find Status-API Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_findstatus_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Partner Activate Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_partner_activate_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Partner Activate Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_partner_activate_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production SMB Domain Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_smb_domain_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox SMB Domain Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_smb_domain_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Client ID Rotation Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_client_id_rotation_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Client ID Rotation Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_client_id_rotation_api_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'unifiapi',
			);
			$settings[] = array(
				'name' => __( 'Promo Tags API', 'synchrony-payments' ),
				'type' => 'title',
				'id'   => 'promotagapi',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Promo Tag Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_promo_tag_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Promo Tag Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_promo_tag_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox Promo Tag Determination Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_promo_tag_determination_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production Promo Tag Determination Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_promo_tag_determination_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Sandbox MPP Banner Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_test_banner_mpp_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'name' => __( 'Production MPP Banner Endpoint', 'synchrony-payments' ),
				'id'   => 'synchrony_deployed_banner_mpp_endpoint',
				'type' => 'text',
			);
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'promotagapi',
			);
		}
		return $settings;
	}
	/**
	 * API Form Fields value.
	 *
	 * @param  string $name name string.
	 * @param  int    $id field id.
	 *
	 * @return array
	 */
	public function settings_field_value( $name, $id ) {
		return array(
			'name' => $name,
			'id'   => $id,
			'type' => 'text',
		);
	}
	/**
	 * Check Modified Values and Fields.
	 *
	 * @param  array $old_data This is for old data.
	 * @param  array $new_data This is new data.
	 *
	 * @return array
	 */
	public function check_difference( $old_data, $new_data ) {
		$difference = array();
		if ( ! isset( $old_data ) || ! isset( $new_data ) ) {
			return array();
		}
		foreach ( $old_data as $key => $value ) {
			$path = preg_replace( '/\s+/', '', ucwords( str_replace( '_', ' ', $key ) ) );
			if ( $value !== $new_data[ $key ] ) {
				if ( is_array( $value ) ) {
					$old_value = implode( ',', $value );
					$new_value = is_array( $new_data[ $key ] ) ? implode( ',', $new_data[ $key ] ) : '';
				} else {
					$old_value = $value;
					$new_value = $new_data[ $key ];
				}
				$difference[] = array(
					'fieldName' => substr( $path, 0, 90 ),
					'oldValue'  => $old_value,
					'newValue'  => $new_value,
				);
			}
		}
		return $difference;
	}
	/**
	 * Show Unify Widgets.
	 *
	 * @return array
	 */
	public function show_unify_widget() {
		$tooltips          = new Synchrony_Tooltip();
		$tooltip_value     = $tooltips->retrieve_tooltips();
		$display_area_desc = $tooltip_value['display_area'];

		return array(
			'title'             => __( 'Display Area', 'synchrony-payments' ),
			'type'              => 'multiselect',
			'class'             => 'wc-enhanced-select',
			'css'               => 'width: 400px;',
			'default'           => '',
			'options'           => array(
				'all'      => __( 'All', 'synchrony-payments' ),
				'product'  => __( 'Product Page', 'synchrony-payments' ),
				'cart'     => __( 'Cart Page', 'synchrony-payments' ),
				'checkout' => __( 'Checkout Page', 'synchrony-payments' ),
				'plp'      => __( 'Product Listing Page', 'synchrony-payments' ),
			),
			'desc_tip'          => $display_area_desc,
			'custom_attributes' => array(
				'data-placeholder' => __( 'Select Pages to display unify widgets', 'synchrony-payments' ),
			),
		);
	}
	/**
	 * Payment action field in admin configuration.
	 *
	 * @return array
	 */
	public function payment_action() {
		$tooltips            = new Synchrony_Tooltip();
		$tooltip_value       = $tooltips->retrieve_tooltips();
		$payment_action_desc = $tooltip_value['payment_action'];
		return array(
			'title'       => __( 'Payment Action', 'synchrony-payments' ),
			'label'       => __( 'Select Payment Action', 'synchrony-payments' ),
			'type'        => 'select',
			'desc_tip'    => $payment_action_desc,
			'description' => __( 'Select Payment Action.', 'synchrony-payments' ),
			'default'     => 'authorize-capture',
			'options'     => array(
				'authorize'         => 'Authorize',
				'authorize-capture' => 'Authorize & Capture',
			),
		);
	}
	/**
	 * Sandbox field in admin configuration.
	 *
	 * @return array
	 */
	public function sandbox() {
		$tooltips          = new Synchrony_Tooltip();
		$tooltip_value     = $tooltips->retrieve_tooltips();
		$gateway_mode_desc = $tooltip_value['gateway_mode'];
		return array(
			'title'       => __( 'Use Synchrony Sandbox', 'synchrony-payments' ),
			'label'       => __( 'Enable Test Mode', 'synchrony-payments' ),
			'type'        => 'select',
			'desc_tip'    => $gateway_mode_desc,
			'description' => __( 'This is the test mode of gateway.', 'synchrony-payments' ),
			'default'     => 'no',
			'options'     => array(
				'yes' => 'yes',
				'no'  => 'no',
			),
		);
	}
}
