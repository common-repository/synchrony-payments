<?php
/**
 * Plugin Name: Synchrony Payments
 * Plugin URI:  https://www.synchronybusiness.com/platforms-integrations.html
 * Description: Synchrony (NYSE: SYF) is a premier consumer financial services company delivering customized financing programs and award-winning banking products.
 * Version:     1.0.4
 * Author:      Synchrony
 * Author URI:  https://www.synchrony.com/
 * License:     GPL-2.0
 * Requires PHP: 7.2
 * WC requires at least: 3.9
 * WC tested up to: 9.1.4
 * Text Domain: synchrony-payments
 *
 * @package Synchrony\Payments
 */

defined( 'ABSPATH' ) || exit;

$autoload_filepath = __DIR__ . '/autoload.php';
if ( file_exists( $autoload_filepath ) ) {
	require_once $autoload_filepath;
}

use Synchrony\Payments\Frontend\Synchrony_Frontend;
use Synchrony\Payments\Admin\Synchrony_Install;
use Synchrony\Payments\Admin\Synchrony_Promotag_Configurator;
use Synchrony\Payments\Webhooks\Synchrony_Callback;
use Synchrony\Payments\Admin\Synchrony_Tracker_Connection;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Widget\Synchrony_Mppaw_Widget;
use Synchrony\Payments\Admin\Synchrony_Mppaw_Banner;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Widget_Helper;
use Synchrony\Payments\Frontend\Synchrony_Cart_Hooks;
use Synchrony\Payments\Frontend\Synchrony_Blocks;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Synchrony\Payments\Frontend\Synchrony_Widgets;
use Synchrony\Payments\Blocks\Synchrony_Block_Mpp_Widget;
/**
 * Class Synchrony
 */
class Synchrony_Payment {

	/**
	 * Frontend
	 *
	 * @var Frontend $frontend
	 */
	private $frontend;

	/**
	 * Promotag_Configurator
	 *
	 * @var Promotag_Configurator $tag_configurator
	 */
	private $tag_configurator;

	/**
	 * Tracker_Connection
	 *
	 * @var Tracker_Connection $tracker_connection
	 */
	private $tracker_connection;

	/**
	 * Synchrony_Cart_Hooks
	 *
	 * @var Synchrony_Cart_Hooks $cart_hooks
	 */
	private $cart_hooks;

	/**
	 * Synchrony_Widgets
	 *
	 * @var Synchrony_Widgets $widget_load
	 */
	private $widget_load;

	/**
	 * Synchrony_Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;

	/**
	 * Synchrony_Mppaw_Widget Class.
	 *
	 * @var Synchrony_Mppaw_Widget $mpp_widget
	 */
	private $mpp_widget;

	/**
	 * Synchrony_Mppaw_Banner Class.
	 *
	 * @var  $mpp_banner
	 */
	private $mpp_banner;

	/**
	 * Logger
	 *
	 * @var Logger $logger
	 */
	private $logger;

	/**
	 * Client
	 *
	 * @var Client $client
	 */
	private $client;

	/**
	 * Callback
	 *
	 * @var Callback $callback
	 */
	private $callback;

