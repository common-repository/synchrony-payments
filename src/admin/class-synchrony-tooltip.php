<?php
/**
 * Helper Admin
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Synchrony_Tooltip.
 */
class Synchrony_Tooltip {

	/**
	 * ENABLE_SYNCHRONY_PAYMENT_GATEWAY
	 *
	 * @var string
	 */
	private const ENABLE_SYNCHRONY_PAYMENT_GATEWAY = 'We can enable the synchrony payment gateway.';
	/**
	 * TITLE
	 *
	 * @var string
	 */
	private const TITLE = 'Payment title of checkout process.';
	/**
	 * LOGO
	 *
	 * @var string
	 */
	private const LOGO = 'Logo will be displayed on checkout page near your gateway name.';
	/**
	 * PAYMENT_ACTION
	 *
	 * @var string
	 */
	private const PAYMENT_ACTION = 'Payment Action (Authorize/Capture).';
	/**
	 * GATEWAY_MODE
	 *
	 * @var string
	 */
	private const GATEWAY_MODE = 'You can select the test mode of gateway i.e Sandbox.';
	/**
	 * PRODUCTION_PARTNER_ID
	 *
	 * @var string
	 */
	private const PRODUCTION_PARTNER_ID = 'This is the Production Partner Id provided by Synchrony Pay when you signed up for an account';
	/**
	 * PRODUCTION_CLIENT_ID
	 *
	 * @var string
	 */
	private const PRODUCTION_CLIENT_ID = 'This is the Production Client Id provided by Synchrony Pay when you signed up for an account';
	/**
	 * PRODUCTION_CLIENT_SECRET
	 *
	 * @var string
	 */
	private const PRODUCTION_CLIENT_SECRET = 'This is the Production Client Secret provided by Synchrony Pay when you signed up for an account';
	/**
	 * SANDBOX_PARTNER_ID
	 *
	 * @var string
	 */
	private const SANDBOX_PARTNER_ID = 'This is the Sandbox Partner Id provided by Synchrony Pay when you signed up for an account';
	/**
	 * SANDBOX_CLIENT_ID
	 *
	 * @var string
	 */
	private const SANDBOX_CLIENT_ID = 'This is the Sandbox Client Id provided by Synchrony Pay when you signed up for an account';
	/**
	 * SANDBOX_CLIENT_SECRET
	 *
	 * @var string
	 */
	private const SANDBOX_CLIENT_SECRET = 'This is the Sandbox Client Secret provided by Synchrony Pay when you signed up for an account';
	/**
	 * DISPLAY_AREA
	 *
	 * @var string
	 */
	private const DISPLAY_AREA = 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods';
	/**
	 * PDP_WIDGET_LOCATION
	 *
	 * @var string
	 */
	private const PDP_WIDGET_LOCATION = 'The PDP widget shows based on the selected location of the page';
	/**
	 * MODULE_VERSION
	 *
	 * @var string
	 */
	private const MODULE_VERSION = 'This is module version';
	/**
	 * API_REQUEST_TIMEOUT
	 *
	 * @var string
	 */
	private const API_REQUEST_TIMEOUT = 'This is timeout for the API request';
	/**
	 * DEBUG_MODE
	 *
	 * @var string
	 */
	private const DEBUG_MODE = 'This is flag to enable logs';
	/**
	 * ADDRESS_TYPE_TO_PASS
	 *
	 * @var string
	 */
	private const ADDRESS_TYPE_TO_PASS = 'This is the address shipping/billing which will be passed to unify modal';
	/**
	 * SEND_ERROR_LOG_TO_SYF
	 *
	 * @var string
	 */
	private const SEND_ERROR_LOG_TO_SYF = 'This is the flag to send all error logs to synchrony server';
	/**
	 * CACHE_TIME_OUT
	 *
	 * @var string
	 */
	private const CACHE_TIME_OUT = 'Cache timeout';
	/**
	 * ENABLE_SYNCHRONY_PROMOTIONS
	 *
	 * @var string
	 */
	private const PLP_PAGE = 'This is to select multi widget cache mechanism';

	/**
	 * ENABLE_SYNCHRONY_PROMOTIONS
	 *
	 * @var string
	 */
	private const ENABLE_SYNCHRONY_PROMOTIONS = 'This is the flag to enable synchrony promotions';

	/**
	 * ENABLE_SYNCHRONY_MULTIWIDGET_VARIAIONS
	 *
	 * @var string
	 */
	private const ENABLE_MW_ON_VARIABLES = 'This is the flag to enable multiwidget on variable product default price';

	/**
	 * PARENT_PRICE_CLASS_SELECTOR_PDP
	 *
	 * @var string
	 */
	private const PARENT_PRICE_CLASS_SELECTOR_PDP = 'Add parent custom class/ Id for price to show widget on product detail page';

	/**
	 * PRICE_CLASS_SELECTOR_PDP
	 *
	 * @var string
	 */
	private const PRICE_CLASS_SELECTOR_PDP = 'Use # or . before the selector name';
	/**
	 * ENABLE_SYNCHRONY_CHECKOUT_POPUP
	 *
	 * @var string
	 */
	private const ENABLE_CHECKOUT_POPUP = 'This is to select checkout modal presentation type';
	/**
	 * ADDRESS_ON_FILE
	 *
	 * @var string
	 */
	private const ADDRESS_ON_FILE = 'This is flag to enable order address verification feature.';

	/**
	 * Get all tooltips display in admin
	 *
	 * @return array
	 */
	public function retrieve_tooltips() {
		return array(
			'enable_syf_payment_gateway'       => self::ENABLE_SYNCHRONY_PAYMENT_GATEWAY,
			'title'                            => self::TITLE,
			'logo'                             => self::LOGO,
			'payment_action'                   => self::PAYMENT_ACTION,
			'gateway_mode'                     => self::GATEWAY_MODE,
			'synchrony_deployed_partner_id'    => self::PRODUCTION_PARTNER_ID,
			'synchrony_deployed_client_id'     => self::PRODUCTION_CLIENT_ID,
			'synchrony_deployed_client_secret' => self::PRODUCTION_CLIENT_SECRET,
			'synchrony_test_partner_id'        => self::SANDBOX_PARTNER_ID,
			'synchrony_test_client_id'         => self::SANDBOX_CLIENT_ID,
			'synchrony_test_client_secret'     => self::SANDBOX_CLIENT_SECRET,
			'display_area'                     => self::DISPLAY_AREA,
			'pdp_widget_location'              => self::PDP_WIDGET_LOCATION,
			'module_version'                   => self::MODULE_VERSION,
			'api_request_timeout'              => self::API_REQUEST_TIMEOUT,
			'debug_mode'                       => self::DEBUG_MODE,
			'address_type_to_pass'             => self::ADDRESS_TYPE_TO_PASS,
			'send_error_to_syf'                => self::SEND_ERROR_LOG_TO_SYF,
			'cache_time_out'                   => self::CACHE_TIME_OUT,
			'enable_syf_promotions'            => self::ENABLE_SYNCHRONY_PROMOTIONS,
			'plp_page'                         => self::PLP_PAGE,
			'default_varient_price'            => self::ENABLE_MW_ON_VARIABLES,
			'parent_price_class_selector_pdp'  => self::PARENT_PRICE_CLASS_SELECTOR_PDP,
			'price_class_selector_pdp'         => self::PRICE_CLASS_SELECTOR_PDP,
			'pop_up'                           => self::ENABLE_CHECKOUT_POPUP,
			'address_on_file'                  => self::ADDRESS_ON_FILE,
		);
	}
}
