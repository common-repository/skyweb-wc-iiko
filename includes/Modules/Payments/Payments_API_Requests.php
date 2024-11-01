<?php

namespace SkyWeb\WC_Iiko\Modules\Payments;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\API_Requests\Common_API_Requests;
use SkyWeb\WC_Iiko\HTTP_Request;

class Payments_API_Requests extends Common_API_Requests {

	/**
	 * Update payment types in the plugin settings.
	 */
	protected function update_payment_types_setting( $payment_types ) {

		delete_option( 'skyweb_wc_iiko_payment_types' );

		if ( ! update_option( 'skyweb_wc_iiko_payment_types', $payment_types ) ) {
			$this->logs->add_error( 'Cannot add payment types to the plugin settings.' );

			return false;
		}

		return true;
	}

	/**
	 * Get payment types from iiko.
	 *
	 * Echo JSON.
	 */
	public function get_payment_types( $organization_id = null ) {

		$access_token = $this->get_access_token();

		// Take organization ID from settings if parameter is empty.
		if ( empty( $organization_id ) && ! empty( $this->organization_id ) ) {
			$organization_id = $this->organization_id;
		}

		if ( false === $access_token || empty( $organization_id ) ) {
			return false;
		}

		$url     = 'payment_types';
		$headers = array(
			'Authorization' => $access_token
		);
		$body    = array(
			'organizationIds' => array( $organization_id )
		);

		$payment_types = HTTP_Request::remote_post( $url, $headers, $body );

		if ( is_array( $payment_types ) && isset( $payment_types['paymentTypes'] ) && ! empty( $payment_types['paymentTypes'] ) ) {

			// Change keys onto iiko codes and remove doubled IDs.
			$payment_types = array_column( $payment_types['paymentTypes'], null, 'code' );

			// TODO - update only if we put params from POST.
			// TODO - check update_payment_types_setting.
			$this->update_payment_types_setting( $payment_types );

		} else {
			$this->logs->add_error( 'Response does not contain payment types.' );

			return false;
		}

		return $payment_types;
	}

	/**
	 * Get payment types from iiko by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function get_payment_types_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$organization_id = $this->check_id( $_POST['organizationId'], 'Organization' );

		$this->check_parameter_for_ajax( $organization_id, 'Organization ID' );

		$payment_types = $this->get_payment_types( $organization_id );

		$this->check_response_for_ajax( $payment_types, 'Payment types' );

		echo wp_json_encode( array( 'paymentTypes' => $payment_types ) );

		wp_die();
	}
}