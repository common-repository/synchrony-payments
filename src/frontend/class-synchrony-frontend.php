<?php
/**
 * Frontend File
 *
 * @package Synchrony\Payments\Frontend
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Widget_Helper;
use Synchrony\Payments\Admin\Synchrony_Promotag_Configurator;
use Synchrony\Payments\Admin\Synchrony_List_Product_Cache;
use Synchrony\Payments\Frontend\Synchrony_Promotag_Config;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Frontend\Synchrony_Cache_Mechanism;
use Synchrony\Payments\Gateway\Synchrony_Smb;

/**
 * Class Synchrony_Frontend
 */
class Synchrony_Frontend {

	/**
	 * SYNCHRONY_PAYMENT_OPTION_KEY
	 *
	 * @var string
	 */
	const SYNCHRONY_PAYMENT_OPTION_KEY = 'woocommerce_synchrony-unifi-payments_settings';
	/**
	 * SYNCHRONY_WIDGET_OBJECT_PA
	 *
	 * @var string
	 */
	const SYNCHRONY_WIDGET_OBJECT_PA = 'syfWidgetObject.productAttributes = ';

	/**
	 * SCRIPT_VERSION
	 *
	 * @var string
	 */
	const SCRIPT_VERSION = '3.0.3.1';

	/**
	 * STYLE_VERSION
	 *
	 * @var string
	 */
	const STYLE_VERSION = '1.0.1';
	/**
	 * CLOSE_DIV
	 *
	 * @var string
	 */
	const CLOSE_DIV = '</div>';

	/**
	 * Synchrony_Cart_Script
	 *
	 * @var Synchrony_Cart_Script $synchrony_cart_script
	 */
	private $synchrony_cart_script;

	/**
	 * Logger
	 *
	 * @var Synchrony_Logger
	 */
	private $logger;

	/**
	 * Client
	 *
	 * @var Synchrony_Client $client
	 */
	private $client;

	/**
	 * Synchrony_Cache_Mechanism
	 *
	 * @var Synchrony_Cache_Mechanism $widget_cache_mechanism
	 */
	private $widget_cache_mechanism;

	/**
	 * Synchrony_Setting_Config_Helper Class.
	 *
	 * @var Synchrony_Setting_Config_Helper
	 */
	private $setting_config_helper;

	/**
	 * Synchrony_Widget_Helper Class.
	 *
	 * @var Synchrony_Widget_Helper
	 */
	private $widget_helper;

	/**
	 * Define Init to add woocommerce hooks
	 */
	public function __construct() {
		$this->setting_config_helper  = new Synchrony_Setting_Config_Helper();
		$this->synchrony_cart_script  = plugin_dir_url( __FILE__ ) . '../../assets/js/syfcart.js';
		$this->logger                 = new Synchrony_Logger();
		$this->client                 = new Synchrony_Client();
		$this->widget_cache_mechanism = new Synchrony_Cache_Mechanism();
		$this->widget_helper          = new Synchrony_Widget_Helper();
	}

