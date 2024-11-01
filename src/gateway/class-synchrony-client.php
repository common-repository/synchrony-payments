<?php
/**
 * This file contains the Client class that handles with the server.
 *
 * @package Synchrony\Payments\Gateway
 */

namespace Synchrony\Payments\Gateway;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Http;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Gateway\Synchrony_Jwt;


/**
 * Class Synchrony_Client
 * This class handles communication with the server.
 */
class Synchrony_Client {

	/**
	 * PROXY
	 *
	 * @var null
	 */
	public const PROXY = null;
	/**
	 * REFUND_ACTION
	 *
	 * @var string
	 */
	public const REFUND_ACTION = 'REFUND';
	/**
	 * SEND_LOGS_X_SYNCHRONY_REQUEST_CHANNEL_ID
	 *
	 * @var string
	 */
	public const SEND_LOGS_X_SYNCHRONY_REQUEST_CHANNEL_ID = 'SYF';
	/**
	 * MAX_LENGTH
	 *
	 * @var int
	 */
	public const MAX_LENGTH = 700;
	/**
	 * DATE_FORMAT
	 *
	 * @var string
	 */
	public const DATE_FORMAT = 'yyyy-MM-dd HH:mm:ss';
	/**
	 * HEADER_TRACKING_ID
	 *
	 * @var string
	 */
	public const HEADER_TRACKING_ID = 'X-SYF-Request-TrackingId';

	/**
	 * SYF API KEY
	 *
	 * @var string
	 */
	public const HEADER_API_KEY = 'X-SYF-API-Key';
	/**
	 * HEADER_BEARER
	 *
	 * @var string
	 */
	public const HEADER_BEARER = 'Authorization: Bearer';
	/**
	 * HEADER_CHANNELID
	 *
	 * @var string
	 */
	public const HEADER_CHANNELID = 'X-SYF-CHANNELID';
	/**
	 * HEADER_REQ_CHANNELID
	 *
	 * @var string
	 */
	public const HEADER_REQ_CHANNELID = 'X-SYF-Request-channelId';
	/**
	 * HEADER_REQ_PARTNERID
	 *
	 * @var string
	 */
	public const HEADER_REQ_PARTNERID = 'X-SYF-Request-partnerId';
	/**
	 * FILE_PATH_TXT
	 *
	 * @var string
	 */
	public const FILE_PATH_TXT = 'file path';
	/**
	 * LINE_NO_TXT
	 *
	 * @var string
	 */
	public const LINE_NO_TXT = 'line no';
	/**
	 * EXCEPTION_MSG_TXT
	 *
	 * @var string
	 */
	public const EXCEPTION_MSG_TXT = 'exception message';
	/**
	 * ERROR_TXT
	 *
	 * @var string
	 */
	public const ERROR_TXT = 'error';
	/**
	 * CHANNEL_ID
	 *
	 * @var string
	 */
	public const CHANNEL_ID = 'DY';
	/**
	 * ALGO
	 *
	 * @var string
	 */
	public const ALGO = 'sha256';
	/**
	 * CODE_CHALLANGE_METHOD
	 *
	 * @var string
	 */
	public const CODE_CHALLANGE_METHOD = 'S256';
	/**
	 * PLATFORM
	 *
	 * @var string
	 */
	public const PLATFORM = 'WOOCOMMERCE';

	/**
	 * Config_Helper
	 *
	 * @var Synchrony_Config_Helper
	 */
	private $config_helper;

	/**
	 * Common_Config_Helper
	 *
	 * @var Synchrony_Common_Config_Helper
	 */
	private $common_config_helper;

	/**
	 * Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper
	 */
	private $setting_config_helper;

	/**
	 * Logger
	 *
	 * @var Synchrony_Logger
	 */
	private $logger;
	/**
	 * HTTP response code
	 *
	 * @var int
	 */
	public const HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN = 401;
	/**
	 * HTTP_RESPONSE_CODE_SUCCESS
	 */
	public const HTTP_RESPONSE_CODE_SUCCESS = 200;

	/**
	 * HTTP response code
	 *
	 * @var int
	 */
	public const HTTP_RESPONSE_CODE_AFTER_24_HOUR = 422;

