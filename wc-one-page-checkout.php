<?php
/*
Plugin Name: TT One-Page Checkout for WooCommerce
Plugin URI: http://terrytsang.com/product/tt-woocommerce-one-page-checkout/
Description: Allow you to combine WooCommerce Cart page into the checkout page and customize the texts or button labels within the new page
Version: 1.0.1
Author: Terry Tsang
Author URI: https://terrytsang.com
*/

/*  Copyright 2012-2022 Terry Tsang (email: terrytsang811@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

// Define plugin name
define('wc_plugin_name_one_page_checkout', 'TT One-Page Checkout for WooCommerce');

// Define plugin version
define('wc_version_one_page_checkout', '1.0.1');


// Checks if the WooCommerce plugins is installed and active.
if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	if(!class_exists('WooCommerce_One_Page_Checkout')){
		class WooCommerce_One_Page_Checkout{

			public static $plugin_prefix;
			public static $plugin_url;
			public static $plugin_path;
			public static $plugin_basefile;

			/**
			 * Gets things started by adding an action to initialize this plugin once
			 * WooCommerce is known to be active and initialized
			 */
			public function __construct(){
				load_plugin_textdomain('wc-one-page-checkout', false, dirname(plugin_basename(__FILE__)) . '/languages/');
				
				WooCommerce_One_Page_Checkout::$plugin_prefix = 'wc_one_page_checkout_';
				WooCommerce_One_Page_Checkout::$plugin_basefile = plugin_basename(__FILE__);
				WooCommerce_One_Page_Checkout::$plugin_url = plugin_dir_url(WooCommerce_One_Page_Checkout::$plugin_basefile);
				WooCommerce_One_Page_Checkout::$plugin_path = trailingslashit(dirname(__FILE__));


				$this->positions = array('top' => 'Top', 'bottom' => 'Bottom');
				
				$this->options_one_page_checkout = array(
					'one_page_checkout_enabled' => '',
					'one_page_checkout_position' => __( 'Top', "woocommerce-one-page-checkout" ),
					'one_page_checkout_addtocart_text' => __( 'Add to cart', "woocommerce-one-page-checkout" ),
					'one_page_checkout_placeorder_text' => __( 'Place order', "woocommerce-one-page-checkout" ),
					'one_page_checkout_ordernotes_text' => __( 'Order notes', "woocommerce-one-page-checkout" ),
				);
	
				$this->saved_options_one_page_checkout = array();
				
				add_action('woocommerce_init', array(&$this, 'init'));
			}

			/**
			 * Initialize extension when WooCommerce is active
			 */
			public function init(){
				
				//add menu link for the plugin (backend)
				add_action( 'admin_menu', array( &$this, 'add_menu_one_page_checkout' ) );

				add_action( 'admin_enqueue_scripts', array( &$this, 'wc_onepage_admin_scripts' ) );
				
				if(get_option('one_page_checkout_enabled'))
				{
					if(get_option('one_page_checkout_position') == 'top')
						add_action( 'woocommerce_before_checkout_form', array( &$this, 'tt_cart_checkout_page') );
					else
						add_action( 'woocommerce_after_checkout_form', array( &$this, 'tt_cart_checkout_page') );

					if(get_option('one_page_checkout_addtocart_text') != '')
					{
						add_filter( 'woocommerce_loop_add_to_cart_link', array( &$this, 'tt_add_to_cart_text_loop' ) );
						add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'tt_add_to_cart_text' ) ) ;
					}

					if(get_option('one_page_checkout_placeorder_text') != '')
						add_filter( 'woocommerce_order_button_text', array( &$this, 'tt_custom_button_placeorder_text' ) );

					if(get_option('one_page_checkout_ordernotes_text') != '') 
					{
						add_filter( 'woocommerce_checkout_fields' , array( &$this, 'tt_change_checkout_ordernotes_fields' ) );
					}

					add_filter( 'woocommerce_add_to_cart_redirect', array( &$this, 'tt_skip_cart_redirect_to_checkout' ) );

					add_action( 'template_redirect', array( &$this, 'tt_empty_cart_home') );

					add_filter( 'wc_add_to_cart_message_html', '__return_false' );

					add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', array( &$this, 'tt_skip_add_to_cart_sold_individually_in_cart' ), 10, 2 );
				}
			}

			public function tt_change_checkout_ordernotes_fields( $fields ) {
				$order_notes_label = get_option('one_page_checkout_ordernotes_text') ? get_option('one_page_checkout_ordernotes_text') : 'Order notes';

			     $fields['order']['order_comments']['label'] = $order_notes_label;
			     return $fields;
			}

			public function tt_cart_checkout_page() {
 
				if ( is_wc_endpoint_url( 'order-received' ) ) return;
				 
				echo do_shortcode('[woocommerce_cart]');
				 
			}

			public function tt_add_to_cart_text_loop( $add_to_cart_html ) {
				$one_page_checkout_addtocart_text	= get_option( 'one_page_checkout_addtocart_text' ) ? get_option( 'one_page_checkout_addtocart_text' ) : __( 'Add to cart', "woocommerce-one-page-checkout" );

				return str_replace( 'Add to cart', $one_page_checkout_addtocart_text, $add_to_cart_html );
			}

			public function tt_add_to_cart_text( $product ){
				$one_page_checkout_addtocart_text	= get_option( 'one_page_checkout_addtocart_text' ) ? get_option( 'one_page_checkout_addtocart_text' ) : __( 'Add to cart', "woocommerce-one-page-checkout" );

				return $one_page_checkout_addtocart_text;
			}

			public function tt_custom_button_placeorder_text( $button_text ) {
				$one_page_checkout_placeorder_text	= get_option( 'one_page_checkout_placeorder_text' ) ? get_option( 'one_page_checkout_placeorder_text' ) : __( 'Place order', "woocommerce-one-page-checkout" );

				return $one_page_checkout_placeorder_text;
			}

			public function tt_skip_cart_redirect_to_checkout( $url ) {
				return wc_get_checkout_url();
			}

			public function tt_empty_cart_home() {
			   if ( (is_cart() || is_checkout()) && 0 == WC()->cart->get_cart_contents_count() ) {
			      wp_safe_redirect( home_url() );
			      exit;
			   } 
			}

			public function tt_skip_add_to_cart_sold_individually_in_cart( $found_in_cart, $product_id ) {
			    if ( $found_in_cart ) {
			        wp_redirect( wc_get_checkout_url() );
			        exit;
			    }
			    
			    return $found_in_cart;
			}
			
			public function wc_onepage_admin_scripts($hook){
				/* Register admin stylesheet. */
				wp_register_style( 'admin-css', plugins_url('/assets/css/admin.css', __FILE__) );
				wp_register_script( 'admin-js', plugins_url('/assets/js/script.js', __FILE__), array( 'jquery-ui-core', 'jquery-ui-datepicker' ), 1, true);
				wp_register_script( 'chosen-jquery', plugins_url('/lib/chosen/js/chosen.jquery.js', __FILE__), array( 'jquery', 'jquery-ui-datepicker' ), '1', true);

				wp_enqueue_style( 'admin-css' );
				wp_enqueue_script( 'admin-js' );
				wp_enqueue_style( 'jquery-style', plugins_url('/assets/css/jquery-ui.css', __FILE__));
			}
			
			public function woo_one_page_form() {
				global $woocommerce, $post, $wpdb;
				
				$one_page_checkout_position	= get_option( 'one_page_checkout_position' ) ? get_option( 'one_page_checkout_position' ) : 'top';
		
				
				$info_message = '';
			
			}
			
			/**
			 * Add a menu link to the woocommerce section menu
			 */
			function add_menu_one_page_checkout() {
				$wc_page = 'woocommerce';
				$onepage_settings_page = add_submenu_page( $wc_page , __( 'TT One-Page Checkout', "woocommerce-one-page-checkout" ), __( 'TT One-Page Checkout', "woocommerce-one-page-checkout" ), 'manage_options', 'wc-one-page-checkout', array(
						&$this,
						'settings_page_one_page_checkout'
				));
			}
			
			/**
			 * Create the settings page content
			 */
			public function settings_page_one_page_checkout() {
			
				// If form was submitted
				if ( isset( $_POST['submitted'] ) )
				{
					check_admin_referer( "woocommerce-one-page-checkout" );
	
					$this->saved_options_one_page_checkout['one_page_checkout_enabled'] = ! isset( $_POST['one_page_checkout_enabled'] ) ? '1' : sanitize_text_field( $_POST['one_page_checkout_enabled'] );
					$this->saved_options_one_page_checkout['one_page_checkout_position'] = ! isset( $_POST['one_page_checkout_position'] ) ? 'top' : sanitize_text_field( $_POST['one_page_checkout_position'] );
					$this->saved_options_one_page_checkout['one_page_checkout_addtocart_text'] = ! isset( $_POST['one_page_checkout_addtocart_text'] ) ? 'Add to cart' : sanitize_text_field( $_POST['one_page_checkout_addtocart_text'] );
					$this->saved_options_one_page_checkout['one_page_checkout_placeorder_text'] = ! isset( $_POST['one_page_checkout_placeorder_text'] ) ? 'Place order' : sanitize_text_field( $_POST['one_page_checkout_placeorder_text'] );
					$this->saved_options_one_page_checkout['one_page_checkout_ordernotes_text'] = ! isset( $_POST['one_page_checkout_ordernotes_text'] ) ? 'Order notes' : sanitize_text_field( $_POST['one_page_checkout_ordernotes_text'] );

					foreach($this->options_one_page_checkout as $field => $value)
					{
						$option_one_page_checkout = get_option( $field );
			
						if($option_one_page_checkout != $this->saved_options_one_page_checkout[$field])
							update_option( $field, $this->saved_options_one_page_checkout[$field] );
					}
						
					// Show message
					echo '<div id="message" class="updated fade"><p>' . __( 'WooCommerce One-Page Checkout options saved.', "woocommerce-one-page-checkout" ) . '</p></div>';
				}

				$checked_enabled = '';
			
				$one_page_checkout_enabled	= get_option( 'one_page_checkout_enabled' );
				$one_page_checkout_position	= get_option( 'one_page_checkout_position' ) ? get_option( 'one_page_checkout_position' ) : 'top';
				$one_page_checkout_addtocart_text	= get_option( 'one_page_checkout_addtocart_text' ) ? get_option( 'one_page_checkout_addtocart_text' ) : 'Add to cart';
				$one_page_checkout_placeorder_text	= get_option( 'one_page_checkout_placeorder_text' ) ? get_option( 'one_page_checkout_placeorder_text' ) : 'Place order';
				$one_page_checkout_ordernotes_text	= get_option( 'one_page_checkout_ordernotes_text' ) ? get_option( 'one_page_checkout_ordernotes_text' ) : 'Order notes';

				if($one_page_checkout_enabled)
					$checked_enabled = 'checked="checked"';

			
				$actionurl = sanitize_url( $_SERVER['REQUEST_URI'] );
				$nonce = wp_create_nonce( "woocommerce-one-page-checkout" );
			
			
				// Configuration Page
			
				?>
				<div id="icon-options-general" class="icon32"></div>
				<h3><?php _e( 'TT One-Page Checkout', "woocommerce-one-page-checkout"); ?></h3>

						<form action="<?php echo esc_url( $actionurl ); ?>" method="post">
						<table>
								<tbody>
									<tr>
										<td colspan="2">
											<table class="widefat fixed" cellspacing="2" cellpadding="2" border="0">
												<tr>
													<td width="300"><?php _e( 'Enable', "woocommerce-one-page-checkout" ); ?></td>
													<td>
														<input class="checkbox" name="one_page_checkout_enabled" id="one_page_checkout_enabled" value="0" type="hidden">
														<input class="checkbox" name="one_page_checkout_enabled" id="one_page_checkout_enabled" value="1" <?php echo esc_attr( $checked_enabled ); ?> type="checkbox">
													</td>
												</tr>
											
												<tr>
													<td><?php _e( 'Position Cart Form at Checkout Page', "woocommerce-one-page-checkout" ); ?></td>
													<td>
														<select type="text" id="one_page_checkout_position" name="one_page_checkout_position" value="<?php echo esc_attr( $one_page_checkout_position ); ?>">
															<?php foreach($this->positions as $choice => $choice_name): ?>
															<?php if($one_page_checkout_position == $choice){ ?>
																<option value="<?php echo esc_attr( $choice ); ?>" selected="selected"><?php echo esc_attr( $choice_name ); ?></option>
															<?php } else { ?>
																<option value="<?php echo esc_attr( $choice ); ?>"><?php echo esc_attr( $choice_name ); ?></option>
															<?php } ?>
														<?php endforeach; ?>
														</select>

													</td>
												</tr>

												<tr>
													<td><?php _e( '"Add to cart" Button Text', "woocommerce-one-page-checkout" ); ?></td>
													<td>
														<input type="text" id="one_page_checkout_addtocart_text" name="one_page_checkout_addtocart_text" value="<?php echo esc_attr( $one_page_checkout_addtocart_text ); ?>" placeholder="Add to cart" size="26" />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Order notes" Field Label', "woocommerce-one-page-checkout" ); ?></td>
													<td>
														<input type="text" id="one_page_checkout_ordernotes_text" name="one_page_checkout_ordernotes_text" value="<?php echo esc_attr( $one_page_checkout_ordernotes_text ); ?>" placeholder="Order notes" size="26" />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Place order" Button Text', "woocommerce-one-page-checkout" ); ?></td>
													<td>
														<input type="text" id="one_page_checkout_placeorder_text" name="one_page_checkout_placeorder_text" value="<?php echo esc_attr( $one_page_checkout_placeorder_text ); ?>" placeholder="Place order" size="26" />
													</td>
												</tr>

												<tr><td><hr /></td></tr>

												<tr>
													<td><?php _e( '"Billing details" Heading Text', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_billing" name="one_page_checkout_billing" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Ship to a different address?" Heading Text', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_shipping" name="one_page_checkout_shipping" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Your order" Heading Text', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_yourorder" name="one_page_checkout_yourorder" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr><td><hr /></td></tr>

												<tr>
													<td><?php _e( '"Coupon code" Placeholder Text', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_couponcode" name="one_page_checkout_couponcode" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Apply coupon" Button Text', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_applycoupon" name="one_page_checkout_applycoupon" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Update cart" Button Text', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_updatecart" name="one_page_checkout_updatecart" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Have a coupon? Click here to enter your code" Message', "woocommerce-one-page-checkout" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="one_page_checkout_couponmessage" name="one_page_checkout_couponmessage" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												


												
												
											</table>
										</td>
									</tr>
									<tr><td>&nbsp;</td></tr>
									<tr>
										<td colspan=2">
											<input class="button-primary" type="submit" name="Save" value="<?php _e('Save Options', "woocommerce-one-page-checkout"); ?>" id="submitbutton" />
											<input type="hidden" name="submitted" value="1" /> 
											<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
										</td>
									</tr>
								</tbody>
						</table>
						</form>


					<br />
				<hr />
				<div style="height:30px"></div>
				<div class="center woocommerce-BlankState">
					<p><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>logo-terrytsang.png" title="Terry Tsang" alt="Terry Tsang" /></p>
					<h2 class="woocommerce-BlankState-message">Hi, I'm <a href="https://terrytsang.com" target="_blank">Terry Tsang</a> from 3 Mini Monsters. I have built WooCommerce plugins since 10 years ago and always find ways to make WooCommerce experience better through my products and articles. Thanks for using my plugin and do share around if you love this.</h2>

					<a class="woocommerce-BlankState-cta button" target="_blank" href="https://terrytsang.com/products">Check out our WooCommerce plugins</a>

					<a class="woocommerce-BlankState-cta button-primary button" href="https://terrytsang.com/product/tt-woocommerce-one-page-checkout-pro" target="_blank">Upgrade to TT One-Page Checkout PRO</a>

					
				</div>

				<br /><br /><br />

				<div class="components-card is-size-medium woocommerce-marketing-recommended-extensions-card woocommerce-marketing-recommended-extensions-card__category-coupons woocommerce-admin-marketing-card">
					<div class="components-flex components-card__header is-size-medium"><div>
						<span class="components-truncate components-text"></span>
						<div style="margin: 20px 20px">Try my other plugins to power up your online store and bring more sales/leads to you.</div>
					</div>
				</div>

				<div class="components-card__body is-size-medium">
					<div class="woocommerce-marketing-recommended-extensions-card__items woocommerce-marketing-recommended-extensions-card__items--count-6">
						<a href="https://terrytsang.com/product/tt-woocommerce-add-to-cart-buy-now-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-add-to-cart-buy-now.png" title="WooCommerce Add to Cart Buy Now" alt="WooCommerce Add to Cart Buy Now" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Add to Cart Buy Now</h4>
								<p style="color:#333333;">Customize the "Add to cart" button and add a simple “Buy Now” button to your WooCommerce website.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-discount-option-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-custom-checkout.png" title="WooCommerce Discount Option" alt="WooCommerce Discount Option" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Discount Option</h4>
								<p style="color:#333333;">Add a fixed fee/percentage discount based on minimum order amount, products, categories, date range and day.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-donation-checkout-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-donation-checkout.png" title="WooCommerce Donation Checkout" alt="WooCommerce Donation Checkout" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Donation Checkout</h4>
								<p style="color:#333333;">Enable customers to topup their donation/tips at the checkout page.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-extra-fee-option-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-custom-checkout.png" title="WooCommerce Extra Fee Options" alt="WooCommerce Extra Fee Options" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Extra Fee Option</h4>
								<p style="color:#333333;">Add a discount based on minimum order amount, product categories, products and date range.</p>
							</div>
						</a>

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-coming-soon/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php //echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-coming-soon-product.png" title="WooCommerce Coming Soon Product" alt="WooCommerce Coming Soon Product" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Coming Soon</h4>
								<p style="color:#333333;">Display countdown clock at coming-soon product page.</p>
							</div>
						</a> -->

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-product-badge/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php //echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-product-badge.png" title="WooCommerce Product Badge" alt="WooCommerce Product Badge" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Product Badge</h4>
								<p style="color:#333333;">Add product badges liked Popular, Sales, Featured to the product.</p>
							</div>
						</a> -->

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-product-catalog/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php //echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-product-catalog.png" title="WooCommerce Product Catalog" alt="WooCommerce Product Catalog" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Product Catalog</h4>
								<p style="color:#333333;">Hide Add to Cart / Checkout button and turn your website into product catalog.</p>
							</div>
						</a> -->

					
					</div>
				</div>
					

				
			<?php
			}
			
			function update_checkout(){
			?>
				<script>
				jQuery(document).ready(function($){
				$('.payment_methods input.input-radio').live('click', function(){
					$('#billing_country, #shipping_country, .country_to_state').trigger('change');
				})
				});
				</script>
			<?php
			}
			
			/**
			 * Get the setting options
			 */
			function get_options() {
				
				foreach($this->options_one_page_checkout as $field => $value)
				{
					$array_options[$field] = get_option( $field );
				}
					
				return $array_options;
			}
			
			
		}//end class
			
	}//if class does not exist
	
	$woocommerce_one_page_checkout = new WooCommerce_One_Page_Checkout();
}
else{
	add_action('admin_notices', 'wc_one_page_checkout_error_notice');
	function wc_one_page_checkout_error_notice(){
		global $current_screen;
		if($current_screen->parent_base == 'plugins'){
			echo '<div class="error"><p>'.__(wc_plugin_name_one_page_checkout.' requires <a href="http://www.woocommerce.com/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.').'</p></div>';
		}
	}
}

?>