	/**
	 * Init the Widget by setting up action and filter hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->setting_config_helper->synchrony_plugin_active() ) {
			$common_config_helper  = new Synchrony_Common_Config_Helper();
			$is_activation_enabled = $common_config_helper->fetch_activation_enable_flag();
			add_shortcode( 'synchrony_multiwidget', array( $this, 'custom_multi_widget' ) );
			if ( 'yes' !== $is_activation_enabled ) {
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'multi_widget' ), 11 );
			}
			$pdp_widget_location = $this->setting_config_helper->fetch_pdp_widget_hook();
			add_action( $pdp_widget_location, array( $this, 'product_widget' ), 10 );
			add_action( 'wp_footer', array( $this, 'unifi_widget_script' ) );
			add_shortcode( 'synchrony_product_widget', array( $this, 'product_widget' ) );
			if ( 'yes' !== $is_activation_enabled ) {
				add_action( 'wp_head', array( $this, 'load_cache_in_plp' ), 10 );
			}
			add_action( 'woocommerce_before_single_product', array( $this, 'retrieve_smb_auth_token' ), 10 );
		}
	}

	/**
	 * Load cache on plp page
	 *
	 * @return bool
	 */
	public function load_cache_in_plp() {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$enabled_widget_arr    = $setting_config_helper->fetch_unify_widget();
		if ( ! is_page_template( 'template-synchrony.php' ) && ( is_shop() || is_product_category() ) && $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'plp', $enabled_widget_arr, true ) ) ) {
			$widget_cache    = new Synchrony_List_Product_Cache();
			$product_details = $this->widget_cache_mechanism->retrieve_query_ids();
			if ( ! empty( $product_details ) ) {
				$product_ids        = $product_details['products'];
				$product_attributes = $product_details['product_attributes'];
				$widget_cache->load_cache_mechanism( $product_ids, $product_attributes );
				return true;
			} else {
				$this->logger->debug( 'MultiWidgetEngine: No product found in the current page : ' . get_the_title() );
				return false;
			}
		}
	}

	/**
	 * Load multi Widget on list page
	 *
	 * @return void
	 */
	public function multi_widget() {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$enabled_widget_arr    = $setting_config_helper->fetch_unify_widget();

		if ( $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'plp', $enabled_widget_arr, true ) ) ) {
			$synchrony_mw = '[synchrony_multiwidget]';
			echo do_shortcode( wp_kses_post( $synchrony_mw ) );
		}
	}
	/**
	 * Get cache tags
	 *
	 * @param array  $cache_tag This is cache tag list.
	 * @param string $tag_approach_config This is tag approach type.
	 *
	 * @return string
	 */
	public function retrieve_tags( $cache_tag, $tag_approach_config ) {
		$tags = '';
		if ( ! empty( $cache_tag ) ) {
			if ( is_array( $cache_tag ) && 1 === intval( $tag_approach_config ) ) {
				$tags = implode( ',', $cache_tag );
			} elseif ( is_array( $cache_tag ) && 2 === intval( $tag_approach_config ) ) {
				if ( is_array( $cache_tag ) && ! empty( $cache_tag ) ) {
					if ( isset( $cache_tag ) ) {
						$tags = $cache_tag;
					} else {
						$tags = implode( ',', $cache_tag );
					}
				}
			} else {
				$tags = $cache_tag;
			}
		}
		return $tags;
	}

	/**
	 * Set Updated Price on Product page
	 *
	 * @return void
	 */
	public function product_widget() {
		global $product;
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$product_price         = $setting_config_helper->format_amount( $product->get_price() );

		if ( $product->get_type() === 'grouped' ) {
			$product_price = $this->retrieve_child_product_price( $product );
		}
		echo wp_kses_post( $this->retrieve_widgets( $product_price ) );
		if ( $setting_config_helper->fetch_custom_class_pdp() ) {
			wc_enqueue_js(
				"
				window.onload = function() {
					var priceObserver = new MutationObserver(function(mutations) {
						mutations.forEach(function(mutation)
					{
						$('.syf_product_price').html($('" . $setting_config_helper->fetch_custom_class_pdp() . "').text());
					});
					});

					var config = { childList: true, subtree:true};

					//Check for class
					if ($('body').hasClass('" . $setting_config_helper->fetch_custom_parent_class_pdp() . "')) {
						$('.syf_product_price').html($('." . $setting_config_helper->fetch_custom_parent_class_pdp() . "').text());
						var targetElement = $('." . $setting_config_helper->fetch_custom_parent_class_pdp() . "')[0];
						priceObserver.observe(targetElement, config);
					}
					//Check for Id
					if ($('#" . $setting_config_helper->fetch_custom_parent_class_pdp() . "').length > 0) {
						$('.syf_product_price').html($('" . $setting_config_helper->fetch_custom_class_pdp() . "').text());
						var targetElement = $('#" . $setting_config_helper->fetch_custom_parent_class_pdp() . "')[0];
						priceObserver.observe(targetElement, config);
					}
				};
				"
			);
			return;
		}
		if ( $product->is_type( 'variable' ) ) {
			wc_enqueue_js(
				"
				$(document).on('found_variation', 'form.cart', function( event, variation ) { 
					$('.syf_product_price').html(variation.display_price);       
				});"
			);
		}
		if ( $product->is_type( 'grouped' ) && $this->retrieve_children_info( $product ) ) {
			$get_child_info = wp_json_encode( $this->retrieve_children_info( $product ) );
			wc_enqueue_js(
				"
			set_widget_total();
			$('.qty').on('input', function () {
				set_widget_total();
			});
			function set_widget_total() {
				if (get_input_qty()) {
					var group_total_price = $.map(get_input_qty(), function (item) {
						item = JSON.stringify(item);
						var match = item.match(/\[(\d+)\]=(\d+)/);
						if (match && match[2]) {
							var pid = parseInt(match[1]);
							return get_item_price(pid) * match[2];
						}
					}).reduce(
						function (accumulator, current_value) {
							return accumulator + current_value;
						}, 0
					);
					if (group_total_price != 0) {
						$('.syf_product_price').html(group_total_price);
					} else {
						$('.syf_product_price').html(" . $product_price . ");
					}
				}
			}
			function get_input_qty() {
				var select_qty = [];
				$('.woocommerce-grouped-product-list-item :input').each(function () {
					select_qty.push([
						$(this).attr('name') + '=' + $(this).val(),
					]);
				});
				return select_qty;
			}
			function get_item_price(pid) {
				let childs = " . $get_child_info . ';
				var prices = $.map(childs, function (item) {
					if (item.id == pid) {
						return item.price;
					}
				});
				return prices.length > 0 ? prices : null;
			}
			'
			);
		}
	}

	/**
	 * Get Child Product Info
	 *
	 * @param mixed $product This is product object.
	 *
	 * @return array
	 */
	private function retrieve_children_info( $product ) {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$child_products        = $product->get_children();
		$child_info            = array();
		foreach ( $child_products as $pid ) {
			$_product     = wc_get_product( $pid );
			$child_info[] = array(
				'price' => $setting_config_helper->format_amount( $_product->get_price() ),
				'id'    => $pid,
			);
		}
		return $child_info;
	}

	/**
	 * Load Widget
	 *
	 * @return mixed
	 */
	public function retrieve_widget_with_price() {
		global $woocommerce;
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$product_total         = 0;
		if ( $woocommerce->cart ) {
			$total         = $woocommerce->cart->get_totals();
			$product_total = $setting_config_helper->format_amount( $total['total'] );
		}
		return $this->retrieve_widgets( $product_total );
	}
	/**
	 * Add Price Tag for widget.
	 *
	 * @param int $price This is product's price.
	 *
	 * @return string
	 */
	private function retrieve_widgets( $price ) {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$enabled_widget_arr    = $setting_config_helper->fetch_unify_widget();
		if ( is_admin() && ! $enabled_widget_arr ) {
			return '';
		}
		$product_content = '<div class="sync-price"></div>
				<div id="product-content" class="syf_product_price" style="display:none">' . esc_html( $price ) . wp_kses_post( self::CLOSE_DIV );
		if ( in_array( 'all', $enabled_widget_arr, true ) ) {
			return $product_content;
		}
		if ( ( in_array( 'cart', $enabled_widget_arr, true ) && is_cart() ) || ( in_array( 'checkout', $enabled_widget_arr, true ) && is_checkout() ) || ( in_array( 'product', $enabled_widget_arr, true ) && is_product() ) ) {
			return $product_content;
		}
		return '';
	}

	/**
	 * Enqueue Widget Script on Synchrony payment page
	 *
	 * @return void
	 */
	public function unifi_widget_script() {
		$config_helper          = new Synchrony_Config_Helper();
		$setting_config_helper  = new Synchrony_Setting_Config_Helper();
		$common_config_helper   = new Synchrony_Common_Config_Helper();
		$partner_id             = $setting_config_helper->fetch_partner_id();
		$enabled_widget_arr     = $setting_config_helper->fetch_unify_widget();
		$is_synchrony_test_mode = $common_config_helper->does_test_mode();
		$unifi_endpoint         = $config_helper->fetch_api_endpoint( 'synchrony_test_unifi_script_endpoint', 'synchrony_deployed_unifi_script_endpoint', $is_synchrony_test_mode );
		$get_syf_unifi_base_url = $unifi_endpoint . 'mpp/UniFi.js';
		$get_tag_rules          = $setting_config_helper->fetch_tag_rules();
		$child_merchant_id      = $setting_config_helper->fetch_child_merchant_id();

		$this->product_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $get_tag_rules, $child_merchant_id );
		$this->listing_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $child_merchant_id );
		$this->cart_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $get_tag_rules, $child_merchant_id );
		$this->checkout_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $get_tag_rules, $child_merchant_id );

		if ( is_page( 'Synchrony Payment' ) ) {
			wp_enqueue_script( 'syfMPPScript', $get_syf_unifi_base_url, array(), self::SCRIPT_VERSION, false );
		}
		wp_enqueue_style( 'syfMPPstyle', plugin_dir_url( __FILE__ ) . '../../assets/css/style.css', array(), self::STYLE_VERSION, false );
		if ( $common_config_helper->fetch_syf_custom_css() ) {
			wp_register_style( 'syf-inline', false, array(), '1.0.0' );
			wp_enqueue_style( 'syf-inline' );
			wp_add_inline_style( 'syf-inline', $common_config_helper->fetch_syf_custom_css() );
		}
	}

	/**
	 * Load multi Widget script on list page
	 *
	 * @param string $partner_id This is partner id configured in admin.
	 * @param array  $enabled_widget_arr These are the widget's options selected from admin.
	 * @param string $get_syf_unifi_base_url This is unify script endpoint configured in admin.
	 * @param string $child_merchant_id child merchant id configured in admin.
	 *
	 * @return void
	 */
	public function listing_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $child_merchant_id ) {
		if ( $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'plp', $enabled_widget_arr, true ) ) && ( is_shop() || is_product_category() || is_search() ) ) {
			wp_enqueue_script( 'syfMPPScript', $get_syf_unifi_base_url, array(), self::SCRIPT_VERSION, false );
			wp_add_inline_script( 'syfMPPScript', 'var syfWidgetObject={}; syfWidgetObject.syfPartnerId = "' . $partner_id . '"; syfWidgetObject.childSyfMerchantNumber = "' . $child_merchant_id . '"; syfWidgetObject.flowType = "MULTIWIDGET"; window["syfWidgetObject"] = syfWidgetObject;', 'before' );
			wp_enqueue_script( 'syfMPPCustom', $this->synchrony_cart_script, array(), self::SCRIPT_VERSION, true );
		} elseif ( $this->widget_helper->enable_mppbanner() && ! is_product() && ! is_cart() && ! is_checkout() && $partner_id ) {
			wp_enqueue_script( 'syfMPPScript', $get_syf_unifi_base_url, array(), self::SCRIPT_VERSION, false );
			wp_add_inline_script( 'syfMPPScript', 'var syfWidgetObject={}; syfWidgetObject.syfPartnerId = "' . $partner_id . '"; syfWidgetObject.childSyfMerchantNumber = "' . $child_merchant_id . '";', 'before' );
			wp_enqueue_script( 'syfMPPCustom', $this->synchrony_cart_script, array(), self::SCRIPT_VERSION, true );
		}
	}

	/**
	 * Enqueue Cart Page Script.
	 *
	 * @param string $partner_id This is partner id configured in admin.
	 * @param array  $enabled_widget_arr These are the widget's options selected from admin.
	 * @param string $get_syf_unifi_base_url This is unify script endpoint configured in admin.
	 * @param int    $get_tag_rules This is tag rules.
	 * @param string $child_merchant_id child merchant id configured in admin.
	 *
	 * @return void
	 */
	public function cart_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $get_tag_rules, $child_merchant_id ) {
		if ( is_cart() && $partner_id && ( ( $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'cart', $enabled_widget_arr, true ) ) ) || $this->widget_helper->enable_mppbanner() ) ) {
			$tag_config        = new Synchrony_Promotag_Configurator();
			$product_attribute = $tag_config->retrieve_cart_product_data();
			wp_enqueue_script( 'syfMPPScript', $get_syf_unifi_base_url, array(), self::SCRIPT_VERSION, false );
			wp_add_inline_script( 'syfMPPScript', 'var syfWidgetObject={}; syfPlatform = "WOOCOMMERCE"; syfWidgetObject.syfPartnerId = "' . $partner_id . '"; syfWidgetObject.childSyfMerchantNumber = "' . $child_merchant_id . '"; ' . ( ( 1 === $get_tag_rules && $product_attribute ) ? self::SYNCHRONY_WIDGET_OBJECT_PA . $product_attribute . ';' : '' ) . ' syfWidgetObject.flowType = "CART";', 'before' );
			wp_enqueue_script( 'syfMPPCustom', $this->synchrony_cart_script, array(), self::SCRIPT_VERSION, true );
		}
	}
	/**
	 * Enqueue PDP Page Script.
	 *
	 * @param string $partner_id This is partner id configured in admin.
	 * @param array  $enabled_widget_arr These are the widget's options selected from admin.
	 * @param string $get_syf_unifi_base_url This is unify script endpoint configured in admin.
	 * @param int    $get_tag_rules This is tag rules.
	 * @param string $child_merchant_id child merchant id configured in admin.
	 *
	 * @return void
	 */
	public function product_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $get_tag_rules, $child_merchant_id ) {
		if ( is_product() && $partner_id && ( ( $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'product', $enabled_widget_arr, true ) ) ) || $this->widget_helper->enable_mppbanner() ) ) {
			$tag_config        = new Synchrony_Promotag_Configurator();
			$product_attribute = $tag_config->retrieve_product_data();
			wp_enqueue_script( 'syfMPPScript', $get_syf_unifi_base_url, array(), self::SCRIPT_VERSION, false );
			wp_add_inline_script( 'syfMPPScript', 'var syfWidgetObject={}; syfPlatform = "WOOCOMMERCE"; syfWidgetObject.syfPartnerId = "' . $partner_id . '"; syfWidgetObject.childSyfMerchantNumber = "' . $child_merchant_id . '"; ' . ( ( 1 === $get_tag_rules && $product_attribute ) ? self::SYNCHRONY_WIDGET_OBJECT_PA . $product_attribute . ';' : '' ) . ' syfWidgetObject.flowType = "PDP";', 'before' );
			wp_enqueue_script( 'syfMPPCustom', $this->synchrony_cart_script, array(), self::SCRIPT_VERSION, true );
		}
	}

	/**
	 * Enqueue Checkout Page Script
	 *
	 * @param string $partner_id This is partner id configured in admin.
	 * @param array  $enabled_widget_arr These are the widget's options selected from admin.
	 * @param string $get_syf_unifi_base_url This is unify script endpoint configured in admin.
	 * @param int    $get_tag_rules This is tag rules.
	 * @param string $child_merchant_id child merchant id configured in admin.
	 *
	 * @return void
	 */
	public function checkout_page_script( $partner_id, $enabled_widget_arr, $get_syf_unifi_base_url, $get_tag_rules, $child_merchant_id ) {
		if ( is_checkout() && $partner_id && ( ( $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'checkout', $enabled_widget_arr, true ) ) ) || $this->widget_helper->enable_mppbanner() ) ) {
			$tag_config        = new Synchrony_Promotag_Configurator();
			$product_attribute = $tag_config->retrieve_cart_product_data();
			wp_enqueue_script( 'syfMPPScript', $get_syf_unifi_base_url, array(), self::SCRIPT_VERSION, false );
			wp_add_inline_script( 'syfMPPScript', ' var syfWidgetObject={}; syfWidgetObject.syfPartnerId = "' . $partner_id . '"; syfWidgetObject.childSyfMerchantNumber = "' . $child_merchant_id . '"; ' . ( ( 1 === $get_tag_rules && $product_attribute ) ? self::SYNCHRONY_WIDGET_OBJECT_PA . $product_attribute . ';' : '' ) . ' syfWidgetObject.flowType = "PDP";', 'before' );
			wp_enqueue_script( 'syfMPPCustom', $this->synchrony_cart_script, array(), self::SCRIPT_VERSION, true );
		}
	}

	/**
	 * Get Lowest Price Of Grouped Child Product
	 *
	 * @param mixed $product This is product object.
	 *
	 * @return int
	 */
	public function retrieve_child_product_price( $product ) {
		$group_child = $this->retrieve_children_info( $product );
		foreach ( $group_child as $value ) {
			$child_price[] = $value['price'];
		}
		return min( $child_price );
	}

	/**
	 * Get the multiwidget everywhere
	 *
	 * @return string
	 */
	public function custom_multi_widget() {
		$setting_config_helper  = new Synchrony_Setting_Config_Helper();
		$get_tag_rules          = $setting_config_helper->fetch_tag_rules();
		$enabled_widget_arr     = $setting_config_helper->fetch_unify_widget();
		$default_variable_price = $this->widget_helper->fetch_mw_variable_option();
		$common_config_helper   = new Synchrony_Common_Config_Helper();
		$is_activation_enabled  = $common_config_helper->fetch_activation_enable_flag();

		if ( ! is_admin() && 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			$html = '';
			if ( 'yes' === $is_activation_enabled ) {
				return $html;
			}
			global $product;
			$tag_config      = new Synchrony_Promotag_Configurator();
			$widget_cache    = new Synchrony_List_Product_Cache();
			$current_product = get_the_ID();
			$cache_key       = 'synchrony_digitalbuy_Tag_' . $current_product;
			$cache_tag_array = $tag_config->retrieve_cache( $cache_key );
			$promo_elemet_id = 'syf-tags';
			$tags            = '';
			if ( ! empty( $cache_tag_array ) ) {
				if ( 1 === $widget_cache->retrieve_widget_cache_method() ) {
					$cache_tag      = $cache_tag_array['final_tag_list'];
					$cache_tag_type = $cache_tag_array['type'];
				} elseif ( 2 === $widget_cache->retrieve_widget_cache_method() ) {
					$cache_tag      = $cache_tag_array[ $current_product ]['final_tag_list'];
					$cache_tag_type = $cache_tag_array[ $current_product ]['type'];
				}

				if ( isset( $cache_tag_type ) && 'promos' === $cache_tag_type ) {
					$promo_elemet_id = 'syf-promos';
				}

				$tag_approach_config = $widget_cache->retrieve_widget_cache_method();
				$tags                = $this->retrieve_tags( $cache_tag, $tag_approach_config );
			}
			$product_price = $setting_config_helper->format_amount( $product->get_price() );
			// For variation product.
			if ( 'variable' === $product->get_type() && 1 === intval( $default_variable_price ) ) {
				$product_price = $this->retrieve_default_product_price( $product );
			}
			if ( $enabled_widget_arr && ( in_array( 'all', $enabled_widget_arr, true ) || in_array( 'plp', $enabled_widget_arr, true ) ) ) {
				if ( 1 === $get_tag_rules ) {
					$html .= '<div id="mproduct-price" style="display:none">$' . esc_html( $product_price ) . self::CLOSE_DIV;
					$html .= '<div style="display:none" id="' . $promo_elemet_id . '">' . esc_html( $tags ) . self::CLOSE_DIV;
					$html .= '<div  class="mproduct-widget"></div>';
				} else {
					$html .= '<div id="mproduct-price" style="display:none">$' . esc_html( $product_price ) . self::CLOSE_DIV;
					$html .= '<div style="display:none" id="' . $promo_elemet_id . '"></div>';
					$html .= '<div  class="mproduct-widget"></div>';
				}
			}
			return $html;
		}
	}

	/**
	 * Get the multiwidget variable Price
	 *
	 * @param mixed $product This is product object.
	 *
	 * @return string
	 */
	public function retrieve_default_product_price( $product ) {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$price                 = $product->get_price();
		$is_default_variation  = false;
		foreach ( $product->get_available_variations() as $variation_values ) {
			foreach ( $variation_values['attributes'] as $key => $attribute_value ) {
				$attribute_name = str_replace( 'attribute_', '', $key );
				$default_value  = $product->get_variation_default_attribute( $attribute_name );
				if ( $default_value === $attribute_value ) {
					$is_default_variation = true;
				} else {
					$is_default_variation = false;
					break; // Stop this loop to start next main loop.
				}
			}
			if ( $is_default_variation ) {
				$variation_id = $variation_values['variation_id'];
				break; // Stop the main loop.
			}
		}
		// Now we get the default variation data.
		if ( $is_default_variation ) {
			$default_variation = wc_get_product( $variation_id );
			// Get The active price.
			$price = $default_variation->get_price();
		}
		return $setting_config_helper->format_amount( $price );
	}
	/**
	 * Get Refresh Token after the expiry.
	 *
	 * @return void
	 */
	public function retrieve_smb_auth_token() {
		$common_config_helper = new Synchrony_Common_Config_Helper();
		$is_activation_enable = $common_config_helper->fetch_activation_enable_flag();
		if ( 'yes' === $is_activation_enable ) {
			$client_smb = new Synchrony_Smb();
			$client_smb->retrieve_smb_access_token();
		}
	}
}
