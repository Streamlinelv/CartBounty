<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/includes
 * @author     Streamline.lv
 */
 
class CartBounty_Activator{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.3
	 */
	public static function activate() {
		
		//Deactivating CartBounty Pro plugin
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins('woo-save-abandoned-carts-pro/cartbounty-pro-abandoned-carts.php');
		
		/**
		* Creating table
		*/
		global $wpdb;
		
		$cart_table = $wpdb->prefix . CARTBOUNTY_TABLE_NAME;
		$old_cart_table = $wpdb->prefix . "captured_wc_fields";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $cart_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(60),
			surname VARCHAR(60),
			email VARCHAR(100),
			phone VARCHAR(20),
			email_consent TINYINT DEFAULT 0,
			location VARCHAR(100),
			cart_contents LONGTEXT,
			cart_hash CHAR(32) DEFAULT NULL,
			cart_meta LONGTEXT DEFAULT NULL,
			cart_total DECIMAL(10,2),
			currency VARCHAR(10),
			time DATETIME DEFAULT '0000-00-00 00:00:00',
			session_id VARCHAR(60),
			other_fields LONGTEXT,
			ip_address VARCHAR(100),
			mail_sent TINYINT NOT NULL DEFAULT 0,
			wp_unsubscribed TINYINT DEFAULT 0,
			wp_steps_completed INT(3) DEFAULT 0,
			wp_last_sent DATETIME DEFAULT '0000-00-00 00:00:00',
			wp_complete TINYINT DEFAULT 0,
			type VARCHAR(10) DEFAULT 0,
			saved_via VARCHAR(10),
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		/**
		* Resets table Auto increment index to 1
		*/
		$sql ="ALTER TABLE $cart_table AUTO_INCREMENT = 1";
		dbDelta( $sql );

		$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );

		/**
		 * Since version 7.0.7.1
		 * This code will be removed in later versions
		 */
		if(get_option('cartbounty_automation_sent_emails')){
			update_option('cartbounty_automation_sends', get_option('cartbounty_automation_sent_emails'));
			delete_option('cartbounty_automation_sent_emails');
		}

		/**
		 * Since version 7.1.6
		 * Transferring time to milliseconds
		 * This code will be removed in later versions
		 */
		function transfer_time_to_milliseconds(){
			$admin = new CartBounty_Admin( CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER );
			$misc_settings = $admin->get_settings( 'misc_settings' );

			//Since version 8.9
			//This code will be removed in later versions
			//Updating typo error
			if( isset( $misc_settings['converted_minutes_to_miliseconds'] ) ){
				$misc_settings['converted_minutes_to_milliseconds'] = true;
				unset( $misc_settings['converted_minutes_to_miliseconds'] );
				update_option( 'cartbounty_misc_settings', $misc_settings );
				return;
			}

			if( CARTBOUNTY_VERSION_NUMBER == $misc_settings['version_number'] || empty( $misc_settings['version_number'] ) ){ //If this is a fresh install or plugin activation
				$misc_settings['converted_minutes_to_milliseconds'] = true;
				update_option( 'cartbounty_misc_settings', $misc_settings ); //setting this variable as we do not require to convert minutes to milliseconds for new installs or activations
				return;
			}

			if( $misc_settings['converted_minutes_to_milliseconds'] ) return;

			$wordpress_steps = get_option( 'cartbounty_automation_steps' );
			$notification_frequency = get_option( 'cartbounty_notification_frequency' );

			//Converting WordPres recovery time intervals
			if( $wordpress_steps ){

				if( is_array( $wordpress_steps ) && !empty( $wordpress_steps ) ){
					foreach( $wordpress_steps as $key => $step ){
						
						if( isset( $wordpress_steps[$key]['interval'] ) ){
							$wordpress_steps[$key]['interval'] = $admin->convert_minutes_to_milliseconds( $step['interval'] );
						}
					}
					update_option( 'cartbounty_automation_steps', $wordpress_steps );
				}
			}

			//Converting Notification time interval
			if( !empty( $notification_frequency ) ){

				if( isset( $notification_frequency['hours'] ) ){
					$notification_frequency['interval'] = $admin->convert_minutes_to_milliseconds( $notification_frequency['hours'] );
					update_option( 'cartbounty_notification_frequency', $notification_frequency );
				}
			}

			$misc_settings['converted_minutes_to_milliseconds'] = true;
			update_option( 'cartbounty_misc_settings', $misc_settings );

			/**
			 * Since version 7.1.6
			 * Due to moving to a different time interval add_custom_wp_cron_intervals() functions
			 */
			if( wp_next_scheduled( 'cartbounty_remove_empty_carts_hook' ) ){
				wp_clear_scheduled_hook( 'cartbounty_remove_empty_carts_hook' );
			}

		}

