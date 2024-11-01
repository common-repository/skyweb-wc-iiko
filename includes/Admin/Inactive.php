<?php

namespace SkyWeb\WC_Iiko\Admin;

defined( 'ABSPATH' ) || exit;

class Inactive {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'wc_inactive_admin_notice' ) );
	}

	/**
	 * Admin notice if WooCommerce is inactive.
	 */
	public function wc_inactive_admin_notice() {
		$class   = 'notice notice-warning is-dismissible';
		$message = esc_html__( 'IikoCloud needs WooCommerce to run. Please, install and active WooCommerce plugin.', 'skyweb-wc-iiko' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}