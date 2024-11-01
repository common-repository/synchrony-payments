<?php
/**
 * This file contains the SMB classes.
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
use Synchrony\Payments\Helper\Synchrony_Smb_Helper;

/**
 * Class Synchrony_Smb
 * This class handles communication with the server.
 */
class Synchrony_Smb {

	/**
	 * PROXY
	 *
	 * @var null
	 */
	public const PROXY = null;

	/**
	 * HEADER_TRACKING_ID
	 *
	 * @var string
	 */
	public const HEADER_TRACKING_ID = 'X-SYF-Request-TrackingId';

	/**
	 * HEADER_CHANNELID
	 *
	 * @var string
	 */
	public const HEADER_CHANNELID = 'X-SYF-CHANNELID';

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
	 * CODE_CHALLENGE_METHOD
	 *
	 * @var string
	 */
	public const CODE_CHALLENGE_METHOD = 'S256';
	/**
	 * PLATFORM
	 *
	 * @var string
	 */
	public const PLATFORM = 'WOOCOMMERCE';

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
	 * CHANNEL_ID
	 *
	 * @var string
	 */
	public const CHANNEL_ID = 'DY';

	/**
	 * SANDBOX_PUBLIC_KEY
	 *
	 * @var string
	 */
	public const SANDBOX_PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs8VaPQF0m9F0zim+MwIQQrOkHBcOEzS0Cr8mW+rv9iif9MHuwjdGN1NVaU22m/icOPpEmcEI2Dd/LU11gF/KCn0hQ7mrww+j7vaJOmE+Z+KgpSRc74J5nhq98hn9/cMLe2zubXGoep7Q1m4ofBrQ4GM+zstIJk2HZZ1Gb06DEfdu4C2OHw+tw9WI4woJciqjXsYmeVVDhSz9SrQqGBjhpMTWgiNkVrKR12wBd5aTxAoA8XOwO5K4NEwqfOdCVWxm1QjAYDvb6C6M4miYFobCD7Vbe//4UTzJaATfOc0WfmYL0HIHsU2snLgCSSEbLPzTr/5HKSIjCbZ1EW70Eltk4QIDAQAB';

	/**
	 * PRODUCTION_PUBLIC_KEY
	 *
	 * @var string
	 */
	public const PRODUCTION_PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAldbwgPhqKgJCCzM+0IrA/Z52kneIdtKqTrhY+jicnLmCOs44Og+NFXnPDCo6l8nnSznJVRADtpqY6ucJDfGJLfHJ3ZBgHosjsUyRXwTZNGliRgLf9xdCAEMjOA/xYnTIQ3o3RPrCd3HzD8GSk5TTJIxnlsbkuKPmPBPmG3AwJY9iPf6f//xexoRs6sQ8glGSrVscZ/wrE+TmtuY4JKjh8ZlCOyJsZYhaakY2Ih+tEmlq6CB9nYWxdO3kTgkR7NI1LC/7doDIzPC4WmDS2Cm8Fr6XNiCCdjTXeUzlgyhyzcs/GsfKsNxLqftvEJQF+eqOiZceV4LiQKptuw9NVGfHnwIDAQAB';

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
	 * Synchrony_Smb_Helper
	 *
	 * @var Synchrony_Smb_Helper
	 */
	private $smb_helper;

	/**
	 * Environment
	 *
	 * @var int
	 */
	private $environment;

	/**
	 * Module Enabled
	 *
	 * @var int
	 */
	private $module_enabled;

	/**
	 * Activation Key
	 *
	 * @var string
	 */
	private $activation_key;

	/**
	 * Auth Response Error
	 *
	 * @var string
	 */

	private $authresponse_error;

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
	 * SANDBOX_DOMAIN_ENDPOINT
	 *
	 * @var string
	 */
	public const SANDBOX_DOMAIN_ENDPOINT = 'synchrony_test_smb_domain_api_endpoint';
	/**
	 * PRODUCTION_DOMAIN_ENDPOINT
	 *
	 * @var string
	 */
	public const PRODUCTION_DOMAIN_ENDPOINT = 'synchrony_deployed_smb_domain_api_endpoint';

