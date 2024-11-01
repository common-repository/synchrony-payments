<?php
/**
 *  List Product Cache
 *
 * @package Synchrony\Payments\Admin
 */

declare(strict_types=1);

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Admin\Synchrony_Promotag_Configurator;
use Synchrony\Payments\Frontend\Synchrony_Promotag_Config;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Logs\Synchrony_Logger;
use WC_Product_Query;
use Synchrony\Payments\Gateway\Synchrony_Payment;

/**
 * Class Synchrony_List_Product_Cache
 */
class Synchrony_List_Product_Cache extends Synchrony_Payment {

	/**
	 * Setting_Config_Helper
	 *
	 * @var Synchrony_Setting_Config_Helper $setting_config_helper
	 */
	private $setting_config_helper;

	/**
	 * Promotag_Configurator
	 *
	 * @var Synchrony_Promotag_Configurator $tag_configurator
	 */
	private $tag_configurator;

	/**
	 * Promotag_Config
	 *
	 * @var Synchrony_Promotag_Config $tag_config
	 */
	private $tag_config;

	/**
	 * ENABLE_SYNCHRONY_PROMOTIONS
	 *
	 * @var Synchrony_Logger $logger
	 */
	private $logger;

	/**
	 * Client
	 *
	 * @var Synchrony_Client $client
	 */
	private $client;

	/**
	 * TYPE_IDENTIFIER
	 *
	 * @var string
	 */
	const TYPE_IDENTIFIER = 'synchrony_digitalbuy';
	/**
	 * CACHE_TAG
	 *
	 * @var string
	 */
	const CACHE_TAG = 'promo_tag';
	/**
	 * TAG
	 *
	 * @var string
	 */
	public const TAG = '_Tag_';
	/**
	 * MULTI_WIDGET_CACHE
	 *
	 * @var string
	 */
	public const MULTI_WIDGET_CACHE = 'MultiWidgetRemoteCache: ';
	/**
	 * Loads cache mechanism
	 *
	 * @param array $product_ids This is having list of products.
	 * @param array $product_attributes It holds the attribute api response.
	 *
	 * @return void
	 */
	public function load_cache_mechanism( $product_ids = array(), $product_attributes = array() ) {
		global $woocommerce;
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$this->tag_configurator      = new Synchrony_Promotag_Configurator();
		$this->tag_config            = new Synchrony_Promotag_Config();
		$this->logger                = new Synchrony_Logger();
		$this->client                = new Synchrony_Client();
		$get_tag_rules               = $this->setting_config_helper->fetch_tag_rules();
		if ( 1 === $get_tag_rules ) {

			$product_array = array();
			if ( ! empty( $product_ids ) ) {
				$cache_key = self::TYPE_IDENTIFIER;
				foreach ( $product_ids as $product_id ) {
					$final_cache_key = $cache_key . self::TAG . $product_id;
					$get_cache_tags  = $this->tag_configurator->retrieve_cache( $final_cache_key );
					if ( empty( $get_cache_tags ) ) {
						$product_array[] = $product_id;
					}
				}

				$this->after_get_loaded_product_collection( $product_array, $product_attributes );
			}
		}
	}

	/**
	 * After get loaded product collection.
	 *
	 * @param mixed $products This is product object.
	 * @param mixed $product_attributes It holds the attribute api response.
	 *
	 * @return void
	 */
	public function after_get_loaded_product_collection( $products = '', $product_attributes = '' ) {

		if ( ! empty( $this->setting_config_helper->fetch_partner_id() ) && $this->setting_config_helper->fetch_tag_rules() === 1 ) {
			$tag_approach_config = $this->retrieve_widget_cache_method();
			$cache_key           = self::TYPE_IDENTIFIER;
			$cache_tag           = self::CACHE_TAG;
			if ( 1 === $tag_approach_config ) {
				$this->retrieve_approach_one_option( $products, $cache_key, $cache_tag, $product_attributes );
			} elseif ( 2 === $tag_approach_config ) {
				$this->retrieve_approach_two_option( $products, $cache_key, $cache_tag, $product_attributes );

			}
		}
	}

	/**
	 * Approach Remote Rule Execution.
	 *
	 * @param mixed  $result This is result.
	 * @param string $cache_key This is cache_key.
	 * @param mixed  $cache_tag This is cache_tag.
	 * @param mixed  $tag_attributes It holds the attribute api response.
	 *
	 * @return bool
	 * @throws \Exception - If an Exception occurs during the request.
	 */
	public function retrieve_approach_one_option( $result, $cache_key, $cache_tag, $tag_attributes ) {
		try {
			$product_id_array = $this->retrieve_product_id_array( $result, $cache_key );
			if ( empty( $product_id_array ) ) {
				return true;
			}
			$each_product_array = array();
			$product            = array();
			$att_code_array     = array();
			$att_code_array     = $this->attribute_code_array( $tag_attributes );

			foreach ( $product_id_array as $each ) {
				$product_details                         = wc_get_product( $each );
				$product_attribute                       = array();
				$product_attribute                       = $this->retrieve_product_attributes( $att_code_array, $product_details );
				$product_attribute[]                     = array(
					'name'  => 'item_name',
					'value' => $product_details->get_name(),
				);
				$price                                   = strval( $this->setting_config_helper->format_amount( $product_details->get_price() ) );
				$product_attribute[]                     = array(
					'name'  => 'item_price',
					'value' => $price,
				);
				$each_product_array['id']                = strval( $each );
				$each_product_array['productAttributes'] = $product_attribute;
				$product['products'][]                   = $each_product_array;
			}

			$tag_determination_result = $this->client->retrieve_tag_determination( $product );
			if ( ! is_array( $tag_determination_result ) && '' !== $tag_determination_result && ! empty( $tag_determination_result ) ) {
				$tag_determination_result = json_decode( $tag_determination_result );
			}
			$this->tag_determination_result( $tag_determination_result, $cache_key );
		} catch ( \Exception $e ) {
			$this->logger->debug( 'MultiWidgetRemoteCache: Unable to retrieve Tag  ' . $e->getMessage() );
		}
		return true;
	}

