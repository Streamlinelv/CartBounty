(function( $ ) {
	'use strict';
	 
	 jQuery(document).ready(function(){

		var timer;
		var save_custom_fields = cartbounty_co.save_custom_fields;
		var custom_checkout_fields = cartbounty_co.checkout_fields;
		var custom_email_selectors = cartbounty_co.custom_email_selectors;
		var custom_phone_selectors = cartbounty_co.custom_phone_selectors;
		var custom_button_selectors = cartbounty_co.custom_button_selectors;
		var contact_saved = localStorage.getItem('cartbounty_contact_saved');

		function getCheckoutData(){ //Reading WooCommerce field values
			let email = jQuery('#email').val() || jQuery('#billing_email').val() || '';
			let phone = jQuery('#billing-phone').val() || jQuery('#shipping-phone').val() || jQuery('#billing_phone').val() || '';
			
			if(email.length > 0 || phone.length > 0){
				let checkoutFields = {};
				jQuery(custom_checkout_fields).each(function () {
					let field = jQuery(this);
					let fieldId = field.attr('id');
					let fieldValue = field.val();
					
					if(field.is('textarea') && field.closest('#order-notes').length > 0){ //If handling order notes field that does not have an ID
						fieldId = 'order_notes';
						
					}else if(field.is(':checkbox')){ //If handling checkbox fields
						// Handle checkbox fields
						fieldValue = field.is(':checked') ? 1 : 0;
					}

					checkoutFields[fieldId] = fieldValue;
				});

				clearTimeout(timer);
				var emailValidation = cartbounty_co.email_validation; //Regex validation
				var phoneValidation = cartbounty_co.phone_validation; //Regex validation

				if ( email.match(emailValidation) || phone.match(phoneValidation) ){
					timer = setTimeout(function(){
						let data = {
							action:				"cartbounty_save",
							nonce:				cartbounty_co.nonce,
							customer:			checkoutFields,
				 		}

						jQuery.post(cartbounty_co.ajaxurl, data,
						function(response){
							
							if(response.success){ //If successfuly saved data
								localStorage.setItem('cartbounty_contact_saved', true);
								removeExitIntentForm(); //If we have successfully captured abandoned cart, we do not have to display Exit intent form anymore
							}
						});
						
					}, 600);
				}
			}
		}

		function saveCustomField(){ //Function for saving custom email field
			var custom_field_selector = jQuery(this);
			var cartbounty_contact_saved = localStorage.getItem('cartbounty_contact_saved');

			if(cartbounty_contact_saved){ //Exit in case any of CartBounty tools have already saved data
				return;
			}

			if(jQuery(custom_field_selector).length > 0 && !contact_saved){ //If email or phone field is present and contact information is not saved
				var cartbounty_custom_field = jQuery(custom_field_selector).val() || '';
				var emailValidation = cartbounty_co.email_validation; //Regex validation
				var phoneValidation = cartbounty_co.phone_validation; //Regex validation

				if(cartbounty_custom_field != ''){ //If email or phone is not empty
					
					if(cartbounty_custom_field.match(emailValidation)){
						localStorage.setItem('cartbounty_custom_email', cartbounty_custom_field); //Saving user's input in browser memory

					}else if(cartbounty_custom_field.match(phoneValidation)){ //In case if phone number entered
						localStorage.setItem('cartbounty_custom_phone', cartbounty_custom_field); //Saving user's input in browser memory
					}
				}
			}
		}

		function passCustomFieldToCartBounty(){ //Function passes custom email or phone field to backend
			var cartbounty_custom_email_stored = localStorage.getItem('cartbounty_custom_email');
			var cartbounty_custom_phone_stored = localStorage.getItem('cartbounty_custom_phone');
			var cartbounty_contact_saved = localStorage.getItem('cartbounty_contact_saved');

			if( ( cartbounty_custom_email_stored == null && cartbounty_custom_phone_stored == null ) || cartbounty_contact_saved ){ //If data is missing or any of the CartBounty tools have already saved data - exit
				return;
			}

			var fields = {
				email: 			cartbounty_custom_email_stored,
				phone: 			cartbounty_custom_phone_stored,
			};
			
			var data = {
				action:			"cartbounty_save",
				source:			"cartbounty_custom_field",
				nonce:			cartbounty_co.nonce,
				customer:		fields,
			}

			jQuery.post(cartbounty_co.ajaxurl, data, //Send data over to backend for saving
			function(response) {
				if(response.success){ //If data successfuly saved
					localStorage.setItem('cartbounty_contact_saved', true);
					removeCustomFields();
					removeExitIntentForm();
					jQuery(document).off( 'added_to_cart', passCustomFieldToCartBounty );
				}
			});
		}

		function removeCustomFields(){ //Removing from local storage custom email and phone fields
			localStorage.removeItem('cartbounty_custom_email');
			localStorage.removeItem('cartbounty_custom_phone');
		}

		function removeExitIntentForm(){//Removing Exit Intent form
			if(jQuery('#cartbounty-exit-intent-form').length > 0){ //If Exit intent HTML exists on page
				jQuery('#cartbounty-exit-intent-form').remove();
				jQuery('#cartbounty-exit-intent-form-backdrop').remove();
			}
		}

		//Adding a hidden input field to all "Add to cart" forms on click to protect against bots leaving anonymous carts since they may not use Javascript
		function appendHiddenInputField(){
			var $target = jQuery(this);
			var customInputField = jQuery('<input>',{
				type: 'hidden',
				name: 'cartbounty_bot_test',
				value: '1'
			});

			var $form = $target.closest('form.cart');

			if($form.length){
				$form.append(customInputField);
			}else{
				$target.data('cartbounty_bot_test', '1');
			}
		}

		//Adding additional CartBounty data whenever Ajax "Add to cart" button is clicked in order to detect anonymous carts left by bots
		function addCartBountyInputDataToAjax(e, xhr, settings){
			if(settings.url.indexOf('wc-ajax=add_to_cart') > -1){
				settings.data += '&cartbounty_bot_test=1';
			}
		};

		//If Ajax Add to cart has been disabled under WooCommerce settings - store bot test result in local storage
		function saveBotTestResult(){
			localStorage.setItem('cartbounty_bot_test_passed', true);
		}

		//Save anonymous cart in case Ajax Add to cart has been disabled under WooCommerce settings and the cart has not been already saved. Necessary mostly for home shop page
		function saveAnonymousCart(){
			let bot_test_passed = localStorage.getItem('cartbounty_bot_test_passed');

			if(bot_test_passed){
				var data = {
					action:			"cartbounty_save",
					source:			"cartbounty_anonymous_bot_test",
					nonce:			cartbounty_co.nonce
				}
				setTimeout(function(){
					jQuery.post(cartbounty_co.ajaxurl, data, //Send data over to backend for saving
					function(response){
						if(response.success){ //If data successfuly saved
							localStorage.removeItem('cartbounty_bot_test_passed');
						}
					});
				}, 1000);
			}
		}
		
		jQuery('.wc-block-checkout, .woocommerce-checkout').on( 'keyup keypress change', 'input, textarea, select', getCheckoutData );
		jQuery(window).on( 'load', getCheckoutData ); //Automatically collect and save input field data if input fields already filled on page load
		jQuery(document).ajaxSend(addCartBountyInputDataToAjax);
		jQuery(document).on('click', custom_button_selectors, appendHiddenInputField);
		jQuery(document).on('click', custom_button_selectors, saveBotTestResult);

		if( ( save_custom_fields && !contact_saved ) ){ //If custom field saving enabled and contact is not saved - try to save email or phone
			passCustomFieldToCartBounty();
			jQuery(document).on( 'keyup keypress change', custom_email_selectors + ', ' + custom_phone_selectors, saveCustomField );
			jQuery(document).on( 'added_to_cart', passCustomFieldToCartBounty ); //Sending data over for saving in case WooCommerce "added_to_cart" event fires after product added to cart
		}

		saveAnonymousCart();
	});

})( jQuery );