<?php
/**
 * Tag Configurator Admin
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Client;

/**
 * Class Synchrony_Promotag_Config
 */
class Synchrony_Promotag_Config {

	/**
	 * Add Order data and formatting
	 *
	 * @param mixed $post_data This is order data.
	 * @param mixed $order This order object.
	 *
	 * @return array
	 */
	public function promo_tag_post_data( $post_data, $order ) {
		global $woocommerce;
		$data                  = array();
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		if ( $setting_config_helper->fetch_tag_rules() === 1 ) {
			$products       = array();
			$client         = new Synchrony_Client();
			$tag_attributes = $client->retrieve_product_attributes();
			$items          = $order->get_items();
			if ( ! empty( $tag_attributes ) ) {
				foreach ( $items as $values ) {
					$product               = array();
					$product_obj           = $values->get_product();
					$product['item_name']  = $product_obj->get_name();
					$product['item_price'] = $setting_config_helper->format_amount( $product_obj->get_price() * $values['quantity'] );

					foreach ( $tag_attributes as $att_code ) {
						$att_code_value = null;
						$att_code_value = $this->retrieve_product_attribute_label_by_value( $att_code, $product_obj );
						if ( ! empty( $att_code_value ) ) {
							$product[ $att_code ] = $att_code_value;
						}
					}
					$products[ $product_obj->get_id() ] = $product;
				}
			}
			$data = $this->prepare_promo_tag_products( $products );
		}
		return $this->filter_post_data( $data, $post_data );
	}

	/**
	 * Filter data by ID
	 *
	 * @param mixed $promo_data This is promo data.
	 * @param mixed $post_data This Order data.
	 *
	 * @return array
	 */
	public function filter_post_data( $promo_data, $post_data ) {
		if ( $promo_data ) {
			$promo_tag = json_decode( $promo_data, true );
			if ( isset( $promo_tag['products'] ) && ! empty( $promo_tag['products'] ) ) {
				$promo_tag_products = $promo_tag['products'];
				foreach ( $promo_tag_products as $key => $promo_tag_products_item ) {
					$promo_tag_products[ $key ]['productId'] = $promo_tag_products_item['id'];
					unset( $promo_tag_products[ $key ]['id'] );
					unset( $promo_tag_products[ $key ]['description'] );
				}
				$post_data['transactionInfo']['products'] = $promo_tag_products;
			}
		}
		return $post_data;
	}

	/**
	 * Get Product Attribute Value
	 *
	 * @param mixed $attribute_code This is attribute name.
	 * @param mixed $product This is product object.
	 *
	 * @return string
	 */
	public function retrieve_product_attribute_label_by_value( $attribute_code, $product ) {
		global $wpdb;
		$get_data        = $product->get_data();
		$attribute_value = '';
		if ( isset( $get_data[ $attribute_code ] ) ) {
			$attribute_value = $get_data[ $attribute_code ];
		} else {
			$attribute_value      = '';
			$attribute_taxonomies = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"
					SELECT attribute_name 
					FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
					WHERE attribute_name = %s",
					$attribute_code
				)
			);
			if ( isset( $attribute_taxonomies->attribute_name ) ) {
				$attribute_value = $product->get_attribute( $attribute_code );
			}
		}
		return $attribute_value;
	}

	/**
	 * Formating product data array
	 *
	 * @param mixed $products This is product array.
	 *
	 * @return string
	 */
	public function prepare_promo_tag_products( $products ) {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$products_data         = array();
		$products_data_json    = null;
		if ( empty( $products ) && count( $products ) <= 0 ) {
			return $products_data_json;
		}
		$inc = 0;
		foreach ( $products as $id => $product ) {
			$product_attributes = array();
			if ( is_array( $product ) && ! empty( $product ) ) {
				$product_attributes = $this->assign_rules_values( $product );
			}
			$products_data['products'][ $inc ]['id']                = (string) $id;
			$products_data['products'][ $inc ]['price']             = $setting_config_helper->format_amount( $product['item_price'] );
			$products_data['products'][ $inc ]['description']       = $product['item_name'];
			$products_data['products'][ $inc ]['productAttributes'] = $product_attributes;
			++$inc;
		}
		return wp_json_encode( $products_data );
	}

	/**
	 * Get product attribute values
	 *
	 * @param mixed $product This is product attribute list.
	 *
	 * @return array
	 */
	public function assign_rules_values( $product ) {
		$product_attributes = array();
		$inc                = 0;
		foreach ( $product as $att_code => $attval ) {
			if ( 'item_price' === $att_code || 'item_name' === $att_code ) {
				continue;
			}
			$product_attributes[ $inc ]['name'] = $att_code;
			$attval_final                       = '';
			if ( is_array( $attval ) ) {
				$attval_final = implode( ',', $attval );
			} else {
				$attval_final = $attval;
			}
			$product_attributes[ $inc ]['value'] = $attval_final;
			++$inc;
		}
		return $product_attributes;
	}
}