	/**
	 * PUBLIC_KEY
	 *
	 * @var string
	 */
	public const PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs8VaPQF0m9F0zim+MwIQQrOkHBcOEzS0Cr8mW+rv9iif9MHuwjdGN1NVaU22m/icOPpEmcEI2Dd/LU11gF/KCn0hQ7mrww+j7vaJOmE+Z+KgpSRc74J5nhq98hn9/cMLe2zubXGoep7Q1m4ofBrQ4GM+zstIJk2HZZ1Gb06DEfdu4C2OHw+tw9WI4woJciqjXsYmeVVDhSz9SrQqGBjhpMTWgiNkVrKR12wBd5aTxAoA8XOwO5K4NEwqfOdCVWxm1QjAYDvb6C6M4miYFobCD7Vbe//4UTzJaATfOc0WfmYL0HIHsU2snLgCSSEbLPzTr/5HKSIjCbZ1EW70Eltk4QIDAQAB';

	/**
	 * Constructor for thr client class.
	 */
	public function __construct() {
		$this->logger                = new Synchrony_Logger();
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
	}

	/**
	 * Call Get Token API.
	 *
	 * @return string
	 * @throws \Exception - if an error occurs while calling the Api.
	 */
	public function retrieve_token() {
		$partner_id = $this->setting_config_helper->fetch_partner_id();
		$timeout    = $this->setting_config_helper->fetch_api_timeout();
		$mpptoken   = '';
		$post_data  = array( 'syfPartnerId' => $partner_id );
		// card on file feature.
		$user_id            = get_current_user_id();
		$syf_paymenttoken   = get_user_meta( $user_id, 'syf_paymenttoken', true );
		$syf_cardonfileflag = get_user_meta( $user_id, 'syf_cardonfileflag', true );
		// Child Merchant Number.
		$child_merchant_id = $this->setting_config_helper->fetch_child_merchant_id();
		if ( $child_merchant_id ) {
			$post_data['childSyfMerchantNumber'] = $child_merchant_id;
			$post_data['partnerCode']            = $this->setting_config_helper->fetch_child_partner_id();
		}
		if ( ! empty( $syf_paymenttoken ) && ( 'yes' === $syf_cardonfileflag ) ) {
			$this->logger->debug( 'syf_paymenttoken : ' . $syf_paymenttoken );
			$this->logger->debug( 'syf_cardonfileflag : ' . $syf_cardonfileflag );
			$post_data['cipher.storedPaymentToken'] = $syf_paymenttoken;
		}

		$auth_token = $this->retrieve_auth_token();
		if ( empty( $auth_token ) || is_wp_error( $auth_token ) ) {
			return '';
		}

		$auth_result = json_decode( $auth_token );
		try {
			$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
			$auth_transport         = new Synchrony_Http( $this->config_helper->fetch_api_endpoint( 'synchrony_test_token_api_endpoint', 'synchrony_deployed_token_api_endpoint', $is_synchrony_test_mode ), $timeout, self::PROXY );
			$auth_response          = $this->post_api_call( $auth_transport, $auth_result, $post_data );
			$this->logger->debug( $auth_response );
			if ( $auth_response && $auth_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN ) {
				$auth_result   = json_decode( $this->retrieve_auth_token( 1 ) );
				$auth_response = $this->post_api_call( $auth_transport, $auth_result, $post_data );
			}

			if ( $auth_response ) {
				$auth_response = json_decode( $auth_response, true );
			}
			if ( isset( $auth_response['mppToken'] ) && '' !== $auth_response['mppToken'] ) {
					$mpptoken = $auth_response['mppToken'];
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return $mpptoken;
	}

	/**
	 * Call Get Token API SMB and Non SMB
	 *
	 * @param int $token_gen_flag This is flag to get data from cache.
	 *
	 * @return mixed
	 */
	public function retrieve_auth_token( $token_gen_flag = 0 ) {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		if ( 'yes' === $is_activation_enable ) {
			$client_smb = new \Synchrony\Payments\Gateway\Synchrony_Smb();
			if ( 0 === $token_gen_flag && get_transient( 'synchrony_smb_access_token' ) ) {
				return get_transient( 'synchrony_smb_access_token' );
			}
			return $client_smb->retrieve_smb_auth_refresh_token( $is_activation_enable );
		}
		return $this->synchrony_access_token( $token_gen_flag );
	}

	/**
	 * Call Get Token API Non SMB
	 *
	 * @param int $token_gen_flag This is flag to get data from cache.
	 *
	 * @return mixed
	 */
	public function synchrony_access_token( $token_gen_flag ) {
		if ( 0 === $token_gen_flag && get_transient( 'synchrony_access_token' ) ) {
			return get_transient( 'synchrony_access_token' );
		}
		$client_id     = $this->setting_config_helper->fetch_client_id();
		$client_secret = $this->setting_config_helper->fetch_client_secret();
		$timeout       = $this->setting_config_helper->fetch_api_timeout();
		$auth_result   = '';
		try {
			$post_data              = array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'grant_type'    => 'client_credentials',
			);
			$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
			$auth_endpoint          = $this->config_helper->fetch_api_endpoint( 'synchrony_test_authentication_api_endpoint', 'synchrony_deployed_authentication_api_endpoint', $is_synchrony_test_mode );
			$auth_transport         = new Synchrony_Http( $auth_endpoint, $timeout, self::PROXY );
			$auth_response          = $auth_transport->post( $post_data, 'no', null );
			if ( 1 === $token_gen_flag ) {
				$auth_transport = new Synchrony_Http( $auth_endpoint, $timeout, self::PROXY );
				$auth_response  = $auth_transport->post( $post_data, 'no', null );
			}
			if ( $auth_response ) {
				$auth_result = json_decode( $auth_response, true );
			}
			if ( null === $auth_result ) {
				$this->logger->debug( 'issue in authentication api response decode: Unable to decode Authentication API response, ' . self::FILE_PATH_TXT . ': ' . __FILE__ );
			}
			if ( isset( $auth_result['access_token'] ) && ' ' !== $auth_result['access_token'] ) {
				$expiry = $auth_result['expires_in'] - 300;
				set_transient( 'synchrony_access_token', $auth_response, $expiry );
				return $auth_response;
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return '';
	}

	/**
	 * Call Get Ruleset API
	 *
	 * @return array
	 * @throws \Exception - If an error occurs during API call.
	 */
	public function retrieve_rulesets() {
		$auth_result      = '';
		$ruleset_response = '';
		$auth_token       = $this->retrieve_auth_token();
		if ( empty( $auth_token ) ) {
			return array();
		}
		$auth_result            = json_decode( $auth_token );
		$timeout                = $this->setting_config_helper->fetch_api_timeout();
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		$promo_endpoint         = $this->config_helper->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', $is_synchrony_test_mode );
		$promo_tags_attribute   = $promo_endpoint . '/rulesets?platform=WOOCOMMERCE';
		try {
			$ruleset_transport = new Synchrony_Http( $promo_tags_attribute, $timeout, self::PROXY );
			$ruleset_response  = $this->retrieve_api_call( $ruleset_transport, $auth_result );

			if ( $ruleset_response && $ruleset_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN ) {
				$auth_result      = json_decode( $this->retrieve_auth_token( 1 ) );
				$ruleset_response = $this->retrieve_api_call( $ruleset_transport, $auth_result );
			}

			if ( $ruleset_response ) {
				$ruleset_response = json_decode( $ruleset_response, true );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return $ruleset_response;
	}

	/**
	 * Call Get Ruleset API for multi widget.
	 *
	 * @return string
	 * @throws \Exception - If an error occurs while getting product attributes.
	 */
	public function multiwidretrieve_rulesets() {
		$auth_result            = json_decode( $this->retrieve_auth_token() );
		$access_token           = ( $auth_result ) ? $auth_result->access_token : '';
		$tracking_id            = $this->common_config_helper->fetch_tracking_id();
		$channel_id             = $this->common_config_helper->fetch_synchrony_channel_id();
		$timeout                = $this->setting_config_helper->fetch_api_timeout();
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		$promo_endpoint         = $this->config_helper->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', $is_synchrony_test_mode );
		$promo_tags_attribute   = $promo_endpoint . '/rulesets?platform=WOOCOMMERCE';
		$headers                = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, '' . self::HEADER_REQ_CHANNELID . ': ' . $channel_id );
		$ruleset_response       = '';
		try {
			$ruleset_transport = new Synchrony_Http( $promo_tags_attribute, $timeout, self::PROXY );
			$ruleset_response  = $ruleset_transport->get( $headers );
			$ruleset_response  = json_decode( $ruleset_response, true );
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return $ruleset_response;
	}
	/**
	 * Call Get Product Attributes
	 *
	 * @return array
	 * @throws \Exception - If an error occurs while getting product attributes.
	 */
	public function retrieve_product_attributes() {
		$attribute_response = '';
		$auth_token         = $this->retrieve_auth_token();
		if ( empty( $auth_token ) ) {
			return array();
		}
		$auth_result            = json_decode( $auth_token );
		$timeout                = $this->setting_config_helper->fetch_api_timeout();
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		$promo_endpoint         = $this->config_helper->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', $is_synchrony_test_mode );
		$promo_tags_attribute   = $promo_endpoint . '/productattributes';

		try {
			$ruleset_transport  = new Synchrony_Http( $promo_tags_attribute, $timeout, self::PROXY );
			$attribute_response = $this->retrieve_api_call( $ruleset_transport, $auth_result );

			if ( $attribute_response && $ruleset_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN ) {
				$auth_result        = json_decode( $this->retrieve_auth_token( 1 ) );
				$attribute_response = $this->retrieve_api_call( $ruleset_transport, $auth_result );
			}
			if ( $attribute_response ) {
				$attribute_response = json_decode( $attribute_response, true );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return $attribute_response;
	}

	/**
	 * Call tag determination API.
	 *
	 * @param array $tag_post_value This is for data.
	 *
	 * @return array|void
	 * @throws \Exception - If an Exception occurs.
	 */
	public function retrieve_tag_determination( $tag_post_value = '' ) {
		$auth_result            = json_decode( $this->retrieve_auth_token() );
		$access_token           = ( $auth_result ) ? $auth_result->access_token : '';
		$tracking_id            = $this->common_config_helper->fetch_tracking_id();
		$partner_id             = $this->setting_config_helper->fetch_partner_id();
		$channel_id             = $this->common_config_helper->fetch_synchrony_channel_id();
		$timeout                = $this->setting_config_helper->fetch_api_timeout();
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		$promo_endpoint         = $this->config_helper->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', $is_synchrony_test_mode );
		$promo_deter_api        = $promo_endpoint . '/tags';
		$headers                = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, 'X-SYF-Request-channelId: ' . $channel_id, 'X-SYF-Request-partnerId:' . $partner_id );
		$attribute_response     = '';

		try {
			$ruleset_transport  = new Synchrony_Http( $promo_deter_api, $timeout, self::PROXY );
			$attribute_response = $ruleset_transport->post( $tag_post_value, 'yes', $headers );
			$attribute_response = json_decode( $attribute_response, true );
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return $attribute_response;
	}

	/**
	 * Process a  refund for transaction.
	 *
	 * @param array $data This is for data.
	 *
	 * @return array|void
	 * @throws \Exception - If an Exception occurs.
	 */
	public function refund( array $data ) {
		$auth_result = json_decode( $this->retrieve_auth_token() );
		$result      = null;
		$headers     = array();
		if ( $auth_result ) {
			$access_token = $auth_result->access_token;
			$tracking_id  = $this->common_config_helper->generate_reference_id();
			$timeout      = $this->setting_config_helper->fetch_api_timeout();
			$headers      = array(
				'' . self::HEADER_BEARER . ' ' . $access_token,
				'' . self::HEADER_TRACKING_ID . ': ' . $tracking_id,
				'X-SYF-Batch-Process:false',
				'' . self::HEADER_CHANNELID . ': WE',
			);
			$amount       = floatval( $data['amount'] );
			if ( ! isset( $data['TransactionToken'] ) && empty( $data['TransactionToken'] ) ) {
				return $result;
			}
			$post_data = array(
				'action'           => self::REFUND_ACTION,
				'transactionToken' => $data['TransactionToken'],
				'transactionInfo'  => array(
					'amount' => $amount,
				),
			);
			// Child Merchant Number.
			$child_merchant_id = $this->setting_config_helper->fetch_child_merchant_id();
			if ( $child_merchant_id ) {
				$post_data['childMerchantNumber'] = $child_merchant_id;
			}
			return $this->refund_request( $post_data, $timeout, $headers );
		}
	}

	/**
	 * Process a refund request.
	 *
	 * @param array $post_data This contains post data.
	 * @param int   $timeout   This is for timeout.
	 * @param array $headers   This is for headers.
	 *
	 * @return mixed
	 * @throws \Exception - If an Exception occurs during the request.
	 */
	private function refund_request( $post_data, $timeout, $headers ) {
		$result = null;
		try {
			$is_syf_test_mode  = $this->common_config_helper->does_test_mode();
			$transact_endpoint = $this->config_helper->fetch_api_endpoint( 'synchrony_test_transactapi_api_endpoint', 'synchrony_deployed_transactapi_api_endpoint', $is_syf_test_mode );
			$refund            = new Synchrony_Http( $transact_endpoint, $timeout, self::PROXY );
			$response          = $refund->post( wp_json_encode( $post_data ), 'yes', $headers );
			if ( $response && $refund->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN ) {
				$auth_result  = json_decode( $this->retrieve_auth_token( 1 ) );
				$access_token = $auth_result->access_token;
				$tracking_id  = $this->common_config_helper->generate_reference_id();
				$headers      = array(
					'' . self::HEADER_BEARER . ' ' . $access_token,
					'' . self::HEADER_TRACKING_ID . ': ' . $tracking_id,
					'X-SYF-Batch-Process:false',
					'' . self::HEADER_CHANNELID . ': WE',
				);
				$refund       = new Synchrony_Http( $transact_endpoint, $timeout, self::PROXY );
				$response     = $refund->post( wp_json_encode( $post_data ), 'yes', $headers );
			}
			$refund_result = json_decode( $response, true );
			if ( $refund_result ) {
				if ( '' !== $refund_result['transactionId'] && isset( $refund_result['transactionId'] ) ) {
					$refund_result['status'] = 'APPROVED';
				} else {
					$refund_result['status'] = 'FAILED';
				}
				$result = array(
					'Status'       => $refund_result['status'],
					'ResponseText' => $refund_result['transactionId'],
				);
				if ( 'APPROVED' === $refund_result['status'] ) {
					$result['ResponseCode'] = '000';
				} else {
					$result['ResponseCode'] = '001';
				}
			}

			if ( null === $result ) {
				throw new \Exception( 'Unable to decode Refund API response: ' . json_last_error_msg() );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return $result;
	}

	/**
	 * Send Log Data to Synchrony
	 *
	 * @param array $data This contains data.
	 *
	 * @return array
	 */
	public function send_logs( array $data ) {
		$result                = null;
		$is_syf_test_mode      = $this->common_config_helper->does_test_mode();
		$base_url              = $this->config_helper->fetch_api_endpoint( 'synchrony_test_logger_api_endpoint', 'synchrony_logger_api_endpoint', $is_syf_test_mode );
		$timeout               = $this->setting_config_helper->fetch_api_timeout();
		$message               = $data['message'];
		$auth_token            = '';
		$logauth_session_token = '';
		try {
			$auth_token = json_decode( $this->retrieve_auth_token(), true );
			if ( ! $auth_token ) {
				return array();
			}
			$logauth_session_token = $auth_token['access_token'];
			$getauth_token         = $logauth_session_token;

			$get_auth_expiry = '';
			if ( ! $getauth_token ) {
				$auth_result     = $auth_token;
				$getauth_token   = $auth_result['access_token'];
				$get_auth_expiry = $auth_result['expires_in'];
			}
			if ( ! empty( $getauth_token ) ) {
				$tracking_id = $this->common_config_helper->fetch_tracking_id();
				$headers     = array(
					'' . self::HEADER_BEARER . ' ' . $getauth_token,
					'' . self::HEADER_TRACKING_ID . ': ' . $tracking_id,
					'' . self::HEADER_REQ_CHANNELID . ': ' . self::SEND_LOGS_X_SYNCHRONY_REQUEST_CHANNEL_ID,
				);

				$str_maxlength = self::MAX_LENGTH;
				$split_msg     = str_split( $message, $str_maxlength );
				$j             = 0;
				$gm_data       = gmdate( 'Y-m-d H:m:s' );

				foreach ( $split_msg as $split_val ) {
					if ( 3 === $j ) {
						break;
					}
					$merge_msg[ $j ]['logType'] = $data['logType'];
					// Encoding binary data for transmission.
					$merge_msg[ $j ]['logMessage']        = base64_encode( $split_val ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$merge_msg[ $j ]['logDateTime']       = $gm_data;
					$merge_msg[ $j ]['logDateTimeFormat'] = self::DATE_FORMAT;
					++$j;
				}
				$params       = array(
					'sourceIp'   => $data['ip'],
					'logMessage' => $merge_msg,
				);
				$http_request = new Synchrony_Http( $base_url, $timeout, null );
				$response     = $http_request->post( $params, 'yes', $headers );

				if ( $response && $http_request->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN ) {
					$auth_result   = json_decode( $this->retrieve_auth_token( 1 ), true );
					$getauth_token = $auth_result['access_token'];
					$headers       = array(
						'' . self::HEADER_BEARER . ' ' . $getauth_token,
						'' . self::HEADER_TRACKING_ID . ': ' . $tracking_id,
						'' . self::HEADER_REQ_CHANNELID . ': ' . self::SEND_LOGS_X_SYNCHRONY_REQUEST_CHANNEL_ID,
					);
					$response      = $http_request->post( $params, 'yes', $headers );
				}
				if ( $response ) {
					$result = json_decode( $response, true );
				}
				$logresponse = $this->retrieve_logresponse( $result, $getauth_token, $get_auth_expiry );
			}
		} catch ( \Exception $e ) {
			$this->logger->info( 'Error while calling Send Logs API: ' . $e->getMessage() );
		}
		return $logresponse;
	}

	/**
	 * Retrieve response for send logs function.
	 *
	 * @param array  $result This contains data.
	 * @param string $getauth_token This is auth token.
	 * @param string $get_auth_expiry This auth expiry.
	 *
	 * @return array
	 */
	public function retrieve_logresponse( $result, $getauth_token, $get_auth_expiry ) {
		$logresponse = array();
		if ( null !== $result ) {
			$logresponse                             = $result;
			$logresponse['log_access_token']         = $getauth_token;
			$logresponse['log_access_token_timeout'] = $get_auth_expiry;
		}
		return $logresponse;
	}
	/**
	 * Module tracking functionality
	 *
	 * @param array $data This contains data.
	 *
	 * @return array
	 */
	public function module_tracking( array $data ) {
		$final_response = '';
		$error_response = array(
			'code'        => '',
			'message'     => 'Something went wrong. Please try after sometime',
			'trackingId'  => '',
			'endPointUrl' => '',
		);
		$response       = $this->module_tracking_api_call( $data );
		if ( empty( $response ) ) {
			return $error_response;
		}
		if ( $response ) {
			$final_response = json_decode( $response, true );
		}
		if ( empty( $final_response ) && ! is_array( $final_response ) ) {
			$resfinal = $error_response;
		} else {
			$resfinal = $this->generate_response( $final_response );
		}
		return $resfinal;
	}
	/**
	 * Module tracking
	 *
	 * @param array $data This is for data.
	 *
	 * @return mixed
	 */
	public function module_tracking_api_call( array $data ) {
		$apicall    = false;
		$partner_id = $this->setting_config_helper->fetch_partner_id();
		$base_uri   = $this->config_helper->fetch_tracker_endpoint( $partner_id );
		$syfkey     = $this->config_helper->fetch_synchrony_api_key();
		$channel_id = $this->common_config_helper->fetch_synchrony_channel_id();
		$timeout    = $this->setting_config_helper->fetch_api_timeout();

		if ( '' === $partner_id ) {
			$headers        = array(
				'X-SYF-API-Key: ' . $syfkey,
				'' . self::HEADER_REQ_CHANNELID . ': ' . $channel_id,
			);
			$exclude_fields = array( 'configChangeDetails' );
			$data_final     = $this->retrieve_data_final( $data, $exclude_fields );
			$apicall        = true;
		} else {
			$access_token = '';
			if ( empty( $this->retrieve_auth_token() ) ) {
				return false;
			}
			$auth_result  = json_decode( $this->retrieve_auth_token() );
			$access_token = ( $auth_result ) ? $auth_result->access_token : '';
			$headers      = array(
				'' . self::HEADER_REQ_PARTNERID . ':' . $partner_id,
				'' . self::HEADER_BEARER . ' ' . $access_token,
				'' . self::HEADER_REQ_CHANNELID . ': ' . $channel_id,
			);
			$data_final   = $data;
			$apicall      = true;
		}
		if ( empty( $base_uri ) && ! is_string( $base_uri ) && ! $apicall ) {
			return false;
		}
		$post_data    = wp_json_encode( $data_final );
		$http_request = new Synchrony_Http( $base_uri, $timeout, self::PROXY );
		$response     = $http_request->post( $post_data, 'yes', $headers );

		if ( $response && $http_request->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_INVALID_ACCESS_TOKEN ) {
			$auth_result  = json_decode( $this->retrieve_auth_token( 1 ) );
			$access_token = ( $auth_result ) ? $auth_result->access_token : '';
			$headers      = array(
				'' . self::HEADER_REQ_PARTNERID . ':' . $partner_id,
				'' . self::HEADER_BEARER . ' ' . $access_token,
				'' . self::HEADER_REQ_CHANNELID . ': ' . $channel_id,
			);
			$http_request = new Synchrony_Http( $base_uri, $timeout, self::PROXY );
			$response     = $http_request->post( $post_data, 'yes', $headers );
		}
		return $response;
	}
	/**
	 * Get final data for tracking.
	 *
	 * @param array $data This contains data.
	 * @param array $exclude_fields This contains excluded fields.
	 *
	 * @return array
	 */
	public function retrieve_data_final( $data, $exclude_fields ) {
		$data_final = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $exclude_fields, true ) ) {
				continue;
			}
			$data_final[ $key ] = $value;
		}
		return $data_final;
	}

	/**
	 * Generate Response
	 *
	 * @param mixed $final_response This process final response.
	 *
	 * @return mixed
	 */
	public function generate_response( $final_response ) {
		$partner_id = $this->setting_config_helper->fetch_partner_id();
		$base_uri   = $this->config_helper->fetch_tracker_endpoint( $partner_id );
		return array(
			'code'        => array_key_exists( 'code', $final_response ) ? $final_response['code'] : '',
			'message'     => array_key_exists( 'message', $final_response ) ? $final_response['message'] : 'Something went wrong. Please try after sometime',
			'trackingId'  => array_key_exists( 'trackingId', $final_response ) ? $final_response['trackingId'] : '',
			'endPointUrl' => $base_uri,
		);
	}

	/**
	 * Post API Call
	 *
	 * @param mixed $auth_transport This is auth_transport object.
	 * @param mixed $auth_result This is auth_result object.
	 * @param array $post_data This is post_data to pass in API.
	 *
	 * @return mixed
	 */
	public function post_api_call( $auth_transport, $auth_result, $post_data ) {
		$access_token = '';
		$tracking_id  = '';
		if ( ! empty( $auth_result ) ) {
			$access_token = $auth_result->access_token;
			$tracking_id  = $auth_result->application_name;
		}
		$headers = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id );
		return $auth_transport->post( $post_data, 'yes', $headers );
	}

	/**
	 * Get API Call
	 *
	 * @param mixed $ruleset_transport This is ruleset_transport object.
	 * @param mixed $auth_result This is auth_result object.
	 *
	 * @return mixed
	 */
	public function retrieve_api_call( $ruleset_transport, $auth_result ) {
		$access_token = '';
		if ( ! empty( $auth_result ) ) {
			$access_token = ( $auth_result ) ? $auth_result->access_token : '';
		}
		$tracking_id = $this->common_config_helper->fetch_tracking_id();
		$partner_id  = $this->setting_config_helper->fetch_partner_id();
		$channel_id  = $this->common_config_helper->fetch_synchrony_channel_id();

		$headers = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, '' . self::HEADER_REQ_CHANNELID . ': ' . $channel_id, '' . self::HEADER_REQ_PARTNERID . ':' . $partner_id );

		return $ruleset_transport->get( $headers );
	}
}
