<?php
/**
 * Class Http
 *
 * @package Synchrony\Payments\Gateway
 */

namespace Synchrony\Payments\Gateway;

use Synchrony\Payments\Logs\Synchrony_Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class Http
 */
class Synchrony_Http extends Synchrony_Abstract_Transport {

	/**
	 * Submit POST request
	 *
	 * @param array|string $post_data This is Post data.
	 * @param string       $send_as_json This is for send json.
	 * @param  mixed        $headers This is for headers.
	 * @param  string       $method This is for method.
	 * @return mixed
	 */
	public function post( $post_data, $send_as_json = 'no', $headers = null, $method = 'POST' ) {
		if ( 'yes' === $send_as_json ) {
			$post_data = is_array( $post_data ) ? wp_json_encode( $post_data ) : $post_data;
			$headers[] = 'Content-Type: application/json; charset=utf-8';
		} else {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			$post_data = ! is_array( $post_data ) ? $post_data : http_build_query( $post_data );
		}
		$request_data             = array(
			'method'     => $method,
			'timeout'    => $this->timeout,
			'headers'    => $this->format_headers( $headers ),
			'body'       => $post_data,
			'user-agent' => 'curl',
		);
		$response                 = wp_remote_post(
			$this->url,
			$request_data
		);
		$this->last_response_code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$logger        = new Synchrony_Logger();
			$logger->debug( 'Error in POST Api: ' . $error_message );
			$result = null;
		} else {
			$result = wp_remote_retrieve_body( $response );
		}
		return $result;
	}

	/**
	 * Submit Get Request.
	 *
	 * @param mixed $headers This is for headers.
	 * @return mixed.
	 */
	public function get( $headers = null ) {
		$request_data = array(
			'method'     => 'GET',
			'timeout'    => $this->timeout,
			'headers'    => $this->format_headers( $headers ),
			'user-agent' => 'curl',
		);
		$response     = wp_remote_post(
			$this->url,
			$request_data
		);

		$this->last_response_code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$logger        = new Synchrony_Logger();
			$logger->debug( 'Error in GET Api: ' . $error_message );
			$result = null;
		} else {
			$result = wp_remote_retrieve_body( $response );
		}
		return $result;
	}

	/**
	 * Format header to array format.
	 *
	 * @param mixed $headers This is for headers.
	 * @return array
	 */
	public function format_headers( $headers = null ) {
		$final_array = array();
		if ( ! is_null( $headers ) ) {
			foreach ( $headers as $header ) {
				$header_arr                    = explode( ':', $header );
				$final_array[ $header_arr[0] ] = $header_arr[1];
			}
		}
		return $final_array;
	}
}
