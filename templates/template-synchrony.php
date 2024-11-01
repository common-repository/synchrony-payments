<?php
/**
 * Template Name: Synchrony Template
 * Template Post Type: post, page
 *
 * @package WordPress
 */

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Common_Config_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;

$base_url              = home_url();
$common_config_helper  = new Synchrony_Common_Config_Helper();
$setting_config_helper = new Synchrony_Setting_Config_Helper();
$order_id              = WC()->session->get( 'order_awaiting_payment' );
if ( WC()->session->get( 'store_api_draft_order' ) ) {
	$order_id = WC()->session->get( 'store_api_draft_order' );
}
$cart_data = new \Synchrony\Payments\Frontend\Synchrony_Cart_Data();
$cart_data->redirect_to_home_page( $base_url, $order_id );
wp_head();
$customer_data      = $cart_data->retrieve_customer_info();
$get_totals         = $cart_data->retrieve_cart()->get_totals();
$trans_amount       = $setting_config_helper->format_amount( $get_totals['total'] );
$client             = new \Synchrony\Payments\Gateway\Synchrony_Client();
$mpp_token          = $client->retrieve_token();
$timestamp          = gmdate( 'Y-m-d H:i:s' );
$partner_id         = $setting_config_helper->fetch_partner_id();
$postbackurl        = get_rest_url( null, '/syf/v1/callback' );
$client_trans_id    = $cart_data->retrieve_client_trans_id();
$loader_image       = $common_config_helper->fetch_loader_image();
$promo_tag_products = $cart_data->retrieve_promo_tags_products();
$get_tag_rules      = $setting_config_helper->fetch_tag_rules();
$child_merchant_id  = $setting_config_helper->fetch_child_merchant_id();

$process_ind            = 3;
$dbuy_form_name         = 'dbuyform3';
$dbuy_div_id            = 'dbuymodel3';
$auth                   = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'auth' ) ? '1' : '';
$session_token_id       = WC()->session->get( 'pay_syf_token_id' );
$user_id                = get_current_user_id();
$syf_cardonfileflag     = get_user_meta( $user_id, 'syf_cardonfileflag', true );
$is_card_on_file_enable = $setting_config_helper->customer_allow_to_save_card();
$card_on_file_flag      = 'NO';
if ( $is_card_on_file_enable && '' !== $mpp_token && 'yes' === $syf_cardonfileflag ) {
	$card_on_file_flag = 'YES';
}

if ( '1' === $auth ) {
	$process_ind       = 1;
	$dbuy_form_name    = 'dbuyform1';
	$dbuy_div_id       = 'dbuymodel1';
	$postbackurl       = $base_url . '/wp-json/syf/v1/find_status/';
	$child_merchant_id = $setting_config_helper->fetch_child_merchant_id();
}
if ( '1' !== $auth && $session_token_id ) {
	$process_ind       = 2;
	$dbuy_form_name    = 'dbuyform2';
	$dbuy_div_id       = 'dbuymodel2';
	$mpp_token         = $session_token_id;
	$child_merchant_id = '';
}
?>
<script type="text/javascript">
	var partner_id = "<?php echo esc_js( $partner_id ); ?>";
	var syfWidgetObject = {};
	syfWidgetObject.syfPartnerId = partner_id;
	syfWidgetObject.flowType = "PDP";
