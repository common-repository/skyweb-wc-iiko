<?php

namespace SkyWeb\WC_Iiko\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_sections_skyweb_wc_iiko_settings', array( $this, 'add_checkout_section' ) );
		add_filter( 'woocommerce_get_settings_skyweb_wc_iiko_settings', array( $this, 'add_checkout_settings' ), 99, 2 );
	}

	/**
	 * Add checkout section.
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function add_checkout_section( $sections ) {

		$sections['checkout'] = __( 'Checkout', 'skyweb-wc-iiko' );

		return $sections;
	}

	/**
	 * Add checkout settings.
	 *
	 * @param $settings
	 * @param $current_section
	 *
	 * @return array|mixed
	 */
	public function add_checkout_settings( $settings, $current_section ) {

		if ( 'checkout' === $current_section ) {

			$settings = apply_filters(
				'skyweb_wc_iiko_checkout_settings',
				array(
					array(
						'title' => __( 'Checkout Fields', 'skyweb-wc-iiko' ),
						'type'  => 'title',
						'id'    => 'skyweb_wc_iiko_checkout_options',
						'desc'  => __( "Additional fields and features for WooCommerce checkout page.", 'skyweb-wc-iiko' ),
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_checkout_options',
					),

					array(
						'title' => __( '"Phone" field', 'skyweb-wc-iiko' ),
						'type'  => 'title',
						'id'    => 'skyweb_wc_iiko_checkout_phone_options',
					),

					array(
						'desc'     => __( 'Use billing phone mask on checkout page', 'skyweb-wc-iiko' ),
						'id'       => 'skyweb_wc_iiko_show_imask',
						'type'     => 'checkbox',
						'default'  => 'no',
					),

					array(
						'title'       => __( 'Format', 'skyweb-wc-iiko' ),
						'desc'  => sprintf(
							__( 'Use zeros for arbitrary values and numbers for predefined values. %sRead more%s.', 'skyweb-wc-iiko' ),
							'<a href="' . esc_url( 'https://imask.js.org/guide.html' ) . '" target="_blank">',
							'</a>'
						),
						'id'          => 'skyweb_wc_iiko_imask',
						'type'        => 'text',
						'css'         => 'width: 300px;',
						'placeholder' => '0(000) 0000-00-00',
						'autoload'    => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_checkout_phone_options',
					),

					array(
						'title' => __( '"Address" field', 'skyweb-wc-iiko' ),
						'type'  => 'title',
						'id'    => 'skyweb_wc_iiko_checkout_address_options',
					),

					array(
						'desc'     => __( 'Make not required and hide address fields for local pickup', 'skyweb-wc-iiko' ),
						'id'       => 'skyweb_wc_iiko_process_address_fields',
						'type'     => 'checkbox',
						'default'  => 'no',
					),

					array(
						'title'       => __( 'Fields IDs', 'skyweb-wc-iiko' ),
						'desc'        => __( "Use ID fields separated by commas. For example '#billing_country_field, #billing_city_field, #billing_postcode_field'.", 'skyweb-wc-iiko' ),
						'id'          => 'skyweb_wc_iiko_address_fields_ids',
						'type'        => 'text',
						'autoload'    => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_checkout_address_options',
					),

					array(
						'title' => __( '"Complete before" field', 'skyweb-wc-iiko' ),
						'type'  => 'title',
						'id'    => 'skyweb_wc_iiko_checkout_complete_before_options',
					),

					array(
						'desc'     => __( 'Show complete before field on checkout page', 'skyweb-wc-iiko' ),
						'id'       => 'skyweb_wc_iiko_show_complete_before',
						'type'     => 'checkbox',
						'default'  => 'no',
						'autoload' => false,
					),

					array(
						'title'    => __( 'Position', 'skyweb-wc-iiko' ),
						'desc'     => __( "Action tag for output the field. Default value is 'woocommerce_checkout_after_customer_details'.", 'skyweb-wc-iiko' ),
						'id'       => 'skyweb_wc_iiko_show_complete_before_position',
						'type'     => 'text',
						'css'      => 'width: 300px;',
						'autoload' => false,
					),

					array(
						'title'    => __( 'Start time', 'skyweb-wc-iiko' ),
						'desc'     => __( "Default value is '11'.", 'skyweb-wc-iiko' ),
						'id'       => 'skyweb_wc_iiko_show_complete_before_start_time',
						'type'     => 'text',
						'css'      => 'width: 100px;',
						'autoload' => false,
					),

					array(
						'title'    => __( 'End time', 'skyweb-wc-iiko' ),
						'desc'     => __( "Default value is '23'.", 'skyweb-wc-iiko' ),
						'id'       => 'skyweb_wc_iiko_show_complete_before_end_time',
						'type'     => 'text',
						'css'      => 'width: 100px;',
						'autoload' => false,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_checkout_complete_before_options',
					),
				)
			);
		}

		return $settings;
	}
}