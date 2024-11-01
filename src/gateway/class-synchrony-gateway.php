<?php
/**
 * Gateway file
 *
 * @package Synchrony\Payments\Gateway
 */

namespace Synchrony\Payments\Gateway;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Frontend\Synchrony_Promotag_Config;

/**
 * Class Synchrony_Gateway
 */
class Synchrony_Gateway {

	/**
	 * PROXY
	 */
	public const PROXY = null;
	/**
	 * HTTP_RESPONSE_CODE_SUCCESS
	 */
	public const HTTP_RESPONSE_CODE_SUCCESS = 200;
	/**
	 * CHANNELID
	 *
	 * @var string
	 */
	public const CHANNELID = 'WE';
	/**
	 * TYPE_AUTH_SETTLE
	 *
	 * @var string
	 */
	public const TYPE_AUTH_SETTLE = 'AUTH_SETTLE';
	/**
	 * TYPE_SETTLE
	 *
	 * @var string
	 */
	public const TYPE_SETTLE = 'SETTLE';
	/**
	 * TYPE_ADDRESS_BILLING
	 *
	 * @var string
	 */
	public const TYPE_ADDRESS_BILLING = 'BILLING';
	/**
	 * TYPE_ADDRESS_SHIPPING
	 *
	 * @var string
	 */
	public const TYPE_ADDRESS_SHIPPING = 'SHIPPING';
	/**
	 * TYPE_ADDRESS
	 *
	 * @var string
	 */
	public const TYPE_ADDRESS = 'BILLING';
	/**
	 * LEGACY_Y
	 *
	 * @var string
	 */
	public const LEGACY_Y = 'Y';
	/**
	 * LEGACY_N
	 *
	 * @var string
	 */
	public const LEGACY_N = 'N';
	/**
	 * TYPE_AUTH
	 *
	 * @var string
	 */
	public const TYPE_AUTH = 'AUTH';
	/**
	 * PAYMENT_FAILURE
	 *
	 * @var string
	 */
	public const PAYMENT_FAILURE = 'FAILURE';
	/**
	 * Client
	 *
	 * @var Synchrony_Client $client
	 */
	private $client;
	/**
	 * Config_helper
	 *
	 * @var Synchrony_Config_Helper $config_helper
	 */
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
	 * Logger
	 *
	 * @var Synchrony_Logger $logger
	 */
	private $logger;
	/**
	 * Configuration settings for tag.
	 *
	 * @var Synchrony_Promotag_Config $tag_config
	 */
	private $tag_config;

	/**
	 * This is for Authorize.
	 *
	 * @var string
	 */
	const AUTH = 'authorize';

	/**
	 * This is for authorize and capture.
	 *
	 * @var string
	 */
	const AUTH_CAPTURE = 'authorize-capture';

	/**
	 * This is syf_paymenttoken.
	 *
	 * @var string
	 */
	const PAYMENT_TOKEN_KEY = 'syf_paymenttoken';

	/**
	 * This is syf_cardonfileflag.
	 *
	 * @var string
	 */
	const CARD_ON_FILE_FLAG_KEY = 'syf_cardonfileflag';

	/**
	 * This is error code in transact API.
	 *
	 * @var string
	 */
	const API_TRANSACT_CODE = 'gateway.transact.avf.unprocessable';

	/**
	 * Gateway constructor
	 */
	public function __construct() {
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$this->client                = new Synchrony_Client();
		$this->logger                = new Synchrony_Logger();
		$this->tag_config            = new Synchrony_Promotag_Config();
		add_action( 'woocommerce_before_checkout_form', array( $this, 'order_error_message' ) );
		add_shortcode( 'synchrony_error_message', array( $this, 'order_sc_error_message' ) );
	}

	/**
	 * Capture strategy function
	 *
	 * @param mixed $data This contains data.
	 * @param mixed $order This is for order.
	 *
	 * @return void
	 */
	public function capture_strategy( $data, $order ) {
		switch ( $this->config_helper->fetch_config_payment_action() ) {
			case self::AUTH_CAPTURE:
				$this->capture( $data, $order );
				$this->logger->debug( 'log_info: Capture has been completed.' );
				break;
			case self::AUTH:
				$this->authorize( $data, $order );
				$this->logger->debug( 'log_info: Authorize has been completed.' );
				break;
			default:
				$this->redirect_to_checkout();
				break;
		}
	}