</script>
<div class="page-wrapper">
	<main id="maincontent" class="page-main">
		<div class="columns">
			<div class="column main"><input name="form_key" type="hidden" value="3dlBDmQPMsgYSesC1" />
				<div id="checkout" class="checkout-container">
					<div id="checkout-loader" class="loading-mask">
						<div class="loader">
							<img src="<?php echo esc_html( $loader_image ); ?>" alt="Loading..."
								style="position: absolute; top: 50%; left: 50%;">
						</div>
					</div>
					<div id="<?php echo esc_html( $dbuy_div_id ); ?>"></div>
					<form name="<?php echo esc_html( $dbuy_form_name ); ?>" id="<?php echo esc_html( $dbuy_form_name ); ?>">
						<input type="hidden" id="processInd" name="processInd" value="<?php echo esc_html( $process_ind ); ?>" />
						<input type="hidden" id="tokenId" name="tokenId" value="<?php echo esc_html( $mpp_token ); ?>" />
						<input type="hidden" name="syfPartnerId" id="syfPartnerId" value="<?php echo esc_html( $partner_id ); ?>" />
						<input type="hidden" id="merchantID" name="merchantID" value="" />
						<input type="hidden" id="childSyfMerchantNumber" name="childSyfMerchantNumber" value="<?php echo esc_html( $child_merchant_id ); ?>" />
						<input type="hidden" id="clientTransId" name="clientTransId"
							value="<?php echo esc_html( $client_trans_id ); ?>" />
						<input type="hidden" id="custFirstName" name="custFirstName"
							value="<?php echo esc_html( $customer_data['first_name'] ); ?>" />
						<input type="hidden" id="custLastName" name="custLastName"
							value="<?php echo esc_html( $customer_data['last_name'] ); ?>" />
						<input type="hidden" id="custZIPCode" name="custZipCode"
							value="<?php echo esc_html( $customer_data['postal_code'] ); ?>" />
						<input type="hidden" id="custAddress1" name="custAddress1"
							value="<?php echo esc_html( $customer_data['address1'] ); ?>" />
						<input type="hidden" id="custAddress2" name="custAddress2"
							value="<?php echo esc_html( $customer_data['address2'] ); ?>" />
						<input type="hidden" id="custCity" name="custCity"
							value="<?php echo esc_html( $customer_data['city'] ); ?>" />
						<input type="hidden" id="custState" name="custState"
							value="<?php echo esc_html( $customer_data['state'] ); ?>" />
						<input type="hidden" id="phoneNumber" name="phoneNumber"
							value="<?php echo esc_html( $customer_data['phone_number'] ); ?>">
						<input type="hidden" id="emailAddress" name="emailAddress"
							value="<?php echo esc_html( $customer_data['email'] ); ?>">
						<input type="hidden" id="transAmount1" name="transAmount1"
							value="<?php echo esc_html( $trans_amount ); ?>" />
						<input type="hidden" id="productAttributes" name="productAttributes" value='<?php echo ( 1 === $get_tag_rules ) ? esc_html( $promo_tag_products ) : ''; ?>'/>
						<?php if ( 1 === intval( $is_card_on_file_enable ) ) { ?>
						<input type="hidden" id="saveCard" name="saveCard" value="<?php echo esc_html( $card_on_file_flag ); ?>"/>
						<?php } ?>
					</form>
					<form name="postbackform" id="postbackform" method="post" action="<?php echo esc_html( $postbackurl ); ?>">
						<input name="form_key" type="hidden" value="<?php echo esc_html( wp_rand( 1, 10 ) ); ?>" />
						<input type="hidden" id="tokenId" name="tokenId" value="<?php echo $mpp_token ? esc_html( $mpp_token ) : esc_html( 'NOTOKEN' ); ?>" />
						<input type="hidden" id="timestamp" name="timestamp" value="<?php echo esc_html( $timestamp ); ?>" />
						<input type="hidden" name="reference_id" value="<?php echo esc_html( $order_id ); ?>" />
						<input type="hidden" id="user_id" name="user_id" value="<?php echo esc_html( $user_id ); ?>" />
					</form>
					<script type="text/javascript">
						var productAttributes = '';
						<?php if ( 1 === $get_tag_rules ) { ?>
						productAttributes = JSON.stringify(<?php echo wp_kses_post( $promo_tag_products ); ?>);
						<?php } ?>
						function formToJSON() {
							let SyfObject = {};
							SyfObject.tokenId = document.getElementById("tokenId").value;
							SyfObject.merchantID = document.getElementById("merchantID").value;
							SyfObject.childSyfMerchantNumber  = document.getElementById("childSyfMerchantNumber").value;
							SyfObject.syfPartnerId = document.getElementById("syfPartnerId").value;
							SyfObject.processInd = document.getElementById("processInd").value;
							SyfObject.clientTransId = document.getElementById("clientTransId").value;

							SyfObject.custAddress1 = document.getElementById("custAddress1").value;
							SyfObject.custAddress2 = document.getElementById("custAddress2").value;
							SyfObject.custCity = document.getElementById("custCity").value;
							SyfObject.custState = document.getElementById("custState").value;
							SyfObject.custZipCode = document.getElementById("custZIPCode").value;

							SyfObject.custFirstName = document.getElementById("custFirstName").value;
							SyfObject.custLastName = document.getElementById("custLastName").value;
							SyfObject.transAmount1 = document.getElementById("transAmount1").value;
							SyfObject.phoneNumber = document.getElementById("phoneNumber").value;
							SyfObject.emailAddress = document.getElementById("emailAddress").value;
							<?php if ( 1 === intval( $is_card_on_file_enable ) ) { ?>
							SyfObject.saveCard = document.getElementById("saveCard").value;
							<?php } ?>
							SyfObject.productAttributes = productAttributes;
							window.CurrentJsonObj = SyfObject;
							return SyfObject;
						}
						var digitalBuyModalManagerInitialized = false;
						var digitalBuyModalManagerInitCallback = function (e) {
							e.preventDefault();
							e.stopPropagation();
							if (digitalBuyModalManagerInitialized) {
								return;
							}
							digitalBuyModalManagerInitialized = true;
							DigitalBuyModalManager.init({
								formId: "dbuyform3",
								postbackFormId: "postbackform",
								formToJsonObj: formToJSON()
							});
							e.preventDefault();
							e.stopPropagation();
						};

						if (document.addEventListener) {
							//document.addEventListener("DOMContentLoaded", digitalBuyModalManagerInitCallback, false);
							window.addEventListener("load", digitalBuyModalManagerInitCallback, false);
						} else if (document.attachEvent) {
							document.attachEvent("onreadystatechange", function () {
								if (document.readyState === "complete") {
									digitalBuyModalManagerInitCallback();
								}
							});
							window.attachEvent("onload", digitalBuyModalManagerInitCallback);
						} else {
							digitalBuyModalManagerInitCallback();
						}
					</script>
				</div>
			</div>
		</div>
	</main>