	/**
	 * Tag determination result.
	 *
	 * @param array  $tag_determination_result This is tag_determination_result.
	 * @param string $cache_key This is cache_key.
	 *
	 * @return void
	 */
	public function tag_determination_result( $tag_determination_result, $cache_key ) {
		if ( $tag_determination_result ) {
			foreach ( $tag_determination_result as $each_result ) {

				$promo_tags = $this->tag_configurator->check_ruleset_params( $each_result, 'tags', 'promos' );
				if ( isset( $promo_tags ) ) {
					$final_tag = '';
					$final_key = $cache_key . self::TAG . $each_result['id'];
					$this->save_cache( $promo_tags, $final_key, $final_tag );
				}
			}
		}
	}

	/**
	 * Get attribute code array.
	 *
	 * @param array $tag_attributes This is tag_attributes.
	 *
	 * @return array
	 */
	public function attribute_code_array( $tag_attributes ) {
		$att_code_array = array();
		if ( ! empty( $tag_attributes ) && count( $tag_attributes ) > 0 ) {
			foreach ( $tag_attributes as $att_code ) {
				$att_code_array[] = $att_code;
			}
		}
		return $att_code_array;
	}

	/**
	 * Get Product attributes
	 *
	 * @param array $att_code_array This is att_code_array.
	 * @param mixed $product_details This is product_details.
	 *
	 * @return array
	 */
	public function retrieve_product_attributes( $att_code_array, $product_details ) {
		$product_attribute = array();
		foreach ( $att_code_array as $att_code ) {
			$att_code_value = $this->tag_config->retrieve_product_attribute_label_by_value( $att_code, $product_details );
			if ( ! empty( $att_code_value ) ) {
				$product_attribute[] = array(
					'name'  => $att_code,
					'value' => $att_code_value,
				);
			}
		}
		return $product_attribute;
	}

	/**
	 * Get Product Details
	 *
	 * @param mixed  $result This is result.
	 * @param string $cache_key This is cache_key.
	 *
	 * @return array
	 */
	public function retrieve_product_id_array( $result, $cache_key ) {
		$product_id_array = array();
		foreach ( $result as $each ) {
			$finalcache_key           = $cache_key . self::TAG . $each;
			$tag_determination_result = $this->tag_configurator->retrieve_cache( $finalcache_key );
			if ( empty( $tag_determination_result ) ) {
				$product_id_array[] = $each;
			}
		}
		return $product_id_array;
	}
	/**
	 * Approach Local Rule Execution.
	 *
	 * @param mixed  $result This is result.
	 * @param string $cache_key This is cache_key.
	 * @param mixed  $cache_tag This is cache_tag.
	 * @param mixed  $tag_attributes It holds the attribute api response.
	 *
	 * @return bool
	 * @throws \Exception - If an Exception occurs during the request.
	 */
	public function retrieve_approach_two_option( $result, $cache_key, $cache_tag, $tag_attributes ) {
		try {
			$product_id_array = array();
			foreach ( $result as $each ) {
				$finalcache_key = $cache_key . self::TAG . $each;

				$product_tag_cache = $this->tag_configurator->retrieve_cache( $finalcache_key );
				if ( empty( $product_tag_cache[ $each ] ) ) {
					$product_id_array[] = $each;
				}
			}
			if ( empty( $product_id_array ) ) {
				return true;
			}
			$rules_set = $this->client->multiwidretrieve_rulesets();
			foreach ( $product_id_array as $each ) {
				$product_id_list     = array();
				$product_attributes  = $this->tag_configurator->retrieve_product_data( $each );
				$rules_engine_custom = $this->tag_configurator->retrieve_determine_tags( $rules_set, $product_attributes );
				if ( ! empty( $rules_engine_custom ) ) {
					$product_id_list[ $each ] = $rules_engine_custom;
				}
				$finalcache_key = $cache_key . self::TAG . $each;
				$finalcache_tag = '';
				// synchrony_digitalbuy_Tag_41.
				if ( ! empty( $product_id_list ) ) {
					$this->save_cache( $product_id_list, $finalcache_key, $finalcache_tag );
				}
			}
		} catch ( \Exception $e ) {
			$this->logger->debug( 'MultiWidgetLocalCache: Unable to retrieve Tag approach 2 : ' . $e->getMessage() );
		}
		return true;
	}

	/**
	 * Save Cache
	 *
	 * @param array  $cache_data this is for cache data.
	 * @param string $cache_key this is for cache key.
	 *
	 * @return void
	 */
	public function save_cache( $cache_data = array(), $cache_key = 'syf_cache_method_1' ) {
		if ( ! empty( $cache_key ) && ! empty( $cache_data ) ) {
			$serialized_cache = maybe_serialize( $cache_data );
			set_transient( $cache_key, $serialized_cache, $this->setting_config_helper->fetch_cache_timeout() );
		}
	}
	/**
	 * Get Widget Cache
	 *
	 * @return mixed
	 */
	public function retrieve_widget_cache_method() {
		$this->setting_config_helper = new Synchrony_Setting_Config_Helper();
		$approach                    = $this->setting_config_helper->fetch_widget_display_approach();
		if ( $approach ) {
			return $approach;
		} else {
			return false;
		}
	}
}
