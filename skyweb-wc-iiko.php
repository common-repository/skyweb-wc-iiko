<?php
/**
 * Plugin Name: iikoCloud for WooCommerce
 * Plugin URI: https://skyweb.team/projects/iiko-cloud-transport/
 * Description: Integration of the basic functionality of the iikoCloud API into WooCommerce: import of categories and products (sizes and modifiers) and export of orders.
 * Version: 1.3.2
 * Author: SkyWeb
 * Author URI: https://skyweb.team
 * Text Domain: skyweb-wc-iiko
 * Domain Path: /languages
 *
 * Requires at least: 5.5
 * Requires PHP: 7.2
 *
 * WC requires at least: 4.3
 * WC tested up to: 5.4
 *
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/vendor/autoload.php';

// Check if PHP version is lower than required.
if ( version_compare( PHP_VERSION, '7.2.0', '<' ) ) {

	add_action( 'admin_notices', function () {
		$class   = 'notice notice-warning is-dismissible';
		$message = esc_html__(
			'iikoCloud for WooCommerce requires PHP 7.2 and high. Please, update you PHP version.',
			'skyweb-wc-iiko'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	} );

	return;
}

// Check if the required functions exist.
if ( ! function_exists( 'get_plugin_data' ) || ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

// Define main plugin file constant for activation and deactivation plugin hooks.
if ( ! defined( 'SKYWEB_WC_IIKO_FILE' ) ) {
	define( 'SKYWEB_WC_IIKO_FILE', __FILE__ );
}

// Define a constant for assets handle.
if ( ! defined( 'SKYWEB_WC_IIKO_SLUG' ) ) {
	define( 'SKYWEB_WC_IIKO_SLUG', dirname( plugin_basename( __FILE__ ) ) );
}

// Define a constant for assets version.
if ( ! defined( 'SKYWEB_WC_IIKO_VERSION' ) ) {
	define( 'SKYWEB_WC_IIKO_VERSION', get_plugin_data( __FILE__ )['Version'] );
}

// Register plugin text domain.
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'skyweb-wc-iiko',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// If WooCommerce inactive show the notice in admin area and stop working.
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	if ( is_admin() ) {
		new SkyWeb\WC_Iiko\Admin\Inactive();
	}

	return;
}

// Logs object.
global $skyweb_wc_iiko_logs;
$skyweb_wc_iiko_logs = new SkyWeb\WC_Iiko\Logs();

// Admin actions.
if ( is_admin() ) {
	// Settings.
	add_filter( 'woocommerce_get_settings_pages', function ( $settings ) {
		$settings[] = new SkyWeb\WC_Iiko\Admin\Settings();

		return $settings;
	} );

	// Activation, deactivation hooks, admin styles and scripts, admin AJAX.
	SkyWeb\WC_Iiko\Admin\Admin::init();
	// Main iiko admin page.
	new SkyWeb\WC_Iiko\Admin\Page();
	// Products and categories meta fields.
	new SkyWeb\WC_Iiko\Admin\MetaFields();
	// Actions for order.
	new SkyWeb\WC_Iiko\Admin\Order();
}

// Export to iiko.
new SkyWeb\WC_Iiko\Export();

// Module payments.
SkyWeb\WC_Iiko\Modules\Payments\Payments::init();
// Module notices.
SkyWeb\WC_Iiko\Modules\Notices\Notices::init();
// Module Checkout.
SkyWeb\WC_Iiko\Modules\Checkout\Checkout::init();