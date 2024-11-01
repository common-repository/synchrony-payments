<?php
/**
 * Tracker Connection Admin
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Client;

/**
 * Class Synchrony_Tracker_Connection.
 */
class Synchrony_Tracker_Connection {

	/**
	 * MODULE_NAME
	 *
	 * @var string
	 */
	public const MODULE_NAME = 'Synchrony Payments';

	/**
	 * Tracker_Connection constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'does_module_enable' ) );
	}
	/**
	 * Call Module Tracking client API
	 *
	 * @param int   $flag This is flag.
	 * @param array $config This is config details.
	 *
	 * @return bool
	 */
	public function retrieve_tracker_call( int $flag, array $config ) {
		$module_data = $this->does_module_data( $flag, $config );
		$client      = new Synchrony_Client();
		$client->module_tracking( $module_data );
		return true;
	}

	/**
	 * Get_domain
	 *
	 * @param string $url This is url.
	 *
	 * @return string
	 */
	public function retrieve_domain( $url ) {
		$pieces = wp_parse_url( $url );
		return isset( $pieces['host'] ) ? $pieces['host'] : $pieces['path'];
	}

	/**
	 * If Module Enabled
	 *
	 * @return bool
	 */
	public function does_module_enable() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'synchrony-payments/class-synchrony-payment.php' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get_module_data
	 *
	 * @param int   $flag This is flag.
	 * @param array $config This is config details.
	 *
	 * @return array
	 */
	public function does_module_data( int $flag, array $config ) {
		$common_config_helper  = new Synchrony_Common_Config_Helper();
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$date                  = new \DateTime();
		$date->setTimezone( new \DateTimeZone( 'US/Eastern' ) );
		$currenttime = $date->format( 'Y-m-d H:i:s' );
		if ( $setting_config_helper->synchrony_plugin_active() ) {
			$module_status = 'Enabled';
		} else {
			$module_status = 'Disabled';
		}

		$current_domain   = $this->retrieve_domain( get_site_url() );
		$wc_version       = WC_VERSION;
		$module_name      = preg_replace( '/\s+/', '', ucwords( str_replace( '_', ' ', self::MODULE_NAME ) ) );
		$language_version = phpversion();
		return array(
			'moduleName'                    => substr( $module_name, 0, 40 ),
			'platform'                      => $common_config_helper->fetch_platform(),
			'installationStatus'            => $this->does_module_enable(),
			'moduleStatus'                  => $module_status,
			'url'                           => substr( get_site_url(), 0, 180 ),
			'domain'                        => substr( $current_domain, 0, 180 ),
			'platformVersion'               => substr( $wc_version, 0, 40 ),
			'moduleVersion'                 => substr( $common_config_helper->fetch_app_version(), 0, 10 ),
			'language'                      => 'PHP',
			'languageVersion'               => substr( $language_version, 0, 40 ),
			'systemConfigurationChangeDate' => substr( $currenttime, 0, 10 ),
			'configChangeDetails'           => ( 1 === $flag ) ? $config : null,
		);
	}
}
