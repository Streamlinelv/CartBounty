<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/admin
 * @author     Streamline.lv
 */
class CartBounty_Admin{

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
	 * @since    8.9
	 * @access   protected
	 * @var      CartBounty_Public    $public    Provides methods to control and extend the plugin's public area.
	 */
	protected $public = null;

	/**
	 * @since    8.9
	 * @access   protected
	 * @var      CartBounty_WordPress    		$wordpress    Provides methods to control and extend the plugin's WordPress area.
	 */
	protected $wordpress = null;

	/**
	 * @since    8.9
	 * @access   protected
	 * @var      CartBounty_Reports    		$reports    Provides methods to control and extend the plugin's Reports area.
	 */
	protected $reports  = null;

	/**
	 * @since 8.9
	 * @access protected
	 * @return CartBounty_Public
	 */
	protected function public(){
		
		if( $this->public === null ){
			$this->public = new CartBounty_Public( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
		}

		return $this->public;
	}

	/**
	 * @since 8.9
	 * @access protected
	 * @return CartBounty_WordPress
	 */
	protected function wordpress(){
		
		if( $this->wordpress === null ){
			$this->wordpress = new CartBounty_WordPress();
		}

		return $this->wordpress;
	}

	/**
	 * @since 8.9
	 * @access protected
	 * @return CartBounty_Reports
	 */
	protected function reports(){
		
		if( $this->reports === null ){
			$this->reports = new CartBounty_Reports();
		}

		return $this->reports;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    	      The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ){
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0
	 */
	public function enqueue_styles(){
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();

		if( !is_object( $screen ) ) return;

		wp_enqueue_style( $this->plugin_name . '-global', plugin_dir_url( __FILE__ ) . 'css/cartbounty-admin-global.css', $this->version ); //Global styles
		wp_style_add_data( $this->plugin_name . '-global', 'rtl', 'replace' );
		
		if( $screen->id == $cartbounty_admin_menu_page ){
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cartbounty-admin.css', array('wp-color-picker'), $this->version );
			wp_style_add_data( $this->plugin_name, 'rtl', 'replace' );
		}
	}

	/**
	 * Register the javascripts for the admin area.
	 *
	 * @since    3.0
	 */
	public function enqueue_scripts(){
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
		
		//Do not continue if we are not on CartBounty plugin page
		if( !is_object( $screen ) || $screen->id != $cartbounty_admin_menu_page ) return;

		$data = array(
		    'ajaxurl' => admin_url( 'admin-ajax.php' )
		);

		if( $screen->id == $cartbounty_admin_menu_page ){ //Load report scripts only on Dashboard

			if( !isset( $_GET['tab'] ) || $_GET['tab'] == 'dashboard' ){
				wp_enqueue_script( $this->plugin_name . '-d3', plugin_dir_url( __FILE__ ) . 'js/d3.min.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name . '-plot', plugin_dir_url( __FILE__ ) . 'js/plot.min.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name . '-daypicker', plugin_dir_url( __FILE__ ) . 'js/daypicker.min.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name . '-reports', plugin_dir_url( __FILE__ ) . 'js/cartbounty-reports.js', array( 'jquery' ), $this->version, false );

				//Adding extra data related with daypicker calendar
				$reports = $this->reports();
				$data['daypicker'] = $reports->prepare_daypicker_data();
				$data['active_charts'] = $reports->get_active_reports( 'charts' );
				$data['chart_type'] = $reports->get_selected_chart_type();
				$data['locale'] = $this->get_locale_with_hyphen( get_user_locale() );
				$data['report_translations'] = array(
					'missing_chart_data' 	=> $reports->get_defaults( 'empty_chart_data' ),
				);
				$data['countries'] = plugin_dir_url( __FILE__ ) . 'assets/countries.json';

			}
		}

		wp_enqueue_script( $this->plugin_name . '-micromodal', plugin_dir_url( __FILE__ ) . 'js/micromodal.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-selectize', plugin_dir_url( __FILE__ ) . 'js/selectize.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cartbounty-admin.js', array( 'wp-color-picker', 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'cartbounty_admin_data', $data ); //Sending data over to JS file
	}

	/**
	* Returning setting defaults
	*
	* @since    8.4
	* @return   array or string
	* @param    string     $value                Value to return
	*/
	public function get_defaults( $value = false ){
		$default_placeholders = $this->get_consent_default_placeholders();
		$checkout_consent = $default_placeholders['checkout_consent'];
		$tools_consent = $default_placeholders['tools_consent'];

		$defaults = array(
			'checkout_consent'				=> $checkout_consent,
			'checkout_consent_name'			=> 'Checkout consent label',
			'tools_consent'					=> $tools_consent,
			'tools_consent_name'			=> 'Tools consent notice',
		);

		if( $value ){ //If a single value should be returned
			
			if( isset( $defaults[$value] ) ){ //Checking if value exists
				$defaults = $defaults[$value];
			}
		}

		return $defaults;
	}

	/**
	* Retrieve CartBounty settings
	*
	* @since    8.1
	* @return   array
	* @param    string     $section              Name of the section which options should be retrieved
	* @param    string     $value                Value to return
	*/
	public function get_settings( $section, $value = false ){
		
		$saved_options = array();
		$defaults = array();

		switch ( $section ) {

			case 'settings':
				$saved_options = get_option( 'cartbounty_main_settings' );
				$defaults = array(
					'exclude_anonymous_carts' 	=> false,
					'notification_email' 		=> '',
					'notification_frequency' 	=> 3600000,
					'exclude_recovered' 		=> false,
					'email_consent'				=> false,
					'checkout_consent'			=> '',
					'tools_consent'				=> '',
					'lift_email'				=> false,
					'hide_images'				=> false,
				);
				break;

			case 'exit_intent':
				$saved_options = get_option( 'cartbounty_exit_intent_settings' );
				$defaults = array(
					'status' 			=> false,
					'heading' 			=> '',
					'content' 			=> '',
					'style' 			=> 1,
					'main_color' 		=> '',
					'inverse_color' 	=> '',
					'image' 			=> '',
					'test_mode' 		=> false,
				);
				break;

			case 'submitted_warnings':
				$saved_options = get_option( 'cartbounty_submitted_warnings' );
				$defaults = array(
					'cron_warning' 		=> false,
				);

				break;

			case 'misc_settings':
				$saved_options = get_option( 'cartbounty_misc_settings' );
				$defaults = array(
					'version_number' 					=> '',
					'recoverable_carts' 				=> 0,
					'anonymous_carts' 					=> 0,
					'recovered_carts' 					=> 0,
					'time_bubble_displayed' 			=> '',
					'time_bubble_steps_displayed' 		=> '',
					'times_review_declined' 			=> 0,
					'email_table_exists' 				=> false,
					'converted_minutes_to_milliseconds' => false,
				);

				break;
		}

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
	 * Register the menu under WooCommerce admin menu.
	 *
	 * @since    1.0
	 */
	function cartbounty_menu(){
		global $cartbounty_admin_menu_page;
		if(class_exists('WooCommerce')){
			$cartbounty_admin_menu_page = add_submenu_page( 'woocommerce', CARTBOUNTY_PLUGIN_NAME, esc_html__('CartBounty Abandoned carts', 'woo-save-abandoned-carts'), 'list_users', CARTBOUNTY, array( $this,'display_page' ) );
		}else{
			$cartbounty_admin_menu_page = add_menu_page( CARTBOUNTY_PLUGIN_NAME, esc_html__('CartBounty Abandoned carts', 'woo-save-abandoned-carts'), 'list_users', CARTBOUNTY, array( $this,'display_page' ), 'dashicons-archive' );
		}
	}

	/**
	 * Adds newly abandoned, recoverable abandoned cart count to the menu
	 *
	 * @since    1.4
	 */
	function menu_abandoned_count(){
		global $wpdb, $submenu;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		if ( isset( $submenu['woocommerce'] ) ) { //If WooCommerce Menu exists
			$time = $this->get_time_intervals();
			$where_sentence = $this->get_where_sentence( 'recoverable' );

			$recoverable_new_cart_count = $wpdb->get_var( //Counting newly abandoned carts
				$wpdb->prepare(
					"SELECT COUNT(id)
					FROM $cart_table
					WHERE cart_contents != ''
					$where_sentence AND
					time > %s",
					$time['old_cart']
				)
			);

			foreach( $submenu['woocommerce'] as $key => $menu_item ){ //Go through all submenu sections of WooCommerce and look for CartBounty Abandoned carts
				
				if( isset( $menu_item[0] ) ){
					if ( 0 === strpos( $menu_item[0], esc_html__( 'CartBounty Abandoned carts', 'woo-save-abandoned-carts' ) ) ){
						$submenu['woocommerce'][$key][0] .= ' <span class="new-abandoned update-plugins count-' . esc_attr( $recoverable_new_cart_count ) . '">' .  esc_html( $recoverable_new_cart_count ) .'</span>';
					}
				}
			}
		}
	}

	/**
	 * Adds Screen options tab and Help tab to plugin
	 *
	 * @since    5.0
	 */
	function register_admin_tabs(){
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();

		$wordpress = $this->wordpress();
		$status = new CartBounty_System_Status(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);

		// Check if we are on CartBounty page
		if(!is_object($screen) || $screen->id != $cartbounty_admin_menu_page){
			return;

		}else{
			//Outputting how many rows we would like to see on the page
			$option = 'per_page';
			$args = array(
				'label' 	=> esc_html__('Carts per page:', 'woo-save-abandoned-carts'),
				'default' 	=> 10,
				'option' 	=> 'cartbounty_carts_per_page'
			);

			//Adding screen options only on Abandoned cart page
			if(isset($_GET['tab'])){
				if($_GET['tab'] == 'carts'){
					add_screen_option( $option, $args );
				}
			}elseif(!isset($_GET['tab'])){
				add_screen_option( $option, $args );
			}

			$nonce = wp_create_nonce( 'get_system_status' );
			//Ads Help tab sections
			$cartbounty_carts_help_support_content = '
				<h2>'. esc_html__('Need help or support?', 'woo-save-abandoned-carts') .'</h2>
				<p>'. sprintf(
					/* translators: %1$s - Plugin name */
					esc_html__('%1$s saves all activity in the WooCommerce checkout form before it is submitted. The plugin allows to see who abandons your shopping carts and get in touch with them.', 'woo-save-abandoned-carts'), esc_html( CARTBOUNTY_ABBREVIATION ) ) . '<br/>' .
					esc_html__('You will receive regular email notifications about newly abandoned shopping carts and will be able to remind about these carts either manually or automatically by sending automated abandoned cart recovery messages.', 'woo-save-abandoned-carts') .'</p>
				<p>'. sprintf(
					/* translators: %1$s - Link start, %2$s - Link end */
					esc_html__('If you have additional questions, please see the readme file, or look at %1$sfrequently asked questions%2$s.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( CARTBOUNTY_FAQ_LINK ) .'" target="_blank">', '</a>' ) .'</p>
				<div class="cartbounty-button-row"><a href="'. esc_url( CARTBOUNTY_FAQ_LINK ) .'" class="cartbounty-button" target="_blank">'. esc_html__('View FAQ', 'woo-save-abandoned-carts') .'</a><span class="cartbounty-tooltip-container"><a href="#" id="cartbounty-copy-system-status" class="cartbounty-button cartbounty-progress" data-nonce="' . esc_attr( $nonce ) . '">'. esc_html__('Copy system report', 'woo-save-abandoned-carts') .'</a><span class="cartbounty-tooltip">'. esc_html__('Copied to clipboard', 'woo-save-abandoned-carts') .'</span></span><a href="'. esc_attr( CARTBOUNTY_SUPPORT_LINK ) .'" class="cartbounty-button" target="_blank">'. esc_html__( 'Get support', 'woo-save-abandoned-carts' ) .'</a></div>';

			$screen->add_help_tab( array(
				'id'       =>	'cartbounty_carts_help_support',
				'title'    =>	esc_html__( 'Need help?', 'woo-save-abandoned-carts' ),
				'content'  =>	$cartbounty_carts_help_support_content
			));

			$cartbounty_carts_help_request_feature = '
				<h2>'. esc_html__("Have a new feature in mind? That's awesome!", 'woo-save-abandoned-carts') .'</h2>
				<p>'. sprintf(
					/* translators: %s - Plugin name */
					esc_html__('We always welcome suggestions from our users and will evaluate each new idea to improve %s. In fact, many of the features you are currently using have arrived from users like yourself.', 'woo-save-abandoned-carts'), esc_html( CARTBOUNTY_ABBREVIATION ) ) .'</p>
				<div class="cartbounty-button-row"><a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_FEATURE_LINK, 'help_tab_suggest_feature') ) .'" class="cartbounty-button" target="_blank">'. esc_html__('Suggest a feature', 'woo-save-abandoned-carts') .'</a>
				</div>';
			$screen->add_help_tab( array(
				'id'       =>	'cartbounty_carts_request_feature',
				'title'    =>	esc_html__('Suggest a feature', 'woo-save-abandoned-carts'),
				'content'  =>	$cartbounty_carts_help_request_feature
			));
		}
	}

	/**
	 * Saves settings displayed under Screen options
	 *
	 * @since    1.0
	 */
	function save_page_options( $status, $option, $value ){
		return $value;
	}
	
	/**
	 * Display the abandoned carts and settings under admin page
	 *
	 * @since    1.3
	 */
	function display_page(){
		global $pagenow;
		
		if( !$this->user_is_shop_manager() ){
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-save-abandoned-carts' ) );
		}?>

		<div id="cartbounty-page-wrapper" class="wrap<?php if( $this->get_settings( 'settings', 'hide_images' ) ) {echo " cartbounty-without-thumbnails";}?>">
			<h1><?php esc_html_e( CARTBOUNTY_ABBREVIATION ); ?></h1>
			<?php do_action('cartbounty_after_page_title'); ?>

			<?php if( isset ( $_GET['tab'] ) ){
				$this->create_admin_tabs( $_GET['tab'] );
			}else{
				$this->create_admin_tabs( 'dashboard' );
			}

			if( $pagenow == 'admin.php' && $_GET['page'] == CARTBOUNTY ){
				$tab = $this->get_open_tab();
				$current_section = $this->get_open_section();

				if($current_section): //If one of the sections has been opened

					$sections = $this->get_sections( $tab );
					foreach( $sections as $key => $section ){
						if($current_section == $key): //Determine which section is open to output correct contents ?>
							<div id="cartbounty-content-container">
								<div class="cartbounty-row">
									<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
									<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-container">
											<h2 class="cartbounty-section-title"><?php esc_html_e( $section['name'] ); ?></h2>
											<?php $this->section_contents( $key ); ?>
										</div>
									</div>
								</div>
							</div>
						<?php endif;
					}?>

				<?php elseif( $tab == 'dashboard' ): //Dashboard tab ?>

					<?php
					$reports = $this->reports();
					$nonce = wp_create_nonce('cartbounty_report_period');
					?>

					<div id="cartbounty-content-container" class="cartbounty-dashboard">
						<div class="cartbounty-row cartbounty-flex">
							<div class="cartbounty-col-xs-6 cartbounty-col-sm-6 cartbounty-dashboard-title-column">
								<h2 id="cartbounty-dashboard-title" class="cartbounty-section-title"><?php esc_html_e( 'Welcome to Dashboard', 'woo-save-abandoned-carts' ); ?></h2>
							</div>
							<div class="cartbounty-col-xs-6 cartbounty-col-sm-6 cartbounty-calendar-column">
								<div id="cartbounty-calendar-container">
									<div id="cartbounty-period-dropdown-container">
										<?php echo $reports->display_period_dropdown(); ?>
									</div>
									<div id="cartbounty-daypicker-container">
										<form method="post">
											<input type="hidden" name="cartbounty_apply_period" value="cartbounty_apply_period_c" />
											<input type="hidden" name="cartbounty_report_period" value="<?php echo $nonce; ?>">
											<div id='cartbounty-daypicker-container'></div>
										</form>
									</div>
								</div>
							</div>
							<div class="cartbounty-col-xs-12 cartbounty-col-md-6 cartbounty-col-lg-7">
								<div class="cartbounty-abandoned-cart-stats-block cartbounty-report-widget">
									<div class="cartbounty-stats-header cartbounty-report-content">
										<h3>
											<i class="cartbounty-widget-icon cartbounty-abandoned-cart-stats-icon">
												<img src="<?php echo esc_url( plugins_url( 'assets/reports-icon.svg', __FILE__ ) ) ?>" />
											</i><?php esc_html_e( 'Abandoned cart reports', 'woo-save-abandoned-carts' ); ?>
										</h3>
										<?php echo $reports->edit_options( 'reports' ); ?>
									</div>
									<?php echo $reports->display_reports(); ?>
								</div>
							</div>
							<div class="cartbounty-col-xs-12 cartbounty-col-md-6 cartbounty-col-lg-5">
								<?php echo $this->display_dashboard_notices(); ?>
								<div class="cartbounty-abandoned-carts-by-country cartbounty-report-widget">
									<div class="cartbounty-stats-header cartbounty-report-content">
										<h3 id="cartbounty-abandoned-carts-by-country-report-name-container">
											<i class="cartbounty-widget-icon cartbounty-top-abandoned-products-icon">
												<img src="<?php echo esc_url( plugins_url( 'assets/world-map-icon.svg', __FILE__ ) ) ?>" />
											</i>
											<span id="cartbounty-abandoned-carts-by-country-report-name"><?php echo $reports->get_selected_map_report_name(); ?></span>
										</h3>
										<?php echo $reports->edit_options( 'carts-by-country' ); ?>
									</div>
									<div id="cartbounty-abandoned-carts-by-country-container" class="cartbounty-loading-skeleton-screen">
										<?php echo $reports->display_report_skeleton( 'map' ); ?>
									</div>
								</div>
								<div class="cartbounty-top-abandoned-products cartbounty-report-widget">
									<div class="cartbounty-stats-header cartbounty-report-content">
										<h3>
											<i class="cartbounty-widget-icon cartbounty-top-abandoned-products-icon">
												<img src="<?php echo esc_url( plugins_url( 'assets/top-products-icon.svg', __FILE__ ) ) ?>" />
											</i>
											<?php esc_html_e( 'Top abandoned products', 'woo-save-abandoned-carts' ); ?>
										</h3>
										<?php echo $reports->edit_options( 'top-products' ); ?>
									</div>
									<div id="cartbounty-top-abandoned-products-container">
										<div id="cartbounty-cart-top-products" class="cartbounty-dashboard-table cartbounty-loading-skeleton-screen">
											<?php echo $reports->display_report_skeleton( 'top_products' ); ?>
										</div>
									</div>
								</div>
								<?php $active_features = $this->display_active_features();
								if( !empty( trim( $active_features ) ) ) : ?>
								<div class="cartbounty-active-features">
									<h3><?php esc_html_e( 'Active features', 'woo-save-abandoned-carts' ); ?></h3>
									<div class="cartbounty-row cartbounty-flex">
										<?php echo $active_features; ?>
									</div>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

				<?php elseif( $tab == 'carts' ): //Abandoned carts tab ?>

					<?php
						require_once plugin_dir_path( __FILE__ ) . 'class-cartbounty-admin-table.php';
						$table = new CartBounty_Table();
						$table->prepare_items();
						$current_action = $table->current_action();

						//Output table contents
						$message = '';

						if( $current_action ){

							if( !empty( $_REQUEST['id'] ) ){ //In case we have a row selected, process the message output
								$processed_rows = 0;
								$action_message = esc_html__( 'Carts deleted: %d', 'woo-save-abandoned-carts' );

								if( isset( $_REQUEST['processed_rows'] ) ){
									$processed_rows = esc_html( $_REQUEST['processed_rows'] );
								}

								if( $current_action === 'delete' ){
									$action_message = sprintf( $action_message, esc_html( $processed_rows ) );
									$notification_class = 'updated';

								}else{
									$action_message = $this->display_unavailable_notice( 'bulk_actions' );
									$notification_class = 'error';
								}

								$message = '<div class="'. $notification_class .' below-h2" id="message"><p>' . $action_message . '</p></div>';
							}
						}

						$cart_status = 'all';
						if (isset($_GET['cart-status'])){
							$cart_status = $_GET['cart-status'];
						}
					?>
					<?php echo $message; 
					if ($this->get_cart_count( 'all' ) == 0): //If no abandoned carts, then output this note ?>
						<p id="cartbounty-no-carts-message">
							<?php echo wp_kses_post( __( 'Looks like you do not have any saved Abandoned carts yet.<br/>But do not worry, as soon as someone fills the <strong>Email</strong> or <strong>Phone number</strong> fields of your WooCommerce Checkout form and abandons the cart, it will automatically appear here.', 'woo-save-abandoned-carts') ); ?>
						</p>
					<?php else: ?>
						<?php $action_url = admin_url( 'admin.php' );?>
						<form id="cartbounty-table" method="GET" action="<?php echo esc_url( $action_url ); ?>">
							<div class="cartbounty-row">
								<div class="cartbounty-col-sm-6">
									<?php $this->display_cart_statuses( $cart_status, $tab);?>
								</div>
								<div class="cartbounty-col-sm-6">
									<div id="cartbounty-cart-search">
										<label class="screen-reader-text cartbounty-unavailable" for="post-search-input"><?php esc_html_e('Search carts', 'woo-save-abandoned-carts'); ?> :</label>
										<input type="search" id="cartbounty-cart-search-input" class="cartbounty-text cartbounty-unavailable" disabled readonly><button type="button" id="cartbounty-search-submit" class="cartbounty-button button button-secondary cartbounty-unavailable"><i class="cartbounty-icon cartbounty-visible-xs"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70.06 70"><path d="M30,60a29.9,29.9,0,0,0,16.64-5.06L60.7,69a3.58,3.58,0,0,0,5,0L69,65.64a3.57,3.57,0,0,0,0-5l-14.13-14A30,30,0,1,0,30,60Zm0-48.21A18.23,18.23,0,1,1,11.76,30,18.24,18.24,0,0,1,30,11.76Zm0,0" transform="translate(0)"/></svg></i><i class='cartbounty-hidden-xs'><?php esc_html_e('Search carts', 'woo-save-abandoned-carts'); ?></i>
										</button>
										<p class='cartbounty-additional-information'>
											<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'enable_search' ); ?></i>
										</p>
									</div>
								</div>
							</div>
							<input type="hidden" name="cart-status" value="<?php echo esc_attr( $cart_status ); ?>">
							<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"/>
							<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>"/>
							<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'bulk_action_nonce' ); ?>"/>
							<?php $table->display(); ?>
						</form>
					<?php endif; ?>

				<?php elseif( $tab == 'recovery' ): //Recovery tab ?>

					<div id="cartbounty-content-container">
						<div class="cartbounty-row">
							<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
							<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
								<h2 class="cartbounty-section-title"><?php esc_html_e('Recovery', 'woo-save-abandoned-carts'); ?></h2>
								<div class="cartbounty-section-intro"><?php esc_html_e('Automate your abandoned cart recovery by sending automated recovery emails, SMS text messages and web push notifications to your visitors.', 'woo-save-abandoned-carts')?><br/> <?php echo sprintf(
									/* translators: %s - URL link tags */
									esc_html__('Please consider upgrading to %s%s Pro%s to connect one of the professional automation services listed below.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'recovery' ) ) .'" target="_blank">', esc_html( CARTBOUNTY_ABBREVIATION ), '</a>'); ?></div>
								<div class="cartbounty-row cartbounty-flex">
									<?php
									$recovery_items = $this->get_sections( $tab );
									foreach( $recovery_items as $key => $item ): ?>
										<?php $button = esc_html__('Connect', 'woo-save-abandoned-carts');
											if($item['connected']){
												$button = esc_html__('Edit', 'woo-save-abandoned-carts');
											}elseif(!$item['availability']){
												$button = esc_html__('Get Pro to connect', 'woo-save-abandoned-carts');
											}
										?>
										<div class="cartbounty-section-item-container cartbounty-col-sm-6 cartbounty-col-lg-4">
											<div class="cartbounty-section-item<?php if($item['connected']){echo ' cartbounty-connected'; }?><?php if(!$item['availability']){echo ' cartbounty-unavailable'; }?><?php if($item['faded']){echo ' cartbounty-item-faded'; }?>">
												<?php if($item['availability']){
													$link = '?page='. CARTBOUNTY .'&tab='. $tab .'&section='. $key;
													$item['info_link'] = $link;
												}else{
													$link = $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'recovery_'. $key );
												}?>
												<div class="cartbounty-section-image">
													<?php echo $this->get_connection( $item['connected'], true, $tab ); ?>
													<a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>"><?php echo $this->get_icon( $key, false, false, true ); ?></a>
												</div>
												<div class="cartbounty-section-content">
													<h3><a href="<?php echo esc_url( $item['info_link'] ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>" <?php if(!$item['availability']){echo ' target="_blank"'; }?>><?php echo esc_html( $item['name'] ); ?></a></h3>
													<?php echo wp_kses_post( $item['description'] ); ?>
													<a class="button cartbounty-button<?php if(!$item['availability']){echo " button-primary";}?>" href="<?php echo esc_url( $link ); ?>"<?php if(!$item['availability']){echo ' target="_blank"'; }?>><?php echo esc_html( $button ); ?></a>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>

				<?php elseif( $tab == 'tools' ): //Tools tab ?>

					<div id="cartbounty-content-container">
						<div class="cartbounty-row">
							<div class="cartbounty-sidebar cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3"><?php $this->display_sections( $current_section, $tab ); ?></div>
							<div class="cartbounty-content cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
								<h2 class="cartbounty-section-title"><?php esc_html_e('Tools', 'woo-save-abandoned-carts'); ?></h2>
								<div class="cartbounty-section-intro"><?php esc_html_e('Here you will find some special tools that will enable you to discover even more bounty. Increase your chances of getting more recoverable abandoned carts. You can enable one or all of them, just make sure not to overwhelm your visitors with information :)', 'woo-save-abandoned-carts'); ?></div>
								<div class="cartbounty-row cartbounty-flex">
									<?php
									$tools_items = $this->get_sections( $tab );
									foreach( $tools_items as $key => $item ): ?>
										<?php $button = esc_html__('Enable', 'woo-save-abandoned-carts'); 
											if($item['connected']){
												$button = esc_html__('Edit', 'woo-save-abandoned-carts');
											}
										?>
										<div class="cartbounty-section-item-container cartbounty-col-sm-6 cartbounty-col-lg-4">
											<div class="cartbounty-section-item<?php if($item['connected']){echo ' cartbounty-connected'; }?><?php if($item['faded']){echo ' cartbounty-item-faded'; }?>">
												<?php $link = '?page='. CARTBOUNTY .'&tab='. $tab .'&section='. $key; ?>
												<div class="cartbounty-section-image">
													<?php echo $this->get_connection( $item['connected'], true, $tab ); ?>
													<a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>"><?php echo $this->get_icon( $key, false, false, true ); ?></a>
												</div>
												<div class="cartbounty-section-content">
													<h3><a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>"><?php echo esc_html( $item['name'] ); ?></a></h3>
													<?php echo wp_kses_post( $item['description'] ); ?>
													<a class="button cartbounty-button" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $button ); ?></a>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>

				<?php elseif( $tab == 'settings' ): //Settings tab ?>

					<div id="cartbounty-content-container">
						<div class="cartbounty-settings-container">
							<form method="post" action="options.php">
								<?php
									$settings = $this->get_settings( 'settings' );
									$exclude_anonymous_carts = $settings['exclude_anonymous_carts'];
									$notification_email = $settings['notification_email'];
									$exclude_recovered = $settings['exclude_recovered'];
									$email_consent = $settings['email_consent'];
									$checkout_consent = $settings['checkout_consent'];
									$tools_consent = $settings['tools_consent'];
									$lift_email = $settings['lift_email'];
									$hide_images = $settings['hide_images'];
								?>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e( 'Abandoned carts', 'woo-save-abandoned-carts' ); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e( 'Manage shopping cart exclusions. Specify emails or phone numbers to prevent them from being saved as abandoned carts by CartBounty.', 'woo-save-abandoned-carts' ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9<?php if( $exclude_anonymous_carts ){ echo ' cartbounty-checked-parent'; }?>">
										<div class="cartbounty-settings-group cartbounty-toggle <?php if( $exclude_anonymous_carts ){ echo ' cartbounty-checked'; }?>">
											<label for="cartbounty-exclude-anonymous-carts" class="cartbounty-switch cartbounty-control-visibility">
												<input id="cartbounty-exclude-anonymous-carts" class="cartbounty-checkbox" type="checkbox" name="cartbounty_main_settings[exclude_anonymous_carts]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exclude_anonymous_carts, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-exclude-anonymous-carts" class="cartbounty-control-visibility">
												<?php esc_html_e( 'Exclude anonymous carts', 'woo-save-abandoned-carts' ); ?>
											</label>
										</div>
										<div class="cartbounty-settings-group cartbounty-hidden">
											<label for="cartbounty_allowed_countries" class="cartbounty-unavailable"><?php esc_html_e('Exclude from all countries except these', 'woo-save-abandoned-carts'); ?></label>
											<select id="cartbounty-allowed-countries" class="cartbounty-select cartbounty-unavailable" disabled>
												<option><?php esc_html_e( 'Choose countries / regions…', 'woo-save-abandoned-carts' ); ?></option>
											</select>
											<button id="cartbounty-add-all-countries" class="cartbounty-button button button-secondary cartbounty-unavailable hidden" type="button"><?php esc_html_e('Select all', 'woo-save-abandoned-carts'); ?></button>
											<button id="cartbounty-remove-all-countries" class="cartbounty-button button button-secondary cartbounty-unavailable hidden" type="button"><?php esc_html_e('Select none', 'woo-save-abandoned-carts'); ?></button>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'anonymous_countries' ); ?></i>
											</p>
										</div>
										<div class="cartbounty-settings-group">
											<label for="cartbounty_delete_anonymous_carts" class="cartbounty-unavailable"><?php esc_html_e( 'Automatically delete anonymous carts older than', 'woo-save-abandoned-carts' ); ?></label>
											<select id="cartbounty_delete_anonymous_carts" class="cartbounty-select cartbounty-unavailable" disabled autocomplete="off">
												<option><?php esc_html_e( 'Disable deletion', 'woo-save-abandoned-carts' ); ?></option>
											</select>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'anonymous_cart_auto_delete' ); ?></i>
											</p>
										</div>
										<div class="cartbounty-settings-group">
											<label for="cartbounty-excluded-emails-phones" class="cartbounty-unavailable"><?php esc_html_e( 'Exclude carts containing any of these emails and phone numbers', 'woo-save-abandoned-carts' ); ?></label>
											<input id="cartbounty-excluded-emails-phones" class="cartbounty-text cartbounty-unavailable" type="text" disabled autocomplete="off" />
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'settings_exclude_carts_by_email_phone' ); ?></i>
											</p>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-hide-images" class="cartbounty-switch">
												<input id="cartbounty-hide-images" class="cartbounty-checkbox" type="checkbox" name="cartbounty_main_settings[hide_images]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $hide_images, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-hide-images"><?php esc_html_e('Display abandoned cart contents in a list', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('This will only affect how abandoned cart contents are displayed in the list of abandoned carts.', 'woo-save-abandoned-carts'); ?>
											</p>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Notifications', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Receive email notifications about newly abandoned carts. Please note, that you will not get emails about anonymous carts.', 'woo-save-abandoned-carts'); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group">
											<label for="cartbounty_notification_email"><?php esc_html_e('Admin email', 'woo-save-abandoned-carts'); ?></label>
											<input id="cartbounty_notification_email" class="cartbounty-text cartbounty-display-emails" type="email" name="cartbounty_main_settings[notification_email]" value="<?php echo esc_attr( $notification_email ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) );?>" <?php echo $this->disable_field(); ?> multiple />
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('You can add multiple emails separated by a comma.', 'woo-save-abandoned-carts'); ?>
											</p>
										</div>
										<div class="cartbounty-settings-group">
											<?php $this->display_time_intervals( 'cartbounty_main_settings[notification_frequency]' ); ?>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-exclude-recovered" class="cartbounty-switch">
												<input id="cartbounty-exclude-recovered" class="cartbounty-checkbox" type="checkbox" name="cartbounty_main_settings[exclude_recovered]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exclude_recovered, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-exclude-recovered">
												<?php esc_html_e('Exclude recovered carts from notifications', 'woo-save-abandoned-carts'); ?>
											</label>
										</div>
									</div>
								</div>
								<?php if( $this->gtranslate_is_active() ): ?>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e( 'GTranslate', 'woo-save-abandoned-carts' ); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo sprintf(
											/* translators: %s - Link tags */
											esc_html__( 'Translate abandoned cart reminder messages using GTranslate API. %sSee this%s to learn how to configure translations.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'translations', 'gtranslate_configuration', '#gtranslate' ) ) .'" title="'. esc_attr__( 'Configure translations with GTranslate', 'woo-save-abandoned-carts' ) .'" target="_blank">', '</a>' ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group">
											<label for="cartbounty_gtranslate_key" class="cartbounty-unavailable"><?php esc_html_e( 'API Key', 'woo-save-abandoned-carts' ); ?></label>
											<input id="cartbounty_gtranslate_key" class="cartbounty-text cartbounty-unavailable disabled" type="text" />
											<p class='cartbounty-additional-information'>
												<?php echo sprintf(
												/* translators: %s - Link tags */
												esc_html__( 'You can sign up for a free API key %shere%s.', 'woo-save-abandoned-carts' ), '<a href="https://translatex.com" target="_blank">', '</a>' ); ?>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><br/><?php echo $this->display_unavailable_notice( 'gtranslate' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<?php endif; ?>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Protection', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php echo sprintf(
											/* translators: %s - URL link */
											esc_html__('In case you feel that bots might be leaving recoverable abandoned carts. Please %sview this%s to learn how to prevent bots from leaving anonymous carts.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_LICENSE_SERVER_URL . 'abandoned-carts', 'anonymous_bots', '#prevent-bots-from-leaving-abandoned-carts' ) ) .'" title="'. esc_attr__('Prevent bots from leaving anonymous carts', 'woo-save-abandoned-carts') .'" target="_blank">', '</a>' ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty_recaptcha" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty_recaptcha" class="cartbounty-checkbox" type="checkbox" disabled />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty_recaptcha" class="cartbounty-unavailable"><?php esc_html_e('Enable reCAPTCHA v3', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'recaptcha' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Unfinished orders', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Send reminders about WooCommerce orders that have been abandoned.', 'woo-save-abandoned-carts'); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group-container">
											<div class="cartbounty-settings-group cartbounty-toggle">
												<label for="cartbounty-order-recovery" class="cartbounty-switch cartbounty-unavailable">
													<input id="cartbounty-order-recovery" class="cartbounty-checkbox" type="checkbox" disabled />
													<span class="cartbounty-slider round"></span>
												</label>
												<label for="cartbounty-order-recovery" class="cartbounty-unavailable"><?php esc_html_e('Enable unfinished order recovery', 'woo-save-abandoned-carts'); ?></label>
												<p class='cartbounty-additional-information'>
													<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'unfinished_orders' ); ?></i>
												</p>
											</div>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e( 'Consent', 'woo-save-abandoned-carts' ); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e( 'Settings related to the collection of visitor consent for phone and email in compliance with data protection laws.', 'woo-save-abandoned-carts' ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div id="cartbounty-consent-settings" class="cartbounty-select-multiple<?php if( $email_consent ){ echo ' cartbounty-checked-parent'; }?>">
											<div class="cartbounty-settings-group cartbounty-toggle">
												<label for="cartbounty-email-consent" class="cartbounty-switch">
													<input id="cartbounty-email-consent" class="cartbounty-checkbox" type="checkbox" name="cartbounty_main_settings[email_consent]" value="1" data-type="email" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $email_consent, false ); ?> autocomplete="off" />
													<span class="cartbounty-slider round"></span>
												</label>
												<label for="cartbounty-email-consent"><?php esc_html_e( 'Enable email consent', 'woo-save-abandoned-carts' ); ?></label>
											</div>
											<div class="cartbounty-settings-group cartbounty-toggle">
												<label for="cartbounty-phone-consent" class="cartbounty-switch cartbounty-unavailable">
													<input id="cartbounty-phone-consent" class="cartbounty-checkbox" type="checkbox" disabled />
													<span class="cartbounty-slider round"></span>
												</label>
												<label for="cartbounty-phone-consent" class="cartbounty-unavailable"><?php esc_html_e( 'Enable phone number consent', 'woo-save-abandoned-carts' ); ?></label>
												<p class='cartbounty-additional-information'>
													<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'phone_consent' ); ?></i>
												</p>
											</div>
											<div class="cartbounty-toggle-content">
												<div class="cartbounty-settings-group cartbounty-hidden">
													<label for="cartbounty-checkout-consent"><?php esc_html_e( 'Checkout consent label', 'woo-save-abandoned-carts' ); ?></label>
													<div class="cartbounty-content-creation cartbounty-flex">
														<input id="cartbounty-checkout-consent" class="cartbounty-text" type="text" name="cartbounty_main_settings[checkout_consent]" value="<?php echo esc_attr( $checkout_consent ); ?>" placeholder="<?php echo esc_attr( $this->get_defaults( 'checkout_consent' ) ); ?>" /><?php $this->add_emojis(); ?>
													</div>
												</div>
												<div class="cartbounty-settings-group cartbounty-hidden">
													<label for="cartbounty-tools-consent"><?php esc_html_e( 'Tools consent notice', 'woo-save-abandoned-carts' ); ?></label>
													<div class="cartbounty-content-creation cartbounty-flex">
														<input id="cartbounty-tools-consent" class="cartbounty-text" type="text" name="cartbounty_main_settings[tools_consent]" value="<?php echo esc_attr( $tools_consent ); ?>" placeholder="<?php echo esc_attr( $this->get_defaults( 'tools_consent' ) ); ?>" /><?php $this->add_emojis(); ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e( 'Magic login', 'woo-save-abandoned-carts' ); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e( 'Automatically sign in registered customers when they open their recovery link. Make sure the Cart, Checkout and My account pages bypass all caches (CDN and caching plugins).', 'woo-save-abandoned-carts' ); ?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-magic-login" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty-magic-login" class="cartbounty-checkbox" type="checkbox" disabled />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-magic-login" class="cartbounty-unavailable"><?php esc_html_e( 'Enable Magic login', 'woo-save-abandoned-carts' ); ?></label>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'magic_login' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<div class="cartbounty-row">
									<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
										<h4><?php esc_html_e('Miscellaneous', 'woo-save-abandoned-carts'); ?></h4>
										<p class="cartbounty-titles-column-description">
											<?php esc_html_e('Other settings that may be useful.', 'woo-save-abandoned-carts');?>
										</p>
									</div>
									<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
										<div class="cartbounty-settings-group cartbounty-toggle<?php if($lift_email){ echo ' cartbounty-checked'; }?>">
											<label for="cartbounty-lift-email" class="cartbounty-switch cartbounty-control-visibility">
												<input id="cartbounty-lift-email" class="cartbounty-checkbox" type="checkbox" name="cartbounty_main_settings[lift_email]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $lift_email, false ); ?> autocomplete="off" />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-lift-email" class="cartbounty-control-visibility"><?php esc_html_e('Lift email field', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('You could increase the chances of saving recoverable abandoned carts by moving the email field to the top of your checkout form.', 'woo-save-abandoned-carts');
												echo " <i class='cartbounty-hidden'>". esc_html__('Please test the checkout after enabling this, as sometimes it can cause issues or not raise the field if you have a custom checkout.', 'woo-save-abandoned-carts') ."</i>";
												 ?>
											</p>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-lift-phone" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty-lift-phone" class="cartbounty-checkbox" type="checkbox" disabled />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-lift-phone" class="cartbounty-unavailable"><?php echo __('Lift phone field', 'woo-save-abandoned-carts'); ?></label>
											<p class='cartbounty-additional-information'>
												<?php esc_html_e('You could increase the chances of saving recoverable abandoned carts by moving the phone field to the top of your checkout form.', 'woo-save-abandoned-carts');
												 ?>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><br/><?php echo $this->display_unavailable_notice( 'lift_phone' ); ?></i>
											</p>
										</div>
										<div class="cartbounty-settings-group cartbounty-toggle">
											<label for="cartbounty-international-phone" class="cartbounty-switch cartbounty-unavailable">
												<input id="cartbounty-international-phone" class="cartbounty-checkbox" type="checkbox" disabled />
												<span class="cartbounty-slider round"></span>
											</label>
											<label for="cartbounty-international-phone" class="cartbounty-unavailable"><?php esc_html_e( 'Enable easy international phone input', 'woo-save-abandoned-carts' ); ?></label>
											<p class='cartbounty-additional-information'>
												<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'easy_phone_input' ); ?></i>
											</p>
										</div>
									</div>
								</div>
								<div class='cartbounty-button-row'>
									<?php
									settings_fields( 'cartbounty-settings' );
									do_settings_sections( 'cartbounty-settings' );
									if( $this->user_is_admin() ){
										echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". esc_html__('Save settings', 'woo-save-abandoned-carts') ."</button>";
									}?>
								</div>
							</form>
						</div>
					</div>
				<?php endif;
			}?>
		</div>
	<?php
	}

