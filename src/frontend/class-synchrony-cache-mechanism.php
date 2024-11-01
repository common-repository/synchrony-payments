<?php
/**
 * Synchrony Cache Mechanism
 *
 * @package Synchrony\Payments\Synchrony_Cache_Mechanism
 */

namespace Synchrony\Payments\Frontend;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Admin\Synchrony_List_Product_Cache;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Gateway\Synchrony_Client;

/**
 * Class Synchrony_Cache_Mechanism
 */
class Synchrony_Cache_Mechanism {

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
	 * Define Init to add woocommerce hooks
	 */
	public function __construct() {
		$this->logger = new Synchrony_Logger();
		$this->client = new Synchrony_Client();
	}

	/**
	 * Function will check the loaded product on the current page
	 *
	 * @return array
	 */
	public function retrieve_query_ids() {
		global $wp_query;
		$result             = array();
		$products           = array();
		$product_attributes = $this->client->retrieve_product_attributes();
		if ( ! empty( $product_attributes ) ) {
			$post_ids = wp_list_pluck( $wp_query->posts, 'ID' );
			foreach ( $post_ids as $id ) {
				if ( 'product' === get_post_type( $id ) ) {
					array_push( $products, $id );
				}
			}
			$result['product_attributes'] = $product_attributes;
			$result['products']           = $products;

		}
		return $result;
	}
}
