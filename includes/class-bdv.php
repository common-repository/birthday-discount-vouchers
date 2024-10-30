<?php
class Birthday_Discount_Vouchers_Lite {
  public function __construct() {

    //Check if woocommerce plugin is installed.
    add_action( 'admin_notices', array( $this, 'check_required_plugins' ) );

    if( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == 'birthday-calendar' ) {
      add_action( 'admin_init', array( $this, 'wbdv_get_users_birthday' ));
      add_action( 'admin_footer', array( $this, 'wbdv_add_scripts') );
    }

    //Add setting link for the admin settings
    add_filter( "plugin_action_links_".BDV_BASE, array( $this, 'bdv_settings_link' ) );

    //Add backend settings
    add_filter( 'woocommerce_get_settings_pages', array( $this, 'bdv_settings_class' ) );

    //Add custom field to the registration page
    if( get_option('wbdv_enabled')  == 'yes' ) {
      if( get_option( 'woocommerce_enable_myaccount_registration' ) == 'yes' ) {
          add_action( 'woocommerce_register_form', array( $this, 'wpdv_add_birthday_field' ) );
          add_action( 'woocommerce_register_post', array( $this, 'wbdv_validate_date_of_birth' ), 10, 3 );
          add_action('woocommerce_created_customer', array( $this, 'wpdv_save_birthday_field' ));
        }
      
        if( get_option('woocommerce_enable_signup_and_login_from_checkout') == 'yes' ) {
         add_filter( 'woocommerce_checkout_fields', array( $this, 'wpdv_filter_checkout_fields' ) );
          add_action( 'woocommerce_checkout_order_processed', array( $this, 'wpdv_save_birthday_checkout_field' ), 10, 2 );
        }
      }
      
      add_action( 'wp_ajax_wbdv_ajax_products', array( $this, 'wbdv_ajax_products' ) );

      add_action( 'wp_enqueue_scripts',  array( $this, 'wpdv_enque_scripts' ) );

      add_action('init', array( $this, 'send_birthday_emails_to_users' ) );

      add_action('wbdv_birthday_cron', array( $this, 'wbdv_run_birthday_cron' ) );

      add_action( 'admin_enqueue_scripts',  array( $this, 'wbdv_enque_admin_scripts' ) );

      register_deactivation_hook( __FILE__, array( $this, 'wbdv_deactivate_emails' ) );

      add_action( 'show_user_profile', array( $this, 'wbdv_user_birth_day_field' ) );

      add_action( 'edit_user_profile', array( $this, 'wbdv_user_birth_day_field' ) );

      add_action( 'personal_options_update', array( $this, 'wbdv_save_user_birthday_profile_fields' ) );
      add_action( 'edit_user_profile_update', array( $this, 'wbdv_save_user_birthday_profile_fields' ) );

      add_action( 'woocommerce_edit_account_form', array( $this, 'wbdv_edit_account_form') );

      add_action( 'woocommerce_save_account_details', array( $this, 'wbdv_save_account_details' ), 10, 1 );
    }


    public function wbdv_save_account_details( $user_id ) {
      $birthday_method = get_option( 'wbdv_method' );

      if( $birthday_method == 'datepicker' 
        && !empty( $_POST['wbdv_date_confirm_submit'] ) ) {
        $birthday = sanitize_text_field( $_POST['wbdv_date_confirm_submit'] );
      }
    
      if( $birthday_method == 'dropdown' 
        && !empty( $_POST['birthdate'] ) ) {
        $birthday = sanitize_text_field( $_POST['birthdate'] );
      }

      if( $birthday_method == 'jqdatepicker' && !empty( $_POST['wbdv_date_confirm'] ) ) {
        $birthday = sanitize_text_field( $_POST['wbdv_date_confirm'] );
      }

      if( !empty($birthday) ) {
        $birthday_var = explode('-', $birthday);
        $birthday_var = $birthday_var[1].'-'.$birthday_var[2];
        if( !empty($user_id) ) {
          update_user_meta( $user_id, 'wpdv_birthday', $birthday_var );
          update_user_meta( $user_id, 'wpdv_birthday_calender', $birthday );
        }
      }

    }


