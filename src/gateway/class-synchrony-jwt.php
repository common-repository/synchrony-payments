<?php
/**
 * This file contains the logic to encrypt or decrypt the jwt token.
 *
 * @package Synchrony\Payments\Gateway
 */

namespace Synchrony\Payments\Gateway;

defined( 'ABSPATH' ) || exit;

use ArrayAccess;
use DateTime;
use DomainException;
use Exception;
use InvalidArgumentException;
use OpenSSLCertificate;
use stdClass;
use UnexpectedValueException;
use Synchrony\Payments\Logs\Synchrony_Logger;

/**
 * Class Synchrony_Jwt
 * This class encrypts or decrypts the jwt token.
 */
class Synchrony_Jwt {

	/**
	 * When checking nbf, iat or expiration times,
	 * we want to provide some extra leeway time to
	 * account for clock skew.
	 *
	 * @var int
	 */
	public static $leeway = 0;

	/**
	 * Allow the current timestamp to be specified.
	 * Useful for fixing a value within unit testing.
	 * Will default to PHP time() value if null.
	 *
	 * @var ?int
	 */
	public static $timestamp = null;

	/**
	 * Supported Algorithm
	 *
	 * @var array<string, string[]>
	 */
	public static $supported_algs = array(
		'RS256' => array( 'openssl', 'SHA256' ),
	);

	/**
	 * DateTime
	 *
	 * @var DateTime
	 */
	protected $date;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * JWT Constructor
	 */
	public function __construct() {
		$this->date   = new DateTime();
		$this->logger = new Synchrony_Logger();
	}

	/**
	 * Decodes a JWT string into a PHP object.
	 *
	 * @param string                                        $jwt            The JWT Token.
	 * @param Key|ArrayAccess<string,Key>|array<string,Key> $key  The Key or associative array of key IDs
	 *                                                                      (kid) to Key objects.
	 *                                                                      If the algorithm used is asymmetric, this is
	 *                                                                      the public key.
	 *                                                                      Each Key object contains an algorithm and
	 *                                                                      matching key.
	 *                                                                      Supported algorithms are 'ES384','ES256',
	 *                                                                      'HS256', 'HS384', 'HS512', 'RS256', 'RS384'
	 *                                                                      and 'RS512'.
	 * @param stdClass                                      $headers                               Optional. Populates stdClass with headers.
	 *
	 * @return stdClass The JWT's payload as a PHP object.
	 *
	 * @throws InvalidArgumentException     Provided key/key-array was empty or malformed.
	 * @throws UnexpectedValueException     Provided JWT was invalid.
	 * @throws Exception                    Exceptions Errors.
	 *
	 * @uses decode_string
	 * @uses urlsafe_b64_decode
	 */
	public static function get_decoded_partner_id(
		string $jwt,
		string $key,
		stdClass &$headers = null
	) {
		// Validate JWT.
		$timestamp = strtotime( current_time( 'mysql', 1 ) );

		if ( empty( $key ) ) {
			throw new InvalidArgumentException( 'Key may not be empty' );
		}
		$tks = \explode( '.', $jwt );
		if ( \count( $tks ) !== 3 ) {
			throw new UnexpectedValueException( 'Wrong number of segments' );
		}
		list($headb64, $bodyb64, $cryptob64) = $tks;
		$header_raw                          = static::urlsafe_b64_decode( $headb64 );
		$header                              = static::decode_string( $header_raw );
		if ( null === $header ) {
			throw new UnexpectedValueException( 'Invalid header encoding' );
		}

		if ( null !== $headers ) {
			$headers = $header;
		}
		$payload_raw = static::urlsafe_b64_decode( $bodyb64 );
		$payload     = static::decode_string( $payload_raw );
		if ( null === $payload ) {
			throw new UnexpectedValueException( 'Invalid claims encoding' );
		}

		if ( \is_array( $payload ) ) {
			// prevent PHP Fatal Error in edge-cases when payload is empty array.
			$payload = (object) $payload;
		}
		if ( ! $payload instanceof stdClass ) {
			throw new UnexpectedValueException( 'Payload must be a JSON object' );
		}
		$sig = static::urlsafe_b64_decode( $cryptob64 );

		if ( empty( $header->alg ) ) {
			throw new UnexpectedValueException( 'Empty algorithm' );
		}

		if ( empty( static::$supported_algs[ $header->alg ] ) ) {
			throw new UnexpectedValueException( 'Algorithm not supported' );
		}
		$public_key = self::get_key( $key );

		if ( ! self::verify( "{$headb64}.{$bodyb64}", $sig, $public_key, $header->alg ) ) {
			throw new UnexpectedValueException( 'Signature verification failed' );
		}

		// Check the nbf if it is defined. This is the time that the token can actually be used. If it's not yet that time, abort.
		if ( isset( $payload->nbf ) && floor( $payload->nbf ) > ( $timestamp + static::$leeway ) ) {
			throw new Exception( sprintf( 'Cannot handle token with nbf with server time %s, Payload nbf: %s', esc_html( current_time( 'mysql', 1 ) ), esc_html( $payload->nbf ) ) );
		}

		// Check if this token has expired.
		if ( isset( $payload->exp ) && ( $timestamp - static::$leeway ) >= $payload->exp ) {
			throw new Exception( 'Expired token' );
		}
		return array(
			'partner_id'               => $payload->sub,
			'refresh_token_expired_in' => $payload->exp,
			'partner_profile_code'     => static::decode_string( $payload->payload )->partnerProfileCode,
			'refresh_token_refresh_in' => static::decode_string( $payload->payload )->refreshTokenRefreshIn,
		);
	}

