<?php
/**
 * The Light email template for sending CartBounty abandoned cart reminder
 *
 * This template can be overridden by copying it to 
 * yourtheme/templates/emails/cartbounty-email-light.php or
 * yourtheme/templates/cartbounty-email-light.php or
 * yourtheme/cartbounty-email-light.php

 * If a new CartBounty version is released with an updated template file, you
 * may need to replace the old template file with the new one to maintain compatibility.

 * @package    CartBounty - Save and recover abandoned carts for WooCommerce/Templates
 * @author     Streamline.lv
 * @version    8.9
 */

if (!defined( 'ABSPATH' )){ //Don't allow direct access
	exit;
}?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title><?php echo esc_html( $args['heading'] ); ?></title>
	<style type="text/css">
		#outlook a {
			padding: 0;
		}

		body{
			width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; margin: 0; padding: 0;
		}

		@media only screen and (max-width: 650px) {
			.cartbounty-email-image{
				width: 100% !important;
			}

			.cartbounty-email-footer,
			.cartbounty-email-unsubscribe{
				font-size: 14px !important;
			}

			h1{
				font-size: 22px !important;
			}

			.cartbounty-email-contents{
				padding-left: 10px !important;
				padding-right: 10px !important;
			}
		}
	</style>
</head>
<body>
	<table cellpadding="0" cellspacing="0" border="0" style="background-color: <?php echo esc_attr( $args['background_color'] ); ?>; width: 100%; height: 100%; line-height: 100%; margin: 0; padding: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
		<tr>
			<td style="padding: 30px 10px 20px; margin: 0; width: 100%; height: 100%;">
				<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
					<?php if( $args['include_image'] ):?>
					<tr>
						<td valign="top">
							<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-top-left-radius: 6px;border-top-right-radius: 6px; border-bottom-right-radius: 0; border-bottom-left-radius: 0;">
								<tr>
									<td valign="top" width="650">
										<img class="cartbounty-email-image" src="<?php echo esc_url( $args['main_image'] ); ?>" alt="<?php echo esc_attr( get_option( 'blogname' ) );?>" title="<?php echo esc_attr( get_option( 'blogname' ) );?>" width="650" height="auto" style="display:block; -ms-interpolation-mode: bicubic; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; border: none 0;" />
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td valign="top" style="background-color: <?php echo esc_attr( $args['main_color'] ); ?>; padding: 50px 0; border-bottom: 2px solid <?php echo esc_attr( $args['border_color'] );?>;">
							<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
								<tr>
									<td valign="top" width="650">
										<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
											<tr>
												<td class="cartbounty-email-contents" width="650" valign="top" style="text-align: center; padding: 0 50px;">
													<?php do_action('cartbounty_automation_before_title'); ?>
													<?php echo apply_filters( 'cartbounty_automation_title_html', sprintf('<h1 style="color: %s; font-size: 28px; line-height: 1.3; letter-spacing: normal; font-weight: 600; margin: 0; padding: 0; display: block; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif;">%s</h1>', esc_attr( $args['text_color'] ), esc_html( $args['heading'] ) ) );?>
													<?php do_action('cartbounty_automation_after_title'); ?>
													<?php echo apply_filters( 'cartbounty_automation_intro_html', sprintf('<p style="color: %s; font-size: 16px; line-height: 1.3; margin: 10px 0; padding: 0; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif;">%s</p>', esc_attr( $args['text_color'] ), esc_html( $args['content'] ) ) );?>
													<?php do_action('cartbounty_automation_after_intro'); ?>
												</td>
											</tr>
										</table>
										<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
											<tr>
												<td valign="top" style="padding: 20px 0 0; text-align: center;">
													<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
														<tr>
															<td width="650" valign="middle" style="mso-line-height-rule: exactly; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding: 0 50px; text-align: center;">
																<?php $button_html = sprintf(
																	'<a href="%1$s" title="%4$s" style="margin: 0; outline: none; padding: 0; box-shadow: none;"><span style="padding: 18px 35px; background-color: %3$s; border-radius: 4px; color: %2$s; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif; display:inline-block; border: 0px none; font-size: 17px; font-weight: bold; line-height: 1; letter-spacing: normal; text-align: center; text-decoration: none; outline: none;">%4$s</span></a>',
																	esc_url( $args['recovery_link'] ),
																	esc_attr( $args['main_color'] ),
																	esc_attr( $args['button_color'] ),
																	esc_html__( 'Complete checkout', 'woo-save-abandoned-carts' ) );

																	$button_html = apply_filters( 'cartbounty_automation_button_html', $button_html, $args );
																	echo $button_html;
																?>
																<?php do_action('cartbounty_automation_after_button'); ?>
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" style="padding-top: 30px;">
							<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
								<tr>
									<td class="cartbounty-email-footer" width="650" valign="top" style="text-align: center; font-size: 12px; line-height: 1.3; color: <?php echo esc_attr( $args['footer_color'] ); ?>; font-family: 'Open Sans', Roboto, 'San Francisco', Arial, Helvetica, sans-serif;">
										<?php do_action('cartbounty_automation_footer_start'); ?>
										<?php echo apply_filters( 'cartbounty_automation_copyright', sprintf('%s © %s %s. %s', esc_html__( 'Copyright', 'woo-save-abandoned-carts' ), esc_html( $args['current_year'] ), get_option( 'blogname' ), esc_html__( 'All rights reserved.', 'woo-save-abandoned-carts' ) ) );?>
										<br/>
										<?php echo wp_kses_post( apply_filters( 'cartbounty_automation_footer_address_1', $args['store_address']['address_1'])); ?>
										<br/>
										<?php echo wp_kses_post( apply_filters( 'cartbounty_automation_footer_address_2', $args['store_address']['address_2'])); ?>
										<br/>
										<p style="margin: 20px 0 0; padding: 0;"><?php echo apply_filters( 'cartbounty_automation_unsubscribe_html', sprintf('<a class="cartbounty-email-unsubscribe" href="%1$s" title="%2$s" style="text-decoration: none; color: %3$s; font-size: 12px; line-height: 1.3; text-decoration: underline; font-family: \'Open Sans\', Roboto, \'San Francisco\', Arial, Helvetica, sans-serif;">%2$s</a>', esc_url( $args['unsubscribe_link'] ), esc_attr__( 'Unsubscribe', 'woo-save-abandoned-carts' ), esc_attr( $args['footer_color'] ) ) );?></p>
										<?php do_action('cartbounty_automation_footer_end'); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>