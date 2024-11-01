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
 * Class Synchrony_Setting_Config_Helper.
 */
class Synchrony_Setting_Config_Helper {

	/**
	 * PLUGIN_STATUS
	 *
	 * @var string
	 */
	public const PLUGIN_STATUS = 'enabled'; // yes - Enable, No - Disable.
	/**
	 * API_TIMEOUT_KEY
	 *
	 * @var string
	 */
	public const API_TIMEOUT_KEY = 'time_out';
	/**
	 * PROD_PARTNER_ID
	 *
	 * @var string
	 */
	public const PROD_PARTNER_ID = 'synchrony_deployed_digitalbuy_api_partner_id';
	/**
	 * SANDBOX_PARTNER_ID
	 *
	 * @var string
	 */
	public const SANDBOX_PARTNER_ID = 'synchrony_test_digitalbuy_api_partner_id';

	/**
	 * PROD_SMB_PARTNER_ID
	 *
	 * @var string
	 */
	public const PROD_SMB_PARTNER_ID = 'synchrony_deployed_digitalbuy_api_smb_partner_id';

	/**
	 * SANDBOX_CHILD_MERCHANT_ID
	 *
	 * @var string
	 */
	public const SANDBOX_CHILD_MERCHANT_ID = 'synchrony_test_digitalbuy_api_child_merchant_id';

	/**
	 * PROD_CHILD_MERCHANT_ID
	 *
	 * @var string
	 */
	public const PROD_CHILD_MERCHANT_ID = 'synchrony_deployed_digitalbuy_api_child_merchant_id';

	/**
	 * SANDBOX_CHILD_PARTNER_ID
	 *
	 * @var string
	 */
	public const SANDBOX_CHILD_PARTNER_ID = 'synchrony_test_digitalbuy_api_child_partner_code';

	/**
	 * PROD_CHILD_PARTNER_ID
	 *
	 * @var string
	 */
	public const PROD_CHILD_PARTNER_ID = 'synchrony_deployed_digitalbuy_api_child_partner_code';

	/**
	 * SANDBOX_SMB_PARTNER_ID
	 *
	 * @var string
	 */
	public const SANDBOX_SMB_PARTNER_ID = 'synchrony_test_digitalbuy_api_smb_partner_id';


	/**
	 * PROD_CLIENT_ID
	 *
	 * @var string
	 */
	public const PROD_CLIENT_ID = 'synchrony_deployed_digitalbuy_api_client_id';

	/**
	 * SANDBOX_CLIENT_ID
	 *
	 * @var string
	 */
	public const SANDBOX_CLIENT_ID = 'synchrony_test_digitalbuy_api_client_id';

	/**
	 * PROD_SMB_CLIENT_ID
	 *
	 * @var string
	 */
	public const PROD_SMB_CLIENT_ID = 'synchrony_deployed_digitalbuy_api_smb_client_id';

	/**
	 * SANDBOX_SMB_CLIENT_ID
	 *
	 * @var string
	 */
	public const SANDBOX_SMB_CLIENT_ID = 'synchrony_test_digitalbuy_api_smb_client_id';


	/**
	 * PROD_CLIENT_SECRET_ID
	 *
	 * @var string
	 */
	public const PROD_CLIENT_SECRET_ID = 'synchrony_deployed_digitalbuy_api_client_secret';
	/**
	 * SANDBOX_CLIENT_SECRET_ID
	 *
	 * @var string
	 */
	public const SANDBOX_CLIENT_SECRET_ID = 'synchrony_test_digitalbuy_api_client_secret';
	/**
	 * SHOW_UNIFY_WIDGET
	 *
	 * @var string
	 */
	public const SHOW_UNIFY_WIDGET = 'show_unify_widget';
	/**
	 * SYNCHRONY_PROMOTIONS_KEY
	 *
	 * @var string
	 */
	public const SYNCHRONY_PROMOTIONS_KEY = 'tag_rules_option';
	/**
	 * ADDRESS_TYPE
	 *
	 * @var string
	 */
	public const ADDRESS_TYPE = 'address_type_to_pass';
	/**
	 * CACHE_TIME_OUT
	 *
	 * @var string
	 */
	public const CACHE_TIME_OUT = 'cache_time_out';
	/**
	 * WIDGET_DISPLAY_APPROACH
	 *
	 * @var string
	 */
	public const WIDGET_DISPLAY_APPROACH = 'widget_display_approach';

	/**
	 * CUSTOM_PARENT_CLASS_PRICE_PDP
	 *
	 * @var string
	 */
	public const CUSTOM_PARENT_CLASS_PRICE_PDP = 'parent_price_class_selector_pdp';

	/**
	 * CUSTOM_CLASS_PRICE_PDP
	 *
	 * @var string
	 */
	public const CUSTOM_CLASS_PRICE_PDP = 'price_class_selector_pdp';
	/**
	 * The Timeout value.
	 *
	 * @var int
	 */
	private $timeout;
	/**
	 * ENABLE_SAVE_CARD
	 *
	 * @var string
	 */
	public const ENABLE_SAVE_CARD = 'enable_savelater';


	/**
	 * PDP_WIDGET_LOCATION
	 *
	 * @var string
	 */
	public const PDP_WIDGET_LOCATION = 'widget_location_on_pdp';