    public function wbdv_enque_admin_scripts() {
      if( is_admin() && isset($_GET['tab']) && $_GET['tab'] == 'birthday_discount_vouchers' ) {
        wp_enqueue_style( 'select2-style', plugins_url( 'assets/css/select2.css', BDV_FILE ) );
        wp_enqueue_script( 'wcsd-enhanced-select', plugins_url( 'assets/js/select2.min.js', BDV_FILE ) , array( 'jquery' ), '1.0.0', true );
      }

      wp_localize_script( 'wbdv-enhanced-select', 'wbdv_enhanced_select_params', array(
        'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
        'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
        'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
        'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
        'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
        'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
        'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
        'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
        'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
        'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
        'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
        'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
      ) );

      wp_register_style( 'jquery-ui', plugins_url( 'assets/css/jquery-ui.css', BDV_FILE ) );

      wp_enqueue_style( 'jquery-ui' ); 
      
      wp_enqueue_script('wbdv-admin-script', plugins_url( 'assets/js/wbdv-admin-profile.js', BDV_FILE ), array( 'jquery' ), '1.0.0', true );

      wp_enqueue_script( 'jquery-ui-datepicker' );
     } 

  /**
  *
  * Check if woocommerce is installed and activated and if not
  *
  */
  public function check_required_plugins() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) { ?>
      <div id="message" class="error">
        <p><?php echo BDV_PLUGIN_NAME; ?> requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="<?php echo admin_url('/plugin-install.php?tab=search&amp;type=term&amp;s=WooCommerce'); ?>" target="">WooCommerce</a> first.</p>
      </div>

      <?php
      deactivate_plugins( '/birthday-discount-vouchers/birthday-discount-vouchers.php' );
    }
  }

  /**
  * Add new link for the settings under plugin links
  *
  * @param array $links an array of existing links.
  * @return array of links along with birthday discount vouchers settings link.
  *
  */
  public function bdv_settings_link($links) {
    $settings_link = '<a href="'.admin_url('admin.php?page=wc-settings&tab=birthday_discount_vouchers').'">Settings</a>'; 
    array_unshift( $links, $settings_link ); 
    return $links; 
  }

  public function wbdv_get_users_birthday() {
    if ( get_option('wbdv_enabled')  == 'yes' ) {
      $this->get_users_birthdays();
    }
  }

  public function get_users_birthdays() {
    $args = array(
      'meta_key' => 'wpdv_birthday_calender', 
      );
    $birthday_users = get_users($args);
    $user_array = array();
    if( is_array($birthday_users) ) {
      $data = array();
      foreach( $birthday_users as $k => $birthday_user ) {
        $user_id = $birthday_user->data->ID;
        $user_name = get_user_meta($user_id, 'first_name', true );
        if( empty($user_name) ) {
          $user_obj = get_user_by('id', $user_id);
          $user_name = $user_obj->user_login;
        }
        
        $birthday = get_user_meta($user_id, 'wpdv_birthday_calender', true );
       
        if( !empty($birthday) ) {
          $data[$k]['title'] = $user_name;
          $data[$k]['start'] = $birthday;
          $data[$k]['end'] = $birthday;
        }
      }
    }
    return json_encode($data);
  }

  public function wbdv_edit_account_form() {
    ?>
    <fieldset>
    <legend><?php echo __('Personal information'); ?></legend>
    <?php $this->wpdv_add_birthday_in_account(); ?>
    
  </fieldset>
  <?php
  }

  public function wbdv_add_scripts() {
      wp_enqueue_style( 'wbdv-calender-stylesheet', plugins_url( 'assets/css/fullcalendar.min.css', BDV_FILE ));
      wp_enqueue_script('moment', plugins_url( 'assets/js/moment.min.js', BDV_FILE ));
      wp_enqueue_script('fullcaljs', plugins_url( 'assets/js/fullcalendar.min.js', BDV_FILE ));
      wp_enqueue_style( 'wbdv-custom-style', plugins_url( 'assets/css/wbdv-admin.css', BDV_FILE ));

      ?>

      <script type="text/javascript">
        jQuery(document).ready(function() {
          jQuery('#wbdv-calendar').fullCalendar({
            events: <?php echo $this->get_users_birthdays(); ?>
          });
        });
      </script>
      <?php     
  }

  public function wbdv_run_birthday_cron() {
    if( get_option('wbdv_enabled')  == 'yes' ) {
      $this->wbdv_check_user_birthday();
    }
  }

  public function wbdv_check_user_birthday() {

    $coupon_send_times = get_option('wbdv_coupon_number_of_years');
    $coupon_send_times = intval($coupon_send_times);

    global $woocommerce;

    $today = date("Y-m-d");
    $store_name = get_bloginfo('name');
    $store_url = get_bloginfo('url');
    $mail_before_days = get_option('wbdv_days_before');
    
    if( $mail_before_days == 0 || empty($mail_before_days) )
      $mail_before_days = 0;

    $args = array(
      'meta_key' => 'wpdv_birthday', 
    );
    
    $birthday_users = get_users($args);

    if( is_array($birthday_users) && !empty($birthday_users) ) {
      foreach( $birthday_users as $birthday_user ) {
        $get_user_birthday = get_user_meta($birthday_user->data->ID, 'wpdv_birthday', true );


        if( !empty($get_user_birthday) ) {
          $new_birthday_year = date('Y').'-'.$get_user_birthday;
          
          
          $date = date('Y-m-d', strtotime($new_birthday_year . " -".$mail_before_days." days"));


          if( $today == $date ) {
            $user_info = get_userdata($birthday_user->data->ID);

            //check user is eligible for send birthday this time
            $check_coupon_year = get_user_meta($birthday_user->data->ID, 'coupon_year', true );

            if( empty($check_coupon_year) || $check_coupon_year == '' ) {
              $check_coupon_year = 0;
            }
            $check_coupon_year = intval($check_coupon_year);

            if( $coupon_send_times == 0 || $check_coupon_year <= $coupon_send_times ) {
              $user_email = $birthday_user->data->user_email;
              $first_name = !empty($birthday_user->data->first_name) ? $birthday_user->data->first_name : '' ;
              $last_name = !empty($birthday_user->data->last_name) ? $birthday_user->data->last_name : '';

              $username = $birthday_user->data->user_login;

              if( !empty($user_email) ) {
                //create coupon based on settings
                $code_length = get_option( 'wbdv_code_length' );
                if( $code_length == '' )
                  $code_length = 12;

                $prefix = get_option( 'wbdv_prefix' );
                $code = $prefix . strtoupper( substr( str_shuffle( md5( time() ) ), 0, $code_length ) );
                $type = get_option( 'wbdv_dis_type' );
                $amount = get_option( 'wbdv_amount' );
                $product_ids = get_option( 'wbdv_products' );
                $allowed_products = '';
                $excluded_products = '';

                if ( is_array( $product_ids ) ) {
                  foreach ( $product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    $allowed_products .= '<a href="'.$product->get_permalink().'">'.$product->get_title().'</a>,';
                  }
                  $allowed_products = rtrim( $allowed_products, ',' );
                  $product_ids = implode( ',', $product_ids );
                }

                $exclude_product_ids = get_option( 'wbdv_exclude_products' );
                if ( is_array( $exclude_product_ids ) ) {
                  foreach ( $exclude_product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    $excluded_products .= '<a href="'.$product->get_permalink().'">'.$product->get_title().'</a>,';
                  }
                  $excluded_products = rtrim( $excluded_products, ',' );
                  $exclude_product_ids = implode( ',', $exclude_product_ids );
                }

                $product_categories = get_option( 'wbdv_categories' );
                $allowed_cats = '';
                $excluded_cats = '';
                if ( is_array( $product_categories ) ) {
                  foreach ( $product_categories as $cat_id ) {
                    $cat = get_term_by( 'id', $cat_id, 'product_cat' );
                    $allowed_cats .= '<a href="'.get_term_link( $cat->slug, 'product_cat' ).'">'.$cat->name.'</a>,';
                  }
                  $allowed_cats = rtrim( $allowed_cats, ',' );
                }
                else
                  $product_categories = array();

                $exclude_product_categories = get_option( 'wbdv_exclude_categories' );
                if ( is_array( $exclude_product_categories ) ) {
                  foreach ( $exclude_product_categories as $cat_id ) {
                    $cat = get_term_by( 'id', $cat_id, 'product_cat' );
                    $excluded_cats .= '<a href="'.get_term_link( $cat->slug, 'product_cat' ).'">'.$cat->name.'</a>,';
                  }
                  $excluded_cats = rtrim( $excluded_cats, ',' );
                }
                else
                  $exclude_product_categories = array();

                $days = get_option( 'wbdv_days' );
                $date = '';
                $expire = '';
                $format = get_option( 'wbdv_date_format' ) == '' ? 'jS F Y' : get_option( 'wbdv_date_format' );
                    
                if ( $days ) {
                  $date = date( 'Y-m-d', strtotime( '+'.$days.' days' ) );
                  $expire = date_i18n( $format, strtotime( '+'.$days.' days' ) );
                }
                
                $free_shipping = get_option( 'wbdv_shipping' );
                $exclude_sale_items = get_option( 'wbdv_sale' );
                $minimum_amount = get_option( 'wbdv_min_purchase' );
                $maximum_amount = get_option( 'wbdv_max_purchase' );
                $customer_email = '';

                if ( get_option( 'wbdv_restrict' ) == 'yes' )
                  $customer_email = $user_email;

                //Add a new coupon when user registers
                $coupon = array(
                  'post_title' => $code,
                  'post_content' => '',
                  'post_status' => 'publish',
                  'post_author' => 1,
                  'post_type'     => 'shop_coupon'
                );
                $coupon_id = wp_insert_post( $coupon );

                //Add coupon meta data
                update_post_meta( $coupon_id, 'discount_type', $type );
                update_post_meta( $coupon_id, 'coupon_amount', $amount );
                update_post_meta( $coupon_id, 'individual_use', 'yes' );
                update_post_meta( $coupon_id, 'product_ids', $product_ids );
                update_post_meta( $coupon_id, 'exclude_product_ids', $exclude_product_ids );
                update_post_meta( $coupon_id, 'usage_limit', '1' );
                update_post_meta( $coupon_id, 'usage_limit_per_user', '1' );
                update_post_meta( $coupon_id, 'limit_usage_to_x_items', '' );
                update_post_meta( $coupon_id, 'expiry_date', $date );
                update_post_meta( $coupon_id, 'apply_before_tax', 'no' );
                update_post_meta( $coupon_id, 'free_shipping', $free_shipping );
                update_post_meta( $coupon_id, 'exclude_sale_items', $exclude_sale_items );
                update_post_meta( $coupon_id, 'product_categories', $product_categories );
                update_post_meta( $coupon_id, 'exclude_product_categories', $exclude_product_categories );
                update_post_meta( $coupon_id, 'minimum_amount', $minimum_amount );
                update_post_meta( $coupon_id, 'maximum_amount', $maximum_amount );
                update_post_meta( $coupon_id, 'customer_email', $customer_email );

                $search = array( '{COUPONCODE}', '{COUPONEXPIRY}', '{ALLOWEDCATEGORIES}', '{EXCLUDEDCATEGORIES}', '{ALLOWEDPRODUCTS}', '{EXCLUDEDPRODUCTS}'
                ,'{USERNAME}',  '{STORENAME}', '{STOREURL}', '{FIRSTNAME}', '{LASTNAME}' );
                $replace = array( $code, $expire, $allowed_cats, $excluded_cats, $allowed_products, $excluded_products, $username,  
                    $store_name, $store_url, $first_name, $last_name );
                $subject = str_replace( $search, $replace, get_option( 'wbdv_email_sub' ) );
                $body = str_replace( $search, $replace, get_option( 'wbdv_email' ) );
                $body = stripslashes( $body );

                add_filter( 'wp_mail_content_type', array( $this, 'mail_content_type' ) );
                add_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
                add_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );
                $headers = array('Content-Type: text/html; charset=UTF-8');

                if ( version_compare( $woocommerce->version, '2.3',  ">=" ) ) {
                  $mailer = WC()->mailer();
                  $mailer->send( $user_email, $subject, $mailer->wrap_message( $subject, $body ), $headers, '' );
                }
                else
                  wp_mail( $user_email, $subject, wpautop( $body ), $headers );

                remove_filter( 'wp_mail_content_type', array( $this, 'mail_content_type' ) );
                remove_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
                remove_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );

                

                $new_coupon_year = $check_coupon_year + 1;

                update_user_meta($birthday_user->data->ID, 'coupon_year', $new_coupon_year );
              }
            }
          }
        }
      }
    }
  }

   /**
    *
    * Set default email from address set from the admin.
    *
    * @return string $from_email email address from which the email should be sent.
    *
    */
    public function mail_from() {
        $from_email = get_option( 'wbdv_email_id' );
        return $from_email;
    }

    /**
    *
    * Set default email from name set from the admin.
    *
    * @return string $from_name name  from which the email should be sent.
    *
    */
    public function mail_from_name() {
        $from_name = get_option( 'wbdv_email_name' );
        return $from_name;
    }

    /**
    *
    * Set email content type
    *
    * @return string content type for the email to be sent.
    *
    */
    public function mail_content_type() {
        return "text/html";
    }

  
  public function wpdv_enque_scripts() {
    $method = get_option('wbdv_method');
    $pick_style = get_option( 'wbdv_dp_style' ) . '.css';

    wp_enqueue_style( 'wbdv-custom-stylesheet', plugins_url( 'assets/css/wbdv-custom.css', BDV_FILE ) );
    
    wp_enqueue_script( 'wbdv-birthday-picker', plugins_url( 'assets/js/bday-picker.min.js', BDV_FILE ) , array( 'jquery' ), '1.0.0', true);
    wp_enqueue_script( 'jquery-ui-datepicker' );
    
    wp_enqueue_style( 'wbdv-picker-stylesheet', plugins_url( 'assets/css/' . $pick_style, BDV_FILE ));
    wp_enqueue_script( 'wbdv-picker', plugins_url( 'assets/js/picker.js', BDV_FILE ) , array( 'jquery' ), '1.0.0', true);
    wp_register_style( 'jquery-ui', '//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
    wp_enqueue_style( 'jquery-ui' ); 
    wp_enqueue_script( 'wbdv-datepicker', plugins_url( 'assets/js/picker.date.js', BDV_FILE ) , array( 'jquery', 'wbdv-picker' ), '1.0.0', true);

    wp_enqueue_script('custom-script', plugins_url( 'assets/js/custom.js', BDV_FILE ), array( 'jquery' ), '1.0.0', true );

    wp_localize_script('custom-script', 'wbdv', 
      array('method' => $method)
    );
  }


  public function wpdv_add_birthday_in_account() {
    $birthday_label = get_option('wbdv_birthday_label');
    $birthday_required = get_option('wbdv_birthday_required');
    ?>

    <p class="form-row form-row-wide">
      <label for="wpdv_birthday"><?php _e( $birthday_label, 'wpdv' ); ?>
        <?php if( $birthday_required == 'yes' ) : ?>
         <span class="required">*</span>
        <?php endif; ?>
      </label>

      <?php
        $method = get_option( 'wbdv_method' );
        $wpdv_birthday = ( isset( $_POST['wpdv_birthday'] ) ) ? sanitize_text_field($_POST['wpdv_birthday']) : '';
        $user_id = get_current_user_id();
        $enable_user_edit =  (get_option('wbdv_user_bday_enabled') == 'yes')  ? 'yes' : 'no';
        $enable_user_edit = $enable_user_edit !== 'yes' ? 'disabled' : 'yes';

        if( !empty($user_id) ) {
          $check_birthday_set = get_user_meta($user_id, 'wpdv_birthday_calender', true );
          if( !empty($check_birthday_set) ) {
            if( $enable_user_edit == 'yes' ) {
              if( $method == 'dropdown') : ?>
                <div id="wbdv_date_confirm" name="wbdv_date_confirm"><?php echo $check_birthday_set; ?></div>
              <?php endif; ?>
              <?php if( $method == 'datepicker' ): ?>
                <input id="wbdv_date_confirm" name="wbdv_date_confirm" class="input-text" value="<?php echo $check_birthday_set; ?>">
              <?php endif; ?>
              <?php if( $method == 'jqdatepicker' ): ?>
                <input id="wbdv_jquery_confirm" value="<?php echo $check_birthday_set; ?>" name="wbdv_date_confirm" class="input-text">
              <?php endif;
            } else {
              ?>
              <input value="<?php echo $check_birthday_set; ?>" 
              <?php echo $enable_user_edit; ?> class="input-text">
              <?php
            }
          } else {
             if( $method == 'dropdown') : ?>
                <div id="wbdv_date_confirm" name="wbdv_date_confirm"><?php echo $check_birthday_set; ?></div>
              <?php endif; ?>
              <?php if( $method == 'datepicker' ): ?>
                <input id="wbdv_date_confirm" name="wbdv_date_confirm" class="input-text" value="<?php echo $check_birthday_set; ?>">
              <?php endif; ?>
              <?php if( $method == 'jqdatepicker' ): ?>
                <input id="wbdv_jquery_confirm" value="<?php echo $check_birthday_set; ?>" name="wbdv_date_confirm" class="input-text">
              <?php endif;
          }
        }
        else {
          if( $method == 'dropdown') : ?>
            <div id="wbdv_date_confirm" name="wbdv_date_confirm"></div>
          <?php endif; ?>
          <?php if( $method == 'datepicker' ): ?>
            <input id="wbdv_date_confirm" name="wbdv_date_confirm" class="input-text">
          <?php endif; ?>
          <?php if( $method == 'jqdatepicker' ): ?>
            <input id="wbdv_jquery_confirm" name="wbdv_date_confirm" class="input-text">
          <?php endif;
        }
      ?>
    </p>
    <?php
  }

  public function wpdv_add_birthday_field() {
    $first_name_label = get_option('wbdv_first_name');
    $last_name_label = get_option('wbdv_last_name');
    $birthday_label = get_option('wbdv_birthday_label');

    $first_name_required = get_option('wbdv_firstname_required');
    $last_name_required = get_option('wbdv_lastname_required');
    $birthday_required = get_option('wbdv_birthday_required');


    if( empty($first_name_label) )
      $first_name_label = 'First Name';

    if( empty($last_name_label) )
      $last_name_label = 'Last Name';

    if( empty($birthday_label) )
      $birthday_label = 'Birthday';
    ?>
    <p class="form-row form-row-wide">
      <label for="wpdv_birthday"><?php _e( $first_name_label, 'wpdv' ); ?>
        <?php if( $first_name_required == 'yes' ) : ?>
        <span class="required">*</span>
        <?php endif; ?>
      </label>
      <input name="wbdv_first_name" type="text" class="input-text">
    </p>
      
    <p class="form-row form-row-wide">
      <label for="wpdv_birthday"><?php _e( $last_name_label, 'wpdv' ); ?>
        <?php if( $last_name_required == 'yes' ) : ?>
        <span class="required">*</span>
      <?php endif; ?>
      </label>
      <input name="wbdv_last_name" type="text" class="input-text">
    </p>

    <p class="form-row form-row-wide">
       <label for="wpdv_birthday"><?php _e( $birthday_label, 'wpdv' ); ?>
        <?php if( $birthday_required == 'yes' ) : ?>
         <span class="required">*</span>
       <?php endif; ?>
       </label>

       <?php
        $method = get_option( 'wbdv_method' );
        $wpdv_birthday = ( isset( $_POST['wpdv_birthday'] ) ) ? sanitize_text_field($_POST['wpdv_birthday']) : '';
        if( $method == 'dropdown') : ?>
          <div id="wbdv_date_confirm" name="wbdv_date_confirm"></div>
        <?php endif; ?>
        <?php if( $method == 'datepicker' ): ?>
          <input id="wbdv_date_confirm" name="wbdv_date_confirm" class="input-text">
        <?php endif; ?>
        <?php if( $method == 'jqdatepicker' ): ?>
          <input id="wbdv_jquery_confirm" name="wbdv_date_confirm" class="input-text">
        <?php endif; ?>
    </p>
  <?php    
  }

  public function wpdv_filter_checkout_fields($fields) {
    $birthday_method = get_option('wbdv_method');
    $birthday_label = get_option('wbdv_birthday_label');
    $birthday_required = get_option('wbdv_birthday_required');

    $firstname_label = get_option('wbdv_first_name');
    $lastname_label = get_option('wbdv_last_name');

    $first_name_required = get_option('wbdv_firstname_required');
    $last_name_required = get_option('wbdv_lastname_required');

    if( is_user_logged_in() ) {
      $user_id = get_current_user_id();

      $check_birthday_set = get_user_meta($user_id, 'wpdv_birthday_calender', true );

      if( !empty($check_birthday_set) || $check_birthday_set !=='' ) {
        return $fields;
      }
    }


    $bday_required_field = false;
    $fname_required_field = false;
    $lname_required_field = false;

    if( $birthday_required == 'yes' ) {
     $bday_required_field = true;
    }

    if( $first_name_required == 'yes' ) {
      $fname_required_field = true;
    }

    if( $last_name_required == 'yes' ) {
      $lname_required_field = true;
    }

    $fields['billing']['billing_first_name'] = array(
        'label'     => __($firstname_label, 'woocommerce'),
        'required'  => $fname_required_field,
         'class'     => array('form-row-first'),
     );

    $fields['billing']['billing_last_name'] = array(
        'label'     => __($lastname_label, 'woocommerce'),
        'required'  => $lname_required_field,
         'class'     => array('form-row-last'),
     );


    if( $birthday_method == 'datepicker' ) {
      $fields['billing']['wpdv_birthday_field'] = array(
        'label'     => __($birthday_label, 'woocommerce'),
        'placeholder'   => _x('', 'placeholder', 'woocommerce'),
        'required'  => $bday_required_field,
        'class'     => array('form-row-wide'),
        'clear'     => true
     );
    }
    else {
      $fields['billing']['wpdv_birthday_picker'] = array(
        'label'     => __($birthday_label, 'woocommerce'),
        'placeholder'   => _x('', 'placeholder', 'woocommerce'),
        'required'  => $bday_required_field,
        'class'     => array('form-row-wide'),
        'clear'     => true
     );
    }

    return $fields;
  }

  public function wpdv_save_birthday_checkout_field( $order_id, $posted ){
    $birthday_method = get_option('wbdv_method');

    if( $birthday_method == 'datepicker' && !empty($order_id) && !empty($_POST['wpdv_birthday_field_submit']) ) {
        $birthday = sanitize_text_field($_POST['wpdv_birthday_field_submit']);
    }
    
    if( $birthday_method != 'datepicker' && !empty($order_id) && !empty($_POST['wpdv_birthday_picker']) ) {
        $birthday = sanitize_text_field($_POST['wpdv_birthday_picker']);
    }

    if( !empty($order_id) && !empty($birthday) ) {
      $get_user_id = get_post_meta($order_id, '_customer_user', true);
      $birthday_var = explode('-', $birthday);
      $birthday_var = $birthday_var[1].'-'.$birthday_var[2];
      if( !empty($get_user_id) ) {
        update_user_meta($get_user_id, 'wpdv_birthday', $birthday_var);
        update_user_meta($get_user_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));
        update_user_meta($get_user_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));
        update_user_meta($get_user_id, 'wpdv_birthday_calender', $birthday);
      }

    }

  }


  public function wbdv_validate_date_of_birth($username, $email, $validation_errors) {
    $birthday_label = get_option('wbdv_birthday_label');
    $first_name_required = get_option('wbdv_firstname_required');
    $last_name_required = get_option('wbdv_lastname_required');
    $birthday_required = get_option('wbdv_birthday_required');


    if( empty($birthday_label) )
      $birthday_label = __( 'Birthday' );

    if( isset($_POST['wbdv_first_name']) && empty($_POST['wbdv_first_name']) && $first_name_required == 'yes' ) {
      $validation_errors->add('billing_first_name_error', __(' First name is required!', 'text_domain'));
    }

    if( isset($_POST['wbdv_last_name']) && empty($_POST['wbdv_last_name']) && $last_name_required == 'yes' ) {
      $validation_errors->add('billing_last_name_error', __(' Last name is required!', 'text_domain'));
    }

    if (isset($_POST['wpdv_birthday']) && empty($_POST['wpdv_birthday']) && $birthday_required == 'yes' ) {
        $validation_errors->add('billing_phone_error', __($birthday_label.' is required!', 'text_domain'));
    }

    
 }

  public function wpdv_save_birthday_field($customer_id) {
    $datepicker_method = get_option('wbdv_method');

    if( $datepicker_method == 'datepicker' ) {
      $birthday = sanitize_text_field($_POST['wbdv_date_confirm_submit']);
    }
    if( $datepicker_method == 'dropdown' ) {
      $birthday = sanitize_text_field($_POST['birthdate']);
    }
    if( $datepicker_method == 'jqdatepicker' ) {
      $birthday = sanitize_text_field($_POST['wbdv_date_confirm']);
    }


    if( empty($birthday) )
      $birthday = '';
    

    $first_name = sanitize_text_field($_POST['wbdv_first_name']);
    $last_name = sanitize_text_field($_POST['wbdv_last_name']);
    
    if (!empty($birthday)) {
        update_user_meta($customer_id, 'wpdv_birthday_calender', $birthday);
        $wpdv_birthday = explode('-', $birthday);
        $birthday_var = $wpdv_birthday[1].'-'.$wpdv_birthday[2];
        update_user_meta($customer_id, 'wpdv_birthday', $birthday_var);
    }

    update_user_meta($customer_id, 'first_name', $first_name);
    update_user_meta($customer_id, 'last_name', $last_name);
  }

    /**
    *
    * Output products for the ajax search on admin.
    *
    * @return json matched products 
    *
    */
    public function wbdv_ajax_products() {
        global $wpdb;
        $post_types = array( 'product' );
        ob_start();

        if ( empty( $term ) ) {
            $term = wc_clean( stripslashes( $_GET['term'] ) );
        } else {
            $term = wc_clean( $term );
        }

        if ( empty( $term ) ) {
            die();
        }

        $like_term = '%' . $wpdb->esc_like( $term ) . '%';

        if ( is_numeric( $term ) ) {
            $query = $wpdb->prepare( "
                SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
                WHERE posts.post_status = 'publish'
                AND (
                    posts.post_parent = %s
                    OR posts.ID = %s
                    OR posts.post_title LIKE %s
                    OR (
                        postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
                    )
                )
            ", $term, $term, $term, $like_term );
        } else {
            $query = $wpdb->prepare( "
                SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
                WHERE posts.post_status = 'publish'
                AND (
                    posts.post_title LIKE %s
                    or posts.post_content LIKE %s
                    OR (
                        postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
                    )
                )
            ", $like_term, $like_term, $like_term );
        }

        $query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "')";

        if ( ! empty( $_GET['exclude'] ) ) {
            $query .= " AND posts.ID NOT IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['exclude'] ) ) ) . ")";
        }

        if ( ! empty( $_GET['include'] ) ) {
            $query .= " AND posts.ID IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['include'] ) ) ) . ")";
        }

        if ( ! empty( $_GET['limit'] ) ) {
            $query .= " LIMIT " . intval( $_GET['limit'] );
        }

        $posts          = array_unique( $wpdb->get_col( $query ) );
        $found_products = array();

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                $product = wc_get_product( $post );

                if ( ! current_user_can( 'read_product', $post ) ) {
                    continue;
                }

                if ( ! $product || ( $product->is_type( 'variation' ) && empty( $product->parent ) ) ) {
                    continue;
                }

                $found_products[ $post ] = rawurldecode( $product->get_formatted_name() );
            }
        }
        
        wp_send_json( $found_products );
    }

    public function send_birthday_emails_to_users() {
      if(!wp_next_scheduled('wbdv_birthday_cron')) {
        $customer_time = '00:00';
        wp_schedule_event(strtotime( date( 'Y-m-d' ) .' '. $customer_time ), 'daily', 'wbdv_birthday_cron');
      }
    }


    public function wbdv_deactivate_emails() {
      wp_clear_scheduled_hook('wbdv_birthday_cron' );
      wp_die();
    }

  /**
  * Add new admin setting page for birthday discount voucher settings.
  *
  * @param array $settings an array of existing setting pages.
  * @return array of setting pages along with birthday discount voucher settings page.
  *
  */
  public function bdv_settings_class( $settings ) {
    $settings[] = include 'class-wc-settings-birthday-discount-vouchers.php';
    return $settings;
  }

  public function wbdv_user_birth_day_field($user) { 
    ?>
    <table class="form-table">
      <tr>
        <th><label for="birthday"><?php _e("Date Of Birth"); ?></label></th>
        <td>
          <?php if( is_admin() ) : ?>
          <input type="text" name="wbdv_profile_datepicker" id="wbdv_profile_datepicker" value="<?php echo esc_attr( get_the_author_meta( 'wpdv_birthday_calender', $user->ID ) ); ?>">
          <?php else: ?>
            <input type="text" readonly name="wbdv_profile_datepicker" id="wbdv_profile_datepicker" value="<?php echo esc_attr( get_the_author_meta( 'wpdv_birthday', $user->ID ) ); ?>">
          <?php endif; ?>
            <span class="description"><?php _e("Select date of birth for the user"); ?></span>
        </td>
    </tr>
    </table>
  <?php }

  public function wbdv_save_user_birthday_profile_fields($user_id) {
    if ( !is_admin() )
     return false;

    $birthday = sanitize_text_field($_POST['wbdv_profile_datepicker']);
    $birthday_var = explode('-', $birthday);
    $birthday_var = $birthday_var[1].'-'.$birthday_var[2];
    if( !empty($user_id) ) {
      update_user_meta($user_id, 'wpdv_birthday', $birthday_var);
      update_user_meta($user_id, 'wpdv_birthday_calender', $birthday);
    }
  }

}