<?php
/**
 * Config Helper.
 *
 * @package Synchrony\Payments\Helper
 */

namespace Synchrony\Payments\Helper;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;

/**
 * Class Synchrony_Config_Helper.
 */
class Synchrony_Config_Helper {

	/**
	 * PAYMENT_ACTION
	 *
	 * @var string
	 */
	public const PAYMENT_ACTION = 'payment_action';
	/**
	 * SYNCHRONY_PAGE_TITLE
	 *
	 * @var string
	 */
	public const SYNCHRONY_PAGE_TITLE = 'title';
	/**
	 * SYNCHRONY_SANDBOX_AK
	 *
	 * @var string
	 */
	public const SYNCHRONY_SANDBOX_AK = 'K7oqU3GGGPw5nUFXdsSZ92eAltA5JiL4';
	/**
	 * SYNCHRONY_LIVE_AK
	 *
	 * @var string
	 */
	public const SYNCHRONY_LIVE_AK = 's4KAddCjKWGNDomH88Dz4IwwOdnbVM43';
	/**
	 * POP_UP
	 *
	 * @var string
	 */
	public const POP_UP = 'pop_up';
	/**
	 * Production Authentication API.
	 *
	 * @var string
	 */
	private $synchrony_deployed_authentication_api_endpoint;
	/**
	 * API token for synchrony_deployed environment.
	 *
	 * @var string|int
	 */
	private $synchrony_deployed_token_api_endpoint;
	/**
	 * API endpoint for synchrony_deployed environment.
	 *
	 * @var string
	 */
	private $synchrony_deployed_unifi_script_endpoint;
	/**
	 * API URL for synchrony_deployed logger.
	 *
	 * @var string
	 */
	private $synchrony_logger_api_endpoint;
	/**
	 * API endpoint for synchrony_deployed monitoring.
	 *
	 * @var string
	 */
	private $synchrony_deployed_moduletracking_api_endpoint;
	/**
	 * Production  Transact API.
	 *
	 * @var string
	 */
	private $synchrony_deployed_transactapi_api_endpoint;
	/**
	 * API endpoint for synchrony_deployed promo tags.
	 *
	 * @var string
	 */
	private $synchrony_deployed_promo_tag_endpoint;
	/**
	 * Production Determination API.
	 *
	 * @var string
	 */
	private $synchrony_deployed_promo_tag_determination_endpoint;
	/**
	 * Production MPP Banner API.
	 *
	 * @var string
	 */
	private $synchrony_deployed_mpp_banner_api_endpoint;
	/**
	 * Sandbox Authentication API.
	 *
	 * @var string
	 */
	private $synchrony_test_authentication_api_endpoint;
	/**
	 * Sandbox Token API.
	 *
	 * @var string|int
	 */
	private $synchrony_test_token_api_endpoint;
	/**
	 * Sandbox Synchrony Endpint API.
	 *
	 * @var string
	 */
	private $synchrony_test_unifi_script_endpoint;
	/**
	 * Sandbox logger API.
	 *
	 * @var string
	 */
	private $synchrony_test_logger_api_endpoint;
	/**
	 * The Sandbox Monitoring API.
	 *
	 * @var string
	 */
	private $synchrony_test_monitoring_api_endpoint;
	/**
	 * The API call for  Sandbox Transact API.
	 *
	 * @var string
	 */
	private $synchrony_test_transactapi_api_endpoint;
	/**
	 * The API call for Sandbox promotag.
	 *
	 * @var string|int|null
	 */
	private $synchrony_test_promo_tag_endpoint;
	/**
	 * The Sandbox determination API instance.
	 *
	 * @var string
	 */
	private $synchrony_test_promo_tag_determination_endpoint;
	/**
	 * The Sandbox MPP Banner API instance.
	 *
	 * @var string
	 */
	private $synchrony_test_mpp_banner_api_endpoint;

