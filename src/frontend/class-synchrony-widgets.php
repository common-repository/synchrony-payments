<?php
/**
 * Synchrony_cart_checkout_widgets File
 *
 * @package Synchrony\Payments\Frontend
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\payments\Frontend\Synchrony_Frontend;

/**
 * Class Synchrony_Widgets
 */
class Synchrony_Widgets {

	/**
	 * Synchrony_Setting_Config_Helper Class.
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;


	/**
	 * Install constructor
	 */
	public function __construct() {
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$this->init();
	}
	/**
	 * Init the Widget by setting up action and filter hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->setting_config_helper->synchrony_plugin_active() ) {
			add_action( 'woocommerce_after_cart', array( $this, 'cart_widget' ), 10 );
			add_action( 'woocommerce_review_order_after_payment', array( $this, 'checkout_widget' ), 10 );
			add_shortcode( 'synchrony_cart_widget', array( $this, 'cart_widget_block' ) );
			add_shortcode( 'synchrony_checkout_widget', array( $this, 'checkout_widget_block' ) );
		}
	}

	/**
	 * Load Widget on Cart page
	 *
	 * @return void
	 */
	public function cart_widget() {
		$short_code = '[synchrony_cart_widget]';
		echo do_shortcode( wp_kses_post( $short_code ) );
	}

	/**
	 * Load Widget on Cart page
	 *
	 * @return string
	 */
	public function cart_widget_block() {
		$cart_block_widget = new Synchrony_Frontend();
		return $cart_block_widget->retrieve_widget_with_price();
	}

		/**
		 * Load Widget on Checkout page
		 *
		 * @return void
		 */
	public function checkout_widget() {
		$short_code = '[synchrony_checkout_widget]';
		echo do_shortcode( wp_kses_post( $short_code ) );
	}

	/**
	 * Load Widget on Checkout page
	 *
	 * @return string
	 */
	public function checkout_widget_block() {
		$checkout_block_widget = new Synchrony_Frontend();
		return $checkout_block_widget->retrieve_widget_with_price();
	}
}
