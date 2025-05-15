<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/public
 * @author     Streamline.lv
 */
 
class CartBounty_Public{
	
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ){
		global $wpdb;
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public area.
	 *
	 * @since    3.0
	 */
	public function enqueue_styles(){
		if($this->tool_enabled( 'exit_intent' )){ //If Exit Intent Enabled
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartbounty-public.css', array(), $this->version, 'all' );
			wp_style_add_data( $this->plugin_name, 'rtl', 'replace' );
		}
	}

	/**
	 * Register the javascripts for the public area.
	 *
	 * @since    3.0
	 */
	public function enqueue_scripts(){

		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$admin_ajax = admin_url( 'admin-ajax.php' );
		$email_validation = apply_filters( 'cartbounty_email_validation', '^[^\s@]+@[^\s@]+\.[^\s@]{2,}$');

		if( $this->tool_enabled( 'exit_intent' ) ){ //If Exit Intent Enabled
			$ei_settings = $admin->get_settings( 'exit_intent' );
			$hours = 1;

			if( $ei_settings['test_mode'] ){ //If test mode is enabled
				$hours = 0;
			}

			$data_ei = array(
				'hours' 		=> $hours,
				'product_count' => $this->get_cart_product_count(),
			);

			wp_enqueue_script( $this->plugin_name . '-exit-intent', plugin_dir_url( __FILE__ ) . 'js/cartbounty-public-exit-intent.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name . '-exit-intent', 'cartbounty_ei', $data_ei ); //Sending variable over to JS file
		}

		$data_co = array(
			'save_custom_fields' 		=> apply_filters( 'cartbounty_save_custom_fields', true ),
			'checkout_fields' 			=> $this->get_checkout_fields(),
			'custom_email_selectors' 	=> $this->get_custom_email_selectors(),
			'custom_phone_selectors' 	=> $this->get_custom_phone_selectors(),
			'custom_button_selectors' 	=> $this->get_custom_add_to_cart_button_selectors(),
			'consent_field' 			=> $admin->get_consent_field_data( 'field_name' ),
			'email_validation' 			=> $email_validation,
			'phone_validation' 			=> apply_filters( 'cartbounty_phone_validation', '^[+0-9\s]\s?\d[0-9\s-.]{6,30}$'),
			'nonce' 					=> wp_create_nonce( 'user_data' ),
			'ajaxurl' => $admin_ajax
		);

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'cartbounty_co', $data_co );
	}

	/**
	 * Method that takes care of saving and updating carts
	 *
	 * @since    5.0
	 */
	function save_cart(){
		$anonymous = true;

		if( isset( $_GET['cartbounty'] ) ) return;

		if( !WC()->cart ) return;

		if( $this->is_bot() ) return;

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );

		if( $this->cart_recoverable() ){ //If cart is recoverable
			$anonymous = false;
			$this->update_logged_customer_id(); //If current user had an abandoned cart before - restore session ID (in case of user switching)
		}

		if( $admin->get_settings( 'settings', 'exclude_anonymous_carts' ) && $anonymous ) return;

		$cart = $this->read_cart();
		$cart_saved = $this->cart_saved( $cart['session_id'] );
		$user_data = $this->get_user_data();

		if( $cart_saved ){ //If cart has already been saved
			$result = $this->update_cart( $cart, $user_data, $anonymous );

		}else{
			$result = $this->create_new_cart( $cart, $user_data, $anonymous );
		}

		if( isset( $_POST["action"] ) ){ //In case we are saving cart data via Ajax coming from CartBounty tools - try and return the result
			if( $_POST["action"] == 'cartbounty_save' ){
				if( $result ){
					wp_send_json_success();

				}else{
					wp_send_json_error();
				}
			}
		}
	}

	/**
	 * Method creates a new cart
	 *
	 * @since    5.0
	 * @return   boolean
	 * @param    array      $cart     		Cart contents
	 * @param    array      $user_data      User's data
	 * @param    boolean    $anonymous    	If the cart is anonymous or not
	 */
	function create_new_cart( $cart = array(), $user_data = array(), $anonymous = false ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		//In case if the cart has no items in it, we must delete the cart
		if( empty( $cart['cart_contents']['products'] ) ){
			$admin->clear_cart_data();
			return;
		}

		if( $anonymous ){ //If dealing with anonymous cart
			//Inserting row into database
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $cart_table
					( location, cart_contents, cart_total, currency, time, session_id )
					VALUES ( %s, %s, %0.2f, %s, %s, %s )",
					array(
						'location'		=> sanitize_text_field( serialize( $user_data['location'] ) ),
						'cart_contents'	=> serialize( $cart['cart_contents'] ),
						'total'			=> sanitize_text_field( $cart['cart_total'] ),
						'currency'		=> sanitize_text_field( $cart['cart_currency'] ),
						'time'			=> sanitize_text_field( $cart['current_time'] ),
						'session_id'	=> sanitize_text_field( $cart['session_id'] )
					)
				)
			);
			$this->increase_anonymous_cart_count();

		}else{
			$other_fields = NULL;
			$cart_source = $this->get_cart_source();
			$source = $cart_source['source'];
			$email_consent = false;

			if( empty( $source ) ){ //If source not coming from Tools
				$source = 'NULL';
			}

			if( !empty( $user_data['other_fields'] ) ){
				$other_fields = sanitize_text_field( serialize( $user_data['other_fields'] ) );
			}
			//Inserting row into Database
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $cart_table
					( name, surname, email, phone, email_consent, location, cart_contents, cart_total, currency, time, session_id, other_fields, saved_via )
					VALUES ( %s, %s, %s, %s, %d, %s, %s, %0.2f, %s, %s, %s, %s, %s )",
					array(
						'name'			=> sanitize_text_field( $user_data['name'] ),
						'surname'		=> sanitize_text_field( $user_data['surname'] ),
						'email'			=> sanitize_email( $user_data['email'] ),
						'phone'			=> filter_var( $user_data['phone'], FILTER_SANITIZE_NUMBER_INT),
						'email_consent'	=> sanitize_text_field( $email_consent ),
						'location'		=> serialize( $user_data['location'] ),
						'cart_contents'	=> serialize( $cart['cart_contents'] ),
						'total'			=> sanitize_text_field( $cart['cart_total'] ),
						'currency'		=> sanitize_text_field( $cart['cart_currency'] ),
						'time'			=> sanitize_text_field( $cart['current_time'] ),
						'session_id'	=> sanitize_text_field( $cart['session_id'] ),
						'other_fields'	=> $other_fields,
						'saved_via'		=> $source
					)
				)
			);
			$this->increase_recoverable_cart_count();
		}
		$this->set_cartbounty_session( $cart['session_id'] );

		return true;
	}

	/**
	 * Method updates a cart
	 *
	 * @since    5.0
	 * @return   boolean
	 * @param    array      $cart     		Cart contents
	 * @param    array      $user_data      User's data
	 * @param    boolean    $anonymous   	If the cart is anonymous or not
	 */
	function update_cart( $cart = array(), $user_data = array(), $anonymous = false ){
		//In case if the cart has no items in it, we must delete the cart
		if( empty( $cart['cart_contents']['products'] ) ){
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
			$admin->clear_cart_data();
			return;
		}

		if( $anonymous ){ //In case of anonymous cart
			$this->update_cart_data($cart);

		}else{
			$save_user_data = false;

			if( isset( $_POST["action"] ) ){

				if( $_POST["action"] == 'cartbounty_save' ){
					$save_user_data = true;
				}
			}

			if( is_user_logged_in() || $save_user_data ){
				$this->update_cart_and_user_data( $cart, $user_data );

			}else{
				$this->update_cart_data( $cart );
			}
		}

		$this->set_cartbounty_session( $cart['session_id'] );
		return true;
	}

	/**
	 * Method updates only cart related data excluding customer details
	 *
	 * @since    5.0
	 * @param    Array    $cart    Cart contents inclding cart session ID
	 */
	function update_cart_data( $cart ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		
		$updated_rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET cart_contents = %s,
				cart_total = %0.2f,
				currency = %s,
				time = %s
				WHERE session_id = %s AND
				type = %d",
				serialize( $cart['cart_contents'] ),
				sanitize_text_field( $cart['cart_total'] ),
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$cart['session_id'],
				$admin->get_cart_type( 'abandoned' )
			)
		);

		$this->delete_duplicate_carts( $cart['session_id'], $updated_rows );
	}

	/**
	 * Method updates both customer data and contents of the cart
	 *
	 * @since    5.0
	 * @param    Array    $cart    		Cart contents inclding cart session ID
	 * @param    Array    $user_data    User's data
	 */
	function update_cart_and_user_data( $cart, $user_data ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$other_fields = NULL;
		$cart_source = $this->get_cart_source();
		$source = $cart_source['source'];
		$email_consent = $user_data['email_consent'];

		if( empty( $source ) ){ //If source not coming from Tools
			$source = 'NULL';
		}

		if( !empty( $user_data['other_fields'] ) ){
			$other_fields = sanitize_text_field( serialize( $user_data['other_fields'] ) );
		}

		$updated_rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET name = %s,
				surname = %s,
				email = %s,
				phone = %s,
				email_consent = %d,
				location = %s,
				cart_contents = %s,
				cart_total = %0.2f,
				currency = %s,
				time = %s,
				other_fields = %s,
				saved_via = $source
				WHERE session_id = %s AND
				type = %d",
				sanitize_text_field( $user_data['name'] ),
				sanitize_text_field( $user_data['surname'] ),
				sanitize_email( $user_data['email'] ),
				filter_var( $user_data['phone'], FILTER_SANITIZE_NUMBER_INT),
				sanitize_text_field( $email_consent ),
				sanitize_text_field( serialize( $user_data['location'] ) ),
				serialize( $cart['cart_contents'] ),
				sanitize_text_field( $cart['cart_total'] ),
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$other_fields,
				$cart['session_id'],
				$admin->get_cart_type( 'abandoned' )
			)
		);

		$this->delete_duplicate_carts( $cart['session_id'], $updated_rows );
		$this->increase_recoverable_cart_count();
		$this->update_woocommerce_database_session( (object)$user_data );
	}

	/**
	 * Method shows if the current user can be identifiable and the cart can be recovered later
	 *
	 * @since    5.0
	 * @return   Boolean
	 */
	function cart_recoverable(){
		$recoverable = false;

		if( is_user_logged_in() || isset( $_POST["action"] ) || $this->cart_identifiable() ){
			$recoverable = true;
		}

		if( isset( $_POST["source"] ) ){

			if( $_POST["source"] == 'cartbounty_anonymous_bot_test' ){
				$recoverable = false;
			}
		}

		return $recoverable;
	}

	/**
	 * Method returns True in case the current user has got a cart that contains a phone or email
	 *
	 * @since    5.0
	 * @return   Boolean
	 */
	function cart_identifiable(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$identifiable = false;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$where_sentence = $admin->get_where_sentence('recoverable');
		$cart = $this->read_cart();

		//Checking if we have this abandoned cart in our database already
		$result = $wpdb->get_var($wpdb->prepare(
			"SELECT id
			FROM $cart_table
			WHERE session_id = %s
			$where_sentence",
			$cart['session_id']
		));

		if($result){
			$identifiable = true;
		}

		return $identifiable;
	}

	/**
	 * Method returns available user's data
	 *
	 * @since    5.0
	 * @return   Array
	 */
	function get_user_data(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$user_data = array(
			'name'			=> '',
			'surname'		=> '',
			'email'			=> '',
			'phone'			=> '',
			'email_consent'	=> '',
			'location'		=> '',
			'other_fields'	=> '',
		);
		$location = array(
			'country' 	=> '',
			'city' 		=> '',
			'postcode' 	=> ''
		);
		$other_fields = array();
		$email_consent_field_name = $admin->get_consent_field_name();

		if( is_user_logged_in() && !isset( $_POST["action"] ) ){ //If user has signed in and the request is not triggered by checkout fields or Exit Intent
			$current_user = wp_get_current_user();

			if( $current_user->billing_first_name ){
				$user_data['name'] = sanitize_text_field( $current_user->billing_first_name );
			
			}else{
				$user_data['name'] = sanitize_text_field( $current_user->user_firstname );
			}

			if( $current_user->billing_last_name ){
				$user_data['surname'] = sanitize_text_field( $current_user->billing_last_name );
			
			}else{
				$user_data['surname'] = sanitize_text_field( $current_user->user_lastname );
			}

			if( $current_user->billing_email ){
				$user_data['email'] = sanitize_email( $current_user->billing_email );
			
			}else{
				$user_data['email'] = sanitize_email( $current_user->user_email );
			}

			if( $current_user->billing_phone ){
				$user_data['phone'] = sanitize_text_field( $current_user->billing_phone );
			}

			if( $current_user->billing_country ){
				$location['country'] = sanitize_text_field( $current_user->country );
			}

			if( $current_user->billing_city ){
				$location['city'] = sanitize_text_field( $current_user->billing_city );
			}

			if( $current_user->billing_postcode ){
				$location['postcode'] = sanitize_text_field( $current_user->billing_postcode );
			}

			//Try to check if registered user has consent data saved
			$customer = new WC_Customer( get_current_user_id() );

			if( $customer->get_meta( $email_consent_field_name ) ){
				$user_data['email_consent'] = 1;
			}

		}else{

			if( check_ajax_referer( 'user_data', 'nonce', false ) ){ //If security check passed or user has logged in

				if( isset( $_POST['customer'] ) ){
					$customer = $_POST['customer'];

					//In case of data coming from block checkout and user has only provided Shipping details - change shipping fields to billing fields
					if( !preg_grep( '/^billing-/', array_keys( $customer ) ) && preg_grep( '/^shipping-/', array_keys( $customer ) ) ){
						
						foreach( $customer as $key => $value ){
							
							if( strpos( $key, 'shipping-' ) === 0 ){
								$new_key = str_replace( 'shipping-', 'billing-', $key );
								$customer[$new_key] = $value;
								unset( $customer[$key] );
							}
						}

						$other_fields['converted-shipping-to-billing'] = true;
					}

					foreach( $customer as $key => $value ){

						if( $key == 'billing-first_name' || $key == 'billing_first_name' ){
							$user_data['name'] = sanitize_text_field( $value );

						}elseif( $key == 'billing-last_name' || $key == 'billing_last_name' ){
							$user_data['surname'] = sanitize_text_field( $value );
						
						}elseif( $key == 'email' || $key == 'billing_email' ){
							$user_data['email'] = sanitize_email( $value );

						}elseif( $key == 'billing-phone' || $key == 'billing_phone' || $key == 'phone' ){
							$user_data['phone'] = sanitize_text_field( $value );

						}elseif( $key == $email_consent_field_name ){
							$user_data['email_consent'] = sanitize_text_field( $value );

						}elseif( $key == 'billing-country' || $key == 'billing_country' ){
							$location['country'] = sanitize_text_field( $value );

						}elseif( $key == 'billing-city' || $key == 'billing_city' ){
							$location['city'] = sanitize_text_field( $value );

						}elseif( $key == 'billing-postcode' || $key == 'billing_postcode' ){
							$location['postcode'] = sanitize_text_field( $value );

						}else{
							$other_fields[$key] = sanitize_text_field( $value );
						}
					}
				}
			}
		}

		if( empty( $location['country'] ) ){ //Try to locate country in case unknown
			$country = WC_Geolocation::geolocate_ip();
			$location['country'] = $country['country'];
		}

		$user_data['location'] = $location;
		$user_data['other_fields'] = $other_fields;
		return $user_data;
	}

	/**
	 * Check if visitor is a bot
	 * Checking only "Add to cart" actions in case of guest users
	 *
	 * @since    8.4
	 * @return   boolean
	 */
	function is_bot(){

		if( is_user_logged_in() ) return;

		if( current_filter() != 'woocommerce_add_to_cart' ) return;

		$bot = false;

		if( !apply_filters( 'cartbounty_disable_input_bot_test', false ) ){
			
			if( ( !isset( $_POST['cartbounty_bot_test'] ) || sanitize_text_field( $_POST['cartbounty_bot_test'] ) != '1' ) ){ 
				$bot = true;
			}
		}

		return $bot;
	}

	/**
	 * Method checks if current shopping cart has been saved in the past 2 hours (by default) basing upon session ID
	 * Cooldown period introduced to prevent creating new abandoned carts during the same session after user has already placed a new order
	 * New cart for the same user in the same session will be created once the cooldown period has ended
	 *
	 * @since    3.0
	 * @return   boolean
	 * @param    $session_id    Session ID
	 */
	function cart_saved( $session_id ){
		$saved = false;

		if( $session_id !== NULL ){
			global $wpdb;
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$time = $admin->get_time_intervals();
			$cooldown_period = apply_filters( 'cartbounty_cart_cooldown_period', $time['two_hours'] );

			//Checking if we have this abandoned cart in our database already
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT session_id
					FROM $cart_table
					WHERE session_id = %s AND
					time > %s",
					$session_id,
					$cooldown_period
				)
			);

			if( $result ){
				$saved = true;
			}
		}

		return $saved;
	}

	/**
	 * Method sets CartBounty session id value
	 *
	 * @since    5.0
	 */
	function set_cartbounty_session($session_id){
		if(!WC()->session->get('cartbounty_session_id')){ //In case browser session is not set, we make sure it gets set
			WC()->session->set('cartbounty_session_id', $session_id); //Storing session_id in WooCommerce session
		}
	}

	/**
	 * Method updates abandoned cart session from unknown session customer_id to known one in case if the user logs in
	 *
	 * @since    4.4
	 */
	function update_logged_customer_id(){
		if(is_user_logged_in()){ //If a user is logged in
			if(!WC()->session){ //If session does not exist, exit function
				return;
			}
			$customer_id = WC()->session->get_customer_id();

			if( WC()->session->get('cartbounty_session_id') !== NULL && WC()->session->get('cartbounty_session_id') !== $customer_id){ //If session is set and it is different from the one that currently is assigned to the customer
				global $wpdb;
				$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

				//Updating session ID to match the one of a logged in user
				$wpdb->update(
					$cart_table,
					array('session_id' => $customer_id),
					array('session_id' => WC()->session->get('cartbounty_session_id')),
					array('%s'),
					array('%s')
				);

				WC()->session->set('cartbounty_session_id', $customer_id);
				$cart = $this->read_cart();
				$user_data = $this->get_user_data();
				$this->update_cart_and_user_data( $cart, $user_data ); //Updating logged in user cart data so we do not have anything that was entered in the checkout form prior the user signed in

			}else{
				return;
			}

		}else{
			return;
		}
	}

	/**
	 * Method deletes duplicate abandoned carts from the database
	 *
	 * @since    4.4
	 * @param    $session_id			Session ID
	 * @param    $duplicate_count		Number of duplicate carts
	 */
	private function delete_duplicate_carts( $session_id, $duplicate_count ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		if($duplicate_count){ //If we have updated at least one row
			if($duplicate_count > 1){ //Checking if we have updated more than a single row to know if there were duplicates
				$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
				$where_sentence = $admin->get_where_sentence('anonymous');
				//First delete all duplicate anonymous carts
				$deleted_duplicate_anonymous_carts = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $cart_table
						WHERE session_id = %s
						$where_sentence",
						$session_id
					)
				);

				$limit = $duplicate_count - $deleted_duplicate_anonymous_carts - 1;
				if($limit < 1){
					$limit = 0;
				}

				$wpdb->query( //Leaving one cart remaining that can be identified
					$wpdb->prepare(
						"DELETE FROM $cart_table
						WHERE session_id = %s AND
						type != %d
						ORDER BY id DESC
						LIMIT %d",
						$session_id,
						$admin->get_cart_type('recovered'),
						$limit
					)
				);
			}
		}
	}

	/**
	 * Method builds and returns an array of cart products and their quantities, car total value, currency, time, session id
	 *
	 * @since    3.0
	 * @return   Array
	 */
	function read_cart(){

		if( !WC()->cart ) return; //Exit if Woocommerce cart has not been initialized

		//Retrieving cart total value and currency
		$cart_total = WC()->cart->total;
		$cart_currency = $this->get_selected_currency();
		$current_time = current_time( 'mysql', false ); //Retrieving current time
		$session_id = WC()->session->get( 'cartbounty_session_id' ); //Check if the session is already set
		
		if( empty( $session_id ) ){ //If session value does not exist - set one now
			$session_id = WC()->session->get_customer_id(); //Retrieving customer ID from WooCommerce sessions variable
		}

		if( WC()->session->get( 'cartbounty_from_link' ) && WC()->session->get( 'cartbounty_session_id' ) ){
			$session_id = WC()->session->get( 'cartbounty_session_id' );
		}

		//Retrieving cart
		$products = WC()->cart->get_cart_contents();
		$product_array = array();
				
		foreach( $products as $key => $product ){
			$item = wc_get_product( $product['data']->get_id() );
			$product_title = strip_tags( $item->get_title() );
			$product_quantity = $product['quantity'];
			$product_variation_price = '';
			$product_tax = '';
			$variation_attributes = array();
			$product_variation_id = '';
			$product_attributes = '';

			if( isset( $product['line_total'] ) ){
				$product_variation_price = $product['line_total'];
			}

			if( isset( $product['line_tax'] ) ){ //If we have taxes, add them to the price
				$product_tax = $product['line_tax'];
			}

			//Handling product variations
			if( isset( $product['variation'] ) ){
				
				if( is_array( $product['variation'] ) ){
					foreach( $product['variation'] as $key => $variation ){
						$variation_attributes[$key] = $variation;
					}
				}
			}

			if( isset( $product['variation_id'] ) ){ //If user has chosen a variation
				$product_variation_id = $product['variation_id'];
				$product_attributes = $this->get_attribute_names( $variation_attributes, $product['product_id'] );
			}

			$product_data = array(
				'product_title' 				=> $product_title . $product_attributes,
				'quantity' 						=> $product_quantity,
				'product_id'					=> $product['product_id'],
				'product_variation_id' 			=> $product_variation_id,
				'product_variation_price' 		=> $product_variation_price,
				'product_variation_attributes' 	=> $variation_attributes,
				'product_tax' 					=> $product_tax
			);

			$product_array[] = $product_data;
		}

		$cart_contents = array(
			'products' => $product_array,
			'cart_data' => WC()->cart->get_cart_contents()
		);

		return $results_array = array(
			'cart_total' 	=> $cart_total,
			'cart_currency' => $cart_currency,
			'current_time' 	=> $current_time,
			'session_id' 	=> $session_id,
			'cart_contents' => $cart_contents
		);
	}

	/**
	 * Returns currently selected currency
	 * First going through session variable to check if currency has been set here since some currency switcher plugins may store this here
	 * After that look inside cart data for currency information since some plugins store it there
	 * Supporting these plugins by default:
	 * - WooPayments: Integrated WooCommerce Payments (built-in currency switcher)				[wcpay_currency]
	 * - YayCurrency – WooCommerce Multi-Currency Switcher 										[yay_currency_code]
	 *
	 * @since    8.7
	 * @return   string
	 */
	function get_selected_currency(){
		$currency_keys = array(
			'wcpay_currency',
		);

		foreach( $currency_keys as $key ){

			if( is_user_logged_in() ){ //For signed-in customers
				$currency = get_user_meta( get_current_user_id(), $key, true );
			
			}else{
				$currency = WC()->session->get( $key );
			}

			if( $currency ){
				return $currency;
			}
		}

		if( did_action( 'wp_loaded' ) ){
			//In case of YayCurrency, we must look inside cart data for stored currency code
			foreach( WC()->cart->get_cart() as $cart_item ){
				
				if( isset( $cart_item['yay_currency_code'] ) ){
					return $cart_item['yay_currency_code'];
				}
			}
		}

		return get_woocommerce_currency();
	}

	/**
	 * Retrieve product attribute names as comma sepparated string
	 *
	 * @since    8.1
	 * @return   string
	 * @param    array    	$variation_attributes   Product variation attributes
	 * @param    integer    $product_id   			Product number
	 */
	public function get_attribute_names( $variation_attributes, $product_id = '' ){
		$attribute_names = '';
		$labels = array();
		
		if( is_array( $variation_attributes ) ){
			foreach( $variation_attributes as $attribute_name => $attribute_value ){

				$attribute_name = str_replace( 'attribute_', '', $attribute_name );
				$product_terms = wc_get_product_terms( $product_id, $attribute_name );

				if( is_array( $product_terms ) ){
					foreach( $product_terms as $key => $term ){

						if( $term->slug == $attribute_value ){
							$labels[] = $term->name;
							break;
						}
					}
				}
			}
		}

		if( !empty( $labels ) ){
			$attribute_names = ': ' . implode( ', ', $labels );
		}

		return $attribute_names;
	}
	
	/**
	 * Method restores previous Checkout form data for users who are not registered
	 *
	 * @since    2.0
	 * @return   Input field values
	 */
	public function restore_classic_checkout_fields(){

		if( !is_checkout() ) return; //Exit if not on checkout page

		if( has_block( 'woocommerce/checkout' ) ) return; //Stop block checkout detected

		if( !apply_filters( 'cartbounty_restore_classic_checkout', true ) ) return;

		$saved_cart = $this->get_saved_cart();

		if( !$saved_cart ) return;

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$get_consent_field_data = $admin->get_consent_field_data( 'field_name' );
		$other_fields = maybe_unserialize( $saved_cart->other_fields );
		$location_data = $admin->get_cart_location( $saved_cart->location );

		if( empty( $_POST['billing_first_name'] ) ){
			$_POST['billing_first_name'] = esc_html( $saved_cart->name );
		}

		if( empty( $_POST['billing_last_name'] ) ){
			$_POST['billing_last_name'] = esc_html( $saved_cart->surname );
		}

		if( empty( $_POST['billing_email'] ) ){
			$_POST['billing_email'] = esc_html( $saved_cart->email );
		}

		if( empty( $_POST['billing_phone'] ) ){
			$_POST['billing_phone'] = esc_html( $saved_cart->phone );
		}

		if( empty( $_POST[$get_consent_field_data] ) ){
			$_POST[$get_consent_field_data] = $admin->get_customers_consent( $saved_cart );
		}

		if( empty( $_POST['billing_country'] ) ){
			$_POST['billing_country'] = esc_html( $location_data['country'] );
		}

		if( empty( $_POST['billing_city'] ) ){
			$_POST['billing_city'] = esc_html( $location_data['city'] );
		}

		if( empty( $_POST['billing_postcode'] ) ){
			$_POST['billing_postcode'] = esc_html( $location_data['postcode'] );
		}

		if( is_array( $other_fields ) ){
			
			foreach( $other_fields as $key => $value ){
				
				if( empty( $_POST[$key] ) ){

					if( $key == 'ship-to-different-address-checkbox' ){
					
						if( $value ){
							add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
						}

					}elseif( $key == 'createaccount' ){

						if( $value ){
							add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
						}
					}

					$_POST[$key] = esc_html( $value );
				}
			}
		}
	}

	/**
	 * Method restores WooCommerce block checkout input fields
	 *
	 * @since    8.4
	 */
	public function restore_block_checkout_fields() {

		if( !apply_filters( 'cartbounty_restore_block_checkout', true ) ) return;

		if( !function_exists( 'is_admin' ) || !function_exists( 'is_user_logged_in' ) ) return;

		if( is_admin() || is_user_logged_in() ) return;

		if( WC()->session ){
			$session_id = WC()->session->get_customer_id();
			$counter = get_transient( 'cartbounty_session' . $session_id );

			if ( false === $counter ){
				$counter = 0;
			}

			$counter++;

			set_transient( 'cartbounty_session' . $session_id , $counter, 30 ); //30 second expiration

			if( $counter < 5 ){
				$this->update_woocommerce_database_session();
			}
		}
	}

	/**
	 * Method adds customer data to WooCommerce sessions database table
	 *
	 * @since    8.4
	 */
	public function update_woocommerce_database_session( $saved_cart = false ){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$session_table_name = $wpdb->prefix . 'woocommerce_sessions';
		$is_block_checkout = false;
		$use_same_address_for_billing = false;
		$new_session_data = array();

	    if( WC()->session ){

	    	if( !$saved_cart ){
				$public = new CartBounty_Public( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
				$saved_cart = $public->get_saved_cart();
			}

			if( !$saved_cart ) return; //Stop if abandoned cart missing

			if( empty( $saved_cart->email ) && empty( $saved_cart->phone ) ) return; //Stop if contact details are missing

			$necessary_fields = array(
				'name' 			=> '',
				'surname' 		=> '',
				'email' 		=> '',
				'phone' 		=> '',
				'location' 		=> '',
				'other_fields' 	=> '',
			);
			$saved_cart = array_intersect_key( (array)$saved_cart, $necessary_fields ); //Leaving just keys that are needed for session
			$session_id = WC()->session->get_customer_id();
			$session_customer = WC()->session->get('customer');
			$other_fields = maybe_unserialize( $saved_cart['other_fields'] );

			if( !is_array( $other_fields ) ){
				$other_fields = array();
			}

			$saved_cart['location'] = $admin->get_cart_location( $saved_cart['location'] );
			$saved_cart['other_fields'] = $other_fields;
			
			if( empty( $other_fields ) ){
				$is_block_checkout = true;

			}elseif( is_array( $other_fields ) ){
				$is_block_checkout = preg_grep( '/^(shipping|billing)-/', array_keys( $other_fields ) );
			}

			if( empty( $is_block_checkout ) ) return; //Stop if block checkout is not detected

			$session_data = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $session_table_name WHERE session_key = %s", $session_id ) );

			if( !$session_data ) return; //Stop if session data missing

			$session_data = maybe_unserialize( $session_data );

			if( isset( $session_data['customer'] ) ){
				$customer = maybe_unserialize( $session_data['customer'] );

				if( ( isset( $customer['email'] ) && !empty( $customer['email'] ) ) || ( isset( $customer['phone'] ) && !empty( $customer['phone'] ) ) ) return; //Stop in case session already has email or phone number stored				

				//In case of data coming from block checkout - replace billing details with shipping unless both shipping and billing fields are present
				if( isset( $other_fields['converted-shipping-to-billing'] ) ){ //If during abandoned cart saving process we converted shipping fields to billing - must use shipping fields as billing fields
					$use_same_address_for_billing = true;
				}

				//Mapping data
				foreach( $saved_cart as $key => $value ){
					
					if( $key == 'name' ){

						if( $use_same_address_for_billing ){
							$new_session_data['shipping_first_' . $key] = $value;

						}else{
							$new_session_data['first_' . $key] = $value;
						}
					
					}elseif( $key == 'surname' ){

						if( $use_same_address_for_billing ){
							$new_session_data['shipping_last_name'] = $value;

						}else{
							$new_session_data['last_name'] = $value;
						}

					}elseif( $key == 'phone' && $use_same_address_for_billing ){
						$new_session_data['shipping_' . $key] = $value;

					}elseif( $key == 'location' ){
						$location_data = $value;
						
						foreach( $location_data as $key => $value){

							if( $use_same_address_for_billing && in_array( $key, ['country', 'city', 'postcode'] ) ){
								$new_session_data['shipping_' . $key] = $value;

							}else{
								$new_session_data[$key] = $value;
							}
						}

					}elseif( $key == 'other_fields' ){
						$other_fields_data = $value;
						
						foreach( $other_fields_data as $key => $value ){

							if( strpos( $key, 'billing-' ) === 0 && $use_same_address_for_billing ){
								$new_key = str_replace( 'billing-', 'shipping_', $key );
								$new_session_data[$new_key] = $value;

							}elseif( strpos( $key, 'billing-' ) === 0 ){

								if( $key == 'billing-company' || $key == 'billing-phone' || $key == 'billing-address_1' || $key == 'billing-address_2' || 'billing-state' ){
									$new_key = str_replace( 'billing-', '', $key );

								}else{
									$new_key = str_replace( 'billing-', 'billing_', $key );
								}
								
								$new_session_data[$new_key] = $value;
							
							}elseif( strpos( $key, 'shipping-' ) === 0 ){
								$new_key = str_replace( 'shipping-', 'shipping_', $key );
								$new_session_data[$new_key] = $value;
							}
						}

					}else{
						$new_session_data[$key] = $value;
					}
				}

				$customer = array_merge( $customer, $new_session_data );
				$session_data['customer'] = maybe_serialize( $customer );
				$new_session_data = maybe_serialize( $session_data );

				$result = $wpdb->update(
					$session_table_name,
					[ 'session_value' => $new_session_data ],
					[ 'session_key' => $session_id ],
					[ '%s' ],
					[ '%s' ]
				);
			}
		}
	}

	/**
	 * Retrieving current user's saved abandoned cart
	 *
	 * @since    8.1
	 * @return   array
	 */
	function get_saved_cart(){
		global $wpdb;
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cart = $this->read_cart();
		
		$this->update_logged_customer_id(); //If current user had an abandoned cart before - restore session ID (in case of user switching)

		//Retrieve a single, latest edited abandoned cart with current customer ID
		$saved_cart = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM $cart_table
				WHERE session_id = %s
				ORDER BY time DESC",
				$cart['session_id']
			)
		);

		return $saved_cart;
	}


	/**
	 * Method saves and updates total count of captured recoverable abandoned carts
	 *
	 * @since    5.0
	 */
	function increase_recoverable_cart_count(){

		if( !WC()->session ) return; //If session does not exist, exit

		if( WC()->session->get( 'cartbounty_recoverable_count_increased' ) ) return; //Exit function in case we already have run this once

		WC()->session->set( 'cartbounty_recoverable_count_increased', true );

		if( WC()->session->get( 'cartbounty_from_link' ) ) return; //Exit if user returned from link - it means we have increased this before

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['recoverable_carts'] = $misc_settings['recoverable_carts'] + 1;
		update_option( 'cartbounty_misc_settings', $misc_settings );

		if( WC()->session->get( 'cartbounty_anonymous_count_increased' ) ){ //In case we previously increased anonymous cart count, we must now reduce it as it has been turned to recoverable
			$this->decrease_anonymous_cart_count( 1 );
		}
	}

	/**
	 * Method saves and updates total count of captured anonymous carts
	 *
	 * @since    5.0
	 */
	function increase_anonymous_cart_count(){

		if( !WC()->session ) return; //If session does not exist, exit

		if( WC()->session->get( 'cartbounty_anonymous_count_increased' ) ) return; //Exit in case we already have run this once

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['anonymous_carts'] = $misc_settings['anonymous_carts'] + 1;
		update_option( 'cartbounty_misc_settings', $misc_settings );

		WC()->session->set( 'cartbounty_anonymous_count_increased', 1 );
	}

	/**
	 * Method saves and updates total count of recovered carts
	 *
	 * @since    7.0
	 */
	function increase_recovered_cart_count(){

		if( !WC()->session ) return; //If session does not exist, exit function

		if( WC()->session->get( 'cartbounty_recovered_count_increased' ) ) return; //Exit fnction in case we already have run this once

		WC()->session->set( 'cartbounty_recovered_count_increased', 1 );

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['recovered_carts'] = $misc_settings['recovered_carts'] + 1;
		update_option( 'cartbounty_misc_settings', $misc_settings );
	}

	/**
	 * Method decreases the total count of captured abandoned carts
	 *
	 * @since    5.0
	 * @param    integer 	$count    			Cart count
	 */
	function decrease_recoverable_cart_count( $count ){
		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['recoverable_carts'] = $misc_settings['recoverable_carts'] - $count;
		update_option( 'cartbounty_misc_settings', $misc_settings ); //Decreasing the count by one abandoned cart

		if( WC()->session ) {
			WC()->session->__unset( 'cartbounty_recoverable_count_increased' );
			$this->increase_anonymous_cart_count(); //Since this is no longer a recoverable cart - must make sure we update anonymous cart count
		}
	}

	/**
	 * Method decreases the total count of captured anonymous carts
	 *
	 * @since    5.0
	 * @param    $count    Cart number - integer 
	 */
	function decrease_anonymous_cart_count( $count ){
		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$misc_settings = $admin->get_settings( 'misc_settings' );
		$misc_settings['anonymous_carts'] = $misc_settings['anonymous_carts'] - $count;

		update_option( 'cartbounty_misc_settings', $misc_settings );

		if( WC()->session ){
			WC()->session->__unset( 'cartbounty_anonymous_count_increased' );
		}
	}

	/**
	 * Outputing email form if anonymous user who is not logged in wants to leave with a full shopping cart
	 *
	 * @since    3.0
	 */
	function display_exit_intent_form(){
		if( !class_exists( 'WooCommerce' ) ) return; //If WooCommerce does not exist
		
		if( !$this->tool_enabled( 'exit_intent' ) || !WC()->cart ) return; //If Exit Intent disabled or WooCommerce cart does not exist
		
		$current_user_is_admin = current_user_can( 'manage_options' );
		$output = $this->build_exit_intent_output($current_user_is_admin); //Creating the Exit Intent output
		echo $output;
		echo "<script>localStorage.setItem( 'cartbounty_product_count', " . $this->get_cart_product_count() . " )</script>";
	}

	/**
	 * Building the Exit Intent output
	 *
	 * @since    3.0
	 * @return   string
	 * @param    $current_user_is_admin    If the current user has Admin rights or not - boolean
	 */
	function build_exit_intent_output( $current_user_is_admin ){
		global $wpdb;
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$cart = $this->read_cart();
		$heading = $admin->get_tools_defaults('heading', 'exit_intent');
		$content = $admin->get_tools_defaults('content', 'exit_intent');
		$ei_settings = $admin->get_settings( 'exit_intent' );
		$main_color = $ei_settings['main_color'];
		$inverse_color = $ei_settings['inverse_color'];
		$where_sentence = $admin->get_where_sentence('recoverable');

		$tools_consent = $admin->get_tools_consent();
		$consent_settings = $admin->get_consent_settings();
		$email_consent_enabled = $consent_settings['email'];
		$consent_enabled = false;

		if( trim( $ei_settings['heading'] ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$heading = $admin->sanitize_field( $ei_settings['heading'] );
		}

		if( trim( $ei_settings['content'] ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$content = $admin->sanitize_field( $ei_settings['content'] );
		}

		if(!$main_color){
			$main_color = '#e3e3e3';
		}
		if(!$inverse_color){
			$inverse_color = $this->invert_color($main_color);
		}

		//Retrieve a single row with current customer ID
		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT *
			FROM $cart_table
			WHERE session_id = %s
			$where_sentence",
			$cart['session_id'])
		);

		if($row && !$current_user_is_admin){ //Exit if cart already saved and the current user is not admin
			return;
		}
		
		//Prepare Exit Intent image
		$image_id = $ei_settings['image'];
		$image_url = $this->get_plugin_url() . '/public/assets/abandoned-shopping-cart.gif';
		if($image_id){
			$image = wp_get_attachment_image_src( $image_id, 'full' );
			if(is_array($image)){
				$image_url = $image[0];
			}
		}

		if( ( $email_consent_enabled ) ){
			$consent_enabled = true;
		}

		$args = array(
			'image_url' => $image_url,
			'heading' => $heading,
			'content' => $content,
			'main_color' => $main_color,
			'inverse_color' => $inverse_color,
			'consent_enabled' => $consent_enabled,
			'tools_consent' => $tools_consent,
			'title' => esc_html__( 'You were not leaving your cart just like that, right?', 'woo-save-abandoned-carts' ),
			'alt' => esc_html__( 'You were not leaving your cart just like that, right?', 'woo-save-abandoned-carts' ),
		);

		return $this->get_template( 'cartbounty-exit-intent.php', $args );
	}

	/**
	 * Checking if we Exit Intent is enabled
	 *
	 * @since    3.0
	 * @return   boolean
	 * @param    string    $tool    Tool that is being checked
	 */
	function tool_enabled( $tool ){
		$admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		switch ( $tool ) {
			case 'exit_intent':
				$ei_settings = $admin->get_settings( 'exit_intent' );
				$tool_enabled = $ei_settings['status'];
				$test_mode_on = $ei_settings['test_mode'];
				break;
		}

		$current_user_is_admin = current_user_can( 'manage_options' );

		if($test_mode_on && $current_user_is_admin){
			//Outputing tool for Testing purposes to Administrators
			return true;
		}elseif($tool_enabled && !$test_mode_on && !is_user_logged_in()){
			//Outputing tool for all users who are not logged in
			return true;
		}else{
			//Tool is not enabled
			return false;
		}
	}

	/**
	 * Getting inverse color from the given one
	 *
	 * @since    3.0
	 * @return   string
	 * @param    $color    Color code - string
	 */
	function invert_color( $color ){
	    $color = str_replace('#', '', $color);
	    if (strlen($color) != 6){ return '000000'; }
	    $rgb = '';
	    for ($x=0;$x<3;$x++){
	        $c = 255 - hexdec(substr($color,(2*$x),2));
	        $c = ($c < 0) ? 0 : dechex($c);
	        $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
	    }
	    return '#'.$rgb;
	}

	/**
	 * Get the plugin url.
	 *
	 * @since    3.0
	 * @return   string
	 */
	public function get_plugin_url() {
		return plugins_url( '/', dirname(__FILE__) );
	}

	/**
	 * Locating template file.
	 * Method returns the path to the template
	 *
	 * Search Order:
	 * 1. /themes/theme/templates/emails/$template_name
	 * 2. /themes/theme/templates/$template_name
	 * 3. /themes/theme/$template_name
	 * 4. /plugins/woo-save-abandoned-carts/templates/$template_name
	 *
	 * @since    3.0
	 * @return   string
	 * @param 	 string    $template_name - template to load.
	 * @param 	 string    $string $template_path - path to templates.
	 * @param    string    $default_path - default path to template files.
	 */
	function get_template_path( $template_name, $template_path = '', $default_path = '' ){
		$search_array = array();

		// Set variable to search folder of theme.
		if( !$template_path ){
			$template_path = 'templates/';
		}

		//Add paths to look for template files
		$search_array[] = $template_path . 'emails/' . $template_name; 
		$search_array[] = $template_path . $template_name;
		$search_array[] = $template_name;

		// Search template file in theme folder.
		$template = locate_template( $search_array );

		// Set default plugin templates path.
		if( !$default_path ){
			$default_path = plugin_dir_path( __FILE__ ) . '../templates/'; // Path to the template folder
		}

		// Get plugins template file.
		if( !$template ){
			$template = $default_path . $template_name;
		}

		return apply_filters( 'cartbounty_get_template_path', $template, $template_name, $template_path, $default_path );
	}

	/**
	 * Get the template.
	 *
	 * @since    3.0
	 *
	 * @param    string    $template_name - template to load.
	 * @param    array     $args - args passed for the template file.
	 * @param    string    $string $template_path - path to templates.
	 * @param    string	   $default_path - default path to template files.
	 */
	function get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {
		if ( is_array( $args ) && isset( $args ) ){
			extract( $args );
		}
		$template_file = $this->get_template_path($template_name, $tempate_path, $default_path);
		
		if ( !file_exists( $template_file ) ){ //Handling error output in case template file does not exist
			_doing_it_wrong( __FUNCTION__, sprintf( 'Template file %s does not exist.', esc_html( $template_file ) ), '6.1' );

			return;
		}
		include $template_file;
	}

	/**
	 * Check what is the source of the saved abandoned cart and return it
	 * Source types NULL = checkout, 1 = Exit Intent, 3 = Custom email or phone field
	 *
	 * @since    7.3
	 * @return 	 Array
	 */
	function get_cart_source(){
		$source = '';

		if(WC()->session){ //If session exists

			if(!WC()->session->get('cartbounty_saved_via')){ //If we do not know how the cart was initially saved
				
				if(isset($_POST['source'])){ //Check if data coming from a source or not
					switch ( $_POST['source'] ) {
						case 'cartbounty_exit_intent':
							$source = 1;
							break;

						case 'cartbounty_custom_field':
							$source = 3;
							break;
					}

					if(!empty($source)){
						WC()->session->set('cartbounty_saved_via', $source);
					}
				}
				
			}else{ //In case we already know how the cart was initially saved - retrieve the information from session
				$source = WC()->session->get('cartbounty_saved_via');
			}
		}
		return array(
			'source' 	=> $source
		);
	}

	/**
	 * Retrieve abandoned cart data value from database of a given key (e.g. name, surname, phone, email..)
	 *
	 * @since    8.4
	 * @return   string
	 * @param    string 	$value    		Key value that must be returned 
	 */
	function get_saved_cart_data( $value ){
		global $wpdb;
		$cart = $this->read_cart();
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT $value
				FROM $cart_table
				WHERE session_id = %s",
				$cart['session_id']
			)
		);

		if( !$result ){
			$result = false;
		}

		return $result;
	}

	/**
	 * Get product count isnide shopping cart
	 *
	 * @since    7.1.3
	 * @return   integer
	 */
	function get_cart_product_count(){
		$count = 0;

		if( WC()->cart ){
			$count = WC()->cart->get_cart_contents_count();
		}

		return $count;
	}

	/**
	 * Get a list of WooCommerce checkout fields that should be saved
	 *
	 * @since    8.4
	 * @return   string
	 */
	function get_checkout_fields(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$consent_field = $admin->get_consent_field_data( 'field_name' );
		$selectors = array(
			"email",
			"billing_email",
			"billing-country",
			"billing_country",
			"billing-first_name",
			"billing_first_name",
			"billing-last_name",
			"billing_last_name",
			"billing-company",
			"billing_company",
			"billing-address_1",
			"billing_address_1",
			"billing-address_2",
			"billing_address_2",
			"billing-city",
			"billing_city",
			"billing-state",
			"billing_state",
			"billing-postcode",
			"billing_postcode",
			"billing-phone",
			"billing_phone",
			"shipping-country",
			"shipping_country",
			"shipping-first_name",
			"shipping_first_name",
			"shipping-last_name",
			"shipping_last_name",
			"shipping-company",
			"shipping_company",
			"shipping-address_1",
			"shipping_address_1",
			"shipping-address_2",
			"shipping_address_2",
			"shipping-city",
			"shipping_city",
			"shipping-state",
			"shipping_state",
			"shipping-postcode",
			"shipping_postcode",
			"shipping-phone",
			"checkbox-control-1",
			"ship-to-different-address-checkbox",
			"checkbox-control-0",
			"createaccount",
			"checkbox-control-2",
			"order-notes textarea",
			"order_comments",
		);

		if( $consent_field ){
			$selectors[] = $consent_field;
		}

		return '#' . implode(', #', apply_filters( 'cartbounty_checkout_fields', $selectors ) );
	}

	/**
	 * Get custom email field selectors (required for adding email to abandoned cart data input field data from 3rd party plugins)
	 * Ability to use a filter to edit this list
	 *
	 * Supporting these plugins by default:
	 * - CartBounty custom email field option									[cartbounty]
	 * - WooCommerce MyAccount login form										[woocommerce]
	 * - WPForms Lite by by WPForms												[wpforms]
	 * - Popup Builder by Looking Forward Software Incorporated					[sgpb]
	 * - Popup Maker by Popup Maker												[pum]
	 * - Ninja Forms by Saturday Drive											[ninja]
	 * - Contact Form 7 by Takayuki Miyoshi										[wpcf7]
	 * - Fluent Forms by Contact Form - WPManageNinja LLC						[fluentform]
	 * - Newsletter, SMTP, Email marketing and Subscribe forms by Brevo			[sib]
	 * - MailPoet by MailPoet													[mailpoet]
	 * - Newsletter by Stefano Lissa & The Newsletter Team						[tnp]
	 * - OptinMonster by OptinMonster Popup Builder Team						[optinmonster]
	 * - OptiMonk: Popups, Personalization & A/B Testing by OptiMonk			[optimonk]
	 * - Poptin by Poptin														[poptin]
	 * - Gravity Forms by Gravity Forms											[gform]
	 * - Popup Anything by WP OnlineSupport, Essential Plugin					[paoc]
	 * - Popup Box by Popup Box Team											[ays]
	 * - Hustle by WPMU DEV														[hustle]
	 * - Popups for Divi by divimode.com										[popdivi]
	 * - Brave Conversion Engine by Brave										[brave]
	 * - Popup by Supsystic by supsystic.com									[ppspopup]
	 * - Login/Signup Popup by XootiX											[xoologin]
	 *
	 * @since    7.3
	 * @return   string
	 */
	function get_custom_email_selectors(){
		$selectors = apply_filters( 'cartbounty_custom_email_selectors',
			array(
				'cartbounty' 	=> '.cartbounty-custom-email-field',
				'woocommerce' 	=> '.login #username',
				'wpforms' 		=> '.wpforms-container input[type="email"]',
				'sgpb' 			=> '.sgpb-form input[type="email"]',
				'pum' 			=> '.pum-container input[type="email"]',
				'ninja'			=> '.nf-form-cont input[type="email"]',
				'wpcf7'			=> '.wpcf7 input[type="email"]',
				'fluentform'	=> '.fluentform input[type="email"]',
				'sib'			=> '.sib_signup_form input[type="email"]',
				'mailpoet'		=> '.mailpoet_form input[type="email"]',
				'tnp'			=> '.tnp input[type="email"]',
				'optinmonster'	=> '.om-element input[type="email"]',
				'optimonk'		=> '.om-holder input[type="email"]',
				'poptin'		=> '.poptin-popup input[type="email"]',
				'gform'			=> '.gform_wrapper input[type="email"]',
				'paoc'			=> '.paoc-popup input[type="email"]',
				'ays'			=> '.ays-pb-form input[type="email"]',
				'hustle'		=> '.hustle-form input[type="email"]',
				'popdivi'		=> '.et_pb_section input[type="email"]',
				'brave'			=> '.brave_form_form input[type="email"]',
				'ppspopup'		=> '.ppsPopupShell input[type="email"]',
				'xoologin'		=> '.xoo-el-container input[type="email"], .xoo-el-container input[name="xoo-el-username"]',
			)
		);

		return implode( ', ', $selectors );
	}

	/**
	 * Get custom phone field selectors (required for adding phone to abandoned cart data input field data from 3rd party plugins)
	 * Ability to use a filter to edit this list
	 *
	 * Supporting these plugins by default:
	 * - CartBounty custom email field option									[cartbounty]
	 * - WPForms Lite by by WPForms												[wpforms]
	 * - Popup Builder by Looking Forward Software Incorporated					[sgpb]
	 * - Ninja Forms by Saturday Drive											[ninja]
	 * - Contact Form 7 by Takayuki Miyoshi										[wpcf7]
	 * - Fluent Forms by Contact Form - WPManageNinja LLC						[fluentform]
	 * - OptinMonster by OptinMonster Popup Builder Team						[optinmonster]
	 * - OptiMonk: Popups, Personalization & A/B Testing by OptiMonk			[optimonk]
	 * - Poptin by Poptin														[poptin]
	 * - Gravity Forms by Gravity Forms											[gform]
	 * - Popup Anything by WP OnlineSupport, Essential Plugin					[paoc]
	 * - Popup Box by Popup Box Team											[ays]
	 * - Hustle by WPMU DEV														[hustle]
	 * - Popups for Divi by divimode.com										[popdivi]
	 * - Login/Signup Popup by XootiX											[xoologin]
	 *
	 * @since    8.3
	 * @return   string
	 */
	function get_custom_phone_selectors(){
		$selectors = apply_filters( 'cartbounty_custom_phone_selectors',
			array(
				'cartbounty' 	=> '.cartbounty-custom-phone-field',
				'wpforms' 		=> '.wpforms-container input[type="tel"]',
				'sgpb' 			=> '.sgpb-form input[type="tel"]',
				'ninja'			=> '.nf-form-cont input[type="tel"]',
				'wpcf7'			=> '.wpcf7 input[type="tel"]',
				'fluentform'	=> '.fluentform input[type="tel"]',
				'optinmonster'	=> '.om-element input[type="tel"]',
				'optimonk'		=> '.om-holder input[type="tel"]',
				'poptin'		=> '.poptin-popup input[type="tel"]',
				'gform'			=> '.gform_wrapper input[type="tel"]',
				'paoc'			=> '.paoc-popup input[type="tel"]',
				'ays'			=> '.ays-pb-form input[type="tel"]',
				'hustle'		=> '.hustle-form input[name="phone"]',
				'popdivi'		=> '.et_pb_section input[type="tel"]',
				'xoologin'		=> '.xoo-el-container input[type="tel"]',
			)
		);

		return implode( ', ', $selectors );
	}

	/**
	 * Get custom "Add to cart" button selectors
	 * Ability to use a filter to edit this list
	 *
	 * Supporting these plugins by default:
	 * - CartBounty custom add to cart button class								[cartbounty]
	 * - WooCommerce 															[woocommerce]
	 * - YITH WooCommerce Frequently Bought Together by YITH					[yith-wfbt]
	 *
	 * @since    10.7
	 * @return   string
	 */
	function get_custom_add_to_cart_button_selectors(){
		$selectors = apply_filters( 'cartbounty_custom_add_to_cart_button_selectors',
			array(
				'cartbounty' 	=> '.cartbounty-add-to-cart',
				'woocommerce' 	=> '.add_to_cart_button, .ajax_add_to_cart, .single_add_to_cart_button',
				'yith-wfbt' 	=> '.yith-wfbt-submit-button',
			)
		);

		return implode( ', ', $selectors );
	}
}