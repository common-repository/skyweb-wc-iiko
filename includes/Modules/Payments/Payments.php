<?php

namespace SkyWeb\WC_Iiko\Modules\Payments;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\Logs;

class Payments {

	/**
	 * Initialization.
	 */
	public static function init() {

		if ( is_admin() ) {
			register_uninstall_hook( SKYWEB_WC_IIKO_FILE, array( __CLASS__, 'uninstall' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles_scripts' ) );
			add_action( 'init', array( __CLASS__, 'register_ajax' ) );

			self::add_settings();
		}

		add_action( 'skyweb_wc_iiko_order_payments', array( __CLASS__, 'prepare_export_payments' ), 10, 2 );
	}

	/**
	 * Plugin uninstallation.
	 */
	public static function uninstall() {

		$payment_types = get_option( 'skyweb_wc_iiko_payment_types' );

		if ( ! empty( $payment_types ) ) {
			foreach ( $payment_types as $payment_type ) {

				if ( ! empty( $payment_type['code'] ) ) {
					delete_option( 'skyweb_wc_iiko_paymentType_' . $payment_type['code'] );
					delete_option( 'skyweb_wc_iiko_paymentType_' . $payment_type['code'] . '_IPS' );
				}
			}
		}
	}

	/**
	 * Enqueue admin styles and scripts.
	 */
	public static function admin_styles_scripts() {

		wp_enqueue_script(
			SKYWEB_WC_IIKO_SLUG . '-payments-admin',
			plugin_dir_url( SKYWEB_WC_IIKO_FILE ) . 'includes/Modules/Payments/assets/js/skyweb-wc-iiko-payments-admin.js',
			array( SKYWEB_WC_IIKO_SLUG . '-admin' ),
			SKYWEB_WC_IIKO_VERSION,
			true
		);
	}

	/**
	 * Register AJAX.
	 */
	public static function register_ajax() {

		$iiko_api_requests = new Payments_API_Requests();

		// Get payment types from iiko.
		add_action( 'wp_ajax_skyweb_ajax_wc_iiko__get_payment_types_ajax', array( $iiko_api_requests, 'get_payment_types_ajax' ) );
	}

	/**
	 * Add settings for the module.
	 */
	private static function add_settings() {
		new Payments_Settings();
	}

	/**
	 * Prepare payments export.
	 *
	 * @param $payments
	 * @param $order
	 */
	public static function prepare_export_payments( $payments, $order ) {

		$order_payment_method = $order->get_payment_method();
		$payment_type_iiko_id = false;

		if ( ! empty( $order_payment_method ) ) {

			$payment_types           = get_option( 'skyweb_wc_iiko_payment_types' );
			$is_processed_externally = false;

			if ( ! empty( $payment_types ) ) {

				foreach ( $payment_types as $payment_type ) {

					if ( ! empty( $payment_type['code'] ) ) {

						if ( $order_payment_method === get_option( 'skyweb_wc_iiko_paymentType_' . $payment_type['code'] ) ) {

							$payment_type_kind       = $payment_type['paymentTypeKind'];
							$payment_type_iiko_id    = $payment_type['id'];
							$is_processed_externally = 'yes' === get_option( 'skyweb_wc_iiko_paymentType_' . $payment_type['code'] . '_IPE' );
							// $total                   = 'true' === $is_processed_externally ? 0 : $order->get_total();

							break;
						}
					}
				}
			}

			if ( ! empty( $payment_type_kind ) && ! empty( $payment_type_iiko_id ) ) {

				return array(
					array(

						// string
						'paymentTypeKind'        => strval( $payment_type_kind ),

						// Required.
						// number <double> [ 0 .. 10000000000 ]
						// Amount due.
						'sum'                    => floatval( $order->get_total() ),
						// TODO - from PHP 7.4
						/*'sum'                    => filter_var( $order->get_total(),
							FILTER_VALIDATE_FLOAT,
							array(
								'options' => array(
									'min_range' => 0,
									'max_range' => 10000000000,
								),
							) ),*/

						// Required.
						// string <uuid>
						// Payment type.
						'paymentTypeId'          => sanitize_key( $payment_type_iiko_id ),

						// boolean
						// Whether payment item is processed by external payment system (made from outside).
						'isProcessedExternally'  => boolval( $is_processed_externally ),

						// iikoTransport.PublicApi.Contracts.Deliveries.Common.IikoCardPaymentAdditionalData (object) Nullable
						// Additional payment parameters.
						'paymentAdditionalData'  => null,

						// boolean
						// Whether the payment item is externally fiscalized.
						'isFiscalizedExternally' => false
					)
				);

			} else {
				Logs::add_wc_error_log( 'Payment type is empty.', 'create-delivery' );
			}
		}

		return null;
	}
}