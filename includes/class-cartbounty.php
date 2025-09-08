<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
 * @author     Streamline.lv
 */
class CartBounty{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0
	 */
	public function __construct(){

		$this->plugin_name = CARTBOUNTY_PLUGIN_NAME_SLUG;
		$this->version = CARTBOUNTY_VERSION_NUMBER;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_wordpress_hooks();
		$this->define_reports_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function load_dependencies(){

		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cartbounty-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-cartbounty-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-cartbounty-public.php';

		/**
		 * The class responsible for defining all actions that occur in the WordPress recovery area
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cartbounty-wordpress.php';

		/**
		 * The class responsible for defining all methods for getting System status
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cartbounty-status.php';

		/**
		 * The class responsible for defining all methods for dealing with reports
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-cartbounty-reports.php';

		$this->loader = new CartBounty_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function define_admin_hooks(){
		$admin = new CartBounty_Admin( $this->get_plugin_name(), $this->get_version() );
		$status = new CartBounty_System_Status( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $admin, 'cartbounty_menu', 10 );
		$this->loader->add_action( 'admin_head', $admin, 'menu_abandoned_count' );
		$this->loader->add_action( 'admin_head', $admin, 'register_admin_tabs' );
		$this->loader->add_filter( 'set-screen-option', $admin, 'save_page_options', 12, 3 ); //Saving Screen options
		$this->loader->add_action( 'admin_init', $admin, 'schedule_events' );
		$this->loader->add_filter( 'cron_schedules', $admin, 'add_custom_wp_cron_intervals' );
		$this->loader->add_action( 'update_option_cartbounty_main_settings', $admin, 'unschedule_notification_sendout_hook' );
		$this->loader->add_action( 'plugins_loaded', $admin, 'check_version' );
		$this->loader->add_filter( 'plugin_action_links_' . CARTBOUNTY_BASENAME, $admin, 'add_plugin_action_links', 10, 2 );
		$this->loader->add_action( 'cartbounty_after_page_title', $admin, 'output_bubble_content' );
		$this->loader->add_action( 'init', $admin, 'cartbounty_text_domain' );
		$this->loader->add_action( 'cartbounty_remove_empty_carts_hook', $admin, 'delete_empty_carts' );
		$this->loader->add_action( 'cartbounty_remove_empty_carts_hook', $admin, 'delete_expired_cartbounty_transients' );
		$this->loader->add_action( 'admin_notices', $admin, 'display_notices' );
		$this->loader->add_action( 'cartbounty_notification_sendout_hook', $admin, 'send_email' );
		$this->loader->add_filter( 'woocommerce_billing_fields', $admin, 'edit_checkout_fields', 10, 1 );
		$this->loader->add_action( 'woocommerce_new_order', $admin, 'handle_order', 30 );
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $admin, 'handle_order', 30 );
		$this->loader->add_action( 'profile_update', $admin, 'reset_abandoned_cart' );
		$this->loader->add_filter( 'admin_body_class', $admin, 'add_cartbounty_body_class' );
		$this->loader->add_action( 'wp_loaded', $admin, 'trigger_on_load', 15 );
		$this->loader->add_action( 'wp_ajax_force_sync', $admin, 'force_sync' );
		$this->loader->add_action( 'wp_ajax_get_system_status', $status, 'get_system_status' );
		$this->loader->add_action( 'wp_ajax_handle_notice', $admin, 'handle_notices' );
		$this->loader->add_action( 'cartbounty_automation_footer_end', $admin, 'add_email_badge', 100 );
		$this->loader->add_action( 'cartbounty_admin_email_footer_end', $admin, 'add_email_badge', 100 );
		$this->loader->add_action( 'pre_update_option', $admin, 'validate_cartbounty_fields', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function define_public_hooks(){
		$public = new CartBounty_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_ajax_nopriv_cartbounty_save', $public, 'save_cart' );
		$this->loader->add_action( 'wp_ajax_cartbounty_save', $public, 'save_cart' );
		$this->loader->add_action( 'woocommerce_add_to_cart', $public, 'save_cart', 200 );
		$this->loader->add_action( 'woocommerce_cart_actions', $public, 'save_cart', 200 );
		$this->loader->add_action( 'woocommerce_cart_item_removed', $public, 'save_cart', 200 );
		$this->loader->add_action( 'wp', $public, 'restore_classic_checkout_fields', 10 );
		$this->loader->add_action( 'shutdown', $public, 'restore_block_checkout_fields', 200 );
		$this->loader->add_action( 'wp_footer', $public, 'display_exit_intent_form' );
	}

	/**
	 * Register all of the hooks related to the WordPress area functionality of the plugin.
	 *
	 * @since    7.1.1
	 * @access   private
	 */
	private function define_wordpress_hooks(){
		$wordpress = new CartBounty_WordPress();
		$this->loader->add_action( 'cartbounty_sync_hook', $wordpress, 'auto_send' );
		$this->loader->add_action( 'update_option_cartbounty_automation_steps', $wordpress, 'validate_automation_steps', 50);
		$this->loader->add_action( 'update_option_cartbounty_automation_settings', $wordpress, 'sanitize_from_field', 50);
		$this->loader->add_action( 'wp_ajax_email_preview', $wordpress, 'email_preview' );
		$this->loader->add_action( 'wp_ajax_send_test', $wordpress, 'send_test' );
		$this->loader->add_filter( 'cartbounty_automation_unsubscribe_url', $wordpress, 'add_click_through_tracking', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the reports area functionality of the plugin.
	 *
	 * @since    8.0
	 * @access   private
	 */
	private function define_reports_hooks(){
		$reports = new CartBounty_Reports();
		$this->loader->add_action( 'admin_init', $reports, 'handle_calendar_period_submit' );
		$this->loader->add_action( 'wp_ajax_update_quick_stats', $reports, 'handle_report_updates' );
		$this->loader->add_action( 'wp_ajax_update_charts', $reports, 'handle_report_updates' );
		$this->loader->add_action( 'wp_ajax_apply_report_period', $reports, 'handle_ajax_submit' );
		$this->loader->add_action( 'wp_ajax_update_chart_type', $reports, 'handle_chart_type_updates' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0
	 */
	public function run(){
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(){
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0
	 * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(){
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(){
		return $this->version;
	}

}