	/**
	 * Live Find Status API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_deployed_findstatus_api_endpoint;

	/**
	 * Deployed Partner Activate API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_deployed_partner_activate_api_endpoint;

	/**
	 * Deployed SMB Domain API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_deployed_smb_domain_api_endpoint;

	/**
	 * Deployed Client ID rotation API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_deployed_client_id_rotation_api_endpoint;

	/**
	 * Sandbox find status API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_test_findstatus_api_endpoint;

	/**
	 * Sandbox Partner Activate API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_test_partner_activate_api_endpoint;

	/**
	 * Sandbox SMB Domain API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_test_smb_domain_api_endpoint;

	/**
	 * Sandbox Client ID Rotation API Endpoint.
	 *
	 * @var string
	 */
	private $synchrony_test_client_id_rotation_api_endpoint;

	/**
	 * The Common Config Helper.
	 *
	 * @var Synchrony_Common_Config_Helper
	 */
	private $common_config_helper;

	/**
	 * Constructor for Config_Helper.
	 */
	public function __construct() {
		$this->common_config_helper                           = new Synchrony_Common_Config_Helper();
		$this->synchrony_deployed_authentication_api_endpoint = 'https://api.syf.com/v1/oauth2/token';
		$this->synchrony_deployed_token_api_endpoint          = 'https://api.syf.com/v1/dpos/utility/token';
		$this->synchrony_deployed_unifi_script_endpoint       = 'https://pdpone.syfpos.com/';
		$this->synchrony_logger_api_endpoint                  = 'https://api.syf.com/v1/credit/ecarts/extension/logs';
		$this->synchrony_deployed_moduletracking_api_endpoint = 'https://api.syf.com/v1/credit/ecarts/extension';
		$this->synchrony_deployed_transactapi_api_endpoint    = 'https://api.syf.com/v1/credit/ecarts/transact';

		$this->synchrony_deployed_findstatus_api_endpoint       = 'https://api.syf.com/v1/dpos/utility/lookup/transaction/';
		$this->synchrony_deployed_partner_activate_api_endpoint = 'https://api.syf.com/v1/credit/ecart/partner/activate';
		// promotag configurations.
		$this->synchrony_deployed_promo_tag_endpoint               = 'https://shop.mysynchrony.com/tag/';
		$this->synchrony_deployed_promo_tag_determination_endpoint = 'https://api.syf.com/v1/credit/offers/dpos';
		$this->synchrony_deployed_banner_mpp_endpoint              = 'https://shop.mysynchrony.com/tag/';
		$this->synchrony_deployed_smb_domain_api_endpoint          = 'https://api.syf.com/v1/credit/ecart/partner/details';
		$this->synchrony_deployed_client_id_rotation_api_endpoint  = 'https://api.syf.com/v1/credit/ecart/partner/renew';

		$this->synchrony_test_authentication_api_endpoint = 'https://api-stg.syf.com/v1/oauth2/token';
		$this->synchrony_test_token_api_endpoint          = 'https://api-stg.syf.com/v1/dpos/utility/token';
		$this->synchrony_test_unifi_script_endpoint       = 'https://spdpone.syfpos.com/';
		$this->synchrony_test_logger_api_endpoint         = 'https://api-stg.syf.com/v1/credit/ecarts/extension/logs';
		$this->synchrony_test_moduletracking_api_endpoint = 'https://api-stg.syf.com/v1/credit/ecarts/extension';
		$this->synchrony_test_transactapi_api_endpoint    = 'https://api-stg.syf.com/v1/credit/ecarts/transact';
		$this->synchrony_test_findstatus_api_endpoint     = 'https://api-stg.syf.com/v1/dpos/utility/lookup/transaction/';
		// promotag configurations.
		$this->synchrony_test_promo_tag_endpoint               = 'https://ushop.mysynchrony.com/tag/';
		$this->synchrony_test_promo_tag_determination_endpoint = 'https://api-stg.syf.com/v1/credit/offers/dpos';
		$this->synchrony_test_banner_mpp_endpoint              = 'https://ushop.mysynchrony.com/tag/';
		$this->synchrony_test_partner_activate_api_endpoint    = 'https://api-stg.syf.com/v1/credit/ecart/partner/activate';
		$this->synchrony_test_smb_domain_api_endpoint          = 'https://api-stg.syf.com/v1/credit/ecart/partner/details';
		$this->synchrony_test_client_id_rotation_api_endpoint  = 'https://api-stg.syf.com/v1/credit/ecart/partner/renew';
	}