</div>
<?php
wp_footer();
?>
<script type="text/javascript">
	var DigitalBuyModalManager = (function () {
		var _config = {
			modalId: "digBuyModal",
			globalLoaderId: "checkout-loader",
			digitalBuyObj: "syfMPP",
			digitalBuyInitFunctionName: "calldBuyProcess"
		};

		var extend = function () {
			for (var i = 1; i < arguments.length; i++)
				for (var key in arguments[i])
					if (arguments[i].hasOwnProperty(key))
						arguments[0][key] = arguments[i][key];
			return arguments[0];
		};

		var showLoader = function () {
			if (!_config.globalLoaderId) {
				return;
			}

			var el = document.getElementById(_config.globalLoaderId);
			if (!el) {
				return;
			}

			el.style.display = "block";
		};

		var hideLoader = function () {
			if (!_config.globalLoaderId) {
				return;
			}

			var el = document.getElementById(_config.globalLoaderId);
			if (!el) {
				return;
			}

			el.style.display = "none";
		};

		var closeEventCallback = function (event) {
			event.preventDefault();
			event.stopPropagation();
			if ( typeof event.data == 'string' && event.data == "Unifi Modal Close" ) {
				showLoader();
				document.getElementById(_config.postbackFormId).submit();
				return false;
			}
		};

		return {
			init: function (config) {
				extend(_config, config);
				var form = document.getElementById(_config.formId);
				var formToJson = _config.formToJsonObj;
				var initFunctionScope = _config.digitalBuyObj ? window[_config.digitalBuyObj] : window;
				window.formToJson = formToJson;
				hideLoader();
				setTimeout(function () {
					initFunctionScope[_config.digitalBuyInitFunctionName](null, formToJson);
				}, 1000);

				if (window.addEventListener) {
					window.addEventListener("message", closeEventCallback);
				} else if (window.attachEvent) {
					window.attachEvent("onmessage", closeEventCallback);
				}
			}
		};
	})();
</script>
