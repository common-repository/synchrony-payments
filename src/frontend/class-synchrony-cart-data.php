<?php
/**
 * Synchrony_Cart_Data File
 *
 * @package Synchrony\Payments\Frontend
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Admin\Synchrony_Promotag_Configurator;
use Synchrony\Payments\Gateway\Synchrony_Client;

/**
 * Class Synchrony_Cart_Data
 */
class Synchrony_Cart_Data {
	/**
	 * Config Helper
	 *
	 * @var Synchrony_Config_Helper $config_helper
	 **/
	private $config_helper;

	/**
	 * Common_Config_helper
	 *
	 * @var Synchrony_Common_Config_Helper $common_config_helper
	 */
	private $common_config_helper;

	/**
	 * Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;

	/**
	 * Install constructor
	 */
	public function __construct() {
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
	}
	/**
	 * Get Current Cart Data
	 *
	 * @return \WC_Cart
	 */
	public function retrieve_cart() {
		global $woocommerce;
		return $woocommerce->cart;
	}

	/**
	 * Get Client Transaction ID
	 *
	 * @return string
	 */
	public function retrieve_client_trans_id() {
		$partner_id     = $this->setting_config_helper->fetch_partner_id();
		$order_id       = WC()->session->get( 'order_awaiting_payment' );
		$wc_version     = WC_VERSION;
		$plugin_version = $this->common_config_helper->fetch_app_version();
		return 'WOO_' . $wc_version . '_S_' . $plugin_version . '_P_' . $partner_id . '_O_' . $order_id;
	}

	/**
	 * Redirect to Home page refer.
	 *
	 * @param string $base_url This is base url.
	 * @param int    $order_id this is Order ID.
	 *
	 * @return void
	 */
	public function redirect_to_home_page( $base_url, $order_id ) {
		if ( empty( $order_id ) && wc_get_cart_url() !== wp_get_referer() ) {
			wp_safe_redirect( $base_url );
			exit;
		}
	}

	/**
	 * Get Customer Information
	 *
	 * @return array
	 */
	public function retrieve_customer_info() {
		$address_type = $this->setting_config_helper->fetch_address_type();
		$customer     = $this->retrieve_cart()->get_customer();
		if ( 'shipping' === $address_type ) {
			return array(
				'first_name'   => $customer->get_shipping_first_name(),
				'last_name'    => $customer->get_shipping_last_name(),
				'email'        => $customer->get_billing_email(),
				'postal_code'  => $customer->get_shipping_postcode(),
				'address1'     => $customer->get_shipping_address(),
				'address2'     => $customer->get_shipping_address_2(),
				'city'         => $customer->get_shipping_city(),
				'state'        => $customer->get_shipping_state(),
				'phone_number' => $customer->get_shipping_phone(),
			);
		}
		return array(
			'first_name'   => $customer->get_billing_first_name(),
			'last_name'    => $customer->get_billing_last_name(),
			'email'        => $customer->get_billing_email(),
			'postal_code'  => $customer->get_billing_postcode(),
			'address1'     => $customer->get_billing_address(),
			'address2'     => $customer->get_billing_address_2(),
			'city'         => $customer->get_billing_city(),
			'state'        => $customer->get_billing_state(),
			'phone_number' => $customer->get_billing_phone(),
		);
	}

	/**
	 * Get Promo Tags Products
	 *
	 * @return string
	 */
	public function retrieve_promo_tags_products() {
		if ( 1 !== $this->setting_config_helper->fetch_tag_rules() ) {
			return '';
		}
		global $woocommerce;
		$tag_config   = new Synchrony_Promotag_Configurator();
		$product_data = $tag_config->retrieve_cart_product_data();
		if ( $product_data && isset( $_SESSION['synchrony_product_tag'] ) ) {
			$synchrony_prod_tag                = sanitize_text_field( $_SESSION['synchrony_product_tag'] );
			$products                          = json_decode( $synchrony_prod_tag, true );
			$products                          = wp_json_encode( $products );
			$_SESSION['synchrony_product_tag'] = $products;
			return $products;
		}
		return '';
	}

	/**
	 * Map Dbuy Data.
	 *
	 * @param mixed $order_id this is Order ID.
	 * @param mixed $user_id this is User ID.
	 *
	 * @return array
	 */
	public function map_dbuy_data( $order_id = '', $user_id = '' ) {
		$overlay = $this->config_helper->fetch_pop_up();
		if ( 1 !== $overlay && is_checkout() ) {
			return array();
		}
		$client                 = new Synchrony_Client();
		$mpp_token              = $client->retrieve_token();
		$customer_data          = $this->retrieve_customer_info();
		$promo_tag_products     = $this->retrieve_promo_tags_products();
		$get_totals             = $this->retrieve_cart()->get_totals();
		$trans_amount           = $this->setting_config_helper->format_amount( $get_totals['total'] );
		$partner_id             = $this->setting_config_helper->fetch_partner_id();
		$client_trans_id        = $this->retrieve_client_trans_id();
		$is_card_on_file_enable = $this->setting_config_helper->customer_allow_to_save_card();
		$card_on_file_flag      = 'NO';
		if ( $is_card_on_file_enable && $user_id ) {
			$syf_cardonfileflag = get_user_meta( $user_id, 'syf_cardonfileflag', true );
			$card_on_file_flag  = ( 'yes' === $syf_cardonfileflag ) ? 'YES' : 'NO';
		}
		$get_tag_rules     = $this->setting_config_helper->fetch_tag_rules();
		$child_merchant_id = $this->setting_config_helper->fetch_child_merchant_id();
		$session_token_id  = WC()->session->get( 'pay_syf_token_id' );
		$process_ind       = 3;
		if ( $session_token_id ) {
			$process_ind = 2;
			$mpp_token   = $session_token_id;
		}
		if ( WC()->session->get( 'store_api_draft_order' ) ) {
			$order_id = WC()->session->get( 'store_api_draft_order' );
		}
		return array(
			'result'                 => 'success',
			'tokenId'                => $mpp_token,
			'syfPartnerId'           => $partner_id,
			'childSyfMerchantNumber' => $child_merchant_id,
			'processInd'             => "$process_ind",
			'clientTransId'          => $client_trans_id,
			'custAddress1'           => $customer_data['address1'],
			'custAddress2'           => $customer_data['address2'],
			'custCity'               => $customer_data['city'],
			'custState'              => $customer_data['state'],
			'custZipCode'            => $customer_data['postal_code'],
			'custFirstName'          => $customer_data['first_name'],
			'custLastName'           => $customer_data['last_name'],
			'transAmount1'           => "$trans_amount",
			'phoneNumber'            => $customer_data['phone_number'],
			'emailAddress'           => $customer_data['email'],
			'saveCard'               => $card_on_file_flag,
			'productAttributes'      => ( 1 === $get_tag_rules ) ? $promo_tag_products : '',
			'order_id'               => $order_id,
		);
	}
}
