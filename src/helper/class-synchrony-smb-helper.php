<?php
/**
 * Config Helper.
 *
 * @package Synchrony\Payments\Helper
 */

namespace Synchrony\Payments\Helper;

use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Logs\Synchrony_Logger;

/**
 * Class Synchrony_Smb_Helper.
 */
class Synchrony_Smb_Helper {

	/**
	 * ALGO
	 *
	 * @var string
	 */
	public const ALGO = 'sha256';

	/**
	 * SANDBOX_ACTIVATION_KEY
	 *
	 * @var string
	 */
	public const SANDBOX_ACTIVATION_KEY = 'synchrony_test_activation_key';

	/**
	 * PRODUCTION_ACTIVATION_KEY
	 *
	 * @var string
	 */
	public const PRODUCTION_ACTIVATION_KEY = 'synchrony_deployed_activation_key';

	/**
	 * SANDBOX_STATIC_CLIENT_ID
	 *
	 * @var string
	 */
	public const SANDBOX_STATIC_CLIENT_ID = '5K41w9ARHaGIQsEAqXQ2AiYyTryajvwS';

	/**
	 * PRODUCTION_STATIC_CLIENT_ID
	 *
	 * @var string
	 */
	public const PRODUCTION_STATIC_CLIENT_ID = 'StXI9Zjvi0Ntz9rKc3HJwTGPETahRNKq';

	/**
	 * The Common Config Helper.
	 *
	 * @var Synchrony_Common_Config_Helper
	 */
	private $common_config_helper;

	/**
	 * Install Constructor.
	 */
	public function __construct() {
		$this->common_config_helper = new Synchrony_Common_Config_Helper();
	}

	/**
	 * Retrieve generate_hash_reference_id.
	 *
	 * @param int $reference_id This is reference_id.
	 *
	 * @return string
	 */
	public function generate_hash_reference_id( $reference_id ) {
		$data = hash( self::ALGO, $reference_id, true );
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Retrieve generate_random_string.
	 *
	 * @param int $length This is length.
	 *
	 * @return string
	 */
	public function generate_random_string( $length = 64 ) {
		$characters        = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$characters_length = strlen( $characters );
		$random_string     = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ random_int( 0, $characters_length - 1 ) ];
		}
		return $random_string;
	}

	/**
	 * Retrieve Columns from smb table.
	 *
	 * @param string $table_prefix This is table_prefix.
	 * @param string $columns This is columns.
	 * @param int    $environment This is environment.
	 *
	 * @return array
	 */
	public function retrieve_columns_from_table( $table_prefix, $columns, $environment ) {
		try {
			global $wpdb;
			$table_name = $wpdb->prefix . $table_prefix;
			$query      = $wpdb->prepare( "SELECT $columns FROM $table_name WHERE env_type=%s", $environment ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		} catch ( \Exception $e ) {
			$logger = new Synchrony_Logger();
			$logger->debug( 'error: ' . $e->getMessage() . ', ' . self::FILE_PATH_TXT . ': ' . $e->getFile() . ', ' . self::LINE_NO_TXT . ': ' . $e->getLine() . ', ' . self::EXCEPTION_MSG_TXT . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Get Refresh Token Expiry Date.
	 *
	 * @param string $refresh_token_refresh_in Refresh Token Expiry.
	 *
	 * @return string
	 */
	public function retrieve_refresh_token_expiry( $refresh_token_refresh_in = null ) {
		$date               = new \DateTime();
		$current_time_stamp = $date->getTimestamp();
		return empty( $refresh_token_refresh_in ) ? $current_time_stamp : ( $current_time_stamp + $refresh_token_refresh_in );
	}

	/**
	 * Retrieve activation key.
	 *
	 * @param string $sandbox_enable This is sandbox enable flag.
	 *
	 * @return string
	 */
	public function fetch_activation_key( $sandbox_enable ) {
		$activation_key = ( $this->common_config_helper->does_test_mode() && 'yes' === $sandbox_enable ) ? $this->common_config_helper->fetch_syf_option( self::SANDBOX_ACTIVATION_KEY )
			: $this->common_config_helper->fetch_syf_option( self::PRODUCTION_ACTIVATION_KEY );
		return trim( $activation_key );
	}

	/**
	 * Retrieve Static client id.
	 *
	 * @param bool $sandbox This is test mode.
	 *
	 * @return string
	 */
	public function fetch_static_client_id( $sandbox = true ) {
		if ( $this->common_config_helper->does_test_mode() === $sandbox ) {
			$client_id = self::SANDBOX_STATIC_CLIENT_ID;
		} else {
			$client_id = self::PRODUCTION_STATIC_CLIENT_ID;
		}
		return $client_id;
	}
}
