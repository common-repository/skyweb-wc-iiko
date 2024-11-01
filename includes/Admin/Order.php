<?php

namespace SkyWeb\WC_Iiko\Admin;

defined( 'ABSPATH' ) || exit;

class Order {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'export_order_to_iiko_button' ), 10, 2 );
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'check_created_delivery_from_iiko_button' ), 10, 2 );
	}

	/**
	 * Export button in admin orders list.
	 *
	 * @param $actions
	 * @param $order
	 *
	 * @return mixed
	 */
	function export_order_to_iiko_button( $actions, $order ) {

		if ( ! $order->has_status( array( 'completed' ) ) ) {

			$status   = method_exists( $order, 'get_status' ) ? $order->get_status() : $order->status;
			$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

			$actions['skyweb-wc-iiko-export-order'] = array(
				'url'    => wp_nonce_url(
					admin_url( 'admin-ajax.php?action=skyweb_wc_iiko_export_order&status=' . $status . '&order_id=' . $order_id ),
					'skyweb-wc-iiko-export-order'
				),
				'name'   => esc_attr__( 'Export order to iiko', 'skyweb-wc-iiko' ),
				'action' => 'skyweb-wc-iiko-export-order',
			);
		}

		return $actions;
	}

	/**
	 * Check created delivery button in admin orders list.
	 *
	 * @param $actions
	 * @param $order
	 *
	 * @return mixed
	 */
	function check_created_delivery_from_iiko_button( $actions, $order ) {

		$status   = method_exists( $order, 'get_status' ) ? $order->get_status() : $order->status;
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

		$actions['skyweb-wc-iiko-check-created-delivery'] = array(
			'url'    => wp_nonce_url(
				admin_url( 'admin-ajax.php?action=skyweb_wc_iiko_check_created_delivery&status=' . $status . '&order_id=' . $order_id ),
				'skyweb-wc-iiko-check-created-delivery'
			),
			'name'   => esc_attr__( 'Check created delivery from iiko', 'skyweb-wc-iiko' ),
			'action' => 'skyweb-wc-iiko-check-created-delivery',
		);

		return $actions;
	}
}