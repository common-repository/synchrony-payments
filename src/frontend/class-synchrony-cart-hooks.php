<?php
/**
 * Synchrony_Cart_Hooks File
 *
 * @package Synchrony\Payments\Frontend
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Widget_Helper;

/**
 * Class Synchrony_Cart_Hooks
 */
class Synchrony_Cart_Hooks {

	/**
	 * AUTH flag for Pay with synchrony button on cart page
	 *
	 * @var int
	 */
	const AUTH = 1;

	/**
	 * Install constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init the Widget by setting up action and filter hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'remove_admin_bar' ) );
		add_shortcode( 'synchrony_mppbanner_link_widget', array( $this, 'mppb_link_shortcodes' ) );
		add_shortcode( 'synchrony_button', array( $this, 'pay_with_synchrony_button' ) );
		add_action( 'wp_ajax_checkout_form_data', array( $this, 'checkout_form_data' ) );
		add_action( 'wp_ajax_nopriv_checkout_form_data', array( $this, 'checkout_form_data' ) );
		add_action( 'wp_footer', array( $this, 'postback_form_checkout' ), 9999 );
		add_action( 'woocommerce_after_order_notes', array( $this, 'add_custom_checkout_popup_status_field' ) );
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		if ( $setting_config_helper->synchrony_plugin_active() && $setting_config_helper->customer_allow_to_save_card() ) {
			add_filter( 'woocommerce_gateway_description', array( $this, 'synchrony_card_on_file_flag' ), 20, 2 );
		}
		if ( $setting_config_helper->synchrony_plugin_active() && $setting_config_helper->fetch_split_modal_details( 'cart_button_enabled' ) ) {
			add_filter( 'woocommerce_proceed_to_checkout', array( $this, 'pay_with_synchrony_button_cart' ), 20 );
		}
	}

	/**
	 * Remove Admin Bar
	 *
	 * @return void
	 */
	public function remove_admin_bar() {
		if ( ! is_admin() && is_page( 'synchrony-payment' ) ) {
			show_admin_bar( false );
		}
	}

	/**
	 * Create Shortcode for MPP Link.
	 *
	 * @param array $atts This is shortcode's attributes.
	 *
	 * @return mixed
	 */
	public function mppb_link_shortcodes( $atts ) {
		$widget_helper = new Synchrony_Widget_Helper();
		if ( ! $widget_helper->enable_mppbanner() ) {
			return '';
		}
		$setting_config_helper = new Synchrony_Setting_Config_Helper();

		$is_plugin_active = $setting_config_helper->synchrony_plugin_active();
		$shortcode_output = '';
		if ( $is_plugin_active ) {
			$link_class = ( isset( $atts['class'] ) ) ? $atts['class'] : '';
			$link_label = ( isset( $atts['label'] ) ) ? $atts['label'] : 'Financing';
			if ( isset( $atts['list'] ) ) {
				$shortcode_output = '<li class="' . $link_class . '"><a class="MPPAnywhereClass">' . $link_label . '</a></li>';
			} else {
				$shortcode_output = '<div class="' . $link_class . '"><a class="MPPAnywhereClass">' . $link_label . '</a></div>';
			}
		}
		return wp_kses_post( $shortcode_output );
	}

	/**
	 * Get digitalbuy modal data and checkout form validation and order creation.
	 *
	 * @return void
	 */
	public function checkout_form_data() {
		WC()->checkout()->process_checkout();
	}

	/**
	 * Add hidden form on checkout page for callback
	 *
	 * @return mixed
	 */
	public function postback_form_checkout() {
		global $wp;
		if ( is_checkout() ) {
			$timestamp   = gmdate( 'Y-m-d H:i:s' );
			$postbackurl = get_rest_url( null, '/syf/v1/callback' );
			$user_id     = get_current_user_id();
			echo '<form style="display: none;" name="postbackform" id="postbackform" method="post" action="' . esc_html( $postbackurl ) . '">
					<input name="form_key" type="hidden" value="' . esc_html( wp_rand( 1, 10 ) ) . '" />
					<input type="hidden" id="tokenId" name="tokenId" value="NOTOKEN" />
					<input type="hidden" id="timestamp" name="timestamp" value="' . esc_html( $timestamp ) . '" />
					<input type="hidden" id="reference_id" name="reference_id" value="" />
					<input type="hidden" id="user_id" name="user_id" value="' . esc_html( $user_id ) . '" />
				</form>';
		}
	}

	/**
	 * Add hidden field on checkout page for checkout popup
	 *
	 * @return mixed
	 */
	public function add_custom_checkout_popup_status_field() {
		$config_helper = new Synchrony_Config_Helper();
		$pop_up        = $config_helper->fetch_pop_up();
		echo '<div id="popup_status_hidden_checkout_field">
				<input type="hidden" class="input-hidden" name="checkout_popup_status" id="checkout_popup_status" value="' . esc_attr( $pop_up ) . '">
		</div>';
	}

	/**
	 * Display Link on cart Page : Pay with Synchrony button.
	 *
	 * @return void
	 */
	public function pay_with_synchrony_button_cart() {
		$short_code = '[synchrony_button]';
		echo do_shortcode( wp_kses_post( $short_code ) );
	}

	/**
	 * Shortcode : Pay with Synchrony button.
	 *
	 * @return string
	 */
	public function pay_with_synchrony_button() {
		$common_config_helper  = new Synchrony_Common_Config_Helper();
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$reference_variable    = $common_config_helper->generate_reference_id();
		$reference_link        = get_permalink( get_page_by_path( 'synchrony-payment' ) ) . '?' . $reference_variable;
		$link                  = wp_nonce_url( $reference_link, 'auth' );
		$button_image_url      = $setting_config_helper->fetch_split_modal_details( 'cart_button_image_url' );
		$button_image_text     = $setting_config_helper->fetch_split_modal_details( 'cart_button_image_text' );
		return '<a href="' . esc_html( $link ) . '" class="button">
			<img src="' . esc_html( $button_image_url ) . '" alt="' . esc_html( $button_image_text ) . '" title="' . esc_html( $button_image_text ) . '" />
		</a>';
	}
	/**
	 * Display Card on File on checkout page.
	 *
	 * @param string $description This is description of payment method.
	 * @param string $payment_id This is id of the payment method.
	 *
	 * @return string
	 */
	public function synchrony_card_on_file_flag( $description, $payment_id ) {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$user_id               = get_current_user_id();
		$syf_paymenttoken      = get_user_meta( $user_id, 'syf_paymenttoken', true );
		$syf_cardonfileflag    = get_user_meta( $user_id, 'syf_cardonfileflag', true );
		$checked               = 0;
		if ( $setting_config_helper->customer_allow_to_save_card() && '' !== $syf_paymenttoken && 'yes' === $syf_cardonfileflag ) {
			$checked = 1;
		}
		if ( is_user_logged_in() && 'synchrony-unifi-payments' === $payment_id ) {
			ob_start(); // Start buffering.
			echo '<div  class="card-on-fields" style="padding:10px 0;">';
			woocommerce_form_field(
				'saveCard',
				array(
					'type'     => 'checkbox',
					'class'    => array( 'input-checkbox' ),
					'label'    => __( 'Remember my card' ),
					'required' => false,
				),
				$checked
			);
			wp_nonce_field( 'cof_nonce', 'syf_nonce', true, true );
			echo '<div>';
			$description .= ob_get_clean(); // Append buffered content.
		}
		return $description;
	}
}
