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
use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Logs\Synchrony_Logger;

/**
 * Class Synchrony_Client
 * This class handles communication with the server.
 */
class Synchrony_Http_Client {
	/**
	 * PROXY
	 *
	 * @var null
	 */
	private const PROXY = null;

	/**
	 * ERROR_TXT
	 *
	 * @var string
	 */
	private const ERROR_TXT = 'error';
	/**
	 * Logger
	 *
	 * @var Synchrony_Logger
	 */

	/**
	 * HEADER_CHANNELID
	 *
	 * @var string
	 */
	private const HEADER_CHANNELID = 'X-SYF-CHANNELID';
	/**
	 * FILE_PATH_TXT
	 *
	 * @var string
	 */
	/**
	 * CHANNEL_ID
	 *
	 * @var string
	 */
	private const CHANNEL_ID = 'DY';
	/**
	 * LINE_NO_TXT
	 *
	 * @var string
	 */
	private const LINE_NO_TXT = 'line no';
	/**
	 * EXCEPTION_MSG_TXT
	 *
	 * @var string
	 */
	private const EXCEPTION_MSG_TXT = 'exception message';
	/**
	 * FILE_PATH_TXT
	 *
	 * @var string
	 */
	private const FILE_PATH_TXT = 'file path';
	/**
	 * Logger
	 *
	 * @var Logger $logger
	 */
	private $logger;
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
	 * Client
	 *
	 * @var Synchrony_Client $client
	 */
	private $client;
	/**
	 * HTTP_RESPONSE_CODE_SUCCESS
	 */
	private const HTTP_RESPONSE_CODE_SUCCESS = 200;
	/**
	 * HEADER_TRACKING_ID
	 *
	 * @var string
	 */
	private const HEADER_TRACKING_ID = 'X-SYF-Request-TrackingId';
	/**
	 * HEADER_BEARER
	 *
	 * @var string
	 */
	private const HEADER_BEARER = 'Authorization: Bearer';
	/**
	 * Constructor for thr client class.
	 */
	public function __construct() {
		$this->logger                = new Synchrony_Logger();
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$this->client                = new Synchrony_Client();
	}
	/**
	 * Find Status API Call.
	 *
	 * @param string $token_id This is mpp token.
	 *
	 * @return array
	 */
	public function find_status_api_call( $token_id ) {
		$partner_id = $this->setting_config_helper->fetch_partner_id();
		$timeout    = $this->setting_config_helper->fetch_api_timeout();
		$auth_token = $this->client->retrieve_auth_token();
		if ( empty( $auth_token ) ) {
			return array();
		}
		$auth_result = json_decode( $auth_token );
		$this->logger->debug( 'mpp token_id : ' . $token_id );
		$access_token = $auth_result->access_token;
		$this->logger->debug( 'access_token : ' . $access_token );
		$tracking_id          = $auth_result->application_name;
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		if ( $this->setting_config_helper->fetch_child_merchant_id() && 'no' === $is_activation_enable ) {
			$find_status = $this->fetch_find_status_endpoint() . $token_id . '?lookupType=MERCHANT_NUMBER&lookupId=' . $this->setting_config_helper->fetch_child_merchant_id();
		} else {
			$find_status = $this->fetch_find_status_endpoint() . $token_id . '?lookupType=PARTNERID&lookupId=' . $partner_id;
		}
		$this->logger->debug( 'find status url : ' . $find_status );
		$headers = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, '' . self::HEADER_CHANNELID . ': ' . self::CHANNEL_ID );
		return $this->find_status_request( $find_status, $timeout, $headers );
	}

	/**
	 * Find Status Request.
	 *
	 * @param string $find_status This is endpoint.
	 * @param int    $timeout Timeout API request.
	 * @param array  $headers Post Request Headers.
	 *
	 * @return array
	 */
	public function find_status_request( $find_status, $timeout, $headers ) {
		try {
			$findstatus_transport = new Synchrony_Http( $find_status, $timeout, self::PROXY );
			$findstatus_response  = $findstatus_transport->get( $headers );
			$response             = json_decode( $findstatus_response, true );
			$this->logger->debug( 'Find Status Response : ' . $findstatus_response );

			if ( $findstatus_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_SUCCESS ) {
				return $this->extract_customer_info( $response, $findstatus_transport );
			} else {
				return $this->get_error_response( $findstatus_transport );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
				return array(
					'error'   => 'Exception',
					'message' => $e->getMessage(),
				);
		}
	}
	/**
	 * Extract Customer Info.
	 *
	 * @param mixed  $response Response from API.
	 * @param string $findstatus_transport Response Object.
	 *
	 * @return array
	 */
	public function extract_customer_info( $response, $findstatus_transport ) {

		$customer_info = $response['accountLookupInfo']['customerInfo'];
		$firstname     = isset( $customer_info['cipher.firstName'] ) ? $customer_info['cipher.firstName'] : '';
		$lastname      = isset( $customer_info['cipher.lastName'] ) ? $customer_info['cipher.lastName'] : '';
		$address       = $response['accountLookupInfo']['customerInfo']['address'];
		$addressline1  = isset( $address['cipher.addressLine1'] ) ? $address['cipher.addressLine1'] : '';
		$addressline2  = isset( $address['cipher.addressLine2'] ) ? $address['cipher.addressLine2'] : '';
		$city          = isset( $address['cipher.city'] ) ? $address['cipher.city'] : '';
		$state         = isset( $address['cipher.state'] ) ? $address['cipher.state'] : '';
		$zip_code      = isset( $address['cipher.zipCode'] ) ? $address['cipher.zipCode'] : '';

		return array(
			'response_code'      => $findstatus_transport->retrieve_last_response_code(),
			'billing_first_name' => $firstname,
			'billing_last_name'  => $lastname,
			'billing_address_1'  => $addressline1,
			'billing_address_2'  => $addressline2,
			'city'               => $city,
			'state'              => $state,
			'zip_code'           => $zip_code,
		);
	}
	/**
	 * Find Status Request.
	 *
	 * @param string $findstatus_transport Response Object.
	 *
	 * @return array
	 */
	private function get_error_response( $findstatus_transport ) {
		return array(
			'error'   => $findstatus_transport->retrieve_last_response_code(),
			'message' => 'something went wrong, please try again later',
		);
	}
	/**
	 * Retrieve Status Inquiry API endpoint
	 *
	 * @return string
	 */
	public function fetch_find_status_endpoint() {
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		return $this->config_helper->fetch_api_endpoint( 'synchrony_test_findstatus_api_endpoint', 'synchrony_deployed_findstatus_api_endpoint', $is_synchrony_test_mode );
	}
}