	/**
	 * Authorize function
	 *
	 * @param mixed $token_id This is for token Id.
	 * @param mixed $order    This is for order.
	 *
	 * @return void
	 */
	public function authorize( $token_id, $order ) {
		$access_token = '';
		if ( ! $token_id ) {
			$this->redirect_to_checkout();
		}
		$order_id    = $order->get_id();
		$customer_id = $order->get_customer_id();
		if ( ! empty( $this->client->retrieve_auth_token() ) ) {
			$auth_result  = json_decode( $this->client->retrieve_auth_token() );
			$access_token = $auth_result->access_token;
		}
		$tracking_id = $this->common_config_helper->generate_reference_id();
		$channel_id  = self::CHANNELID;
		$amount      = $this->setting_config_helper->format_amount( $order->get_total() );
		$legacy      = self::LEGACY_Y;

		$headers = array(
			'' . $this->client::HEADER_BEARER . ' ' . $access_token,
			'' . $this->client::HEADER_CHANNELID . ': ' . $channel_id . '',
			'' . $this->client::HEADER_TRACKING_ID . ': ' . $tracking_id,
		);
		if ( ! empty( $token_id ) ) {
			$get_post_details = $this->fetch_transact_post_details( $token_id, self::TYPE_AUTH, $order, $amount );
			$post_data        = $get_post_details;
			$post_data        = $this->tag_config->promo_tag_post_data( $post_data, $order );
			$auth_response    = $this->transact_call( $headers, $post_data, $order_id );

			$this->logger->debug( 'authorize transact api response=>' . wp_json_encode( $auth_response ) );
			// Card on file : Save payment token against user.
			$syf_savecard_flag = get_user_meta( $customer_id, 'syf_cardonfileflag', true );
			if ( isset( $auth_response['paymentToken'] ) ) {
				$payment_token = $auth_response['paymentToken'];
				$this->cardonfile_data_updates( $payment_token, $customer_id, $syf_savecard_flag );
			}

			if ( ! isset( $auth_response['authorizationCode'] ) ) {
				$this->redirect_to_checkout();
			}
			if ( ! empty( $auth_response['authorizationCode'] ) && isset( $auth_response['authorizationCode'] ) ) {
				$auth_confirmation                    = $auth_response['authConfirmation'][0];
				$process_response                     = array();
				$process_response['transactionToken'] = $token_id;
				$process_response['legacy']           = $legacy;

				if ( ! empty( $auth_response['authConfirmation'] ) && isset( $auth_response['authConfirmation'] ) ) {
					add_post_meta( $order_id, '_syf_order_token_id', $token_id );
					add_post_meta( $order_id, '_syf_order_legacy', $legacy );
					add_post_meta( $order_id, '_order_information', maybe_serialize( $auth_response ) );
					$order = wc_get_order( $order_id );
					$order->add_order_note( 'Promotion code used :' . $auth_confirmation['promotionCode'] );
					$order->add_order_note( 'Transaction authorization completed ' . wp_json_encode( $process_response ) );
					$order->set_status( 'on-hold' );
					$order->save();
					$redirect_url = $order->get_checkout_order_received_url();
					wp_safe_redirect( $redirect_url );
					die;
				}
			}
		}
	}