		transfer_time_to_milliseconds();

		/**
		 * Since version 8.0
		 * This code will be removed in later versions
		 */
		if( get_option( 'cartbounty_review_submitted' ) ){
			update_option( 'cartbounty_submitted_notices', array( 'review' => 1 ) );
			delete_option( 'cartbounty_review_submitted' );
		}
		/* End of this temporary block */

		/**
		 * Since version 8.1
		 * Transferring deprecated multiple sepparate options into a acouple single options.
		 * This code will be removed in later versions
		 */
		function transfer_deprecated_options(){
			//Transferring general settings options
			if( get_option( 'cartbounty_notification_email' ) || get_option( 'cartbounty_lift_email' ) || get_option( 'cartbounty_hide_images' ) || get_option( 'cartbounty_exclude_anonymous_carts' ) || get_option( 'cartbounty_exclude_recovered' ) || get_option( 'cartbounty_notification_frequency' ) ){ //If deprecated options detected
				$notification_frequency = get_option( 'cartbounty_notification_frequency' );

				if( isset( $notification_frequency['interval'] ) ){
					$notification_frequency = $notification_frequency['interval'];
				}

				$existing_settings = array(
					'exclude_anonymous_carts' 	=> get_option( 'cartbounty_exclude_anonymous_carts' ),
					'notification_email' 		=> get_option( 'cartbounty_notification_email' ),
					'notification_frequency' 	=> $notification_frequency,
					'exclude_recovered' 		=> get_option( 'cartbounty_exclude_recovered' ),
					'lift_email'				=> get_option( 'cartbounty_lift_email' ),
					'hide_images'				=> get_option( 'cartbounty_hide_images' ),
				);

				update_option( 'cartbounty_main_settings', $existing_settings );
			}

			//Transferring Exit Intent options
			if ( get_option( 'cartbounty_exit_intent_status' ) || get_option( 'cartbounty_exit_intent_type' ) || get_option( 'cartbounty_exit_intent_heading' ) || get_option( 'cartbounty_exit_intent_content' ) || get_option( 'cartbounty_exit_intent_image' ) ){ //If deprecated option detected
				$existing_settings = array(
					'status' 			=> get_option( 'cartbounty_exit_intent_status' ),
					'test_mode' 		=> get_option( 'cartbounty_exit_intent_test_mode' ),
					'style' 			=> get_option( 'cartbounty_exit_intent_type' ),
					'heading' 			=> get_option( 'cartbounty_exit_intent_heading' ),
					'content' 			=> get_option( 'cartbounty_exit_intent_content' ),
					'main_color' 		=> get_option( 'cartbounty_exit_intent_main_color' ),
					'inverse_color' 	=> get_option( 'cartbounty_exit_intent_inverse_color' ),
					'image' 			=> get_option( 'cartbounty_exit_intent_image' ),
				);

				update_option( 'cartbounty_exit_intent_settings', $existing_settings );
			}

			//Transferring reports fields
			if( get_option( 'cartbounty_active_quick_stats' ) || get_option( 'cartbounty_active_charts' ) || get_option( 'cartbounty_chart_type' ) || get_option( 'cartbounty_top_product_count' ) ){ //If deprecated option detected
				$existing_settings = array(
					'quick_stats' 			=> get_option( 'cartbounty_active_quick_stats' ),
					'charts' 				=> get_option( 'cartbounty_active_charts' ),
					'chart_type' 			=> get_option( 'cartbounty_chart_type' ),
					'top_product_count' 	=> 5,
				);

				update_option( 'cartbounty_report_settings', $existing_settings );
			}

			//Transferring WordPress recovery fields
			if( get_option( 'cartbounty_automation_from_name' ) || get_option( 'cartbounty_automation_from_email' ) || get_option( 'cartbounty_automation_reply_email' ) ){ //If deprecated option detected
				$existing_settings = array(
					'from_name' 			=> get_option( 'cartbounty_automation_from_name' ),
					'from_email' 			=> get_option( 'cartbounty_automation_from_email' ),
					'reply_email' 			=> get_option( 'cartbounty_automation_reply_email' ),
				);

				update_option( 'cartbounty_automation_settings', $existing_settings );
			}

			//Transferring notices
			if( get_option( 'cartbounty_cron_warning' ) ){ //If deprecated option detected
				$existing_settings = array(
					'cron_warning' 		=> get_option( 'cartbounty_cron_warning' ),
				);

				update_option( 'cartbounty_submitted_warnings', $existing_settings );
			}

			//Transferring misc settings
			if( get_option( 'cartbounty_version_number' ) ){ //If deprecated option detected
				$existing_settings = array(
					'version_number' 					=> get_option( 'cartbounty_version_number' ),
					'recoverable_carts' 				=> get_option( 'cartbounty_recoverable_cart_count' ),
					'anonymous_carts' 					=> get_option( 'cartbounty_anonymous_cart_count' ),
					'recovered_carts' 					=> get_option( 'cartbounty_recovered_cart_count' ),
					'time_bubble_displayed' 			=> get_option( 'cartbounty_last_time_bubble_displayed' ),
					'time_bubble_steps_displayed' 		=> get_option( 'cartbounty_last_time_bubble_steps_displayed' ),
					'times_review_declined' 			=> get_option( 'cartbounty_times_review_declined' ),
					'email_table_exists' 				=> get_option( 'cartbounty_email_table_exists' ),
					'converted_minutes_to_milliseconds' 	=> get_option( 'cartbounty_converted_minutes_to_miliseconds' ),
				);

				update_option( 'cartbounty_misc_settings', $existing_settings );
			}

			//Deleting options that will no longer be required
			delete_option( 'cartbounty_exclude_anonymous_carts' );
			delete_option( 'cartbounty_notification_email' );
			delete_option( 'cartbounty_notification_frequency' );
			delete_option( 'cartbounty_exclude_recovered' );
			delete_option( 'cartbounty_lift_email' );
			delete_option( 'cartbounty_hide_images' );
			delete_option( 'cartbounty_exit_intent_status' );
			delete_option( 'cartbounty_exit_intent_test_mode' );
			delete_option( 'cartbounty_exit_intent_type' );
			delete_option( 'cartbounty_exit_intent_heading' );
			delete_option( 'cartbounty_exit_intent_content' );
			delete_option( 'cartbounty_exit_intent_main_color' );
			delete_option( 'cartbounty_exit_intent_inverse_color' );
			delete_option( 'cartbounty_exit_intent_image' );
			delete_option( 'cartbounty_active_quick_stats' );
			delete_option( 'cartbounty_active_charts' );
			delete_option( 'cartbounty_chart_type' );
			delete_option( 'cartbounty_top_product_count' );
			delete_option( 'cartbounty_automation_from_name' );
			delete_option( 'cartbounty_automation_from_email' );
			delete_option( 'cartbounty_automation_reply_email' );
			delete_option( 'cartbounty_cron_warning' );
			delete_option( 'cartbounty_version_number' );
			delete_option( 'cartbounty_recoverable_cart_count' );
			delete_option( 'cartbounty_anonymous_cart_count' );
			delete_option( 'cartbounty_recovered_cart_count' );
			delete_option( 'cartbounty_last_time_bubble_displayed' );
			delete_option( 'cartbounty_last_time_bubble_steps_displayed' );
			delete_option( 'cartbounty_times_review_declined' );
			delete_option( 'cartbounty_email_table_exists' );
			delete_option( 'cartbounty_converted_minutes_to_miliseconds' );
		}

		transfer_deprecated_options();
		/* End of this temporary block */
	}
}