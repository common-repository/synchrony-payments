/**
 * Cart totals Update script.
 *
 * @package Synchrony\Payments\assets
 */

jQuery(
	function ($) {
		$( document.body ).on(
			'updated_cart_totals',
			function () {
				var reloadWidget = setInterval(
					function () {
						if ($( '#product-content' ).length) {
							clearInterval( reloadWidget );
							$( '#product-content' ).html( document.querySelector( 'tr.order-total .woocommerce-Price-amount bdi' ).textContent );
						}
					},
					500
				);
			}
		);
		$( document.body ).on(
			'updated_checkout',
			function () {
				$( '#product-content' ).html( document.querySelector( 'tr.order-total .woocommerce-Price-amount bdi' ).textContent );
			}
		);
		// Block Support on cart price update.
		var targetNode = document.querySelector( '.wc-block-components-totals-footer-item-tax-value' );
		if ( targetNode ) {
			var config            = {
				attributes: true,
				childList: true,
				subtree: true,
				attributeOldValue: true,
				characterDataOldValue: true
			};
			var mutation_callback = function (mutationsList, observer) {
				for (var mutation of mutationsList) {
					document.querySelectorAll( '.syf_product_price' ).forEach(
						function (element) {
							element.innerHTML = targetNode.innerHTML;
						}
					);
				}
			};
			var observer          = new MutationObserver( mutation_callback );
			observer.observe( targetNode, config );
		}
	}
);

function MPPAnywhereIdApply() {
	window.syfMPP.mppFromAnyWhere();
}
jQuery( document ).ready(
	function () {
		var MPPAnywhereIdElements = document.querySelectorAll( '.MPPAnywhereClass' );
		var MPPElementLength      = MPPAnywhereIdElements.length;
		if (MPPAnywhereIdElements && MPPElementLength > 0) {
			for (var i = 0; i < MPPElementLength; i++) {
				MPPAnywhereIdElements.item( i ).onclick = function (event) {
					event.preventDefault();
					var targetElement = MPPAnywhereIdElements.item( i );
					var dataTags      = event.target.parentElement.getAttribute( 'data-tags' );
					delete syfWidgetObject.tags;
					if (dataTags) {
						syfWidgetObject.tags   = dataTags;
						window.syfWidgetObject = syfWidgetObject;
					}
					if (event && event.target) {
						targetElement = event.target;
					}
					MPPAnywhereIdApply.apply( targetElement );
				};
			}
		}
	}
);


var digitalBuyModalManagerInitialized  = false;
var digitalBuyModalManagerInitCallback = function (e) {
	e.preventDefault();
	e.stopPropagation();

	if (digitalBuyModalManagerInitialized) {
		return;
	}
	let SyfObject = {};
	if ( jQuery( 'form.checkout' ).is( '.processing' ) ) {
		return false;
	}

	jQuery( 'form.checkout' ).addClass( 'processing' ).block(
		{
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		}
	);
	jQuery( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();

	var formData = jQuery( 'form.checkout' ).serialize() + '&action=checkout_form_data';
	jQuery.ajax(
		{
			type:'post',
			url:'../wp-admin/admin-ajax.php',
			data: formData,
			success:function (response) {
				if (response.result === 'failure') {
					window.removeEventListener( 'click', digitalBuyModalManagerInitCallback );
					console.log( response.messages );
					jQuery( 'form.checkout' ).prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">Something went wrong.</div>' ); // eslint-disable-line max-len.
					jQuery( 'form.checkout' ).removeClass( 'processing' ).unblock();
					jQuery( 'form.checkout' ).find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).blur();
					jQuery( document.body ).trigger( 'checkout_error' , [ response.messages ] );
					jQuery.scroll_to_notices( jQuery( 'form.checkout' ) );
				} else if (response.tokenId === '') {
					window.removeEventListener( 'click', digitalBuyModalManagerInitCallback );
					jQuery( 'form.checkout' ).prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout" style="color:red;">SYF: Something went wrong.</div>' );
					jQuery( 'form.checkout' ).removeClass( 'processing' ).unblock();
					jQuery( 'form.checkout' ).find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).blur();
					jQuery.scroll_to_notices( jQuery( 'form.checkout' ) );
					jQuery( document.body ).trigger( 'checkout_error' );
				} else if (response.result === 'success') {
					digitalBuyModalManagerInitialized = true;
					window.removeEventListener( 'click', digitalBuyModalManagerInitCallback );
					jQuery( '#tokenId' ).val( response.tokenId );
					jQuery( '#reference_id' ).val( response.order_id );

					SyfObject.tokenId                = response.tokenId;
					SyfObject.syfPartnerId           = response.syfPartnerId;
					SyfObject.childSyfMerchantNumber = response.childSyfMerchantNumber;
					SyfObject.processInd             = response.processInd;
					SyfObject.clientTransId          = response.clientTransId;
					SyfObject.custAddress1           = response.custAddress1;
					SyfObject.custAddress2           = response.custAddress2;
					SyfObject.custCity               = response.custCity;
					SyfObject.custState              = response.custState;
					SyfObject.custZipCode            = response.custZipCode;

					SyfObject.custFirstName = response.custFirstName;
					SyfObject.custLastName  = response.custLastName;
					SyfObject.transAmount1  = response.transAmount1;
					SyfObject.phoneNumber   = response.phoneNumber;
					SyfObject.emailAddress  = response.emailAddress;
					SyfObject.saveCard      = response.saveCard;
					if (response.productAttributes) {
						SyfObject.productAttributes = JSON.stringify( JSON.parse( response.productAttributes ) );
					}

					DigitalBuyModalManager.init(
						{
							formId: 'dbuyform3',
							postbackFormId: 'postbackform',
							formToJsonObj: SyfObject
						}
					);
				}
			},
			error: function (error) {
				if (typeof console !== "undefined" && console.error) {
					console.error( 'AJAX error:', error );
				}
			}
		}
	);
	window.CurrentJsonObj = SyfObject;

	e.preventDefault();
	e.stopPropagation();
};

