<?php

namespace SkyWeb\WC_Iiko\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout {

	/**
	 * Initialization.
	 */
	public static function init() {

		if ( is_admin() ) {
			register_uninstall_hook( SKYWEB_WC_IIKO_FILE, array( __CLASS__, 'uninstall' ) );
			self::add_settings();
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'public_styles_scripts' ) );

		Checkout_Field_Phone::init();
		Checkout_Field_Address::init();
		Checkout_Field_Complete_Before::init();
	}

	/**
	 * Plugin uninstallation.
	 */
	public static function uninstall() {
		delete_option( 'skyweb_wc_iiko_show_imask' );
		delete_option( 'skyweb_wc_iiko_imask' );
		delete_option( 'skyweb_wc_iiko_show_complete_before' );
		delete_option( 'skyweb_wc_iiko_show_complete_before_position' );
		delete_option( 'skyweb_wc_iiko_show_complete_before_start_time' );
		delete_option( 'skyweb_wc_iiko_show_complete_before_end_time' );
	}

	/**
	 * Enqueue public styles and scripts.
	 */
	public static function public_styles_scripts() {

		if ( is_checkout() ) {

			$show_imask = get_option( 'skyweb_wc_iiko_show_imask' );

			// Datetimepicker.
			wp_enqueue_style(
				SKYWEB_WC_IIKO_SLUG . '-datetimepicker',
				plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'includes/Modules/Checkout/assets/css/jquery.datetimepicker.min.css',
				array(),
				null
			);

			wp_enqueue_script(
				SKYWEB_WC_IIKO_SLUG . '-datetimepicker',
				plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'includes/Modules/Checkout/assets/js/jquery.datetimepicker.full.min.js',
				array( 'jquery' ),
				null,
				true
			);

			// iMask.
			if ( 'yes' === $show_imask ) {
				wp_enqueue_script(
					SKYWEB_WC_IIKO_SLUG . '-imask',
					plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'includes/Modules/Checkout/assets/js/imask.min.js',
					array( 'jquery' ),
					null,
					true
				);
			}

			wp_enqueue_script(
				SKYWEB_WC_IIKO_SLUG . '-checkout-public',
				plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'includes/Modules/Checkout/assets/js/skyweb-wc-iiko-checkout-public.js',
				array( 'jquery' ),
				SKYWEB_WC_IIKO_VERSION,
				true
			);
		}
	}

	/**
	 * Add settings for the module.
	 */
	private static function add_settings() {
		new Checkout_Settings();
	}
}