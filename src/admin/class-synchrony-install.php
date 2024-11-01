<?php
/**
 * Installation related functions and actions.
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Admin\Synchrony_Tracker_Connection;
use Synchrony\Payments\Logs\Synchrony_Logger;

/**
 * Class Synchrony_Install
 */
class Synchrony_Install {

	/**
	 * FLAG
	 *
	 * @var int
	 */
	private const FLAG = 1;

	/**
	 * SYNCHRONY_PAGE_OPTION
	 *
	 * @var string
	 */
	private const SYNCHRONY_PAGE_OPTION = 'syf_sync_page';

	/**
	 * SYNCHRONY_CONFIG_SETTING_OPTION
	 *
	 * @var string
	 */
	private const SYNCHRONY_CONFIG_SETTING_OPTION = 'woocommerce_synchrony-unifi-payments_settings';

	/**
	 * SYF_DB_VERSION
	 *
	 * @var string
	 */
	private const SYF_DB_VERSION = '1.0.3';

	/**
	 * Config_helper
	 *
	 * @var Synchrony_Config_Helper $config_helper
	 */
	private $config_helper;

	/**
	 * Common_Config_helper
	 *
	 * @var Synchrony_Common_Config_Helper $common_config_helper
	 */
	private $common_config_helper;

	/**
	 * Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;

	/**
	 * Install constructor
	 */
	public function __construct() {
		$this->config_helper         = new Synchrony_Config_Helper();
		$this->common_config_helper  = new Synchrony_Common_Config_Helper();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
	}


	/**
	 * Creating SYF cms page
	 *
	 * @return int|null
	 */
	public function create_syf_page() {
		global $wpdb;
		$page_id              = '';
		$synchrony_page_title = $this->common_config_helper->synchrony_template();
		$slug                 = strtolower( str_replace( ' ', '-', trim( $synchrony_page_title ) ) );

		$synchrony_query = new \WP_Query(
			array(
				'post_type'   => 'page',
				'title'       => $synchrony_page_title,
				'post_status' => 'all',
			)
		);

		if ( ! empty( $synchrony_query->post ) ) {
			$page_data = array(
				'ID'          => $synchrony_query->post->ID,
				'post_status' => 'publish',
			);
			wp_update_post( $page_data );
			update_post_meta( $synchrony_query->post->ID, '_wp_page_template', 'template-synchrony.php' );
		} else {
			$page_id = wp_insert_post(
				array(
					'comment_status' => 'close',
					'ping_status'    => 'close',
					'post_author'    => 1,
					'post_title'     => ucwords( $this->common_config_helper->synchrony_template() ),
					'post_name'      => $slug,
					'post_status'    => 'publish',
					'post_content'   => '',
					'post_type'      => 'page',
					'post_excerpt'   => $this->common_config_helper->synchrony_template(),
				)
			);
			update_post_meta( $page_id, '_wp_page_template', 'template-synchrony.php' );
			update_option( self::SYNCHRONY_PAGE_OPTION, $page_id );
		}
		return $page_id;
	}

