<?php

namespace SkyWeb\WC_Iiko;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\API_Requests\Export_API_Requests;

class Export {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_order_created', array( $this, 'export_delivery' ) ); // Comment for debug
		// add_action( 'woocommerce_thankyou', array( $this, 'export_delivery_debug' ) ); // Uncomment for debug
	}

	/**
	 * Export order.
	 *
	 * @param $order
	 *
	 * @return array|bool
	 */
	public function export_delivery( $order ) {

		$order_id = $order->get_id();

		return $this->export_delivery_process( $order, $order_id );
	}

	/**
	 * Export order for debug.
	 *
	 * @param $order_id
	 *
	 * @return array|bool
	 */
	public function export_delivery_debug( $order_id ) {

		$order = wc_get_order( $order_id );

		return $this->export_delivery_process( $order, $order_id );
	}

	/**
	 * Export order manually (from orders list).
	 * Action in Admin class.
	 */
	public function export_order_manually() {

		if (
			current_user_can( 'edit_shop_orders' )
			&& check_admin_referer( 'skyweb-wc-iiko-export-order' )
			&& isset( $_GET['status'], $_GET['order_id'] )
		) {

			// $status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
			$order            = wc_get_order( absint( wp_unslash( $_GET['order_id'] ) ) );
			$created_delivery = $this->export_delivery( $order );

			$this->print_response( $created_delivery );

		} else {
			echo 'You cannot see this page.';
			$this->print_back_button();
		}

		// wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );

		wp_die();
	}

	/**
	 * Check created delivery (from orders list).
	 * Action in Admin class.
	 */
	public function check_created_delivery_manually() {

		if (
			current_user_can( 'edit_shop_orders' )
			&& check_admin_referer( 'skyweb-wc-iiko-check-created-delivery' )
			&& isset( $_GET['order_id'] )
		) {

			$order_id      = absint( wp_unslash( $_GET['order_id'] ) );
			$iiko_order_id = $this->get_iiko_order_id( $order_id );

			if ( ! empty( $iiko_order_id ) ) {
				$export_api_requests = new Export_API_Requests();
				$retrieved_order     = $export_api_requests->retrieve_order_by_id( $iiko_order_id );

				$this->print_response( $retrieved_order );

			} else {
				Logs::add_wc_error_log( "Order $order_id doesn't have iiko ID.", 'check-delivery' );
				echo "Order $order_id doesn't have iiko ID.";
			}

		} else {
			echo 'You cannot see this page.';
			$this->print_back_button();
		}

		wp_die();
	}

	/**
	 * Export order process.
	 *
	 * @param $order
	 * @param $order_id
	 */
	protected function export_delivery_process( $order, $order_id ) {

		if ( 'failed' === $order->get_status() ) {
			Logs::add_wc_error_log( "Order $order_id has status 'failed'.", 'create-delivery' );
		}

		$iiko_order_id = $this->get_iiko_order_id( $order_id );
		$delivery      = new Delivery( $order_id, $iiko_order_id );

		$order->add_order_note( 'Iiko order ID: ' . $delivery->get_id() );

		$export_api_requests = new Export_API_Requests();
		$created_delivery    = $export_api_requests->create_delivery( $delivery );

		Logs::add_wc_debug_log( $created_delivery, 'create-delivery-response' );

		do_action( 'skyweb_wc_iiko_created_delivery', $created_delivery, $order_id );

		return $created_delivery;
	}

	/**
	 * Print back button.
	 */
	protected function print_back_button() {
		printf( '%1$s%2$s%3$s%4$s', '<hr>', '<a href="' . admin_url( 'edit.php?post_type=shop_order' ) . '">', 'Back', '</a>' );
	}

	/**
	 * Print back button.
	 */
	protected function print_response( $data ) {

		printf( '%1$s%2$s%3$s%4$s', '<pre>', wc_print_r( $data, true ), '</pre>', '<hr>' );

		echo json_encode( $data, JSON_UNESCAPED_UNICODE );

		$this->print_back_button();
	}

	/**
	 * Get iiko order ID.
	 */
	protected function get_iiko_order_id( $order_id ) {

		$iiko_order_id = wc_get_order_item_meta( $order_id, 'skyweb_wc_iiko_order_id' );

		if ( is_string( $iiko_order_id ) ) {
			return $iiko_order_id;
		}

		return null;
	}
}