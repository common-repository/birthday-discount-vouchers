<?php
/**
 * Woo Birthday Discount Voucher Settings
 *
 * @author 		Magnigenie
 * @category 	Admin
 * @version     1.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (  class_exists( 'WC_Settings_Page' ) ) :

/**
 * WC_Settings_Accounts
 */
class WC_Settings_Birthday_Discount_Vouchers extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'birthday_discount_vouchers';
		$this->label = __( 'Birthday Discount Vouchers', 'wbdv' );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'admin_footer', array( $this, 'wbdv_add_scripts') );
		add_action( 'woocommerce_admin_field_wbdv_wpeditor', array( $this, 'wbdv_display_editor' ) );
		add_action( 'woocommerce_admin_settings_sanitize_option_wbdv_email', array( $this, 'wbdv_save_editor_val' ), 10, 3 );
		add_action( 'woocommerce_admin_field_uploader', array( $this, 'wbdv_display_uploader' ) );
		add_action( 'woocommerce_admin_field_custom_search_products', array( $this, 'wbdv_search_products' ) );

		add_action( 'woocommerce_admin_field_custom_exclude_products', array( $this, 'wbdv_exclude_products' ) );

		//add_action( 'show_user_profile', array($this, 'wbdv_user_birth_day_field') );

	}
	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {

		$wbdv_products = get_option( 'wbdv_products' );
		$products = array();
		if ( is_array( $wbdv_products ) ) {
			foreach ( $wbdv_products as $product_id ) {
				$product = wc_get_product( $product_id );
				$products[$product_id] = wp_kses_post( $product->get_formatted_name() );
			}
		}
		$wbdv_exclude_products = get_option( 'wbdv_exclude_products' );
		$products_exclude = array();
		if ( is_array( $wbdv_exclude_products ) ) {
			foreach ( $wbdv_exclude_products as $product_id ) {
				$product = wc_get_product( $product_id );
				$products_exclude[$product_id] = wp_kses_post( $product->get_formatted_name() );
			}
		}
		$categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		$cats = array();
		if ( $categories ) foreach ( $categories as $cat ) $cats[$cat->term_id] = esc_html( $cat->name );
		return apply_filters( 'woocommerce_' . $this->id . '_settings', array(

			array(	'title' => __( 'Birthday Discount Vouchers Settings', 'wbdv' ), 'type' => 'title','desc' => '', 'id' => 'birthday_discount_vouchers_title' ),
      	array(
					'title' 			=> __( 'Enable', 'wbdv' ),
					'desc' 			=> __( 'Enable Birthday Discount Vouchers.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_enabled',
					'default' 			=> 'no'											
				),
      	array(
					'title' 			=> __( 'Enable user change their birthday ', 'wbdv' ),
					'desc' 			=> __( 'Enable this option will allow users to change thir birthday from their account.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_user_bday_enabled',
					'default' 			=> 'no'											
				),
        array(
					'title'   => __( 'Coupon Before Birthday', 'wbdv' ),
					'desc' 	  => __( 'Number of days before the birthday to send coupons', 'wbdv' ),
					'type' 	  => 'number',
					'id'	  => 'wbdv_days_before',
					'css' 	  => 'width: 125px;',
					'default' => '1',
					'custom_attributes' => array( 'min' => '0' )									
				),
        array(
					'title'   => __( 'First Name Text', 'wbdv' ),
					'desc' 	  => __( 'Text that would be shown as label for first name field', 'wbdv' ),
					'type' 	  => 'text',
					'id'	  => 'wbdv_first_name',
					'css' 	  => 'width: 200px;',
					'default' => 'First Name',
				),
        array(
					'title'   => __( 'Last Name Text', 'wbdv' ),
					'desc' 	  => __( 'Text that would be shown as label for last name field', 'wbdv' ),
					'type' 	  => 'text',
					'id'	  => 'wbdv_last_name',
					'css' 	  => 'width: 200px;',
					'default' => 'Last Name',
				),
        array(
					'title'   => __( 'Birthday Text', 'wbdv' ),
					'desc' 	  => __( 'Text that would be shown as label for registration field', 'wbdv' ),
					'type' 	  => 'text',
					'id'	  => 'wbdv_birthday_label',
					'css' 	  => 'width: 200px;',
					'default' => 'Date Of Birth',
				),
				array(
					'title' 	  => __( 'Method for selecting date of birth', 'wbdv' ),
					'desc' 		  => __( 'Select the method by which the users will select their birthday.', 'wbdv' ),
					'id' 		  => 'wbdv_method',
					'type' 		  => 'select',
					'options'	  => array( 'dropdown' => 'Birth date dropdown', 'datepicker' => 'Date Picker', 'jqdatepicker' => 'jQuery Date Picker'),
					'default'	  => 'dropdown',
				),				
				array(
					'title' 	  => __( 'Date Picker Style', 'wbdv' ),
					'desc' 		  => __( 'The plugin comes with 2 different styles of datepicker, choose your favourite.', 'wbdv' ),
					'id' 		  => 'wbdv_dp_style',
					'type' 		  => 'select',
					'options'	  => array( 'classic' => 'Classic', 'overlay' => 'Overlay' ),
					'default'	  => 'classic',
					'css' 	  => 'width: 200px;',
				),
				array(
					'title' 			=> __( 'Firstname required?', 'wbdv' ),
					'desc' 			=> __( 'Enable to make Firstname field as required.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_firstname_required',
					'default' 			=> 'no'											
				),
				array(
					'title' 			=> __( 'Lastname required?', 'wbdv' ),
					'desc' 			=> __( 'Enable to make Lastname field as required.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_lastname_required',
					'default' 			=> 'no'											
				),
				array(
					'title' 			=> __( 'Date of Birth required?', 'wbdv' ),
					'desc' 			=> __( 'Enable to make Date of Birth field as required.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_birthday_required',
					'default' 			=> 'no'											
				),
				array(
					'title' 			=> __( 'Number of times send coupon to the user', 'wbdv' ),
					'desc' 			=> __( 'This option is for number of times a user can get coupon. If you set 0 then user will get coupon in every year on his/her birthday.  ', 'wbdv' ),
					'type' 				=> 'number',
					'id'				=> 'wbdv_coupon_number_of_years',
					'default' 			=> '0',
					'css'		  => 'width:100px',	
				),			
				array(
					'title' 	=> __( "Discount Type", 'wbdv' ),
					'type' 		=> 'select',
					'id'		=> 'wbdv_dis_type',
					'options' 	=> wc_get_coupon_types(),
					'default' 	=> 'percent'
				),
				array(
					'title' 	  => __( 'Coupon prefix', 'wbdv' ),
					'desc' 		  => __( 'Enter a coupon prefix which would be added before the actual generated coupon code. Leave empty for no prefix.', 'wbdv' ),
					'id' 		  => 'wbdv_prefix',
					'type' 		  => 'text',
					'default'	  => '',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Coupon code length', 'wbdv' ),
					'desc' 		  => __( 'Enter a length for the coupon code. Note: the prefix is not counted in coupon code length.', 'wbdv' ),
					'id' 		  => 'wbdv_code_length',
					'type' 		  => 'number',
					'default'	  => '12',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Discount Amount', 'wbdv' ),
					'desc' 		  => __( 'Enter a coupon discount amount', 'wbdv' ),
					'id' 		  => 'wbdv_amount',
					'type' 		  => 'text',
					'default'	  => '10',
					'desc_tip'	  =>  true
				),
                array(
					'title' 			=> __( 'Allow free shipping', 'wbdv' ),
					'desc' 			=> __( 'Check this box if the coupon grants free shipping. The <a href="'.admin_url('admin.php?page=wc-settings&amp;tab=shipping&amp;section=WC_Shipping_Free_Shipping').'">free shipping method</a> must be enabled with the "must use coupon" setting.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_shipping',
					'default' 			=> 'no'										
				),
                array(
					'title' 			=> __( 'Exclude on sale items', 'wbdv' ),
					'desc' 			=> __( 'Check this box if the coupon should not apply to items on sale. Per-item coupons will only work if the item is not on sale. Per-cart coupons will only work if there are no sale items in the cart.', 'wbdv' ),
					'type' 				=> 'checkbox',
					'id'				=> 'wbdv_sale',
					'default' 			=> 'no'										
				),
				array(
					'title' 	  => __( 'Products', 'wbdv_discount' ),
					'desc' 		  => __( 'Products which need to be in the cart to use this coupon or, for "Product Discounts", which products are discounted.', 'wbdv_discount' ),
					'id' 		  => 'wbdv_products',
					'type'    => 'custom_search_products',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Exclude products', 'wbdv_discount' ),
					'desc' 		  => __( 'Products which must not be in the cart to use this coupon or, for "Product Discounts", which products are not discounted.', 'wbdv_discount' ),
					'id' 		  => 'wbdv_exclude_products',
					'type'    => 'custom_exclude_products',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Categories', 'wbdv' ),
					'desc' 		  => __( 'A product must be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will be discounted.', 'wbdv' ),
					'id' 		  => 'wbdv_categories',
					'type' 		  => 'multiselect',
					'class'		  => 'wbdv_cats',
					'css'		  => 'width:300px',
					'default'	  => '',
					'options'     => $cats,
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Exclude Categories', 'wbdv' ),
					'desc' 		  => __( 'Product must not be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will not be discounted.', 'wbdv' ),
					'id' 		  => 'wbdv_exclude_categories',
					'type' 		  => 'multiselect',
					'class'		  => 'wbdv_cats',
					'css'		  => 'width:300px',
					'default'	  => '',
					'options'     => $cats,
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Coupon Validity (in days)', 'wbdv' ),
					'desc' 		  => __( 'Enter number of days the coupon will active from the date of registration of the user. Leave blank for no limit.', 'wbdv' ),
					'id' 		  => 'wbdv_days',
					'type' 		  => 'number',
					'css'		  => 'width:100px',
					'default'	  => '',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Coupon expiry date format', 'wbdv' ),
					'desc' 		  => __( 'Enter the date format for the coupon expiry date which would be mailed to the user. <a href="http://php.net/manual/en/function.date.php" target="_blank">Click here</a> to know about the available types', 'wbdv' ),
					'id' 		  => 'wbdv_date_format',
					'type' 		  => 'text',
					'css'		  => 'width:100px',
					'default'	  => 'jS F Y',
					'desc_tip'	  =>  false
				),
				array(
					'title' 	  => __( 'Minimum Purchase', 'wbdv' ),
					'desc' 		  => __( 'Minimum purchase subtotal in order to be able to use the coupon. Leave blank for no limit', 'wbdv' ),
					'id' 		  => 'wbdv_min_purchase',
					'type' 		  => 'text',
					'default'	  => '',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Maximum Purchase', 'wbdv' ),
					'desc' 		  => __( 'Maximum purchase subtotal in order to be able to use the coupon. Leave blank for no limit', 'wbdv' ),
					'id' 		  => 'wbdv_max_purchase',
					'type' 		  => 'text',
					'default'	  => '',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Restrict Email', 'wbdv' ),
					'desc' 		  => __( 'Allow discount if the purchase is made for the same email id user registered on account creation.', 'wbdv' ),
					'id' 		  => 'wbdv_restrict',
					'type' 		  => 'checkbox',
					'default'	  => 'yes',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Email From Name', 'wbdv' ),
					'desc' 		  => __( 'Enter the name which will appear on the emails.', 'wbdv' ),
					'id' 		  => 'wbdv_email_name',
					'type' 		  => 'text',
					'css'		  => 'width:300px',
					'default'	  => get_bloginfo('name'),
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'From Email', 'wbdv' ),
					'desc' 		  => __( 'Enter the email from which the emails will be sent.', 'wbdv' ),
					'id' 		  => 'wbdv_email_id',
					'type' 		  => 'text',
					'css'		  => 'width:300px',
					'default'	  => get_bloginfo('admin_email'),
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Email Subject', 'wbdv' ),
					'desc' 		  => __( 'This will be email subject for the emails that will be sent to the users.', 'wbdv' ),
					'id' 		  => 'wbdv_email_sub',
					'type' 		  => 'text',
					'css'		  => 'width:100%',
					'default'	  => get_bloginfo('name').' Wishes you a very happy birthday',
					'desc_tip'	  =>  true
				),
				array(
					'title' 	  => __( 'Email Body', 'wpdv' ),
					'desc' 		  => '',
					'id' 		  => 'wbdv_email',
					'type' 		  => 'wbdv_wpeditor',
					'default'	  => '<p>Hi There,</p><p>Wish you a very happy birthday. On the ocassion of your birthday we just want to give a coupon. The coupon code to redeem the discount is <h3>{COUPONCODE}</h3></p><p>The coupon will expire on {COUPONEXPIRY} so make sure to get the benefits while you still have time.</p>',
					'desc_tip'	  =>  true
				),


				array( 'type' => 'sectionend', 'id' => 'simple_wpdv_options'),

		)); // End pages settings
	}


	/**
	* Saves the content fpr wp_editor.
	*
	* @return null saves the value of the option. 
	*
	*/
	public function wbdv_save_editor_val( $value, $option, $raw_value ) {
		update_option( $option['id'], $raw_value  );
	}
	

	/**
	* Output wordpress file uploader.
	*
	* @param array $value array of settings variables.
	* @return null displays the editor. 
	*
	*/
	public function wars_display_uploader( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] ); ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<div class="uploader">
					<input value="<?php echo $option_value; ?>" id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" type="text" />
					<input id="wars_button" class="button" type="button" value="Upload" />
					<div class="wars_image">
						<?php if($option_value != '') { 
							echo '<img src="'.$option_value.'" style="width: 100px;" alt="">';
							} ?>
					</div>
				</div>
			</td>
		</tr>
	<?php
	}

	/**
	* Output wordpress editor for email body condent.
	*
	* @param array $value array of settings variables.
	* @return null displays the editor. 
	*
	*/
	public function wbdv_display_editor( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] ); ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<?php echo $value['desc']; ?>
				<?php wp_editor( $option_value, esc_attr( $value['id'] ) ); ?>
			</td>
		</tr>
	<?php
	}


	/**
	* Product ids
	*/
	public function wbdv_search_products() {
		?>
		<tr valign="top" class="search-products">
			<th><?php _e( 'Products', 'woocommerce' ); ?></th>
			<td>
				<input type="hidden" class="wbdv wc-product-search" data-multiple="true" style="width: 50%;" name="wbdv_products" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="wbdv_ajax_products" data-selected="<?php
					$product_ids = array_filter( array_map( 'absint', explode( ',', get_option( 'wbdv_products' ) ) ) );
					$json_ids    = array();

					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
						}
					}
					echo esc_attr( json_encode( $json_ids ) );
				?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> 
			</td>
		</tr>

	<?php
	}

	/**
	* Exclude Product Ids
	*/
	public function wbdv_exclude_products() {
		?>
		<tr valign="top" class="search-products">
			<th><?php _e( 'Exclude Products', 'woocommerce' ); ?></th>
			<td>
				<input type="hidden" class="wbdv wc-product-search" data-multiple="true" style="width: 50%;" name="wbdv_exclude_products" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="wbdv_ajax_products" data-selected="<?php
					$product_ids = array_filter( array_map( 'absint', explode( ',', get_option( 'wbdv_exclude_products' ) ) ) );
					$json_ids    = array();

					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
						}
					}

					echo esc_attr( json_encode( $json_ids ) );
				?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> 
			</td>
		</tr>


	<?php
	}



	/**
	* Add the required js needed for the plugin to display the list of products using ajax.
	*
	* @return null outputs the scripts on the footer. 
	*
	*/
	public function wbdv_add_scripts() { 
	?>
		<script type="text/javascript">
			jQuery(function($){
			// Ajax product search box
			$( ':input.wbdv.wc-product-search' ).each( function() {
				var select2_args = {
					allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
					placeholder: $( this ).data( 'placeholder' ),
					minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
					escapeMarkup: function( m ) {
						return m;
					},
					ajax: {
						url:            '<?php echo admin_url('admin-ajax.php'); ?>',
						dataType:    'json',
						quietMillis: 250,
						data: function( term ) {
							return {
								term:     term,
								action:   'wbdv_ajax_products',
								security: '<?php echo wp_create_nonce( "wbdv-search-products" ); ?>',
								exclude:  $( this ).data( 'exclude' ),
								include:  $( this ).data( 'include' ),
								limit:    $( this ).data( 'limit' )
							};
						},
						results: function( data ) {
							var terms = [];
							if ( data ) {
								$.each( data, function( id, text ) {
									terms.push( { id: id, text: text } );
								});
							}
							return {
								results: terms
							};
						},
						cache: true
					}
				};

				if ( $( this ).data( 'multiple' ) === true ) {
					select2_args.multiple = true;
					select2_args.initSelection = function( element, callback ) {
						var data     = $.parseJSON( element.attr( 'data-selected' ) );
						var selected = [];

						$( element.val().split( ',' ) ).each( function( i, val ) {
							selected.push({
								id: val,
								text: data[ val ]
							});
						});
						return callback( selected );
					};
					select2_args.formatSelection = function( data ) {
						return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
					};
				} else {
					select2_args.multiple = false;
					select2_args.initSelection = function( element, callback ) {
						var data = {
							id: element.val(),
							text: element.attr( 'data-selected' )
						};
						return callback( data );
					};
				}

				//select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );

				$( this ).select2( select2_args ).addClass( 'enhanced' );
			});


				jQuery('.wbdv-help').click(function(){
					jQuery('#contextual-help-link').click();
				});
				jQuery('#tab-panel-wbdv_help input').click(function(){ 
					jQuery(this).select(); 
				});
				$('.wbdv_cats').select2();			
			});
		</script>
	<?php
	}
}
return new WC_Settings_Birthday_Discount_Vouchers();

endif;



