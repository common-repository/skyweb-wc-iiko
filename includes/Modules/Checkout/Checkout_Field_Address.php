<?php

namespace SkyWeb\WC_Iiko\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout_Field_Address {

	private static $logs;

	/**
	 * Initialization.
	 */
	public static function init() {

		global $skyweb_wc_iiko_logs;
		self::$logs = &$skyweb_wc_iiko_logs;

		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'override_city_field' ), 99 );
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'override_address_fields' ), 99 );
		add_action( 'woocommerce_form_field_text', array( __CLASS__, 'add_street_validation_text' ), 10, 2 );
		add_filter( 'woocommerce_checkout_billing', array( __CLASS__, 'add_iiko_streets_datalist' ), 99 );

		if ( 'yes' === get_option( 'skyweb_wc_iiko_process_address_fields' ) ) {
			add_action( 'woocommerce_checkout_process', array( __CLASS__, 'disable_required_address_fields_filter' ) );
			add_action( 'woocommerce_after_checkout_form', array( __CLASS__, 'hide_address_fields_if_shipping_local_pickup' ) );
		}
	}

	/**
	 * Override necessary checkout fields.
	 */
	public static function override_city_field( $fields ) {

		$iiko_city = get_option( 'skyweb_wc_iiko_city_name' );

		if ( ! empty( $iiko_city ) ) {

			$fields['city']['required']          = true;
			$fields['city']['default']           = $iiko_city;
			$fields['city']['custom_attributes'] = array( 'readonly' => 'readonly' );

		} else {
			self::$logs->add_error( 'iiko city is empty.' );
		}

		return $fields;
	}

	/**
	 * Override checkout address fields.
	 */
	public static function override_address_fields( $fields ) {

		$fields['address_1']['required']          = true;
		$fields['address_1']['custom_attributes'] = array( 'list' => 'iiko_streets_datalist' );

		$fields['iiko_street_id']['type']     = 'hidden'; // #billing_iiko_street_id
		$fields['iiko_street_id']['required'] = false;
		$fields['iiko_street_id']['priority'] = 55;

		// TODO - add default value if billing_address_1 has a value
		// $fields['iiko_street_id']['default'] = $iiko_street_id;

		return $fields;
	}

	/**
	 * Add street validation text.
	 */
	public static function add_street_validation_text( $field, $key ) {

		if ( is_checkout() && ( $key == 'billing_address_1' ) ) {
			$field .= '<div id="street_validation_text" class="street_validation_text hidden">';
			$field .= '<p>' . esc_html__( 'Please select a street from the options provided.', 'skyweb-wc-iiko' ) . '<br>';
			$field .= esc_html__( 'If you are sure that there is no such street in the list, then write your own version.', 'skyweb-wc-iiko' ) . '</p>';
			$field .= '</div>';
		}

		return $field;
	}

	/**
	 * Print datalist based on iiko streets.
	 */
	public static function add_iiko_streets_datalist() {

		$html         = '';
		$iiko_streets = get_option( 'skyweb_wc_iiko_streets' );

		if ( empty( $iiko_streets ) ) {
			self::$logs->add_error( 'iiko streets are empty.' );

		} else {

			foreach ( $iiko_streets as $iiko_street_id => $iiko_street_name ) {
				$html .= '<option value="' . $iiko_street_name . '" data-streetid="' . $iiko_street_id . '">';
			}

			$wrapper = '<datalist id="iiko_streets_datalist">%s</datalist>';
			$html    = sprintf( $wrapper, $html );
		}

		echo $html;
	}

	/**
	 * Add filter for WC checkout fields.
	 */
	public static function disable_required_address_fields_filter() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'disable_required_address_fields' ) );
	}

	/**
	 * Disable required address fields on checkout process.
	 */
	public static function disable_required_address_fields( $fields ) {

		$shipping_methods       = WC()->session->get( 'chosen_shipping_methods' ); // chosen_payment_method
		$chosen_shipping_method = $shipping_methods[0];

		if ( 0 === strpos( $chosen_shipping_method, 'local_pickup' ) ) {
			$fields['billing']['billing_address_1']['required'] = false;
			$fields['billing']['billing_address_2']['required'] = false;
		}

		return $fields;
	}

	/**
	 * Hide address fields if shipping local pickup.
	 */
	public static function hide_address_fields_if_shipping_local_pickup( $checkout ) {

		$address_fields_ids = ! empty( get_option( 'skyweb_wc_iiko_address_fields_ids' ) ) ? ', ' . esc_attr( get_option( 'skyweb_wc_iiko_address_fields_ids' ) ) : '';
		?>

        <script>
            (function ($) {

                $(document).ready(function () {
                    show_hide_address_fields();
                });

                $('form.checkout').on('change', 'input[name^="shipping_method"]', function () {
                    show_hide_address_fields();
                });

                function show_hide_address_fields() {

                    let shipping_method = $('input[name^="shipping_method"]:checked').val(),
                        fields_selector = '#billing_address_1_field, #billing_address_2_field<?php echo $address_fields_ids; ?>';

                    if (shipping_method.match("^local_pickup")) {
                        $(fields_selector).fadeOut();
                    } else {
                        $(fields_selector).fadeIn();
                    }
                }

            })(jQuery);
        </script>

		<?php
	}
}