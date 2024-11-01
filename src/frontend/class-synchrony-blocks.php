<?php
/**
 * Block File
 *
 * @package Synchrony\Payments\Frontend
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Synchrony\Payments\Admin\Synchrony_Admin;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Frontend\Synchrony_Cart_Data;
use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;


/**
 * Class Synchrony_Blocks
 */
final class Synchrony_Blocks extends AbstractPaymentMethodType {
	/**
	 * Gateway
	 *
	 * @var mixed
	 */
	private $gateway;
	/**
	 * Name
	 *
	 * @var string
	 */
	protected $name = 'synchrony-unifi-payments';
	/**
	 * Define Init to add woocommerce block.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_synchrony-unifi-payments_settings', array() );
		if ( ! is_admin() ) {
			$this->gateway = new Synchrony_Admin();
		}
	}
	/**
	 * Retrieve syf plugin active flag
	 *
	 * @return bool
	 */
	public function is_active() {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		return $setting_config_helper->synchrony_plugin_active();
	}
	/**
	 * Payment method script
	 *
	 * @return mixed
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-synchrony-unifi-payments-blocks-integration',
			plugins_url( '../../assets/js/checkout.js', __FILE__ ),
			array(
				'react',
				'react-dom',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			'11.0.3',
			true
		);
		return array( 'wc-synchrony-unifi-payments-blocks-integration' );
	}
	/**
	 * Get Payment methods
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		if ( is_admin() ) {
			return array();
		}
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$syf_data              = new Synchrony_Cart_Data();
		$common_config_helper  = new Synchrony_Common_Config_Helper();
		$user_id               = get_current_user_id();
		$syf_paymenttoken      = get_user_meta( $user_id, 'syf_paymenttoken', true );
		$syf_cardonfileflag    = get_user_meta( $user_id, 'syf_cardonfileflag', true );
		$checked               = '';
		$is_save_card_enable   = '';
		if ( $setting_config_helper->customer_allow_to_save_card() && '' !== $syf_paymenttoken && 'yes' === $syf_cardonfileflag ) {
			$checked = 1;
		}
		if ( $setting_config_helper->customer_allow_to_save_card() && is_user_logged_in() && 'synchrony-unifi-payments' === $this->gateway->id ) {
			$is_save_card_enable = 1;
		}
		$order_id      = WC()->session->get( 'order_awaiting_payment' ) ? WC()->session->get( 'order_awaiting_payment' ) : '';
		$config_helper = new Synchrony_Config_Helper();
		$pop_up        = $config_helper->fetch_pop_up();
		$get_option    = $common_config_helper->fetch_synchrony_config_option();
		$syf_logo      = $common_config_helper->fetch_synchrony_logo();
		if ( isset( $get_option['logo'] ) ) {
			$syf_logo = $get_option['logo'];
		}
		return array(
			'syf_title'           => $this->gateway->title,
			'is_save_card_enable' => $is_save_card_enable,
			'checked'             => $checked,
			'syf_data'            => $syf_data->map_dbuy_data(),
			'order_id'            => $order_id,
			'is_overlay'          => $pop_up,
			'icon'                => $syf_logo,
			'description'         => '',
			'address_type'        => $setting_config_helper->fetch_address_type(),
		);
	}
}
