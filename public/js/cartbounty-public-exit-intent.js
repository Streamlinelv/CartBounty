(function($){
	'use strict';
	 
	jQuery(document).ready(function(){

	 	var timer;

	 	function showExitIntentForm(event){
	 		var currentTime = new Date().getTime();
			var timePeriod = cartbounty_ei.hours; //Time period in hours
			var last_time_displayed = localStorage.getItem('cartbounty_ei_last_time');
			var product_count = cartbounty_ei.product_count; //Products in the shopping cart

			if(localStorage.getItem('cartbounty_product_count') !== null){
				product_count = localStorage.getItem('cartbounty_product_count');
			}

			if(product_count == 0) return; //Exit if we have no products

			if (event.clientY <= 0 && event.target.tagName.toLowerCase() != "select" && event.target.tagName.toLowerCase() != "option" && event.target.tagName.toLowerCase() != "input") { //Checking if mouse Y poosition goes beyond the top screen and that we haven't clicked on dropdown or autocomplete input field

		        if(last_time_displayed == null || timePeriod == 0) { //If time period has passed or Exit Intent test mode is enabled
		            $('#cartbounty-exit-intent-form').addClass('cartbounty-visible'); //Display form
		        	$('#cartbounty-exit-intent-form-backdrop').css('opacity', '').addClass('cartbounty-visible'); //Show backdrop
		        	if(timePeriod != 0){
		        		localStorage.setItem('cartbounty_ei_last_time', currentTime);
		        	}
		        }else{
		        	if(currentTime - last_time_displayed > timePeriod * 60 * 60 * 1000){ // If the time has expired, clear the cookie
						localStorage.removeItem('cartbounty_ei_last_time');
					}
		        }
		    }
	 	}

		function getExitIntentEmail() { //Reading email entered in exit intent
			let email = jQuery('#cartbounty-exit-intent-email').val() || '';
			
			if(email.length > 0){				
				clearTimeout(timer);
				var emailValidation = cartbounty_co.email_validation; //Regex validation
				var consent = cartbounty_co.consent_field;
				
				if( email.match(emailValidation) ){
					var fields = {
						email: 			email,
					};

					if( consent ){
						fields[consent] = 1;
					}

					var data = {
						action:			"cartbounty_save",
						nonce:			cartbounty_co.nonce,
						source:			"cartbounty_exit_intent",
						customer:		fields,
					}

					timer = setTimeout(function(){
						jQuery.post(cartbounty_co.ajaxurl, data,
						function(response) {
							if(response.success){ //If successfuly saved data
								localStorage.setItem('cartbounty_contact_saved', true);
							}
						});
					}, 600);
				}
			}
		}

		function increaseProductCount(){ //Increasing product count
			if( localStorage.getItem( 'cartbounty_product_count' ) === null ){
				localStorage.setItem( 'cartbounty_product_count', 1 );

			}else{
				localStorage.setItem( 'cartbounty_product_count', parseInt( localStorage.getItem( 'cartbounty_product_count' ) ) + 1 );
			}
		}

		function decreaseProductCount(){ //Decreasing product count
			if( localStorage.getItem( 'cartbounty_product_count' ) === null ) return;

			localStorage.setItem( 'cartbounty_product_count', parseInt( localStorage.getItem( 'cartbounty_product_count' ) ) - 1 );
		}

		function closeExitIntentForm(){ //Close exit intent window
			$('#cartbounty-exit-intent-form').addClass('cartbounty-fade-out'); //Hide form

			setTimeout(function(){
				$('#cartbounty-exit-intent-form').removeClass('cartbounty-visible'); //Hide form
				$('#cartbounty-exit-intent-form-backdrop').removeClass('cartbounty-visible'); //Hide backdrop
			}, 300);
		}

	 	//Handling Exit Intent form submit button. Making sure the page is not reloaded
		function handleFormAfterSubmit(e){
			e.preventDefault();

			//If form is invalid, let browser show validation UI
			if (!this.checkValidity()){
				this.reportValidity(); //Triggers browser validation tooltip
				return;
			}

			closeExitIntentForm();
		}

		jQuery(document).on("mouseleave", showExitIntentForm); //Displaying Exit intent if the mouse leaves the window
		jQuery("#cartbounty-exit-intent-email").on("keyup keypress change", getExitIntentEmail ); //All action happens on or after changing Email field. Data saved to Database only after Email fields have been entered.
		jQuery("#cartbounty-exit-intent-close, #cartbounty-exit-intent-form-backdrop").on("click", closeExitIntentForm ); //Close Exit intent window
		jQuery('#cartbounty-exit-intent-form form').on('submit', handleFormAfterSubmit); //Closing Exit Intent form
		jQuery(document).on("added_to_cart", increaseProductCount ); //Increasing product count if Ajax Add to Cart button pressed
		jQuery(document).on("removed_from_cart", decreaseProductCount ); //Firing the function if item is removed from cart via Ajax 
	});

})(jQuery);