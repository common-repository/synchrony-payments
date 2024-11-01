<?php
/**
 * This file contains Callback function.
 *
 * @package Synchrony\Payments\Webhooks
 */

namespace Synchrony\Payments\Webhooks;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Gateway\Synchrony_Gateway;
use Synchrony\Payments\Logs\Synchrony_Logger;

/**
 * Class Synchrony_Callback
 */
class Synchrony_Callback {
	/**
	 * Gateway
	 *
	 * @var Gateway $gateway
	 */
	private $gateway;
	/**
	 * Logger
	 *
	 * @var Logger $logger
	 */
	private $logger;
	/**
	 * NS
	 *
	 * @var string
	 */
	const NS = 'syf/v1';

	/**
	 * BASE
	 *
	 * @var string
	 */
	const BASE = 'callback';
	/**
	 * HTTP_RESPONSE_CODE_SUCCESS
	 */
	public const HTTP_RESPONSE_CODE_SUCCESS = 200;

	/**
	 * PERMISSION_CALLBACK
	 */
	public const PERMISSION_CALLBACK = '__return_true';

	/**
	 * Class constructor
	 * Initializes the object and sets up properties.
	 */
	public function __construct() {
		$this->gateway = new Synchrony_Gateway();
		$this->init_webhook();
		$this->logger = new Synchrony_Logger();
	}

