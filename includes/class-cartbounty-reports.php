<?php
/**
* The Reports class.
*
* Used to define functions related with CartBounty reports
*
*
* @since      8.0
* @package    CartBounty - Save and recover abandoned carts for WooCommerce
* @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
* @author     Streamline.lv
*/
class CartBounty_Reports{

	/**
     * Returning reporting defaults
     *
     * @since    8.0
     * @return   array or string
     * @param    string     $value    		  	  Value to return
     */
	public function get_defaults( $value = false ){
		$currency = '';

		if( class_exists( 'WooCommerce' ) ){
			$currency = get_woocommerce_currency();
		}

		$defaults = array(
			'start_format'				=> 'Y-m-d 00:00:00',
			'end_format'				=> 'Y-m-d 23:59:59',
			'default_period'			=> 'month-to-date',
			'default_comparison'		=> 'previous-period',
			'min_start_date'			=> '2018-01-01',
			'max_end_date'				=> date( 'Y-m-d', strtotime( 'Last day of December' ) ),
			'currency'					=> $currency,
			'reverse_delta_state'		=> array(
				'abandonment-rate'
			),
			'default_chart_type'		=> 'bar',
			'default_top_product_count'	=> 5,
			'available_list_values'		=> 5,
			'empty_chart_data'			=> __( 'No data for selected date range', 'woo-save-abandoned-carts' ),
			'default_map'				=> 'abandoned-carts',
			'country_count'				=> 5,
		);

		if( $value ){ //If a single value should be returned

			if( isset( $defaults[$value] ) ){ //Checking if value exists
				$defaults = $defaults[$value];
			}
		}

		return $defaults;
	}

	/**
	 * Retrieve available chart types
	 *
	 * @since    8.0
	 * @return   array
	 */
	public function get_available_chart_types(){
		$chart_types = array(
			'bar' => array(
				'icon' => '<svg viewBox="0 0 73.48 70"><path d="M19.86,49.28V66.53A3.48,3.48,0,0,1,16.39,70H3.47A3.48,3.48,0,0,1,0,66.53V49.28A3.5,3.5,0,0,1,3.48,45.8H16.39A3.49,3.49,0,0,1,19.86,49.28Z"/><path d="M46.67,3.48V66.53A3.48,3.48,0,0,1,43.2,70H30.28a3.48,3.48,0,0,1-3.47-3.47v-63A3.49,3.49,0,0,1,30.29,0h12.9A3.48,3.48,0,0,1,46.67,3.48Z"/><path d="M73.48,22.34V66.53A3.49,3.49,0,0,1,70,70H57.09a3.49,3.49,0,0,1-3.48-3.47V22.35a3.49,3.49,0,0,1,3.48-3.48H70A3.49,3.49,0,0,1,73.48,22.34Z"/></svg>'
			),
			'line' => array(
				'icon' => '<svg viewBox="0 0 87.36 70"><circle cx="8.89" cy="61.11" r="8.89"/><circle cx="24.91" cy="36.31" r="8.89"/><circle cx="78.47" cy="8.89" r="8.89"/><circle cx="58.99" cy="54.68" r="8.89"/><rect x="13.13" y="33.33" width="7.11" height="31.09" transform="translate(29.42 -1.18) rotate(33.12)"/><rect x="65.68" y="11.39" width="7.11" height="39.75" transform="translate(17.01 -23.9) rotate(22.27)"/><rect x="39.5" y="26.58" width="7.11" height="39.75" transform="translate(104.34 30.45) rotate(118.19)"/></svg>'
			)
		);

		return $chart_types;
	}

	/**
	 * Retrieve default reports that should be enabled
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string     $type    		  	  Type of data to return
	 */
	public function get_default_reports( $type = 'quick_stats' ){

		$reports = array(
			'abandonment-rate',
			'abandoned-carts',
			'anonymous-carts',
			'recoverable-carts',
			'recoverable-revenue',
			'recovered-carts'
		);

		if( $type == 'charts' ){
			$reports = array(
				'abandoned-carts'
			);
		}

		return $reports;
	}

	/**
	 * Return all available reports
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string     $type    		  	  Type of data to return
	 * @param    string     $item    		  	  Item to return
	 */
	public function get_available_reports( $type = 'quick_stats', $item = false ){
		$items = array(
			'abandonment-rate' 		=> esc_html__( 'Cart abandonment rate', 'woo-save-abandoned-carts' ),
			'abandoned-carts' 		=> esc_html__( 'Abandoned carts', 'woo-save-abandoned-carts' ),
			'anonymous-carts' 		=> esc_html__( 'Anonymous carts', 'woo-save-abandoned-carts' ),
			'recoverable-carts' 	=> esc_html__( 'Recoverable carts', 'woo-save-abandoned-carts' ),
			'recoverable-revenue' 	=> esc_html__( 'Recoverable revenue', 'woo-save-abandoned-carts' ),
			'recovered-carts' 		=> esc_html__( 'Recovered carts', 'woo-save-abandoned-carts' ),
		);

		if( $type == 'charts' ){
			$items = array(
				'abandoned-carts' 		=> esc_html__( 'Abandoned carts', 'woo-save-abandoned-carts' )
			);
		}

		if( $type == 'map' ){
			$items = array(
				'abandoned-carts' 		=> esc_html__( 'Abandoned carts', 'woo-save-abandoned-carts' ),
				'abandonment-rate' 		=> esc_html__( 'Cart abandonment rate', 'woo-save-abandoned-carts' ),
				'anonymous-carts' 		=> esc_html__( 'Anonymous carts', 'woo-save-abandoned-carts' ),
				'recoverable-carts' 	=> esc_html__( 'Recoverable carts', 'woo-save-abandoned-carts' ),
				'recovered-carts' 		=> esc_html__( 'Recovered carts', 'woo-save-abandoned-carts' ),
				'average-cart-value' 	=> esc_html__( 'Average cart value', 'woo-save-abandoned-carts' )
			);
		}

		if( $item ){ //If a single value should be returned

			if( isset( $items[$item] ) ){ //Checking if value exists
				$items = $items[$item];
			}
		}

		return $items;
	}

	/**
	* Retrieve report settings
	*
	* @since    8.1
	* @return   array
	* @param    string     $value                Value to return
	*/
	public function get_settings( $value = false ){
		$saved_options = get_option( 'cartbounty_report_settings' );
		$default_values = $this->get_defaults();
		$defaults = array(
			'quick_stats' 		=> $this->get_default_reports(),
			'charts' 			=> $this->get_default_reports( 'charts' ),
			'chart_type' 		=> $default_values['default_chart_type'],
			'top_product_count' => $default_values['default_top_product_count'],
			'map' 				=> $default_values['default_map'],
			'country_count' 	=> $default_values['country_count'],
		);

		if( is_array( $saved_options ) ){
			$settings = array_merge( $defaults, $saved_options ); //Merging default settings with saved options
			
		}else{
			$settings = $defaults;
		}

		if( $value ){ //If a single value should be returned
			
			if( isset( $settings[$value] ) ){ //Checking if value exists
				$settings = $settings[$value];
			}
		}

		return $settings;
	}

	/**
	 * Retrieve active reports
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string     $item    		  	  Item to return
	 */
	function get_active_reports( $item = false ){
		$settings = $this->get_settings();
		$active_reports = array(
			'quick_stats' 	=> $settings['quick_stats'],
			'charts' 		=> $settings['charts']
		);

		if( $item ){ //If a single value should be returned

			if( isset( $active_reports[$item] ) ){ //Checking if value exists
				$active_reports = $active_reports[$item];
			}
		}

		return $active_reports;
	}

	/**
	 * Update active report list
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string     $item    		  	  New active item
	 * @param    string     $status    		  	  Weather the item must be enabled or disabled e.g. 'include'/ 'remove'
	 * @param    string     $type    		  	  Type of data to return
	 */
	private function update_active_reports( $item, $status, $type = 'quick_stats' ){
		$settings = $this->get_settings();
		$active_reports = $settings[$type];

		if( !is_array( $active_reports ) ){
			$active_reports = array();
		}

		if( $status == 'remove' ){ //If must remove existing item from the array
			$active_reports = array_diff( $active_reports, array( $item ) );

		}else{ //Add a new item to active item array and order array according to available repor order
			$active_reports[] = $item;
			$ordered_active_reports = array();
			$available_reports = $this->get_available_reports( $type );

			foreach( $available_reports as $key => $value ){ //Order array items in the order of available reports
				
				if( in_array( $key, $active_reports ) ){
					$ordered_active_reports[] = $key;
				}
			}

			$active_reports = $ordered_active_reports;
		}

		$settings[$type] = $active_reports;
		return update_option( 'cartbounty_report_settings', $settings );

	}

	/**
	 * Return report name
	 *
	 * @since    8.2
	 * @return   string
	 */
	public function get_selected_map_report_name(){
		$report_name = '';

		if( empty( $selected_report ) ){
			$selected_report = $this->get_selected_map();
		}

		$report_name = $this->get_available_reports( 'map', $selected_report );
		return $report_name;
	}

		/**
	 * Retrieve selected map report to display
	 *
	 * @since    8.2
	 * @return   string
	 */
	private function get_selected_map(){
		$report = $this->get_settings( 'map' );
		return $report;
	}

	/**
	 * Retrieve selected country count to display
	 *
	 * @since    8.2
	 * @return   string
	 */
	private function get_selected_country_count(){
		$count = $this->get_settings( 'country_count' );
		return $count;
	}

	/**
	 * Retrieve selected top product count to display
	 *
	 * @since    8.0
	 * @return   string
	 */
	private function get_selected_top_product_count(){
		$count = $this->get_settings( 'top_product_count' );
		return $count;
	}

	/**
	 * Retrieve selected chart type
	 *
	 * @since    8.0
	 * @return   string
	 */
	public function get_selected_chart_type(){
		$type = $this->get_settings( 'chart_type' );
		return $type;
	}

	/**
	 * Get selected report currency
	 *
	 * @since    8.0
	 * @return   string
	 */
	public function get_selected_currency(){
		$selected_currency = $this->get_defaults( 'currency' );
		return $selected_currency;
	}

