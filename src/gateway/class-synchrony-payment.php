<?php
/**
 * Payment class
 *
 * @package Synchrony\Payments\Gateway
 */

namespace Synchrony\Payments\Gateway;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Gateway\Synchrony_Client;
use Synchrony\Payments\Logs\Synchrony_Logger;
use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;

/**
 * Class Payment
 */
class Synchrony_Payment extends \WC_Payment_Gateway {


	/**
	 * SYNCHRONY_ORDER_TOKEN_ID
	 *
	 * @var string
	 */
	public const SYNCHRONY_ORDER_TOKEN_ID = '_syf_order_token_id';
	/**
	 * AUTHORIZATION_CODE
	 *
	 * @var string
	 */
	public const AUTHORIZATION_CODE = 'authorizationCode';
	/**
	 * PROMO_CODE
	 *
	 * @var string
	 */
	public const PROMO_CODE = 'promotionCode';
	/**
	 * LOG_INFO
	 *
	 * @var string
	 */
	public const LOG_INFO = 'log_info: ';
	/**
	 * Process Payment function
	 *
	 *  @param array $order_id  This is for order_id.
	 *  @return array
	 *  @throws \Exception - Exception occurs during Process payment.
	 */
	public function process_payment( $order_id ) {
		$logger = new Synchrony_Logger();
		try {
			$logger->info( 'starting payment execution.' );
			$user_id               = get_current_user_id();
			$helper                = new Synchrony_Config_Helper();
			$common_config_helper  = new Synchrony_Common_Config_Helper();
			$setting_config_helper = new Synchrony_Setting_Config_Helper();
			$slug                  = strtolower( str_replace( ' ', '-', trim( $common_config_helper::TEMPLATE_TITLE ) ) );
			$page                  = get_page_by_path( $slug, OBJECT );
			$reference_variable    = $common_config_helper->generate_reference_id();
			// Card on file feature.
			$syf_savecard_flag = 'no';
			$remember_my_card  = isset( $_POST['syf_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['syf_nonce'] ) ), 'cof_nonce' ) && isset( $_POST['saveCard'] ) ? sanitize_text_field( wp_unslash( $_POST['saveCard'] ) ) : 0;
			// Checking for default checkout and checkout blocks.
			if ( $remember_my_card || ( isset( $_POST['wc-synchrony-unifi-payments-new-payment-method'] ) && '' !== $_POST['wc-synchrony-unifi-payments-new-payment-method'] ) ) {
				$syf_savecard_flag = 'yes';
			}
			// Update flag on the basis of checkbox selection.
			if ( 'yes' === $syf_savecard_flag && $user_id ) {
				update_user_meta( $user_id, 'syf_cardonfileflag', $syf_savecard_flag, '' );
			}
			// Remove paymentToken and flag from db.
			if ( 'no' === $syf_savecard_flag && $user_id ) {
				update_user_meta( $user_id, 'syf_paymenttoken', '', '' );
				update_user_meta( $user_id, 'syf_cardonfileflag', '', '' );
			}
			if ( ! isset( $page ) ) {
				throw new \Exception( __( 'Invalid order.', 'synchrony-payments' ) );
			}
			$pop_up = $helper->fetch_pop_up();
			if ( 1 === $pop_up ) {
				$cart_data = new \Synchrony\Payments\Frontend\Synchrony_Cart_Data();
				return $cart_data->map_dbuy_data( $order_id, $user_id );
			}
			return array(
				'result'   => 'success',
				'redirect' => get_permalink( get_page_by_path( 'synchrony-payment' ) ) . '?' . $reference_variable,
			);
		} catch ( \Exception $e ) {
			$logger->error( 'error in payment process: , file path: ' . $e->getFile() . ', line no: ' . $e->getLine() . ', exception message: ' . $e->getMessage() );
			return array(
				'result'   => 'failure',
				'redirect' => wc_get_checkout_url(),
			);
		}
	}

	/**
	 * Function used in refund
	 *
	 * @param mixed $order This is for order.
	 * @return mixed
	 */
	public function can_refund_order( $order ) {
		return $order && $this->retrieve_syf_token_id( $order );
	}

	/**
	 * Function process_refund
	 *
	 * @param array  $order_id This is order id.
	 * @param int    $amount   This provides amount.
	 * @param string $reason  This provides reason.
	 * @return mixed
	 *
	 * @throws \Exception -  Exception occurs during process refund.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$logger   = new Synchrony_Logger();
		$log_info = array( 'order_id' => $order_id );
		try {
			$order = wc_get_order( $order_id );

			if ( ! $this->retrieve_syf_token_id( $order ) ) {
				throw new \Exception( __( 'No Transaction Id', 'synchrony-payments' ) );
			}
			$logger->debug( self::LOG_INFO . wp_json_encode( $log_info ) . ', Start Refund Process.' );
			$data = array(
				'TransactionToken' => $this->retrieve_syf_token_id( $order ),
				'amount'           => $amount,
			);

			$client = new Synchrony_Client();
			$result = $client->refund( $data );
			$logger->debug( self::LOG_INFO . wp_json_encode( $log_info ) . ', End Refund Process.' );
			if ( ! $result || '000' !== $result['ResponseCode'] ) {
				throw new \Exception( __( 'Something wrong', 'synchrony-payments' ) );
			}
			$order->add_order_note(
				sprintf( 'Refunded %1$s - Refund ID: %2$s', $result['Status'], $result['ResponseText'] )
			);
			return true;
		} catch ( \Exception $e ) {
			$logger->error( 'error in Refund process: , file path: ' . $e->getFile() . ', line no: ' . $e->getLine() . ', exception message: ' . $e->getMessage() );
			return false;
		}
	}
	/**
	 * Get token id
	 *
	 * @param mixed $order  This is for order.
	 * @return array
	 */
	public function retrieve_syf_token_id( $order ) {
		return get_post_meta( $order->get_id(), self::SYNCHRONY_ORDER_TOKEN_ID, true );
	}
}
