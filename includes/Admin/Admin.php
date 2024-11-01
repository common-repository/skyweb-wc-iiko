<?php

namespace SkyWeb\WC_Iiko\Admin;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\API_Requests\AJAX_API_Requests;
use SkyWeb\WC_Iiko\Export;

class Admin {

	/**
	 * Initialization.
	 */
	public static function init() {
		register_activation_hook( SKYWEB_WC_IIKO_FILE, array( __CLASS__, 'activation' ) );
		register_uninstall_hook( SKYWEB_WC_IIKO_FILE, array( __CLASS__, 'uninstall' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SKYWEB_WC_IIKO_FILE ), array( __CLASS__, 'add_plugin_page_settings_link' ) );
		add_action( 'init', array( __CLASS__, 'register_ajax' ) );
	}

	/**
	 * Plugin activation.
	 */
	public static function activation() {
		add_option( 'skyweb_wc_iiko_timeout', 10 );
		add_option( 'skyweb_wc_iiko_download_images', 'yes' );

		// TODO - add notice with links to settings and plugin page.
	}

	/**
	 * Plugin uninstallation.
	 */
	public static function uninstall() {
		delete_option( 'skyweb_wc_iiko_apiLogin' );
		delete_option( 'skyweb_wc_iiko_timeout' );
		delete_option( 'skyweb_wc_iiko_debug_mode' );
		delete_option( 'skyweb_wc_iiko_organization_name' );
		delete_option( 'skyweb_wc_iiko_organization_id' );
		delete_option( 'skyweb_wc_iiko_terminal_name' );
		delete_option( 'skyweb_wc_iiko_terminal_id' );
		delete_option( 'skyweb_wc_iiko_nomenclature_revision' );
		delete_option( 'skyweb_wc_iiko_chosen_groups' );
		delete_option( 'skyweb_wc_iiko_chosen_groups_to_show' );
		delete_option( 'skyweb_wc_iiko_download_images' );
		delete_option( 'skyweb_wc_iiko_city_name' );
		delete_option( 'skyweb_wc_iiko_city_id' );
		delete_option( 'skyweb_wc_iiko_streets_amount' );
		delete_option( 'skyweb_wc_iiko_default_street' );
	}

	/**
	 * Enqueue admin styles and scripts.
	 */
	public static function admin_styles_scripts() {

		wp_enqueue_style(
			SKYWEB_WC_IIKO_SLUG . '-admin',
			plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'assets/css/skyweb-wc-iiko-admin.css',
			array(),
			SKYWEB_WC_IIKO_VERSION
		);

		wp_enqueue_script(
			SKYWEB_WC_IIKO_SLUG . '-admin',
			plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'assets/js/skyweb-wc-iiko-admin.js',
			array( 'jquery' ),
			SKYWEB_WC_IIKO_VERSION,
			true
		);

		wp_localize_script(
			SKYWEB_WC_IIKO_SLUG . '-admin',
			'skyweb_wc_iiko',
			array(
				'ajax_url' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			)
		);
	}

	/**
	 * Add plugin page and settings links.
	 */
	public static function add_plugin_page_settings_link( $links ) {

		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=skyweb_wc_iiko_settings' ) . '">' .
		           esc_html__( 'Settings', 'skyweb-wc-iiko' ) .
		           '</a>';
		$links[] = '<a href="' . admin_url( 'admin.php?page=skyweb_wc_iiko' ) . '">' .
		           esc_html__( 'Plugin page', 'skyweb-wc-iiko' ) .
		           '</a>';

		return $links;
	}

	/**
	 * Register admin AJAX.
	 */
	public static function register_ajax() {

		$api_requests = new AJAX_API_Requests();
		$api_export   = new Export();

		// Get organizations from iiko.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__get_organizations_ajax', array( $api_requests, 'get_organizations_ajax' ) );
		// Get terminals from iiko.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__get_terminals_ajax', array( $api_requests, 'get_terminals_ajax' ) );
		// Get nomenclature from iiko.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__get_nomenclature_ajax', array( $api_requests, 'get_nomenclature_ajax' ) );
		// Get cities from iiko.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__get_cities_ajax', array( $api_requests, 'get_cities_ajax' ) );
		// Get streets from iiko.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__get_streets_ajax', array( $api_requests, 'get_streets_ajax' ) );
		// Import groups and products to WooCommerce.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__import_nomenclature_ajax', array( $api_requests, 'import_nomenclature_ajax' ) );
		// Export order to iiko.
		add_action( 'wp_ajax_skyweb_wc_iiko_export_order', array( $api_export, 'export_order_manually' ) );
		// Check created delivery.
		add_action( 'wp_ajax_skyweb_wc_iiko_check_created_delivery', array( $api_export, 'check_created_delivery_manually' ) );
	}
}