	/**
	 * Method creates tabs on plugin page
	 *
	 * @since    3.0
	 * @param    $current    Currently open tab - string
	 */
	function create_admin_tabs( $current = 'dashboard' ){
		$tabs = array(
			'dashboard' => esc_html__( 'Dashboard', 'woo-save-abandoned-carts' ),
			'carts' 	=> esc_html__( 'Abandoned carts', 'woo-save-abandoned-carts' ),
			'recovery' 	=> esc_html__( 'Recovery', 'woo-save-abandoned-carts' ),
			'tools' 	=> esc_html__( 'Tools', 'woo-save-abandoned-carts' ),
			'settings' 	=> esc_html__( 'Settings', 'woo-save-abandoned-carts' )
		);
		echo '<h2 class="nav-tab-wrapper">';
		
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : ''; //if the tab is open, an additional class, nav-tab-active, is added
			echo "<a class='cartbounty-tab nav-tab$class' href='". esc_url( "?page=". esc_html( CARTBOUNTY ) ."&tab=$tab" ) ."'>". $this->get_icon( $tab, $current, false, false ) ."<span class='cartbounty-tab-name'>". esc_html( $name ) ."</span></a>";
		}
		echo '</h2>';
	}

	/**
	 * Method returns icons as SVG code
	 *
	 * @since    6.0
	 * @return 	 String
	 * @param    $icon 		Icon to get - string
	 * @param    $current 	Current active tab - string
	 * @param    $section 	Whether the icon is located in sections - boolean
	 * @param    $grid 		Whether the icon is located section items grid - boolean
	 */
	public function get_icon( $icon, $current, $section, $grid ){

		$color = '#555'; //Default icon color
		$svg = '';
		
		if( $current == $icon ){ //If the icon is active in tabs
			$color = '#000';
		}

		if( $grid ){ //If the icon is in the item grid
			$color = '#000';
		}

		if( $current == $icon && $section ){ //If the icon is active in sections
			$color = '#976dfb';
		}

		if( $icon == 'dashboard' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92.73 65"><path d="M61.2,2.41C29.21-7.79,0,15.85,0,46.45,0,50.63,1.11,65,4.71,65H88.56c2.82,0,4.17-14.37,4.17-18.55C92.73,26.52,80.21,8.43,61.2,2.41Zm-45.58,50a5.58,5.58,0,1,1,5.57-5.58A5.58,5.58,0,0,1,15.62,52.38Zm8.46-22.9a5.58,5.58,0,1,1,5.57-5.58A5.57,5.57,0,0,1,24.08,29.48ZM46.37,9.12a5.57,5.57,0,1,1-5.58,5.57A5.57,5.57,0,0,1,46.37,9.12Zm4.69,46.15a5.57,5.57,0,0,1-9-6.55l3-4.09c1.81-2.48,23.85-28.58,26.34-26.77S55.83,48.69,54,51.18Zm26.05-3.59a5.57,5.57,0,1,1,5.58-5.57A5.57,5.57,0,0,1,77.11,51.68Z"/></svg>';
		}

		elseif( $icon == 'carts' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26.34 29.48"><path d="M7.65,24c-2.43,0-3.54-1.51-3.54-2.91V3.44C3.77,3.34,3,3.15,2.48,3L.9,2.59A1.28,1.28,0,0,1,0,1.15,1.32,1.32,0,0,1,1.34,0a1.52,1.52,0,0,1,.42.06l.68.2c1.38.41,2.89.85,3.25,1A1.72,1.72,0,0,1,6.79,2.8V5.16L24.67,7.53a1.75,1.75,0,0,1,1.67,2v6.1a3.45,3.45,0,0,1-3.59,3.62h-16v1.68c0,.14,0,.47,1.07.47H21.13a1.32,1.32,0,0,1,1.29,1.38,1.35,1.35,0,0,1-.25.79,1.18,1.18,0,0,1-1,.5Zm-.86-7.5,15.76,0c.41,0,1.11,0,1.11-1.45V10c-3-.41-13.49-1.69-16.87-2.11Z"/><path d="M21.78,29.48a4,4,0,1,1,4-4A4,4,0,0,1,21.78,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.35,1.35,0,0,0,21.78,24.11ZM10.14,29.48a4,4,0,1,1,4-4A4,4,0,0,1,10.14,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.34,1.34,0,0,0,10.14,24.11Z"/><path d="M18.61,18.91a1.34,1.34,0,0,1-1.34-1.34v-9a1.34,1.34,0,1,1,2.67,0v9A1.34,1.34,0,0,1,18.61,18.91Z"/><path d="M12.05,18.87a1.32,1.32,0,0,1-1.34-1.29v-10a1.34,1.34,0,0,1,2.68,0v10A1.32,1.32,0,0,1,12.05,18.87Z"/></svg>';
		}

		elseif( $icon == 'recovery' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 66.07 49.48"><path d="M28.91,26.67A7.62,7.62,0,0,0,33,28a7,7,0,0,0,4.16-1.37c.77-.46,23.19-17.66,26.05-20s1.06-4.9,1.06-4.9S63.51,0,60.64,0H6.08c-3.83,0-4.5,2-4.5,2S0,4.49,3.28,7Z"/><path d="M40.84,32.14A13.26,13.26,0,0,1,33,34.9a13,13,0,0,1-7.77-2.76C24.33,31.55,1.11,14.49,0,13.25V43a6.52,6.52,0,0,0,6.5,6.5H59.57a6.51,6.51,0,0,0,6.5-6.5V13.25C65,14.46,41.74,31.55,40.84,32.14Z"/></svg>';
		}

		elseif( $icon == 'settings' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 69.95 69.95"><path d="M64,34.83a20.36,20.36,0,0,0,5.2-20A2.49,2.49,0,0,0,65,13.79L56.61,22.2a6.25,6.25,0,0,1-8.85,0h0a6.26,6.26,0,0,1,0-8.86l8.4-8.41a2.49,2.49,0,0,0-1-4.16A20.4,20.4,0,0,0,30.26,27a2.16,2.16,0,0,1-.54,2.22L2.27,56.71a7.76,7.76,0,0,0,0,11h0a7.77,7.77,0,0,0,11,0L40.7,40.23a2.18,2.18,0,0,1,2.22-.54A20.38,20.38,0,0,0,64,34.83Z"/></svg>';
		}

		elseif( $icon == 'exit_intent' || $icon == 'tools' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.5 70"><path d="M29,7.23A7.23,7.23,0,1,1,21.75,0,7.23,7.23,0,0,1,29,7.23Z"/><path d="M17.32,70a5.73,5.73,0,0,1-4.78-2.6,4.85,4.85,0,0,1-.18-4.84q1-2.12,2-4.25c1.33-2.8,2.71-5.68,4.14-8.5,1.33-2.6,5-5.49,11.29-8.81-2.17-4.18-4.25-8-6.35-11.61a21.16,21.16,0,0,1-5.12.66C11.6,30.05,5.59,26.63,1,20.18a4.58,4.58,0,0,1-.48-4.86,5.76,5.76,0,0,1,5.06-3,5.28,5.28,0,0,1,4.39,2.29c2.32,3.26,5.1,4.92,8.26,4.92A13.46,13.46,0,0,0,25,17.43c.18-.12.63-.36,1.12-.64l.31-.17,1.36-.78a23.44,23.44,0,0,1,12-3.55c6.76,0,12.77,3.42,17.39,9.89A4.56,4.56,0,0,1,57.58,27,5.76,5.76,0,0,1,52.52,30a5.26,5.26,0,0,1-4.38-2.28c-2.33-3.26-5.11-4.91-8.27-4.91a10.63,10.63,0,0,0-1.66.14c2.44,4.4,6.53,12.22,7.08,13.58,2.23,4.07,4.78,7.82,8.25,7.82A7,7,0,0,0,57,43.23a5.68,5.68,0,0,1,2.85-.81,5.85,5.85,0,0,1,5.41,4.43A5.27,5.27,0,0,1,62.74,53a18,18,0,0,1-9.08,2.68c-5,0-9.91-2.61-14.08-7.55-2.93,1.44-8.65,4.38-11.3,6.65-.53.87-4.4,8.16-6.4,12.29A5,5,0,0,1,17.32,70Z"/></svg>';
		}

		elseif( $icon == 'early_capture' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38.87 70"><path d="M38.53,32.71,23,67.71A3.89,3.89,0,0,1,19.43,70a5.56,5.56,0,0,1-.81-.08,3.87,3.87,0,0,1-3.07-3.81V42.78H3.88A3.89,3.89,0,0,1,.34,37.3l15.55-35A3.88,3.88,0,0,1,23.32,3.9V27.23H35a3.9,3.9,0,0,1,3.54,5.48Zm0,0"/></svg>';
		}

		elseif( $icon == 'tab_notification' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 67.5 70"><path d="M55.38,41V28.11A19.19,19.19,0,0,0,55.21,25l0-.27C54.12,16.29,47.66,9.48,38.72,7.34l-1.29-.29V5.3a3.87,3.87,0,1,0-7.73,0V7.06A21.9,21.9,0,0,0,11.75,27.94c-.07,2.61-.07,8.9-.06,13a9.82,9.82,0,0,0-6.33,9.33c0,5.44,4.16,9.86,9.29,9.86H33.32v.48a9.38,9.38,0,0,0,18.75,0v-.48h.36c5.12,0,9.29-4.42,9.29-9.86A9.84,9.84,0,0,0,55.38,41ZM19.44,28.67a15.16,15.16,0,0,1,.29-3A14.06,14.06,0,0,1,33.54,14.44a14.53,14.53,0,0,1,4.77.81,14,14,0,0,1,9.34,12.46c0,.45,0,.53,0,.56a3.91,3.91,0,0,1,0,.51c0,1,0,7.44,0,11.66H19.42C19.42,36.37,19.42,30.92,19.44,28.67ZM42.7,62.92a2.47,2.47,0,0,1-2.47-2.46,3,3,0,0,1,0-.31h4.87a3,3,0,0,1,0,.31A2.46,2.46,0,0,1,42.7,62.92Zm9.62-10.16H14.75c-.92,0-1.67-1.11-1.67-2.47s.75-2.46,1.67-2.46H52.32c.92,0,1.67,1.1,1.67,2.46S53.24,52.76,52.32,52.76Z"/><path d="M11.31.8A34.14,34.14,0,0,0,.24,15.73a3.82,3.82,0,0,0,3.61,5.11h0A3.78,3.78,0,0,0,7.4,18.43,26.5,26.5,0,0,1,16,6.87a3.81,3.81,0,0,0,1.47-3v0A3.83,3.83,0,0,0,11.31.8Z"/><path d="M56.19.8A34.14,34.14,0,0,1,67.26,15.73a3.82,3.82,0,0,1-3.61,5.11h0a3.8,3.8,0,0,1-3.56-2.41A26.4,26.4,0,0,0,51.5,6.87a3.78,3.78,0,0,1-1.47-3v0A3.83,3.83,0,0,1,56.19.8Z"/></svg>';
		}

		elseif( $icon == 'activecampaign' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 13.24 20"><path d="M12.52,8.69C12.23,8.45.76,0.44,0.24,0.12L0.08,0V2A1.32,1.32,0,0,0,.8,3.14l0.08,0L10.7,10c-1.09.76-9.38,6.52-9.9,6.84a1.16,1.16,0,0,0-.68,1.25V20s12.19-8.49,12.43-8.69h0a1.52,1.52,0,0,0,.68-1.25V9.82A1.4,1.4,0,0,0,12.52,8.69Z"/><path d="M5.35,10.91a1.61,1.61,0,0,0,1-.36L7.08,10,7.2,9.94,7.08,9.86s-5.39-3.74-6-4.1A0.7,0.7,0,0,0,.36,5.63,0.71,0.71,0,0,0,0,6.28V7.53l0,0s3.7,2.58,4.43,3.06A1.63,1.63,0,0,0,5.35,10.91Z"/></svg>';
		}

		elseif( $icon == 'getresponse' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 44.54"><path d="M35,35a29,29,0,0,1-17.22-5.84A28.38,28.38,0,0,1,6.89,11.35c-.07-.47-.15-.94-.21-1.33A3.2,3.2,0,0,1,9.86,6.36h1.48a23.94,23.94,0,0,0,8.4,13.76A24.74,24.74,0,0,0,34.91,25.5C48.16,25.78,61.05,14,69.31,1.15A3,3,0,0,0,67,0H3A3,3,0,0,0,0,3V41.55a3,3,0,0,0,3,3H67a3,3,0,0,0,3-3V8.5C59.14,27.59,46.65,35,35,35Z"/></svg>';
		}

		elseif( $icon == 'mailchimp' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 65.96 70"><path d="M49.61,33.08a5.41,5.41,0,0,1,1.45,0,4.92,4.92,0,0,0,.07-2.75c-.34-1.66-.82-2.67-1.79-2.52s-1,1.37-.66,3a5.7,5.7,0,0,0,.93,2.24Z"/><path d="M41.26,34.4c.69.3,1.12.5,1.29.33s.07-.32-.09-.59a4,4,0,0,0-1.8-1.45,4.88,4.88,0,0,0-4.78.57c-.47.34-.91.81-.84,1.1,0,.09.09.16.25.19a27.75,27.75,0,0,1,3.27-.73,5.65,5.65,0,0,1,2.7.58Z"/><path d="M39.85,35.2a3.23,3.23,0,0,0-1.72.72,1.1,1.1,0,0,0-.45.69.19.19,0,0,0,.07.16.2.2,0,0,0,.15.06,2.81,2.81,0,0,0,.67-.18,5.74,5.74,0,0,1,2.92-.31c.45,0,.67.08.77-.07a.26.26,0,0,0,0-.29,2.62,2.62,0,0,0-2.38-.78Z"/><path d="M46.79,38.13a1.13,1.13,0,0,0,1.52-.26c.22-.45-.1-1.06-.72-1.37a1.13,1.13,0,0,0-1.52.27,1.11,1.11,0,0,0,.72,1.36Z"/><path d="M50.75,34.67c-.5,0-.92.54-.93,1.23s.39,1.25.89,1.26.91-.55.93-1.23-.39-1.25-.89-1.26Z"/><path d="M17.14,47c-.12-.15-.33-.1-.53-.06a2.11,2.11,0,0,1-.46.07,1,1,0,0,1-.86-.44,1.59,1.59,0,0,1,0-1.47,2,2,0,0,1,.12-.26c.4-.9,1.07-2.41.31-3.85a3.38,3.38,0,0,0-2.6-1.89,3.34,3.34,0,0,0-2.87,1,4.14,4.14,0,0,0-1.07,3.47c.08.22.2.28.29.29s.47-.11.64-.58a.86.86,0,0,0,0-.15,5,5,0,0,1,.46-1.08,2,2,0,0,1,1.26-.87,2,2,0,0,1,1.53.29,2,2,0,0,1,.74,2.36A5.58,5.58,0,0,0,13.8,46a2.11,2.11,0,0,0,1.87,2.16,1.59,1.59,0,0,0,1.5-.75.31.31,0,0,0,0-.37Z"/><path d="M24.76,19.66a31,31,0,0,1,8.71-7.12.11.11,0,0,1,.15.15,8.56,8.56,0,0,0-.81,2,.12.12,0,0,0,.18.12,17,17,0,0,1,7.65-2.7.13.13,0,0,1,.08.22,6.6,6.6,0,0,0-1.21,1.21.12.12,0,0,0,.1.18A15.09,15.09,0,0,1,46,15.38c.12.06,0,.3-.1.27a25.86,25.86,0,0,0-11.58,0,26.57,26.57,0,0,0-9.41,4.15.11.11,0,0,1-.15-.17Zm13,29.25Zm10.78,1.27a.21.21,0,0,0,.12-.2.2.2,0,0,0-.22-.18,24.86,24.86,0,0,1-10.84-1.1c.57-1.87,2.1-1.19,4.4-1a32.17,32.17,0,0,0,10.64-1.15,24.28,24.28,0,0,0,8-3.95,16,16,0,0,1,1.11,3.78,1.86,1.86,0,0,1,1.17.22c.5.31.87.95.62,2.61a14.39,14.39,0,0,1-4,7.93,16.67,16.67,0,0,1-4.86,3.63,20,20,0,0,1-3.17,1.34c-8.35,2.73-16.9-.27-19.65-6.71a9.46,9.46,0,0,1-.55-1.52,13.36,13.36,0,0,1,2.93-12.54h0a1.09,1.09,0,0,0,.39-.75,1.27,1.27,0,0,0-.3-.7c-1.09-1.59-4.86-4.28-4.11-9.49C30.68,26.64,34,24,37,24.16l.77,0c1.33.08,2.48.25,3.57.3a7.19,7.19,0,0,0,5.41-1.81,4.13,4.13,0,0,1,2.07-1.17,2.71,2.71,0,0,1,.79-.08,2.68,2.68,0,0,1,1.33.43c1.56,1,1.78,3.55,1.86,5.38,0,1.05.17,3.58.21,4.31.1,1.67.54,1.9,1.42,2.19.5.17,1,.29,1.65.48a9.31,9.31,0,0,1,4,1.92,2.56,2.56,0,0,1,.74,1.45c.24,1.77-1.38,4-5.67,5.95a28.69,28.69,0,0,1-14.3,2.29l-1.37-.15c-3.15-.43-4.94,3.63-3,6.42,1.21,1.79,4.52,3,7.84,3,7.59,0,13.43-3.24,15.6-6l.17-.24c.11-.16,0-.25-.11-.16-1.77,1.21-9.66,6-18.08,4.58a11.38,11.38,0,0,1-2-.53c-.75-.29-2.3-1-2.49-2.6,6.8,2.1,11.09.11,11.09.11ZM11.18,34a9.06,9.06,0,0,0-5.72,3.65A15.45,15.45,0,0,1,3,35.33C1,31.46,5.24,24,8.22,19.7c7.35-10.49,18.86-18.43,24.19-17,.86.25,3.73,3.58,3.73,3.58a74.88,74.88,0,0,0-10.26,7.07A46.63,46.63,0,0,0,11.18,34Zm4,17.73a5,5,0,0,1-1.09.08c-3.56-.09-7.41-3.3-7.79-7.1-.42-4.2,1.72-7.43,5.52-8.2a6.67,6.67,0,0,1,1.6-.11c2.13.11,5.27,1.75,6,6.39.64,4.11-.37,8.29-4.22,8.94Zm48.22-7.43c0-.11-.23-.84-.51-1.71a13.28,13.28,0,0,0-.55-1.49,5.47,5.47,0,0,0,1-3.94,5,5,0,0,0-1.45-2.81,11.64,11.64,0,0,0-5.11-2.53l-1.3-.36c0-.06-.07-3.07-.13-4.37a15,15,0,0,0-.57-3.84,10.35,10.35,0,0,0-2.66-4.74c3.24-3.36,5.27-7.06,5.26-10.24,0-6.11-7.51-8-16.76-4.13l-2,.83L35,1.47c-10.54-9.2-43.51,27.44-33,36.34l2.31,1.95A11.32,11.32,0,0,0,3.71,45,10.3,10.3,0,0,0,7.27,51.6a10.86,10.86,0,0,0,7,2.81C18.35,63.86,27.72,69.66,38.71,70c11.78.35,21.68-5.18,25.82-15.11A20.84,20.84,0,0,0,66,48.26c0-2.79-1.58-3.94-2.58-3.94Z"/></svg>';
		}

		elseif( $icon == 'wordpress' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 70"><path d="M35,0A35,35,0,1,0,70,35,35,35,0,0,0,35,0M3.53,35A31.33,31.33,0,0,1,6.26,22.19l15,41.13A31.48,31.48,0,0,1,3.53,35M35,66.47a31.42,31.42,0,0,1-8.89-1.28l9.44-27.44,9.67,26.5a3.45,3.45,0,0,0,.23.43A31.21,31.21,0,0,1,35,66.47m4.34-46.22c1.89-.1,3.6-.3,3.6-.3a1.3,1.3,0,0,0-.2-2.6s-5.1.4-8.39.4c-3.09,0-8.29-.4-8.29-.4a1.3,1.3,0,0,0-.2,2.6s1.61.2,3.3.3l4.91,13.43L27.18,54.33,15.72,20.25c1.9-.1,3.6-.3,3.6-.3a1.3,1.3,0,0,0-.2-2.6s-5.1.4-8.39.4c-.59,0-1.28,0-2,0a31.46,31.46,0,0,1,47.54-5.92l-.41,0a5.44,5.44,0,0,0-5.28,5.58c0,2.6,1.49,4.79,3.09,7.38a16.66,16.66,0,0,1,2.59,8.68c0,2.69-1,5.82-2.39,10.17L50.71,54.07Zm23.27-.35A31.46,31.46,0,0,1,50.82,62.2l9.61-27.79a29.62,29.62,0,0,0,2.39-11.27,23.42,23.42,0,0,0-.21-3.24"/></svg>';
		}

		elseif( $icon == 'bulkgate' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 53.8"><g id="g3418"><path id="path3420" d="M35,12.83c11.44,7.83,19.44,17.88,22.73,41H70C68.8,30.78,55,11,35,0,15,11,1.29,30.78,0,53.8H12.29S34,54.61,48.41,32c0,0-14.44,7.68-22.94,4.49-8-3-4.15-10-3.71-10.72A48,48,0,0,1,35,12.83"/></g></svg>';
		
		}elseif( $icon == 'whatsapp' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" width="360" height="362" viewBox="0 0 360 362" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M307.546 52.5655C273.709 18.685 228.706 0.0171895 180.756 0C81.951 0 1.53846 80.404 1.50408 179.235C1.48689 210.829 9.74646 241.667 25.4319 268.844L0 361.736L95.0236 336.811C121.203 351.096 150.683 358.616 180.679 358.625H180.756C279.544 358.625 359.966 278.212 360 179.381C360.017 131.483 341.392 86.4547 307.546 52.5741V52.5655ZM180.756 328.354H180.696C153.966 328.346 127.744 321.16 104.865 307.589L99.4242 304.358L43.034 319.149L58.0834 264.168L54.5423 258.53C39.6304 234.809 31.749 207.391 31.7662 179.244C31.8006 97.1036 98.6334 30.2707 180.817 30.2707C220.61 30.2879 258.015 45.8015 286.145 73.9665C314.276 102.123 329.755 139.562 329.738 179.364C329.703 261.513 262.871 328.346 180.756 328.346V328.354ZM262.475 216.777C257.997 214.534 235.978 203.704 231.869 202.209C227.761 200.713 224.779 199.966 221.796 204.452C218.814 208.939 210.228 219.029 207.615 222.011C205.002 225.002 202.389 225.372 197.911 223.128C193.434 220.885 179.003 216.158 161.891 200.902C148.578 189.024 139.587 174.362 136.975 169.875C134.362 165.389 136.7 162.965 138.934 160.739C140.945 158.728 143.412 155.505 145.655 152.892C147.899 150.279 148.638 148.406 150.133 145.423C151.629 142.432 150.881 139.82 149.764 137.576C148.646 135.333 139.691 113.287 135.952 104.323C132.316 95.5909 128.621 96.777 125.879 96.6309C123.266 96.5019 120.284 96.4762 117.293 96.4762C114.302 96.4762 109.454 97.5935 105.346 102.08C101.238 106.566 89.6691 117.404 89.6691 139.441C89.6691 161.478 105.716 182.785 107.959 185.776C110.202 188.767 139.544 234.001 184.469 253.408C195.153 258.023 203.498 260.782 210.004 262.845C220.731 266.257 230.494 265.776 238.212 264.624C246.816 263.335 264.71 253.786 268.44 243.326C272.17 232.866 272.17 223.893 271.053 222.028C269.936 220.163 266.945 219.037 262.467 216.794L262.475 216.777Z"/></svg>';

		}elseif( $icon == 'push_notification' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 67"><path d="M50.84,67a6.26,6.26,0,0,1-4.27-1.68L34,53.58H12.45A12.4,12.4,0,0,1,0,41.26V12.32A12.4,12.4,0,0,1,12.45,0h45.1A12.4,12.4,0,0,1,70,12.32V41.26A12.4,12.4,0,0,1,57.55,53.58h-.43l-.07,7.3A6.22,6.22,0,0,1,50.84,67ZM12.45,6.53a5.87,5.87,0,0,0-5.92,5.79V41.26a5.87,5.87,0,0,0,5.92,5.79H36.62l13.91,13,.12-13h6.9a5.87,5.87,0,0,0,5.92-5.79V12.32a5.87,5.87,0,0,0-5.92-5.79Z"/><rect x="14.47" y="16.99" width="41.06" height="6.53" rx="3.27"/><rect x="14.47" y="30.01" width="31.06" height="6.53" rx="3.27"/></svg>';
		}

		elseif( $icon == 'webhook' ){
			$svg = '<svg style="fill: '. esc_attr( $color ) .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72.88 68"><path d="M17.52,68a17.68,17.68,0,0,1-1.78-.09A17.49,17.49,0,0,1,.09,52.27a17.3,17.3,0,0,1,2.8-11.41,3.15,3.15,0,0,1,2.63-1.42,3.08,3.08,0,0,1,2.35,1.07,3.24,3.24,0,0,1,.2,3.82,11.28,11.28,0,0,0,9.45,17.44,12.63,12.63,0,0,0,4.56-.9,11,11,0,0,0,6.72-10,3.59,3.59,0,0,1,3.52-3.65H51l.3-.38a5.17,5.17,0,1,1,0,6.4l-.3-.38H37.47a3.24,3.24,0,0,0-3.1,2.37A17.58,17.58,0,0,1,17.52,68Z"/><path d="M55.39,67.57c-.36,0-.72,0-1.08,0a3.12,3.12,0,0,1-2.79-4.14,3.23,3.23,0,0,1,3-2.08h.18l.64,0A11.26,11.26,0,0,0,66.6,50.87a11.52,11.52,0,0,0-4.53-9.95,10.39,10.39,0,0,0-6.51-2.23,11.85,11.85,0,0,0-5.49,1.38l-.09,0a3.75,3.75,0,0,1-1.79.46,3.44,3.44,0,0,1-3-1.73L35.85,22.68l-.48-.07a5.17,5.17,0,0,1,.74-10.28,5.17,5.17,0,0,1,4.81,7.09l-.18.45,6.73,11.7a3.23,3.23,0,0,0,2.8,1.61,3.58,3.58,0,0,0,.8-.1,17.51,17.51,0,1,1,4.32,34.49Z"/><path d="M17.51,55.6a5.17,5.17,0,0,1-.73-10.29l.48-.07L24,33.57a3.25,3.25,0,0,0-.49-3.88A17.5,17.5,0,0,1,36.18,0a17.1,17.1,0,0,1,7.14,1.54,17.34,17.34,0,0,1,8.47,8.15,3.13,3.13,0,0,1,0,2.8,3.1,3.1,0,0,1-2.21,1.68,3.31,3.31,0,0,1-.55.05,3.24,3.24,0,0,1-2.84-1.8,11.32,11.32,0,0,0-21.34,3.83,11,11,0,0,0,5.26,10.82,3.58,3.58,0,0,1,1.39,4.86L22.14,48.07l.18.45A5.13,5.13,0,0,1,22,53a5.19,5.19,0,0,1-4.48,2.58Z"/></svg>';
		}

		return "<span class='cartbounty-icon-container cartbounty-icon-$icon'><img src='data:image/svg+xml;base64," . esc_attr( base64_encode( $svg ) ) . "' alt='" . esc_attr( $icon ) . "' /></span>";
    }

	/**
	 * Method returns current open tab. Default tab - carts
	 *
	 * @since    7.0.4
	 */
	function get_open_tab(){
		$tab = 'dashboard';

		if( isset( $_GET['tab'] ) ){
			$tab = $_GET['tab'];
		}
		return $tab;
	}

	/**
	 * Method returns current open section. Default - empty section
	 *
	 * @since    7.0.4
	 */
	function get_open_section(){
		$section = '';

		if( isset( $_GET['section'] ) ){
			$section = $_GET['section'];
		}
		return $section;
	}

	/**
     * Method displays available sections
     *
     * @since    6.0
     * @return   string
     * @param 	 string    $active_section    	Currently open section item
     * @param    string    $tab    		  		Tab name
     */
    function display_sections( $active_section, $tab ){
    	$sections = $this->get_sections( $tab );
    	$all_title = esc_html__('All integrations', 'woo-save-abandoned-carts');

    	if($tab == 'tools'){
    		$all_title = esc_html__('All tools', 'woo-save-abandoned-carts');
    	}

    	//Generating sections for large screens
    	echo '<ul id="cartbounty-sections" class="cartbounty-hidden-xs">';
    	foreach( $sections as $key => $section ){
    		$class = ( $key == $active_section ) ? 'current' : '';
    		$availability_class = (!$section['availability']) ? 'cartbounty-unavailable' : '';
    		$link = "?page=". CARTBOUNTY ."&tab=$tab&section=$key";
    		if(!$section['availability']){
    			$link = '#';
    		}

	    	echo "<li class='". esc_attr( $availability_class ) ."'><a href='". esc_url( $link ) ."' title='". esc_attr( $section['name'] ). "' class='". esc_attr( $class ) ."'>". $this->get_icon( $key, $active_section, true, false) ." <span class='cartbounty-section-name'>". esc_html( $section['name'] ). "</span>". $this->get_connection( $section['connected'], $text = false, false ) ."</a></li>";
    	}
    	echo '</ul>';

    	//Generating sections for small screens
    	echo '<select id="cartbounty-mobile-sections" class="cartbounty-select" onchange="window.location.href=this.value" style="display: none;">';
    		echo "<option value='". esc_url( "?page=". esc_html( CARTBOUNTY ) ."&tab=$tab" ) ."'>". esc_html( $all_title ) ."</option>";
	    	foreach( $sections as $key => $section ){
	    		$link = "?page=". CARTBOUNTY ."&tab=$tab&section=$key";
	    		if($section['availability']){
	    			echo "<option value='". esc_url( $link ) ."' ". selected( $active_section, $key, false ) .">". esc_html( $section['name'] ). "</option>";
	    		}
	    	}
    	echo '</select>';
    }

    /**
	 * Method displays a dropdown field of available time intervals for a given option name
	 *
	 * @since    7.1.6
	 * @return   HTML
	 * @param    string     $option				Name of the option field used for storing time in database
     * @param    integer    $automation			Automation number
	 */
	public function display_time_intervals( $option, $automation = NULL ) {
		$data = $this->get_interval_data( $option, $automation );
		$storage_array = '[interval]';
		$step_nr = '';
		$step_array = '';

		if( $option == 'cartbounty_automation_steps' ){
			$step_nr = '_' . $automation;
			$step_array = '[' . $automation . ']';
		}

		if( $option == 'cartbounty_main_settings[notification_frequency]' ){
			$storage_array = '';
		}


		echo '<label for="' . $option . $step_nr . '">' . $data['name'] . '</label>';
		echo '<select id="' . $option . $step_nr . '" class="cartbounty-select" name="' . $option . $step_array . $storage_array .'" autocomplete="off" ' . $this->disable_field() . '>';
		foreach( $this->prepare_time_intervals( $data['interval'], $data['zero_name'], $option ) as $key => $milliseconds ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $data['selected'], $key, false ) . '>' . esc_html( $milliseconds ) . '</option>';
		}
		echo '</select>';
	}

	/**
     * Prepare time intervals from milliseconds
     *
     * @since    7.0.5
     * @return   array
     * @param 	 array    $milliseconds    	Array of milliseconds
     * @param 	 string   $zero_value    	Content for zero value
     * @param    string   $option			Name of the option field used for storing time in database
     */
	function prepare_time_intervals( $milliseconds = array(), $zero_value = '', $option = '' ){
		$intervals = array();
		$alternative_options = array( 'cartbounty_main_settings[notification_frequency]' );

		if( is_array( $milliseconds ) ){

			foreach( $milliseconds as $milisecond ){
				
				if( $milisecond == 0 ) {
					$intervals[$milisecond] = $zero_value;

				}elseif( $milisecond < 60000 ){ //Generate seconds
					$seconds = $milisecond / 1000;
					$intervals[$milisecond] = sprintf(
						esc_html( _n( '%s second', '%s seconds', esc_html( $seconds ), 'woo-save-abandoned-carts' ) ), esc_html( $seconds )
					);

				}elseif( $milisecond < 3600000 ){ //Generate minutes
					$minutes = $milisecond / 60000;
					$intervals[$milisecond] = sprintf(
						esc_html( _n( '%s minute', '%s minutes', esc_html( $minutes ), 'woo-save-abandoned-carts' ) ), esc_html( $minutes )
					);

					if( in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = sprintf(
							esc_html( _n( 'Every minute', 'Every %s minutes', esc_html( $minutes ), 'woo-save-abandoned-carts' ) ), esc_html( $minutes )
						);
					}

				}elseif( $milisecond < 86400000 ){ //Generate hours
					$hours = $milisecond / 3600000;
					$intervals[$milisecond] = sprintf(
						esc_html( _n( '%s hour', '%s hours', esc_html( $hours ), 'woo-save-abandoned-carts' ) ), esc_html( $hours )
					);

					if( in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = sprintf(
							esc_html( _n( 'Every hour', 'Every %s hours', esc_html( $hours ), 'woo-save-abandoned-carts' ) ), esc_html( $hours )
						);
					}

					if( $hours == 12 && in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = esc_html__( 'Twice daily', 'woo-save-abandoned-carts' );
					}

				}elseif( $milisecond < 604800000 ){ //Generate days
					$days = $milisecond / 86400000;
					$intervals[$milisecond] = sprintf(
						esc_html( _n( '%s day', '%s days', esc_html( $days ), 'woo-save-abandoned-carts' ) ), esc_html( $days )
					);

					if( $days == 1 && in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = esc_html__( 'Once daily', 'woo-save-abandoned-carts' );
					}

					if( $days == 2 && in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = esc_html__( 'Once every 2 days', 'woo-save-abandoned-carts' );
					}

				}elseif( $milisecond < 2419200000 ){ //Generate weeks
					$weeks = $milisecond / 604800000;
					$intervals[$milisecond] = sprintf(
						esc_html( _n( '%s week', '%s weeks', esc_html( $weeks ), 'woo-save-abandoned-carts' ) ), esc_html( $weeks )
					);

					if( $weeks == 1 && in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = esc_html__( 'Once weekly', 'woo-save-abandoned-carts' );
					}

				}else{ //Generate months
					$months = $milisecond / 2419200000;
					$intervals[$milisecond] = sprintf(
						esc_html( _n( '%s month', '%s months', esc_html( $months ), 'woo-save-abandoned-carts' ) ), esc_html( $months )
					);

					if( $months == 1 && in_array( $option, $alternative_options ) ){ //To display alternative frequencies like "Every minute" or "Once a day"
						$intervals[$milisecond] = esc_html__( 'Once monthly', 'woo-save-abandoned-carts' );
					}
				}
			}
		}

		return $intervals;
	}

	/**
	 * Method returns existing time intervals for a given option
	 *
	 * @since    7.1.6
	 * @return   array or string
	 * @param    string     $option					Name of the option field saved in database
     * @param    integer    $automation				Automation number
     * @param    boolean    $just_selected_value	Should only the selected Interval value be returned
	 */
	public function get_interval_data( $option, $automation = false, $just_selected_value = false ){
		$option_value = get_option( $option );

		switch( $option ) {

			case 'cartbounty_main_settings[notification_frequency]':
				$name = esc_html__( 'Check for new abandoned carts', 'woo-save-abandoned-carts' );
				$zero_name = esc_html__( 'Disable notifications', 'woo-save-abandoned-carts' );
				$milliseconds = array( 0, 600000, 1200000, 1800000, 3600000, 7200000, 10800000, 14400000, 18000000, 21600000, 43200000, 86400000, 172800000, 604800000, 2419200000 );
				$selected_interval = 3600000;
				$notification_frequency = $this->get_settings( 'settings', 'notification_frequency' );

				if( isset( $notification_frequency ) ){ //If interval has been set - use it
					$selected_interval = $notification_frequency;
				}

				break;

			case 'cartbounty_automation_steps':

				$name = esc_html__( 'Send email after', 'woo-save-abandoned-carts' );
				$zero_name = '';
				$milliseconds = array( 300000, 600000, 900000, 1200000, 1500000, 1800000, 2400000, 3000000, 3600000, 7200000, 10800000, 14400000, 18000000, 21600000, 25200000, 28800000, 32400000, 36000000, 39600000, 43200000, 64800000, 86400000, 172800000, 259200000, 345600000, 432000000, 518400000, 604800000, 1209600000, 1814400000, 2419200000, 4838400000, 7257600000 );
				$wordpress = $this->wordpress();
				$selected_interval = $wordpress->get_defaults( 'interval', $automation );

				if( isset( $option_value[$automation] ) ){
					$automation_step = $option_value[$automation];
				}

				if( !empty( $automation_step['interval'] ) ){ //If interval has been set - use it
					$selected_interval = $automation_step['interval'];
				}

				break;
		}

		if( $just_selected_value ){ //In case just the selected value is requested
			$prepared_interval_array = $this->prepare_time_intervals( array( $selected_interval ) );

			if( isset( $prepared_interval_array[$selected_interval] ) ){
				return $prepared_interval_array[$selected_interval];
			}

		}else{
			return array(
				'name'  		=> $name,
				'zero_name'		=> $zero_name,
				'interval'		=> $milliseconds,
				'selected'		=> $selected_interval
			);
		}
	}

    /**
	 * Method returns sections
	 *
	 * @since    6.0
	 * @return   array
	 * @param    string    $tab    		  Tab name
	 */
	function get_sections( $tab ){
		$sections = array();

		if($tab == 'recovery'){

			$wordpress = $this->wordpress();

			$sections = array(
				'wordpress'	=> array(
					'name'				=> 'WordPress',
					'connected'			=> $wordpress->automation_enabled() ? true : false,
					'availability'		=> true,
					'faded'				=> false,
					'info_link'			=> '',
					'description'		=> '<p>' . esc_html__("A simple solution for sending abandoned cart reminder emails using WordPress mail server. This recovery option works best if you have a small to medium number of abandoned carts.", 'woo-save-abandoned-carts') . '</p>'
				),
				'activecampaign'	=> array(
					'name'				=> 'ActiveCampaign',
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK,
					'description'		=> '<p>' . esc_html__("ActiveCampaign is an awesome platform that enable you to set up advanced rules for sending abandoned cart recovery emails tailored to customer behavior.", 'woo-save-abandoned-carts') . '</p>'
				),
				'getresponse'	=> array(
					'name'				=> 'GetResponse',
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_GETRESPONSE_TRIAL_LINK,
					'description'		=> '<p>' . esc_html__("GetResponse offers efficient and beautifully designed email marketing platform to recover abandoned carts. It is a professional email marketing system with awesome email design options and beautifully pre-designed email templates.", 'woo-save-abandoned-carts') . '</p>'
				),
				'mailchimp'	=> array(
					'name'				=> 'MailChimp',
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_MAILCHIMP_LINK,
					'description'		=> '<p>' . esc_html__("MailChimp allows to send personalized reminder emails to your customers, either as one-time messages or a series of follow-up emails, such as sending the first email within an hour of cart abandonment, the second one after 24 hours, and so on.", 'woo-save-abandoned-carts') . '</p>'
				),
				'bulkgate'	=> array(
					'name'				=> 'BulkGate',
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_BULKGATE_TRIAL_LINK,
					'description'		=> '<p>' . esc_html__("A perfect channel for sending personalized SMS text messages like abandoned cart reminders.", 'woo-save-abandoned-carts') . '</p><p>' . esc_html__("Recover more sales by sending a personal SMS message along with other abandoned cart reminders.", 'woo-save-abandoned-carts') . '</p>'
				),
				'whatsapp'	=> array(
					'name'				=> 'WhatsApp',
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_WHATSAPP_LINK,
					'description'		=> '<p>' . esc_html__("Reach customers where they are with instant and personalized WhatsApp abandoned cart reminder messages. Increase conversions and create a more personal shopping experience.", 'woo-save-abandoned-carts') . '</p>'
				),
				'push_notification'	=> array(
					'name'				=> esc_html__( 'Push notifications', 'woo-save-abandoned-carts' ),
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_PUSH_NOTIFICATION_LINK,
					'description'		=> '<p>' . esc_html__("Web push notifications offer a low-friction, real-time way to send cart reminders - no email or phone necessary. They can reach users even after they have left your store, increasing the chance of cart recovery.", 'woo-save-abandoned-carts') . '</p>'
				),
				'webhook'	=> array(
					'name'				=> 'Webhook',
					'connected'			=> false,
					'availability'		=> false,
					'faded'				=> true,
					'info_link'			=> CARTBOUNTY_WEBHOOK_LINK,
					'description'		=> '<p>' . sprintf(
						/* translators: %1$s - Link start, %2$s - Link start, %3$s - Link end */
						esc_html__( 'Webhook offers an easy way of sending event-based data about abandoned carts to applications like %1$sMake (former Integromat)%3$s, %2$sPabbly%3$s, Zapier or other. A great way for building powerful automations and advanced marketing processes.', 'woo-save-abandoned-carts' ), '<a href="'. esc_url( CARTBOUNTY_MAKE_LINK ) .'" target="_blank">', '<a href="'. esc_url( CARTBOUNTY_PABBLY_LINK ) .'" target="_blank">', '</a>' ) . '</p>'
				)
			);
		}

		if($tab == 'tools'){
			$sections = array(
				'exit_intent'	=> array(
					'name'				=> esc_html__('Exit Intent', 'woo-save-abandoned-carts'),
					'connected'			=> $this->get_settings( 'exit_intent', 'status' ) ? true : false,
					'availability'		=> true,
					'faded'				=> false,
					'description'		=> '<p>' . esc_html__("Save more recoverable abandoned carts by showcasing a popup message right before your customer tries to leave and offer an option to save his shopping cart by entering his email.", 'woo-save-abandoned-carts') . '</p>'
				),
				'early_capture'	=> array(
					'name'				=> esc_html__('Early capture', 'woo-save-abandoned-carts'),
					'connected'			=> false,
					'availability'		=> true,
					'faded'				=> true,
					'description'		=> '<p>' . esc_html__('Try saving more recoverable abandoned carts by enabling Early capture to collect customer’s email or phone right after the "Add to cart" button is clicked.', 'woo-save-abandoned-carts') . '</p>'
				),
				'tab_notification'	=> array(
					'name'				=> esc_html__('Tab notification', 'woo-save-abandoned-carts'),
					'connected'			=> false,
					'availability'		=> true,
					'faded'				=> true,
					'description'		=> '<p>' . esc_html__('Decrease shopping cart abandonment by grabbing customer attention and returning them to your store after they have switched to a new browser tab with Tab notification.', 'woo-save-abandoned-carts') . '</p>'
				)
			);
		}
		
		return $sections;
	}

	/**
	 * Method returns section contents
	 *
	 * @since    6.0
	 * @return   array
	 * @param    string    $active_section    	Currently open section item
	 */
	function section_contents( $active_section ){
		switch ( $active_section ) {

			case 'wordpress':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
					/* translators: %s - URL link tags */
					esc_html__('A simple solution for sending abandoned cart reminder emails using WordPress mail server. This recovery option works best if you have a small to medium number of abandoned carts.', 'woo-save-abandoned-carts') . esc_html__('If you are looking for something more advanced and powerful, please consider upgrading to %s%s Pro%s.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_section_intro' ) ) .'" target="_blank">', esc_html( CARTBOUNTY_ABBREVIATION ), '</a>' );?>
				</div>

				<form method="post" action="options.php">
					<?php
						global $wpdb;
						settings_fields( 'cartbounty-wordpress-settings' );
						do_settings_sections( 'cartbounty-wordpress-settings' );
						$wordpress = $this->wordpress();
						$settings = $wordpress->get_settings();
						$automation_steps = get_option('cartbounty_automation_steps');
					?>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-full-width cartbounty-col-sm-12 cartbounty-col-md-12 cartbounty-col-lg-12">
							<h4><?php esc_html_e('Automation process', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Configure your abandoned cart reminder emails, how they look, when to send them, include coupons, etc. You can choose to enable just one or all of them creating a 4-step automation process. The countdown of the next step starts right after the previous one is finished.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-full-width cartbounty-col-sm-12 cartbounty-col-md-12 cartbounty-col-lg-12">
							<div class="cartbounty-settings-group">
								<div class="cartbounty-stairway">
									<?php if( !empty( $automation_steps ) ){
										foreach( $automation_steps as $key => $step ){
											$step = (object)$step;
											$enabled = ( isset($step->enabled) ) ? $step->enabled : false;
											$subject = ( isset($step->subject) ) ? $step->subject : '';
											$heading = ( isset($step->heading) ) ? $step->heading : '';
											$content = ( isset($step->content) ) ? $step->content : '';
											$main_color = ( isset($step->main_color) ) ? $step->main_color : false;
											$button_color = ( isset($step->button_color) ) ? $step->button_color : false;
											$text_color = ( isset($step->text_color) ) ? $step->text_color : false;
											$background_color = ( isset($step->background_color) ) ? $step->background_color : false;
											$include_image = ( isset($step->include_image) ) ? $step->include_image : false;
											$main_image = ( isset($step->main_image) ) ? $step->main_image : false;
											$time_interval_name = $this->get_interval_data( 'cartbounty_automation_steps', $key, $just_selected_value = true );
											$preview_email_nonce = wp_create_nonce( 'preview_email' );
											$test_email_nonce = wp_create_nonce( 'test_email' );
											$automation_status = $wordpress->display_automation_status( $enabled ); ?>

											<div class="cartbounty-step<?php esc_attr( $wordpress->check_if_accessible( $key ) ); ?>" data-automation-step="<?php echo esc_attr( $key ); ?>">
												<div class="cartbounty-step-opener">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-xs-12 cartbounty-col-sm-4 cartbounty-col-lg-3">
															<div class="cartbounty-automation-number"><?php echo esc_html( $key ) + 1; ?></div>
															<div class="cartbounty-automation-name">
																<h3><?php echo esc_html( $wordpress->get_defaults( 'name', $key ) ); ?></h3>
																<p><?php echo sprintf(
																	/* translators: %s - Time, e.g. 10 minutes */
																	 esc_html__('Sends after %s', 'woo-save-abandoned-carts'), esc_html( $time_interval_name ) );?></p>
																<div class="cartbounty-automation-status">
																	<?php echo $automation_status; ?>
																</div>
															</div>
															<div class="cartbounty-step-trigger"></div>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-xs-12 cartbounty-col-sm-8 cartbounty-col-lg-9">
															<div class="cartbounty-row">
																<div class="cartbounty-stats-container cartbounty-col-sm-12 cartbounty-col-lg-9">
																	<div class="cartbounty-stats cartbounty-percentage-switcher">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e( 'Queue', 'woo-save-abandoned-carts' ); ?></i>
																			<p><?php echo esc_html( $wordpress->get_queue( $key ) ); ?></p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e( 'Sends', 'woo-save-abandoned-carts' ); ?></i>
																			<p><?php echo esc_html( $wordpress->get_stats( $key , $ingle = true ) ); ?></p>
																		</div>
																	</div>
																	<div class="cartbounty-stats">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e( 'Open rate', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_enable_email_stats' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Upgrade to see stats', 'woo-save-abandoned-carts'); ?></a>
																			<i><?php esc_html_e( 'Opens', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																	<div class="cartbounty-stats">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e( 'Click rate', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e( 'Clicks', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																	<div class="cartbounty-stats">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e( 'Recovery rate', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e( 'Recovered', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																	<div class="cartbounty-stats">
																		<div class="cartbounty-stats-percentage">
																			<i><?php esc_html_e( 'Unsubscribe rate', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																		<div class="cartbounty-stats-count">
																			<i><?php esc_html_e( 'Unsubscribes', 'woo-save-abandoned-carts' ); ?></i>
																			<p>-</p>
																		</div>
																	</div>
																</div>
																<div class="cartbounty-trigger-container cartbounty-col-sm-12 cartbounty-col-lg-3">
																	<div class="cartbounty-automation-status">
																		<?php echo $automation_status; ?>
																	</div>
																	<div class="cartbounty-step-trigger"></div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="cartbounty-step-contents">
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e('Enable email sending to start this automated abandoned cart recovery step.', 'woo-save-abandoned-carts');
																	echo ' ' . sprintf(
																	/* translators: %s - Link tags */
																	 esc_html__('Learn how to use %spersonalization tags%s.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'personalization-tags', 'wp_personalization' ) ) .'" target="_blank">', '</a>');?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9<?php if($enabled){ echo ' cartbounty-checked-parent'; }?>">
															<div class="cartbounty-settings-group cartbounty-toggle">
																<label for="cartbounty-automation-status-<?php echo esc_attr( $key ); ?>" class="cartbounty-switch cartbounty-control-visibility cartbounty-step-controller">
																	<input id="cartbounty-automation-status-<?php echo esc_attr( $key ); ?>" class="cartbounty-checkbox" type="checkbox" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $enabled, false ); ?> autocomplete="off" />
																	<span class="cartbounty-slider round"></span>
																</label>
																<label for="cartbounty-automation-status-<?php echo esc_attr( $key ); ?>" class="cartbounty-control-visibility cartbounty-step-controller"><?php esc_html_e('Enable email', 'woo-save-abandoned-carts'); ?></label>
															</div>
															<div class="cartbounty-settings-group cartbounty-hidden">
																<label for="cartbounty-automation-interval"><?php esc_html_e('Send email after', 'woo-save-abandoned-carts'); ?></label>
																<?php $this->display_time_intervals( 'cartbounty_automation_steps', $key ); ?>
																<p class='cartbounty-additional-information'>
																	<?php echo sprintf(
																	/* translators: %s - Link tags */
																	 esc_html__( 'Please %ssee this%s to learn how reminder sending works and when it will be delivered.', 'woo-save-abandoned-carts' ), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'abandoned-carts', 'wp_is_it_abandoned', '#when-is-the-cart-abandoned' ) ) .'" target="_blank">', '</a>'); ?>
																</p>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-subject-<?php echo esc_attr( $key ); ?>"><?php esc_html_e('Email subject', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-content-creation cartbounty-flex">
																	<input id="cartbounty-automation-subject-<?php echo esc_attr( $key ); ?>" class="cartbounty-text" type="text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][subject]" value="<?php echo $this->sanitize_field($subject); ?>" placeholder="<?php echo esc_attr( $wordpress->get_defaults( 'subject', $key ) ); ?>" /><?php $this->add_emojis(); ?><?php $this->add_tags(); ?>
																</div>
																<p class='cartbounty-additional-information'>
																	<?php esc_html_e('Subject line has a huge impact on email open rate.', 'woo-save-abandoned-carts'); ?>
																</p>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-heading-<?php echo esc_attr( $key ); ?>"><?php esc_html_e('Main title', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-content-creation cartbounty-flex">
																	<input id="cartbounty-automation-heading-<?php echo esc_attr( $key ); ?>" class="cartbounty-text" type="text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][heading]" value="<?php echo $this->sanitize_field($heading); ?>" placeholder="<?php echo esc_attr( $wordpress->get_defaults( 'heading', $key ) ); ?>" /><?php $this->add_emojis(); ?><?php $this->add_tags(); ?>
																</div>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-content-<?php echo esc_attr( $key ); ?>"><?php esc_html_e('Content', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-content-creation cartbounty-flex">
																	<textarea id="cartbounty-automation-content-<?php echo esc_attr( $key ); ?>" class="cartbounty-text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][content]" placeholder="<?php echo esc_attr( $wordpress->get_defaults( 'content', $key ) ); ?>" rows="4"><?php echo $this->sanitize_field($content); ?></textarea><?php $this->add_emojis(); ?><?php $this->add_tags(); ?>
																</div>
															</div>
														</div>
													</div>
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('Appearance', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e( 'Choose a template that will be used to display the abandoned cart reminder email.', 'woo-save-abandoned-carts' ); ?> <?php echo sprintf(
																		/* translators: %s - Link tags */
																		 esc_html__( 'Look here to see advanced %stemplate customization%s options.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'templates', 'wp_template_customization' ) ) .'" target="_blank">', '</a>');?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9<?php if($include_image){ echo ' cartbounty-checked-parent'; }?>">
															<div class="cartbounty-settings-group">
																<h4><?php esc_html_e('Template', 'woo-save-abandoned-carts'); ?></h4>
																<div class="cartbounty-flex-container">
																	<div id="cartbounty-automation-template-light-<?php echo esc_attr( $key ); ?>" class="cartbounty-type cartbounty-email-template cartbounty-email-template-light cartbounty-radio-active">
																		<label class="cartbounty-image" for="cartbounty-template-light">
																			<em>
																				<i>
																					<span class="cartbounty-template-top-image"></span>
																					<img src="<?php echo esc_url( plugins_url( 'assets/template-light.svg', __FILE__ ) ) ; ?>" title="<?php esc_attr_e('Light', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Light', 'woo-save-abandoned-carts'); ?>"/>
																				</i>
																			</em>
																			<input id="cartbounty-template-light-<?php echo esc_attr( $key ); ?>" class="cartbounty-radiobutton" type="radio" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][template]" value="1" <?php echo $this->disable_field(); ?> checked="" autocomplete="off" />
																			<?php esc_html_e('Light', 'woo-save-abandoned-carts'); ?>
																		</label>
																	</div>
																	<div id="cartbounty-automation-template-rows-<?php echo esc_attr( $key ); ?>" class="cartbounty-type cartbounty-email-template cartbounty-email-template-rows">
																		<label class="cartbounty-image" for="cartbounty-template-rows">
																			<em>
																				<i>
																					<span class="cartbounty-template-top-image"></span>
																					<img src="<?php echo esc_url( plugins_url( 'assets/template-rows.svg', __FILE__ ) ) ; ?>" title="<?php esc_attr_e('With cart contents', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('With cart contents', 'woo-save-abandoned-carts'); ?>"/>
																				</i>
																				<span class="cartbounty-wordpress-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
																					<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_style_rows' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
																				</span>
																			</em>
																			<input id="cartbounty-template-rows-<?php echo esc_attr( $key ); ?>" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
																			<?php esc_html_e('With cart contents', 'woo-save-abandoned-carts'); ?>
																		</label>
																	</div>
																	<div id="cartbounty-automation-template-columns-<?php echo esc_attr( $key ); ?>" class="cartbounty-type cartbounty-email-template cartbounty-email-template-columns">
																		<label class="cartbounty-image" for="cartbounty-template-columns">
																			<em>
																				<i>
																					<span class="cartbounty-template-top-image"></span>
																					<img src="<?php echo esc_url( plugins_url( 'assets/template-columns.svg', __FILE__ ) ) ; ?>" title="<?php esc_attr_e('With cart contents in columns', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('With cart contents in columns', 'woo-save-abandoned-carts'); ?>"/>
																				</i>
																				<span class="cartbounty-wordpress-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
																					<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_style_columns' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
																				</span>
																			</em>
																			<input id="cartbounty-template-columns-<?php echo esc_attr( $key ); ?>" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
																			<?php esc_html_e('With cart contents in columns', 'woo-save-abandoned-carts'); ?>
																		</label>
																	</div>
																</div>
															</div>
															<div class="cartbounty-settings-group">
																<h4><?php esc_html_e('Colors', 'woo-save-abandoned-carts'); ?></h4>
																<p class='cartbounty-additional-information'>
																	<?php esc_html_e('Look at the default email colors and adjust them to fit your design requirements.', 'woo-save-abandoned-carts'); ?>
																</p>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-main-color"><?php esc_html_e('Main:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-main-color-<?php echo esc_attr( $key ); ?>" type="text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][main_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $main_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-inverse-color"><?php esc_html_e('Button:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-button-color-<?php echo esc_attr( $key ); ?>" type="text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][button_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $button_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-text-color"><?php esc_html_e('Text:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-text-color-<?php echo esc_attr( $key ); ?>" type="text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][text_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $text_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
																<div class="cartbounty-colors">
																	<label for="cartbounty-template-background-color"><?php esc_html_e('Backdrop:', 'woo-save-abandoned-carts'); ?></label>
																	<input id="cartbounty-template-background-color-<?php echo esc_attr( $key ); ?>" type="text" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][background_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $background_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
																</div>
															</div>
															<div class="cartbounty-settings-group cartbounty-toggle <?php if($include_image){ echo ' cartbounty-checked'; }?>">
																<label for="cartbounty-automation-include-image-<?php echo esc_attr( $key ); ?>" class="cartbounty-switch cartbounty-control-visibility">
																	<input id="cartbounty-automation-include-image-<?php echo esc_attr( $key ); ?>" class="cartbounty-checkbox" type="checkbox" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][include_image]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $include_image, false ); ?> autocomplete="off" />
																	<span class="cartbounty-slider round"></span>
																</label>
																<label for="cartbounty-automation-include-image-<?php echo esc_attr( $key ); ?>"><?php esc_html_e('Include image', 'woo-save-abandoned-carts'); ?></label>
															</div>
															<div class="cartbounty-settings-group cartbounty-hidden">
																<?php
																	if(!did_action('wp_enqueue_media')){
																		wp_enqueue_media();
																	}
																	$image = wp_get_attachment_image_src( $main_image );
																?>
																<h4><?php esc_html_e('Custom image', 'woo-save-abandoned-carts'); ?></h4>
																<p class='cartbounty-additional-information'>
																	<?php esc_html_e('Recommended dimensions:', 'woo-save-abandoned-carts'); ?> 900 x 300 px.
																</p>
																<div class="cartbounty-action-container">
																	<p id="cartbounty-upload-custom-image-<?php echo esc_attr( $key ); ?>" class="cartbounty-upload-image" data-automation="<?php echo esc_attr( $key ); ?>">
																		<?php if($image):?>
																			<img src="<?php echo esc_url( $image[0] ); ?>" />
																		<?php else: ?>
																			<input type="button" value="<?php esc_attr_e('Add a custom image', 'woo-save-abandoned-carts'); ?>" class="cartbounty-button button-secondary button" <?php echo $this->disable_field(); ?> />
																		<?php endif;?>
																	</p>
																	<a href="#" id="cartbounty-remove-custom-image-<?php echo esc_attr( $key ); ?>" class="cartbounty-remove-image" data-automation="<?php echo esc_attr( $key ); ?>" <?php if(!$image){echo 'style="display:none"';}?>></a>
																</div>
																<input id="cartbounty-custom-image-<?php echo esc_attr( $key ); ?>" type="hidden" name="cartbounty_automation_steps[<?php echo esc_attr( $key ); ?>][main_image]" value="<?php if($main_image){echo esc_attr( $main_image );}?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
															</div>
														</div>
													</div>
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('Coupon', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e('Consider adding a coupon code to your reminder to encourage customers to complete their purchase.', 'woo-save-abandoned-carts'); ?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
															<div class="cartbounty-settings-group cartbounty-toggle">
																<label for="cartbounty-automation-generate-coupon" class="cartbounty-switch cartbounty-unavailable">
																	<input id="cartbounty-automation-generate-coupon-<?php echo esc_attr( $key ); ?>" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
																	<span class="cartbounty-slider round"></span>
																</label>
																<label for="cartbounty-automation-generate-coupon" class="cartbounty-unavailable"><?php esc_html_e('Generate coupon', 'woo-save-abandoned-carts'); ?></label>
																<p class='cartbounty-additional-information'>
																	<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'generate_coupon' ); ?></i>
																</p>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-automation-existing-coupon"><?php esc_html_e('Include an existing coupon', 'woo-save-abandoned-carts'); ?></label>
																<select id="cartbounty-automation-existing-coupon-<?php echo esc_attr( $key ); ?>" class="cartbounty-select" placeholder="<?php esc_attr_e('Search coupon...', 'woo-save-abandoned-carts'); ?>" disabled autocomplete="off">
																</select>
															</div>
														</div>
													</div>
													<div class="cartbounty-row">
														<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
															<h4><?php esc_html_e('Test email', 'woo-save-abandoned-carts'); ?></h4>
															<p class="cartbounty-titles-column-description">
																<?php esc_html_e('Before activating this automation, you might want to test your email.', 'woo-save-abandoned-carts'); ?>
															</p>
														</div>
														<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
															<div class="cartbounty-settings-group">
																<h4><?php esc_html_e('Preview', 'woo-save-abandoned-carts'); ?></h4>
																<button type="button" class='cartbounty-button button-secondary cartbounty-progress cartbounty-preview-email' data-automation='<?php echo esc_attr( $key ); ?>' data-nonce='<?php echo esc_attr( $preview_email_nonce ); ?>' <?php echo $this->disable_field(); ?>><?php esc_html_e('Preview email', 'woo-save-abandoned-carts'); ?></button>
																<?php echo $this->output_modal_container( $key ); ?>
															</div>
															<div class="cartbounty-settings-group">
																<label for="cartbounty-send-test"><?php esc_html_e('Send a test email to', 'woo-save-abandoned-carts'); ?></label>
																<div class="cartbounty-input-with-button">
																	<input id="cartbounty-send-test-<?php echo esc_attr( $key ); ?>" class="cartbounty-text cartbounty-disable-submit" type="email"  placeholder="<?php echo esc_attr( get_option( 'admin_email' ) );?>" <?php echo $this->disable_field(); ?> />
																	<button type="button" class='cartbounty-button button-secondary cartbounty-progress cartbounty-send-email' data-automation='<?php echo esc_attr( $key ); ?>' data-nonce='<?php echo esc_attr( $test_email_nonce ); ?>' <?php echo $this->disable_field(); ?>><?php esc_html_e('Send', 'woo-save-abandoned-carts'); ?></button>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										<?php }} ?>
										<div class="cartbounty-step<?php esc_attr( $wordpress->check_if_accessible( 3 ) ); ?> cartbounty-step-unavailable" data-automation-step="3">
											<div class="cartbounty-step-opener">
												<div class="cartbounty-row">
													<div class="cartbounty-titles-column cartbounty-col-xs-12 cartbounty-col-sm-4 cartbounty-col-lg-3">
														<div class="cartbounty-automation-number">4</div>
														<div class="cartbounty-automation-name">
															<h3><?php echo esc_html( $wordpress->get_defaults( 'name', 3 ) ); ?></h3>
															<p><?php $time_interval_name = $this->get_interval_data( 'cartbounty_automation_steps', 3, $just_selected_value = true );
																echo sprintf( esc_html__('Sends after %s', 'woo-save-abandoned-carts'), esc_html( $time_interval_name ) );?></p>
															<div class="cartbounty-automation-status">
																<span class="status inactive"><?php esc_html_e( 'Disabled', 'woo-save-abandoned-carts' ); ?></span>
															</div>
														</div>
														<div class="cartbounty-step-trigger"></div>
													</div>
													<div class="cartbounty-settings-column cartbounty-col-xs-12 cartbounty-col-sm-8 cartbounty-col-lg-9">
														<div class="cartbounty-row">
															<div class="cartbounty-stats-container cartbounty-col-sm-12 cartbounty-col-lg-9">
																<div class="cartbounty-stats cartbounty-percentage-switcher">
																	<div class="cartbounty-stats-percentage">
																		<i><?php esc_html_e( 'Queue', 'woo-save-abandoned-carts' ); ?></i>
																		<p>0</p>
																	</div>
																	<div class="cartbounty-stats-count">
																		<i><?php esc_html_e( 'Sends', 'woo-save-abandoned-carts' ); ?></i>
																		<p>0</p>
																	</div>
																</div>
																<div class="cartbounty-stats">
																	<div class="cartbounty-stats-percentage">
																		<i><?php esc_html_e( 'Open rate', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																	<div class="cartbounty-stats-count">
																		<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_enable_email_stats' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Upgrade to see stats', 'woo-save-abandoned-carts'); ?></a>
																		<i><?php esc_html_e( 'Opens', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																</div>
																<div class="cartbounty-stats">
																	<div class="cartbounty-stats-percentage">
																		<i><?php esc_html_e( 'Click rate', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																	<div class="cartbounty-stats-count">
																		<i><?php esc_html_e( 'Clicks', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																</div>
																<div class="cartbounty-stats">
																	<div class="cartbounty-stats-percentage">
																		<i><?php esc_html_e( 'Recovery rate', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																	<div class="cartbounty-stats-count">
																		<i><?php esc_html_e( 'Recovered', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																</div>
																<div class="cartbounty-stats">
																	<div class="cartbounty-stats-percentage">
																		<i><?php esc_html_e( 'Unsubscribe rate', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																	<div class="cartbounty-stats-count">
																		<i><?php esc_html_e( 'Unsubscribes', 'woo-save-abandoned-carts' ); ?></i>
																		<p>-</p>
																	</div>
																</div>
															</div>
															<div class="cartbounty-trigger-container cartbounty-col-sm-12 cartbounty-col-lg-3">
																<div class="cartbounty-automation-status">
																	<span class="status inactive"><?php esc_html_e( 'Disabled', 'woo-save-abandoned-carts' ); ?></span>
																</div>
																<div class="cartbounty-step-trigger"></div>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="cartbounty-wordpress-get-additional-step">
												<div class="cartbounty-row">
													<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-lg-3">
														<div class="cartbounty-automation-name">
															<h3><?php esc_html_e('Upgrade to enable this reminder', 'woo-save-abandoned-carts'); ?></h3>
														</div>
													</div>
													<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-lg-9">
														<div class="cartbounty-stats">
															<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'wp_add_automation_step' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
														</div>
													</div>
												</div>
											</div>
										</div>
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General email settings', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('"From" name tells your recipients who sent them the message. It is just as important as your subject line and can impact whether your email is opened, or not.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<label for="cartbounty-automation-from-name"><?php esc_html_e('"From" name', 'woo-save-abandoned-carts'); ?></label>
								<input id="cartbounty-automation-from-name" class="cartbounty-text" type="text" name="cartbounty_automation_settings[from_name]" value="<?php echo $this->sanitize_field( $settings['from_name'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'blogname' ) );?>" <?php echo $this->disable_field(); ?> />
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-automation-from-email"><?php esc_html_e('"From" email', 'woo-save-abandoned-carts'); ?></label>
								<input id="cartbounty-automation-from-email" class="cartbounty-text" type="email" name="cartbounty_automation_settings[from_email]" value="<?php echo sanitize_email( $settings['from_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) );?>" <?php echo $this->disable_field(); ?> />
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-automation-reply-email"><?php esc_html_e('Reply-to address', 'woo-save-abandoned-carts'); ?></label>
								<input id="cartbounty-automation-reply-email" class="cartbounty-text" type="email" name="cartbounty_automation_settings[reply_email]" value="<?php echo sanitize_email( $settings['reply_email'] ); ?>" <?php echo $this->disable_field(); ?> />
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<?php $this->display_exclusion_settings(); ?>
					</div>
					<div class='cartbounty-button-row'>
						<?php
						if( $this->user_is_admin() ){
							echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". esc_html__('Save settings', 'woo-save-abandoned-carts') ."</button>";
						}
						if($wordpress->automation_enabled()){
							echo $this->display_force_sync_button( $active_section );
						}
						?>
					</div>
				</form>

				<?php
				break;

			case 'exit_intent':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
						/* translators: %s - Link */
						 esc_html__('With the help of Exit Intent, you can capture even more abandoned carts by displaying a message including an email or phone field that the customer can fill to save his shopping cart. You can even offer to send a discount code. Please note that the Exit Intent will only be showed to unregistered users once every 60 minutes after they have added an item to their cart and try to leave your store. Learn how to %scustomize contents%s of Exit Intent popup.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'exit-intent-popup-technology', 'ei_modify_content' ) ) .'" target="_blank" title="'. esc_html__( 'How to customize contents of Exit Intent', 'woo-save-abandoned-carts' ) .'">', '</a>' );
					?>
				</div>
				<form method="post" action="options.php">
					<?php 
						settings_fields( 'cartbounty-settings-exit-intent' );
						do_settings_sections( 'cartbounty-settings-exit-intent' );

						$ei_settings = $this->get_settings( 'exit_intent' );
						$ei_status = $ei_settings['status'];
						$ei_heading = $ei_settings['heading'];
						$ei_content = $ei_settings['content'];
						$ei_style = $ei_settings['style'];
						$ei_main_color = $ei_settings['main_color'];
						$ei_inverse_color = $ei_settings['inverse_color'];
						$ei_image = $ei_settings['image'];
						$ei_test_mode = $ei_settings['test_mode'];
					?>

					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable email or phone request before leaving your store.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9<?php if($ei_status){ echo ' cartbounty-checked-parent'; }?>">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-exit-intent-status" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-exit-intent-status" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_settings[status]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $ei_status, false ); ?> autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-status" class="cartbounty-control-visibility"><?php esc_html_e('Enable Exit Intent', 'woo-save-abandoned-carts'); ?></label>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle cartbounty-hidden">
								<label for="cartbounty-exit-intent-mobile-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-exit-intent-mobile-status" class="cartbounty-checkbox" type="checkbox" value="0" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-mobile-status" class="cartbounty-unavailable"><?php esc_html_e('Enable mobile Exit Intent', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'exit_intent_mobile' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Field type', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('Choose which input field should be collected in the popup.', 'woo-save-abandoned-carts'); ?>
								</p>
								<label for="cartbounty-exit-intent-field-type-email" class="cartbounty-radiobutton-label">
									<input id="cartbounty-exit-intent-field-type-email" class="cartbounty-radiobutton" type="radio" checked autocomplete="off" />
										<?php esc_html_e('Email', 'woo-save-abandoned-carts'); ?>
								</label>
								<label for="cartbounty-exit-intent-field-type-phone" class="cartbounty-radiobutton-label cartbounty-unavailable">
									<input id="cartbounty-exit-intent-field-type-phone" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Phone', 'woo-save-abandoned-carts'); ?>
								</label>
								<label for="cartbounty-exit-intent-field-type-phone-and-email" class="cartbounty-radiobutton-label cartbounty-unavailable">
									<input id="cartbounty-exit-intent-field-type-phone-and-email" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Both', 'woo-save-abandoned-carts'); ?>
								</label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'exit_intent_phone_or_email' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-exit-intent-heading"><?php esc_html_e('Main title', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<input id="cartbounty-exit-intent-heading" class="cartbounty-text" type="text" name="cartbounty_exit_intent_settings[heading]" value="<?php echo esc_attr( $this->sanitize_field($ei_heading) ); ?>" placeholder="<?php echo esc_attr( $this->get_tools_defaults('heading', 'exit_intent') ); ?>" /><?php $this->add_emojis(); ?>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-exit-intent-content"><?php esc_html_e('Content', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<textarea id="cartbounty-exit-intent-content" class="cartbounty-text" name="cartbounty_exit_intent_settings[content]" placeholder="<?php echo esc_attr( $this->get_tools_defaults('content', 'exit_intent') ); ?>" rows="4"><?php echo $this->sanitize_field( $ei_content ); ?></textarea><?php $this->add_emojis(); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Appearance', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Adjust the visual appearance of your Exit Intent popup.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Style', 'woo-save-abandoned-carts'); ?></h4>
								<div class="cartbounty-flex-container">
									<div id="cartbounty-exit-intent-center" class="cartbounty-type <?php if($ei_style == 1){ echo "cartbounty-radio-active";} ?>">
										<label class="cartbounty-image" for="cartbounty-radiobutton-center">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Appear in center', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Appear in center', 'woo-save-abandoned-carts'); ?>"/>
												</i>
											</em>
											<input id="cartbounty-radiobutton-center" class="cartbounty-radiobutton" type="radio" name="cartbounty_exit_intent_settings[style]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $ei_style, false ); ?> autocomplete="off" />
											<?php esc_html_e('Appear in center', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
									<div id="cartbounty-exit-intent-left" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-left">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Slide in from left', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Slide in from left', 'woo-save-abandoned-carts'); ?>"/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ei_style_left' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-left" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Slide in from left', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
									<div id="cartbounty-exit-intent-fullscreen" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-fullscreen">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/exit-intent-form.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Fullscreen', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Fullscreen', 'woo-save-abandoned-carts'); ?>"/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ei_style_fullscreen' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-fullscreen" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Fullscreen', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Colors', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('If you leave the Inverse color empty, it will automatically use the inverse color of the main color you have picked. Clear both colors to use the default colors.', 'woo-save-abandoned-carts'); ?>
								</p>
								<div class="cartbounty-colors">
									<label for="cartbounty-exit-intent-main-color"><?php esc_html_e('Main:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-exit-intent-main-color" type="text" name="cartbounty_exit_intent_settings[main_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $ei_main_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
								</div>
								<div class="cartbounty-colors">
									<label for="cartbounty-exit-intent-inverse-color"><?php esc_html_e('Inverse:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-exit-intent-inverse-color" type="text" name="cartbounty_exit_intent_settings[inverse_color]" class="cartbounty-color-picker cartbounty-text" value="<?php echo esc_attr( $ei_inverse_color ); ?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<?php
									if(!did_action('wp_enqueue_media')){
										wp_enqueue_media();
									}
									$image = wp_get_attachment_image_src( $ei_image );
								?>
								<h4><?php esc_html_e('Custom image', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('Recommended dimensions:', 'woo-save-abandoned-carts'); ?> 1024 x 600 px.
								</p>
								<div class="cartbounty-action-container">
									<p id="cartbounty-upload-custom-image" class="cartbounty-upload-image">
										<?php if($image):?>
											<img src="<?php echo esc_url( $image[0] ); ?>" />
										<?php else: ?>
											<input type="button" value="<?php esc_attr_e('Add a custom image', 'woo-save-abandoned-carts'); ?>" class="cartbounty-button button-secondary button" <?php echo $this->disable_field(); ?> />
										<?php endif;?>
									</p>
									<a href="#" id="cartbounty-remove-custom-image" class="cartbounty-remove-image" <?php if(!$image){echo 'style="display:none"';}?>></a>
								</div>
								<input id="cartbounty-custom-image" type="hidden" name="cartbounty_exit_intent_settings[image]" value="<?php if($ei_image){echo esc_attr( $ei_image );}?>" <?php echo $this->disable_field(); ?> autocomplete="off" />
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<?php echo $this->display_instant_coupon_settings( 'exit-intent' ); ?>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Miscellaneous', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable test mode to see how the Exit Intent popup looks like.', 'woo-save-abandoned-carts');?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle<?php if($ei_test_mode){ echo ' cartbounty-checked'; }?>">
								<label for="cartbounty-exit-intent-test-mode" class="cartbounty-switch cartbounty-control-visibility">
									<input id="cartbounty-exit-intent-test-mode" class="cartbounty-checkbox" type="checkbox" name="cartbounty_exit_intent_settings[test_mode]" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $ei_test_mode, false ); ?> autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-exit-intent-test-mode" class="cartbounty-control-visibility"><?php esc_html_e('Enable test mode', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden'>
										<?php esc_html_e('Now open your store, add a product to the cart and try leaving it. Please note that while this is enabled, only users with Admin rights will be able to see the Exit Intent and appearance limitations have been removed which means that you will see the popup each time you try to leave your store. Do not forget to disable this after you have done testing.', 'woo-save-abandoned-carts'); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<?php
						if( $this->user_is_admin() ){
							echo "<button type='submit' class='cartbounty-button button-primary cartbounty-progress'>". esc_html__('Save settings', 'woo-save-abandoned-carts') ."</button>";
						}?>
					</div>
				</form>
				<?php
				break;

			case 'early_capture':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo sprintf(
						/* translators: %s - Link */
						 esc_html__('Try saving more recoverable abandoned carts by enabling Early capture to collect customer’s email or phone right after the "Add to cart" button is clicked. You can also enable mandatory input to make sure guest visitors are not able to add anything to their carts until a valid email or phone is provided. Please note that Early capture will only be presented to unregistered visitors once every 60 minutes. Learn how to %scustomize contents%s of Early capture request.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'early-capture-add-to-cart-popup', 'ec_modify_content' ) ) .'" target="_blank" title="'. esc_html__( 'How to customize contents of Early capture', 'woo-save-abandoned-carts' ) .'">', '</a>' );
					?>
				</div>
				<form>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable email or phone request before adding an item to the cart.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-early-capture-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-early-capture-status" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-early-capture-status" class="cartbounty-unavailable"><?php esc_html_e('Enable Early capture', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'early_capture_enable' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-early-capture-mandatory" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-early-capture-mandatory" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-early-capture-mandatory" class="cartbounty-unavailable"><?php esc_html_e('Enable mandatory input', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i style="display: none;">
										<?php esc_html_e('Your guest visitors will not be able to add anything to their carts until a valid email or phone is provided.', 'woo-save-abandoned-carts'); ?>
									</i>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Field type', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('Choose which input field should be collected in the request.', 'woo-save-abandoned-carts'); ?>
								</p>
								<label for="cartbounty-early-capture-field-type-email" class="cartbounty-radiobutton-label cartbounty-unavailable">
									<input id="cartbounty-early-capture-field-type-email" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Email', 'woo-save-abandoned-carts'); ?>
								</label>
								<label for="cartbounty-early-capture-field-type-phone" class="cartbounty-radiobutton-label cartbounty-unavailable">
									<input id="cartbounty-early-capture-field-type-phone" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
										<?php esc_html_e('Phone', 'woo-save-abandoned-carts'); ?>
								</label>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty-early-capture-heading"><?php esc_html_e('Main title', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<input id="cartbounty-early-capture-heading" class="cartbounty-text" type="text" placeholder="<?php echo esc_attr( $this->get_tools_defaults('heading', 'early_capture') ); ?>" disabled /><?php $this->add_emojis(); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Appearance', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Adjust the visual appearance of your Early capture request.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Style', 'woo-save-abandoned-carts'); ?></h4>
								<div class="cartbounty-flex-container">
									<div id="cartbounty-early-capture-near-button" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-center">
											<em>
												<i>
													<svg viewBox="0 0 61 35">
														<g id="cartbounty-early-capture-popup-group">
															<path id="cartbounty-near-button-1" d="M58,35H3a3,3,0,0,1-3-3V20a3,3,0,0,1,3-3H9l3-4,3,4H58a3,3,0,0,1,3,3V32A3,3,0,0,1,58,35Z"/>
															<path id="cartbounty-near-button-2" d="M38.88,27.33H12a1.86,1.86,0,0,1-2-1.67V25.5a1.86,1.86,0,0,1,2-1.67H38.88Z"/>
															<path id="cartbounty-near-button-3" d="M49.38,27.33H38.5v-3.5H49.38a1.86,1.86,0,0,1,2,1.67v.16A1.86,1.86,0,0,1,49.38,27.33Z"/>
														</g>
														<rect id="cartbounty-near-button-4" width="42" height="12" rx="3"/>
													</svg>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ec_style_button' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-center" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Next to button', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
									<div id="cartbounty-early-capture-center" class="cartbounty-type">
										<label class="cartbounty-image" for="cartbounty-radiobutton-left">
											<em>
												<i>
													<img src="<?php echo esc_url( plugins_url( 'assets/early-capture-form-popup.svg', __FILE__ ) ); ?>" title="<?php esc_attr_e('Popup overlay', 'woo-save-abandoned-carts'); ?>" alt="<?php esc_attr_e('Popup overlay', 'woo-save-abandoned-carts'); ?>"/>
												</i>
												<span class="cartbounty-exit-intent-additional-style"><?php esc_html_e('Upgrade to enable this style', 'woo-save-abandoned-carts'); ?>
													<a href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'ec_style_popup' ) ); ?>" class="button cartbounty-button" target="_blank"><?php esc_html_e('Get Pro', 'woo-save-abandoned-carts'); ?></a>
												</span>
											</em>
											<input id="cartbounty-radiobutton-left" class="cartbounty-radiobutton" type="radio" disabled autocomplete="off" />
											<?php esc_html_e('Popup overlay', 'woo-save-abandoned-carts'); ?>
										</label>
									</div>
								</div>
							</div>
							<div class="cartbounty-settings-group">
								<h4><?php esc_html_e('Colors', 'woo-save-abandoned-carts'); ?></h4>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('If you leave the Inverse color empty, it will automatically use the inverse color of the main color you have picked. Clear both colors to use the default colors.', 'woo-save-abandoned-carts'); ?>
								</p>
								<div class="cartbounty-colors">
									<label for="cartbounty-early-capture-main-color"><?php esc_html_e('Main:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-early-capture-main-color" type="text" class="cartbounty-color-picker cartbounty-text" disabled autocomplete="off" />
								</div>
								<div class="cartbounty-colors">
									<label for="cartbounty-early-capture-inverse-color"><?php esc_html_e('Inverse:', 'woo-save-abandoned-carts'); ?></label>
									<input id="cartbounty-early-capture-inverse-color" type="text" class="cartbounty-color-picker cartbounty-text" disabled autocomplete="off" />
								</div>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<?php echo $this->display_instant_coupon_settings( 'early-capture' ); ?>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Miscellaneous', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable test mode to see how the Early capture looks like.', 'woo-save-abandoned-carts');?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-early-capture-test-mode" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-early-capture-test-mode" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-early-capture-test-mode" class="cartbounty-control-visibility cartbounty-unavailable"><?php esc_html_e('Enable test mode', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i style="display: none;">
										<?php esc_html_e('Now open your store and try adding a product to your cart. Please note that while this is enabled, only users with Admin rights will be able to see the Early capture request and appearance limitations have been removed which means that you will see the request each time you try to add an item to your cart. Do not forget to disable this after you have done testing.', 'woo-save-abandoned-carts'); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<a class="button cartbounty-button button-primary" href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'early_capture_enable_button' ) ); ?>" target="_blank"> <?php esc_html_e('Get Pro to enable', 'woo-save-abandoned-carts'); ?></a>
					</div>
				</form>
				<?php
				break;

			case 'tab_notification':

				if(!class_exists('WooCommerce')){ //If WooCommerce is not active
					$this->missing_woocommerce_notice( $active_section ); 
					return;
				}?>

				<div class="cartbounty-section-intro">
					<?php echo esc_html__( "Decrease shopping cart abandonment by grabbing customer attention and returning them to your store after they have switched to a new browser tab with Tab notification.", 'woo-save-abandoned-carts') . ' ' . sprintf( 
						/* translators: %s - Link */
						esc_html__( "Remind your customers that their shopping cart is craving for some love and attention :). Learn more about %sTab notification%s.", 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'dynamic-browser-tab-notification', 'tn_learn_more' ) ) .'" target="_blank" title="'. esc_html__( 'Tab notification', 'woo-save-abandoned-carts' ) .'">', '</a>'
					); ?>
				</div>
				<form>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('General', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Enable Tab notification and set the speed at which tab Title and Favicon will change.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-tab-notification-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-tab-notification-status" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-tab-notification-status" class="cartbounty-unavailable"><?php esc_html_e('Enable Tab notification', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'tab_notification_enable' ); ?></i>
								</p>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-tab-notification-check-cart" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-tab-notification-check-cart" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-tab-notification-check-cart" class="cartbounty-unavailable"><?php esc_html_e('Check for empty cart', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('If enabled, will show Tab notification only when shopping cart is not empty.', 'woo-save-abandoned-carts'); ?>
								</p>
							</div>
							<div class="cartbounty-settings-group">
								<label for="cartbounty_tab_notification_interval"><?php esc_html_e( 'Notification interval', 'woo-save-abandoned-carts' ); ?></label>
								<select id="cartbounty_tab_notification_interval" class="cartbounty-select" disabled autocomplete="off">
									<option><?php echo sprintf( esc_html( _n( '%s second', '%s seconds', 2, 'woo-save-abandoned-carts' ) ), 2 )?></option>
								</select>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e('Notification content', 'woo-save-abandoned-carts'); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e('Customize the message that will appear in the Tab title and enable Favicon.', 'woo-save-abandoned-carts'); ?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group">
								<label for="cartbounty-tab-notification-message"><?php esc_html_e('Message', 'woo-save-abandoned-carts'); ?></label>
								<div class="cartbounty-content-creation cartbounty-flex">
									<input id="cartbounty-tab-notification-message" class="cartbounty-text" type="text" placeholder="<?php echo esc_attr__( 'I miss you 💔', 'woo-save-abandoned-carts' ); ?>" disabled /><?php $this->add_emojis(); ?>
								</div>
							</div>
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-tab-notification-favicon-status" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-tab-notification-favicon-status" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-tab-notification-favicon-status" class="cartbounty-unavailable"><?php esc_html_e('Enable Favicon change', 'woo-save-abandoned-carts'); ?></label>
								<p class='cartbounty-additional-information'>
									<?php esc_html_e('A Favicon is a small image displayed next to the page title in the browser tab.', 'woo-save-abandoned-carts'); ?> <i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'tab_notification_favicon_enable' ); ?></i>
								</p>
							</div>
						</div>
					</div>
					<div class="cartbounty-row">
						<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
							<h4><?php esc_html_e( 'Miscellaneous', 'woo-save-abandoned-carts' ); ?></h4>
							<p class="cartbounty-titles-column-description">
								<?php esc_html_e( 'Enable test mode to see how Tab notification works.', 'woo-save-abandoned-carts' );?>
							</p>
						</div>
						<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
							<div class="cartbounty-settings-group cartbounty-toggle">
								<label for="cartbounty-tab-notification-test-mode" class="cartbounty-switch cartbounty-unavailable">
									<input id="cartbounty-tab-notification-test-mode" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
									<span class="cartbounty-slider round"></span>
								</label>
								<label for="cartbounty-tab-notification-test-mode" class="cartbounty-unavailable"><?php esc_html_e( 'Enable test mode', 'woo-save-abandoned-carts' ); ?></label>
								<p class='cartbounty-additional-information'>
									<i style="display: none;">
										<?php esc_html_e( 'Now open your store, add a product to the cart and switch to a new browser tab. Please note that while this is enabled, only users with Admin rights will be able to see the Tab notification. Do not forget to disable this after you have done testing.', 'woo-save-abandoned-carts' ); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
					<div class='cartbounty-button-row'>
						<a class="button cartbounty-button button-primary" href="<?php echo esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'tab_notification_enable_button' ) ); ?>" target="_blank"> <?php esc_html_e('Get Pro to enable', 'woo-save-abandoned-carts'); ?></a>
					</div>
				</form>
				<?php
				break;
		}
	}

	/**
	 * Method displays notice of a missing WooCommerce plugin
	 *
	 * @since    6.0
	 * @param    string    $current_section    	Currently open section
	 */
	function missing_woocommerce_notice( $current_section ){
		$sections = $this->get_sections( 'recovery' );
		$name = '';
		foreach ($sections as $key => $section) { //Getting integration name from array
			if($key == $current_section){
				$name = $section['name'];
			}
		}
		echo '<p class="cartbounty-description">' . sprintf(
			/* translators: %s - Gets replaced by an integration name, e.g. MailChimp */
			esc_html__('Not so fast, sailor! You must enable WooCommerce before we can set sail or this %s boat gets us nowhere.', 'woo-save-abandoned-carts'), esc_html( $name ) ) . '</p>';
	}

	/**
	 * Method returns visual status indiciator if the item has been connected
	 *
	 * @since    6.0
	 * @return   string
	 * @param    boolean    $connected    	Whether the item is connected or not
	 * @param    boolean    $text    		Should the text be displayed or just the green status
	 * @param    string    	$tab    		Tab section, used to determine which type of text should be returned
	 */
	function get_connection( $connected, $text, $tab ){
		$status = '';
		if($connected){
			if($text){
				$text = esc_html__('Enabled', 'woo-save-abandoned-carts');

				if($tab == 'recovery'){
					$text = esc_html__('Connected', 'woo-save-abandoned-carts');
				}
				
			}
			$status = '<em class="cartbounty-connection">'. esc_html( $text ) .'</em>';
		}
		return $status;
	}

	/**
	* Check if WooCommerce Action scheduler library exists
	*
	* @since    7.1.6
	* @return   boolean
	*/
	function action_scheduler_enabled(){
		$status = false;

		if( class_exists( 'ActionScheduler_Store' ) ){
			$status = true;
		}

		return $status;
	}

	/**
	 * Schedules WordPress events
	 * By default trying to use WooCommerce Action Scheduler library to schedule events.
	 * Documentation: https://actionscheduler.org/api/
	 * Fallback to WP Cron
	 * Moved outside of Plugin activation class in v.9.4 since there were many ocurances when events were not scheduled after plugin activation
	 *
	 * @since    1.1
	 */
	function schedule_events(){
		$notification_frequency = $this->get_settings( 'settings', 'notification_frequency' );

		if ( empty( $notification_frequency ) ){ //Making sure that if the value is an empty string, we do not get a fatal error
			$notification_frequency = 0;
		}

		$hooks = array(
			'cartbounty_sync_hook' 						=> array( 
				'interval'			=> 5 * 60, //Every 5 minutes
				'wp_cron_interval'	=> 'cartbounty_sync_interval',
				'enabled'			=> true
			),
			'cartbounty_remove_empty_carts_hook' 	=> array(
				'interval'			=> 12 * 60 * 60, //Twice Daily
				'wp_cron_interval'	=> 'cartbounty_twice_daily_interval',
				'enabled'			=> true
			),
			'cartbounty_notification_sendout_hook' 	=> array(
				'interval'			=> $notification_frequency / 1000,
				'wp_cron_interval'	=> 'cartbounty_notification_sendout_interval',
				'enabled'			=> ( $notification_frequency == 0 ) ? false : true
			)
		);

		foreach( $hooks as $hook_name => $hook_data ){

			if( $this->action_scheduler_enabled() ){ //Check if WooCommerce Action scheduler library exists

				if( $hook_data['enabled'] ){ //If action should be scheduled

					if ( !as_next_scheduled_action( $hook_name ) ){ //Validate if action has not already been scheduled
						as_schedule_recurring_action( time(), $hook_data['interval'], $hook_name, array(), CARTBOUNTY );
					}

				}else{ //Unschedule action
					as_unschedule_action( $hook_name, array(), CARTBOUNTY );
				}

				wp_clear_scheduled_hook( $hook_name ); //Removing any WP Cron events in case they were scheduled previously

			}else{ //Falling back to using WP Cron in case Action Scheduler not available
				
				if( $hook_data['enabled'] ){ //If action should be scheduled

					if ( !wp_next_scheduled( $hook_name ) ){ //Validate if action has not already been scheduled
						wp_schedule_event( time(), $hook_data['wp_cron_interval'], $hook_name );
					}

				}else{ //Unschedule action
					wp_clear_scheduled_hook( $hook_name );
				}
			}
		}
	}

	/**
	* Unschedule email notification sendout hook in case the interval gets changed and Action Scheduler is being used
	*
	* @since    7.1.6
	*/
	function unschedule_notification_sendout_hook(){

		if( $this->action_scheduler_enabled() ){
			as_unschedule_action( 'cartbounty_notification_sendout_hook', array(), CARTBOUNTY );
		}
	}

	/**
	 * Method adds additional intervals to default WordPress cron intervals (hourly, twicedaily, daily). Interval provided in minutes
	 *
	 * @since    3.0
	 * @param    array    $intervals    Existing interval array
	 */
	function add_custom_wp_cron_intervals( $intervals ){
		$notification_frequency = $this->get_interval_data( 'cartbounty_main_settings[notification_frequency]' );
		$interval_name = $this->prepare_time_intervals( $notification_frequency['interval'], $zero_value = '', 'cartbounty_main_settings[notification_frequency]' );
		$interval = $notification_frequency['selected'];

		if( !is_numeric( $interval ) ){
			$interval = 3600000;
		}

		$intervals['cartbounty_notification_sendout_interval'] = array( //Defining cron Interval for sending out email notifications about abandoned carts
			'interval' 	=> $interval / 1000,
			'display' 	=> $interval_name[$interval]
		);

		$intervals['cartbounty_sync_interval'] = array( //Defining cron Interval for sending out abandoned carts
			'interval' 	=> 5 * 60,
			'display' 	=> 'Every 5 minutes'
		);

		$intervals['cartbounty_twice_daily_interval'] = array(
			'interval' 	=> 12 * 60 * 60,
			'display' 	=> 'Twice Daily'
		);

		return $intervals;
	}

	/**
	 * Output admin notices
	 *
	 * @since    6.1.2
	 */
	function display_notices(){
		$this->display_wp_cron_warnings();
		$this->display_unsupported_plugin_notice();
	}

	/**
	 * Method shows warnings if any of the WP Cron events not scheduled or if the WP Cron has been disabled
	 *
	 * @since    4.3
	 */
	function display_wp_cron_warnings(){

		if( $this->action_scheduler_enabled() ) return; //Do not display WP Cron related messages in case WooCommerce Action scheduler is enabled

		$wordpress = $this->wordpress();

		if( $wordpress->automation_enabled() ){ //Check if we have connected to WordPress automation
			$missing_hooks = array();
			$notification_frequency = $this->get_settings( 'settings', 'notification_frequency' );

			if( wp_next_scheduled( 'cartbounty_notification_sendout_hook' ) === false && $notification_frequency != 0 ){ //If we havent scheduled email notifications and notifications have not been disabled
				$missing_hooks[] = 'cartbounty_notification_sendout_hook';
			}

			if( wp_next_scheduled( 'cartbounty_sync_hook' ) === false ){
				$missing_hooks[] = 'cartbounty_sync_hook';
			}

			if ( !empty($missing_hooks ) ) { //If we have hooks that are not scheduled
				$hooks = '';
				$current = 1;
				$total = count( $missing_hooks );
				
				foreach( $missing_hooks as $missing_hook ){
					$hooks .= $missing_hook;
					
					if ( $current != $total ){
						$hooks .= ', ';
					}
					$current++;
				}
				$message = sprintf(
					/* translators: %s - Cron event name */
					wp_kses( _n( 'It seems that WP Cron event <strong>%s</strong> required for automation is not scheduled.', 'It seems that WP Cron events <strong>%s</strong> required for automation are not scheduled.', esc_html( $total ), 'woo-save-abandoned-carts' ), 'data' ), esc_html( $hooks ) ) . ' ' .
					sprintf(
					/* translators: %1$s - Plugin name, %2$s - Link start, %3$s - Link end */
					esc_html__( 'Please try disabling and enabling %1$s plugin. If this notice does not go away after that, please %2$sget in touch with us%3$s.', 'woo-save-abandoned-carts' ), esc_html( CARTBOUNTY_ABBREVIATION ), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_SUPPORT_LINK, 'wp_cron_disabled' ) ) .'" target="_blank">', '</a>' );
				echo $this->get_notice_output( $message, $handle = '', 'warning' );
			}
		}

		//Checking if WP Cron is enabled
		if( defined( 'DISABLE_WP_CRON' ) ){
			
			if( DISABLE_WP_CRON == true ){
				$handle = 'cron_warning';
				$message = esc_html__( "WP Cron has been disabled. Several WordPress core features, such as checking for updates or sending notifications utilize this function. Please enable it or contact your system administrator to help you with this.", 'woo-save-abandoned-carts' );
				echo $this->get_notice_output( $message, $handle, $class = 'warning', true, 'close' );
			}
		}
	}

	/**
	 * Method shows notice in case there are active plugins which the free CartBounty version does not support
	 *
	 * @since    7.1.2.5
	 */
	function display_unsupported_plugin_notice(){
		$active_unsupported_plugins = array();
		$unsupported_plugins = array(
			array( //Extra Product Options for WooCommerce (Free) by ThemeHigh
				'path' => 'woo-extra-product-options/woo-extra-product-options.php',
				'name' => 'Extra Product Options for WooCommerce'
			),
			array( //Extra Product Options for WooCommerce by ThemeHigh
				'path' => 'woocommerce-extra-product-options-pro/woocommerce-extra-product-options-pro.php',
				'name' => 'Extra Product Options for WooCommerce'
			),
			array( //WooCommerce Custom Product Addons (Free) by Acowebs
				'path' => 'woo-custom-product-addons/start.php',
				'name' => 'WooCommerce Custom Product Addons'
			),
			array( //WooCommerce Custom Product Addons by Acowebs
				'path' => 'woo-custom-product-addons-pro/start.php',
				'name' => 'WooCommerce Custom Product Addons'
			),
			array( //Advanced Product Fields for WooCommerce (Free) by StudioWombat
				'path' => 'advanced-product-fields-for-woocommerce/advanced-product-fields-for-woocommerce.php',
				'name' => 'Advanced Product Fields for WooCommerce'
			),
			array( //Advanced Product Fields for WooCommerce by StudioWombat
				'path' => 'advanced-product-fields-for-woocommerce-pro\advanced-product-fields-for-woocommerce-pro.php',
				'name' => 'Advanced Product Fields for WooCommerce'
			),
			array( //WooCommerce Product Bundles by SomewhereWarm
				'path' => 'woocommerce-product-bundles/woocommerce-product-bundles.php',
				'name' => 'WooCommerce Product Bundles'
			)
		);

		foreach ( $unsupported_plugins as $key => $plugins ) {
			
			if( is_plugin_active( $plugins['path'] ) ){
				$active_unsupported_plugins[] = '<strong>' . $plugins['name'] . '</strong>';
			}
		}

		$active_unsupported_plugins = array_unique( $active_unsupported_plugins ); //Removing duplicate items from array

		if( !empty( $active_unsupported_plugins ) ){
			$handle = 'cartbounty_unsupported_plugin_notice';
			$total = count( $active_unsupported_plugins );
			$unsupported_plugins_list = implode( ' ' . __('and') . ' ', array_filter( array_merge( array( implode( ', ', array_slice( $active_unsupported_plugins, 0, -1 ) ) ), array_slice( $active_unsupported_plugins, -1 ) ), 'strlen')); //Join plugins with commas and the last two elements with "and"
			$message = sprintf(
				/* translators: %s - Single or multiple plugin names */
				wp_kses( _n( 'You are using %s. The Free version of %s does not support saving all options this plugin adds.', 'You are using %s. The Free version of %s does not support saving all options these plugins add.', esc_html( $total ), 'woo-save-abandoned-carts' ), 'data' ), $unsupported_plugins_list, esc_html( CARTBOUNTY_ABBREVIATION ) ) . '<br/>' . $this->display_unavailable_notice( 'unsupported_plugins' );
			echo $this->get_notice_output( $message, $handle, 'notice', true, 'close', $user_specific = true );
		}
	}

	/**
	 * Handling notice output
	 *
	 * @since    6.1.2
	 * @return   string
	 * @param    string   	$message   		Content of the message
	 * @param    string   	$handle   		Unique ID for the notice. Default empty
	 * @param    string   	$class   		Additional classes required for the notice. Default empty
	 * @param    boolean   	$submit   		Should a submit button be added or not. Default false
	 * @param    string   	$button_type   Type of the button (done or close). Default done
	 * @param    boolean   	$user_specific  Weather notice is saved in the user or global site level
	 */
	function get_notice_output( $message, $handle = '', $class = '', $submit = false, $button_type = 'done', $user_specific = false ){
		$closed = false;

		if( $user_specific ){

			if( isset( $_GET[$handle] ) ){ //Check if we should update the option and close the notice
				check_admin_referer( 'cartbounty-notice-nonce', $handle ); //Exit in case security check is not passed
				update_user_meta( get_current_user_id(), $handle, 1 );
			}
			$closed = get_user_meta( get_current_user_id(), $handle, false );

		}else{

			$notice_options = $this->get_settings( 'submitted_warnings' );

			if( isset( $_GET[$handle] ) ){ //Check if we should update the option and close the notice
				check_admin_referer( 'cartbounty-notice-nonce', $handle ); //Exit in case security check is not passed
				$notice_options[$handle] = true;
				update_option( 'cartbounty_submitted_warnings', $notice_options );
			}

			if( isset( $notice_options[$handle] ) ){
				$closed = $notice_options[$handle];
			}
		}

		if( $closed ){ //Exit if notice previously has been already closed
			return;
		}

		$button = false;
		$button_text = esc_html__( 'Done', 'woo-save-abandoned-carts' );
		
		if( $button_type == 'close' ){
			$button_text = esc_html__( 'Close', 'woo-save-abandoned-carts' );
		}

		if( $submit ){
			$button = '<span class="cartbounty-button-container"><a class="cartbounty-close-header-notice cartbounty-button button button-secondary cartbounty-progress" href="'. esc_url( wp_nonce_url( add_query_arg( 'handle', $handle ), 'cartbounty-notice-nonce', $handle ) ) .'">'. esc_html( $button_text ) .'</a></span>';
		}

		$output = '<div class="cartbounty-notification notice updated '. esc_attr( $class ) .'">
			<p>'. $message .'</p>'. $button .'
		</div>';

		return $output;
	}

	/**
	 * Method for sending out email notification in order to notify about new abandoned carts and recovered
	 *
	 * @since    7.0
	 */
	function send_email(){
		$exclude_recovered_carts = $this->get_settings( 'settings', 'exclude_recovered' );
		$this->prepare_email( 'recoverable' );

		if( !$exclude_recovered_carts ){ //If we do not exclude recovered carts from emails
			$this->prepare_email( 'recovered' );
		}
	}

	/**
	 * Method prepares and sends the email
	 *
	 * @since    7.0
	 * @param    string   	$type   	The type of notification email
	 */
	private function prepare_email( $type ){
		global $wpdb;
		$public = $this->public();
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time_intervals = $this->get_time_intervals();
		$time = $time_intervals['cart_abandoned'];
		$where_sentence = $this->get_where_sentence( $type );
		$cart_count = false;

		if($type == 'recovered'){
			$time = $time_intervals['cart_recovered'];
		}

		//Retrieve from database rows that have not been emailed and are older than 60 minutes
		$cart_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id)
				FROM $cart_table
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				time < %s",
				0,
				$time
			)
		);
		
		if(!$cart_count){
			return;
		}

		$to = esc_html(get_option( 'admin_email' ));
		$user_settings_email = $this->get_settings( 'settings', 'notification_email' ); //Retrieving email address if the user has entered one

		if(!empty($user_settings_email)){
			$to = esc_html($user_settings_email);
			$to_without_spaces = str_replace(' ', '', $to);
			$to = explode(',', $to_without_spaces);
		}
		
		$sender = 'WordPress@' . preg_replace( '#^www.#', '', $this->get_current_domain_name() );
		$from = "From: ". esc_html( CARTBOUNTY_ABBREVIATION ) ." <" . apply_filters( 'cartbounty_from_email', esc_html( $sender ) ) . ">";
		$blog_name = get_option( 'blogname' );
		$admin_link = get_admin_url() .'admin.php?page='. CARTBOUNTY;

		if($type == 'recovered'){ //In case if we are sending notification email about newly recovered carts
			$subject = '['.$blog_name.'] '. esc_html( _n('Bounty! Cart recovered! 🤟', 'Bounty! Carts recovered! 🤑', $cart_count, 'woo-save-abandoned-carts') );
			$heading = esc_html( _n('Cart recovered! 🤟', 'Carts recovered! 🤑', $cart_count, 'woo-save-abandoned-carts') );
			$content = sprintf(
			/* translators: %1$d - Abandoned cart count, %2$s - Plugin name */
			esc_html( _n('Excellent, you have recovered an abandoned cart using %2$s.', 'Amazing, you have recovered %1$d abandoned carts using %2$s.', $cart_count, 'woo-save-abandoned-carts') ), esc_html( $cart_count ), esc_html( CARTBOUNTY_ABBREVIATION ) );
			$content .= ' ' . sprintf(
			/* translators: %s - Link tags */
			esc_html__('Please use %sthis link%s to see full information about your carts.', 'woo-save-abandoned-carts'), '<a href="' . esc_url( $admin_link ) . '">', '</a>');
			$button_color = '#20bca0';

		}else{
			$subject = '['.$blog_name.'] '. esc_html( _n('New abandoned cart saved! 🛒', 'New abandoned carts saved! 🛒', $cart_count, 'woo-save-abandoned-carts') );
			$heading = esc_html( _n('New abandoned cart!', 'New abandoned carts!', $cart_count, 'woo-save-abandoned-carts') );
			$content = sprintf(
			/* translators: %1$d - Abandoned cart count, %2$s - Plugin name */
			esc_html( _n('Great, you have saved a new recoverable abandoned cart using %2$s.', 'Congratulations, you have saved %1$d new recoverable abandoned carts using %2$s.', $cart_count, 'woo-save-abandoned-carts') ), esc_html( $cart_count ), esc_html( CARTBOUNTY_ABBREVIATION ) );
			$content .= ' ' . sprintf(
			/* translators: %s - Link tags */
			esc_html__('Please use %sthis link%s to see full information about your carts.', 'woo-save-abandoned-carts'), '<a href="' . esc_url( $admin_link ) . '">', '</a>');
			$button_color = '#aa88fc';
		}

		$main_color = '#ffffff';
		$text_color = '#000000';
		$background_color = '#f2f2f2';
		$footer_color = '#353535';
		$border_color = '#e9e8e8';
		$get_pro_text = sprintf(
		/* translators: %s - Link tags */
		esc_html__('Get %sCartBounty Pro%s to enable cart data preview above.', 'woo-save-abandoned-carts'), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'enable_admin_email_contents' ) ) .'">', '</a>');

		$example_items = array(
			plugins_url( 'assets/admin-notification-email-product-image-1.png', __FILE__ ),
			plugins_url( 'assets/admin-notification-email-product-image-2.png', __FILE__ ),
			plugins_url( 'assets/admin-notification-email-product-image-3.png', __FILE__ ),
			plugins_url( 'assets/admin-notification-email-product-image-4.png', __FILE__ ),
			plugins_url( 'assets/admin-notification-email-product-image-5.png', __FILE__ ),
			plugins_url( 'assets/admin-notification-email-product-image-6.png', __FILE__ )
		);

		$args = array(
			'main_color'			=> $main_color,
			'button_color'			=> $button_color,
			'text_color'			=> $text_color,
			'background_color'		=> $background_color,
			'footer_color'			=> $footer_color,
			'border_color'			=> $border_color,
			'heading'				=> $heading,
			'content'				=> $content,
			'example_items'			=> $example_items,
			'total_1'				=> $this->format_price('320'),
			'total_2'				=> $this->format_price('4820'),
			'total_3'				=> $this->format_price('51'),
			'carts_link'			=> $admin_link,
			'get_pro_text'			=> $get_pro_text
		);

		ob_start();
		echo $public->get_template( 'cartbounty-admin-email-notification.php', $args, false, plugin_dir_path( __FILE__ ) . '../templates/emails/');
		$message = ob_get_contents();
		ob_end_clean();

		$headers = "$from\n" . "Content-Type: text/html; charset=\"" . esc_attr( get_option('blog_charset') ) . "\"\n";
		//Sending out email
		wp_mail( $to, esc_html($subject), $message, $headers );

		//Update mail_sent status to true with mail_status = 0 and are older than 60 minutes
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET mail_sent = %d 
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				time < %s",
				1,
				0,
				$time
			)
		);
	}

	/**
	 * Method retrieves Settings tab url
	 *
	 * @since    1.1
	 */
	public static function get_settings_tab_url(){
		$url = menu_page_url( CARTBOUNTY, false );
		$url = $url . '&tab=settings';
		return $url;
	}

	/**
	 * Method retrieves Carts tab url
	 *
	 * @since    8.0
	 */
	public static function get_carts_tab_url(){
		$url = menu_page_url( CARTBOUNTY, false );
		$url = $url . '&tab=carts';
		return $url;
	}

	/**
	 * Adds custom action link on Plugin page under plugin name
	 *
	 * @since    1.2
	 * @param    $actions        Action
	 * @param    $plugin_file    Location of the plugin
	 */
	function add_plugin_action_links( $actions, $plugin_file ){
		
		if ( !is_array( $actions ) ){
			return $actions;
		}

		$carts_tab = $this->get_carts_tab_url();
		
		$action_links = array();
		$action_links['cartbounty_settings'] = array(
			'label' => esc_html__( 'Carts', 'woo-save-abandoned-carts' ),
			'url'   => $carts_tab
		);
		$action_links['cartbounty_carts'] = array(
			'label' => esc_html__( 'Dashboard', 'woo-save-abandoned-carts' ),
			'url'   => menu_page_url( CARTBOUNTY, false )
		);
		$action_links['cartbounty_get_pro'] = array(
			'label' => esc_html__( 'Get Pro', 'woo-save-abandoned-carts' ),
			'url'   => $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, 'plugin_link' )
		);

		return $this->add_display_plugin_action_links( $actions, $plugin_file, $action_links, 'before' );
	}

	/**
	 * Method that merges the links on Plugin page under plugin name
	 *
	 * @since    1.2
	 * @return   array
	 * @param    $actions        Action
	 * @param    $plugin_file    Location of the plugin
	 * @param    $action_links   Action links - array
	 * @param    $position       Position
	 */
	function add_display_plugin_action_links( $actions, $plugin_file, $action_links = array(), $position = 'after' ){
		static $plugin;
		if ( ! isset( $plugin ) ) {
			$plugin = CARTBOUNTY_BASENAME;
		}
		if ( $plugin === $plugin_file && ! empty( $action_links ) ) {
			foreach ( $action_links as $key => $value ) {
				$link = array( $key => '<a href="' . esc_url( $value['url'] ) . '">' . esc_html( $value['label'] ) . '</a>' );
				if ( 'after' === $position ) {
					$actions = array_merge( $actions, $link );
				} else {
					$actions = array_merge( $link, $actions );
				}
			}
		}
		return $actions;
	}
	
	/**
	 * Method calculates if time has passed since the given time period (In days)
	 *
	 * @since    1.3
	 * @return   Boolean
	 * @param    $option    Option from WordPress database
	 * @param    $days      Number of days
	 */
	function days_have_passed( $option, $days ){
		$result = false;
		$misc_settings = $this->get_settings( 'misc_settings', $option );
		$last_time = strtotime( $misc_settings ); //Convert time from text to Unix timestamp
		
		$date = date_create( current_time( 'mysql', true ) );
		$current_time = strtotime( date_format( $date, 'Y-m-d H:i:s' ) );
		
		if( $last_time < $current_time - $days * 24 * 60 * 60 ){
			$result = true;
		}

		return $result;
	}
	
	/**
	 * Method checks the current plugin version with the one saved in database
	 *
	 * @since    1.4.1
	 */
	function check_version(){
		$misc_settings = $this->get_settings( 'misc_settings' );

		if( CARTBOUNTY_VERSION_NUMBER == $misc_settings['version_number'] ){
			return;

		}else{
			$misc_settings['version_number'] = CARTBOUNTY_VERSION_NUMBER;
			update_option( 'cartbounty_misc_settings', $misc_settings );
			activate_cartbounty();
			return;
		}
	}

	/**
	 * Checks if we have to disable input field or not because of the users access right to save data
	 *
	 * @since    3.0
	 * @param    $options    Options
	 */
	function disable_field( $options = array() ){
		$status = '';
		if($options){
			if($options['forced'] == true){
				$status = 'disabled=""';
			}
		}
		elseif( !$this->user_is_admin() ){
			$status = 'disabled=""';
		}
		return $status;
	}

	/**
	 * Check if notice has been submitted or not
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string    		$notice_type        Notice type
	 */
	function is_notice_submitted( $notice_type ){
		$result = false;
		$submitted_notices = get_option( 'cartbounty_submitted_notices' );

		if( isset( $submitted_notices[$notice_type] ) ){
			$result = true;
		}

		return $result;
	}

	/**
	 * Method outputs bubble content
	 *
	 * @since    1.4.2
	 */
	function output_bubble_content(){
		$tab = $this->get_open_tab();
		$current_section = $this->get_open_section();

		if( $tab == 'carts' ){ //In case we are on Abandoned cart page
			$review_bubble = $this->draw_bubble( 'review' );
			$upgrade_bubble = $this->draw_bubble( 'upgrade' );
			echo $content = $this->prepare_notice( 'review' );
			echo $content = $this->prepare_notice( 'upgrade', false, 'bubble_upgrade' );
			
			if( $upgrade_bubble ){ //If we should display this bubble
				echo $upgrade_bubble;
			
			}elseif( $review_bubble ){ //If we should display this bubble
				echo $review_bubble;

			}

		}elseif( $current_section == 'wordpress' ){
			$steps_bubble = $this->draw_bubble( 'steps' );
			echo $content = $this->prepare_notice( 'steps', false, 'bubble_steps' );
			
			if( $steps_bubble ){ //If we should display this bubble
				echo $steps_bubble;
			}
		}
	}

	/**
	 * Show bubble slide-out window
	 *
	 * @since    1.3
	 * @return   false / HTML
	 * @param    string    	$notice_type        Notice type
	 */
	function draw_bubble( $notice_type ){
		$result = false;

		if( $this->should_display_notice( $notice_type ) ){ //If we should display the bubble
			$notice_id = '#cartbounty-notice-' . $notice_type;
			ob_start(); ?>
			<script>
				jQuery(document).ready(function($) {
					var bubble = $(<?php echo "'". $notice_id ."'"; ?>);
					var close = $('.cartbounty-close, .cartbounty-notice-done');
					
					//Function loads the bubble after a given time period in seconds	
					setTimeout(function() {
						bubble.addClass('cartbounty-show-bubble');
					}, 2500);
				});
			</script>
			<?php
			$result .= ob_get_contents();
			ob_end_clean();
		}

		return $result;
	}

	/**
	 * Handles notice actions (dashboard and bubble notices)
	 *
	 * @since    8.0
	 */
	function handle_notices(){

		if ( check_ajax_referer( 'notice_nonce', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing function
			wp_send_json_error(esc_html__( 'Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ) );
		}

		if( !( isset( $_POST['type'] ) && isset( $_POST['operation'] ) ) ){ //Stop if missing necessary data
			wp_send_json_error(esc_html__( 'Something is wrong.', 'woo-save-abandoned-carts' ) );
		}

		$notice_type = sanitize_text_field( $_POST['type'] );
		$operation = sanitize_text_field( $_POST['operation'] );
		$submitted_notices = get_option( 'cartbounty_submitted_notices' );
		$misc_settings = $this->get_settings( 'misc_settings' );
		
		if( empty( $submitted_notices ) ){
			$submitted_notices = array();
		}

		switch( $notice_type ){
			case 'upgrade':

				if( $operation == 'declined' ){
					$misc_settings['time_bubble_displayed'] = current_time( 'mysql', true );
					update_option( 'cartbounty_misc_settings', $misc_settings ); //Update declined count according to the current level
					wp_send_json_success();

				}
				break;

			case 'steps':

				if( $operation == 'declined' ){
					$misc_settings['time_bubble_steps_displayed'] = current_time( 'mysql', true );
					update_option( 'cartbounty_misc_settings', $misc_settings ); //Update declined count according to the current level
					wp_send_json_success();

				}
				break;

			case 'review':

				if( $operation == 'submitted' ){
					$submitted_notices[$notice_type] = 1;
					update_option( 'cartbounty_submitted_notices', $submitted_notices ); //Update option that the review has been added
					wp_send_json_success();

				}elseif( $operation == 'declined' ){
					$level = $this->get_achieved_review_level();
					
					if( $level > 0 ){
						$misc_settings['times_review_declined'] = $level;
						update_option( 'cartbounty_misc_settings', $misc_settings ); //Update declined count according to the current level
						wp_send_json_success();
					}
				}
				break;
		}
	}

	/**
	 * Returns the count of total captured abandoned carts
	 *
	 * @since    2.1
	 * @return 	 number
	 */
	function total_cartbounty_recoverable_cart_count(){
		$count = $this->get_settings( 'misc_settings', 'recoverable_carts' );
		return $count;
	}

	/**
	 * Retrieves the closest and lowest rounded integer number
	 * If number is 1, return 1
	 * If number is smaller than 5, output number - 1
	 * If number is smaller than 10, output 5
	 * If number is anything else, output lowest integer
	 *
	 * @since    8.0
	 * @return 	 integer
	 */
	function get_closest_lowest_integer( $number ){

		if( empty( $number ) ){
			$number = 0;

		}elseif( $number == 1 ){
			$number = 1;

		}elseif( $number <= 5 ){
			$number = $number - 1;

		}elseif( $number <= 10 ){
			$number = 5;

		}else{
			$number = floor( ( $number - 1 ) / 10 ) * 10;
		}

		return $number;
	}

	/**
	 * Sets the path to language folder for internationalization
	 *
	 * @since    2.1
	 */
	function cartbounty_text_domain(){
		return load_plugin_textdomain( 'woo-save-abandoned-carts', false, basename( plugin_dir_path( __DIR__ ) ) . '/languages' );
	}

	/**
	 * Method removes empty abandoned carts
	 * First removing carts that are anonymous and do not have cart contents
	 * Then deleting recoverable carts without cart contents
	 * Next looking for ordered carts and updating them to ordered-deducted carts and decreasing recoverable cart count
	 * Finally deleting ordered deducted carts from database since they have never really been abandoned (customer placed items, CartBounty saved them and user went through with purchase)
	 *
	 * @since    3.0
	 */
	function delete_empty_carts(){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time = $this->get_time_intervals();
		$public = $this->public();
		$where_sentence = $this->get_where_sentence( 'anonymous' );

		//Deleting anonymous rows with empty cart contents from database first
		$anonymous_cart_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE cart_contents = ''
				$where_sentence AND
				time < %s",
				$time['cart_abandoned']
			)
		);

		$public->decrease_anonymous_cart_count( $anonymous_cart_count );

		//Deleting recoverable abandoned carts without products
		$recoverable_empty_cart_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE cart_contents = '' AND
				time < %s",
				$time['cart_abandoned']
			)
		);

		//Updating ordered carts as ordered-deducted carts
		//This way we can immediately decrease recoverable cart count and leave them with other abandoned carts
		//This can be useful if we would like to search cart history to look if a specific coupon code has been used by a specific email address before
		$ordered_cart_count = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$cart_table}
				SET type = %d
				WHERE type = %d AND
				time < %s",
				$this->get_cart_type( 'ordered_deducted' ),
				$this->get_cart_type( 'ordered' ),
				$time['cart_abandoned']
			)
		);

		$public->decrease_recoverable_cart_count( $recoverable_empty_cart_count + $ordered_cart_count ); //Decreasing recoverable cart count by a number that consists of both ordered cart and recoverable empty cart counts

		//Deleting ordered-deducted carts from database since they have never really been abandoned (user has added an item to cart and placed an order without abandoneding the cart)
		$ordered_deducted_cart_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE type = %d AND
				time < %s",
				$this->get_cart_type( 'ordered_deducted' ),
				$time['maximum_sync_period']
			)
		);
	}

	/**
	 * Method to clear cart data from row
	 *
	 * @since    3.0
	 */
	function clear_cart_data(){
		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if( !isset( WC()->session ) ){
			return;
		}

		if( WC()->session->get( 'cartbounty_order_placed' ) ){ //If user created a new order - do not clear the cart for the first time in case of a quick checkout form submission or else the cart is cleared
			WC()->session->__unset( 'cartbounty_order_placed' );
			return;
		}

		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$public = $this->public();
		$cart = $public->read_cart();

		if( !isset( $cart['session_id'] ) ){
			return;
		}

		//Cleaning Cart data
		$update_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $cart_table
				SET cart_contents = '',
				cart_total = %d,
				currency = %s,
				time = %s
				WHERE session_id = %s AND
				type = %d",
				0,
				sanitize_text_field( $cart['cart_currency'] ),
				sanitize_text_field( $cart['current_time'] ),
				$cart['session_id'],
				$this->get_cart_type('abandoned')
			)
		);
	}

	/**
	 * Resetting abandoned cart data in case if a registered user has an existing abandoned cart and updates his data on his Account page
	 *
	 * @since    5.0.3
	 */
	public function reset_abandoned_cart(){
		if( !is_user_logged_in() ) return; //Exit in case the user is not logged in

		global $wpdb;
		$user_id = 0;

		if( !empty( $_POST['user_id'] ) && is_numeric( $_POST['user_id'] ) ){ //In case the user's data is updated from WordPress admin dashboard "Edit profile page"
			$user_id = $_POST['user_id'];

		}elseif( !empty( $_POST['action'] ) ){ //This check is to prevent profile update to be fired after a new Order is created since no "action" is provided and the user's ID remains 0 and we exit resetting of the abandoned cart
			$user_id = get_current_user_id();
		}

		if( !$user_id ) return; //Exit in case we do not have user's ID value
		
		$public = $this->public();
		$cart = array(
			'session_id' 	=> $user_id,
			'cart_contents' => '',
		);

		if( $public->cart_saved( $cart, $ip_address = '', $ignore_cooldown = true ) ){ //If we have saved an abandoned cart for the user - go ahead and reset in case it has been abandoned or payment is still pending
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$updated_rows = $wpdb->query(
				$wpdb->prepare(
					"UPDATE $cart_table
					SET name = '',
					surname = '',
					email = '',
					phone = '',
					location = '',
					cart_contents = '',
					cart_total = '',
					currency = '',
					time = '',
					other_fields = ''
					WHERE session_id = %s AND
					type != %d",
					$user_id,
					$this->get_cart_type( 'recovered' )
				)
			);
		}
	}

	/**
	 * Method returns different expressions depending on the amount of captured carts
	 *
	 * @since    3.2.1
	 * @return 	 String
	 */
	function get_expressions(){
		if($this->total_cartbounty_recoverable_cart_count() <= 10){
			$expressions = array(
				'exclamation' => esc_html__('Congrats!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 30){
			$expressions = array(
				'exclamation' => esc_html__('Awesome!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 100){
			$expressions = array(
				'exclamation' => esc_html__('Amazing!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 300){
			$expressions = array(
				'exclamation' => esc_html__('Incredible!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 500){
			$expressions = array(
				'exclamation' => esc_html__('Wow!', 'woo-save-abandoned-carts')
			);
		}elseif($this->total_cartbounty_recoverable_cart_count() <= 1000){
			$expressions = array(
				'exclamation' => esc_html__('Fantastic!', 'woo-save-abandoned-carts')
			);
		}else{
			$expressions = array(
				'exclamation' => esc_html__('Insane!', 'woo-save-abandoned-carts')
			);
		}

		return $expressions;
	}

    /**
	 * Method reads GET parameter from the link to restore the cart
	 * At first all products from the cart are removed and then populated with those that were previously saved
	 *
	 * @since    7.0
	 */
	function restore_cart(){

		if( !class_exists( 'WooCommerce' ) ) return;

		global $wpdb;
		$hash = '';
		$id = '';

		//Checking if GET argument is present in the link. If not, exit function
		if (empty( $_GET['cartbounty'] )){
			return;
		}
		
		//Processing GET parameter from the link
		$hash_id = sanitize_text_field($_GET['cartbounty']); //Getting and sanitizing GET value from the link
		$parts = explode('-', $hash_id); //Splitting GET value into hash and ID
		$step_nr = false;

		if( isset( $parts[0] ) ){
			$hash = $parts[0];
		}

		if( isset( $parts[1] ) ){
			$id = $parts[1];
		}

		//Determine recovery step
		if( isset( $_GET['step'] ) ){
			$step_nr = $_GET['step'];
		}

		//Retrieve row from the abandoned cart table in order to check if hashes match
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, email, session_id, cart_contents, cart_meta
				FROM $cart_table
				WHERE id = %d AND
				type != %d",
				$id,
				$this->get_cart_type('recovered')
			)
		);

		if( empty( $row ) ) return; //Exit function if no row found

		//Checking if hashes match
		$row_hash = hash_hmac('sha256', $row->email . $row->session_id, CARTBOUNTY_ENCRYPTION_KEY); //Building encrypted hash from the row

		if( !hash_equals( $hash, $row_hash ) ) return; //If hashes do not match, exit function

		//If we have received an Unsubscribe request - stop restoring cart and unsubscribe user instead
		if( isset( $_GET['cartbounty-unsubscribe'] ) ){
			$wordpress = $this->wordpress();
			$wordpress->unsubscribe_user( $id, $step_nr );
			wp_die( esc_html__('You have successfully unsubscribed from further emails about your shopping cart.', 'woo-save-abandoned-carts'), esc_html__( 'Successfully unsubscribed', 'woo-save-abandoned-carts'), $args = array( 'link_url' => get_site_url(), 'link_text' => esc_html__( 'Return to store', 'woo-save-abandoned-carts') ) );
		}

		$this->build_cart( $row ); //Restore cart with previous products

		//Redirecting user to Checkout page
		$checkout_url = wc_get_checkout_url();
		wp_redirect( $checkout_url, '303' );
		exit();
	}

	/**
	 * Build cart
	 *
	 * @since    7.0.5
	 * @return   boolean
	 * @param    object     $cart   		    Cart data
	 */
	public function build_cart( $cart ){
		
		if( WC()->cart ){ //Checking if WooCommerce has loaded
			WC()->cart->empty_cart();//Removing any products that might have be added in the cart

			$cart_contents = array();
			$cart_meta = array();

			if( isset( $cart->cart_contents ) ){
				$cart_contents = $this->get_saved_cart_contents( $cart->cart_contents );
			}

			if( isset( $cart->cart_meta ) ){
				$cart_meta = $this->get_saved_cart_contents( $cart->cart_meta );
			}
			
			if( !$cart_contents ) return; //If missing products

			foreach( $cart_contents as $product ){ //Looping through cart products
				$custom_meta = array();
				$product_exists = wc_get_product( $product['product_id'] ); //Checking if the product exists
				
				if( $product_exists ){
					$variation_attributes = '';

					if( isset( $product['product_variation_attributes'] ) ){
						$variation_attributes = $product['product_variation_attributes'];

					}elseif( $product['product_variation_id'] ){ //Deprecated since version 10.1.1. Will be removed in future
						$single_variation = new WC_Product_Variation( $product['product_variation_id'] );
						$single_variation_data = $single_variation->get_data();
						$variation_attributes = $single_variation->get_variation_attributes();
					}
					
					foreach( $cart_meta as $key => $meta ){
						if( $meta['product_id'] == $product['product_id'] ){
							$custom_meta = $meta;
						}
					}

					WC()->cart->add_to_cart( $product['product_id'], $product['quantity'], $product['product_variation_id'], $variation_attributes, $custom_meta ); //Adding previous products back to cart
				}
			}

			//Restore previous session id because we want the user abandoned cart data to be in sync
			//Starting session in order to check if we have to insert or update database row with the data from input boxes
			WC()->session->set( 'cartbounty_session_id', $cart->session_id ); //Putting previous customer ID back to WooCommerce session
			WC()->session->set( 'cartbounty_from_link', true ); //Setting a marker that current user arrived from email
		}
	}

    /**
	 * Method edits checkout fields
	 * Tries to move email field higher in the checkout form and insert additional checkout field
	 * Adding consent checkbox field
	 *
	 * @since    4.5
	 * @return 	 Array
	 * @param 	 $fields    Checkout form fields
	 */ 
	public function edit_checkout_fields( $fields ) {
		$lift_email = $this->get_settings( 'settings', 'lift_email' );
		$checkout_consent = $this->get_checkout_consent();
		
		if( $lift_email ){ //Changing the priority and moving the email higher
			if( isset( $fields['billing_email'] ) ){
				$fields['billing_email']['priority'] = 4;
			}
		}

		$consent_data = $this->get_consent_field_data( $value = false, $fields );
		$consent_enabled = $consent_data['consent_enabled'];
		$field_name = $consent_data['field_name'];
		$consent_position = $consent_data['consent_position'];

		if( $consent_enabled ){

			$fields[$field_name] = apply_filters(
				'cartbounty_consent_checkbox_args',
				array(
					'label' 		=> $checkout_consent,
					'type' 			=> 'checkbox',
					'priority' 		=> $consent_position,
					'required' 		=> false,
					'default' 		=> false,
					'clear' 		=> true,
					'class' 		=> array( 'cartbounty-consent' )
				)
			);
		}

		return $fields;
	}

	/**
	 * Retrieve consent field name
	 *
	 * @since    8.4
	 * @return 	 array
	 */
	public function get_consent_field_name() {
		$name = apply_filters( 'cartbounty_consent_email_name', 'billing_email_consent' );
		return $name;
	}

	/**
	 * Retrieve consent field data
	 *
	 * @since    8.4
	 * @return 	 array
	 * @param 	 $value     Value to return
	 * @param 	 $fields    Checkout form fields
	 */
	public function get_consent_field_data( $value = false, $fields = array() ) {
		$consent_settings = $this->get_consent_settings();
		$email_consent_enabled = $consent_settings['email'];
		$field_name = '';
		$consent_enabled = false;
		$consent_position = '';

		if( $email_consent_enabled ){
			$field_name = $this->get_consent_field_name();
			$consent_enabled = true;

			if( isset( $fields['billing_email'] ) ){
				$consent_position = $fields['billing_email']['priority'] + 1;
			}
		}

		$result = array(
			'field_name' 		=>	$field_name,
			'consent_enabled' 	=>	$consent_enabled,
			'consent_position' 	=>	$consent_position,
		);

		if( $value ){ //If a single value should be returned
			
			if( isset( $result[$value] ) ){ //Checking if value exists
				$result = $result[$value];
			}
		}

		return $result;
	}

	/**
	 * Retrieve customer's saved consent field value
	 *
	 * @since    8.4
	 * @return 	 boolean
	 * @param    boolean     $saved_cart    		  Customer's abandoned cart data
	 */
	public function get_customers_consent( $saved_cart = false ){
		$consent = false;

		if( !$saved_cart ){
			$public = $this->public();
			$saved_cart = $public->get_saved_cart();
		}
		
		$get_consent_field_data = $this->get_consent_field_data( 'field_name' );
		$email_consent_field_name = $this->get_consent_field_name();

		if( $get_consent_field_data == $email_consent_field_name && $saved_cart->email_consent ){
			$consent = true;
		}

		return $consent;
	}

	/**
	 * Method prepares and returns an array of different time intervals used for calculating time subtractions
	 *
	 * @since    4.6
	 * @return 	 Array
	 * @param    integer     $interval    		  	  Time interval that has to be waited for in milliseconds
	 * @param    boolean     $first_step    		  Whether function requested during the first step of WordPress automation. Default false
	 */
	public function get_time_intervals( $interval = false, $first_step = false ){
		$waiting_time = $this->get_waiting_time();

		if($first_step){ //In case if we need to get WordPress first automation step time interval, we must add additional time the cart was waiting to be recognized as abandoned
			$interval = $interval + $waiting_time;
		}

		$interval = $this->convert_milliseconds_to_minutes( $interval );

		//Calculating time intervals
		$datetime = current_time( 'mysql', true );
		$date_format = 'Y-m-d H:i:s';

		return array(
			'cart_abandoned' 			=> date( $date_format, strtotime( '-' . $waiting_time . ' minutes', strtotime( $datetime ) ) ),
			'cart_recovered' 			=> date( $date_format, strtotime( '-30 seconds', strtotime( $datetime ) ) ),
			'old_cart' 					=> date( $date_format, strtotime( '-' . CARTBOUNTY_NEW_NOTICE . ' minutes', strtotime( $datetime ) ) ),
			'ten_minutes' 				=> date( $date_format, strtotime( '-10 minutes', strtotime( $datetime ) ) ),
			'two_hours' 				=> date( $date_format, strtotime( '-2 hours', strtotime( $datetime ) ) ),
			'day' 						=> date( $date_format, strtotime( '-1 day', strtotime( $datetime ) ) ),
			'week' 						=> date( $date_format, strtotime( '-7 days', strtotime( $datetime ) ) ),
			'wp_step_send_period' 		=> date( $date_format, strtotime( '-' . $interval . ' minutes', strtotime( $datetime ) ) ),
			'maximum_sync_period'		=> date( $date_format, strtotime( '-' . CARTBOUNTY_MAX_SYNC_PERIOD . ' days', strtotime( $datetime ) ) )
		);
	}

	/**
	 * Getting time period during which the cart is not considered abandoned - the customer is presumed to be shopping
	 *
	 * @since    6.1.3
	 * @return 	 Number
	 */
	public function get_waiting_time(){
		$default_waiting_time = 60; //In minutes. Defines the time period after which an email notice will be sent to admin and the cart is presumed abandoned
		$waiting_time = apply_filters( 'cartbounty_waiting_time', $default_waiting_time );
		
		if($waiting_time < 20){ //Making sure minimum waiting period is not less than 20 minutes
			$waiting_time = 20;
		}
		
		return $waiting_time;
	}

	/**
	 * Method counts carts in the selected category
	 *
	 * @since    5.0
	 * @return   number
	 */
	function get_cart_count( $cart_status ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$total_items = 0;
		$where_sentence = $this->get_where_sentence($cart_status);

		$total_items = $wpdb->get_var(
			"SELECT COUNT(id)
			FROM $cart_table
			WHERE cart_contents != ''
			$where_sentence"
		);

		return $total_items;
	}

	/**
	 * Method returns if anonymous carts are excluded
	 *
	 * @since    7.2
	 * @return   boolean
	 */
	function anonymous_carts_excluded(){
		$exclude = false;

		if( $this->get_settings( 'settings', 'exclude_anonymous_carts' ) ){
			$exclude = true;
		}

		return $exclude;
	}

	/**
     * Method displays available cart type filters
     *
     * @since    5.0
     * @return   string
     * @param 	 $cart_status    Currently filtered cart status
     * @param 	 $tab    		 Currently open tab
     */
	function display_cart_statuses( $cart_status, $tab ){
		$exclude = $this->anonymous_carts_excluded();
		$cart_types = array(
			'all' 			=> esc_html__('All', 'woo-save-abandoned-carts'),
			'recoverable' 	=> esc_html__('Recoverable', 'woo-save-abandoned-carts'),
			'anonymous' 	=> esc_html__('Anonymous', 'woo-save-abandoned-carts'),
			'recovered' 	=> esc_html__('Recovered', 'woo-save-abandoned-carts')
		);

		$output = '<ul id="cartbounty-cart-statuses" class="subsubsub">';
		$counter = 0;
		
		foreach( $cart_types as $key => $type ){
			$counter++;
			$divider = '<em>|</em>';
			$count = $this->get_cart_count($key);

			if( $counter == 1 ){ //Do not output vertical line before the first item
				$divider = '';
			}

			$class = ( $key == $cart_status ) ? 'current' : '';

			if( $count != 0 ){//Do not display empty categories

				if( !( $key == 'anonymous' && $exclude ) ){ //If we are not processing anonymous carts and they have not been excluded
					$url = '?page="'. esc_attr( CARTBOUNTY ) .'"&tab='. esc_attr( $tab ) .'&cart-status='. esc_attr( $key );
					$output .= "<li>". wp_kses( $divider, 'data' ) ."<a href='". esc_url( $url ) ."' title='". esc_attr( $type ) ."' class='". esc_attr( $class ) ."'>". esc_html( $type ) ." <span class='count'>(". esc_html( $count ) .")</span></a></li>";
				}
			}
		}

		$output .= '</ul>';
		echo $output;
	}

    /**
     * Method for creating SQL query depending on different post types 
     * If cart object provided - validate its status e.g. if it is recovered, anonymous etc. (developed for use by reports)
     *
     * @since    5.0
     * @return   string / boolean
     * @param    integer    $cart_status    		 Currently filtered cart status
     * @param    boolean    $starting_and    		 If the query should start with AND or not
     * @param    object     $cart					 Abandoned cart object
     */
	function get_where_sentence( $cart_status, $starting_and = true, $cart = null ){
		$where_sentence = '';
		$cart_validation_result = false;

		if( $cart_status == 'recoverable' ){

			if( $cart ){
				//If all of these conditions are true - $cart_validation_result will be true
				$cart_validation_result = ( !empty( $cart->email ) || !empty( $cart->phone ) )
				&& $cart->type != $this->get_cart_type( 'recovered' )
				&& $cart->type != $this->get_cart_type( 'ordered' )
				&& $cart->type != $this->get_cart_type( 'ordered_deducted' );

			}else{
				$where_sentence = "AND (email != '' OR phone != '') AND type != ". $this->get_cart_type( 'recovered' ) ." AND type != " . $this->get_cart_type( 'ordered' ) ." AND type != " . $this->get_cart_type( 'ordered_deducted' );
			}

		}elseif($cart_status == 'anonymous'){

			if( $cart ){
				//If all of these conditions are true - $cart_validation_result will be true
				$cart_validation_result = empty( $cart->email )
				&& empty( $cart->phone )
				&& $cart->type != $this->get_cart_type( 'recovered' )
				&& $cart->type != $this->get_cart_type( 'ordered' )
				&& $cart->type != $this->get_cart_type( 'ordered_deducted' );

			}else{
				$where_sentence = "AND ((email IS NULL OR email = '') AND (phone IS NULL OR phone = '')) AND type != ". $this->get_cart_type( 'recovered' ) ." AND type != " . $this->get_cart_type( 'ordered' ) ." AND type != " . $this->get_cart_type( 'ordered_deducted' );
			}

		}elseif($cart_status == 'recovered'){

			if( $cart ){
				$cart_validation_result = $cart->type == $this->get_cart_type( 'recovered' );

			}else{
				$where_sentence = "AND type = ". $this->get_cart_type( 'recovered' );
			}

		}elseif( $cart_status == 'ordered' ){

			if( $cart ){
				$cart_validation_result = (
					$cart->type == $this->get_cart_type( 'recovered' )
					|| $cart->type == $this->get_cart_type( 'ordered' )
					|| $cart->type == $this->get_cart_type( 'ordered_deducted' )
				);

			}else{
				$where_sentence = "AND (type = ". $this->get_cart_type( 'recovered' ) ." OR type = ". $this->get_cart_type( 'ordered' ) ." OR type = ". $this->get_cart_type( 'ordered_deducted' ) .")";
			}

		}elseif( $cart_status == 'abandoned' ){

			if( $cart ){
				$cart_validation_result = $cart->type != $this->get_cart_type( 'recovered' )
				&& $cart->type != $this->get_cart_type( 'ordered' )
				&& $cart->type != $this->get_cart_type( 'ordered_deducted' );

			}else{
				$where_sentence = "AND ( type != ". $this->get_cart_type( 'recovered' ) ." AND type != " . $this->get_cart_type( 'ordered' ) ." AND type != " . $this->get_cart_type( 'ordered_deducted' );
			}

		}elseif( $cart_status == 'all' ){ //Used to count the total number of all abandoned carts in the abandoned cart table
			$additional_anonymous_cart_validation = false;

			if( $cart ){

				if( $this->anonymous_carts_excluded() ){ //In case anonymous shopping carts are excluded - do not include them
					$additional_anonymous_cart_validation = ( !empty( $cart->email )
					|| !empty( $cart->phone ) );
				}

				$cart_validation_result = $cart->type != $this->get_cart_type( 'ordered' )
				&& $cart->type != $this->get_cart_type( 'ordered_deducted' )
				&& $additional_anonymous_cart_validation;

			}else{

				if( $this->anonymous_carts_excluded() ){ //In case anonymous shopping carts are excluded - do not include them
					$additional_anonymous_cart_validation = " AND (email != '' OR phone != '')";
				}

				$where_sentence = "AND type != " . $this->get_cart_type( 'ordered' ) ." AND type != " . $this->get_cart_type( 'ordered_deducted' ) . $additional_anonymous_cart_validation;
			}
		}

		if( !empty( $where_sentence ) ){ //Looking if "AND" needs to be removed from the query

			if( $starting_and == false ){

				if( substr( $where_sentence, 0, 4 ) === 'AND ' ){ //If "AND" is at the beginning of the query
					$where_sentence = substr( $where_sentence, 4 ); //Removing "AND" from the start of the query
				}
			}
		}

		//In case we must validate returned carts in a PHP operation (e.g. in reports)
		if( $cart ){
			$where_sentence = $cart_validation_result;
		}

		return $where_sentence;
    }

    /**
	 * Method handles "Force sync" button functionality
	 *
	 * @since    7.0
	 * @return 	 Boolean
	 */
	public function force_sync(){
		$data = $_POST;
		if ( check_ajax_referer( 'force_sync', 'nonce', false ) == false ) { //If the request does not include our nonce security check, stop executing the function
	        wp_send_json_error(esc_html__( 'Sync failed. Looks like you are not allowed to do this.', 'woo-save-abandoned-carts' ));
	    }

	    if($data['integration'] == 'wordpress'){
	    	$wordpress = $this->wordpress();
			$wordpress->auto_send();
	    }
		wp_send_json_success( esc_html__("Sync finished", 'woo-save-abandoned-carts' ));
	}

	/**
	 * Method displays the Force sync button
	 *
	 * @since    7.0
	 * @return 	 String
	 */
	public function display_force_sync_button( $integration ){
		if( !$integration ){
			return;
		}

		$button_name = esc_html__('Force send', 'woo-save-abandoned-carts');
		$nonce = wp_create_nonce( 'force_sync' );
		$button = "<a id='force_sync' class='cartbounty-button button button-secondary' href='#' data-integration='". esc_attr( $integration ) ."' data-nonce='". esc_attr( $nonce ) ."'>". esc_html( $button_name ) ."</a>";
		return $button;
	}

	/**
	 * Method checks if a specific database table has been created
	 *
	 * @since    7.2
	 * @return   boolean
	 * @param    string     $option    		 Database option field name
	 */
	public function table_exists( $option ){
		$exists = true;
		$table_exists = $this->get_settings( 'misc_settings', $option );

		if( empty( $table_exists ) ){
			$exists = false;
		}

		return $exists;
	}

	/**
	 * Method creates abandoned cart URL that is used to restore the shopping cart
	 *
	 * @since    7.0
	 * @return   string
	 * @param    string   	$email			Cart email
	 * @param    string   	$session_id		Cart session ID
	 * @param    integer   	$cart_id		Cart ID
	 */
	public function create_cart_url( $email, $session_id, $cart_id ){
		$cart_url = wc_get_cart_url();
		$hash = hash_hmac( 'sha256', $email . $session_id, CARTBOUNTY_ENCRYPTION_KEY ) . '-' . $cart_id; //Creating encrypted hash with abandoned cart row ID in the end
		return $checkout_url = $cart_url . '?cartbounty=' . $hash;
	}

	/**
	 * Return cart type from cart name
	 *
	 * @since    7.0.8
	 * @return   integer
	 * @param    string    	$status		    Cart status. 
	 * 										0 = abandoned (default),
	 *										1 = recovered,
	 *										2 = order created
	 *										4 = order created and cart deducted from recoverable cart count stats (this type added just to make sure we keep longer ordered abandoned carts in our database to check if a user has used a coupon code in previous orders). Cart has never really been abandoned (user has added an item to cart and placed an order without abandoning the cart)
	 */
	function get_cart_type( $status ){
		if( empty($status) ){
			return;
		}

		$type = 0;

		switch ( $status ) {
			case 'abandoned':

				$type = 0;
				break;

			case 'recovered':

				$type = 1;
				break;

			case 'ordered':

				$type = 2;
				break;

			case 'ordered_deducted':

				$type = 4;
				break;
				
		}
		return $type;
	}

	/**
	 * Method updates cart type and returns updated row count
	 *
	 *
	 * @since    7.0
	 * @return   boolean / integer
	 * @param    string    	$session_id		Session ID
	 * @param    integer   	$type			Cart status type 0 = default, 1 = recovered, 2 = order created
	 */
	function update_cart_type( $session_id, $type ){
		$updated_rows = 0;

		if($session_id){
			global $wpdb;
			$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
			$field = 'session_id';
			$where_value = $session_id;

			$data = array(
				'type = ' . sanitize_text_field($type)
			);

			if( $type == $this->get_cart_type('recovered') ){ //If order should be marked as recovered
				//Increase total
				$data[] = 'mail_sent = 0';
				$public = $this->public();
				$public->increase_recovered_cart_count();
			}

			$data = implode(', ', $data);

			$updated_rows = $wpdb->query(
				$wpdb->prepare(
					"UPDATE $cart_table
					SET $data
					WHERE $field = %s AND
					type != %d",
					$where_value,
					$this->get_cart_type('recovered')
				)
			);
		}

		return $updated_rows;
	}

    /**
	 * Handling abandoned carts in case of a new order is placed.
	 * Removing duplicate abandoned carts with the same email or phone and cart contents.
	 *
	 * @since    5.0.2
	 * @param    integer    $order_id - ID of the order created by WooCommerce
	 */
	function handle_order( $order_id ){

		if( !isset( $order_id ) ) return; //Exit if Order ID is not present
		
		if( !WC()->session ) return; //Exit if WooCommerce session does not exist

		global $wpdb;
		$public = $this->public();
		$recovered = $this->get_cart_type( 'recovered' );
		$ordered = $this->get_cart_type( 'ordered' );
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$time_intervals = $this->get_time_intervals();
		$from_link = false;
		$public->update_logged_customer_id(); //In case user creates an account during checkout process, the session_id changes to a new one so we must update it
			
		$cart = $public->read_cart();
		$session_id = $cart['session_id'];

		if( WC()->session->get( 'cartbounty_from_link' ) ){ //If user has arrived from CartBounty recovery link
			$from_link = true;
		}

		$cart = $wpdb->get_row( //Retrieve latest abandoned cart
			$wpdb->prepare(
				"SELECT *
				FROM $cart_table
				WHERE session_id = %s AND
				cart_contents != '' AND
				time > %s
				ORDER BY time DESC",
				$session_id,
				$time_intervals['maximum_sync_period']
			)
		);

		if( !$cart ) return;

		if( $cart->type == $recovered || $cart->type == $ordered ){ //If cart has been marked recovered or ordered - exit and do not do anything since cart already has been processed and there is nothing else to be done
			return;
		}

		if( $from_link ){ //If user has arrived from CartBounty recovery link
			$type = $recovered;

		}else{
			$type = $ordered;
		}

		$this->update_cart_type( $session_id, $type );
		$this->handle_duplicate_carts( $cart->email, $cart->phone, $cart->cart_contents );
		WC()->session->set( 'cartbounty_order_placed', true ); //Add marker to session since we do not want to clear the abandoned cart saved via CartBounty after it has been ordered. This happened when the order was placed quickly on the Checkout page and the cart update function fired after the order was already placed.
	}

	/**
	 * Handling duplicate abandoned carts in case of a new order is placed.
	 * If duplicates are found, do not delete them, but set type to "ordered" (2).
	 *
	 * @since    8.0
	 * @param    string     $email                  Cart email
	 * @param    string     $phone                  Cart phone
	 * @param    array      $cart_contents          Cart contents
	 */
	function handle_duplicate_carts( $email, $phone, $cart_contents ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		$recent_unpaid_user_carts = $this->get_recent_unpaid_user_carts( $email, $phone );
		$matching_carts = $this->get_matching_cart_contents( $recent_unpaid_user_carts, $cart_contents );

		if( empty( $matching_carts ) ) return;

		$ordered = $this->get_cart_type( 'ordered' );
		$duplicate_cart_ids = array();

		foreach ( $matching_carts as $key => $cart ) {
			$duplicate_cart_ids[] = $key;
		}

		if( empty( $duplicate_cart_ids ) ) return;

		$ids = implode( ', ', $duplicate_cart_ids );
		$placeholders = implode( ', ', array_fill( 0, count( $duplicate_cart_ids ), '%d' ) );

		$result = $wpdb->query( //Update all duplicate carts to type = ordered (2)
			$wpdb->prepare(
				"UPDATE $cart_table
				SET type = %s
				WHERE id IN ($placeholders)",
				array_merge( array( $ordered ), $duplicate_cart_ids )
			)
		);
	}

	/**
	 * Returning recoverable abandoned carts with the same email address or phone number which have not been paid for during the last 7 days
	 *
	 * @since    8.0
	 * @return   array 
	 * @param    string     $email                  Email we are searching for 
	 * @param    string     $phone                  Phone we are searching for 
	 */
	function get_recent_unpaid_user_carts( $email, $phone ){
		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$where_sentence = $this->get_where_sentence( 'recoverable' );
		$time_intervals = $this->get_time_intervals();

		if( !$this->validate_phone_number( $phone ) ){ //If phone number is not valid, considering it empty
			$phone = '';
		}
		
		$carts = $wpdb->get_results( //Get carts with the same email in the last 30 days
			$wpdb->prepare(
				"SELECT *
				FROM $cart_table
				WHERE ((email = %s AND email != '') OR (phone = %s AND phone != ''))
				$where_sentence AND
				time > %s
				ORDER BY time DESC",
				$email,
				$phone,
				$time_intervals['week']
			)
		);

		return $carts;
	}

	/**
	 * Retrieve abandoned carts that have the same contents that are passed for comparing.
	 * Not looking at product variations, quantities or prices - if the product ID values match, we consider it as a duplicate cart which should no longer be reminded about
	 *
	 * @since    8.0
	 * @return   array 
	 * @param    array      $carts                  Abandoned carts
	 * @param    array      $cart_contents          Cart contents that must be compared
	 */
	function get_matching_cart_contents( $carts, $cart_contents ){
		
		if( empty( $carts ) ) return; //Exit if we have no carts

		$cart_contents = $this->get_saved_cart_contents( $cart_contents );
		
		if( !is_array( $cart_contents ) ){ //In case cart contents are not an array - exit
			return;
		}
		
		$ordered_products = array();
		$duplicate_carts = array();

		foreach( $cart_contents as $key => $product ){ //Build ordered product array
			$ordered_products[] = $product['product_id'];
		}

		foreach( $carts as $key => $cart ){ //Build product comparison array for each cart look for duplicates
			$cart_contents_to_compare = $this->get_saved_cart_contents( $cart->cart_contents );
			
			if( is_array( $cart_contents_to_compare ) ){
				$products = array();
				foreach( $cart_contents_to_compare as $key => $product ){ //Build product array we are comparing against

					if( isset( $product['product_id'] )){
						$products[] = $product['product_id'];
					}
				}

				sort( $ordered_products );
			    sort( $products );

				if( $ordered_products == $products ){ //Comparing arrays
					$duplicate_carts[$cart->id] = $cart; //Cart is a duplicate, must add to duplicates array
				}
			}
		}

		return $duplicate_carts;
	}

	/**
	 * Adds one or more classes to the body tag in the dashboard
	 *
	 * @since    6.0
	 * @return   String    Altered body classes
	 * @param    String    $classes    Current body classes
	 */
	function add_cartbounty_body_class( $classes ) {
		global $cartbounty_admin_menu_page;
		$screen = get_current_screen();
	    if(is_object($screen) && $screen->id == $cartbounty_admin_menu_page){ //If we are on CartBounty page
			$classes = $classes .' '.  CARTBOUNTY_PLUGIN_NAME_SLUG .' ';
		}
	    return $classes;
	}

	/**
	 * Returns a link with tracking information
	 *
	 * @since    6.1.2
	 * @return   String
	 * @param    String    $url        URL
	 * @param    String    $medium     Determines where the button was clicked from. Default none
	 * @param    String    $tag        Hashtag to a specific section in the document. Default none
	 */
	function get_trackable_link( $url, $medium = '', $tag = '' ) {
		return $url .'?utm_source='. urlencode(get_bloginfo("url")) .'&utm_medium='. esc_attr( $medium ) .'&utm_campaign='. esc_attr( CARTBOUNTY ) . esc_attr( $tag );
	}

	/**
	 * Outputs message about upgrade to Pro
	 *
	 * @since    6.0
	 * @param    String    $medium    Determines where the button was clicked from
	 * @return   String    Message
	 */
	function display_unavailable_notice( $medium = false ) {
		$message = sprintf(
			/* translators: %s - URL link tags */
			esc_html__('Please consider upgrading to %sCartBounty Pro%s to enable this feature.', 'woo-save-abandoned-carts'),
			'<a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_LICENSE_SERVER_URL, $medium) ) .'" target="_blank">','</a>');
		return $message;
	}

	/**
	 * Returns abandoned cart product price with or without VAT
	 *
	 * @since    7.0.2
	 * @return   Number
	 * @param    Array or Object    $product          		Cart line item (array) or WooCommerce Product (object)
	 * @param    Boolean  			$force_tax     			Should we bypass all filters and display price with VAT. Default false
	 * @param    Boolean  			$force_exclude_tax     	Weather we should bypass all filters and exclude taxes in the price. Default false
	 */
	function get_product_price( $product, $force_tax = false, $force_exclude_tax = false ) {
		if ( !class_exists( 'WooCommerce' ) ){ //If WooCommerce is not active
			return;
		}

		$tax = 0;
		$price = 0;
		$decimals = 0;

		if( wc_get_price_decimals() ){
			$decimals = wc_get_price_decimals();
		}

		if( is_array( $product ) ){ //In case we are working with CartBounty line item
			$price = $product['product_variation_price'];
			
			if( empty( $price ) ){
				$price = 0;
			}

			if( isset( $product['product_tax'] ) ){ //If tax data exists
				$tax = $product['product_tax'];
			}

		}elseif( is_object( $product ) ){ //In case we are working with WooCommerce product
			$price_with_tax = wc_get_price_including_tax( $product );
			$price = $product->get_price();
			
			if( empty( $price_with_tax ) ){
				$price_with_tax = 0;
			}

			if( empty( $price ) ){
				$price = 0;
			}
			$tax = $price_with_tax - $price;
		}

		if( $force_exclude_tax ){ //If we do not wat to include taxes. E.g., in GetResponse sync
			$tax = 0;
		}

		if( !empty( $tax ) && ( apply_filters( 'cartbounty_include_tax', true ) || $force_tax == true ) ){ //If the tax value is set, the filter is set to true or the taxes are forced to display
			$price = $price + $tax;
		}

		if( empty( $price ) ){ //In case the price is empty
			$price = 0;
		}

		return round( $price, $decimals );
	}

	/**
	* Method sanitizes field
	*
	* @since    7.0.2
	* @return   string
	* @param    string    $field    		  Field that should be sanitized
	*/
	public function sanitize_field( $field ){
		$field = str_replace('"', '', $field);
		return wp_specialchars_decode( sanitize_text_field( wp_unslash( $field ) ), ENT_NOQUOTES );
	}

	/**
     * Returning Tools defaults
     *
     * @since    7.0.6
     * @return   array or string
     * @param    string     $value    		  	  Value to return
     * @param    integer    $tool    		      Tool name
     */
	public function get_tools_defaults( $value = false, $tool = false ){
		switch ( $tool ) {
			case 'exit_intent':

				$defaults = array(
					'heading'		=> esc_html__( 'You were not leaving your cart just like that, right?', 'woo-save-abandoned-carts' ),
					'content'		=> esc_html__( 'Enter your details below to save your shopping cart for later. And, who knows, maybe we will even send you a sweet discount code :)', 'woo-save-abandoned-carts' )
				);

				break;

			case 'early_capture':

				$defaults = array(
					'heading'		=> esc_html__( 'Please enter your details to add this item to your cart', 'woo-save-abandoned-carts' )
				);
			
				break;
		}

		if($value){ //If a single value should be returned
			if(isset($defaults[$value])){ //Checking if value exists
				$defaults = $defaults[$value];
			}
		}

		return $defaults;
	}

	/**
	* Method for outputting price accordingly to the user's selected WooCommerce currency position
	*
	* @since    7.0.6
	* @return   string
	* @param    float      $price    		  	  Price value
	* @param    string     $currency_code   	  Currency code, e.g. EUR, USD
	*/
	public function format_price( $price, $currency_code = false ){
		
		if( !class_exists( 'WooCommerce' ) ) return;

		$decimals = 0;

		if( wc_get_price_decimals() ){
			$decimals = wc_get_price_decimals();
		}

		$price = number_format( ( float )$price, $decimals, '.', '' ); //Format price so there would always be correct number of decimals after comma, e.g. 2.30 instead of 2.3
		$woocommerce_price_format = get_woocommerce_price_format(); //Retrieve the pricing format the user has set
		$currency = $this->get_currency( $currency_code );

		$price = sprintf( apply_filters( 'cartbounty_price_format', $woocommerce_price_format ), $currency, $price );
		return $price;
	}

	/**
	* Return currency from provided currency code
	*
	* @since    9.6
	* @return   string
	* @param    string     $currency_code   	  Currency code, e.g. EUR, USD
	*/
	public function get_currency( $currency_code = false ){
		
		if( !class_exists( 'WooCommerce' ) ) return;

		$currency = get_woocommerce_currency_symbol( $currency_code );

		if( apply_filters( 'cartbounty_display_currency_code', false ) ){ //If currency code display is enabled, display currency code instead of symbol. By default we display currency symbol
			$currency = $currency_code;
		}

		if( empty( $currency ) ){ //If the currency is empty, retrieve default WooCommerce currency ignoring the one saved in the abandoned cart
			$currency = get_woocommerce_currency_symbol();
		}

		return $currency;
	}

	/**
	* Method scans all files inside CartBounty templates folder. 
	* Returns an array of all found files.
	*
	* @since    7.0.7
	* @return   array
	* @param    string    $default_path			Default path to template files.
	*/
	function scan_files( $default_path ){
		$files  = scandir($default_path);
		$result = array();

		if(!empty($files)){
			foreach($files as $key => $value){
				if(!in_array($value, array( '.', '..' ), true)){
					if(is_dir($default_path . '/' . $value)){
						$sub_files = $this->scan_files( $default_path . '/' . $value );
						foreach($sub_files as $sub_file){
							$result[] = $sub_file;
						}
						foreach ($sub_files as $sub_file){
							$result[] = $value . '/' . $sub_file;
						}

					}else{
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	* Get an array of templates that have been overridden
	*
	* @since    7.0.7
	* @return   array
	*/
	function get_template_overrides(){
		$template_path = 'templates/';
		$default_path = plugin_dir_path( __FILE__ ) . '../templates/';
		$override_files = array();
		$scan_files = $this->scan_files( $default_path );

		foreach($scan_files as $file){
			if(file_exists(get_stylesheet_directory() . '/' . $file)){
				$theme_file = get_stylesheet_directory() . '/' . $file;

			}elseif(file_exists(get_stylesheet_directory() . '/' . $template_path . $file)){
				$theme_file = get_stylesheet_directory() . '/' . $template_path . $file;

			}elseif(file_exists(get_template_directory() . '/' . $file)){
				$theme_file = get_template_directory() . '/' . $file;

			}elseif(file_exists(get_template_directory() . '/' . $template_path . $file)){
				$theme_file = get_template_directory() . '/' . $template_path . $file;

			}else{
				$theme_file = false;
			}

			if(!empty($theme_file)){
				$override_files[] = str_replace(WP_CONTENT_DIR . '/themes/', '', $theme_file);
			}
		}
		return $override_files;
	}

	/**
	* Add email badge
	*
	* @since    7.0.7
	* @return   html
	*/
	function add_email_badge(){
		$tag = 'automation_email';
		if( current_filter() == 'cartbounty_admin_email_footer_end' ){ //If the function triggered inside admin notification email
			$tag = 'admin_notification_email';
		}
		$public = $this->public();
		$image = $public->get_plugin_url() . '/public/assets/sent-via-cartbounty.png';
		$output = '';
		$output .= '<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
			<tr>
				<td valign="top">
					<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
						<tr>
							<td valign="top" width="650"  align="center" style="text-align: center;">
								<a href="'. esc_url( $this->get_trackable_link(CARTBOUNTY_LICENSE_SERVER_URL, $tag ) ) .'" style="display: block;">
									<img src="'. esc_url( $image ) .'" alt="Reminded using CartBounty" title="Reminded using CartBounty" width="130" height="auto" style="display:inline; text-align: center; margin: 20px 0 5px; -ms-interpolation-mode: bicubic; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; border: none 0;" />
								</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>';
		echo $output;
	}

	/**
	 * Fire functions on WordPress load
	 *
	 * @since    7.0.7.1
	 */
	function trigger_on_load(){
		$this->restore_cart(); //Restoring abandoned cart if a user returns back from an abandoned cart email link
		$this->validate_cart_deletion(); //Make sure abandoned cart deletion passes nonce security
	}

	/**
	 * Return language code. Taking WordPress locale like 'es_ES' and turning it into 'es'
	 *
	 * @since    8.0
	 * @return   String
	 * @param    String   $locale     WordPress language
	 */
	function get_language_code( $locale ){

		if ( !empty( $locale ) ){
			$language = explode( '_', $locale );
			
			if ( ! empty( $language ) && is_array( $language ) ) {
				$locale = strtolower( $language[0] );
			}
		}

		return $locale;
	}

	/**
	 * Return locale with hyphen instead of default underscore. Taking WordPress locale like 'es_ES' and turning it into 'es-ES'
	 *
	 * @since    8.0
	 * @return   String
	 * @param    String   $locale     WordPress language
	 */
	function get_locale_with_hyphen( $locale ) {
		
		if ( !empty( $locale ) ){
			$locale = str_replace('_', '-', $locale);
		}

		return $locale;
	}

	/**
	 * Output emoji button
	 *
	 * @since    7.1
	 */
	function add_emojis(){
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 67 67"><path d="M33.5,0A33.5,33.5,0,1,0,67,33.5,33.5,33.5,0,0,0,33.5,0Zm0,61.88A28.37,28.37,0,1,1,61.86,33.51,28.37,28.37,0,0,1,33.49,61.88Z"/><path d="M24.23,32a2.55,2.55,0,0,1-2.55-2.55c0-2,0-3.1,0-5.21a2.46,2.46,0,0,1,2.55-2.54,2.51,2.51,0,0,1,2.54,2.57c0,.72,0,5.2,0,5.2A2.56,2.56,0,0,1,24.25,32Z"/><path d="M42.78,32a2.55,2.55,0,0,1-2.54-2.58c0-2.17,0-3.2,0-5.21a2.55,2.55,0,0,1,5.09,0c0,.71,0,5.2,0,5.2A2.55,2.55,0,0,1,42.78,32Z"/><path d="M18.64,43.33a27.92,27.92,0,0,0,5.52,4.35,19.35,19.35,0,0,0,18.52.08,27,27,0,0,0,5.67-4.43c2.54-2.38-1.28-6.2-3.82-3.82a22.36,22.36,0,0,1-4.11,3.33,14,14,0,0,1-6.92,1.84,13.83,13.83,0,0,1-6.77-1.75,22.73,22.73,0,0,1-4.27-3.42c-2.53-2.38-6.36,1.44-3.82,3.82Z"/></svg>';
		$output = "<div class='cartbounty-icon-button-container cartbounty-preview-container'><div class='cartbounty-button button-secondary cartbounty-emoji cartbounty-icon-button button button-preview'>". $icon ."</div>". $this->display_preview_contents( 'emojis' ) ."</div>";
		echo $output;
	}

	/**
	 * Output persnalization tags button
	 *
	 * @since    7.1
	 */
	function add_tags(){
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.56 67.4"><path d="M3.55,67.4c-1.93,0-3.28-.49-3.49-2a18.69,18.69,0,0,1,.22-4.31c.16-1.13.31-2.19.55-3.23,2.55-11,9.08-19,19.39-23.74L22.56,33l-1.68-1.92c-5.08-5.42-6.58-11.6-4.46-18.34S23.34,1.73,30.56.32A17.8,17.8,0,0,1,33.89,0,18.22,18.22,0,0,1,35.5,36.35c-.82.09-1.65.13-2.53.17a28.77,28.77,0,0,0-4.19.39C17.7,39.05,10.2,45.6,6.51,56.37,6.19,57.32,5,62.25,5,62.25H33.45a2.69,2.69,0,0,1,2.28,1.16A2.46,2.46,0,0,1,36,65.74a2.38,2.38,0,0,1-2.14,1.6C33.56,67.37,3.55,67.4,3.55,67.4Zm30-61.91a12.91,12.91,0,0,0-9.14,22,12.74,12.74,0,0,0,9.1,3.79,12.91,12.91,0,0,0,.08-25.82Z"/><path d="M80,46.72H67.12V33.89a2.61,2.61,0,0,0-5.21,0V46.72H49.08a2.61,2.61,0,1,0,0,5.21H61.91V64.76a2.61,2.61,0,1,0,5.21,0V51.93H80a2.61,2.61,0,1,0,0-5.21Z"/></svg>';
		$output = "<div class='cartbounty-icon-button-container cartbounty-preview-container'><div class='cartbounty-button button-secondary cartbounty-tags cartbounty-icon-button button button-preview'>". $icon ."</div>". $this->display_preview_contents( 'personalization' ) ."</div>";
		echo $output;
	}

	/**
	 * Check consent collection settings status
	 *
	 * @since    8.4
	 * @return   boolean
	 * @param    string     $value                Value to return
	 */
	function get_consent_settings( $value = false ){
		$settings = $this->get_settings( 'settings' );
		$consent_settings = array(
			'email' => false,
		);

		if( isset( $settings['email_consent'] ) ){
			$consent_settings['email'] = $settings['email_consent'];
		}

		if( $value ){ //If a single value should be returned
			
			if( isset( $consent_settings[$value] ) ){ //Checking if value exists
				$consent_settings = $consent_settings[$value];
			}
		}

		return $consent_settings;
	}

	/**
	 * Retrieve default consent placeholders
	 *
	 * @since    8.4
	 * @return   array
	 */
	function get_consent_default_placeholders(){
		$email_consent_enabled = false;
		$privacy_policy_url = '';
		$checkout_consent = esc_attr__( 'Get news and offers via email', 'woo-save-abandoned-carts' );
		$tools_consent = esc_attr__( 'By entering your email, you agree to get news and offers via email. You can unsubscribe using a link inside the message.', 'woo-save-abandoned-carts' );
		
		if ( function_exists( 'get_privacy_policy_url' ) ) { //This function is available starting from WP 4.9.6
			$privacy_policy_url = get_privacy_policy_url();
		}
		
		if( !empty( $privacy_policy_url ) ){ //If privacy policy url is available, add it to the default text
			$tools_consent = $tools_consent . ' ' . sprintf(
				/* translators: %s - URL link */
				esc_attr__( 'View %sPrivacy policy%s.', 'woo-save-abandoned-carts' ), '<a href="' . esc_attr( esc_url( $privacy_policy_url ) ) . '" target="_blank">', '</a>'
			);
		}

		return array(
			'checkout_consent' => $checkout_consent,
			'tools_consent' => $tools_consent,
		);
	}

	/**
	 * Get checkout consent value
	 *
	 * @since    8.4
	 * @return   string
	 */
	function get_checkout_consent(){
		$field = array();
		$field = $this->get_defaults( 'checkout_consent' );
		$checkout_consent = $this->get_settings( 'settings', 'checkout_consent' );
		
		if( trim( $checkout_consent ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$field = $this->sanitize_field( $checkout_consent );
		}

		return $field;
	}

	/**
	 * Get tools consent value
	 *
	 * @since    8.4
	 * @return   string
	 */
	function get_tools_consent(){
		$field = $this->get_defaults( 'tools_consent' );
		$tools_consent = $this->get_settings( 'settings', 'tools_consent' );

		if( trim( $tools_consent ) != '' ){ //If the value is not empty and does not contain only whitespaces
			$field = $this->sanitize_field( $tools_consent );
		}
		return $field;
	}

	/**
	* Return preview contents according to feature
	*
	* @since    7.1
	* @return   HTML
	* @param    string    $feature			Feature that has to be displayed
	*/
	function display_preview_contents( $feature ){
		$output = "<div class='cartbounty-preview-contents cartbounty-preview-". $feature ."'>";
		$tracking_label = 'preview_' . $feature;
		$output .= $this->prepare_notice( $feature, false, $tracking_label );
		$output .= "</div>";
		return $output;
	}

	/**
	 * Get product thumbnail
	 *
	 * @since    7.1.2.6
	 * @return   string
	 * @param    object     $product			Abandoned cart product data
	 * @param    string     $size				Image size, default 'woocommerce_thumbnail' 300x300 (with hard crop)
	 */
	function get_product_thumbnail_url( $product, $size = 'woocommerce_thumbnail' ){
		$image = '';

		if( function_exists( 'has_image_size' ) && !has_image_size( 'woocommerce_thumbnail' ) && $size == 'woocommerce_thumbnail' ){ //If 'woocommerce_thumbnail' image requested but it does not exist, fallback to default WordPress image size 'medium'
			$size = 'medium';
		}

		if( !empty( $product['product_variation_id'] ) ){ //In case of a variable product
			$image = get_the_post_thumbnail_url( $product['product_variation_id'], $size );
		}

		if( empty( $image ) ){ //In case of a simple product or if variation did not have an image set
			$image = get_the_post_thumbnail_url( $product['product_id'], $size );
		}

		if( empty( $image ) && class_exists( 'WooCommerce' ) ){ //In case WooCommerce is active and product has no image, output default WooCommerce image
			$image = wc_placeholder_img_src( $size );
		}

		return $image;
	}

	/**
     * Delete cart
     *
     * @since    8.6
     * @return   integer
     * @param    integer     $cart_id   		    Abandoned cart ID
     */
	public function delete_cart( $cart_id ){

		if( !$this->user_is_admin() ){
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'woo-save-abandoned-carts' ) );
		}

		global $wpdb;
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;

		if( empty( $cart_id ) ) return;

		$rows = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE id = %d",
				intval( $cart_id )
			)
		);

		if( !$rows ){
			$rows = 0;
		}

		return $rows;
	}

	/**
	* Returning HTML of product settings
	*
	* @since    7.1.2.8
	* @return   HTML
	*/
	public function display_exclusion_settings(){ ?>
		<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
			<h4><?php esc_html_e('Exclusions', 'woo-save-abandoned-carts'); ?></h4>
			<p class="cartbounty-titles-column-description">
				<?php esc_html_e( 'Exclude from abandoned cart recovery carts containing specific products or categories.', 'woo-save-abandoned-carts' ); ?> <?php echo sprintf(
					/* translators: %1$s - Link start, %2$s - Link end */
					esc_html__( 'Use %1$svarious filters%2$s to exclude carts by language, country etc.', 'woo-save-abandoned-carts' ), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'actions-and-filters', 'custom_exclusion_filters', '#exclude-specific-countries-from-abandoned-cart-recovery' ) ) .'" target="_blank">', '</a>' ); ?>
			</p>
		</div>
		<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
			<div class="cartbounty-settings-group cartbounty-toggle">
				<label for="cartbounty-enable-exclusions" class="cartbounty-switch cartbounty-unavailable">
					<input id="cartbounty-enable-exclusions" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
					<span class="cartbounty-slider round"></span>
				</label>
				<label for="cartbounty-enable-exclusions" class="cartbounty-unavailable"><?php esc_html_e( 'Enable', 'woo-save-abandoned-carts' ); ?></label>
				<p class='cartbounty-additional-information'>
					<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'wp_exclude_settings' ); ?></i>
				</p>
			</div>
		</div>
	<?php }

	/**
	* Convert milliseconds to minutes
	*
	* @since    7.1.6
	* @return   integer
	* @param    integer    $milliseconds    		 A numeric value of milliseconds
	*/
	function convert_milliseconds_to_minutes( $milliseconds ){
		$minutes = 0;

		if( !empty( $milliseconds ) ){
			$minutes = intval( $milliseconds / 60000 );
		}
		
		return $minutes;
	}

	/**
	* Convert minutes to milliseconds
	*
	* @since    7.1.6
	* @return   integer
	* @param    integer    $minutes    		 A numeric value of minutes
	*/
	function convert_minutes_to_milliseconds( $minutes ){
		$milliseconds = 0;

		if( !empty( $minutes ) ){
			$milliseconds = intval( $minutes * 60000 );
		}
		
		return $milliseconds;
	}

	/**
	* Retrieve current domain name
	*
	* @since    7.1.6
	* @return   string
	*/
	function get_current_domain_name(){
		$domain = strtolower( parse_url( get_site_url(), PHP_URL_HOST ) );
		return $domain;
	}

	/**
	* Method for encoding emoji symbols in content input fields
	*
	* @since    7.2
	* @param    string   $item    		 Array value
	* @param    string   $key    		 Array key
	*/
	function encode_emojis( &$item, $key ) {
		$content_fields = array( //Content fields that may include emoji symbols
			'subject',
			'heading',
			'content',
			'checkout_consent',
			'tools_consent',
		);

		if( in_array( $key, $content_fields ) ){ //Encoding only content input fields
			$item = wp_encode_emoji( $item );
		}
	}

	/**
	* Filtering CartBounty options before their values are serialized and saved inside database
	*
	* @since    7.2
	* @return   mixed
	* @param    mixed    $value    		 	 The new, unserialized option value
	* @param    string   $option    		 Name of the option
	* @param    mixed    $old_value    		 The old option value
	*/
	function validate_cartbounty_fields( $value, $option, $old_value ) {
		
		if ( strpos( $option, 'cartbounty_' ) === 0 ) { //Check if the option being updated belongs to CartBounty

			if( is_array( $value ) ){ //If option value is an array
				array_walk_recursive( $value, array( $this, 'encode_emojis' ) );

			}else{
				$this->encode_emojis( $value, $option );
			}
		}

		return $value;
	}

	/**
	* Returning cart location data. A single value or if no $value is specified - returning all location data as an array
	*
	* @since    7.2.1
	* @return   string or array
	* @param    string   $value    		 	 Location value to return, e.g. "country", "city"
	*/
	function get_cart_location( $location_data, $value = false ) {
		$location_array = '';
		$location_value = array( //Setting defaults
			'country' 	=> '',
			'city' 		=> '',
			'postcode' 	=> '',
		);

		if( is_string( $location_data ) ){
			$location_array = maybe_unserialize( $location_data );
		}

		if( is_array( $location_array ) ){ //If unserialization succeeded and we have an array
			
			if( isset( $location_array['country'] ) ){
				$location_value['country'] = $location_array['country'];
			}

			if( isset( $location_array['city'] ) ){
				$location_value['city'] = $location_array['city'];
			}

			if( isset( $location_array['postcode'] ) ){
				$location_value['postcode'] = $location_array['postcode'];
			}

		}

		if( $value ){ //If a single value should be returned
			
			if( isset( $location_value[$value] ) ){ //Checking if value exists
				$location_value = $location_value[$value];
			}

		}

		return $location_value;
	}

	/**
	* Returning instant coupon settings
	*
	* @since    7.3
	* @return   HTML
	* @param    string   $location    		 	 Location where instant coupon settings should be displayed
	*/
	function display_instant_coupon_settings( $location ) {
		$output = '';
		$page_slug = 'exit-intent-popup-technology';

		if( $location == 'early-capture' ){
			$page_slug = 'early-capture-add-to-cart-popup';
		}

		ob_start(); ?>
		<div class="cartbounty-titles-column cartbounty-col-sm-12 cartbounty-col-md-4 cartbounty-col-lg-3">
			<h4><?php esc_html_e('Instant coupon', 'woo-save-abandoned-carts'); ?></h4>
			<p class="cartbounty-titles-column-description">
				<?php echo sprintf(
					/* translators: %s - Link start, %s - Link end */
					esc_html__( 'Provide %sInstant coupon codes%s to motivate customers to complete their purchase. Be sure to mention this in your message title.', 'woo-save-abandoned-carts' ), '<a href="'. esc_url( $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . $page_slug, 'instant_coupons', '#enable-instant-coupons' ) ) .'" target="_blank">', '</a>' ); ?>
			</p>
		</div>
		<div class="cartbounty-settings-column cartbounty-col-sm-12 cartbounty-col-md-8 cartbounty-col-lg-9">
			<div class="cartbounty-settings-group cartbounty-toggle">
				<label for="cartbounty-<?php echo $location; ?>-generate-coupon" class="cartbounty-switch cartbounty-unavailable">
					<input id="cartbounty-<?php echo $location; ?>-generate-coupon" class="cartbounty-checkbox" type="checkbox" disabled autocomplete="off" />
					<span class="cartbounty-slider round"></span>
				</label>
				<label for="cartbounty-<?php echo $location; ?>-generate-coupon" class="cartbounty-unavailable"><?php esc_html_e('Generate coupon', 'woo-save-abandoned-carts'); ?></label>
				<p class='cartbounty-additional-information'>
					<i class='cartbounty-hidden cartbounty-unavailable-notice'><?php echo $this->display_unavailable_notice( 'generate_instant_coupon' ); ?></i>
				</p>
			</div>
			<div class="cartbounty-settings-group">
				<label for="cartbounty-<?php echo $location; ?>-existing-coupon"><?php esc_html_e('Include an existing coupon', 'woo-save-abandoned-carts'); ?></label>
				<select id="cartbounty-<?php echo $location; ?>-existing-coupon" class="cartbounty-select" placeholder="<?php esc_attr_e('Search coupon...', 'woo-save-abandoned-carts'); ?>" disabled autocomplete="off">
				</select>
			</div>
		</div>
		<?php
		$output .= ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Display active features
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function display_active_features(){
		$content = '';
		$features = array();
		$features['recovery'] = $this->get_sections( 'recovery' );
		$features['tools'] = $this->get_sections( 'tools' );
		ob_start(); ?>
		<?php foreach( $features as $section => $feature ): ?>
			<?php foreach( $feature as $key => $item ): ?>
				<?php if( $item['connected'] ): ?>
					<?php $link = '?page='. CARTBOUNTY .'&tab='. $section .'&section='. $key; ?>
					<div class="cartbounty-col-xs-6 cartbounty-section-item-container">
						<a class="cartbounty-section-item cartbounty-connected" href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $item['name'] ); ?>">
							<span class="cartbounty-section-image"><?php echo $this->get_icon( $key, false, false, true ); ?></span>
							<span class="cartbounty-section-content">
								<em class="cartbounty-section-title"><?php echo esc_html( $item['name'] ); ?></em>
								<?php echo $this->get_connection( $item['connected'], true, $section ); ?>
							</span>
						</a>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
		<?php $content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Retrieve notice contents
	 *
	 * @since    8.0
	 * @return   array
	 * @param    string    $notice_type        		Notice type
	 * @param    String    $medium     				Determines where the button was clicked from. Default none
	 * @param    String    $tag        				Hashtag to a specific section in the document. Default none
	 */
	public function get_notice_contents( $notice_type, $medium = '', $tag = '' ){
		$contents = array();
		$expression = $this->get_expressions();
		$saved_cart_count = $this->total_cartbounty_recoverable_cart_count();
		$closest_lowest_cart_count_decimal = $this->get_closest_lowest_integer( $saved_cart_count );

		switch( $notice_type ){

			case 'upgrade':
				$contents = array(
					'title' 		=> esc_html__( 'Unlock the full power of CartBounty and treat yourself with some lovely cat videos. 😸', 'woo-save-abandoned-carts' ),
					'description' 	=> esc_html__( 'Personalized messages, coupon codes, SMS reminders, push notifications, Early Capture - just some of the cool features that help you recover more sales.', 'woo-save-abandoned-carts' ),
					'image'			=> plugins_url( 'assets/notification-email.gif', __FILE__ ),
					'color_class'	=> ' cartbounty-purple',
					'main_url'		=> $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, $medium, $tag ),
					'local_url'		=> false,
					'using_buttons'	=> true,
					'url_label'		=> esc_html__( 'Get Pro', 'woo-save-abandoned-carts' ),
					'done_label'	=> '',
					'close_label'	=> esc_html__( 'Not now', 'woo-save-abandoned-carts' )
				);
				break;

			case 'steps':
				$contents = array(
					'title' 		=> esc_html__( 'Make the most of your WordPress 4-step recovery automation!', 'woo-save-abandoned-carts' ),
					'description' 	=> esc_html__( 'Recover more shopping carts with personalized messages, product templates, unique coupon codes and track email stats like open and click rates.', 'woo-save-abandoned-carts' ),
					'image'			=> plugins_url( 'assets/4-step-email-series.gif', __FILE__ ),
					'color_class'	=> ' cartbounty-teal',
					'main_url'		=> $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, $medium, $tag ),
					'local_url'		=> false,
					'using_buttons'	=> true,
					'url_label'		=> esc_html__( 'Get Pro', 'woo-save-abandoned-carts' ),
					'done_label'	=> '',
					'close_label'	=> esc_html__( 'Not now', 'woo-save-abandoned-carts' )
				);
				break;

			case 'review':
				$contents = array(
					'title' 		=> sprintf(
										/* translators: %s - Gets replaced by an excitement word e.g. Awesome!, %d - Abandoned cart count */
										esc_html( _n( '%s You have already captured %d abandoned cart!', '%s You have already captured %d abandoned carts!', $closest_lowest_cart_count_decimal , 'woo-save-abandoned-carts' ) ), esc_html( $expression['exclamation'] ), esc_html( $closest_lowest_cart_count_decimal ) ),
					'description' 	=> esc_html__( 'If you like our plugin, please leave us a 5-star rating. It is the easiest way to help us grow and keep evolving further.', 'woo-save-abandoned-carts' ),
					'image'			=> plugins_url( 'assets/review-notification.gif', __FILE__ ),
					'color_class'	=> '',
					'main_url'		=> CARTBOUNTY_REVIEW_LINK,
					'local_url'		=> false,
					'using_buttons'	=> true,
					'url_label'		=> esc_html__( 'Leave a 5-star rating', 'woo-save-abandoned-carts' ),
					'done_label'	=> esc_html__( 'Done', 'woo-save-abandoned-carts' ),
					'close_label'	=> esc_html__( 'Close', 'woo-save-abandoned-carts' )
				);
				break;

			case 'emojis':
				$contents = array(
					'title' 		=> esc_html__( 'Upgrade to allow easy emoji insertion', 'woo-save-abandoned-carts' ),
					'description' 	=> '',
					'image'			=> plugins_url( 'assets/emoji-preview.gif', __FILE__ ),
					'color_class'	=> '',
					'main_url'		=> $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL, $medium, $tag ),
					'local_url'		=> false,
					'using_buttons'	=> true,
					'url_label'		=> esc_html__( 'Get Pro', 'woo-save-abandoned-carts' ),
					'done_label'	=> '',
					'close_label'	=> esc_html__( 'Close', 'woo-save-abandoned-carts' )
				);
				break;

			case 'personalization':
				$contents = array(
					'title' 		=> esc_html__( 'Increase open-rate and sales using personalization', 'woo-save-abandoned-carts' ),
					'description' 	=> '',
					'image'			=> plugins_url( 'assets/personalization-preview.gif', __FILE__ ),
					'color_class'	=> ' cartbounty-teal',
					'main_url'		=> $this->get_trackable_link( CARTBOUNTY_LICENSE_SERVER_URL . 'personalization-tags', $medium, $tag ),
					'local_url'		=> false,
					'using_buttons'	=> true,
					'url_label'		=> esc_html__( 'Get Pro', 'woo-save-abandoned-carts' ),
					'done_label'	=> '',
					'close_label'	=> esc_html__( 'Close', 'woo-save-abandoned-carts' )
				);
				break;
		}

		return $contents;
	}

	/**
	 * Display dashboard notices
	 * Priority in which notices will be displayed:
	 *		1) Information notice
	 *		2) Sales notices
	 *		3) Review request
	 *
	 * @since    8.0
	 * @return   HTML
	 */
	public function display_dashboard_notices(){
		$content = '';
		$dashboard = true;

		if( $this->should_display_notice( 'upgrade' ) ){
			$content = $this->prepare_notice( 'upgrade', $dashboard, 'dashboard_upgrade' );

		}elseif( $this->should_display_notice( 'steps' ) ){
			$content = $this->prepare_notice( 'steps', $dashboard, 'dashboard_steps' );

		}elseif( $this->should_display_notice( 'review' ) ){ //Checking if we should display the Review notice
			$content = $this->prepare_notice( 'review', $dashboard );
		}

		return $content;
	}

	/**
	 * Check if notice should be displayed or not
	 *
	 * @since    8.0
	 * @return   boolean
	 * @param    string    	$notice_type        Notice type
	 */
	function should_display_notice( $notice_type ){
		$display = false;
		$recoverable_cart_count = $this->total_cartbounty_recoverable_cart_count();
		$wordpress = $this->wordpress();

		switch( $notice_type ){
			
			case 'upgrade':

				if( $recoverable_cart_count > 5 && $this->days_have_passed( 'time_bubble_displayed', 18 ) ){ //Display welcome message in case license is inactive and no cart has been saved so far
					$display = true; //Show the notice
				}

				break;

			case 'steps':

				if( $wordpress->get_stats() > 9 && $this->days_have_passed( 'time_bubble_steps_displayed', 18 ) ){
					$display = true; //Show the notice
				}

				break;

			case 'review':

				if( !$this->is_notice_submitted( $notice_type ) ){
					$level = $this->get_achieved_review_level();
					
					if( $level ){
						$display = true;
					}
				}

				break;
		}

		return $display;
	}

	/**
	 * Prepare notice contents
	 *
	 * @since    8.0
	 * @return   HTML
	 * @param    string    	$notice_type        Notice type
	 * @param    boolean    $dashboard        	If the notice should be displayed on dashboard or not
	 * @param    String    $medium     			Determines where the button was clicked from. Default none
	 * @param    String    $tag        			Hashtag to a specific section in the document. Default none
	 */
	public function prepare_notice( $notice_type, $dashboard = false, $medium = '', $tag = '' ){
		$notice_contents = $this->get_notice_contents( $notice_type, $medium, $tag );
		$title = $notice_contents['title'];
		$description = $notice_contents['description'];
		$image_url = $notice_contents['image'];
		$color_class = $notice_contents['color_class'];
		$main_url = $notice_contents['main_url'];
		$local_url = $notice_contents['local_url'];
		$using_buttons = $notice_contents['using_buttons'];
		$url_label = $notice_contents['url_label'];
		$done_label = $notice_contents['done_label'];
		$close_label = $notice_contents['close_label'];
		$nonce = wp_create_nonce( 'notice_nonce' );
		$class = '';
		$target = '_blank';

		if( $dashboard ){ //If not displaying notice inside dashboard
			$class = ' cartbounty-report-widget';
		}else{
			$class = ' cartbounty-bubble';
		}

		if( $local_url ){ //In case this is a local URL, open link in a the same tab
			$target = '_self';
		}

		ob_start(); ?>
		<div id="cartbounty-notice-<?php echo $notice_type; ?>" class="cartbounty-notice-block<?php echo $class; ?>">
			<?php if( $image_url ): ?>
			<a class="cartbounty-notice-image<?php echo $color_class; ?>" href="<?php echo esc_url( $main_url ); ?>" title="<?php echo esc_attr( $title); ?>" target="<?php echo $target; ?>">
				<img src="<?php echo esc_url( $image_url ); ?>"/>
			</a>
			<?php endif; ?>
			<div class="cartbounty-notice-content">
				<h2><?php echo $title; ?></h2>
				<p><?php echo $description; ?></p>
				<?php if( $using_buttons ): ?>
				<div class="cartbounty-button-row">
					<?php if( $url_label ): ?>
					<a href="<?php echo esc_url( $main_url ); ?>" class="button cartbounty-button" target="<?php echo $target; ?>"><?php echo $url_label; ?></a>
					<?php endif; ?>
					<?php if( $done_label ): ?>
					<button type="button" class='button cartbounty-button cartbounty-notice-done cartbounty-close-notice' data-operation='submitted' data-type='<?php echo $notice_type; ?>' data-nonce='<?php echo esc_attr( $nonce ); ?>'><?php echo $done_label; ?></button>
					<?php endif; ?>
					<?php if( $close_label ): ?>
					<button type="button" class='button cartbounty-button cartbounty-close cartbounty-close-notice' data-operation='declined' data-type='<?php echo $notice_type; ?>' data-nonce='<?php echo esc_attr( $nonce ); ?>'><?php echo $close_label; ?></button>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php $content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Return currently achieved review level
	 * Used to define various levels at which the review should be displayed
	 *
	 * @since    8.0
	 * @return   integer
	 */
	public function get_achieved_review_level(){
		$level = 0;
		$levels = array(
			'1' => 20,
			'2' => 40,
			'3' => 80,
			'4' => 120,
			'5' => 250,
			'6' => 500,
			'7' => 1000,
		);
		$times_review_declined = $this->get_settings( 'misc_settings', 'times_review_declined' );
		$maximum_value = max( array_keys( $levels ) );
		
		if( $times_review_declined >= $maximum_value ) return; //Stop in case we have reached maximum level at which we ask reviews

		$recoverable_cart_count = $this->total_cartbounty_recoverable_cart_count();

		foreach( $levels as $key => $value ) {
			if( $recoverable_cart_count >= $value && $times_review_declined < $key ){
				$level = $key;
			}
		}

		return $level;
	}

	/**
	* Return saved abandoned cart data
	*
	* @since    8.1
	* @return   array
	* @param    array     $cart_data         Cart data that needs to be returned
	*/
	public function get_saved_cart_contents( $cart_data ){
		$saved_cart_data = maybe_unserialize( $cart_data );

		if( isset( $saved_cart_data['products'] ) ){ //Temporary block since version 8.8. Will be removed in future versions. Supports previous version of saving cart contents and cart mete information in the same column
			$saved_cart_data = $saved_cart_data['products'];
		}

		return $saved_cart_data;
	}

	/**
	* Validate abandoned cart deletion security nonce
	*
	* @since    8.2.1
	*/
	public function validate_cart_deletion(){
		
		if( isset( $_GET['page'] ) && $_GET['page'] == CARTBOUNTY_PLUGIN_NAME_SLUG ){ //If delete action coming from CartBounty

			if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ){ //Check if any delete action fired including bottom Bulk delete action

				$nonce = false;

				if( isset( $_GET['nonce'] ) ){
					$nonce = $_GET['nonce'];
				}

				if( !wp_verify_nonce( $nonce, 'delete_cart_nonce' ) && !wp_verify_nonce( $nonce, 'bulk_action_nonce' ) ){
					wp_die( esc_html__( 'Security check failed. The link is not valid.', 'woo-save-abandoned-carts' ) ); 
				}
			}
		}
	}

	/**
	* Validate phone number
	*
	* @return   boolean
	* @param    string     $phone     Phone number
	* @since    8.7
	*/
	public function validate_phone_number( $phone ){
		$valid = false;
		$pattern = '^[+0-9\s]\s?\d[0-9\s.\-]{6,30}$';
		$regex = '/'. $pattern .'/';

		if( !empty( $phone ) ){

			if( preg_match( $regex, $phone ) === 1 ){
				$valid = true;
			}
		}
		
		return $valid;
	}


	/**
	* Return email preview modal container
	*
	* @since    7.0
	* @return   HTML
	* @param    string    $modal_id              Identifier to distinguish modal windows from one another
	*/
	public function output_modal_container( $modal_id = false ){
		$output = '';
		$output .= '<div class="cartbounty-modal" id="cartbounty-modal-'. esc_attr( $modal_id ) .'" aria-hidden="true">';
			$output .= '<div class="cartbounty-modal-overlay" tabindex="-1" data-micromodal-close>';
				$output .= '<div class="cartbounty-modal-content-container" role="dialog" aria-modal="true">';
					$output .= '<button type="button" class="cartbounty-close-modal" aria-label="'. esc_html__("Close", 'woo-save-abandoned-carts') .'" data-micromodal-close></button>';
					$output .= '<div class="cartbounty-modal-content" id="cartbounty-modal-content-'. esc_attr( $modal_id ) .'"></div>';
				$output .= '</div>';
			$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

	/**
	 * Method checks if GTranslate is active
	 *
	 * @since    8.7
	 * @return   boolean
	 */
	function gtranslate_is_active(){
		$result = false;

		if( class_exists( 'GTranslate' ) ){
			$result = true;
		}

		return $result;
	}

	/**
	 * Replacing WordPress default transients with custom function for storing temporary data
	 * Transients do not seem to be working reliably for all users
	 * Using WooCommerce session value as a unique identifier
	 *
	 * @since    8.8
	 * @param    string    $key                Temporary data field identifier
	 * @param    string    $value              Value to be stored
	 * @param    string    $expiration         Data expiration time
	 * @param    boolean   $unique         	   If the transient should be unique for each customer or not
	 */
	function set_cartbounty_transient( $key, $value, $expiration = 60, $unique = false ){
		$customer_id = '';
		
		if( $unique ){ //If storing unique option for each visitor

			if( WC()->session ){
				$customer_id = '_' . WC()->session->get_customer_id();
			}
		}

		$data = array(
			'value'      => $value,
			'expiration' => time() + absint( $expiration ),
		);

		$id = 'cartbounty_temp_' . $key . $customer_id;
		update_option( $id, $data, false );
	}

	/**
	 * Retrieving custom transient data from database
	 *
	 * @since    8.8
	 * @return   false / string
	 * @param    string    $key                Temporary data field identifier
	 * @param    boolean   $unique         	   If the transient should be unique for each customer or not
	 */
	function get_cartbounty_transient( $key, $unique = false ){
		$result = false;
		$customer_id = '';

		if( $unique ){ //If retrieving a unique option for each visitor

			if( WC()->session ){
				$customer_id = '_' . WC()->session->get_customer_id();
			}
		}

		$id = 'cartbounty_temp_' . $key . $customer_id;
		$data = get_option( $id );

		if( empty( $data ) || !is_array( $data ) || !isset( $data['expiration'] ) ){ //If data is missing
			//Do nothing
		
		}elseif( time() > $data['expiration'] ){ //Clean up expired data
			delete_option( $id );

		}else{
			$result = $data['value'];
		}

		return $result;
	}

	/**
	 * Delete a single transient with the given id
	 *
	 * @since    8.8
	 * @param    string    $key                Temporary data field identifier
	 */
	function delete_cartbounty_transient( $key ){
		$id = 'cartbounty_temp_' . $key;
		delete_option( $id );
	}

	/**
	* Deleting transients
	* Function does not check if transient has expired or not
	*
	* @since    8.8
	*/
	function delete_expired_cartbounty_transients(){
		global $wpdb;
		$options_table = $wpdb->options;
		$prefix = 'cartbounty_temp_';
		$now = time();

		$wpdb->get_results(
			$wpdb->prepare(
				"DELETE FROM $options_table
				 WHERE option_name
				 LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
	}

	/**
	* Checks if current user has Admin privileges
	*
	* @since    8.8
	* @return   boolean
	*/
	function user_is_admin(){
		return current_user_can( 'manage_options' );
	}

	/**
	* Checks if current user has Shop manager privileges
	*
	* @since    8.8
	* @return   boolean
	*/
	function user_is_shop_manager(){
		return current_user_can( 'list_users' );
	}

	/**
	 * Returns prepared contents for displaying Ajax action results
	 *
	 * @since    8.10
	 * @return   HTML
	 * @param    string    $message           	Message content
	 * @param    string    $type           		Result type, e.g. success, failure
	 * @param    string    $class           	Additional class or classes
	 * @param    boolean   $fade           	    If the notice should fade out after a while or not
	 */
	public function prepare_response_message( $message = '', $type = 'failure', $class = '', $fade = true ){
		$direction = is_rtl() ? 'rtl' : 'ltr';
		$license_class = 'license-inactive';
		$icon_name = 'invalid-icon';

		if( empty( $message ) ){
			$message = __( 'Looks like you are not allowed to do this', 'woo-save-abandoned-carts' );
		}

		if( $type == 'success' ){
			$license_class = 'license-active';
			$icon_name = 'active-icon';

		}elseif( $type == 'expired' ){
			$license_class = 'license-active license-expired';
			$icon_name = 'expired-icon';
		
		}elseif( $type == 'invalid' ){
			$icon_name = 'invalid-icon';
		
		}elseif( $type == 'inactive' ){
			$license_class = 'license-missing';
			$icon_name = 'inactive-icon';
		}

		if( !empty( $class ) ){
			$class = ' ' . $class;
		}

		if( $fade ){
			$license_class .= ' fadeOutSlow';
		}

		$result = '<span class="license-status '. esc_attr( $license_class ) . $class .'" dir="'. esc_attr( $direction ) .'"><i class="license-status-icon"><img src="'. esc_url( plugin_dir_url( __DIR__ ) ) . 'admin/assets/'. $icon_name .'.svg" /></i>'. $message .'</span>';
		return $result;
	}

	/**
	 * Converts the WooCommerce country codes to 3-letter ISO codes
	 *
	 * @since    8.2
	 * @return   string    ISO 3-letter country code
	 * @param    string    $country               WooCommerce's 2 letter country code
	 */
	public function convert_country_code( $country ){
		$countries = array(
			'AF' => 'AFG', //Afghanistan
			'AX' => 'ALA', //&#197;land Islands
			'AL' => 'ALB', //Albania
			'DZ' => 'DZA', //Algeria
			'AS' => 'ASM', //American Samoa
			'AD' => 'AND', //Andorra
			'AO' => 'AGO', //Angola
			'AI' => 'AIA', //Anguilla
			'AQ' => 'ATA', //Antarctica
			'AG' => 'ATG', //Antigua and Barbuda
			'AR' => 'ARG', //Argentina
			'AM' => 'ARM', //Armenia
			'AW' => 'ABW', //Aruba
			'AU' => 'AUS', //Australia
			'AT' => 'AUT', //Austria
			'AZ' => 'AZE', //Azerbaijan
			'BS' => 'BHS', //Bahamas
			'BH' => 'BHR', //Bahrain
			'BD' => 'BGD', //Bangladesh
			'BB' => 'BRB', //Barbados
			'BY' => 'BLR', //Belarus
			'BE' => 'BEL', //Belgium
			'BZ' => 'BLZ', //Belize
			'BJ' => 'BEN', //Benin
			'BM' => 'BMU', //Bermuda
			'BT' => 'BTN', //Bhutan
			'BO' => 'BOL', //Bolivia
			'BQ' => 'BES', //Bonaire, Saint Estatius and Saba
			'BA' => 'BIH', //Bosnia and Herzegovina
			'BW' => 'BWA', //Botswana
			'BV' => 'BVT', //Bouvet Islands
			'BR' => 'BRA', //Brazil
			'IO' => 'IOT', //British Indian Ocean Territory
			'BN' => 'BRN', //Brunei
			'BG' => 'BGR', //Bulgaria
			'BF' => 'BFA', //Burkina Faso
			'BI' => 'BDI', //Burundi
			'KH' => 'KHM', //Cambodia
			'CM' => 'CMR', //Cameroon
			'CA' => 'CAN', //Canada
			'CV' => 'CPV', //Cape Verde
			'KY' => 'CYM', //Cayman Islands
			'CF' => 'CAF', //Central African Republic
			'TD' => 'TCD', //Chad
			'CL' => 'CHL', //Chile
			'CN' => 'CHN', //China
			'CX' => 'CXR', //Christmas Island
			'CC' => 'CCK', //Cocos (Keeling) Islands
			'CO' => 'COL', //Colombia
			'KM' => 'COM', //Comoros
			'CG' => 'COG', //Congo
			'CD' => 'COD', //Congo, Democratic Republic of the
			'CK' => 'COK', //Cook Islands
			'CR' => 'CRI', //Costa Rica
			'CI' => 'CIV', //Côte d\'Ivoire
			'HR' => 'HRV', //Croatia
			'CU' => 'CUB', //Cuba
			'CW' => 'CUW', //Curaçao
			'CY' => 'CYP', //Cyprus
			'CZ' => 'CZE', //Czech Republic
			'DK' => 'DNK', //Denmark
			'DJ' => 'DJI', //Djibouti
			'DM' => 'DMA', //Dominica
			'DO' => 'DOM', //Dominican Republic
			'EC' => 'ECU', //Ecuador
			'EG' => 'EGY', //Egypt
			'SV' => 'SLV', //El Salvador
			'GQ' => 'GNQ', //Equatorial Guinea
			'ER' => 'ERI', //Eritrea
			'EE' => 'EST', //Estonia
			'ET' => 'ETH', //Ethiopia
			'FK' => 'FLK', //Falkland Islands
			'FO' => 'FRO', //Faroe Islands
			'FJ' => 'FIJ', //Fiji
			'FI' => 'FIN', //Finland
			'FR' => 'FRA', //France
			'GF' => 'GUF', //French Guiana
			'PF' => 'PYF', //French Polynesia
			'TF' => 'ATF', //French Southern Territories
			'GA' => 'GAB', //Gabon
			'GM' => 'GMB', //Gambia
			'GE' => 'GEO', //Georgia
			'DE' => 'DEU', //Germany
			'GH' => 'GHA', //Ghana
			'GI' => 'GIB', //Gibraltar
			'GR' => 'GRC', //Greece
			'GL' => 'GRL', //Greenland
			'GD' => 'GRD', //Grenada
			'GP' => 'GLP', //Guadeloupe
			'GU' => 'GUM', //Guam
			'GT' => 'GTM', //Guatemala
			'GG' => 'GGY', //Guernsey
			'GN' => 'GIN', //Guinea
			'GW' => 'GNB', //Guinea-Bissau
			'GY' => 'GUY', //Guyana
			'HT' => 'HTI', //Haiti
			'HM' => 'HMD', //Heard Island and McDonald Islands
			'VA' => 'VAT', //Holy See (Vatican City State)
			'HN' => 'HND', //Honduras
			'HK' => 'HKG', //Hong Kong
			'HU' => 'HUN', //Hungary
			'IS' => 'ISL', //Iceland
			'IN' => 'IND', //India
			'ID' => 'IDN', //Indonesia
			'IR' => 'IRN', //Iran
			'IQ' => 'IRQ', //Iraq
			'IE' => 'IRL', //Republic of Ireland
			'IM' => 'IMN', //Isle of Man
			'IL' => 'ISR', //Israel
			'IT' => 'ITA', //Italy
			'JM' => 'JAM', //Jamaica
			'JP' => 'JPN', //Japan
			'JE' => 'JEY', //Jersey
			'JO' => 'JOR', //Jordan
			'KZ' => 'KAZ', //Kazakhstan
			'KE' => 'KEN', //Kenya
			'KI' => 'KIR', //Kiribati
			'KP' => 'PRK', //Korea, Democratic People\'s Republic of
			'KR' => 'KOR', //Korea, Republic of (South)
			'KW' => 'KWT', //Kuwait
			'KG' => 'KGZ', //Kyrgyzstan
			'LA' => 'LAO', //Laos
			'LV' => 'LVA', //Latvia
			'LB' => 'LBN', //Lebanon
			'LS' => 'LSO', //Lesotho
			'LR' => 'LBR', //Liberia
			'LY' => 'LBY', //Libya
			'LI' => 'LIE', //Liechtenstein
			'LT' => 'LTU', //Lithuania
			'LU' => 'LUX', //Luxembourg
			'MO' => 'MAC', //Macao S.A.R., China
			'MK' => 'MKD', //Macedonia
			'MG' => 'MDG', //Madagascar
			'MW' => 'MWI', //Malawi
			'MY' => 'MYS', //Malaysia
			'MV' => 'MDV', //Maldives
			'ML' => 'MLI', //Mali
			'MT' => 'MLT', //Malta
			'MH' => 'MHL', //Marshall Islands
			'MQ' => 'MTQ', //Martinique
			'MR' => 'MRT', //Mauritania
			'MU' => 'MUS', //Mauritius
			'YT' => 'MYT', //Mayotte
			'MX' => 'MEX', //Mexico
			'FM' => 'FSM', //Micronesia
			'MD' => 'MDA', //Moldova
			'MC' => 'MCO', //Monaco
			'MN' => 'MNG', //Mongolia
			'ME' => 'MNE', //Montenegro
			'MS' => 'MSR', //Montserrat
			'MA' => 'MAR', //Morocco
			'MZ' => 'MOZ', //Mozambique
			'MM' => 'MMR', //Myanmar
			'NA' => 'NAM', //Namibia
			'NR' => 'NRU', //Nauru
			'NP' => 'NPL', //Nepal
			'NL' => 'NLD', //Netherlands
			'AN' => 'ANT', //Netherlands Antilles
			'NC' => 'NCL', //New Caledonia
			'NZ' => 'NZL', //New Zealand
			'NI' => 'NIC', //Nicaragua
			'NE' => 'NER', //Niger
			'NG' => 'NGA', //Nigeria
			'NU' => 'NIU', //Niue
			'NF' => 'NFK', //Norfolk Island
			'MP' => 'MNP', //Northern Mariana Islands
			'NO' => 'NOR', //Norway
			'OM' => 'OMN', //Oman
			'PK' => 'PAK', //Pakistan
			'PW' => 'PLW', //Palau
			'PS' => 'PSE', //Palestinian Territory
			'PA' => 'PAN', //Panama
			'PG' => 'PNG', //Papua New Guinea
			'PY' => 'PRY', //Paraguay
			'PE' => 'PER', //Peru
			'PH' => 'PHL', //Philippines
			'PN' => 'PCN', //Pitcairn
			'PL' => 'POL', //Poland
			'PT' => 'PRT', //Portugal
			'PR' => 'PRI', //Puerto Rico
			'QA' => 'QAT', //Qatar
			'RE' => 'REU', //Reunion
			'RO' => 'ROU', //Romania
			'RU' => 'RUS', //Russia
			'RW' => 'RWA', //Rwanda
			'BL' => 'BLM', //Saint Barth&eacute;lemy
			'SH' => 'SHN', //Saint Helena
			'KN' => 'KNA', //Saint Kitts and Nevis
			'LC' => 'LCA', //Saint Lucia
			'MF' => 'MAF', //Saint Martin (French part)
			'SX' => 'SXM', //Sint Maarten / Saint Matin (Dutch part)
			'PM' => 'SPM', //Saint Pierre and Miquelon
			'VC' => 'VCT', //Saint Vincent and the Grenadines
			'WS' => 'WSM', //Samoa
			'SM' => 'SMR', //San Marino
			'ST' => 'STP', //S&atilde;o Tom&eacute; and Pr&iacute;ncipe
			'SA' => 'SAU', //Saudi Arabia
			'SN' => 'SEN', //Senegal
			'RS' => 'SRB', //Serbia
			'SC' => 'SYC', //Seychelles
			'SL' => 'SLE', //Sierra Leone
			'SG' => 'SGP', //Singapore
			'SK' => 'SVK', //Slovakia
			'SI' => 'SVN', //Slovenia
			'SB' => 'SLB', //Solomon Islands
			'SO' => 'SOM', //Somalia
			'ZA' => 'ZAF', //South Africa
			'GS' => 'SGS', //South Georgia/Sandwich Islands
			'SS' => 'SSD', //South Sudan
			'ES' => 'ESP', //Spain
			'LK' => 'LKA', //Sri Lanka
			'SD' => 'SDN', //Sudan
			'SR' => 'SUR', //Suriname
			'SJ' => 'SJM', //Svalbard and Jan Mayen
			'SZ' => 'SWZ', //Swaziland
			'SE' => 'SWE', //Sweden
			'CH' => 'CHE', //Switzerland
			'SY' => 'SYR', //Syria
			'TW' => 'TWN', //Taiwan
			'TJ' => 'TJK', //Tajikistan
			'TZ' => 'TZA', //Tanzania
			'TH' => 'THA', //Thailand    
			'TL' => 'TLS', //Timor-Leste
			'TG' => 'TGO', //Togo
			'TK' => 'TKL', //Tokelau
			'TO' => 'TON', //Tonga
			'TT' => 'TTO', //Trinidad and Tobago
			'TN' => 'TUN', //Tunisia
			'TR' => 'TUR', //Turkey
			'TM' => 'TKM', //Turkmenistan
			'TC' => 'TCA', //Turks and Caicos Islands
			'TV' => 'TUV', //Tuvalu     
			'UG' => 'UGA', //Uganda
			'UA' => 'UKR', //Ukraine
			'AE' => 'ARE', //United Arab Emirates
			'GB' => 'GBR', //United Kingdom
			'US' => 'USA', //United States
			'UM' => 'UMI', //United States Minor Outlying Islands
			'UY' => 'URY', //Uruguay
			'UZ' => 'UZB', //Uzbekistan
			'VU' => 'VUT', //Vanuatu
			'VE' => 'VEN', //Venezuela
			'VN' => 'VNM', //Vietnam
			'VG' => 'VGB', //Virgin Islands, British
			'VI' => 'VIR', //Virgin Island, U.S.
			'WF' => 'WLF', //Wallis and Futuna
			'EH' => 'ESH', //Western Sahara
			'YE' => 'YEM', //Yemen
			'ZM' => 'ZMB', //Zambia
			'ZW' => 'ZWE', //Zimbabwe
		);

		$iso_code = isset( $countries[$country] ) ? $countries[$country] : $country;
		return $iso_code;
	}
}