	/**
	 * Capture function
	 *
	 * @param mixed $token_id This is for token Id.
	 * @param mixed $order This is for order.
	 *
	 * @return void
	 */
	public function capture( $token_id, $order ) {
		$access_token = '';
		if ( ! $token_id ) {
			$this->redirect_to_checkout();
		}
		$order_id    = $order->get_id();
		$customer_id = $order->get_customer_id();
		$tracking_id = $this->common_config_helper->generate_reference_id();
		if ( ! empty( $this->client->retrieve_auth_token() ) ) {
			$auth_result  = json_decode( $this->client->retrieve_auth_token() );
			$access_token = $auth_result->access_token;
		}
		$channel_id       = self::CHANNELID;
		$amount           = $this->setting_config_helper->format_amount( $order->get_total() );
		$legacy           = self::LEGACY_N;
		$headers          = array(
			'' . $this->client::HEADER_BEARER . ' ' . $access_token,
			'' . $this->client::HEADER_CHANNELID . ': ' . $channel_id . '',
			'' . $this->client::HEADER_TRACKING_ID . ': ' . $tracking_id,
		);
		$get_post_details = $this->fetch_transact_post_details( $token_id, self::TYPE_AUTH_SETTLE, $order, $amount );
		$address_type     = $this->setting_config_helper->fetch_address_type();
		if ( 'shipping' === $address_type ) {
			$transact_address_type = self::TYPE_ADDRESS_SHIPPING;
		} else {
			$transact_address_type = self::TYPE_ADDRESS_BILLING;
		}
		$post_data         = $get_post_details;
		$post_data         = $this->tag_config->promo_tag_post_data( $post_data, $order );
		$transact_response = $this->transact_call( $headers, $post_data, $order_id );

		$this->logger->debug( 'capture transact api response=>' . wp_json_encode( $transact_response ) );
		// Card on file : Save payment token against user.
		$syf_savecard_flag = get_user_meta( $customer_id, 'syf_cardonfileflag', true );
		if ( isset( $transact_response['paymentToken'] ) && ( 'yes' === $syf_savecard_flag ) ) {
			$payment_token = $transact_response['paymentToken'];
			update_user_meta( $customer_id, self::PAYMENT_TOKEN_KEY, $payment_token, '' );
		}
		if ( ! isset( $transact_response['settlementConfirmation'] ) ) {
			$this->redirect_to_checkout();
		}

		$settlement_confirmation = $transact_response['settlementConfirmation'][0];
		if ( isset( $settlement_confirmation['status'] ) && 'SUCCESS' === $settlement_confirmation['status'] ) {
			$settle_response = array(
				'settlementConfirmation' => $settlement_confirmation['transactionId'],
				'TransactionId'          => $settlement_confirmation['transactionId'],
				'amount'                 => $this->setting_config_helper->format_amount( $order->get_total() ),
				'transactionToken'       => $token_id,
				'legacy'                 => $legacy,
			);
			add_post_meta( $order_id, '_syf_order_token_id', $token_id );
			add_post_meta( $order_id, '_syf_order_legacy', $legacy );
			add_post_meta( $order_id, '_order_information', maybe_serialize( $settle_response ) );
			$order = wc_get_order( $order_id );
			$order->add_order_note( 'Promotion code used :' . $settlement_confirmation['promotionCode'] );
			$order->add_order_note( 'Payment is completed by Synchrony' );
			$order->set_status( 'processing' );
			$order->save();
			$redirect_url = $order->get_checkout_order_received_url();
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Capture function
	 *
	 * @param mixed $token_id This is for token Id.
	 * @param mixed $payment_type This is for payment.
	 * @param mixed $order This is for order details.
	 * @param mixed $amount This is for amount.
	 *
	 * @return array
	 */
	public function fetch_transact_post_details( $token_id, $payment_type, $order, $amount ) {
		$amount         = $this->setting_config_helper->format_amount( $order->get_total() );
		$address_type   = $this->setting_config_helper->fetch_address_type();
		$payment_option = $this->config_helper->fetch_config_payment_action();
		if ( self::AUTH_CAPTURE === $payment_option ) {
			$payment_type = self::TYPE_AUTH_SETTLE;
		} else {
			$payment_type = self::TYPE_AUTH;
		}
		if ( 'shipping' === $address_type ) {
			$transact_address_type = self::TYPE_ADDRESS_SHIPPING;
			$post_data             = array(
				'action'           => $payment_type,
				'transactionToken' => $token_id,
				'addressInfo'      =>
					array(
						'type'    => $transact_address_type,
						'line1'   => $order->get_shipping_address_1(),
						'line2'   => $order->get_shipping_address_2(),
						'city'    => $order->get_shipping_city(),
						'state'   => $order->get_shipping_state(),
						'zipCode' => $order->get_shipping_postcode(),
					),
				'transactionInfo'  => array(
					'amount' => $amount,
				),
			);
		} else {
			$transact_address_type = self::TYPE_ADDRESS_BILLING;
			$post_data             = array(
				'action'           => $payment_type,
				'transactionToken' => $token_id,
				'addressInfo'      =>
				array(
					'type'    => $transact_address_type,
					'line1'   => $order->get_billing_address_1(),
					'line2'   => $order->get_billing_address_2(),
					'city'    => $order->get_billing_city(),
					'state'   => $order->get_billing_state(),
					'zipCode' => $order->get_billing_postcode(),
				),
				'transactionInfo'  => array(
					'amount' => $amount,
				),
			);
		}
		$this->logger->info( 'post data for transact call=>' . wp_json_encode( $post_data ) );
		return $post_data;
	}

	/**
	 * Manual capture function
	 *
	 * @param mixed $token_id This is for Token Id.
	 * @param mixed $order This is  order.
	 *
	 * @return bool
	 */
	public function manual_capture( $token_id, $order ) {
		$this->logger->info( 'Start Manual capture function' );
		try {
			$order_id            = $order->get_id();
			$retrieve_auth_token = $this->client->retrieve_auth_token();
			if ( empty( $retrieve_auth_token ) ) {
				$this->logger->info( 'AUTH Token is coming as empty' );
				return false;
			}
			$auth_result  = json_decode( $retrieve_auth_token );
			$access_token = $auth_result->access_token;
			$tracking_id  = $this->common_config_helper->generate_reference_id();
			$channel_id   = self::CHANNELID;
			$amount       = $this->setting_config_helper->format_amount( $order->get_total() );
			$headers      = array(
				'' . $this->client::HEADER_BEARER . ' ' . $access_token,
				'' . $this->client::HEADER_CHANNELID . ': ' . $channel_id . '',
				'' . $this->client::HEADER_TRACKING_ID . ': ' . $tracking_id,
			);

			if ( ! empty( $token_id ) ) {
				$post_data = array(
					'action'           => self::TYPE_SETTLE,
					'transactionToken' => $token_id,
					'transactionInfo'  => array(
						'amount' => $amount,
					),
				);
				$post_data = $this->tag_config->promo_tag_post_data( $post_data, $order );
				$this->logger->info( 'manual capture post data=>' . wp_json_encode( $post_data ) );
				$transact_response = $this->transact_call( $headers, $post_data, $order_id );
				$this->logger->info( 'manual capture response=>' . wp_json_encode( $transact_response ) );
				if ( ! isset( $transact_response['settlementConfirmation'][0] ) ) {
					return false;
				}
				$settlement_confirmation = $transact_response['settlementConfirmation'][0];
				if ( isset( $settlement_confirmation['status'] ) && 'SUCCESS' === $settlement_confirmation['status'] ) {
					$order->update_status( 'processing' );
					$order->save();
					return true;
				}
			}
		} catch ( \Exception $e ) {
			$this->logger->error( 'error : ' . $e->getMessage() );
		}
		return false;
	}


	/**
	 * Transact call api
	 *
	 * @param mixed $headers This provides Headers.
	 * @param mixed $post_data This provides Post data.
	 * @param int   $order_id This is current order id.
	 * @return array|null
	 * @throws \Exception - If an Exceptions occurs during transact call.
	 */
	public function transact_call( $headers, $post_data, $order_id ) {
		$is_syf_test_mode   = $this->common_config_helper->does_test_mode();
		$auth_api           = $this->config_helper->fetch_api_endpoint( 'synchrony_test_transactapi_api_endpoint', 'synchrony_deployed_transactapi_api_endpoint', $is_syf_test_mode );
		$timeout            = $this->setting_config_helper->fetch_api_timeout();
		$is_address_on_file = $this->common_config_helper->fetch_syf_option( 'address_on_file' );
		// Child Merchant Number.
		$child_merchant_id = $this->setting_config_helper->fetch_child_merchant_id();
		if ( $child_merchant_id ) {
			$post_data['childMerchantNumber'] = $child_merchant_id;
		}
		try {
			$transact_settle = new Synchrony_Http( $auth_api, $timeout, self::PROXY );

			$settle_response = $transact_settle->post( $post_data, 'yes', $headers );
			if ( $settle_response ) {
				$settle_response = json_decode( $settle_response, true );
			}
			// Order note : Notify Address Failure : Order failure notification.
			$this->order_address_verification( $is_address_on_file, $settle_response, $order_id );
			// Order note : Payment Type.
			if ( isset( $settle_response['paymentType'] ) ) {
				$product_type = $settle_response['paymentType'];
				$order        = wc_get_order( $order_id );
				$order->add_order_note(
					/* translators: %s: search term */
					sprintf( esc_html__( 'Payment Type : %s', 'synchrony-payments' ), $product_type )
				);
			}
			if ( $transact_settle->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_SUCCESS ) {
				if ( $settle_response ) {
					return $settle_response;
				}
			} else {
				throw new \Exception( 'Unable to decode Force Capture API response: ' . wp_json_encode( $settle_response ) );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( 'error: ' . $e->getMessage() . ', file path: ' . $e->getFile() . ', line no: ' . $e->getLine() . ', exception message: ' . $e->getMessage() );
		}
		return null;
	}

	/**
	 * Redirecting to checkout
	 *
	 * @return void
	 */
	public function redirect_to_checkout() {
		set_transient( 'syf_payment_failed', true, 5 );
		wp_safe_redirect( wc_get_checkout_url() );
		die;
	}

	/**
	 * Show Order Error Message.
	 *
	 * @return void
	 */
	public function order_error_message() {
		$short_code = '[synchrony_error_message]';
		echo do_shortcode( wp_kses_post( $short_code ) );
	}

	/**
	 * Error message for order
	 *
	 * @return mixed
	 */
	public function order_sc_error_message() {
		if ( get_transient( 'syf_payment_failed' ) ) {
			return '<div id="syf-error-message">Something went wrong, please try again later. </div>';
		}
		/* Delete transient, only display this notice once. */
		delete_transient( 'syf_payment_failed' );
	}
	/**
	 * Update details while card on file feature.
	 *
	 * @param mixed $payment_token This is payment_token.
	 * @param mixed $customer_id This is customer_id.
	 * @param bool  $syf_savecard_flag This is syf_savecard_flag.
	 *
	 * @return void
	 */
	public function cardonfile_data_updates( $payment_token, $customer_id, $syf_savecard_flag ) {
		if ( $payment_token && ( 'yes' === $syf_savecard_flag ) ) {
			update_user_meta( $customer_id, self::PAYMENT_TOKEN_KEY, $payment_token, '' );
		}
	}

	/**
	 * Order Address verification failed.
	 *
	 * @param int   $is_address_on_file This provides address failure flag.
	 * @param mixed $settle_response This provides response data.
	 * @param int   $order_id This is current order id.
	 *
	 * @return void
	 */
	public function order_address_verification( $is_address_on_file, $settle_response, $order_id ) {
		if ( $is_address_on_file && isset( $settle_response['code'] ) && self::API_TRANSACT_CODE === $settle_response['code'] ) {
			$order_note_msg = isset( $settle_response['message'] ) ? $settle_response['message'] : '';
			$order          = wc_get_order( $order_id );
			try {
				$order->update_status( 'cancelled' );
				$order->add_order_note( 'Address Was Wrong' );
				$order->add_order_note( $order_note_msg );
			} catch ( \Exception $e ) {
				$this->logger->error( 'Order Fail to save faulty address note : , file path: ' . $e->getFile() . ', line no: ' . $e->getLine() . ', exception message: ' . $e->getMessage() );
			}
			// Redirect to checkout page with error message.
			$this->redirect_to_checkout();
		}
	}
}
