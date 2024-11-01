/**
 * Custom JavaScript for Woocommerce Blocks.
 *
 * @package Synchrony\Payments\assets
 */

const syfcheckout_data    = wc.wcSettings.getSetting( 'synchrony-unifi-payments_data' );
const syfcheckout_label   = window.wp.htmlEntities.decodeEntities( syfcheckout_data.syf_title )
	|| window.wp.i18n.__( 'Synchrony Financing – Pay Over Time', 'synchrony-payments' );
const syf_data            = syfcheckout_data.syf_data;
const syfcheckout_content = () => {
	return window.wp.htmlEntities.decodeEntities( '' );
};
const { useSelect }       = window.wp.data;
const { CHECKOUT_STORE_KEY,PAYMENT_STORE_KEY,CART_STORE_KEY } = window.wc.wcBlocksData;

const syfIcon = () => {
	if ( ! syfcheckout_data.icon) {
		return '';
	}
	return React.createElement(
		'img',
		{
			src: syfcheckout_data.icon,
			style: {marginLeft:'10px'},
			alt: syfcheckout_label
		},
		null
	);
}

const Label = () => {
	return [React.createElement( 'span', null, syfcheckout_label ), React.createElement( syfIcon )];
}

const content          = (props) => {
	const isComplete   = useSelect(
		(select) =>
		select( CHECKOUT_STORE_KEY ).isComplete()
	);
	const customerInfo = useSelect(
		(select) =>
		select( CART_STORE_KEY ).getCustomerData()
	);

	const orderId = useSelect(
		( select ) =>
		select( CHECKOUT_STORE_KEY ).getOrderId()
	);

	const isSaveCard = useSelect(
		( select ) =>
		select( PAYMENT_STORE_KEY ).getShouldSavePaymentMethod()
	);

if (syfcheckout_data.is_overlay === 1 && isComplete) {
	jQuery( '#tokenId' ).val( syf_data.tokenId );
	jQuery( '#reference_id' ).val( orderId );
	jQuery( '.wc-block-components-checkout-place-order-button' ).prop( "disabled", false );
	var customerData = customerInfo.billingAddress;
	if ( syfcheckout_data.address_type === 'shipping' ) {
		var customerData = customerInfo.shippingAddress;
	}
	let JSONObject = {
		tokenId: syf_data.tokenId,
		syfPartnerId: syf_data.syfPartnerId,
		childSyfMerchantNumber: syf_data.childSyfMerchantNumber,
		processInd: syf_data.processInd,
		clientTransId: syf_data.clientTransId,
		custAddress1: customerData.address_1,
		custAddress2: customerData.address_2,
		custCity: customerData.city,
		custState: customerData.state,
		custZipCode: customerData.postcode,
		custFirstName: customerData.first_name,
		custLastName: customerData.last_name,
		transAmount1: syf_data.transAmount1,
		phoneNumber: customerData.phone,
		emailAddress: customerInfo.billingAddress.email,
		saveCard: isSaveCard ? 'YES' : 'NO',
		productAttributes: syf_data.productAttributes
	};
	DigitalBuyModalManager.init(
		{
			formId: 'dbuyform3',
			postbackFormId: 'postbackform',
			formToJsonObj: JSONObject
			}
	);
}
	return window.wp.htmlEntities.decodeEntities( syfcheckout_data.description || '' );
};

const SyfCheckout = {
	name: 'synchrony-unifi-payments',
	label: Object( React.createElement )( Label, null ),
	content: Object( React.createElement )( content, null ),
	edit: Object( React.createElement )( syfcheckout_content, null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'synchrony-payments' ),
	ariaLabel: syfcheckout_label,
	supports: {
		features: syfcheckout_data.supports,
		showSaveOption: syfcheckout_data.is_save_card_enable ? true : false,
	},
};
window.wc.wcBlocksRegistry.registerPaymentMethod( SyfCheckout );

jQuery( window ).on(
	'load',
	function () {
		if (syfcheckout_data.checked) {
			jQuery( '.wc-block-components-payment-methods__save-card-info .wc-block-components-checkbox__label' ).trigger( 'click' );
			jQuery( "input[id='radio-control-wc-payment-method-options-synchrony-unifi-payments']" ).change(
				function () {
					if (this.checked) {
						if (jQuery( '.wc-block-components-payment-methods__save-card-info .wc-block-components-checkbox__input' ).prop( 'checked' ) === false) {
							jQuery( '.wc-block-components-payment-methods__save-card-info .wc-block-components-checkbox__label' ).trigger( 'click' );
						}
					}
				}
			);
		}
	}
);