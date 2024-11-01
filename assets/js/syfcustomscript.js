/**
 * Custom JavaScript for Woocommerce Synchrony Payments.
 *
 * @package Synchrony\Payments\assets
 */

jQuery( document ).ready(
	function ($) {
		window.addEventListener(
			'beforeunload',
			function (event) {
				event.stopImmediatePropagation();
			}
		);

		var environment = $( '#woocommerce_synchrony-unifi-payments_synchrony_test' ).val() === 'yes' ? 'test' : 'deployed';
		var activation  = $( '#woocommerce_synchrony-unifi-payments_' + environment + '_enable_activation' ).val();
		$( 'form#mainform h3.wc-settings-sub-title' ).css( { 'border-bottom': '1px solid #ccc','padding': '0 0 10px'} );
		$( '.hide_deployed' ).next( 'table' ).hide();
		$( '.hide_deployed' ).hide();
		$( '.hide_synchrony_test' ).next( 'table' ).hide();
		$( '.hide_synchrony_test' ).hide();

		$( '.hide_deployed_traditional' ).hide();
		$( '.hide_deployed_traditional' ).next( 'table' ).find( 'tr' ).hide();

		$( '.hide_deployed_smb' ).hide();
		$( '.hide_deployed_smb' ).next( 'table' ).hide();

		$( '.hide_test_smb' ).hide();
		$( '.hide_test_smb' ).next( 'table' ).hide();

		$( '.hide_test_traditional' ).hide();
		$( '.hide_test_traditional' ).next( 'table' ).find( 'tr' ).hide();

		// Condition for sandbox enable flag.
		$( '#woocommerce_synchrony-unifi-payments_synchrony_test' ).change(
			function () {
				if ($( '#woocommerce_synchrony-unifi-payments_synchrony_test' ).val() === 'yes') { // sandbox.
					environment = 'test';
					$( '.synchrony_test_div' ).show();
					$( '.synchrony_test_div' ).next( 'table' ).show();
					$( '.deployed_div' ).next( 'table' ).hide();
					$( '.deployed_div' ).hide();

					// hide deployed traditional.
					showHideTraditionalDiv( 'deployed', 'hide' );
					// hide deployed smb.
					showHideSMbDiv( 'deployed', 'hide' );
					if ($( '#woocommerce_synchrony-unifi-payments_' + environment + '_enable_activation' ).val() == 'yes') {
						showHideTraditionalDiv( 'test', 'hide' );
						showHideSMbDiv( 'test', 'show' );
					} else {
						showHideTraditionalDiv( 'test', 'show' );
						showHideSMbDiv( 'test', 'hide' );
						$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).closest( 'tr' ).hide();
						$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).hide();
					}
					showHideChildMerchantId( environment );
				} else { // prod.
					environment = 'deployed';
					$( '.synchrony_test_div' ).hide();
					$( '.synchrony_test_div' ).next( 'table' ).hide();
					$( '.deployed_div' ).next( 'table' ).show();
					$( '.deployed_div' ).show();

					// hide test traditional.
					showHideTraditionalDiv( 'test', 'hide' );
					// hide test smb.
					showHideSMbDiv( 'test', 'hide' );
					if ($( '#woocommerce_synchrony-unifi-payments_' + environment + '_enable_activation' ).val() === 'yes') {
						showHideTraditionalDiv( 'deployed', 'hide' );
						showHideSMbDiv( 'deployed', 'show' );
					} else {
						showHideTraditionalDiv( 'deployed', 'show' );
						showHideSMbDiv( 'deployed', 'hide' );
						$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).closest( 'tr' ).hide();
						$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).hide();
					}
					showHideChildMerchantId( environment );
				}

				if ( $( '#action_row_' + environment ).length === 0 ) {
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( '<div id="action_row_' + environment + '" style="margin-top:10px"></div>' );
				}

				activation         = $( '#woocommerce_synchrony-unifi-payments_' + environment + '_enable_activation' ).val();
				var activation_key = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).val();

				if ( activation_key && activation === 'yes' ) {
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).closest( 'tr' ).show();
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).show();

					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).prop( 'readonly', true );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).prop( 'readonly', true );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).hide();
				} else {
					$( '#action_row_' + environment ).html( '<a id="add_smb_domain_row" style="cursor:pointer; margin-right:10px">Add Domain</a><button class="button-primary validate_credential_button" type="button" style="display: block; margin-top: 10px;">Activate</button><span id="validation_message" style="margin-left:10px"></span>' );
				}
				if (activation === 'yes' && activation_key === '') {
					$( '#mainform' ).find( 'table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).hide();
					$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).hide();
				} else {
					$( '#mainform' ).find( 'table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).show();
					$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).show();
				}

			}
		);

		// Condition for activation flag.
		$( '#woocommerce_synchrony-unifi-payments_deployed_enable_activation' ).change(
			function () {
				if ($( '#woocommerce_synchrony-unifi-payments_deployed_enable_activation' ).val() === 'yes') {
					showHideSMbDiv( 'deployed', 'show' );
					showHideTraditionalDiv( 'deployed', 'hide' );
					$( '.synchrony-promo-field' ).hide();
					$( '.synchrony-save-card-field' ).hide();
					$( '#woocommerce_synchrony-unifi-payments_show_unify_widget option[value="plp"]' ).remove();
					$( 'label[for="woocommerce_synchrony-unifi-payments_enable_savelater"]' ).hide();
					$( '#mainform' ).find( 'table:eq(11)' ).hide();
					if ($( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_activation_key' ).val()) {
						$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_client_id' ).closest( 'tr' ).show();
						$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).show();
						$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_partner_id' ).prop( 'readonly', true );
						$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_activation_key' ).prop( 'readonly', true );
						$( '#mainform' ).find( 'table:eq(7), table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).show();
						$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).show();
					} else {
						$( '#mainform' ).find( 'table:eq(7), table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).hide();
						$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).hide();
					}
				} else {
					showHideSMbDiv( 'deployed', 'hide' );
					showHideTraditionalDiv( 'deployed', 'show' );
					$( '.synchrony-promo-field' ).show();
					$( '.synchrony-save-card-field' ).show();
					var newPlpOption = $(
						'<option>',
						{
							value: 'plp',
							text: 'Product Listing Page'
						}
					);
					$( '#woocommerce_synchrony-unifi-payments_show_unify_widget' ).append( newPlpOption );
					$( 'label[for="woocommerce_synchrony-unifi-payments_enable_savelater"]' ).show();
					$( '#mainform' ).find( 'table:eq(11)' ).show();
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_partner_id' ).prop( 'readonly', false );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_activation_key' ).prop( 'readonly', false );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_client_id' ).closest( 'tr' ).hide();
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).hide();
					var partner_nsmb_id = $( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_partner_id' ).val();
					if ( partner_nsmb_id ) {
						showFormElements();
					}
					// Hide Partner Code.
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_child_partner_code' ).closest( 'tr' ).hide();
				}
			}
		);

		$( '#woocommerce_synchrony-unifi-payments_test_enable_activation' ).change(
			function () {
				if ($( '#woocommerce_synchrony-unifi-payments_test_enable_activation' ).val() === 'yes') {
					showHideSMbDiv( 'test', 'show' );
					showHideTraditionalDiv( 'test', 'hide' );
					$( '.synchrony-promo-field' ).hide();
					$( '.synchrony-save-card-field' ).hide();
					$( '#woocommerce_synchrony-unifi-payments_show_unify_widget option[value="plp"]' ).remove();
					$( 'label[for="woocommerce_synchrony-unifi-payments_enable_savelater"]' ).hide();
					$( '#mainform' ).find( 'table:eq(11)' ).hide();
					if ($( '#woocommerce_synchrony-unifi-payments_synchrony_test_activation_key' ).val()) {
						$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_client_id' ).closest( 'tr' ).show();
						$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).show();
						$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_partner_id' ).prop( 'readonly', true );
						$( '#woocommerce_synchrony-unifi-payments_synchrony_test_activation_key' ).prop( 'readonly', true );
						$( '#mainform' ).find( 'table:eq(7), table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).show();
						$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).show();
					} else {
						$( '#mainform' ).find( 'table:eq(7), table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).hide();
						$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).hide();
					}
				} else {
					showHideSMbDiv( 'test', 'hide' );
					showHideTraditionalDiv( 'test', 'show' );
					$( '.synchrony-promo-field' ).show();
					$( '.synchrony-save-card-field' ).show();
					$( '#woocommerce_synchrony-unifi-payments_show_unify_widget option[value="plp"]' ).show();
					var newPlpOption = $(
						'<option>',
						{
							value: 'plp',
							text: 'Product Listing Page'
						}
					);
					$( '#woocommerce_synchrony-unifi-payments_show_unify_widget' ).append( newPlpOption );
					$( '#mainform' ).find( 'table:eq(11)' ).show();
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_partner_id' ).prop( 'readonly', false );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_activation_key' ).prop( 'readonly', false );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_client_id' ).closest( 'tr' ).hide();
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).hide();
					var partner_nsmb_id = $( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_partner_id' ).val();
					if ( partner_nsmb_id ) {
						showFormElements();
					}
					// Hide Partner Code.
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_child_partner_code' ).closest( 'tr' ).hide();
				}
			}
		);

		// Condition when page loads.
		var activation_key_val = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).val();
		var client_id          = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).val();
		var partner_id         = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).val();
		if ( activation === 'yes' && ( ! activation_key_val || ! client_id || ! partner_id ) ) {
			// Hide other fields.
			$( '#mainform' ).find( 'table:eq(7), table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).hide();
			$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).hide();
		}
		if (activation_key_val && activation === 'yes') {
			// Make Activation and Partner read only.
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).after( '<a id="reactivate_key" style="cursor:pointer; margin-left:10px">Reactivate</a><input value="0" id="reactivate_smb" type="hidden" name="reactivate_smb"/>' );
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).prop( 'readonly', true );
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).prop( 'readonly', true );
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).closest( 'tr' ).show();
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).show();
		}

		if ( ! activation_key_val && activation === 'yes') {
			$( '.' + environment + '_traditional_div' ).next( 'table' ).find( 'tr' ).hide();
		}

		if (activation === 'yes') {
			$( '.' + environment + '_traditional_div' ).hide();
			$( '.' + environment + '_smb_div' ).show();
			$( '.' + environment + '_smb_div' ).next( 'table' ).show();
			$( '.synchrony-promo-field' ).hide();
			$( '.synchrony-save-card-field' ).hide();
			$( '#woocommerce_synchrony-unifi-payments_show_unify_widget option[value="plp"]' ).remove();
			$( 'label[for="woocommerce_synchrony-unifi-payments_enable_savelater"]' ).hide();
			$( '#mainform' ).find( 'table:eq(11)' ).hide();
		} else {
			$( '.' + environment + '_traditional_div' ).next( 'table' ).find( 'tr' ).show();
			$( '.' + environment + '_traditional_div' ).show();
			$( '.' + environment + '_smb_div' ).hide();
			$( '.' + environment + '_smb_div' ).next( 'table' ).hide();
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).closest( 'tr' ).hide();
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).closest( 'tr' ).hide();
		}

		if ($( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).val()) {
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).hide();
			displayDomainInputValues( environment );
		} else {
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( '<div id="action_row_' + environment + '" style="margin-top:10px"><a id="add_smb_domain_row" style="cursor:pointer; margin-right:10px">Add Domain</a><div style="margin-top:10px"><button class="button-primary validate_credential_button" type="button" style="display: block; margin-top: 10px;">Activate</button><span id="validation_message" style="margin-left:10px"></span></div></div>' );
		}

		// Enable the activation key for deployed. Client id rotation.
		$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_digitalbuy_api_smb_client_id' ).keyup(
			function () {
				if ($( this ).val()) {
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_activation_key' ).prop( 'readonly', false );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_deployed_smb_domain' ).hide();
					$( '#action_row_deployed' ).html( '<a id="add_smb_domain_row" style="cursor:pointer; margin-right:10px">Add Domain</a><button class="button-primary validate_credential_button" type="button" style="display: block; margin-top: 10px;">Renew API Client ID</button>' );
				}
			}
		);

		// Enable the activation key for test. Client id rotation.
		$( '#woocommerce_synchrony-unifi-payments_synchrony_test_digitalbuy_api_smb_client_id' ).keyup(
			function () {
				if ($( this ).val()) {
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_activation_key' ).prop( 'readonly', false );
					$( '#woocommerce_synchrony-unifi-payments_synchrony_test_smb_domain' ).hide();
					$( '#action_row_test' ).html( '<a id="add_smb_domain_row" style="cursor:pointer; margin-right:10px">Add Domain</a><button class="button-primary validate_credential_button" type="button" style="display: block; margin-top: 10px;">Renew API Client ID</button>' );
				}
			}
		);

		// Show domain value based on api.
		function displayDomainInputValues(environment) {
			var domainArr = php_vars.domains;
			if ( domainArr ) {
				$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( '<div id="action_row_' + environment + '" style="margin-top:10px"><a id="add_smb_domain_row" style="cursor:pointer; margin-right:10px">Add Domain</a><button class="button-primary update_domain_button" type="button">Update</button><span id="validation_message" style="margin-left:10px"></span></div>' );
				$.each(
					domainArr,
					function (index,value) {
						var input = $( '<input>' );
						input.attr( 'type','text' );
						input.attr( 'class','input-text regular-input' );
						input.val( value );
						input.attr( 'name','woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain_additional[]' );
						var wrapper = $( '<div>' ).addClass( 'smb_domain_row' ).css( {'margin-top':'10px'} ).append( input,'<a style="cursor:pointer;margin-left:10px;" id="delete_row">Delete</a>' );
						$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( wrapper );
					}
				);
			} else {
				$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( '<div style="margin-top:10px; color: red;" class="smb_domain_row">Error in fetching domains</div>' );
			}
		}
		// Show and Hide traditional div.
		function showHideTraditionalDiv( environment, method ) {
			var traditionalDiv = '.' + environment + '_traditional_div';
			if ( 'show' === method ) {
				$( traditionalDiv ).next( 'table' ).find( 'tr' ).show();
				$( traditionalDiv ).show();
			} else {
				$( traditionalDiv ).next( 'table' ).find( 'tr' ).hide();
				$( traditionalDiv ).hide();
			}
		}

		// Show and Hide smb div.
		function showHideSMbDiv(environment, method) {
			var smbDiv = '.' + environment + '_smb_div';
			if ( 'show' === method ) {
				$( smbDiv ).show();
				$( smbDiv ).next( 'table' ).show();
			} else {
				$( smbDiv ).hide();
				$( smbDiv ).next( 'table' ).hide();
			}
		}

		function showFormElements() {
			$( '#mainform' ).find( 'table:eq(7), table:eq(8), table:eq(9), table:eq(10), table:eq(11)' ).show();
			$( '#mainform' ).find( 'h3:eq(7), h3:eq(8), h3:eq(9), h3:eq(10)' ).show();
		}

		// Add or Remove Domain input on click.
		$( document ).on(
			'click',
			'#add_smb_domain_row',
			function () {
				$( '.validation-message' ).remove();
				var current_domains = $( '.smb_domain_row' ).length;
				var max_domains     = 10;
				if ( current_domains >= max_domains ) {
					alert( 'Domain limit exceeded . you cannot add more that ' + max_domains + ' domains.' );
						$( '#add_smb_domain_row' ). prop( 'disabled', true );
					return;
				}
				var all_domain = $( 'input[name="woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain_additional[]"]' ).map(
					function () {
						if ($( this ).val()) {
							return $( this ).val();
						}
					}
				).get();
				if (all_domain.length < 1) {
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( '<div style="margin-top:10px" class="smb_domain_row"><input class="input-text regular-input" type="text" name="woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain_additional[]" /><a style="cursor:pointer;margin-left:10px;" id="delete_row">Delete</a></div>' );
				} else {
					$( '.smb_domain_row' ).last().after( '<div style="margin-top:10px" class="smb_domain_row"><input class="input-text regular-input" type="text" name="woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain_additional[]" /><a style="cursor:pointer;margin-left:10px;" id="delete_row">Delete</a></div>' );
				}
			}
		);
		$( document ).on(
			'click',
			'#delete_row',
			function () {
				$( '.validation-message' ).remove();
				$( this ).parent( '.smb_domain_row' ).remove();
				$( '#add_smb_domain_row' ). prop( 'disabled', false );
			}
		);

		// Run Domain Update Ajax Call.
		$( document ).on(
			'click',
			'.update_domain_button',
			function (e) {
				e.preventDefault();
				var button = $( this );
				button.prop( 'disabled', true );
				button.animate( {opacity: 0.5} , 200 );
				button.text( 'Updating....' );
				$( '.validation-message' ).remove();
				var all_domain = [];
				all_domain     = $( 'input[name="woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain_additional[]"]' ).map(
					function () {
						if ($( this ).val()) {
							return $( this ).val();
						}
					}
				).get();
				if (all_domain.length < 0) {
					button.after( '<span class="validation-message" style="color:red;margin-left:10px;">Invalid Domain<span>' );
					button.animate( {opacity: 1} , 200 );
					button.prop( 'disabled', false );
					button.text( 'Update' );
					return;
				}
				const all_domain_valid = all_domain.every( is_valid_domain );
				if ( ! all_domain_valid ) {
					button.after( '<span class="validation-message" style="color:red;margin-left:10px;">Invalid Domain Urls<span>' );
					button.animate( {opacity: 1} , 200 );
					button.prop( 'disabled', false );
					button.text( 'Update' );
					return;
				}
				var syf_nce       = php_vars.syf_nonce;
				var isadmin_value = php_vars.is_admin;
				var domain_data   = {
					'domain' : all_domain,
					'isadmin': isadmin_value
				};

				$.ajax(
					{
						method: 'POST',
						url: php_vars.site_url + '/wp-json/syf/v1/partner_domain',
						data: JSON.stringify( domain_data ),
						beforeSend: function (xhr) {
							xhr.setRequestHeader( 'X-Syf-Nonce', syf_nce );
						},
						contentType: 'application/json',
						success:function (response) {
							console.log( response );
							button.after( '<span class="validation-message" style="color:green;margin-left:10px;">Domains are Updated<span>' );
							location.reload();
						},
						error: function (xhr) {
							console.error( xhr.responseText );
							try {
								var resText       = xhr.responseText;
								var errorResponse = JSON.parse( resText ).message;
								var resMessage    = JSON.parse( errorResponse );
								errorResponse     = resMessage.message;
							} catch (e) {
								errorResponse = 'Something went wrong';
							}
							button.after( '<span class="validation-message" style="color:red; margin-left:10px;">' + errorResponse + '<span>' );
						},
						complete: function () {
							button.prop( 'disabled', false );
							button.text( 'Update' );
							button.animate( { opacity: 1}, 200 );
						}
					}
				);

			}
		);

		// Run Activate or Client ID rotation ajax call.
		$( document ).on(
			'click',
			'.validate_credential_button',
			function (e) {
				e.preventDefault();
				var button = $( this );
				button.prop( 'disabled', true );
				button.animate( {opacity: 0.5} , 200 );
				button.text( 'Activating....' );
				$( '.validation-message' ).remove();
				var is_enabled     = $( '#woocommerce_synchrony-unifi-payments_enabled' ).val();
				var client_id      = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_client_id' ).val();
				var activation_key = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).val();
				if ( ! activation_key) {
					button.after( '<span class="validation-message" style="color:red;margin-left:10px;">Invalid Activation Key<span>' );
					button.animate( {opacity: 1} , 200 );
					button.prop( 'disabled', false );
					button.text( 'Activate' );
					return;
				}
				var main_domain = $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).val();
				var check_domain_validation;
				var all_domain = [];
				all_domain     = $( 'input[name="woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain_additional[]"]' ).map(
					function () {
						if ($( this ).val()) {
							return $( this ).val();
						}
					}
				).get();

				if ( ! main_domain && 0 === all_domain.length ) {
					button.after( '<span class="validation-message" style="color:red;margin-left:10px;">Invalid Domain<span>' );
					button.animate( {opacity: 1} , 200 );
					button.prop( 'disabled', false );
					button.text( 'Activate' );
					return;
				}

				if ( all_domain.length > 0 ) {
					var check_domain_validation = all_domain.every( is_valid_domain );
				} else {
					var check_domain_validation = is_valid_domain( main_domain );
				}
				if ( ! check_domain_validation  ) {
					button.after( '<span class="validation-message" style="color:red;margin-left:10px;">Invalid Domain Url<span>' );
					button.animate( {opacity: 1} , 200 );
					button.prop( 'disabled', false );
					button.text( 'Activate' );
					return;
				}

				if (all_domain.length > 0) {
					if ( ! php_vars.domains ) {
						all_domain.unshift( main_domain );
					}
					main_domain = all_domain.join( ',' );
				}
				var syf_nce       = php_vars.syf_nonce;
				var isadmin_value = php_vars.is_admin;
				var reactivate    = 0;
				if (activation_key_val && activation === 'yes') {
					reactivate = 1;
				}
				var reactivate      = $( '#reactivate_smb' ).val();
				var activation_data = {
					'activation_key' : activation_key,
					'domain' : main_domain,
					'environment' : environment,
					'client_id'	: client_id,
					'isadmin': isadmin_value,
					'isenabled' : is_enabled,
					'reactivate' : reactivate
				};
				$.ajax(
					{
						method: 'POST',
						url: php_vars.site_url + '/wp-json/syf/v1/partner_activate',
						data: JSON.stringify( activation_data ),
						beforeSend: function (xhr) {
							xhr.setRequestHeader( 'X-Syf-Nonce', syf_nce );
						},
						contentType: 'application/json',
						success:function (response) {
							console.log( response );
							button.after( '<span class="validation-message" style="color:green;margin-left:10px;">Validation passed<span>' );
							location.reload();
							$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_smb_partner_id' ).prop( 'readonly', true );
						},
						error: function (xhr) {
							console.error( xhr.responseText );
							try {
								var resText       = xhr.responseText;
								var errorResponse = JSON.parse( resText ).message;
								var resMessage    = JSON.parse( errorResponse );
								errorResponse     = resMessage.message;
							} catch (e) {
								errorResponse = 'Something went wrong';
							}
							button.after( '<span class="validation-message" style="color:red; margin-left:10px;">' + errorResponse + '<span>' );
						},
						complete: function () {
							button.prop( 'disabled', false );
							button.text( 'Activate' );
							button.animate( { opacity: 1}, 200 );
						}
					}
				);

			}
		);

		$( document ).on(
			'click',
			'#reactivate_key',
			function (e) {
				$( '#reactivate_smb' ).val( 1 );
				$( '.smb_domain_row' ).remove();
				$( '#action_row_' + environment ).remove();
				$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_activation_key' ).prop( 'readonly', false );
				$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).show();
				$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_smb_domain' ).after( '<div id="action_row_' + environment + '" style="margin-top:10px"><a id="add_smb_domain_row" style="cursor:pointer; margin-right:10px">Add Domain</a><div style="margin-top:10px"><button class="button-primary validate_credential_button" type="button" style="display: block; margin-top: 10px;">Activate</button><span id="validation_message" style="margin-left:10px"></span></div></div>' );
			}
		);

		// Domain Validation.
		var is_valid_domain = ( url ) => {
			var pattern     = /^(https?:\/\/)/i;
			return ! ! pattern.test( url );
		};
		var $multi_select   = $( '#woocommerce_synchrony-unifi-payments_show_unify_widget' ).select2();
		$multi_select.change(
			function (e) {
				var selected = $( e.target ).val();
				if ($.inArray( 'all', selected ) !== -1) {
					$multi_select.val( null );
					$multi_select.val( 'all' );
					$multi_select.trigger( 'change.select2' );
				}
			}
		);

		function show_hide_multiwidget_field(){
			var activation_sandbox    = jQuery( '#woocommerce_synchrony-unifi-payments_test_enable_activation' ).val();
			var activation_production = jQuery( '#woocommerce_synchrony-unifi-payments_deployed_enable_activation' ).val();
			var sandbox_mode          = jQuery( '#woocommerce_synchrony-unifi-payments_synchrony_test' ).val();

			if ( ( sandbox_mode == 'yes' && activation_sandbox === 'yes' ) || ( sandbox_mode == 'no' && activation_production == 'yes' ) ) {
				// hide multiwidget fields.
				$( '#woocommerce_synchrony-unifi-payments_default_varient_price' ).closest( 'tr' ).hide();
				$( '#woocommerce_synchrony-unifi-payments_widget_display_approach' ).closest( 'tr' ).hide();
				$( '#woocommerce_synchrony-unifi-payments_cache_time_out' ).closest( 'tr' ).hide();
			} else {
				// show multiwidget fields.
				$( '#woocommerce_synchrony-unifi-payments_default_varient_price' ).closest( 'tr' ).show();
				$( '#woocommerce_synchrony-unifi-payments_widget_display_approach' ).closest( 'tr' ).show();
				$( '#woocommerce_synchrony-unifi-payments_cache_time_out' ).closest( 'tr' ).show();
			}
		}
		showHideChildMerchantId( environment );
		function showHideChildMerchantId( environment ) {
			if ( $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_merchant_id' ).val() === '' ) {
				// Hide Partner Code.
				$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_partner_code' ).closest( 'tr' ).hide();
			}

			// Hide promo tab on merchant id.
			if ( $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_merchant_id' ).val() !== '' ) {
				$( '#woocommerce_synchrony-unifi-payments_tag_rules_option' ).val( 0 );
				$( '#woocommerce_synchrony-unifi-payments_tag_rules_option' ).closest( 'tr' ).hide();
				$( '.synchrony-promo-field' ).hide();
			}

			// Show partner code on key up.
			$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_merchant_id' ).keyup(
				function () {
					$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_partner_code' ).closest( 'tr' ).show();
					$( '#woocommerce_synchrony-unifi-payments_tag_rules_option' ).val( 0 );
					$( '#woocommerce_synchrony-unifi-payments_tag_rules_option' ).closest( 'tr' ).hide();
					$( '.synchrony-promo-field' ).hide();
					if ( $( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_merchant_id' ).val() === '' ) {
						// Hide Partner Code.
						$( '#woocommerce_synchrony-unifi-payments_synchrony_' + environment + '_digitalbuy_api_child_partner_code' ).closest( 'tr' ).hide();
						$( '#woocommerce_synchrony-unifi-payments_tag_rules_option' ).closest( 'tr' ).show();
						$( '.synchrony-promo-field' ).show();
					}
				}
			);
		}

		jQuery( document ).on(
			'change',
			'#woocommerce_synchrony-unifi-payments_test_enable_activation',
			function () {
				show_hide_multiwidget_field();
			}
		);
		show_hide_multiwidget_field();
	}
);
