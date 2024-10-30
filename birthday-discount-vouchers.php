<?php
/*
Plugin Name: Birthday Discount Vouchers
Plugin URI: https://codecanyon.net/item/woocommerce-birthday-discount-vouchers/19822188
Description: This plugin allows you to send discount vouchers to your customers on their birthday
Version: 1.0.3
Author: WooExtension
Author URI: http://wooextension.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// No direct file access
! defined( 'ABSPATH' ) AND exit;

define( 'BDV_FILE', __FILE__ );
define( 'BDV_PATH', plugin_dir_path(__FILE__) );
define( 'BDV_BASE', plugin_basename(__FILE__) );
define( 'BDV_PLUGIN_NAME', 'Birthday and Discount Vouchers' );

/**
 * Enable Localization
 */
add_action( 'plugins_loaded', 'wbdv_load_textdomain' );

function wbdv_load_textdomain() {
	load_plugin_textdomain( 'wbdv', false, basename( dirname( __FILE__ ) ) . '/lang' );
}

require BDV_PATH . '/includes/class-bdv.php';

new Birthday_Discount_Vouchers_Lite();