	/**
	 * Retrieve Payment Action
	 *
	 * @return string|array
	 */
	public function fetch_config_payment_action() {
		return $this->common_config_helper->fetch_syf_option( self::PAYMENT_ACTION );
	}


	/**
	 * Retrieve option value.
	 *
	 * @param string $key This is for Key.
	 * @return string
	 */
	public function fetch_option_value( $key ) {
		return get_option( $key );
	}

	/**
	 * Add Synchrony API url.
	 *
	 * @return void
	 */
	public function update_unifi_api_url() {
		$synchrony_deployed_authentication     = $this->fetch_api_endpoint( 'synchrony_test_authentication_api_endpoint', 'synchrony_deployed_authentication_api_endpoint', false );
		$synchrony_test_authentication         = $this->fetch_api_endpoint( 'synchrony_test_authentication_api_endpoint', 'synchrony_deployed_authentication_api_endpoint' );
		$synchrony_deployed_token              = $this->fetch_api_endpoint( 'synchrony_test_token_api_endpoint', 'synchrony_deployed_token_api_endpoint', false );
		$synchrony_test_token                  = $this->fetch_api_endpoint( 'synchrony_test_token_api_endpoint', 'synchrony_deployed_token_api_endpoint' );
		$synchrony_deployed_unifi_endpoint     = $this->fetch_api_endpoint( 'synchrony_test_unifi_script_endpoint', 'synchrony_deployed_unifi_script_endpoint', false );
		$synchrony_test_unifi_endpoint         = $this->fetch_api_endpoint( 'synchrony_test_unifi_script_endpoint', 'synchrony_deployed_unifi_script_endpoint' );
		$synchrony_deployed_transact           = $this->fetch_api_endpoint( 'synchrony_test_transactapi_api_endpoint', 'synchrony_deployed_transactapi_api_endpoint', false );
		$synchrony_test_transact               = $this->fetch_api_endpoint( 'synchrony_test_transactapi_api_endpoint', 'synchrony_deployed_transactapi_api_endpoint' );
		$synchrony_deployed_logger             = $this->fetch_api_endpoint( 'synchrony_test_logger_api_endpoint', 'synchrony_logger_api_endpoint', false );
		$synchrony_test_logger                 = $this->fetch_api_endpoint( 'synchrony_test_logger_api_endpoint', 'synchrony_logger_api_endpoint' );
		$synchrony_deployed_monitoring         = $this->fetch_api_endpoint( 'synchrony_test_moduletracking_api_endpoint', 'synchrony_deployed_moduletracking_api_endpoint', false );
		$synchrony_test_monitoring             = $this->fetch_api_endpoint( 'synchrony_test_moduletracking_api_endpoint', 'synchrony_deployed_moduletracking_api_endpoint' );
		$synchrony_deployed_findstatus         = $this->fetch_api_endpoint( 'synchrony_test_findstatus_api_endpoint', 'synchrony_deployed_findstatus_api_endpoint', false );
		$synchrony_test_findstatus             = $this->fetch_api_endpoint( 'synchrony_test_findstatus_api_endpoint', 'synchrony_deployed_findstatus_api_endpoint' );
		$synchrony_deployed_promotag           = $this->fetch_api_endpoint( 'synchrony_test_promo_tag_endpoint', 'synchrony_deployed_promo_tag_endpoint', false );
		$synchrony_test_promotag               = $this->fetch_api_endpoint( 'synchrony_test_promo_tag_endpoint', 'synchrony_deployed_promo_tag_endpoint' );
		$synchrony_deployed_determination      = $this->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', false );
		$synchrony_test_determination          = $this->fetch_api_endpoint( 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint' );
		$synchrony_deployed_mpp_banner         = $this->fetch_api_endpoint( 'synchrony_test_banner_mpp_endpoint', 'synchrony_deployed_banner_mpp_endpoint', false );
		$synchrony_test_mpp_banner             = $this->fetch_api_endpoint( 'synchrony_test_banner_mpp_endpoint', 'synchrony_deployed_banner_mpp_endpoint' );
		$synchrony_deployed_partner_activate   = $this->fetch_api_endpoint( 'synchrony_test_partner_activate_api_endpoint', 'synchrony_deployed_partner_activate_api_endpoint', false );
		$synchrony_test_partner_activate       = $this->fetch_api_endpoint( 'synchrony_test_partner_activate_api_endpoint', 'synchrony_deployed_partner_activate_api_endpoint' );
		$synchrony_deployed_smb_domain         = $this->fetch_api_endpoint( 'synchrony_test_smb_domain_api_endpoint', 'synchrony_deployed_smb_domain_api_endpoint', false );
		$synchrony_test_smb_domain             = $this->fetch_api_endpoint( 'synchrony_test_smb_domain_api_endpoint', 'synchrony_deployed_smb_domain_api_endpoint' );
		$synchrony_deployed_client_id_rotation = $this->fetch_api_endpoint( 'synchrony_test_client_id_rotation_api_endpoint', 'synchrony_deployed_client_id_rotation_api_endpoint', false );
		$synchrony_test_client_id_rotation     = $this->fetch_api_endpoint( 'synchrony_test_client_id_rotation_api_endpoint', 'synchrony_deployed_client_id_rotation_api_endpoint' );
		update_option( 'synchrony_deployed_authentication_api_endpoint', $synchrony_deployed_authentication );
		update_option( 'synchrony_test_authentication_api_endpoint', $synchrony_test_authentication );
		update_option( 'synchrony_deployed_token_api_endpoint', $synchrony_deployed_token );
		update_option( 'synchrony_test_token_api_endpoint', $synchrony_test_token );
		update_option( 'synchrony_deployed_unifi_script_endpoint', $synchrony_deployed_unifi_endpoint );
		update_option( 'synchrony_test_unifi_script_endpoint', $synchrony_test_unifi_endpoint );
		update_option( 'synchrony_deployed_transactapi_api_endpoint', $synchrony_deployed_transact );
		update_option( 'synchrony_test_transactapi_api_endpoint', $synchrony_test_transact );
		update_option( 'synchrony_logger_api_endpoint', $synchrony_deployed_logger );
		update_option( 'synchrony_test_logger_api_endpoint', $synchrony_test_logger );
		update_option( 'synchrony_deployed_moduletracking_api_endpoint', $synchrony_deployed_monitoring );
		update_option( 'synchrony_test_moduletracking_api_endpoint', $synchrony_test_monitoring );
		update_option( 'synchrony_deployed_findstatus_api_endpoint', $synchrony_deployed_findstatus );
		update_option( 'synchrony_test_findstatus_api_endpoint', $synchrony_test_findstatus );
		update_option( 'synchrony_deployed_promo_tag_endpoint', $synchrony_deployed_promotag );
		update_option( 'synchrony_test_promo_tag_endpoint', $synchrony_test_promotag );
		update_option( 'synchrony_deployed_promo_tag_determination_endpoint', $synchrony_deployed_determination );
		update_option( 'synchrony_test_promo_tag_determination_endpoint', $synchrony_test_determination );
		update_option( 'synchrony_deployed_banner_mpp_endpoint', $synchrony_deployed_mpp_banner );
		update_option( 'synchrony_test_banner_mpp_endpoint', $synchrony_test_mpp_banner );
		update_option( 'synchrony_deployed_partner_activate_api_endpoint', $synchrony_deployed_partner_activate );
		update_option( 'synchrony_test_partner_activate_api_endpoint', $synchrony_test_partner_activate );
		update_option( 'synchrony_deployed_smb_domain_api_endpoint', $synchrony_deployed_smb_domain );
		update_option( 'synchrony_test_smb_domain_api_endpoint', $synchrony_test_smb_domain );
		update_option( 'synchrony_deployed_client_id_rotation_api_endpoint', $synchrony_deployed_client_id_rotation );
		update_option( 'synchrony_test_client_id_rotation_api_endpoint', $synchrony_test_client_id_rotation );
	}

	/**
	 * Retrieve Tracker Endpoint.
	 *
	 * @param string|int $partner_id This is for partner Id.
	 * @return string|int
	 */
	public function fetch_tracker_endpoint( $partner_id ) {
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		$tracking_path          = $this->fetch_api_endpoint( 'synchrony_test_moduletracking_api_endpoint', 'synchrony_deployed_moduletracking_api_endpoint', $is_synchrony_test_mode );
		return $partner_id ? $tracking_path . '/configuration' : $tracking_path . '/installation';
	}

	/**
	 * Retrieve Capture SYF API Default Key from config.
	 *
	 * @return string
	 */
	public function fetch_synchrony_api_key() {
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		return $is_synchrony_test_mode ? self::SYNCHRONY_SANDBOX_AK : self::SYNCHRONY_LIVE_AK;
	}
	/**
	 * Retrieve API endpoint list.
	 *
	 * @return string
	 */
	public function fetch_api_endpoint_list() {
		$setting_options = array(
			'synchrony_deployed_authentication_api_endpoint',
			'synchrony_test_authentication_api_endpoint',
			'synchrony_deployed_token_api_endpoint',
			'synchrony_test_token_api_endpoint',
			'synchrony_deployed_unifi_script_endpoint',
			'synchrony_test_unifi_script_endpoint',
			'synchrony_deployed_transactapi_api_endpoint',
			'synchrony_test_transactapi_api_endpoint',
			'synchrony_logger_api_endpoint',
			'synchrony_test_logger_api_endpoint',
			'synchrony_deployed_moduletracking_api_endpoint',
			'synchrony_test_moduletracking_api_endpoint',
			'synchrony_deployed_findstatus_api_endpoint',
			'synchrony_test_findstatus_api_endpoint',
			'synchrony_test_promo_tag_endpoint',
			'synchrony_deployed_promo_tag_endpoint',
			'synchrony_test_promo_tag_determination_endpoint',
			'synchrony_deployed_promo_tag_determination_endpoint',
			'synchrony_test_banner_mpp_endpoint',
			'synchrony_deployed_banner_mpp_endpoint',
			'synchrony_test_partner_activate_endpoint',
			'synchrony_deployed_partner_activate_api_endpoint',
			'synchrony_test_smb_domain_api_endpoint',
			'synchrony_deployed_smb_domain_api_endpoint',
			'synchrony_test_client_id_rotation_api_endpoint',
			'synchrony_deployed_client_id_rotation_api_endpoint',
		);
		$endpoint_list   = array();
		foreach ( $setting_options as $endpoint ) {
			$endpoint_val               = get_option( $endpoint );
			$endpoint_list[ $endpoint ] = ( $endpoint_val ) ? $endpoint_val : '';
		}
		return $endpoint_list;
	}

	/**
	 * Retrieve checkout modal presentation.
	 *
	 * @return int
	 */
	public function fetch_pop_up() {
		return (int) $this->common_config_helper->fetch_syf_option( self::POP_UP );
	}

	/**
	 * Retrieve API endpoint.
	 *
	 * @param string $sandbox_endpoint This is for sandbox endpoint.
	 * @param string $production_endpoint This is for production endpoint.
	 * @param bool   $sandbox This is test mode.
	 *
	 * @return string
	 */
	public function fetch_api_endpoint( $sandbox_endpoint, $production_endpoint, $sandbox = true ) {
		if ( $sandbox ) {
			$static_endpoint = $this->$sandbox_endpoint;
			$endpoint        = $this->fetch_endpoints( $sandbox_endpoint, $static_endpoint );
		} else {
			$static_endpoint = $this->$production_endpoint;
			$endpoint        = $this->fetch_endpoints( $production_endpoint, $static_endpoint );
		}
		return trim( $endpoint );
	}
	/**
	 * Retrieve Smb partner activate and smb domain endpoint.
	 *
	 * @param string $endpoint This is for endpoint.
	 * @param string $static_endpoint This is for endpoint variable.
	 *
	 * @return string
	 */
	public function fetch_endpoints( $endpoint, $static_endpoint ) {
		return ( $this->fetch_option_value( $endpoint ) ? $this->fetch_option_value( $endpoint ) : $static_endpoint );
	}
}