	/**
	 * Verify a signature with the message, key and method. Not all methods.
	 * are symmetric, so we must have a separate verify and sign method.
	 *
	 * @param string                                                  $msg         The original message (header and body).
	 * @param string                                                  $signature   The original signature.
	 * @param string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate $key_material For Ed*, ES*, HS*, a string key works. for RS*, must be an instance of OpenSSLAsymmetricKey.
	 * @param string                                                  $alg         The algorithm.
	 *
	 * @return bool
	 *
	 * @throws DomainException Invalid Algorithm, bad key, or OpenSSL failure.
	 */
	private static function verify(
		string $msg,
		string $signature,
		$key_material,
		string $alg
	): bool {

		if ( empty( static::$supported_algs[ $alg ] ) ) {
			throw new DomainException( 'Algorithm not supported' );
		}

		list( $function, $algorithm ) = static::$supported_algs[ $alg ];
		$success                      = \openssl_verify( $msg, $signature, $key_material, $algorithm ); // @phpstan-ignore-line
		if ( 1 === $success ) {
			return true;
		}
		if ( 0 === $success ) {
			return false;
		}
		// returns 1 on success, 0 on failure, -1 on error.
		throw new DomainException( 'OpenSSL error: ' . esc_html( \openssl_error_string() ) );
	}


	/**
	 * Extract public key from certificate and prepare it for use
	 *
	 * @param string|null $pub_key Publik Key.
	 *
	 * @return Key
	 */
	private static function get_key( $pub_key ) {
		$pem_pub    = wordwrap( $pub_key, 64, "\n", true );
		$pubkey_pem = "-----BEGIN PUBLIC KEY-----\n" . $pem_pub . "\n-----END PUBLIC KEY-----";
		return openssl_pkey_get_public( $pubkey_pem );
	}

	/**
	 * Decode a string with URL-safe Base64.
	 *
	 * @param string $input A Base64 encoded string.
	 *
	 * @return string A decoded string.
	 *
	 * @throws InvalidArgumentException Invalid base64 characters.
	 */
	public static function urlsafe_b64_decode( string $input ): string {
		return \base64_decode( self::convert_base64_url_to_base64( $input ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	/**
	 * Convert a string in the base64url (URL-safe Base64) encoding to standard base64.
	 *
	 * @param string $input A Base64 encoded string with URL-safe characters (-_ and no padding).
	 *
	 * @return string A Base64 encoded string with standard characters (+/) and padding (=), when.
	 * needed.
	 */
	public static function convert_base64_url_to_base64( string $input ): string {
		$remainder = \strlen( $input ) % 4;
		if ( $remainder ) {
			$padlen = 4 - $remainder;
			$input .= \str_repeat( '=', $padlen );
		}
		return \strtr( $input, '-_', '+/' );
	}

	/**
	 * Decode a JSON string into a PHP object.
	 *
	 * @param string $input JSON string.
	 *
	 * @return mixed The decoded JSON string.
	 *
	 * @throws DomainException Provided string was invalid JSON.
	 */
	public static function decode_string( string $input ) {
		$obj = \json_decode( $input, false, 512, JSON_BIGINT_AS_STRING );

		if ( \json_last_error() ) {
			self::handle_json_error( \json_last_error() );
		} elseif ( null === $obj && 'null' !== $input ) {
			throw new DomainException( 'Null result with non-null input' );
		}
		return $obj;
	}

	/**
	 * Helper method to create a JSON error.
	 *
	 * @param int $errno An error number from json_last_error().
	 *
	 * @throws DomainException Invalid Json.
	 *
	 * @return void
	 */
	private static function handle_json_error( int $errno ): void {
		$messages        = array(
			JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
			JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
			JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8           => 'Malformed UTF-8 characters', // PHP >= 5.3.3.
		);
		$exception_error = isset( $messages[ $errno ] ) ? $messages[ $errno ] : 'Unknown JSON error: ' . $errno;
		throw new DomainException( esc_html( $exception_error ) );
	}
}
