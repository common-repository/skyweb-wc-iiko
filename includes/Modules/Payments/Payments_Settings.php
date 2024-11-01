<?php

namespace SkyWeb\WC_Iiko\Modules\Payments;

defined( 'ABSPATH' ) || exit;

class Payments_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_sections_skyweb_wc_iiko_settings', array( $this, 'add_payments_section' ) );
		add_filter( 'woocommerce_get_settings_skyweb_wc_iiko_settings', array( $this, 'add_payments_settings' ), 99, 2 );
	}

	/**
	 * Add payments section.
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function add_payments_section( $sections ) {

		$sections['payments'] = __( 'Payments', 'skyweb-wc-iiko' );

		return $sections;
	}

	/**
	 * Add payments settings.
	 *
	 * @param $settings
	 * @param $current_section
	 *
	 * @return array|mixed
	 */
	public function add_payments_settings( $settings, $current_section ) {

		if ( 'payments' === $current_section ) {

			$payment_types = get_option( 'skyweb_wc_iiko_payment_types' );
			$settings      = array();

			$settings[] = array(
				'title' => __( 'Payment Methods', 'skyweb-wc-iiko' ),
				'type'  => 'title',
				'id'    => 'skyweb_wc_iiko_payments_options',
				'desc'  => sprintf(
					__( "Updated automatically when you press 'Get Payment Methods' button on %splugin page%s.", 'skyweb-wc-iiko' ),
					'<a href="' . admin_url( 'admin.php?page=skyweb_wc_iiko' ) . '" target="_blank">',
					'</a>'
				),
			);

			if ( ! empty( $payment_types ) ) {

				foreach ( $payment_types as $payment_type ) {

					if ( ! empty( $payment_type['code'] ) && empty( $payment_type['isDeleted'] ) ) {

						$settings[] = array(
							'title'    => $payment_type['name'],
							'desc'     => sprintf(
								__( 'WooCommerce payment method code for %1$s%2$s%3$s iiko payment type.', 'skyweb-wc-iiko' ),
								'<b>',
								$payment_type['name'],
								'</b>'
							),
							'id'       => 'skyweb_wc_iiko_paymentType_' . $payment_type['code'],
							'type'     => 'text',
							'css'      => 'width: 300px;',
							'desc_tip' => __( 'Please enter the appropriate WooCommerce payment method code.', 'skyweb-wc-iiko' ),
						);

						// IPE - isProcessedExternally
						$settings[] = array(
							'desc'    => __( 'Processed externally (by external payment system)', 'skyweb-wc-iiko' ),
							'id'      => 'skyweb_wc_iiko_paymentType_' . $payment_type['code'] . '_IPE',
							'type'    => 'checkbox',
							'default' => 'no',
						);
					}
				}
			}

			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'skyweb_wc_iiko_payments_options',
			);

			return apply_filters( 'skyweb_wc_iiko_payments_settings', $settings );
		}

		return $settings;
	}
}