	/**
	 * SYNCHRONY_MANUAL_CAPTURE
	 *
	 * @var string
	 */
	const SYNCHRONY_MANUAL_CAPTURE = '_syf_manual_capture';
	/**
	 * FLAG
	 *
	 * @var int
	 */
	const FLAG = 0;
	/**
	 * MODULE_STATUS
	 *
	 * @var string
	 */
	const MODULE_STATUS = 'Disabled';
	/**
	 * INSTALLATION_STATUS
	 *
	 * @var string
	 */
	const INSTALLATION_STATUS = 'false';
	/**
	 * Define Required Classes
	 */
	public function __construct() {
		global $wp_version;
		$this->tag_configurator      = new Synchrony_Promotag_Configurator();
		$this->frontend              = new Synchrony_Frontend();
		$this->callback              = new Synchrony_Callback();
		$this->tracker_connection    = new Synchrony_Tracker_Connection();
		$this->logger                = new Synchrony_Logger();
		$this->client                = new Synchrony_Client();
		$this->mpp_widget            = new Synchrony_Mppaw_Widget();
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$this->cart_hooks            = new Synchrony_Cart_Hooks();
		$this->widget_load           = new Synchrony_Widgets();
		$this->mpp_block_widget      = new Synchrony_Block_Mpp_Widget();
		if ( ! session_id() ) {
			session_start();
		}

		if ( ! $this->does_woocommerce_activate() ) {
			add_action(
				'admin_notices',
				function () {
					/* translators: %s - WooCommerce Link */
					echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'WooCommerce Synchrony Payments requires WooCommerce to be installed and active. You can download %s here.', 'synchrony-payments' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
				}
			);
			return;
		}
		if ( version_compare( PHP_VERSION, '7.1', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>' . esc_html__( 'WooCommerce Synchrony Payments requires PHP 7.1 or above.', 'synchrony-payments' ), '</p></div>';
				}
			);
			return;
		}
		if ( version_compare( $wp_version, '5.2.2', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>' . esc_html__( 'WooCommerce Synchrony Payments requires WordPress 5.2.2 or above.', 'synchrony-payments' ), '</p></div>';
				}
			);
			return;
		}
		$this->init();
		if ( is_admin() ) {
			$this->mpp_banner = new Synchrony_Mppaw_Banner();
		}
	}

	/**
	 * Init Woocommerce Synchrony Hook
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'wp_nav_menu_objects', array( $this, 'remove_menu_item' ), 10, 2 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'synchrony_unifi_payments' ) );
		add_action( 'plugins_loaded', array( $this, 'frontend_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'manual_capture_meta_box' ) );
		register_activation_hook( __FILE__, array( $this, 'synchrony_plugin_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_disable_logger' ) );
		add_action( 'admin_notices', array( $this, 'custom_admin_notice_message' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'unset_session_values' ), 10, 1 );
		add_action( 'widgets_init', array( $this, 'mppb_load_widget' ) );
		// Declare compatibility with High-Performance Order Storage.
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		add_filter( 'script_loader_tag', array( $this, 'update_unifi_script' ), 10, 3 );
		$this->attach_synchrony_template();
		// Blocks Support.
		$this->mpp_block_widget->init();
		add_action(
			'woocommerce_blocks_loaded',
			function () {
				if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							$payment_method_registry->register( new Synchrony_Blocks() );
						}
					);
				}
			}
		);
		// Add "Settings" link to Plugins screen.
		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			function ( $links ) {
				if ( ! $this->does_woocommerce_activate() ) {
					return $links;
				}

				array_unshift(
					$links,
					sprintf(
						'<a href="%1$s">%2$s</a>',
						admin_url( 'admin.php?page=wc-settings&tab=checkout' ),
						__( 'Settings', 'synchrony-payments' )
					)
				);

				return $links;
			}
		);
	}

	/**
	 * Remove -js from unifi.js script id
	 *
	 * @param string $tag Tag for the enqueued script.
	 * @param string $handle The script’s registered handle.
	 *
	 * @return string
	 */
	public function update_unifi_script( $tag, $handle ) {
		if ( 'syfMPPScript' === $handle ) {
			$tag = str_replace( '-js', '', $tag );
		}
		return $tag;
	}

	/**
	 * Add Synchrony Template
	 *
	 * @return void
	 */
	public function attach_synchrony_template() {
		add_filter(
			'page_template',
			function ( $page_template ) {
				if ( is_page_template( 'template-synchrony.php' ) ) {
					return WP_PLUGIN_DIR . '/synchrony-payments/templates/template-synchrony.php';
				}
				return $page_template;
			}
		);
	}

	/**
	 * Initialize the plugin and its modules.
	 *
	 * @return void
	 */
	public function frontend_init() {
		$this->frontend->init();
		$this->cart_hooks->init();
		$this->widget_load->init();
	}
	/**
	 * Add Admin Methods
	 *
	 * @param array $methods These are the methods array.
	 *
	 * @return array
	 */
	public function synchrony_unifi_payments( $methods = array() ) {
		$methods[] = '\Synchrony\Payments\Admin\Synchrony_Admin';
		return $methods;
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool true if WooCommerce is active, otherwise false.
	 */
	public function does_woocommerce_activate() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Plugin Activate
	 *
	 * @return void
	 */
	public function synchrony_plugin_activate() {
		$install = new Synchrony_Install();
		$install->create_syf_page();
		$install->create_custom_db();
		$install->register_plugin();
		$install->create_mpp_banner_post();
		set_transient( 'synchrony_admin_notice', true, 5 );
	}
	/**
	 * Remove Synchrony Payment page from Menu
	 *
	 * @param array $sorted_menu_objects This is array of all menus.
	 *
	 * @return array
	 */
	public function remove_menu_item( $sorted_menu_objects ) {
		// remove the menu item that has a title of 'Synchrony Payment'.
		foreach ( $sorted_menu_objects as $key => $menu_object ) {
			if ( 'Synchrony Payment' === $menu_object->title ) {
				unset( $sorted_menu_objects[ $key ] );
				break;
			}
		}
		return $sorted_menu_objects;
	}


	/**
	 * Config Manual Capture Box
	 *
	 * @return void
	 */
	public function manual_capture_meta_box() {
		$current_screen = get_current_screen();

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) && isset( $current_screen->base ) && 'woocommerce_page_wc-orders' === $current_screen->base ) {
			if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'custom_order_tables' ) ) {
				$order    = wc_get_order();
				$data     = $order->get_data(); // The Order data.
				$order_id = $data['id'];
				if ( isset( $data['id'] ) ) {
					$post_id        = $data['id'];
					$legacy         = get_post_meta( $order_id, '_syf_order_legacy', true );
					$manual_capture = get_post_meta( $order_id, self::SYNCHRONY_MANUAL_CAPTURE, true );
					if ( 'Y' === $legacy && 'shop_order' === OrderUtil::get_order_type( $order_id ) && ( is_null( $manual_capture ) || '' === $manual_capture ) ) {

						$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
						? wc_get_page_screen_id( 'shop-order' )
						: 'shop_order';
						add_meta_box(
							'manual_capture_box',
							'Manual Capture',
							array( $this, 'manual_capture_metabox_callback' ),
							$screen, // 'shop_order',
							'side',
							'high'
						);
					}
				}
			}
		} else {
			global $post;
			$legacy         = get_post_meta( $post->ID, '_syf_order_legacy', true );
			$manual_capture = get_post_meta( $post->ID, self::SYNCHRONY_MANUAL_CAPTURE, true );
			if ( 'Y' === $legacy && 'shop_order' === $post->post_type && ( is_null( $manual_capture ) || '' === $manual_capture ) ) {
				add_meta_box(
					'manual_capture_box',
					'Manual Capture',
					array( $this, 'manual_capture_metabox_callback' ),
					'shop_order',
					'side',
					'high'
				);
			}
		}
	}

	/**
	 * Add Manual Capture Select Box
	 *
	 * @return void
	 */
	public function manual_capture_metabox_callback() {
		wp_nonce_field( 'syf_manual_capture_nonce', 'syf_manual_capture_nonce' );
		$order          = wc_get_order();
		$data           = $order->get_data(); // The Order data.
		$order_id       = $data['id'];
		$value          = get_post_meta( $order_id, self::SYNCHRONY_MANUAL_CAPTURE, true );
		$select_online  = ( 'online' === $value ) ? 'selected' : '';
		$select_offline = ( 'offline' === $value ) ? 'selected' : '';
		$disabled       = '';
		if ( 'offline' === $value || 'online' === $value ) {
			$disabled = 'disabled';
		}
		echo '
		<div class="syf_box">
		  <style scoped>
			  .syf_box{
				  display: grid;
				  grid-template-columns: max-content 1fr;
				  grid-row-gap: 10px;
				  grid-column-gap: 20px;
			  }
			  .syf_field{
				  display: contents;
			  }
		  </style>
		  <p class="meta-options syf_field">
			  <select ' . esc_html( $disabled ) . ' id="syf_manual_capture"
				  type="text"
				  name="syf_manual_capture"
				  value="' . esc_html( $value ) . '">
				  <option value="-1">' . esc_attr( 'Select' ) . '</option>
				  <option ' . esc_html( $select_online ) . ' value="online">' . esc_attr( 'Capture Online' ) . '</option>
				  <option ' . esc_html( $select_offline ) . ' value="offline">' . esc_attr( 'Capture Offline' ) . '</option>
			  </select>
		  </p>
		
		</div>';
	}

	/**
	 * Plugin Deactivate Logs
	 *
	 * @return void
	 */
	public function plugin_disable_logger() {
		$plugin_data = $this->tracker_connection->does_module_data( self::FLAG, array() );
		$action      = $GLOBALS['action'];
		if ( 'deactivate' === $action ) {
			$plugin_data['installationStatus']  = self::INSTALLATION_STATUS;
			$plugin_data['moduleStatus']        = self::MODULE_STATUS;
			$plugin_data['configChangeDetails'] = array();
		}
		$this->logger->debug( 'PostData: ' . wp_json_encode( $plugin_data ) );
		$this->client->module_tracking( $plugin_data );
	}

	/**
	 * Display Admin Notice Message
	 *
	 * @return void
	 */
	public function custom_admin_notice_message() {
		/* Check transient, if available display notice */
		if ( get_transient( 'synchrony_admin_notice' ) ) {
			?>
			<div class="updated notice is-dismissible">
				<p>Thank you for activating <strong>Synchrony Payments</strong> plugin! <strong>Please enable to use it.</strong>.</p>
			</div>
			<?php
			/* Delete transient, only display this notice once. */
			delete_transient( 'synchrony_admin_notice' );
		}
	}
	/**
	 * Register and load the widget
	 *
	 * @return void
	 */
	public function mppb_load_widget() {
		$widget_helper    = new Synchrony_Widget_Helper();
		$is_plugin_active = $this->setting_config_helper->synchrony_plugin_active();
		$enable_mppbanner = $widget_helper->enable_mppbanner();
		if ( $is_plugin_active && $enable_mppbanner ) {
			register_widget( $this->mpp_widget );
		}
	}

	/**
	 * Declare compatibility with High-Performance Order Storage.
	 *
	 * @return void
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Unset the session value 'pay_syf_token_id' on order place.
	 *
	 * @return void
	 */
	public function unset_session_values() {
		// Unset session variable.
		WC()->session->__unset( 'pay_syf_token_id' );
	}
}
new Synchrony_Payment();