	/**
	 * Initializes the webhook
	 *
	 * @return void
	 */
	public function init_webhook() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this, 'synchrony_delete_transient_endpoint' ) );
		add_action( 'rest_api_init', array( $this, 'synchrony_find_status_endpoint' ) );
		add_action( 'woocommerce_before_cart_table', array( $this, 'cart_error_message' ) );
		add_action( 'rest_api_init', array( $this, 'synchrony_partner_activate_endpoint' ) );
		add_action( 'rest_api_init', array( $this, 'synchrony_smb_domain_update_endpoint' ) );
	}
	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/' . self::BASE,
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => function ( $request ) {
					if ( $request->get_param( 'tokenId' ) ) {
						return true;
					}
				},
			)
		);
	}
	/**
	 * Process the webhook for the WP Rest API.
	 *
	 * @param \WP_REST_Request $data This is for data.
	 *
	 * @return string
	 */
	public function webhook( \WP_REST_Request $data ) {
		$order_id = (int) $data->get_param( 'reference_id' );
		if ( empty( $order_id ) ) {
			$order_id = '';
		}
		$token_id = $data->get_param( 'tokenId' );
		if ( empty( $token_id ) ) {
			$token_id = '';
		}
		if ( $order_id ) {
			$order                 = wc_get_order( $order_id );
			$log_info['Order Id:'] = $order_id;
			$log_info['Token Id']  = $token_id;
			if ( $order ) {
				$this->logger->debug( 'log_info: ' . wp_json_encode( $log_info ) . ', Start executing the Capture Strategy Call.' );
				$this->gateway->capture_strategy( $token_id, $order );
				$this->logger->debug( 'log_info: ' . wp_json_encode( $log_info ) . ', End Capture Strategy Call.' );
			}
		}
		return $data->get_param( 'reference_id' );
	}

	/**
	 * Register syf delete endpoint.
	 *
	 * @return void
	 */
	public function synchrony_delete_transient_endpoint() {
		register_rest_route(
			self::NS,
			'/delete_transient/syf_tag/',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'delete_transient_by_tag' ),
				'permission_callback' => function ( $request ) {
					if ( $this->is_admin_request( $request ) ) {
						return true;
					}
				},
			)
		);
	}

	/**
	 * Delete Transient Tags from Database.
	 *
	 * @return array
	 */
	public function delete_transient_by_tag() {
		global $wpdb;
		$transient_prefix = 'synchrony_digitalbuy_Tag_';
		$wildcard         = '_transient_' . $transient_prefix . '%';
		$transients       = $wpdb->get_col( $wpdb->prepare( "Select option_name FROM $wpdb->options WHERE option_name LIKE %s", $wildcard ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $transients ) {
			foreach ( $transients as $transient_name ) {
				delete_transient( str_replace( '_transient_', '', $transient_name ) );
			}
		}
		return array( 'message' => 'Success' );
	}

	/**
	 * Check if this is a request at the backend.
	 *
	 * @param mixed $request This is for request.
	 *
	 * @return bool true if is admin request, otherwise false.
	 */
	public function is_admin_request( $request ) {
		$nonce   = $request->get_header( 'X-Syf-Nonce' );
		$isadmin = $request->get_param( 'isadmin' );
		if ( ! $isadmin && ! wp_verify_nonce( $nonce, 'syf_transient_nonce' ) ) {
			return false;
		}
		$admin_url = strtolower( admin_url() );
		$referrer  = strtolower( wp_get_referer() );
		if ( $referrer && 0 === strpos( $referrer, $admin_url ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Register Find Status API Endpoint.
	 *
	 * @return void
	 */
	public function synchrony_find_status_endpoint() {
		register_rest_route(
			self::NS,
			'/find_status',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook_find_status' ),
				'permission_callback' => function ( $request ) {
					if ( $request->get_param( 'tokenId' ) ) {
						return true;
					}
				},
			)
		);
	}
	/**
	 * Process the webhook for the WP Rest API.
	 *
	 * @param \WP_REST_Request $data This is for data.
	 *
	 * @return void
	 */
	public function webhook_find_status( \WP_REST_Request $data ) {

		if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		$token_id = $data->get_param( 'tokenId' );
		$user_id  = $data->get_param( 'user_id' );

		$this->logger->debug( 'user_id: ' . $user_id );
		$http_client        = new \Synchrony\Payments\Gateway\Synchrony_Http_Client();
		$find_status_result = $http_client->find_status_api_call( $token_id );

		if ( self::HTTP_RESPONSE_CODE_SUCCESS === $find_status_result['response_code'] && ! empty( $find_status_result['billing_first_name'] ) ) {
			// Set mpp token in session.
			WC()->session->set( 'pay_syf_token_id', $token_id );

			$billing_first_name = $find_status_result['billing_first_name'];
			$billing_last_name  = $find_status_result['billing_last_name'];
			$billing_address_1  = $find_status_result['billing_address_1'];
			$billing_address_2  = $find_status_result['billing_address_2'];
			$zip_code           = $find_status_result['zip_code'];
			$city               = $find_status_result['city'];
			$state              = $find_status_result['state'];

			if ( ! empty( $user_id ) ) {
				// set customer address details to cart ( For logged in customer ).
				update_user_meta( $user_id, 'billing_first_name', $billing_first_name, '' );
				update_user_meta( $user_id, 'billing_last_name', $billing_last_name, '' );
				update_user_meta( $user_id, 'billing_address_1', $billing_address_1, '' );
				update_user_meta( $user_id, 'billing_address_2', $billing_address_2, '' );
				update_user_meta( $user_id, 'billing_postcode', $zip_code, '' );
				update_user_meta( $user_id, 'billing_city', $city, '' );
				update_user_meta( $user_id, 'billing_state', $state, '' );
				update_user_meta( $user_id, 'shipping_first_name', $billing_first_name, '' );
				update_user_meta( $user_id, 'shipping_last_name', $billing_last_name, '' );
				update_user_meta( $user_id, 'shipping_address_1', $billing_address_1, '' );
				update_user_meta( $user_id, 'shipping_address_2', $billing_address_2, '' );
				update_user_meta( $user_id, 'shipping_postcode', $zip_code, '' );
				update_user_meta( $user_id, 'shipping_city', $city, '' );
				update_user_meta( $user_id, 'shipping_state', $state, '' );
			} else {
				// set customer address details to cart (for guest user).
				WC()->customer->set_billing_first_name( wc_clean( $billing_first_name ) );
				WC()->customer->set_billing_last_name( wc_clean( $billing_last_name ) );
				WC()->customer->set_billing_address_1( wc_clean( $billing_address_1 ) );
				WC()->customer->set_billing_address_2( wc_clean( $billing_address_2 ) );
				WC()->customer->set_billing_postcode( wc_clean( $zip_code ) );
				WC()->customer->set_billing_city( wc_clean( $city ) );
				WC()->customer->set_billing_state( wc_clean( $state ) );
				WC()->customer->set_shipping_first_name( wc_clean( $billing_first_name ) );
				WC()->customer->set_shipping_last_name( wc_clean( $billing_last_name ) );
				WC()->customer->set_shipping_address_1( wc_clean( $billing_address_1 ) );
				WC()->customer->set_shipping_address_2( wc_clean( $billing_address_2 ) );
				WC()->customer->set_shipping_postcode( wc_clean( $zip_code ) );
				WC()->customer->set_shipping_city( wc_clean( $city ) );
				WC()->customer->set_shipping_state( wc_clean( $state ) );
			}
			// redirect to checkout.
			$checkout_url         = wc_get_checkout_url();
			$cache_buster         = 'v=' . time();
			$checkout_url_updated = add_query_arg( $cache_buster, '', $checkout_url );
			wp_safe_redirect( $checkout_url_updated );
			exit;

		} else {
			// redirect to cart.
			$cart_url = wc_get_cart_url();
			wp_safe_redirect( $cart_url );
			exit;
		}
	}
	/**
	 * Error message for cart.
	 *
	 * @return void
	 */
	public function cart_error_message() {
		$find_status_url = 'auth=1';
		$previous_url    = esc_url( wp_get_referer() );
		// display error msg on cart.
		if ( str_contains( $previous_url, $find_status_url ) ) {
			echo "<div id='syf-error-message'>Something went wrong, please try again later</div>";
		}
	}

	/**
	 * Register Smb partner activate API Endpoint.
	 *
	 * @return void
	 */
	public function synchrony_partner_activate_endpoint() {
		register_rest_route(
			self::NS,
			'/partner_activate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook_smb_partner_activate' ),
				'permission_callback' => function ( $request ) {
					if ( $this->is_admin_request( $request ) ) {
						return true;
					}
				},
			)
		);
	}

	/**
	 * Process the webhook for the WP Rest API.
	 *
	 * @param \WP_REST_Request $data This is for data.
	 *
	 * @return array
	 */
	public function webhook_smb_partner_activate( \WP_REST_Request $data ) {
		$client_smb = new \Synchrony\Payments\Gateway\Synchrony_Smb();
		return $client_smb->get_module_activation_info( $data );
	}

	/**
	 * Update SMB domain update API Endpoint.
	 *
	 * @return void
	 */
	public function synchrony_smb_domain_update_endpoint() {
		register_rest_route(
			self::NS,
			'/partner_domain',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook_smb_domain_update' ),
				'permission_callback' => function ( $request ) {
					if ( $this->is_admin_request( $request ) ) {
						return true;
					}
				},
			)
		);
	}

	/**
	 * Process the webhook for the update doamin.
	 *
	 * @param \WP_REST_Request $data This is for data.
	 *
	 * @return array
	 */
	public function webhook_smb_domain_update( \WP_REST_Request $data ) {
		if ( ! $data->get_param( 'domain' ) ) {
			return new \WP_Error(
				'error_response_smb',
				__( 'Invalid Request' ),
				array(
					'status' => 400,
					'data'   => $data,
				)
			);
		}
		$domains    = $data->get_param( 'domain' );
		$client_smb = new \Synchrony\Payments\Gateway\Synchrony_Smb();
		$result     = $client_smb->update_smb_domains( $domains );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'error_response_smb',
				__( 'Something went wrong.' ),
				array(
					'status' => 400,
					'data'   => $data,
				)
			);
		}
		return $result;
	}
}