	/**
	 * PDP_WIDGET_LOCATION
	 *
	 * @var string
	 */
	public const DEFAULT_PDP_WIDGET_LOCATION = 'woocommerce_single_product_summary';

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
		$this->timeout              = 30;
	}

	/**
	 * Retrieve plugin active flag
	 *
	 * @return bool
	 */
	public function synchrony_plugin_active() {
		return ( $this->common_config_helper->fetch_syf_option( self::PLUGIN_STATUS ) === 'yes' ) ? true : false;
	}

	/**
	 * Retrieve API Timeout
	 *
	 * @return int
	 */
	public function fetch_api_timeout() {
		return ( $this->common_config_helper->fetch_syf_option( self::API_TIMEOUT_KEY ) ) ? $this->common_config_helper->fetch_syf_option( self::API_TIMEOUT_KEY ) : $this->timeout;
	}

	/**
	 * Retrieve Partner ID
	 *
	 * @return string|int
	 */
	public function fetch_partner_id() {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		if ( 'yes' === $is_activation_enable ) {
			$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_SMB_PARTNER_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_SMB_PARTNER_ID );
			return trim( $id );
		}
		$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_PARTNER_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_PARTNER_ID );
		return trim( $id );
	}

	/**
	 * Child Merchant ID
	 *
	 * @return string|int
	 */
	public function fetch_child_merchant_id() {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		if ( 'yes' === $is_activation_enable ) {
			return '';
		}
		$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_CHILD_MERCHANT_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_CHILD_MERCHANT_ID );
		return trim( $id );
	}

	/**
	 * Child Merchant ID
	 *
	 * @return string|int
	 */
	public function fetch_child_partner_id() {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		if ( 'yes' === $is_activation_enable ) {
			return '';
		}
		$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_CHILD_PARTNER_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_CHILD_PARTNER_ID );
		return trim( $id );
	}

	/**
	 * Retrieve Client ID
	 *
	 * @return string|int
	 */
	public function fetch_client_id() {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		if ( 'yes' === $is_activation_enable ) {
			$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_SMB_CLIENT_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_SMB_CLIENT_ID );
			return trim( $id );
		}
		$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_CLIENT_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_CLIENT_ID );
		return trim( $id );
	}

	/**
	 * Retrieve Client Secret
	 *
	 * @return string|int
	 */
	public function fetch_client_secret() {
		$id = $this->common_config_helper->does_test_mode()
			? $this->common_config_helper->fetch_syf_option( self::SANDBOX_CLIENT_SECRET_ID )
			: $this->common_config_helper->fetch_syf_option( self::PROD_CLIENT_SECRET_ID );
		return trim( $id );
	}

	/**
	 * Retrieve Unify Widget Area
	 *
	 * @return array
	 */
	public function fetch_unify_widget() {
		return $this->common_config_helper->fetch_syf_option( self::SHOW_UNIFY_WIDGET );
	}

	/**
	 * Retrieve tag rules.
	 *
	 * @return int
	 */
	public function fetch_tag_rules() {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		return ( $this->common_config_helper->fetch_syf_option( self::SYNCHRONY_PROMOTIONS_KEY ) && 'no' === $is_activation_enable ) ? 1 : 0;
	}
	/**
	 * Retrieve Address Type.
	 *
	 * @return string
	 */
	public function fetch_address_type() {
		return $this->common_config_helper->fetch_syf_option( self::ADDRESS_TYPE );
	}
	/**
	 * Enable logger and Sending Log to Synchrony flag.
	 *
	 * @param string $input This is input string to fetch details.
	 *
	 * @return string|int
	 */
	public function synchrony_logger( $input ) {
		return $this->common_config_helper->fetch_syf_option( $input );
	}
	/**
	 * Retrieve multiwidget cache timeout.
	 *
	 * @return int
	 */
	public function fetch_cache_timeout() {
		return $this->common_config_helper->fetch_syf_option( self::CACHE_TIME_OUT );
	}

	/**
	 * Retrieve widget display approach.
	 *
	 * @return int
	 */
	public function fetch_widget_display_approach() {
		return (int) $this->common_config_helper->fetch_syf_option( self::WIDGET_DISPLAY_APPROACH );
	}

	/**
	 * Retrieve custom class product page.
	 *
	 * @return text
	 */
	public function fetch_custom_class_pdp() {
		return $this->common_config_helper->fetch_syf_option( self::CUSTOM_CLASS_PRICE_PDP );
	}

	/**
	 * Retrieve parent custom class product page.
	 *
	 * @return text
	 */
	public function fetch_custom_parent_class_pdp() {
		return $this->common_config_helper->fetch_syf_option( self::CUSTOM_PARENT_CLASS_PRICE_PDP );
	}
	/**
	 * Retrieve plugin active flag
	 *
	 * @return bool
	 */
	public function customer_allow_to_save_card() {
		$is_activation_enable = $this->common_config_helper->fetch_activation_enable_flag();
		return ( 1 === intval( $this->common_config_helper->fetch_syf_option( self::ENABLE_SAVE_CARD ) && 'no' === $is_activation_enable ) ) ? true : false;
	}
	/**
	 * Retrieve cart button split modal enabled flag image text and image url.
	 *
	 * @param string $input This is input.
	 *
	 * @return string
	 */
	public function fetch_split_modal_details( $input ) {
		return $this->common_config_helper->fetch_syf_option( $input );
	}
	/**
	 * Format Amount to decimal.
	 *
	 * @param float|int $amount Amount.
	 *
	 * @return float
	 */
	public function format_amount( $amount ) {
		return (float) number_format( (float) $amount, 2, '.', '' );
	}

	/**
	 * Retrieve PDP Widget Hook.
	 *
	 * @return string
	 */
	public function fetch_pdp_widget_hook() {
			return $this->common_config_helper->fetch_syf_option( self::PDP_WIDGET_LOCATION ) ?
			$this->common_config_helper->fetch_syf_option( self::PDP_WIDGET_LOCATION )
			: self::DEFAULT_PDP_WIDGET_LOCATION;
	}
}