	/**
	 * Register plugin
	 *
	 * @return void
	 */
	public function register_plugin() {
		// Set default setting data.
		$option_data['enabled']                                      = '';
		$option_data['title']                                        = 'Synchrony Financing – Pay Over Time';
		$option_data['payment_action']                               = 'authorize-capture';
		$option_data['synchrony_test']                               = 'yes';
		$option_data['synchrony_deployed_digitalbuy_api_partner_id'] = '';
		$option_data['synchrony_deployed_digitalbuy_api_child_merchant_id']  = '';
		$option_data['synchrony_deployed_digitalbuy_api_child_partner_code'] = '';
		$option_data['synchrony_deployed_digitalbuy_api_client_id']          = '';
		$option_data['synchrony_deployed_digitalbuy_api_smb_partner_id']     = '';
		$option_data['synchrony_deployed_digitalbuy_api_smb_client_id']      = '';
		$option_data['synchrony_deployed_digitalbuy_api_client_secret']      = '';
		$option_data['synchrony_test_digitalbuy_api_partner_id']             = '';
		$option_data['synchrony_test_digitalbuy_api_child_merchant_id']      = '';
		$option_data['synchrony_test_digitalbuy_api_child_partner_code']     = '';
		$option_data['synchrony_test_digitalbuy_api_client_id']              = '';
		$option_data['synchrony_test_digitalbuy_api_smb_partner_id']         = '';
		$option_data['synchrony_test_digitalbuy_api_smb_client_id']          = '';
		$option_data['synchrony_test_digitalbuy_api_client_secret']          = '';
		$option_data['show_unify_widget']                                    = '';
		$option_data['widget_location_on_pdp']                               = 'woocommerce_single_product_summary';
		$option_data['module_version']                                       = $this->common_config_helper->app_version;
		$option_data['time_out']                          = $this->setting_config_helper->fetch_api_timeout();
		$option_data['default_varient_price']             = '1';
		$option_data['parent_price_class_selector_pdp']   = '';
		$option_data['price_class_selector_pdp']          = '';
		$option_data['cart_button_enabled']               = '0';
		$option_data['cart_button_image_url']             = 'https://shop.mysynchrony.com/v2/public/img/default_cart_button_image.png';
		$option_data['cart_button_image_text']            = 'Pay with Synchrony';
		$option_data['debug']                             = '1';
		$option_data['address_type_to_pass']              = 'billing';
		$option_data['logging_type']                      = '1';
		$option_data['widget_display_approach']           = '1';
		$option_data['enable_savelater']                  = '0';
		$option_data['enable_mppbanner']                  = '0';
		$option_data['cache_time_out']                    = '86400';
		$option_data['pop_up']                            = '2';
		$option_data['tag_rules_option']                  = '0';
		$option_data['syf_custom_css']                    = '';
		$option_data['logo']                              = '';
		$option_data['view_syflogo']                      = '';
		$option_data['view_logo']                         = '';
		$option_data['address_on_file']                   = '0';
		$option_data['deployed_enable_activation']        = 'yes';
		$option_data['synchrony_deployed_activation_key'] = '';
		$option_data['synchrony_deployed_smb_domain']     = $this->get_domain();
		$option_data['test_enable_activation']            = 'yes';
		$option_data['synchrony_test_activation_key']     = '';
		$option_data['synchrony_test_smb_domain']         = $this->get_domain();
		// Get existing setting data.
		$options                    = get_option( self::SYNCHRONY_CONFIG_SETTING_OPTION );
		$default_settings_options   = $this->retrieve_default_options_details( $options );
		$synchrony_deployed_options = $this->retrieve_prod_options_details( $options );
		$synchrony_test_options     = $this->retrieve_test_options_details( $options );
		$marketing_options          = $this->retrieve_marketing_options_details( $options );
		$common_settings_options    = $this->retrieve_common_settings_details( $options );
		$smb_settings_options       = $this->retrieve_smb_settings_details( $options );

		if ( isset( $options['cache_time_out'] ) && '' !== $options['cache_time_out'] ) {
			$option_data['cache_time_out'] = $options['cache_time_out'];
		}
		if ( isset( $options['tag_rules_option'] ) && '' !== $options['tag_rules_option'] ) {
			$option_data['tag_rules_option'] = $options['tag_rules_option'];
		}
		if ( isset( $options['syf_custom_css'] ) && '' !== $options['syf_custom_css'] ) {
			$option_data['syf_custom_css'] = $options['syf_custom_css'];
		}

		$tracker_connection  = new Synchrony_Tracker_Connection();
		$syf_payment_options = array_merge( $option_data, $default_settings_options, $synchrony_deployed_options, $synchrony_test_options, $marketing_options, $common_settings_options, $smb_settings_options );
		update_option( self::SYNCHRONY_CONFIG_SETTING_OPTION, $syf_payment_options );
		// Add default synchrony APIs on plugin activation.
		$this->config_helper->update_unifi_api_url();
		$endpoint_data = $this->config_helper->fetch_api_endpoint_list();
		$tracker_connection->retrieve_tracker_call( self::FLAG, $endpoint_data );
	}
	/**
	 * Get Domain
	 *
	 * @return string
	 */
	public function get_domain() {
		$site_url = wp_parse_url( get_site_url() );
		return $site_url['scheme'] . '://' . $site_url['host'];
	}
	/**
	 * Get default settings values from database
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function retrieve_default_options_details( $options ) {
		$default_settings_options = array();
		if ( isset( $options['title'] ) && '' !== $options['title'] ) {
			$default_settings_options['title'] = $options['title'];
		}
		if ( isset( $options['payment_action'] ) && '' !== $options['payment_action'] ) {
			$default_settings_options['payment_action'] = $options['payment_action'];
		}
		if ( isset( $options['synchrony_test'] ) && '' !== $options['synchrony_test'] ) {
			$default_settings_options['synchrony_test'] = $options['synchrony_test'];
		}
		if ( isset( $options['logo'] ) && '' !== $options['logo'] ) {
			$default_settings_options['logo'] = $options['logo'];
		}
		return $default_settings_options;
	}
	/**
	 * Config Option Value
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function retrieve_prod_options_details( $options ) {
		$synchrony_deployed_options = array();
		if ( isset( $options['synchrony_deployed_digitalbuy_api_partner_id'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_partner_id'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_partner_id'] = $options['synchrony_deployed_digitalbuy_api_partner_id'];
		}
		if ( isset( $options['synchrony_deployed_digitalbuy_api_child_merchant_id'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_child_merchant_id'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_child_merchant_id'] = $options['synchrony_deployed_digitalbuy_api_child_merchant_id'];
		}
		if ( isset( $options['synchrony_deployed_digitalbuy_api_child_partner_code'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_child_partner_code'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_child_partner_code'] = $options['synchrony_deployed_digitalbuy_api_child_partner_code'];
		}
		if ( isset( $options['synchrony_deployed_digitalbuy_api_client_id'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_client_id'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_client_id'] = $options['synchrony_deployed_digitalbuy_api_client_id'];
		}
		if ( isset( $options['synchrony_deployed_digitalbuy_api_smb_partner_id'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_smb_partner_id'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_smb_partner_id'] = $options['synchrony_deployed_digitalbuy_api_smb_partner_id'];
		}
		if ( isset( $options['synchrony_deployed_digitalbuy_api_smb_client_id'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_smb_client_id'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_smb_client_id'] = $options['synchrony_deployed_digitalbuy_api_smb_client_id'];
		}
		if ( isset( $options['synchrony_deployed_digitalbuy_api_client_secret'] ) && '' !== $options['synchrony_deployed_digitalbuy_api_client_secret'] ) {
			$synchrony_deployed_options['synchrony_deployed_digitalbuy_api_client_secret'] = $options['synchrony_deployed_digitalbuy_api_client_secret'];
		}
		return $synchrony_deployed_options;
	}
	/**
	 * Get common settings values from database
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function retrieve_test_options_details( $options ) {
		$synchrony_test_options = array();
		if ( isset( $options['synchrony_test_digitalbuy_api_partner_id'] ) && '' !== $options['synchrony_test_digitalbuy_api_partner_id'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_partner_id'] = $options['synchrony_test_digitalbuy_api_partner_id'];
		}
		if ( isset( $options['synchrony_test_digitalbuy_api_child_merchant_id'] ) && '' !== $options['synchrony_test_digitalbuy_api_child_merchant_id'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_child_merchant_id'] = $options['synchrony_test_digitalbuy_api_child_merchant_id'];
		}
		if ( isset( $options['synchrony_test_digitalbuy_api_child_partner_code'] ) && '' !== $options['synchrony_test_digitalbuy_api_child_partner_code'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_child_partner_code'] = $options['synchrony_test_digitalbuy_api_child_partner_code'];
		}
		if ( isset( $options['synchrony_test_digitalbuy_api_client_id'] ) && '' !== $options['synchrony_test_digitalbuy_api_client_id'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_client_id'] = $options['synchrony_test_digitalbuy_api_client_id'];
		}
		if ( isset( $options['synchrony_test_digitalbuy_api_client_secret'] ) && '' !== $options['synchrony_test_digitalbuy_api_client_secret'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_client_secret'] = $options['synchrony_test_digitalbuy_api_client_secret'];
		}
		if ( isset( $options['synchrony_test_digitalbuy_api_smb_partner_id'] ) && '' !== $options['synchrony_test_digitalbuy_api_smb_partner_id'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_smb_partner_id'] = $options['synchrony_test_digitalbuy_api_smb_partner_id'];
		}
		if ( isset( $options['synchrony_test_digitalbuy_api_smb_client_id'] ) && '' !== $options['synchrony_test_digitalbuy_api_smb_client_id'] ) {
			$synchrony_test_options['synchrony_test_digitalbuy_api_smb_client_id'] = $options['synchrony_test_digitalbuy_api_smb_client_id'];
		}
		return $synchrony_test_options;
	}
	/**
	 * Get marketing settings values from database
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function retrieve_marketing_options_details( $options ) {
		$marketing_options = array();
		if ( isset( $options['show_unify_widget'] ) && '' !== $options['show_unify_widget'] ) {
			$marketing_options['show_unify_widget'] = $options['show_unify_widget'];
		}
		if ( isset( $options['widget_location_on_pdp'] ) && '' !== $options['widget_location_on_pdp'] ) {
			$marketing_options['widget_location_on_pdp'] = $options['widget_location_on_pdp'];
		}
		if ( isset( $options['default_varient_price'] ) && '' !== $options['default_varient_price'] ) {
			$marketing_options['default_varient_price'] = $options['default_varient_price'];
		}
		if ( isset( $options['parent_price_class_selector_pdp'] ) && '' !== $options['parent_price_class_selector_pdp'] ) {
			$marketing_options['parent_price_class_selector_pdp'] = $options['parent_price_class_selector_pdp'];
		}
		if ( isset( $options['price_class_selector_pdp'] ) && '' !== $options['price_class_selector_pdp'] ) {
			$marketing_options['price_class_selector_pdp'] = $options['price_class_selector_pdp'];
		}
		$cart_button_options = array( 'cart_button_enabled', 'cart_button_image_url', 'cart_button_image_text' );
		foreach ( $cart_button_options as $option ) {
			if ( isset( $options[ $option ] ) && '' !== $options[ $option ] ) {
				$marketing_options[ $option ] = $options[ $option ];
			}
		}
		return $marketing_options;
	}
	/**
	 * Get common settings values from database
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function retrieve_common_settings_details( $options ) {
		$common_settings_options = array();
		$common_option_keys      = array( 'debug', 'address_type_to_pass', 'logging_type', 'widget_display_approach', 'enable_savelater', 'pop_up', 'address_on_file', 'enable_mppbanner' );
		foreach ( $common_option_keys as $option ) {
			if ( isset( $options[ $option ] ) && '' !== $options[ $option ] ) {
				$common_settings_options[ $option ] = $options[ $option ];
			}
		}
		return $common_settings_options;
	}
	/**
	 * Get smb settings values from database
	 *
	 * @param array $options Options array.
	 *
	 * @return array
	 */
	public function retrieve_smb_settings_details( $options ) {
		$smb_settings_options = array();
		if ( isset( $options['deployed_enable_activation'] ) && '' !== $options['deployed_enable_activation'] ) {
			$smb_settings_options['deployed_enable_activation'] = $options['deployed_enable_activation'];
		}
		if ( isset( $options['synchrony_deployed_activation_key'] ) && '' !== $options['synchrony_deployed_activation_key'] ) {
			$smb_settings_options['synchrony_deployed_activation_key'] = $options['synchrony_deployed_activation_key'];
		}
		if ( isset( $options['synchrony_deployed_smb_domain'] ) && '' !== $options['synchrony_deployed_smb_domain'] ) {
			$smb_settings_options['synchrony_deployed_smb_domain'] = $options['synchrony_deployed_smb_domain'];
		}
		if ( isset( $options['test_enable_activation'] ) && '' !== $options['test_enable_activation'] ) {
			$smb_settings_options['test_enable_activation'] = $options['test_enable_activation'];
		}
		if ( isset( $options['synchrony_test_activation_key'] ) && '' !== $options['synchrony_test_activation_key'] ) {
			$smb_settings_options['synchrony_test_activation_key'] = $options['synchrony_test_activation_key'];
		}
		if ( isset( $options['synchrony_test_smb_domain'] ) && '' !== $options['synchrony_test_smb_domain'] ) {
			$smb_settings_options['synchrony_test_smb_domain'] = $options['synchrony_test_smb_domain'];
		}
		return $smb_settings_options;
	}
	/**
	 * Insert MPP Banner
	 *
	 * @return void
	 */
	public function create_mpp_banner_post() {
		foreach ( $this->default_banner_list() as $banner ) {
			$post_title = $banner['title'];
			if ( ! post_exists( $post_title ) ) {
				wp_insert_post(
					array(
						'post_status'  => 'publish',
						'post_type'    => 'mpp-banner',
						'post_title'   => $post_title,
						'post_content' => $banner['content'],
					)
				);
			}
		}
	}
	/**
	 * Default MPP Banner List.
	 *
	 * @return array
	 */
	public function default_banner_list() {
		$is_synchrony_test_mode = $this->common_config_helper->does_test_mode();
		$mpp_banner_endpoint    = $this->config_helper->fetch_api_endpoint( 'synchrony_test_banner_mpp_endpoint', 'synchrony_deployed_banner_mpp_endpoint', false );
		$banner_base_url        = $mpp_banner_endpoint . 'banners';
		$content_prefix         = '<a class="MPPAnywhereClass"><img src="' . $banner_base_url;
		return array(
			array(
				'title'      => 'Synchrony Generic Banner (160x600)',
				'identifier' => 'synchrony-promo-generic-banner-160-600',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Banners_-_Generic_-_160x600.png" width="160" height="600" alt="Synchrony Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
			array(
				'title'      => 'Synchrony Generic Banner (300x200)',
				'identifier' => 'synchrony-promo-generic-banner-300-200',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Banners_-_Generic_-_300x200.png" width="300" height="200" alt="Synchrony Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
			array(
				'title'      => 'Synchrony Generic Banner (720x90)',
				'identifier' => 'synchrony-promo-generic-banner-720-90',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Banners_-_Generic_-_720x90.png" width="720" height="90" alt="Synchrony Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
			array(
				'title'      => 'Synchrony HOME Banner (300x200)',
				'identifier' => 'synchrony-promo-home-banner-300-200',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Banners_-_Home_Promotional_Financing_-_300x200.png" width="300" height="200" alt="HOME Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
			array(
				'title'      => 'Synchrony HOME Banner (720x90)',
				'identifier' => 'synchrony-promo-home-banner-720-90',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Banners_-_Home_Promotional_Financing_-_720x90.png" width="720" height="90" alt="HOME Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
			array(
				'title'      => 'Synchrony Car Care Banner (300x200)',
				'identifier' => 'synchrony-promo-car-care-banner-300-200',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Car_Care_Banner_Set_-_300x200.jpg" width="300" height="200" alt="Car Care Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
			array(
				'title'      => 'Synchrony Car Care Banner (720x90)',
				'identifier' => 'synchrony-promo-car-care-banner-720-90',
				'content'    => $content_prefix . '/SYF_DPS-0035_-_Car_Care_Banner_Set_-_720x90.png" width="720" height="90" alt="Car Care Credit Card Banner" /></a>',
				'is_active'  => 0,
			),
		);
	}
	/**
	 * Get title of Payment method.
	 *
	 * @param array $options Options array.
	 *
	 * @return string
	 */
	public function retrieve_title( $options ) {
		$title = 'Synchrony Financing – Pay Over Time';
		if ( isset( $options['title'] ) && '' !== $options['title'] ) {
			$title = $options['title'];
		}
		return $title;
	}
	/**
	 * Get sandbox.
	 *
	 * @param array $options Options array.
	 *
	 * @return string
	 */
	public function retrieve_sandbox( $options ) {
		$synchrony_test = 'no';
		if ( isset( $options['sandbox'] ) && '' !== $options['sandbox'] ) {
			$synchrony_test = $options['sandbox'];
		}
		return $synchrony_test;
	}
	/**
	 * Creating custom database table for smb feature.
	 *
	 * @return void
	 */
	public function create_custom_db() {
		try {
			global $wpdb;
			$installed_db_ver = get_option( 'syf_db_version' );

			if ( self::SYF_DB_VERSION !== $installed_db_ver ) {
				$table_name = $wpdb->prefix . 'synchrony_partner_auth';

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
					id int(20) NOT NULL AUTO_INCREMENT,
					env_type int(20) NOT NULL,
					partner_id varchar(255) DEFAULT '' NOT NULL,
					client_id varchar(255) DEFAULT '' NOT NULL,
					access_token longtext DEFAULT '' NOT NULL,
					refresh_token longtext DEFAULT '' NOT NULL,
					id_token longtext DEFAULT '' NOT NULL,
					refresh_token_issue_at varchar(255) DEFAULT '' NOT NULL,
					expires_in varchar(255) DEFAULT '' NOT NULL,
					refresh_token_expired_in varchar(255) DEFAULT '' NOT NULL,
					generate_refresh_token_time varchar(255) DEFAULT '' NOT NULL,
					refresh_token_refresh_in varchar(255) DEFAULT '' NOT NULL,
					partner_profile_code varchar(255) DEFAULT '' NOT NULL,
					syf_version varchar(255) DEFAULT '' NOT NULL,
					PRIMARY KEY  (id)
				) $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
				update_option( 'syf_db_version', self::SYF_DB_VERSION );
			}
		} catch ( \Exception $e ) {
			$logger = new Synchrony_Logger();
			$logger->debug( 'Error in creating syf auth table: ' . $e->getMessage() );
		}
	}
}
