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
class Synchrony_Widget_Helper {

	/**
	 * SYNCHRONY_MW_VARIABLE_PRICE
	 *
	 * @var string
	 */
	public const SYNCHRONY_MW_VARIABLE_PRICE = 'default_varient_price';

	/**
	 * The Common Config Helper.
	 *
	 * @var Synchrony_Common_Config_Helper $common_config_helper
	 */
	private $common_config_helper;

	/**
	 * Widget Helper constructor.
	 */
	public function __construct() {
		$this->common_config_helper = new Synchrony_Common_Config_Helper();
	}
	/**
	 * Retrieve variable option.
	 *
	 * @return text
	 */
	public function fetch_mw_variable_option() {
		return $this->common_config_helper->fetch_syf_option( self::SYNCHRONY_MW_VARIABLE_PRICE );
	}

	/**
	 * Retrieve plugin active flag
	 *
	 * @return bool
	 */
	public function enable_mppbanner() {
		return 1 === intval( $this->common_config_helper->fetch_syf_option( 'enable_mppbanner' ) ) ? true : false;
	}
}
