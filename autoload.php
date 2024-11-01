<?php
/**
 * Autoload Script.
 *
 * @package Synchrony\Payments\Autoload.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	function ( $callback ) {
		$prefix   = 'Synchrony\\Payments\\';
		$base_dir = __DIR__ . '/src/';
		$len      = strlen( $prefix );
		if ( strncmp( $prefix, $callback, $len ) !== 0 ) {
			return;
		}
		$relative_class = substr( $callback, $len );

		$parts = explode( '\\', $relative_class );

		foreach ( $parts as &$part ) {
			$part = str_replace( '_', '-', $part );
			$part = strtolower( $part );
		}

		$parts = implode( '/class-', $parts );

		$file = $base_dir . str_replace( '\\', '/', $parts ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);