	/**
	 * Constructor for thr client class.
	 */
	public function __construct() {
		$this->logger                = new Synchrony_Logger();
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->smb_helper            = new Synchrony_Smb_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$this->authresponse_error    = __( 'Something went wrong.' );
	}

	/**
	 * Smb partner activate.
	 *
	 * @param array $data This is for data.
	 *
	 * @return array
	 */
	public function get_module_activation_info( $data ) {
		$post_client_id       = $data->get_param( 'client_id' );
		$this->activation_key = $data->get_param( 'activation_key' );
		$this->environment    = $data->get_param( 'environment' );
		$this->module_enabled = $data->get_param( 'isenabled' );
		$smb_domain           = $data->get_param( 'domain' );
		$this->reactivate     = $data->get_param( 'reactivate' );
		if ( $post_client_id && $this->activation_key && ! $this->reactivate ) {
			return $this->retrieve_smb_auth_client_rotation( $post_client_id, $smb_domain );
		}
		if ( ! $this->activation_key || empty( $smb_domain ) ) {
			return new \WP_Error(
				'error_response_smb',
				__( 'Invalid Request' ),
				array(
					'status' => 400,
					'data'   => $data,
				)
			);
		}
		return $this->activation_api_call( $smb_domain );
	}

	/**
	 * Smb partner activate API call.
	 *
	 * @param string $smb_domain SMB domains.
	 *
	 * @return array
	 */
	public function activation_api_call( $smb_domain ) {
		$is_sandbox = $this->common_config_helper->does_test_mode();
		if ( $this->environment ) {
			$is_sandbox = 'test' === $this->environment ? true : false;
		}
		$client_id      = $is_sandbox ? $this->smb_helper::SANDBOX_STATIC_CLIENT_ID : $this->smb_helper::PRODUCTION_STATIC_CLIENT_ID;
		$reference_id   = $this->smb_helper->generate_random_string();
		$code_challenge = $this->smb_helper->generate_hash_reference_id( $reference_id );
		$tracking_id    = $this->common_config_helper->generate_reference_id();

		$post_data = array(
			'activationKey'         => $this->activation_key,
			'client_id'             => $client_id,
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => self::CODE_CHALLENGE_METHOD,
			'store'                 => array(
				'whitelistDomains' => explode( ',', $smb_domain ),
				'platform'         => self::PLATFORM,
			),
		);

		$this->logger->debug( 'activation_api_call post data' . wp_json_encode( $post_data ) );

		$headers = array( '' . self::HEADER_CHANNELID . ': DY', '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, '' . self::HEADER_API_KEY . ': ' . $client_id );

		// Activate API call.
		$auth_response = $this->recursive_api_call( $post_data, $headers, 'smb_post_api_call', 2 );
		if ( empty( $auth_response ) ) {
			return new \WP_Error( 'error_response_smb', $this->authresponse_error, array( 'status' => 400 ) );
		}

		// Second api call i.e oauth token api.
		$response       = json_decode( $auth_response, true );
		$code           = $response['code'];
		$final_response = $this->retrieve_smb_auth_token( $client_id, $code, $reference_id );
		if ( empty( $final_response ) ) {
			return new \WP_Error( 'error_response_smb', $this->authresponse_error, array( 'status' => 400 ) );
		}
		return $final_response;
	}

	/**
	 * Call Get Token API for smb.
	 *
	 * @param string $client_id This is client id.
	 * @param string $activation_code This is activation code.
	 * @param string $reference_id This is random generated string.
	 *
	 * @return mixed
	 */
	public function retrieve_smb_auth_token( $client_id = null, $activation_code = null, $reference_id = null ) {
		$is_activation_enabled = $this->common_config_helper->fetch_activation_enable_flag();
		$tracking_id           = $this->common_config_helper->generate_reference_id();
		if ( 'yes' === $is_activation_enabled || $activation_code ) {
			$header    = array( 'X-SYF-CHANNELID: DY' );
			$header[]  = 'X-SYF-Request-TrackingId: ' . $tracking_id;
			$post_data = array(
				'client_id'     => $client_id,
				'grant_type'    => 'authorization_code',
				'code'          => $activation_code,
				'code_verifier' => $reference_id,
				'redirect_uri'  => 'https://shop.synchrony.com',
			);
			// Save data into cache.
			$auth_response = $this->request_smb_post_api( $post_data, $header );
			$auth_result   = json_decode( $auth_response, true );
			if ( ! empty( $auth_result ) ) {
				$expiry = $auth_result['expires_in'] - 300;
				set_transient( 'synchrony_smb_access_token', $auth_response, $expiry );
				return $auth_response;
			}
		}
		return '';
	}

	/**
	 * Call Get Token API for smb.
	 *
	 * @param bool $is_sandbox_activation This is sandbox activation flag.
	 *
	 * @return string
	 */
	public function retrieve_smb_auth_refresh_token( $is_sandbox_activation ) {
		$is_synchrony_test = $this->common_config_helper->does_test_mode();
		$tracking_id       = $this->common_config_helper->generate_reference_id();
		$response          = '';
		if ( $is_sandbox_activation ) {
			$header           = array( 'X-SYF-CHANNELID: DY' );
			$header[]         = 'X-SYF-Request-TrackingId: ' . $tracking_id;
			$environment_type = $is_synchrony_test ? 2 : 1;
			$getrows          = $this->smb_helper->retrieve_columns_from_table( 'synchrony_partner_auth', '*', $environment_type );
			if ( empty( $getrows ) ) {
				$this->logger->debug( '' . self::ERROR_TXT . ': Error in refresh token call. No data found ' );
				return '';
			}
			$client_id     = $getrows->client_id;
			$header[]      = 'X-SYF-UserId: ' . $getrows->partner_id;
			$post_data     = array(
				'client_id'     => $client_id,
				'grant_type'    => 'refresh_token',
				'refresh_token' => $getrows->refresh_token,
				'redirect_uri'  => 'https://shop.synchrony.com',
			);
			$auth_response = $this->request_smb_post_api( $post_data, $header );

			$auth_result = json_decode( $auth_response, true );

			if ( ! empty( $auth_result ) ) {
				$expiry = $auth_result['expires_in'] - 300;
				set_transient( 'synchrony_smb_access_token', $auth_response, $expiry );
				$response = $auth_response;
			}
			// Check for static client id.
			if ( $getrows->syf_version && $getrows->syf_version !== $this->common_config_helper->fetch_app_version() && $getrows->client_id !== $this->smb_helper->fetch_static_client_id() ) {
				return $this->retrieve_smb_auth_client_rotation( $this->smb_helper->fetch_static_client_id() );
			}
		}

		return $response;
	}

	/**
	 * Client ID Rotation.
	 *
	 * @param string $client_id This is client id.
	 * @param string $domains This is domains passed from api.
	 *
	 * @return string
	 */
	public function retrieve_smb_auth_client_rotation( $client_id, $domains = '' ) {
		if ( $domains && get_transient( 'synchrony_whitelist_domain' ) ) {
			$this->update_domain_client_id_rotation( $domains );
		}
		$is_synchrony_test = $this->common_config_helper->does_test_mode();
		$environment_type  = $is_synchrony_test ? 2 : 1;
		$getrows           = $this->smb_helper->retrieve_columns_from_table( 'synchrony_partner_auth', '*', $environment_type );
		if ( empty( $getrows ) ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': Error in client id rotation. No data found ' );
			return new \WP_Error( 'error_response_smb', 'Error in client id rotation. No data found', array( 'status' => 400 ) );
		}
		$config_settings = get_option( 'woocommerce_synchrony-unifi-payments_settings' );
		if ( empty( $this->environment ) ) {
			$this->environment = ( isset( $config_settings['synchrony_test'] ) && 'yes' === $config_settings['synchrony_test'] ) ? 'test' : 'deployed';
		}
		$admin_activation_key = $config_settings[ 'synchrony_' . $this->environment . '_activation_key' ];
		if ( $getrows->client_id === $client_id && $this->activation_key === $admin_activation_key ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': Error in client id rotation, duplicate client id.' );
			return new \WP_Error( 'error_response_smb', 'Duplicate Client ID', array( 'status' => 400 ) );
		}
		return $this->client_api_call( $getrows, $client_id );
	}

	/**
	 * Client ID Rotation API Call.
	 *
	 * @param array  $getrows Auth table rows.
	 * @param string $client_id This is client id.
	 *
	 * @return string
	 */
	public function client_api_call( $getrows, $client_id ) {
		$tracking_id    = $this->common_config_helper->fetch_tracking_id();
		$header         = array( '' . self::HEADER_BEARER . ' ' . $getrows->access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, '' . self::HEADER_CHANNELID . ': ' . self::CHANNEL_ID );
		$reference_id   = $this->smb_helper->generate_random_string();
		$code_challenge = $this->smb_helper->generate_hash_reference_id( $reference_id );
		$post_data      = array(
			'client_id'             => $client_id,
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => self::CODE_CHALLENGE_METHOD,
		);
		$is_sandbox     = $this->common_config_helper->does_test_mode();
		$apiurl         = $this->config_helper->fetch_api_endpoint( 'synchrony_test_client_id_rotation_api_endpoint', 'synchrony_deployed_client_id_rotation_api_endpoint', $is_sandbox );
		$auth_response  = $this->smb_post_api_call( $post_data, $header, $apiurl );
		if ( empty( $auth_response ) ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': Error in client id rotation api call.' );
			return new \WP_Error( 'error_response_smb', $this->authresponse_error, array( 'status' => 400 ) );
		}

		// Second api call i.e oauth token api.
		$response       = json_decode( $auth_response, true );
		$code           = $response['code'];
		$final_response = $this->retrieve_smb_auth_token( $client_id, $code, $reference_id );
		if ( empty( $final_response ) ) {
			return new \WP_Error( 'error_response_smb', $this->authresponse_error, array( 'status' => 400 ) );
		}
		return $final_response;
	}

	/**
	 * Update Domain Client ID Rotation API.
	 *
	 * @param string $domains This is domains passed from api.
	 *
	 * @return void
	 */
	public function update_domain_client_id_rotation( $domains ) {
		$whitelist_domain = get_transient( 'synchrony_whitelist_domain' );
		$domain_arr       = explode( ',', $domains );
		$domain_diff      = array_diff( $whitelist_domain, $domain_arr );
		if ( count( $whitelist_domain ) !== count( $domain_arr ) || ! empty( $domain_diff ) ) {
			$this->update_smb_domains( $domain_arr );
		}
	}

	/**
	 * SMB Post API.
	 *
	 * @param array  $post_data This is post_data data.
	 * @param array  $header This is header data.
	 * @param string $apiurl This is apiurl.
	 * @param string $send_as_json This is for send json.
	 *
	 * @return mixed
	 */
	public function request_smb_post_api( $post_data, $header, $apiurl = '', $send_as_json = 'no' ) {
		$timeout = $this->setting_config_helper->fetch_api_timeout();
		try {
			$is_sandbox = $this->common_config_helper->does_test_mode();
			if ( $this->environment ) {
				$is_sandbox = 'test' === $this->environment ? true : false;
			}
			$auth_url = $this->config_helper->fetch_api_endpoint( 'synchrony_test_authentication_api_endpoint', 'synchrony_deployed_authentication_api_endpoint', $is_sandbox );
			$apiurl   = ! $apiurl ? $auth_url : $apiurl;
			$this->logger->debug( 'request_smb_post_api url: ' . $apiurl );
			$this->logger->debug( wp_json_encode( $post_data ) );
			$auth_transport = new Synchrony_Http( $apiurl, $timeout, self::PROXY );
			$auth_response  = $auth_transport->post( $post_data, $send_as_json, $header );
			if ( $auth_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_SUCCESS ) {
				// Save response to custom table and config table.
				$response = json_decode( $auth_response, true );
				$this->save_api_response_customdb( $response );
				return $auth_response;
			} else {
				$this->authresponse_error = $auth_response;
				$this->logger->debug( '' . self::ERROR_TXT . ': SMB AUTH TOKEN API CALL: ' . $auth_response );
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return '';
	}


	/**
	 * Get Refresh Access Token.
	 *
	 * @return string
	 */
	public function retrieve_smb_access_token() {
		try {
			$is_synchrony_test = $this->common_config_helper->does_test_mode();
			$environment_type  = $is_synchrony_test ? 2 : 1;
			$getrows           = $this->smb_helper->retrieve_columns_from_table( 'synchrony_partner_auth', 'generate_refresh_token_time', $environment_type );
			if ( empty( $getrows ) ) {
				$this->logger->debug( '' . self::ERROR_TXT . ': Error in fetching access token. No data found ' );
				return '';
			}
			$date               = new \DateTime();
			$current_time_stamp = $date->getTimestamp();
			$refresh_expiry     = $getrows->generate_refresh_token_time;
			if ( $current_time_stamp > $refresh_expiry ) {
				return $this->retrieve_smb_auth_refresh_token( 'yes' );
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return '';
	}

	/**
	 * Call Get SMB Token API.
	 *
	 * @param string $is_activation_enable This is activation status.
	 * @param int    $token_gen_flag This is flag to get data from cache.
	 *
	 * @return mixed
	 */
	public function retrieve_auth_token_smb( $is_activation_enable, $token_gen_flag = 0 ) {
		if ( 'yes' === $is_activation_enable ) {
			if ( 0 === $token_gen_flag && get_transient( 'synchrony_smb_access_token' ) ) {
				return get_transient( 'synchrony_smb_access_token' );
			}
			return $this->retrieve_smb_auth_refresh_token( $is_activation_enable );
		}
	}

	/**
	 * Recursive API call.
	 *
	 * @param array  $post_data This is post_data data.
	 * @param array  $headers This is headers data.
	 * @param string $callback This is callback string.
	 * @param int    $attempts This is attempts.
	 *
	 * @return mixed
	 */
	public function recursive_api_call( $post_data, $headers, $callback, $attempts = 2 ) {
		if ( $attempts <= 0 ) {
			return false;
		}
		$this->logger->debug( '' . self::ERROR_TXT . ': ' . $callback . ' attempts ' . $attempts );
		// Post API Call.
		$response = $this->$callback( $post_data, $headers );
		if ( false !== $response ) {
			return $response;
		}
		return $this->recursive_api_call( $post_data, $headers, $callback, $attempts - 1 );
	}
	/**
	 * Smb partner activate API call.
	 *
	 * @param array  $post_data This is post_data data.
	 * @param array  $headers This is headers data.
	 * @param string $apiurl This is apiurl.
	 *
	 * @return mixed
	 */
	public function smb_post_api_call( $post_data, $headers, $apiurl = '' ) {
		$is_sandbox = $this->common_config_helper->does_test_mode();
		if ( $this->environment ) {
			$is_sandbox = 'test' === $this->environment ? true : false;
		}
		$api_endpoint = ! $apiurl ? $this->config_helper->fetch_api_endpoint( 'synchrony_test_partner_activate_api_endpoint', 'synchrony_deployed_partner_activate_api_endpoint', $is_sandbox ) : $apiurl;
		$this->logger->debug( 'smb_post_api_call url: ' . $api_endpoint );
		$timeout = $this->setting_config_helper->fetch_api_timeout();
		try {
			$this->logger->debug( '' . self::ERROR_TXT . ': SMB POST API CALL: ' . wp_json_encode( $post_data ) );
			$activate_transport = new Synchrony_Http( $api_endpoint, $timeout, self::PROXY );
			$auth_response      = $activate_transport->post( $post_data, 'yes', $headers );
			if ( $activate_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_SUCCESS ) {
				return $auth_response;
			}
			$this->logger->debug( '' . self::ERROR_TXT . ': SMB POST API CALL: ' . wp_json_encode( $auth_response ) );
			$this->authresponse_error = $auth_response;
			if ( $activate_transport->retrieve_last_response_code() === self::HTTP_RESPONSE_CODE_AFTER_24_HOUR ) {
				return false;
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return false;
	}

	/**
	 * Save api response to custom database table.
	 *
	 * @param array $response This is response data.
	 *
	 * @return bool
	 */
	public function save_api_response_customdb( $response ) {
		try {
			global $wpdb;
			$table_name        = $wpdb->prefix . 'synchrony_partner_auth';
			$is_synchrony_test = $this->common_config_helper->does_test_mode();
			if ( $this->environment ) {
				$environment_type  = 'test' === $this->environment ? 2 : 1;
				$is_synchrony_test = 'test' === $this->environment ? true : false;
			}
			$public_key       = $is_synchrony_test ? self::SANDBOX_PUBLIC_KEY : self::PRODUCTION_PUBLIC_KEY;
			$environment_type = $is_synchrony_test ? 2 : 1;
			$row_count        = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) from $table_name WHERE  env_type = %s", $environment_type ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$jwt_decode       = new Synchrony_Jwt();
			$decoded_data     = $jwt_decode->get_decoded_partner_id( $response['id_token'], $public_key );
			if ( empty( $decoded_data ) ) {
				$this->logger->debug( '' . self::ERROR_TXT . ': Save Response: Cannot Decode Data ' );
				return false;
			}
			$refresh_token_refresh_in    = $decoded_data['refresh_token_refresh_in'];
			$plugin_version              = $this->common_config_helper->fetch_app_version();
			$generate_refresh_token_time = $this->smb_helper->retrieve_refresh_token_expiry( $refresh_token_refresh_in );
			$data                        = array(
				'env_type'                    => $environment_type,
				'partner_id'                  => $decoded_data['partner_id'],
				'client_id'                   => $response['client_id'],
				'access_token'                => $response['access_token'],
				'refresh_token'               => $response['refresh_token'],
				'id_token'                    => $response['id_token'],
				'refresh_token_issue_at'      => $response['refresh_token_issued_at'],
				'generate_refresh_token_time' => $generate_refresh_token_time,
				'refresh_token_refresh_in'    => $refresh_token_refresh_in,
				'expires_in'                  => $response['expires_in'],
				'refresh_token_expired_in'    => $decoded_data['refresh_token_expired_in'],
				'partner_profile_code'        => $decoded_data['partner_profile_code'],
				'syf_version'                 => $plugin_version,
			);
			if ( $row_count > 0 ) {
				return $this->update_customdb_reponse( $data, $environment_type );
			}
			$wpdb->insert( $table_name, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( ! $wpdb->insert_id ) {
				$this->logger->debug( '' . self::ERROR_TXT . ': SMB Save Response: Cannot Insert Data ' . $wpdb->last_error );
			} else {
				$this->update_payment_configuration_settings( $data );
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( '' . self::ERROR_TXT . ': SMB Save Response ' . $e->getMessage() );
		}
		return false;
	}

	/**
	 * Save Payment Config Settings.
	 *
	 * @param array $data This is data.
	 *
	 * @return void  *
	 */
	public function update_payment_configuration_settings( $data ) {
		$config_settings = get_option( 'woocommerce_synchrony-unifi-payments_settings' );
		if ( empty( $config_settings ) ) {
			$config_settings = array();
		}
		$is_sandbox = $this->common_config_helper->does_test_mode() ? 'yes' : 'no';
		if ( $this->environment ) {
			$is_sandbox = 'test' === $this->environment ? 'yes' : 'no';
		}
		$is_enabled = 'no';
		if ( $this->module_enabled ) {
			$is_enabled = $this->module_enabled ? 'yes' : 'no';
		}
		$config_settings['woocommerce_synchrony-unifi-payments_enabled']          = $this->module_enabled;
		$config_settings['synchrony_test']                                        = $is_sandbox;
		$config_settings[ 'synchrony_' . $this->environment . '_activation_key' ] = $this->activation_key;
		$config_settings[ 'synchrony_' . $this->environment . '_digitalbuy_api_smb_partner_id' ] = $data['partner_id'];
		$config_settings[ 'synchrony_' . $this->environment . '_digitalbuy_api_smb_client_id' ]  = $data['client_id'];
		$config_settings[ 'synchrony_' . $this->environment . '_enable_activation' ]             = 'yes';
		$config_settings[ $this->environment . '_enable_activation' ]                            = 'yes';
		update_option( 'woocommerce_synchrony-unifi-payments_settings', $config_settings );
	}

	/**
	 * Get SMB Domains.
	 *
	 * @return mixed
	 *
	 * @throws \Exception - If an error occurs while getting product attributes.
	 */
	public function retrieve_smb_domains() {
		$sandbox_enable        = $this->common_config_helper->does_test_mode() ? 'yes' : 'no';
		$config_activation_key = $this->smb_helper->fetch_activation_key( $sandbox_enable );
		if ( ! $config_activation_key ) {
			return '';
		}
		$access_token = '';
		if ( ! is_wp_error( $this->retrieve_auth_token_smb( 'yes' ) ) ) {
			$auth_result  = json_decode( $this->retrieve_auth_token_smb( 'yes' ) );
			$access_token = ( $auth_result ) ? $auth_result->access_token : '';
		}
		if ( ! $access_token ) {
			return '';
		}
		$tracking_id     = $this->common_config_helper->fetch_tracking_id();
		$channel_id      = self::CHANNEL_ID;
		$timeout         = $this->setting_config_helper->fetch_api_timeout();
		$is_sandbox      = $this->common_config_helper->does_test_mode();
		$domain_endpoint = $this->config_helper->fetch_api_endpoint( self::SANDBOX_DOMAIN_ENDPOINT, self::PRODUCTION_DOMAIN_ENDPOINT, $is_sandbox );
		$headers         = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, '' . self::HEADER_CHANNELID . ': ' . $channel_id );
		try {
			$transport = new Synchrony_Http( $domain_endpoint, $timeout, self::PROXY );
			$response  = $transport->get( $headers );
			$this->logger->debug( $response );
			$response_array = json_decode( $response, true );
			if ( ! empty( $response_array['store']['whitelistDomains'] ) ) {
				$whitelist_domain = $response_array['store']['whitelistDomains'];
				set_transient( 'synchrony_whitelist_domain', $whitelist_domain );
				return array_reverse( $whitelist_domain );
			}
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return '';
	}

	/**
	 * Update SMB domain call.
	 *
	 * @param array $domains This is for domains data.
	 *
	 * @return array|void
	 * @throws \Exception - If an Exception occurs.
	 */
	public function update_smb_domains( $domains ) {
		$auth_result  = json_decode( $this->retrieve_auth_token_smb( 'yes' ) );
		$access_token = ( $auth_result ) ? $auth_result->access_token : '';
		if ( ! $access_token || empty( $domains ) ) {
			return new \WP_Error( 'error_response_smb', 'Error in Domain update', array( 'status' => 400 ) );
		}
		$tracking_id           = $this->common_config_helper->fetch_tracking_id();
		$channel_id            = self::CHANNEL_ID;
		$timeout               = $this->setting_config_helper->fetch_api_timeout();
		$is_sandbox            = $this->common_config_helper->does_test_mode();
		$update_smb_domain_api = $this->config_helper->fetch_api_endpoint( self::SANDBOX_DOMAIN_ENDPOINT, self::PRODUCTION_DOMAIN_ENDPOINT, $is_sandbox );
		$headers               = array( '' . self::HEADER_BEARER . ' ' . $access_token, '' . self::HEADER_TRACKING_ID . ': ' . $tracking_id, 'X-SYF-Request-channelId: ' . $channel_id );
		$update_domain_data    = array(
			'store' => array(
				'whitelistDomains' => $domains,
				'platform'         => self::PLATFORM,
			),
		);
		try {
			$transport = new Synchrony_Http( $update_smb_domain_api, $timeout, self::PROXY );
			$response  = $transport->post( wp_json_encode( $update_domain_data ), 'yes', $headers, 'PUT' );
			$this->logger->debug( $response );
			$response = json_decode( $response, true );
			return $response;
		} catch ( \Exception $e ) {
			$this->logger->error( '' . self::ERROR_TXT . ': ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
		return new \WP_Error( 'error_response_smb', 'Error in Domain update', array( 'status' => 400 ) );
	}
	/**
	 * Update data in custom database table.
	 *
	 * @param array $data This is data.
	 * @param int   $environment_type This is environment type.
	 *
	 * @return bool
	 */
	public function update_customdb_reponse( $data, $environment_type ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'synchrony_partner_auth';
		$where      = array( 'env_type' => $environment_type );
		try {
			$result = $wpdb->update( $table_name, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( false !== $result ) {
				$this->update_payment_configuration_settings( $data );
				$this->logger->debug( 'Syf Auth Updated.' );
				return true;
			}
			$this->logger->debug( 'error : No rows updated' );
		} catch ( \Exception $e ) {
			$this->logger->debug( 'error: ' . $e->getMessage() . ', file path: ' . $e->getFile() . ', line no: ' . $e->getLine() . ', exception message: ' . $e->getMessage() );
		}
		return false;
	}
}