	/**
	 * Get selected report currency symbol
	 *
	 * @since    8.0
	 * @return   string
	 * @param    string     $currency    		  	  Currency value e.g. EUR, USD
	 */
	public function get_selected_currency_symbol( $currency = '' ){
		$symbol = '';

		if( empty( $currency ) ){
			$currency = $this->get_selected_currency();
		}

		if( !empty( $currency ) ){

			if( class_exists( 'WooCommerce' ) ){

				$html_entity = get_woocommerce_currency_symbol( $currency );
				$symbol = html_entity_decode( $html_entity );
			}
		}

		return $symbol;
	}

	/**
	 * Checks if store has shopping carts in multiple currencies
	 *
	 * @since    8.0
	 * @return   boolean
	 */
	public function is_multiple_currencies(){
		$multiple = false;
		$currencies = $this->get_cart_currencies();
		
		if( count( $currencies ) > 1 ){
			$multiple = true;
		}

		return $multiple;
	}

	/**
	 * Retrieve available reports that can be enabled or disabled
	 *
	 * @since    8.0
	 * @return   HTML
	 * @param    string     $type    		  	  Type of data to return
	 */
	public function get_available_report_list_items( $type = 'quick_stats' ){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$available_reports = $this->get_available_reports( $type );
		$active_reports = $this->get_active_reports( $type );
		$unavailable_reports = array();
		
		if( !is_array( $active_reports ) ){
			$active_reports = array();
		}

		if( $type == 'quick_stats' ){
			$unavailable_reports = array(
				'recovery-rate' 		=> esc_html__( 'Cart recovery rate', 'woo-save-abandoned-carts' ),
				'recovered-revenue' 	=> esc_html__( 'Recovered revenue', 'woo-save-abandoned-carts' ),
				'average-cart-value' 	=> esc_html__( 'Average cart value', 'woo-save-abandoned-carts' )
			);

		}elseif( $type == 'charts' ){
			$unavailable_reports = array(
				'anonymous-carts' 		=> esc_html__( 'Anonymous carts', 'woo-save-abandoned-carts' ),
				'recoverable-carts' 	=> esc_html__( 'Recoverable carts', 'woo-save-abandoned-carts' ),
				'recoverable-revenue' 	=> esc_html__( 'Recoverable revenue', 'woo-save-abandoned-carts' ),
				'recovered-carts' 		=> esc_html__( 'Recovered carts', 'woo-save-abandoned-carts' ),
				'recovered-revenue' 	=> esc_html__( 'Recovered revenue', 'woo-save-abandoned-carts' ),
			);
		}

		ob_start(); ?>
		<ul id="cartbounty-available-reports-<?php echo $type; ?>">
			<?php foreach( $available_reports as $key => $report ): ?>
				<?php ( in_array( $key, $active_reports ) ) ? $item_enabled = true : $item_enabled = false; //Check which items are active?>
				<li class="cartbounty-available-report-<?php echo $type; ?>">
					<label for="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>" class="cartbounty-switch">
						<input id="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>" class="cartbounty-checkbox" type="checkbox" name="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>" value="1" <?php echo $admin->disable_field(); ?> <?php echo checked( 1, $item_enabled, false ); ?> autocomplete="off" data-action="update_<?php echo $type; ?>" data-nonce="<?php echo wp_create_nonce( 'update_' . $type ); ?>" data-name="<?php echo esc_attr( $key ); ?>" />
						<span class="cartbounty-slider round"></span>
					</label>
					<label for="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>"><?php echo $report; ?></label>
				</li>
			<?php endforeach; ?>
			<?php foreach( $unavailable_reports as $key => $report ): ?>
				<li class="cartbounty-unavailable-report-<?php echo $type; ?>">
					<label for="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>" class="cartbounty-switch cartbounty-unavailable">
						<input id="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>" class="cartbounty-checkbox" type="checkbox" disabled />
						<span class="cartbounty-slider round"></span>
					</label>
					<label for="cartbounty-report-settings-<?php echo $type; ?>-<?php echo $key; ?>" class="cartbounty-unavailable"><?php echo $report; ?></label>
				</li>
			<?php endforeach;?>
		</ul>
		<p class='cartbounty-additional-information'>
			<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $admin->display_unavailable_notice( 'reports_' . $type ); ?></i>
		</p>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Checking if data request is coming from period submit form via PHP (in case Javascript disabled)
	 *
	 * @since    8.0
	 * @return   boolean
	 */
	private function is_php_period_submit(){
		$valid = false;

		if( isset( $_POST['cartbounty_apply_period'] ) && $_POST['cartbounty_apply_period'] == 'cartbounty_apply_period_c' ){
			if ( isset( $_POST['cartbounty_report_period'] ) && wp_verify_nonce( $_POST['cartbounty_report_period'], 'cartbounty_report_period' ) ){ //If PHP request is valid and passes nonce check
				$valid = true;
			}
		}

		return $valid;
	}

	/**
	 * Checking if request is a valid Ajax request
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string     $request_type    		  	Type of the request e.g. "period"
	 */
	private function is_valid_ajax_request( $request_type ){
		$valid = false;

		switch( $request_type ){
			case 'period_submit':
				$action = 'apply_report_period';
				break;

			case 'quick_stats_update':
				$action = 'update_quick_stats';
				break;

			case 'chart_update':
				$action = 'update_charts';
				break;

			case 'update_chart_type':
				$action = 'update_chart_type';
				break;

		}

		if( isset( $_POST['action'] ) && $_POST['action'] == $action ){
			if( check_ajax_referer( $_POST['action'], 'nonce', false ) ){ //If ajax request is valid and passes nonce check
				$valid = true;
			}
		}

		return $valid;
	}

	/**
	 * Handling report updates via ajax
	 *
	 * @since    8.0
	 * @return   JSON
	 */
	public function handle_report_updates(){
		
		if( $this->is_valid_ajax_request( 'quick_stats_update' ) || $this->is_valid_ajax_request( 'chart_update' ) ){ //If this is a valid ajax request coming from report settings update

			if( !empty( $_POST['name'] ) ){
				$item_name = sanitize_text_field( $_POST['name'] );
				$action = sanitize_text_field( $_POST['action'] );
				$status = 'include';

				if( $action == 'update_charts' ){ //If updating active charts
					$type = 'charts';

				}else{ //If updating active quick stats
					$type = 'quick_stats';
				}

				if( !empty( $_POST['status'] ) ){

					if( $_POST['status'] == 'false' ){
						$status = 'remove';
					}
				}
				
				if( $this->is_valid_report( $item_name, $type ) ){

					if( $this->update_active_reports( $item_name, $status, $type ) ){ //If option has been updated
						
						if( $action == 'update_charts' ){ //If updating active charts
							$report_data = $this->display_charts();
							$active_charts = $this->get_active_reports( 'charts' );

						}else{ //If updating active quick stats
							$report_data = $this->display_quick_stats();
							$active_charts = '';
						}

						$response = array(
							'report_data' 		=> $report_data,
							'active_charts' 	=> $active_charts,
							'chart_type' 		=> $this->get_selected_chart_type(),
						);
						wp_send_json_success( $response );
					}
				}
			}
		}
	}

	/**
	 * Handling calendar form ajax submission or currency change
	 *
	 * @since    8.0
	 * @return   JSON
	 */
	public function handle_ajax_submit(){
		
		if( $this->is_valid_ajax_request( 'period_submit' ) ){ //If this is a valid ajax request coming from preiod submit form
			$response = array(
				'report_data'		=> $this->display_quick_stats(),
				'chart_data'		=> $this->display_charts(),
				'active_charts' 	=> $this->get_active_reports( 'charts' ),
				'url' 				=> $this->get_period_url(),
				'period_dropdown' 	=> $this->display_period_dropdown(),
				'top_products' 		=> $this->display_top_products(),
				'map_data' 			=> $this->display_carts_by_country(),
				'chart_type' 		=> $this->get_selected_chart_type(),
			);
			wp_send_json_success( $response );
		}
	}

	/**
	 * Handling chart type updates via ajax
	 *
	 * @since    8.0
	 * @return   JSON
	 */
	public function handle_chart_type_updates(){
		
		if( $this->is_valid_ajax_request( 'update_chart_type' ) ){ //If this is a valid ajax request

			if( !empty( $_POST['value'] ) ){
				$value = sanitize_text_field( $_POST['value'] );
				$settings = $this->get_settings();
				$settings['chart_type'] = $value;
				$update = update_option( 'cartbounty_report_settings', $settings );


				if( $update ){ //If option has been updated
					$response = array(
						'active_charts'		=> $this->get_active_reports( 'charts' ),
						'chart_type' 		=> $value,
					);
					wp_send_json_success( $response );
				}
			}
		}
	}

	/**
	 * Display active reports
	 *
	 * @since    8.0
	 * @return   HTML
	 * @param    string     $type    		  	  Type of data to return
	 */
	public function display_reports( $type = false ){
		$content = '';
		$active_item_count = 0;

		ob_start(); ?>
		<div id="cartbounty-abandoned-cart-quick-stats-container" class="cartbounty-loading-skeleton-screen">
			<?php echo $this->display_report_skeleton( 'quick_stats' ); ?>
		</div>
		<div id="cartbounty-charts-container" class="cartbounty-loading-skeleton-screen">
			<?php echo $this->display_report_skeleton( 'charts' ); ?>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Display active quick stats
	 *
	 * @since    8.0
	 * @return   HTML
	 * @param    array     $active_reports    		  	  Active report data
	 */
	public function display_quick_stats( $active_reports = false ){
		$content = '';
		$active_item_count = 0;
		$active_quick_stats = array();
		
		if( $active_reports ){ //If active report data passed - use it

			if( isset( $active_reports['quick_stats'] ) ){
				$active_quick_stats = $active_reports['quick_stats'];
			}
			
		}else{ //If active report data missing (case of Ajax update) - retrieve active report data
			$active_quick_stats = $this->prepare_active_reports( 'quick_stats' );
		}

		if( !empty( $active_quick_stats ) ){ //If any quick stat has been enabled
			$active_item_count = count( $active_quick_stats );
			$content = $this->get_quick_stats_skeleton( $active_quick_stats, $active_item_count );
		}

		return $content;
	}

	/**
	 * Display active charts
	 *
	 * @since    8.0
	 * @return   HTML
	 * @param    array     $active_reports    		  	  Active report data
	 */
	public function display_charts( $active_reports = false ){
		$content = '';
		$active_charts = array();

		if( $active_reports ){ //If active report data passed - use it

			if( isset( $active_reports['charts'] ) ){
				$active_charts = $active_reports['charts'];
			}
			
		}else{ //If active report data missing (case of Ajax update) - retrieve active report data
			$active_charts = $this->prepare_active_reports( 'charts' );
		}

		if( !empty( $active_charts ) ){ //If any chart has been enabled
			$date_information = $this->get_selected_date_information();
			$content = $this->get_charts_skeleton( $active_charts, $date_information );
		}

		return $content;
	}

	/**
	 * Display top abandoned products
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function display_top_products(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$content = '<div class="cartbounty-report-content cartbounty-empty-top"><div class="cartbounty-empty-text">' . esc_html__( 'N/A', 'woo-save-abandoned-carts' ) .'</div></div>';
		$date_information = $this->get_selected_date_information();
		$carts = $this->get_abandoned_cart_rows( $date_information );
		$product_array = array();
		$start_date = $date_information['start_date'];
		$end_date = $date_information['end_date'];
		$start_date_timestamp = strtotime( $start_date );
		$end_date_timestamp = strtotime( $end_date );

		foreach( $carts as $key => $cart ){

			if( $admin->get_where_sentence( 'abandoned', false, $cart ) ){ //Continue only if cart is abandoned

				$cart_date = $cart->cart_date;
				$cart_date_timestamp = strtotime( $cart->cart_date );

				if( $cart_date_timestamp >= $start_date_timestamp && $cart_date_timestamp <= $end_date_timestamp ){ //Looking for dates in currently selected start - end date period (necessary since the results returned include both current and previous period data)
					$cart_count = $cart->cart_count;
					$cart_contents = $admin->get_saved_cart_contents( $cart->cart_contents );

					if( $cart_count > 1 ){ //If more than one cart found with the same products

						if( is_array( $cart_contents ) ){
							foreach( $cart_contents as $key => $product ){ //Making sure we account for grouped carts with the same products and adjust product quantity
								$cart_contents[$key]['quantity'] = $product['quantity'] * $cart_count;
							}
						}
					}
					$product_array[] = $cart_contents;
				}
			}
		}

		$top_products = $this->get_top_products( $product_array );
		
		if( empty( $top_products ) ) return $content;

		$content = $this->get_top_products_skeleton( $top_products );

		return $content;
	}

	/**
	 * Display abandoned carts by country
	 *
	 * @since    8.2
	 * @return   HTML
	 */
	public function display_carts_by_country(){
		$selected_report = $this->get_selected_map();
		$carts_by_country = $this->get_carts_by_country( $selected_report );
		$country_count = $this->get_selected_country_count();
		$top_countries = array_slice( $carts_by_country['data'], 0, $country_count, true ); //Select the count of countries that is selected
		$content = $this->get_map_skeleton( $top_countries, $carts_by_country );
		return $content;
	}

	/**
	 * Retrieve cart data by country
	 *
	 * @since    8.2
	 * @return   array
	 */
	public function get_carts_by_country(){
		$result = array();
		$date_information = $this->get_selected_date_information();
		$carts = $this->get_abandoned_cart_rows( $date_information );
		$selected_report = $this->get_selected_map();

		switch( $selected_report ){

			case 'abandoned-carts':
				$country_data = $this->get_cart_count( 'abandoned', $carts, $date_information, 'abandoned_carts_by_country' );
				$result['data'] = $country_data['carts_by_country'];
				break;
		}

		return $result;
	}

	/**
	 * Display selected date period dropdown
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function display_period_dropdown(){
		$date_information = $this->get_selected_date_information();
		$compare_start_date = $date_information['previous_period_start'];
		$compare_end_date = $date_information['previous_period_end'];
		
		if( $date_information['compare'] == 'previous-year'){ //In case comparing to previous year period
			$compare_start_date = $date_information['previous_year_start'];
			$compare_end_date = $date_information['previous_year_end'];
		}
		ob_start(); ?>
		<div id="cartbounty-period-dropdown">
			<div id="cartbounty-period-dropdown-main-row">
				<div id="cartbounty-period-dropdown-selected-period-label"><?php echo $this->get_date_periods( $date_information['period'] ); ?></div>
				<div id="cartbounty-period-dropdown-selected-period-date"><?php echo $this->get_user_friendly_date( $date_information['start_date'], $date_information['end_date'] );?></div>
			</div>
			<div id="cartbounty-period-dropdown-secondary-row"><?php echo $this->get_comparison_periods( $date_information['compare'] ); ?>: <?php echo $this->get_user_friendly_date( $compare_start_date, $compare_end_date );?></div>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Display available reports that can be enabled or disabled
	 *
	 * @since    8.0
	 * @return   HTML
	 * @param    string     $type    		  	  Type of data to return
	 */
	public function display_available_reports(){
		ob_start(); ?>
		<div id="cartbounty-abandoned-cart-stats-options">
			<div class="cartbounty-quick-stats-options">
				<h3><?php esc_html_e( 'Quick stats', 'woo-save-abandoned-carts' ); ?></h3>
				<?php echo $this->get_available_report_list_items( 'quick_stats' ); ?>
			</div>
			<div class="cartbounty-charts-options">
				<h3><?php esc_html_e( 'Charts', 'woo-save-abandoned-carts' ); ?></h3>
				<?php echo $this->get_available_report_list_items( 'charts' ); ?>
			</div>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Display available report currencies
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function display_selected_currency(){
		
		if( !$this->is_multiple_currencies() ) return; //Exit if we have just one currency

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$selected_currency = $this->get_selected_currency();

		ob_start(); ?>
		<div id="cartbounty-abandoned-cart-stats-currency">
			<h3 class="cartbounty-unavailable">
				<label for="cartbounty_selected_report_currency"><?php esc_html_e( 'Currency', 'woo-save-abandoned-carts' ); ?></label>
			</h3>
			<select id="cartbounty_selected_report_currency" class="cartbounty-select cartbounty-unavailable" disabled>
				<option><?php esc_html_e( $selected_currency );?></option>
			</select>
			<p class='cartbounty-additional-information'>
				<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $admin->display_unavailable_notice( 'report_multiple_currencies' ); ?></i>
			</p>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Display dropdown that allows to select how many products should be displayed in the top
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function display_top_product_count(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		ob_start(); ?>
		<div id="cartbounty-top-product-count" class="cartbounty-options-tooltip">
			<h3 class="cartbounty-unavailable">
				<label for="cartbounty_top_product_count"><?php esc_html_e( 'Product count', 'woo-save-abandoned-carts' ); ?></label>
			</h3>
			<select id="cartbounty_top_product_count" class="cartbounty-select cartbounty-unavailable" disabled autocomplete="off">
				<option>5</option>
			</select>
			<p class='cartbounty-additional-information'>
				<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $admin->display_unavailable_notice( 'report_product_count' ); ?></i>
			</p>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

		/**
	 * Display dropdown that allows to select how many countries should be displayed in the list under map
	 *
	 * @since    8.2
	 * @return   HTML
	 */
	public function display_selected_country_count(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		ob_start(); ?>
		<div id="cartbounty-country-count" class="cartbounty-options-tooltip">
			<h3>
				<label for="cartbounty_country_count"><?php esc_html_e( 'List size', 'woo-save-abandoned-carts' ); ?></label>
			</h3>
			<select id="cartbounty_country_count" class="cartbounty-select cartbounty-unavailable" disabled autocomplete="off">
				<option>5</option>
			</select>
			<p class='cartbounty-additional-information'>
				<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $admin->display_unavailable_notice( 'report_country_count' ); ?></i>
			</p>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Display dropdown that allows to select available map report
	 *
	 * @since    8.2
	 * @return   HTML
	 */
	public function display_selected_map_report(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$available_reports = $this->get_available_reports( 'map' );
		$selected_map = $this->get_selected_map();
		ob_start(); ?>
		<div id="cartbounty-available-map-reports" class="cartbounty-options-tooltip">
			<h3>
				<label for="cartbounty_available_map_reports"><?php esc_html_e( 'Report', 'woo-save-abandoned-carts' ); ?></label>
			</h3>
			<select id="cartbounty_available_map_reports" class="cartbounty-select cartbounty-unavailable" autocomplete="off">
				<?php foreach( $available_reports as $key => $option ): ?>
				<option value="<?php esc_attr_e( $key ); ?>" <?php echo selected( $selected_map, $key, false ); ?>><?php esc_html_e( $option );?></option>
				<?php endforeach; ?>
			</select>
			<p class='cartbounty-additional-information'>
				<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $admin->display_unavailable_notice( 'report_change_map_report_type' ); ?></i>
			</p>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Display available chart types
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	private function display_available_chart_types(){
		$available_chart_types = $this->get_available_chart_types();

		if( !is_array( $available_chart_types ) ) return;

		$selected_chart_type = $this->get_selected_chart_type();

		ob_start(); ?>
			<?php foreach( $available_chart_types as $key => $type ): ?>
				<div class='cartbounty-button button-secondary cartbounty-chart-type-trigger cartbounty-icon-button button<?php if( $selected_chart_type == $key) echo ' cartbounty-chart-type-active'; ?>' data-action="update_chart_type" data-nonce="<?php echo wp_create_nonce( 'update_chart_type' ); ?>" data-name="<?php echo esc_attr( $key ); ?>"><?php echo $type['icon']; ?></div>
			<?php endforeach; ?>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Output edit block options button
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function edit_options( $block_type ){

		if( $block_type == 'top-products' ){
			$data = $this->display_top_product_count();

		}elseif( $block_type == 'reports' ){
			$data = $this->display_selected_currency();
			$data .= $this->display_available_reports();

		}elseif( $block_type == 'carts-by-country' ){
			$data = $this->display_selected_map_report();
			$data .= $this->display_selected_country_count();
		}

		$more_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17.5 70"><circle cx="8.75" cy="8.75" r="8.75"/><circle cx="8.75" cy="61.25" r="8.75"/><circle cx="8.75" cy="35" r="8.75"/></svg>';

		ob_start(); ?>
		<div class='cartbounty-icon-button-container cartbounty-flex'>
			<?php if( $block_type == 'reports' ){
				echo $this->display_available_chart_types();
			}?>
			<div class='cartbounty-button button-secondary cartbounty-report-options-trigger cartbounty-icon-button button'><?php echo $more_icon; ?></div>
			<div class='cartbounty-report-options-container cartbounty-options-tooltip'><?php echo $data; ?></div>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Method returns available date periods and their corresponding names
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string      $period    		  Time period slug
	 */
	public function get_date_periods( $period = false ) {
		$available_periods = array(
			'today' 			=> esc_html__( 'Today', 'woo-save-abandoned-carts' ),
			'yesterday'			=> esc_html__( 'Yesterday', 'woo-save-abandoned-carts' ),
			'week-to-date'		=> esc_html__( 'Week to date', 'woo-save-abandoned-carts' ),
			'last-week'			=> esc_html__( 'Last week', 'woo-save-abandoned-carts' ),
			'month-to-date'		=> esc_html__( 'Month to date', 'woo-save-abandoned-carts' ),
			'last-month'		=> esc_html__( 'Last month', 'woo-save-abandoned-carts' ),
			'year-to-date'		=> esc_html__( 'Year to date', 'woo-save-abandoned-carts' ),
			'last-year'			=> esc_html__( 'Last year', 'woo-save-abandoned-carts' ),
			'custom'			=> esc_html__( 'Custom', 'woo-save-abandoned-carts' )
		);

		if( $period ){ //If a single period should be returned

			if( isset( $available_periods[$period] ) ){ //Checking if value exists
				$available_periods = $available_periods[$period];
			}
		}

		return $available_periods;
	}

	/**
	 * Method returns available date comparison options
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string      $period    		  Time period slug
	 */
	public function get_comparison_periods( $comparison = false ) {
		$available_comparisons = array(
			'previous-period' 	=> esc_html__( 'Previous period', 'woo-save-abandoned-carts' ),
			'previous-year'		=> esc_html__( 'Previous year', 'woo-save-abandoned-carts' )
		);

		if( $comparison ){ //If a single value should be returned

			if( isset( $available_comparisons[$comparison] ) ){ //Checking if value exists
				$available_comparisons = $available_comparisons[$comparison];
			}
		}

		return $available_comparisons;
	}

	/**
	 * Prepare array of different time intervals used for retrieving various report periods
	 * Time periods:
	 *		- today:			Period of curent day starting from 00:00:00 until end of today at 23:59:59
	 *		- yesterday:		Yesterday starting from 00:00:00 until end of yesterday at 23:59:59
	 *		- week-to-date:		Period of current week starting from Monday 00:00:00 until end of today at 23:59:59
	 *		- last-week:		Last week, from Monday 00:00:00 to Sunday 23:59:59
	 *		- month-to-date:	Period of current month starting from 1st day at 00:00:00 until end of today at 23:59:59
	 *		- last-month:		Full month from 1st day of month at 00:00:00 to last day of month at 23:59:59
	 *		- year-to-date:		Period of current year starting from 1st day at 00:00:00 until end of today at 23:59:59
	 *		- last-year:		Full year from 1st January at 00:00:00 to 31st December at 23:59:59
	 *
	 * @since    8.0
	 * @return 	 array
	 * @param    string      $time_period    		  Time period label
	 * @param    string      $custom_start    		  Custom period start date
	 * @param    string      $custom_end    		  Custom period end date
	 */
	private function get_time_intervals( $time_period, $custom_start = '', $custom_end = '' ){
		//Setting defaults
		$start_format 		= $this->get_defaults('start_format');
		$end_format 		= $this->get_defaults('end_format');
		$time_interval 		= '';
		$start_time_string 	= '';
		$end_time_string 	= '';
		$get_time_to_date 	= false;
		$days_passed 		= 0;

		switch( $time_period ){

			case 'today':
				$time_interval 		= 'day';
				$start_time_string 	= 'Today';
				$end_time_string 	= 'Today + 23 hours 59 minutes 59 seconds';
				break;

			case 'yesterday':
				$time_interval 		= 'day';
				$start_time_string 	= 'Yesterday';
				$end_time_string 	= 'Yesterday + 23 hours 59 minutes 59 seconds';
				break;

			case 'week-to-date':
				$time_interval 		= 'week';
				$start_time_string 	= 'Monday this week';
				$end_time_string 	= 'Today';
				$get_time_to_date 	= true;
				$days_passed 		= $this->get_current_period_days_passed( $start_format, $start_time_string );
				break;

			case 'last-week':
				$time_interval 		= 'week';
				$start_time_string 	= 'Monday last week';
				$end_time_string 	= 'Sunday last week';
				break;

			case 'month-to-date':
				$time_interval 		= 'month';
				$start_time_string 	= 'First day of this month';
				$end_time_string 	= 'Today';
				$get_time_to_date 	= true;
				$days_passed 		= $this->get_current_period_days_passed( $start_format, $start_time_string );
				break;

			case 'last-month':				
				$time_interval 		= 'month';
				$start_time_string 	= 'First day of last month';
				$end_time_string 	= 'Last day of last month';
				break;

			case 'year-to-date':
				$time_interval 		= 'year';
				$start_time_string 	= 'First day of January this year';
				$end_time_string 	= 'Today';
				$get_time_to_date 	= true;
				$days_passed 		= $this->get_current_period_days_passed( $start_format, $start_time_string );
				break;

			case 'last-year':
				$time_interval 		= 'year';
				$start_time_string 	= 'First day of January last year';
				$end_time_string 	= 'Last day of December last year';
				break;

			case 'custom':
				$time_interval 		= 'custom';
				$start_time_string 	= $custom_start;
				$end_time_string 	= $custom_end;
				$days_passed 	= $this->get_current_period_days_passed( $start_format, $start_time_string, $end_format, $end_time_string );
				break;
		}

		$intervals = $this->get_time_periods( $start_format, $end_format, $time_period, $time_interval, $start_time_string, $end_time_string, $get_time_to_date, $days_passed );
		return $intervals;
	}

	/**
	 * Method prepares time periods that are used for requesting cart data including comparing date periods (previous period / last year)
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string            $start_format             Start time format e.g. 'Y-m-d 00:00:00'
	 * @param    string            $end_format               End time format e.g. 'Y-m-d 00:00:00'
	 * @param    string            $time_period              Time period label
	 * @param    string            $time_interval            Time interval e.g. week, month, year etc.
	 * @param    string            $start_time_string        Start time string e.g. "First day of last month"
	 * @param    string            $end_time_string        	 End time string e.g. "Last day of last month"
	 * @param    boolean           $get_time_to_date       	 If need to return time that has passed until today e.g. "Week to date", "Month to date"
	 * @param    integer           $days_passed       	 	 How many days have passed since the start of the time period
	 */
	private function get_time_periods( $start_format, $end_format, $time_period, $time_interval, $start_time_string, $end_time_string, $get_time_to_date, $days_passed ){

		//Calculate the date range for the current period
		$start_date 	= date( $start_format, strtotime( $start_time_string ) );
		$end_date 		= date( $end_format, strtotime( $end_time_string ) );
		
		if( $get_time_to_date ){ //In case we must return time that has passed until today e.g. "Week to date", "Month to date"
			$end_time_string = $start_time_string;
		}

		//Calculate the date range for the previous period
		$previous_period_start 	= date( $start_format, strtotime( "$start_time_string -1 $time_interval" ) );
		$previous_period_end 	= date( $end_format, strtotime( "$end_time_string -1 $time_interval" ) );

		//Calculate the date range for the same period during previous year
		$previous_year_start 	= date( $start_format, strtotime( "$start_time_string -1 year" ) );
		$previous_year_end 		= date( $end_format, strtotime( "$end_time_string -1 year" ) );

		//In case we must add or subtract days that have passed
		if( $days_passed ){

			if( $time_period == 'custom' ){ //In case if dealing with a custom start and end periods
				$previous_period_start 	= date( $start_format, strtotime( "$previous_period_start -$days_passed day" ) );
				$previous_period_end 	= date( $end_format, strtotime( "$previous_period_end -$days_passed day" ) );
				
			}else{
				$previous_period_end 	= date( $end_format, strtotime( "$previous_period_end +$days_passed day" ) );
				$previous_year_end 		= date( $end_format, strtotime( "$previous_year_end +$days_passed day" ) );
			}
		}

		return array(
			'period'					=> $time_period,
			'start_date'				=> $start_date,
			'end_date'					=> $end_date,
			'previous_period_start'		=> $previous_period_start,
			'previous_period_end'		=> $previous_period_end,
			'previous_year_start'		=> $previous_year_start,
			'previous_year_end'			=> $previous_year_end
		);
	}

	/**
	 * Method calculates how many days have passed from the given period until today e.g. Days since the start of the current week or current month
	 * If $end_format and $end_time_string values are present - that means we are dealing with a custom period and must calculate days passed since the given date
	 *
	 * @since    8.0
	 * @return   integer
	 * @param    string            $start_format             Start time format e.g. 'Y-m-d 00:00:00'
	 * @param    string            $start_time_string        Start time string e.g. "Monday this week"
	 * @param    string            $end_time_string        	 End time string (used to calculate days passed for a custom period)
	 */
	private function get_current_period_days_passed( $start_format, $start_time_string, $end_format = '', $end_time_string = false ){
		$start_date_period = new DateTime( date( $start_format, strtotime( $start_time_string ) ) );
		$end_date_period = new DateTime();

		if( $end_time_string ){ //If we are dealing with a custom period, must add extra 1 day
			$end_date_period = new DateTime( date( $end_format, strtotime( $end_time_string . " +1 day") ) );
		}

		$interval = date_diff( $start_date_period, $end_date_period ); //Calculate how many days have passed since start of the period until today
		$days_passed = $interval->format( '%a' );
		return $days_passed;
	}

	/**
	 * Formatting date output to be user friendly
	 * If the dates are within the same year and month i want to format the dates like this format: "Jun 7 - 18, 2023"
     * If the dates are within the same year and months are in different like this: "Jun 1 - Aug 4, 2023"
     * If the dates are in different years and months are in different like this: "Jan 27, 2021 - Sep 30, 2023"
	 *
	 * @since    8.0
	 * @return   integer
	 * @param    string            $start_date             	Start time string e.g. '2023-01-01 00:00:00'
	 * @param    string            $end_date        		End time string e.g. '2023-11-17 23:59:59'
	 */
	function get_user_friendly_date( $start_date, $end_date ) {
		$start = new DateTime($start_date);
		$end = new DateTime($end_date);

		if( $start->format( 'Y-m' ) == $end->format( 'Y-m' ) ){ //Check if dates are within same year and month
			$formatted_start = date_i18n( 'M j', $start->getTimestamp() );
			$formatted_end = date_i18n( 'j, Y', $end->getTimestamp() );

		}elseif( $start->format( 'Y' ) == $end->format( 'Y' ) ){ //Check if dates are within same year
			$formatted_start = date_i18n( 'M j', $start->getTimestamp() );
			$formatted_end = date_i18n( 'M j, Y', $end->getTimestamp() );

		}else{ //If dates span across different years
			$formatted_start = date_i18n( 'M j, Y', $start->getTimestamp() );
			$formatted_end = date_i18n( 'M j, Y', $end->getTimestamp() );
		}

		//Return the formatted date string
		return $formatted_start . ' - ' . $formatted_end;
	}

	/**
	 * Method returns data for Daypicker calendar
	 * If nothing has been selected, return default calendar data
	 * If user has submitted some data (URL contains get parameters), use it to restore previous submission
	 *
	 * @since    8.0
	 * @return   integer
	 */
	public function prepare_daypicker_data(){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$defaults = $this->get_defaults();
		$date_periods = array();
		$comparison_periods = array();
		$fallback_period = 'custom';
		$locale = '';
		$user_locale = $admin->get_language_code( get_user_locale() );
		$report_parameters = $this->get_report_parameters();

		$default_date_period = array(
			'period' 	=> $report_parameters['period'],
			'name' 		=> $this->get_date_periods($report_parameters['period']),
		);

		if( $user_locale != 'en' ){ //No need to set locale in case we have English language alreayd set
			$locale = $user_locale;
		}
		
		//Prepare date periods
		foreach ( $this->get_date_periods() as $key => $period) {
			$intervals = $this->get_time_intervals( $key );
			$date_periods[] = array(
				'period'		=> $key,
				'name'			=> $period,
				'start_date'	=> $intervals['start_date'],
				'end_date'		=> $intervals['end_date']
			);
		}

		//Prepare comparison periods
		foreach( $this->get_comparison_periods() as $key => $comparison_period ){
			$comparison_periods[] = array(
				'label'		=> $key,
				'name'		=> $comparison_period,
			);
		}

		$data = apply_filters(
			'cartbounty_daypicker_settings', 
			array(
				'id' 					=> 'cartbounty-daypicker-container',
				'action' 				=> 'apply_report_period',
				'nonce' 				=> wp_create_nonce( 'apply_report_period' ),
				'defaultStartDate' 		=> $report_parameters['start_date'],
				'defaultEndDate' 		=> $report_parameters['end_date'],
				'defaultComparison'		=> $report_parameters['compare'],
				'defaultDatePeriod' 	=> $default_date_period,
				'mode' 					=> 'range',
				'showOutsideDays' 		=> false,
				'defaultFallbackPeriod' => array( //Used if no date has been selected
					'period'			=> $fallback_period,
					'name'				=> $this->get_date_periods( $fallback_period )
				),
				'dateFormat'			=> 'LLL d, yyyy', //Nov 1, 2023
				'minFromDate'			=> $defaults['min_start_date'],
				'maxToDate'				=> $defaults['max_end_date'],
				'weekStartsOn'			=> 1,
				'dir'					=> is_rtl() ? 'rtl' : 'ltr',
				'language'				=> $locale,
				'componentNames'        => array(
					'start'				=> esc_html__( 'Start date', 'woo-save-abandoned-carts' ),
					'end'				=> esc_html__( 'End date', 'woo-save-abandoned-carts' ),
					'compare' 			=> esc_html__( 'Compare to', 'woo-save-abandoned-carts' ),
					'close'				=> esc_html__( 'Close', 'woo-save-abandoned-carts' ),
					'submit' 			=> esc_html__( 'Apply', 'woo-save-abandoned-carts' ),
				),
				'comparisonOptions'		=> $comparison_periods,
				'datePeriods'			=> $date_periods
			)
		);

		return $data;
	}

	/**
	 * Method returns abandoned cart report parameters
	 * Either if data is submitted by $_POST or if reading it from $_GET
	 *
	 * @since    8.0
	 * @return   array
	 * @param    boolean            $form_submission        		If this request is coming from a daypicker form submission
	 */
	public function get_report_parameters( $form_submission = false ){
		$data = array();
		$defaults = $this->get_defaults();
		$default_intervals = $this->get_time_intervals( $defaults['default_period'] );
		$period = $defaults['default_period'];
		$start_date = $this->format_date( $default_intervals['start_date'] );
		$end_date = $this->format_date( $default_intervals['end_date'] );
		$compare = $defaults['default_comparison'];
		$url_parameters = array();
		$parameters_valid = true;

		if( $form_submission ){ //In case data must be retrieved from a form submission either without ajax and javascript
				
			if( is_array( $_POST ) ){ //If period is valid
				$found = '';
				$checkbox_needle = 'cartbounty_daypicker_checkbox_selected_period_';

				foreach( $_POST as $key => $value ){

					if ( strpos( $key, $checkbox_needle ) === 0) {
						$found = sanitize_text_field( $key );
						break;
					}
				}
				$found = substr( $found, strlen( $checkbox_needle ) );

				if( $found ){
					$url_parameters['period'] = $found;

				}else{
					$parameters_valid = false;
				}
			}

			if( !empty( $_POST['cartbounty_daypicker_start_date_input_duplicate'] ) ){

				if( $this->is_valid_date( $this->format_date( sanitize_text_field( $_POST['cartbounty_daypicker_start_date_input_duplicate'] ) ) ) ){ //If date is valid
					$url_parameters['start_date'] = $this->format_date( sanitize_text_field( $_POST['cartbounty_daypicker_start_date_input_duplicate'] ) );

				}else{
					$parameters_valid = false;
				}
			}

			if( !empty( $_POST['cartbounty_daypicker_end_date_input_duplicate'] ) ){

				if( $this->is_valid_date( $_POST['cartbounty_daypicker_end_date_input_duplicate'] ) ){ //If date is valid
					$url_parameters['end_date'] = $this->format_date( sanitize_text_field( $_POST['cartbounty_daypicker_end_date_input_duplicate'] ) );

				}else{
					$parameters_valid = false;
				}
			}

			$url_parameters['compare'] = $defaults['default_comparison'];

			if( !empty( $_POST['cartbounty_daypicker_checkbox_previous-year'] ) ){
				$url_parameters['compare'] = 'previous-year';
			}

		}else{ //Check if URL contains $_GET parameters for report (in case of page reload) or if ajax form submit sent data via $_POST

			if( !empty( $_GET ) || $this->is_valid_ajax_request( 'period_submit' ) || $this->is_valid_ajax_request( 'quick_stats_update' ) || $this->is_valid_ajax_request( 'chart_update' ) ){

				if( !empty( $_POST['period'] ) && $this->is_valid_period( $_POST['period'] ) ){ //Handling Ajax post data
					$url_parameters['period'] = $_POST['period'];

				}elseif( !empty( $_GET['period'] ) && $this->is_valid_period( $_GET['period'] ) ){ //Handling data coming from URL
					$url_parameters['period'] = $_GET['period'];

				}else{
					$parameters_valid = false;
				}

				if( !empty( $_POST['start_date'] ) && $this->is_valid_date( $this->format_date( sanitize_text_field( $_POST['start_date'] ) ) ) ){ //Handling Ajax post data
					$url_parameters['start_date'] = $this->format_date( sanitize_text_field( $_POST['start_date'] ) );

				}elseif( !empty( $_GET['start_date'] ) && $this->is_valid_date( $_GET['start_date'] ) ){ //Handling data coming from URL
					$url_parameters['start_date'] = $_GET['start_date'];

				}else{
					$parameters_valid = false;
				}

				if( !empty( $_POST['end_date'] ) && $this->is_valid_date( $this->format_date( sanitize_text_field( $_POST['end_date'] ) ) ) ){ //Handling Ajax post data
					$url_parameters['end_date'] = $this->format_date( sanitize_text_field( $_POST['end_date'] ) );

				}elseif( !empty( $_GET['end_date'] ) && $this->is_valid_date( $_GET['end_date'] ) ){ //Handling data coming from URL
					$url_parameters['end_date'] = $_GET['end_date'];

				}else{
					$parameters_valid = false;
				}

				$url_parameters['compare'] = $defaults['default_comparison'];

				if( !empty( $_POST['compare'] ) && $this->is_valid_compare_period( $_POST['compare'] ) ){ //Handling Ajax post data
					$url_parameters['compare'] = $_POST['compare'];

				}elseif( !empty( $_GET['compare'] ) && $this->is_valid_compare_period( $_GET['compare'] ) ){ //Handling data coming from URL
					$url_parameters['compare'] = $_GET['compare'];

				}else{
					$parameters_valid = false;
				}
			}
		}

		$data = array( //Setting default data
			'period' 		=> $period,
			'start_date' 	=> $start_date,
			'end_date' 		=> $end_date,
			'compare' 		=> $compare
		);

		if( $parameters_valid && count( $url_parameters ) > 3 ){ //If all parameters are valid and there are at least 4 parameters in the array, use the params from the URL or submitted calendar form
			$data = $url_parameters;
		}

		return $data;
	}

	/**
	 * Method takes care of date period form submit to retrieve selected date period
	 *
	 * @since    8.0
	 */
	function handle_calendar_period_submit(){
		if( !$this->is_php_period_submit() ) return;

		$url = $this->get_period_url( $form_submission = true );

   		wp_redirect( $url );
	}

	/**
	 * Preparing URL (adding get parameters to the link) after form submit (either using PHP or Ajax)
	 *
	 * @since    8.0
	 * @return   string
	 * @param    boolean            $form_submission        		If this request is coming from a daypicker form submission
	 */
	function get_period_url( $form_submission = false ){
		$current_page_url = $_SERVER['REQUEST_URI'];
		$report_parameters = $this->get_report_parameters( $form_submission );

		if( $this->is_valid_ajax_request( 'period_submit' ) ){ //If request coming from Ajax, use the current page URL passed from Ajax

			if( !empty( $_POST['current_url'] ) ){ //Handling Ajax post data
				$current_page_url = $_POST['current_url'];
			}
		}

		$url = add_query_arg( $report_parameters, sanitize_url( $current_page_url ) ); //Add parameters to the current URL
		return $url;
	}

	/**
	 * Collecting currently selected date information (period, comparison, dates etc.)
	 *
	 * @since    8.0
	 * @return   array
	 */
	private function get_selected_date_information(){
		$report_parameters = $this->get_report_parameters();
		$custom_start = '';
		$custom_end = '';

		if( $report_parameters['period'] == 'custom' ){ //If we are working with a custom date period
			$custom_start = $report_parameters['start_date'];
			$custom_end = $report_parameters['end_date'];
		}

		$intervals = $this->get_time_intervals( $report_parameters['period'], $custom_start, $custom_end );
		$intervals['compare'] = $report_parameters['compare'];

		//Setting compre period values according to selected compare period
		if( $report_parameters['compare'] == 'previous-year'){ //In case comparing to previous year period
			$intervals['compare_start_date'] = $intervals['previous_year_start'];
			$intervals['compare_end_date'] = $intervals['previous_year_end'];

		}else{
			$intervals['compare_start_date'] = $intervals['previous_period_start'];
			$intervals['compare_end_date'] = $intervals['previous_period_end'];
		}

		return $intervals;
	}

	/**
	 * Method is necessary for formating date coming from DayPicker input field to a date that can be used in a URL
	 *
	 * @since    8.0
	 * @return   string
	 * @param    string            $date           Date arriving from Daypicker input field, e.g. "jūn. 28, 2018"
	 */
	private function format_date( $date ){
		$timestamp = strtotime( $date );
		$formatted_date = date( 'Y-m-d', $timestamp );
		return $formatted_date;
	}

	/**
	 * Method checks if period that has been passed from $_GET parameter is valid or not
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string            $period           	Time period, e.g. "last-week"
	 */
	private function is_valid_period( $period ){
		$valid = false;
		$date_periods = $this->get_date_periods();

		if( array_key_exists( sanitize_text_field( $period ), $date_periods ) ){
			$valid = true;
		}

		return $valid;
	}

	/**
	 * Method checks if date that has been passed from $_GET parameter is valid or not
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string            $date           	Date string e.g. "2023-11-01"
	 */
	private function is_valid_date( $date ){
		$valid = false;

		if( strtotime( sanitize_text_field( $date ) ) !== false ){
			$valid = true;
		}

		return $valid;
	}

	/**
	 * Method checks if comparison period that has been passed from $_GET parameter is valid or not
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string            $comparison           		Comparison period, e.g. "previous-year"
	 */
	private function is_valid_compare_period( $comparison ){
		$valid = false;
		$valid_periods = $this->get_comparison_periods();

		if( array_key_exists( sanitize_text_field( $comparison ), $valid_periods ) ){
			$valid = true;
		}

		return $valid;
	}

	/**
	 * Method checks if report item that has been passed from $_GET parameter is valid or not
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string            $item           		Item label
	 * @param    string     	   $type    		  	Type of data to return
	 */
	private function is_valid_report( $item, $type ){
		$valid = false;
		$valid_items = $this->get_available_reports( $type );

		if( array_key_exists( sanitize_text_field( $item ), $valid_items ) ){
			$valid = true;
		}

		return $valid;
	}

	/**
	 * Retrieving data for active reports
	 *
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string     $report_part    		  	Weather a specific report part should only be prepared
	 */
	private function get_report_data( $report_part = false ){
		$report_data = array();
		$date_information = $this->get_selected_date_information();
		$carts = $this->get_abandoned_cart_rows( $date_information );
		$active_reports = $this->get_active_reports();

		if( is_array( $active_reports ) ){
			foreach( $active_reports as $type => $items ){
				
				if( is_array( $items ) ){

					if( $report_part == false || $report_part == $type ) { //Continue in case we need to return all report data or if single value needs to be returned and it is the current one

						foreach( $items as $key => $item ){

							//Shopping cart abandonment rate
							if( $item == 'abandonment-rate' ){
								$report_data[$type][$item] = $this->get_abandonment_rate( $carts, $date_information, $item );
							}

							//Abandoned carts
							if( $item == 'abandoned-carts' ){
								$report_data[$type][$item] = $this->get_cart_count( 'abandoned', $carts, $date_information, $item );
							}

							//Anonymous cart details
							if( $item == 'anonymous-carts' ){
								$report_data[$type][$item] = $this->get_cart_count( 'anonymous', $carts, $date_information, $item );
							}

							//Recoverable cart details
							if( $item == 'recoverable-carts' ){
								$report_data[$type][$item] = $this->get_cart_count( 'recoverable', $carts, $date_information, $item );
							}

							//Recovered cart details
							if( $item == 'recovered-carts' ){
								$report_data[$type][$item] = $this->get_cart_count( 'recovered', $carts, $date_information, $item );
							}

							//Recoverable cart revenue
							if( $item == 'recoverable-revenue' ){
								$report_data[$type][$item] = $this->get_cart_revenue( 'recoverable', $carts, $date_information, $item );
							}
						}
					}				
				}
			}
		}

		return $report_data;
	}

	/**
	 * Retrieve active report data
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string     $report_part    		  	Weather a specific report part should only be prepared
	 */
	private function prepare_active_reports( $report_part = false ){
		$active_reports = $this->get_active_reports();
		$active_item_data = array();
		$report_data = $this->get_report_data( $report_part );

		if( is_array( $active_reports ) ){
			foreach( $active_reports as $type => $items ){

				if( is_array( $items ) ){

					if( $report_part == false || $report_part == $type ) { //Continue in case we need to return all report data or if single value needs to be returned and it is the current one

						foreach( $items as $key => $item ){
							switch( $item ){
								case 'abandonment-rate':
									$abandonment_rate = $report_data[$type][$item];
									$active_item_data[$type][$item] = array(
										'label' => $this->get_available_reports( $type, $item ),
										'value' => $abandonment_rate['current_period_abandonment_rate'],
										'delta' => $abandonment_rate['delta'],
										'state' => $abandonment_rate['delta_state'],
									);
									break;

								case 'abandoned-carts':
									$abandoned_carts = $report_data[$type][$item];
									$active_item_data[$type][$item] = array(
										'label' 				=> $this->get_available_reports( $type, $item ),
										'value' 				=> $abandoned_carts['current_period_cart_count'],
										'delta' 				=> $abandoned_carts['delta'],
										'state' 				=> $abandoned_carts['delta_state'],
										'current_period_data' 	=> $abandoned_carts['current_period_data'],
										'previous_period_data' 	=> $abandoned_carts['previous_period_data'],
									);
									break;

								case 'anonymous-carts':
									$anonymous_cart = $report_data[$type][$item];
									$active_item_data[$type][$item] = array(
										'label' 				=> $this->get_available_reports( $type, $item ),
										'value' 				=> $anonymous_cart['current_period_cart_count'],
										'delta' 				=> $anonymous_cart['delta'],
										'state' 				=> $anonymous_cart['delta_state'],
									);
									break;

								case 'recoverable-carts':
									$recoverable_carts = $report_data[$type][$item];
									$active_item_data[$type][$item] = array(
										'label' 				=> $this->get_available_reports( $type, $item ),
										'value' 				=> $recoverable_carts['current_period_cart_count'],
										'delta' 				=> $recoverable_carts['delta'],
										'state' 				=> $recoverable_carts['delta_state'],
									);
									break;

								case 'recovered-carts':
									$recovered_carts = $report_data[$type][$item];
									$active_item_data[$type][$item] = array(
										'label' 				=> $this->get_available_reports( $type, $item ),
										'value' 				=> $recovered_carts['current_period_cart_count'],
										'delta' 				=> $recovered_carts['delta'],
										'state' 				=> $recovered_carts['delta_state'],
									);
									break;

								case 'recoverable-revenue':
									$recoverable_revenue = $report_data[$type][$item];
									$active_item_data[$type][$item] = array(
										'label' 				=> $this->get_available_reports( $type, $item ),
										'value' 				=> $recoverable_revenue['current_period_value'],
										'delta' 				=> $recoverable_revenue['delta'],
										'state' 				=> $recoverable_revenue['delta_state'],
									);
									break;
							}
						}
					}
				}
			}
		}

		if( $report_part ){ //If a single value should be returned

			if( isset( $active_item_data[$report_part] ) ){ //Checking if value exists
				$active_item_data = $active_item_data[$report_part];
			}
		}

		return $active_item_data;
	}

	/**
	 * Get abandoned cart rows for a given period of time and its corresponding comparison period
	 *
	 * @since    8.0
	 * @return   array
	 * @param    array            $date_information           	Array of dates
	 */
	private function get_abandoned_cart_rows( $date_information ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$start_date = $date_information['start_date'];
		$end_date = $date_information['end_date'];
		$compare_start_date = $date_information['compare_start_date'];
		$compare_end_date = $date_information['compare_end_date'];
		$currency_condition = '';
		$currency = array();
		
		if( $this->is_multiple_currencies() ){ //Adding additional currency parameter in case store has multiple currencies
			$selected_currency = $this->get_selected_currency();
			$currency_condition = !empty( $selected_currency ) ? "AND currency = %s" : "";
    		$currency = !empty( $selected_currency ) ? array( $selected_currency ) : array();
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				email,
				phone,
				cart_total,
				currency,
				type,
				location,
				cart_contents,
				DATE(time) AS cart_date,
				WEEK(time) AS cart_week,
				MONTH(time) AS cart_month,
				YEAR(time) AS cart_year,
				COUNT(id) AS cart_count
				FROM $cart_table
				WHERE ((time >= %s AND time <= %s) OR (time >= %s AND time <= %s))
				$currency_condition
				GROUP BY DATE(time), YEAR(time), email, phone, cart_contents, type
				ORDER BY cart_date", 
				array_merge(
					array(
						$start_date,
						$end_date,
						$compare_start_date,
						$compare_end_date
					),
					$currency
				)
			)
		);

		return $results;
	}

	/**
	 * Get all currencies that we recorded in our abandoned carts
	 *
	 * @since    8.0
	 * @return   array
	 */
	private function get_cart_currencies(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		$currencies = $wpdb->get_results(
			"SELECT DISTINCT currency
			FROM $cart_table
			ORDER BY currency",
			ARRAY_A
		);

		return wp_list_pluck( $currencies, 'currency' );
	}

	/**
	 * Method calculates abandonment rate in the given time period
	 * Measures the percentage of users who add items to the cart but leave without completing the order
	 *
	 * @since    8.0
	 * @return   array
	 * @param    array            $carts           				Abandoned carts
	 * @param    array            $date_information           	Array of dates
	 * @param    string           $report           			Report label
	 */
	private function get_abandonment_rate( $carts, $date_information, $report ){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$current_period_abandoned_count = 0;
		$current_period_converted_count = 0;
		$previous_period_abandoned_count = 0;
		$previous_period_converted_count = 0;

		$start_date = $date_information['start_date'];
		$end_date = $date_information['end_date'];
		$start_date_timestamp = strtotime( $start_date );
		$end_date_timestamp = strtotime( $end_date );

		foreach( $carts as $cart ){
			$cart_date = $cart->cart_date;
			$cart_date_timestamp = strtotime( $cart->cart_date );
			$cart_count = $cart->cart_count;
			$condition = ( $cart_date_timestamp >= $start_date_timestamp && $cart_date_timestamp <= $end_date_timestamp  );

			if( $admin->get_where_sentence( 'ordered', false, $cart ) ){
				
				if( $condition ){
					$current_period_converted_count += $cart_count;

				} else {
					$previous_period_converted_count += $cart_count;
				}

			}else{
				
				if( $condition ){
					$current_period_abandoned_count += $cart_count;

				}else{
					$previous_period_abandoned_count += $cart_count;
				}
			}
		}

		$current_period_total_carts = $current_period_abandoned_count + $current_period_converted_count;
		$previous_period_total_carts = $previous_period_abandoned_count + $previous_period_converted_count;
		$current_abandonment_rate = $this->calculate_rate( $current_period_abandoned_count, $current_period_total_carts );
		$previous_abandonment_rate = $this->calculate_rate( $previous_period_abandoned_count, $previous_period_total_carts );

		$delta = $this->calculate_delta( $previous_abandonment_rate, $current_abandonment_rate );
		$delta_state = $this->determine_delta_state( $delta, $report );

		$data = array(
			'previous_period_abandonment_rate' 	=> $previous_abandonment_rate . '%',
			'current_period_abandonment_rate' 	=> $current_abandonment_rate . '%',
			'delta'								=> $delta,
			'delta_state'						=> $delta_state
		);

		return $data;
	}

	/**
	 * Method calculates count of different abandoned cart types in the given time period
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string           $type           				Cart type
	 * @param    array            $carts           				Abandoned carts
	 * @param    array            $date_information           	Array of dates
	 * @param    string           $report           			Report label
	 */
	private function get_cart_count( $type, $carts, $date_information, $report ){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$previous_period_cart_count = 0;
		$current_period_cart_count = 0;
		$previous_period_data = array();
		$current_period_data = array();
		$current_period_cart_count_by_country = array();

		$start_date = $date_information['start_date'];
		$end_date = $date_information['end_date'];
		$previous_period_start = $date_information['previous_period_start'];
		$previous_period_end = $date_information['previous_period_end'];
		$start_date_timestamp = strtotime( $start_date );
		$end_date_timestamp = strtotime( $end_date );

		foreach( $carts as $cart ){
			$cart_date = $cart->cart_date;
			$cart_date_timestamp = strtotime( $cart->cart_date );
			$cart_count = $cart->cart_count;
			$country = $admin->get_cart_location( $cart->location, 'country');

			if( $admin->get_where_sentence( $type, false, $cart ) ){

				if( $cart_date_timestamp >= $start_date_timestamp && $cart_date_timestamp <= $end_date_timestamp ){ //Looking for dates in the specific start - end period
					//In case period data array already has an item on that day - add to the value of that day
					if( isset( $current_period_data[$cart_date] ) ){
						$current_period_data[$cart_date] += $cart_count;

					} else {
						$current_period_data[$cart_date] = $cart_count;
					}

					$current_period_cart_count += $cart_count;

					//Data for country based report
					if( $country ){

						if( isset( $current_period_cart_count_by_country[$country] ) ){
							$current_period_cart_count_by_country[$country] += $cart_count;

						} else {
							$current_period_cart_count_by_country[$country] = $cart_count;
						}
					}

				}else{
					//In case period data array already has an item on that day - add to the value of that day
					if( isset( $previous_period_data[$cart_date] ) ){
						$previous_period_data[$cart_date] += $cart_count;

					}else{
						$previous_period_data[$cart_date] = $cart_count;
					}

					$previous_period_cart_count += $cart_count;
				}
			}
		}

		//Add labels to data and arrange it in rows
		$previous_period_data = $this->prepare_period_data( $previous_period_data, $previous_period_start, $previous_period_end ); 
		$current_period_data = $this->prepare_period_data( $current_period_data, $start_date, $end_date );

		$delta = $this->calculate_delta( $previous_period_cart_count, $current_period_cart_count );
		$delta_state = $this->determine_delta_state( $delta, $report );

		$data = array(
			'previous_period_cart_count' 	=> $previous_period_cart_count,
			'current_period_cart_count' 	=> $current_period_cart_count,
			'previous_period_data'			=> $previous_period_data,
			'current_period_data'			=> $current_period_data,
			'delta'							=> $delta,
			'delta_state'					=> $delta_state,
			'carts_by_country'				=> $this->prepare_country_data( $current_period_cart_count_by_country )
		);

		return $data;
	}

	/**
	 * Method calculates revenue of different abandoned cart types in the given time period
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string           $type           				Cart type
	 * @param    array            $carts           				Abandoned carts
	 * @param    array            $date_information           	Array of dates
	 * @param    string           $report           			Report label
	 */
	private function get_cart_revenue( $type, $carts, $date_information, $report ){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$previous_period_value = 0;
		$current_period_value = 0;

		$start_date = $date_information['start_date'];
		$end_date = $date_information['end_date'];
		$previous_period_start = $date_information['previous_period_start'];
		$previous_period_end = $date_information['previous_period_end'];
		$start_date_timestamp = strtotime( $start_date );
		$end_date_timestamp = strtotime( $end_date );
		$currency = '';

		foreach( $carts as $cart ){
			$cart_date = $cart->cart_date;
			$cart_date_timestamp = strtotime( $cart->cart_date );
			$cart_total = $cart->cart_total;
			$currency = $cart->currency;

			if( $admin->get_where_sentence( $type, false, $cart ) ){

				if( $cart_date_timestamp >= $start_date_timestamp && $cart_date_timestamp <= $end_date_timestamp ){
					$current_period_value += $cart_total;

				}else{
					$previous_period_value += $cart_total;
				}
			}
		}

		$delta = $this->calculate_delta( $previous_period_value, $current_period_value );
		$delta_state = $this->determine_delta_state( $delta, $report );

		$data = array(
			'previous_period_value' 		=> $previous_period_value,
			'current_period_value' 			=> $this->add_currency_code( $current_period_value, $currency ),
			'delta'							=> $delta,
			'delta_state'					=> $delta_state
		);

		return $data;
	}

	/**
	 * Add label to each value
	 *
	 * @since    8.0
	 * @return   array
	 * @param    array            $data           		Period data array
	 * @param    array            $start_date          	Start date
	 * @param    array            $end_date           	End date
	 */
	private function prepare_period_data( $data, $start_date, $end_date ){
		$result = array();

		if( !empty( $data ) ){
			$full_period = $this->fill_mising_dates( $data, $start_date, $end_date );
			
			foreach( $full_period as $date => $count ){
				$result[] = array(
					'Date' => $date,
					'Value' => $count
				);
			}
		}

		return $result;
	}

	/**
	 * Calculate delta - difference between previous and current time periods
	 *
	 * @since    8.0
	 * @return   string
	 * @param    integer            $previous_value           	Previous period value
	 * @param    integer            $current_value           	Current period value
	 */
	private function calculate_delta( $previous_value, $current_value ){
		$delta = 0 . '%';

		if( $previous_value > 0 ){
			$delta = ( ( $current_value - $previous_value ) / $previous_value ) * 100;
			$delta = round( $delta );
			$sign = ( $delta > 0 ) ? '+' : ( $delta < 0 ? '-' : '' ); //Determine if show + or - sign before the number
			$delta = $sign . abs( $delta ) . '%';
		}					

		return $delta;
	}

	/**
	 * Determine if a positive delta value is good or bad for the store
	 *
	 * @since    8.0
	 * @return   string
	 * @param    string             $delta           			Previous period value
	 * @param    string           	$report           			Report label
	 */
	private function determine_delta_state( $delta, $report ){
		$state = '';
		$sign = substr( $delta, 0, 1 ); //Get the first character from delta value

		if( $sign === '-' ){
			$state = 'negative';

		}elseif( $sign === '+' ) {
			$state = 'positive';
		}

		if( in_array( $report, $this->get_defaults( 'reverse_delta_state' ) ) ){ //Checking if we should reverse state

			if( $state == 'negative' ){
				$state = 'positive';

			}elseif( $state == 'positive' ){
				$state = 'negative';
			}
		}

		return $state;
	}

	/**
	 * Calculate rate (e.g. recovery rate, abandonment rate)
	 *
	 * @since    8.0
	 * @return   float
	 * @param    integer            $carts           			Number of carts
	 * @param    integer            $total_carts           		Number of total carts
	 */
	private function calculate_rate( $carts, $total_carts ){
		$rate = 0;
		
		if( $total_carts > 0 ){
			$rate = ( $carts / $total_carts ) * 100;
			$rate = round( $rate );
		}

		return $rate;
	}

	/**
	 * Prepare country report data in the form that is required to display data
	 *
	 * @since    8.2
	 * @return   array
	 * @param    array           $data         Country data
	 */
	private function prepare_country_data( $data ){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$result = array();

		foreach( $data as $key => $value ){
			$country_name = $key;

			if( isset( WC()->countries ) ){

				if( array_key_exists( $key, WC()->countries->countries ) ){
					$country_name = WC()->countries->countries[ $key ];
				}
			}

			$result[] = array(
				'country' 		=> $admin->convert_country_code( $key ),
				'country_name' 	=> $country_name,
				'value' 		=> $value,
			);
		}

		usort( $result, function( $a, $b ){
			return $b['value'] - $a['value'];
		} );

		return $result;
	}


	/**
	 * Add currency symbol to a given value
	 *
	 * @since    8.0
	 * @return   float
	 * @param    integer            $value           			Amount of money
	 * @param    string             $currency           		Currency code to apply e.g. "USD"
	 */
	private function add_currency_code( $value, $currency ){
		
		if( !$currency ){
			$currency = $this->get_selected_currency();
		}

		$currency_symbol = $this->get_selected_currency_symbol( $currency );

		$result = '<span class="cartbounty-report-currency-code"> ' . $currency_symbol . '</span>' . $value;
		return $result;
	}

	/**
	 * Retrieve most popular product id values
	 *
	 * @since    8.0
	 * @return   array
	 * @param    array            	$cart_products           		Cart products
	 */
	private function get_top_products( $cart_products ){
		$top_products = array();
		$counted_products = array();

		if( is_array( $cart_products ) ){
			foreach( $cart_products as $cart_product ){

				if( is_array( $cart_product ) ){
					foreach( $cart_product as $product ){
						
						if( !empty( $product['product_variation_id'] ) ){
							$key_attributes = '';

							//Building attribute array so we could add them to key values.
							//Necessary to display the same product with different selected variations separately
							if( isset( $product['product_variation_attributes'] ) ){
								$attributes = array();

								foreach( $product['product_variation_attributes'] as $key => $attribute ){
									$attributes[] = $attribute;
								}

								if( !empty( $attributes ) ){
									$key_attributes = '_' . implode( '_', $attributes );
								}
							}

							$key = $product['product_variation_id'] . $key_attributes;

						}else{
							$key = $product['product_id'];
						}

						$id = $product['product_id'];
						$variation_id = $product['product_variation_id'];
						$product_title = $product['product_title'];
						$quantity = $product['quantity'];
						
						if( isset( $counted_products[$key] ) ){
							$counted_products[$key]['quantity'] += $quantity;

						}else{
							$counted_products[$key] = array(
								'product_id' 			=> $id,
								'product_variation_id' 	=> $variation_id,
								'product_title' 		=> $product_title,
								'quantity' 				=> $quantity
							);
						}
					}
				}
			}
		}

		usort( $counted_products, function( $a, $b ){ //Sort products by their quantities in descending order using custom comparison function
			return $b['quantity'] - $a['quantity'];
		} );

		$product_count = $this->get_selected_top_product_count();
		$top_products = array_slice( $counted_products, 0, $product_count, true ); //Pick top products

		return $top_products;
	}

	/**
	 * Fill date period with all of the dates in between that have 0 values (necessary in order to display Chart)
	 *
	 * @since    8.0
	 * @return   array
	 * @param    array            $data           		Period data array
	 * @param    array            $start_date          	Start date
	 * @param    array            $end_date           	End date
	 */
	private function fill_mising_dates( $data, $start_date, $end_date ){
		$period = array();
		$start_date = strtotime( $start_date );
		$end_date = strtotime( $end_date );

		// Loop over each date between start date and end date
		while( $start_date <= $end_date ){
			$formated_start_date = date( 'Y-m-d', $start_date ); //Converting current timestamp to Y-m-d format

			//If the date is in the original array, use the existing value, otherwise, set value to 0
			if( isset( $data[$formated_start_date] ) ){
				$period[$formated_start_date] = $data[$formated_start_date];
			}else{
				$period[$formated_start_date] = 0;
			}

			$start_date = strtotime( '+1 day', $start_date ); //Moving on to the next day
		}

		return $period;
	}

	/**
	 * Output report skeleton during initial page load
	 *
	 * @since    8.8
	 * @return   array
	 * @param    string           $report_type          Type of the report block
	 */
	public function display_report_skeleton( $report_type ){
		$skeleton = '';

		if( $report_type == 'quick_stats' || $report_type == 'charts' ){
			$active_reports = $this->get_active_reports( $report_type );

			if( is_array( $active_reports ) ){
				$active_item_count = count( $active_reports );
				
				if( $report_type == 'quick_stats' ){
					$skeleton = $this->get_quick_stats_skeleton( $active_reports, $active_item_count );
				
				}elseif( $report_type == 'charts' ){
					$skeleton = $this->get_charts_skeleton( $active_reports );
				}
			}

		}elseif( $report_type == 'top_products' ){
			$skeleton = $this->get_top_products_skeleton();

		}elseif( $report_type == 'map' ){
			$skeleton = $this->get_map_skeleton();
		}

		echo $skeleton;
	}

	/**
	 * Retrieve quick stats skeleton
	 *
	 * @since    8.8
	 * @return   HTML
	 * @param    array     		  $active_reports    	Active report data
	 * @param    integer          $active_item_count    Count of active reports
	 */
	public function get_quick_stats_skeleton( $active_reports = array(), $active_item_count = 0 ){
		ob_start(); ?>
		<div id="cartbounty-abandoned-cart-quick-stats">
			<ul class="cartbounty-quick-stat cartbounty-active-items-<?php echo $active_item_count; ?>">
				<?php foreach( $active_reports as $key => $item ): ?>
				<li class="cartbounty-quick-stat-item cartbounty-report-content<?php echo ( !empty( $item['state'] ) ) ? ' ' . $item['state'] : ''; ?>">
					<div class="cartbounty-quick-stat-label"><?php echo $item['label'] ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' ); ?></div>
					<div class="cartbounty-quick-stat-data">
						<div class="cartbounty-quick-stat-value"><?php echo $item['value'] ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' ); ?></div>
						<div class="cartbounty-quick-stat-delta"><?php echo $item['delta'] ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' ); ?></div>
					</div>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Retrieve charts skeleton
	 *
	 * @since    8.8
	 * @return   HTML
	 * @param    array     		  $active_reports    	Active report data
	 * @param    array     		  $date_information    	Date information
	 */
	public function get_charts_skeleton( $active_reports = array(), $date_information = array() ){
		ob_start(); ?>
		<?php foreach( $active_reports as $key => $item ): ?>
			<?php $chart_id = 'chart-' . $key; ?>
			<div class="cartbounty-abandoned-cart-chart">
				<div class="cartbounty-stats-header cartbounty-report-content">
					<h3><?php echo $item['label'] ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' ); ?></h3>
				</div>
				<div class="cartbounty-report-content-chart">
					<div class="cartbounty-report-chart-container">
						<div id="<?php echo $chart_id; ?>" class="cartbounty-report-chart"></div>
					</div>
					<?php if( !empty( $date_information ) ): ?>
					<script>
						var <?php echo str_replace( '-', '_', $chart_id ); ?>_start_date = <?php echo json_encode( $date_information['start_date'] ); ?>;
						var <?php echo str_replace( '-', '_', $chart_id ); ?>_end_date = <?php echo json_encode( $date_information['end_date'] ); ?>;
						var <?php echo str_replace( '-', '_', $chart_id ); ?>_current_period_data = <?php echo json_encode( $item['current_period_data'] ); ?>;
						var <?php echo str_replace( '-', '_', $chart_id ); ?>_previous_period_data = <?php echo json_encode( $item['previous_period_data'] ); ?>;
					</script>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Retrieve top products skeleton
	 *
	 * @since    8.8
	 * @return   HTML
	 * @param    array     		  $top_products    	Top products data
	 */
	public function get_top_products_skeleton( $top_products = array() ){
		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		$position = 0;
		$on_load = false;

		if( empty( $top_products ) ){ //If we are missing products
			$on_load = true;
			$top_products = range( 1, 3 );
		}

		ob_start(); ?>
		<table id="cartbounty-cart-top-products" class="cartbounty-dashboard-table">
			<tr>
				<th class="position"><?php esc_html_e( 'No.', 'woo-save-abandoned-carts' ); ?></th>
				<th class="product"><?php esc_html_e( 'Product', 'woo-save-abandoned-carts' ); ?></th>
				<th class="count"><?php esc_html_e( 'Count', 'woo-save-abandoned-carts' ); ?></th>
			</tr>
			<?php foreach( $top_products as $key => $product ): ?>
			<?php
				$position++;
				$product_title = $product['product_title'] ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' );
				$image_url = '#';
				$edit_product_link = '#';

				if( !$on_load ){ //If not triggered on page load, but Ajax request
					$image_url = $admin->get_product_thumbnail_url( $product, 'thumbnail' ) ?? 'na';
					$edit_product_link = get_edit_post_link( $product['product_id'], '&' ) ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' ); //Get product link by product ID
				}

				$image_html = '<img src="'. esc_url( $image_url ) .'" title="'. esc_attr( $product_title ) .'" alt="'. esc_attr( $product_title ).'" />';
			?>
			<tr>
				<td class="position">
					<div><?php echo $position; ?>.</div>
				</td>
				<td class="product">
					<div class="product-image-container">
						<?php if( $edit_product_link ): //If link exists (meaning the product hasn't been deleted) ?>
							<a href="<?php echo esc_url( $edit_product_link ); ?>" title="<?php echo esc_attr( $product_title ); ?>">
								<?php echo $image_html; ?>
							</a>
						<?php else: ?>
							<?php echo $image_html; ?>
						<?php endif;?>
					</div>
					<div>
						<?php if( $edit_product_link ): //If link exists (meaning the product hasn't been deleted) ?>
							<a href="<?php echo esc_url( $edit_product_link ); ?>" title="<?php echo esc_attr( $product_title ); ?>">
								<?php echo $product_title; ?>
							</a>
						<?php else: ?>
							<?php echo $product_title; ?>
						<?php endif;?>
					</div>
				</td>
				<td class="count">
					<div><?php echo $product['quantity'] ?? esc_html__( 'n/a', 'woo-save-abandoned-carts' ); ?></div>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Retrieve map skeleton
	 *
	 * @since    8.8
	 * @return   HTML
	 * @param    array     		  $top_countries    	Top countries that should be displayed
	 * @param    array     		  $carts_by_country    	Carts by country
	 */
	public function get_map_skeleton( $top_countries = array(), $carts_by_country = array() ){
		$position = 0;
		ob_start(); ?>
		<div id="cartbounty-country-map" class="cartbounty-report-content"></div>
		<?php if( is_array( $top_countries ) ): ?>
			<?php if( $top_countries ): ?>
			<table id="cartbounty-country-data" class="cartbounty-dashboard-table">
				<tr>
					<th class="position"><?php esc_html_e( 'No.', 'woo-save-abandoned-carts' ); ?></th>
					<th class="country"><?php esc_html_e( 'Country', 'woo-save-abandoned-carts' ); ?></th>
					<th class="count"><?php esc_html_e( 'Count', 'woo-save-abandoned-carts' ); ?></th>
				</tr>
				<?php foreach( $top_countries as $key => $country ): ?>
				<?php
					$position++;
					$country_name = $country['country_name'];
				?>
				<tr>
					<td class="position">
						<div><?php echo $position; ?>.</div>
					</td>
					<td class="country">
						<div>
							<?php echo $country_name; ?>
						</div>
					</td>
					<td class="count">
						<div>
							<?php echo $country['value']; ?>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
			<?php endif; ?>
		<?php endif; ?>
		<?php if( isset( $carts_by_country['data'] ) ): ?>
		<script>
			var abandoned_cart_country_data = <?php echo json_encode( $carts_by_country['data'] ); ?>;
		</script>
		<?php endif; ?>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}