<?php

/**
 * Plugin Name: CartBounty - Save and recover abandoned carts for WooCommerce
 * Plugin URI: https://www.cartbounty.com
 * Description: Save abandoned carts by instantly capturing WooCommerce checkout form before submission.
 * Version: 8.10
 * Text Domain: woo-save-abandoned-carts
 * Author: Streamline.lv
 * Author URI: http://www.majas-lapu-izstrade.lv/en
 * Requires at least: 4.6
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

//Defining constants
if (!defined('CARTBOUNTY_VERSION_NUMBER')) define( 'CARTBOUNTY_VERSION_NUMBER', '8.10' );
if (!defined('CARTBOUNTY_PLUGIN_NAME')) define( 'CARTBOUNTY_PLUGIN_NAME', 'CartBounty - Save and recover abandoned carts for WooCommerce' );
if (!defined('CARTBOUNTY')) define( 'CARTBOUNTY', 'cartbounty' );
if (!defined('CARTBOUNTY_PLUGIN_NAME_SLUG')) define( 'CARTBOUNTY_PLUGIN_NAME_SLUG', 'cartbounty' );
if (!defined('CARTBOUNTY_TABLE_NAME_EMAILS')) define( 'CARTBOUNTY_TABLE_NAME_EMAILS', 'cartbounty_emails' );
if (!defined('CARTBOUNTY_BASENAME')) define( 'CARTBOUNTY_BASENAME', plugin_basename( __FILE__ ) );
if (!defined('CARTBOUNTY_TABLE_NAME')) define( 'CARTBOUNTY_TABLE_NAME', 'cartbounty' );
if (!defined('CARTBOUNTY_LICENSE_SERVER_URL')) define('CARTBOUNTY_LICENSE_SERVER_URL', 'https://www.cartbounty.com/' );
if (!defined('CARTBOUNTY_REVIEW_LINK')) define('CARTBOUNTY_REVIEW_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/reviews/#new-post' );
if (!defined('CARTBOUNTY_TEXT_DOMAIN')) define( 'CARTBOUNTY_TEXT_DOMAIN', 'woo-save-abandoned-carts' ); //No longer used since v6.1.3. Will be removed in future releases
if (!defined('CARTBOUNTY_ABBREVIATION')) define( 'CARTBOUNTY_ABBREVIATION', 'CartBounty' );
if (!defined('CARTBOUNTY_NEW_NOTICE')) define( 'CARTBOUNTY_NEW_NOTICE', 240 ); //Defining time in minutes how long New status is shown in the table
if (!defined('CARTBOUNTY_MAX_SYNC_PERIOD')) define( 'CARTBOUNTY_MAX_SYNC_PERIOD', 100 ); //Defining maximum period in days that is up for recovery. We do not want to remind about very old abandoned carts
if (!defined('CARTBOUNTY_MAX_STEPS')) define( 'CARTBOUNTY_MAX_STEPS', 3 ); //Temporary block since version 8.9. Will be removed in future versions
if (!defined('CARTBOUNTY_ENCRYPTION_KEY')) define('CARTBOUNTY_ENCRYPTION_KEY', '6c7f0ff3c5b607b0762gbsEwuqSb5c0e5461611791f2ff8d4d45009853795c' ); //Defines encryption key used for creating checkout URL hash part of the link
if (!defined('CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK')) define('CARTBOUNTY_ACTIVECAMPAIGN_TRIAL_LINK', 'https://try.activecampaign.com/start' ); //ActiveCampaign trial link
if (!defined('CARTBOUNTY_GETRESPONSE_TRIAL_LINK')) define('CARTBOUNTY_GETRESPONSE_TRIAL_LINK', 'https://www.getresponse.com/features/marketing-automation?a=vPJGRchyVX&c=integrate_cartbounty' ); //GetResponse free trial link
if (!defined('CARTBOUNTY_MAILCHIMP_LINK')) define('CARTBOUNTY_MAILCHIMP_LINK', 'http://eepurl.com/hHjfrX' ); //MailChimp link
if (!defined('CARTBOUNTY_FAQ_LINK')) define('CARTBOUNTY_FAQ_LINK', 'https://wordpress.org/plugins/woo-save-abandoned-carts/#faq' );
if (!defined('CARTBOUNTY_FEATURE_LINK')) define('CARTBOUNTY_FEATURE_LINK', 'https://www.cartbounty.com/contact/' ); //This is the URL where users can provide new ideas and suggestions
if (!defined('CARTBOUNTY_SUPPORT_LINK')) define('CARTBOUNTY_SUPPORT_LINK', 'https://wordpress.org/support/plugin/woo-save-abandoned-carts/#new-topic-0' ); //This is the URL where users can get support
if (!defined('CARTBOUNTY_BULKGATE_TRIAL_LINK')) define('CARTBOUNTY_BULKGATE_TRIAL_LINK', 'https://portal.bulkgate.com/join/55713/en/solutions/sms-gateway' );
	if (!defined('CARTBOUNTY_WHATSAPP_LINK')) define('CARTBOUNTY_WHATSAPP_LINK', 'https://www.cartbounty.com/whatsapp-abandoned-cart-recovery-reminders' );
if (!defined('CARTBOUNTY_PUSH_NOTIFICATION_LINK')) define('CARTBOUNTY_PUSH_NOTIFICATION_LINK', 'https://www.cartbounty.com/web-push-notifications-abandoned-cart-recovery/' );
if (!defined('CARTBOUNTY_WEBHOOK_LINK')) define('CARTBOUNTY_WEBHOOK_LINK', 'https://www.cartbounty.com/webhook-make-integromat-pabbly-abandoned-cart-recovery-automation/' );
if (!defined('CARTBOUNTY_MAKE_LINK')) define('CARTBOUNTY_MAKE_LINK', 'https://www.make.com/en?pc=cartbounty' );
if (!defined('CARTBOUNTY_PABBLY_LINK')) define('CARTBOUNTY_PABBLY_LINK', 'https://payments.pabbly.com/api/affurl/RVYZ07kQyUZ0Z1HUKZ1m/xciq3ahCAzNG3CSo?target=9Z2AHyhSldo6KI1Fn' );

//Registering custom options
register_setting( 'cartbounty-settings', 'cartbounty_main_settings' );
register_setting( 'cartbounty-settings-exit-intent', 'cartbounty_exit_intent_settings' );
register_setting( 'cartbounty-wordpress-settings', 'cartbounty_automation_settings' );
register_setting( 'cartbounty-wordpress-settings', 'cartbounty_automation_steps' );

/**
 * The code that runs during plugin activation.
 *
 * @since    1.0
 */
function activate_cartbounty(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty-activator.php';
	CartBounty_Activator::activate();
	$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
	$misc_settings = $admin->get_settings( 'misc_settings' );

	if( $misc_settings['email_table_exists'] ){
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty-wordpress.php';
		CartBounty_WordPress::create_email_table();
	}
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since    1.0
 */
function deactivate_cartbounty(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty-deactivator.php';
	CartBounty_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cartbounty' );
register_deactivation_hook( __FILE__, 'deactivate_cartbounty' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since    1.0
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cartbounty.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0
 */
function run_cartbounty(){
	$plugin = new CartBounty();
	$plugin->run();
}

run_cartbounty();