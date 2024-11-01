<?php

namespace SkyWeb\WC_Iiko\Modules\Notices;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\Logs;

class Notices {

	/**
	 * Initialization.
	 */
	public static function init() {
		add_action( 'skyweb_wc_iiko_created_delivery', array( __CLASS__, 'check_created_delivery_response' ), 10, 2 );
	}

	/**
	 * Check created delivery response.
	 *
	 * @param $created_delivery_response
	 * @param $order_id
	 */
	public static function check_created_delivery_response( $created_delivery_response, $order_id ) {

		$order         = wc_get_order( $order_id );
		$error_subject = sprintf( esc_html__( 'Iiko delivery creation error. Order %s', 'skyweb-wc-iiko' ), $order_id );
		$error_message = esc_html__( 'Turn on debug mode in plugin settings and see WooCommerce logs.', 'skyweb-wc-iiko' );

		if ( false === $created_delivery_response ) {

			$error_subject = sprintf( esc_html__( 'Iiko transfer error. Order %s', 'skyweb-wc-iiko' ), $order_id );

			Logs::add_wc_error_log( $error_subject, 'create-delivery-response' );
			self::send_notice_to_admin_email( $error_subject, $error_message );
			$order->update_status( 'failed', esc_html__( 'Iiko transfer error.', 'skyweb-wc-iiko' ) );

			return;
		}

		if ( empty( $created_delivery_response ) ) {

			$error_subject = sprintf( esc_html__( 'Iiko delivery creation response is empty. Order %s', 'skyweb-wc-iiko' ), $order_id );

			Logs::add_wc_error_log( $error_subject, 'create-delivery-response' );
			self::send_notice_to_admin_email( $error_subject, $error_message );
			$order->update_status( 'failed', esc_html__( 'Iiko delivery creation response is empty.', 'skyweb-wc-iiko' ) );

			return;
		}

		// Code 200.
		if ( ! empty( $creation_status = sanitize_text_field( $created_delivery_response['orderInfo']['creationStatus'] ) ) ) {

			// TODO - retrieve order by ID.
			/* $iiko_order_id           = sanitize_key( $created_delivery_response['orderInfo']['id'] );
			$iiko_api_requests           = new Common_API_Requests();
			$retrieve_order_response = $iiko_api_requests->retrieve_order_by_id( $iiko_order_id ); */

			// Enum: "Success" "InProgress" "Error"
			// Order creation status.
			// In case of asynchronous creation, it allows to track the instance an order was validated/created in iikoFront.

			wc_add_order_item_meta( $order_id, 'skyweb_wc_iiko_create_delivery_status', $creation_status );

			switch ( $creation_status ) {
				case 'InProgress':
				case 'Success':
					$order->add_order_note( esc_html__( 'Transferred to iiko.', 'skyweb-wc-iiko' ) );

					break;

				case 'Error':
					$error_info               = $created_delivery_response['orderInfo']['errorInfo'];
					$error['status']          = $creation_status . PHP_EOL;
					$error['code']            = ! empty( $error_info['code'] ) ? $error_info['code'] . PHP_EOL : '';
					$error['message']         = ! empty( $error_info['message'] ) ? $error_info['message'] . PHP_EOL : '';
					$error['description']     = ! empty( $error_info['description'] ) ? $error_info['description'] . PHP_EOL : '';
					$error['additional_data'] = ! empty( $error_info['additionalData'] ) ? $error_info['additionalData'] . PHP_EOL : '';

					foreach ( $error as $error_key => $error_val ) {
						if ( ! empty( $error_val ) ) {
							$order->add_order_note( mb_strtoupper( $error_key ) . ': ' . $error_val );
						}
					}

					$error_message = implode( PHP_EOL, $error );

					Logs::add_wc_error_log( $error_subject, 'create-delivery-response' );
					Logs::add_wc_error_log( $error_message, 'create-delivery-response' );
					self::send_notice_to_admin_email( $error_subject, $error_message );
					$order->update_status( 'failed', esc_html__( 'Iiko delivery creation error. Code 200.', 'skyweb-wc-iiko' ) );

					break;
			}
		}

		// Codes 400, 401, 408, 500.
		if ( ! empty( $error_description = sanitize_text_field( $created_delivery_response['errorDescription'] ) ) ) {

			$error['description'] = $error_description . PHP_EOL;
			$error['code']        = ! empty( $created_delivery_response['error'] ) ? $created_delivery_response['error'] . PHP_EOL : '';

			$error_message = implode( PHP_EOL, $error );

			Logs::add_wc_error_log( $error_subject, 'create-delivery-response' );
			Logs::add_wc_error_log( $error_message, 'create-delivery-response' );
			self::send_notice_to_admin_email( $error_subject, $error_message );
			$order->update_status( 'failed', esc_html__( 'Iiko delivery creation error. Codes 400, 401, 408 or 500.', 'skyweb-wc-iiko' ) );
		}
	}

	/**
	 * Send notice to admin email.
	 *
	 * @param $subject
	 * @param $message
	 */
	public static function send_notice_to_admin_email( $subject, $message ) {
		wp_mail( get_bloginfo( 'admin_email' ), $subject, $message );
	}
}