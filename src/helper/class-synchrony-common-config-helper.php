<?php
/**
 * Config Helper.
 *
 * @package Synchrony\Payments\Helper
 */

namespace Synchrony\Payments\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class Synchrony_Common_Config_Helper.
 */
class Synchrony_Common_Config_Helper {

	/**
	 * SYNCHRONY_PAYMENT_OPTION_KEY
	 *
	 * @var string
	 */
	public const SYNCHRONY_PAYMENT_OPTION_KEY = 'woocommerce_synchrony-unifi-payments_settings';
	/**
	 * SANDBOX
	 *
	 * @var string
	 */
	public const SANDBOX = 'synchrony_test'; // yes -Sandbox, No - Live.
	/**
	 * TEMPLATE_TITLE
	 *
	 * @var string
	 */
	public const TEMPLATE_TITLE = 'Synchrony Payment';
	/**
	 * SYNCHRONY_REQUEST_CHANNEL_ID
	 *
	 * @var string
	 */
	public const SYNCHRONY_REQUEST_CHANNEL_ID = 'SYF';
	/**
	 * SYNCHRONY_CUSTOM_CSS
	 *
	 * @var string
	 */
	public const SYNCHRONY_CUSTOM_CSS = 'syf_custom_css';

	/**
	 * SANDBOX_ACTIVATION_KEY
	 *
	 * @var string
	 */
	public const SANDBOX_ACTIVATION_KEY = 'test_enable_activation';

	/**
	 * PRODUCTION_ACTIVATION_KEY
	 *
	 * @var string
	 */
	public const PRODUCTION_ACTIVATION_KEY = 'deployed_enable_activation';

	/**
	 * SANDBOX_ACTIVATION_KEY_FLAG
	 *
	 * @var string
	 */
	public const SANDBOX_ACTIVATION_KEY_FLAG = 'yes';

	/**
	 * PRODUCTION_ACTIVATION_KEY_FLAG
	 *
	 * @var string
	 */
	public const PRODUCTION_ACTIVATION_KEY_FLAG = 'yes';

	/**
	 * App Version.
	 *
	 * @var string
	 */
	public $app_version;

	/**
	 * Platform.
	 *
	 * @var string
	 */
	private $platform;

	/**
	 * Constructor for Config_Helper.
	 */
	public function __construct() {
		$this->app_version = '1.0.4';
		$this->platform    = 'woocommerce';
	}

	/**
	 * Retrieve synchrony_test flag
	 *
	 * @return bool
	 */
	public function does_test_mode() {
		return ( $this->fetch_syf_option( self::SANDBOX ) === 'no' ) ? false : true;
	}

	/**
	 * Retrieve plugin setting option by key.
	 *
	 * @param string|array $key  This is for Key.
	 *
	 * @return string|array
	 */
	public function fetch_syf_option( $key ) {
		$options = get_option( self::SYNCHRONY_PAYMENT_OPTION_KEY );
		return ! empty( $options[ $key ] ) ? $options[ $key ] : '';
	}

	/**
	 * Retrieve all plugin setting option.
	 *
	 * @return string|array
	 */
	public function fetch_synchrony_config_option() {
		return get_option( self::SYNCHRONY_PAYMENT_OPTION_KEY );
	}

	/**
	 * Retrieve platform
	 *
	 * @return string
	 */
	public function fetch_platform() {
		return $this->platform;
	}

	/**
	 * Retrieve app version
	 *
	 * @return string
	 */
	public function fetch_app_version() {
		return $this->app_version;
	}
	/**
	 * Retrieve Template
	 */
	public function synchrony_template() {
		return self::TEMPLATE_TITLE;
	}
	/**
	 * Retrieve Reference Id
	 *
	 * @return string
	 */
	public function generate_reference_id() {
		$str_result = '0123456789abcdefghijklmnopqrstuvwxyz';
		return substr( str_shuffle( $str_result ), 0, 8 ) . '-' . substr( str_shuffle( $str_result ), 0, 7 ) . '-' . substr( str_shuffle( $str_result ), 0, 6 ) . '-' . substr( str_shuffle( $str_result ), 0, 5 );
	}
	/**
	 * Retrieve loader image.
	 */
	public function fetch_loader_image() {
		return plugin_dir_url( __DIR__ ) . '../assets/images/loader.gif';
	}
	/**
	 * Retrieve SYF Channel Id.
	 *
	 * @return string|int
	 */
	public function fetch_synchrony_channel_id() {
		return self::SYNCHRONY_REQUEST_CHANNEL_ID;
	}
	/**
	 * Retrieve Image from Upload Directory.
	 */
	public function fetch_synchrony_logo() {
		return plugin_dir_url( __DIR__ ) . '../assets/images/synchrony_logo.png';
	}
	/**
	 * Retrieve Tracking id for API Call.
	 *
	 * @return int
	 */
	public function fetch_tracking_id() {
		return random_int( 100000000, 999999999 );
	}

	/**
	 * Retrieve custom css.
	 *
	 * @return text
	 */
	public function fetch_syf_custom_css() {
		return $this->fetch_syf_option( self::SYNCHRONY_CUSTOM_CSS );
	}

	/**
	 * Retrieve sandbox activation key flag.
	 *
	 * @return bool
	 */
	public function does_sandbox_activation() {
		return ( $this->fetch_syf_option( self::SANDBOX_ACTIVATION_KEY ) === 'yes' ) ? true : false;
	}

	/**
	 * Retrieve production activation key flag.
	 *
	 * @return bool
	 */
	public function does_production_activation() {
		return ( $this->fetch_syf_option( self::PRODUCTION_ACTIVATION_KEY ) === 'yes' ) ? true : false;
	}

	/**
	 * Retrieve activation enable flag.
	 *
	 * @return string
	 */
	public function fetch_activation_enable_flag() {
		$is_synchrony_test = $this->does_test_mode();
		if ( $is_synchrony_test ) {
			$activation_flag = ( '' !== $this->fetch_syf_option( self::SANDBOX_ACTIVATION_KEY ) ? $this->fetch_syf_option( self::SANDBOX_ACTIVATION_KEY ) : self::SANDBOX_ACTIVATION_KEY_FLAG );
		} else {
			$activation_flag = ( '' !== $this->fetch_syf_option( self::PRODUCTION_ACTIVATION_KEY ) ? $this->fetch_syf_option( self::PRODUCTION_ACTIVATION_KEY ) : self::PRODUCTION_ACTIVATION_KEY_FLAG );
		}
		return $activation_flag;
	}
	/**
	 * Configuration test activation key field value.
	 *
	 * @param array $request_data This is post data.
	 *
	 * @return string
	 */
	public function config_test_activation_key( $request_data ) {
		return ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_test_activation_key'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_test_activation_key'] : '';
	}

	/**
	 * Configuration deployed activation key field value.
	 *
	 * @param array $request_data This is post data.
	 *
	 * @return string
	 */
	public function config_deployed_activation_key( $request_data ) {
		return ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_deployed_activation_key'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_deployed_activation_key'] : '';
	}

	/**
	 * Configuration test client id field value.
	 *
	 * @param array $request_data This is post data.
	 *
	 * @return string
	 */
	public function config_test_client_id( $request_data ) {
		return ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_client_id'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_client_id'] : '';
	}

	/**
	 * Configuration deployed client id field value.
	 *
	 * @param array $request_data This is post data.
	 *
	 * @return string
	 */
	public function config_deployed_client_id( $request_data ) {
		return ( isset( $request_data['woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_client_id'] ) ) ? $request_data['woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_client_id'] : '';
	}
}