var DigitalBuyModalManager = (function () {
	var _config = {
		modalId: 'digBuyModal',
		globalLoaderId: 'checkout-loader',
		digitalBuyObj: 'syfMPP',
		digitalBuyInitFunctionName: 'calldBuyProcess'
	};

	var extend = function () {
		var argSize = arguments.length;
		for (var i = 1; i < argSize; i++) {
			for (var key in arguments[i]) {
				if (arguments[i].hasOwnProperty( key )) {
					arguments[0][key] = arguments[i][key];
				}
			}
		}
		return arguments[0];
	};

	var showLoader = function () {
		if ( ! _config.globalLoaderId) {
			return;
		}

		var el = document.getElementById( _config.globalLoaderId );
		if ( ! el) {
			return;
		}

		el.style.display = 'block';
	};

	var hideLoader = function () {
		if ( ! _config.globalLoaderId) {
			return;
		}

		var el = document.getElementById( _config.globalLoaderId );
		if ( ! el) {
			return;
		}

		el.style.display = 'none';
	};

	var closeEventCallback = function (event) {
		event.preventDefault();
		event.stopPropagation();
		if ( typeof event.data == 'string' && event.data == "Unifi Modal Close" ) {
			showLoader();
			document.getElementById( _config.postbackFormId ).submit();
			return false;
		}
	};

	return {
		init: function (config) {
			extend( _config, config );
			document.getElementById( _config.formId );
			var formToJson        = _config.formToJsonObj;
			var initFunctionScope = _config.digitalBuyObj ? window[_config.digitalBuyObj] : window;
			window.formToJson     = formToJson;
			hideLoader();
			setTimeout(
				function () {
					initFunctionScope[_config.digitalBuyInitFunctionName]( null, formToJson );
				},
				1000
			);

			if (window.addEventListener) {
				window.addEventListener( 'message', closeEventCallback );
			} else if (window.attachEvent) {
				window.attachEvent( 'onmessage', closeEventCallback );
			}
		}
	};
})();

jQuery(
	function ($) {
		$( 'form.woocommerce-checkout' ).on(
			'click',
			'#place_order',
			function () {
				if ('synchrony-unifi-payments' === $( 'input:radio[name=payment_method]:checked' ).val()) {
					if ($( '#checkout_popup_status' ).val() === '2') {
						return true;
					}
					if (document.addEventListener) {
						window.addEventListener( 'click', digitalBuyModalManagerInitCallback, false );
					} else if (document.attachEvent) {
						document.attachEvent(
							'onreadystatechange',
							function () {
								if (document.readyState === 'complete') {
									digitalBuyModalManagerInitCallback();
								}
							}
						);
						window.attachEvent( 'onload', digitalBuyModalManagerInitCallback );
					} else {
						digitalBuyModalManagerInitCallback();
					}
				}
			}
		);
	}
);
