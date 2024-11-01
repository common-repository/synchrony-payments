<?php
/**
 * Logger functionality
 *
 * @package Synchrony\Payments\Logs
 */

namespace Synchrony\Payments\Logs;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;


/**
 * Class Synchrony_Logger
 */
class Synchrony_Logger {

	/**
	 * Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;
	/**
	 * Logger constructore
	 */
	public function __construct() {
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
	}

	/**
	 * Info Log
	 *
	 * @param string $message This is for message.
	 *
	 * @return void
	 */
	public function info( $message ) {
		$is_debug_mode = $this->setting_config_helper->synchrony_logger( 'debug' );
		if ( $is_debug_mode ) {
			$logger = wc_get_logger();
			$logger->info( 'Info Log: ' . $message );
		}
	}
	/**
	 * Error log
	 *
	 * @param string $message This is for message.
	 *
	 * @return void
	 */
	public function error( $message ) {
		$is_debug_mode                 = $this->setting_config_helper->synchrony_logger( 'debug' );
		$is_sending_log_into_synchrony = $this->setting_config_helper->synchrony_logger( 'logging_type' );
		$ip                            = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( $is_debug_mode ) {
			$logger           = wc_get_logger();
			$logerror_message = 'Warning Log: ' . $message;
			$logger->error( $logerror_message );
			if ( $is_sending_log_into_synchrony ) {
				$data   = array(
					'message' => $logerror_message,
					'logType' => 'ERROR',
					'ip'      => $ip,
				);
				$client = new Synchrony_Client();
				$client->send_logs( $data );
			}
		}
	}
	/**
	 * Debug log
	 *
	 * @param string $message This is for message.
	 *
	 * @return void
	 */
	public function debug( $message ) {
		$is_debug_mode = $this->setting_config_helper->synchrony_logger( 'debug' );
		if ( $is_debug_mode ) {
			$logger = wc_get_logger();
			$logger->debug( 'Debug Log: ' . $message );
		}
	}
}
