<?php
/** Synchrony_Abstract_Transport
 *
 * @package Synchrony\Payments\Gateway
 */

namespace Synchrony\Payments\Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Class Synchrony_Abstract_Transport
 */
abstract class Synchrony_Abstract_Transport {

	/**
	 * Request URL
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Http Auth Username
	 *
	 * @var string
	 */
	protected $auth_username;

	/**
	 * Http Auth Password
	 *
	 * @var string
	 */
	protected $auth_password;

	/**
	 * Request body
	 *
	 * @var string
	 */
	protected $post_data;

	/**
	 * Request timeout
	 *
	 * @var int
	 */
	protected $timeout;

	/**
	 * Response body
	 *
	 * @var string
	 */
	protected $last_response_body;

	/**
	 * Request error
	 *
	 * @var string
	 */
	protected $last_error;

	/**
	 * Response Code
	 *
	 * @var string
	 */
	protected $last_response_code;

	/**
	 * Request error
	 * Description:
	 * - proxy_host: key which holds API proxy host url
	 * - proxy_port: key which holds API proxy port
	 *
	 * @var array
	 */
	protected $proxy = array(
		'proxy_host' => '',
		'proxy_port' => '',
	);

	/**
	 * Http constructor.
	 *
	 * @param string $url This is for Url.
	 * @param int    $timeout This is for timeout.
	 * @param array  $proxy This is for proxy.
	 */
	public function __construct( $url, $timeout = 5, $proxy = null ) {
		$this->url     = $url;
		$this->timeout = (int) $timeout;
		if ( is_array( $proxy ) && ! empty( $proxy ) ) {
			$this->proxy = array_merge( $this->proxy, $proxy );
		}
	}

	/**
	 * Get response body
	 *
	 * @return string|null
	 */
	public function retrieve_last_response_code() {
		return $this->last_response_code;
	}

	/**
	 * Submit POST request
	 *
	 * @param mixed $post_data This is for post data.
	 * @return mixed
	 */
	abstract public function post( $post_data );
}
