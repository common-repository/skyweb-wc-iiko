<?php

namespace SkyWeb\WC_Iiko\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout_Field_Phone {

	/**
	 * Initialization.
	 */
	public static function init() {

		if ( 'yes' === get_option( 'skyweb_wc_iiko_show_imask' ) ) {
			add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_billing_phone' ), 99, 2 );
			add_action( 'wp_footer', array( __CLASS__, 'imask_script_footer' ) );
		}

		add_filter( 'skyweb_wc_iiko_order_phone', array( __CLASS__, 'custom_iiko_order_phone' ), 10, 2 );
	}

	/**
	 * Validation for Billing Phone checkout field.
	 */
	public static function validate_billing_phone( $fields, $errors ) {

		$phone      = preg_replace( '/\D/', '', $fields['billing_phone'] );
		$is_correct = preg_match( '/^[0-9\D]{11}/i', $phone );

		if ( $phone && ! $is_correct ) {
			wc_add_notice( __( 'Phone number must be at least <strong>11 digits</strong>.', 'skyweb-wc-iiko' ), 'error' );
		}
	}

	/**
	 * Add script for phone mask.
	 */
	public static function imask_script_footer() {

		$imask = ! empty( get_option( 'skyweb_wc_iiko_imask' ) ) ? sanitize_text_field( get_option( 'skyweb_wc_iiko_imask' ) ) : '0(000) 000-00-00';
		?>

        <script>
            (function ($) {
                $(document).ready(function () {

                    if ($('#billing_phone').length) {
                        let phoneField = document.querySelector('#billing_phone'),
                            maskOptions = {
                                mask: '<?php echo $imask; ?>',
                                lazy: false, // make placeholder always visible
                                placeholderChar: '_'
                            };

                        IMask(phoneField, maskOptions);
                    }

                });
            })(jQuery);
        </script>

		<?php
	}

	/**
	 * Custom guests field for iiko export.
	 */
	public static function custom_iiko_order_phone( $phone ) {

		// Return if phone is empty.
		if ( empty( $phone ) ) {
			return $phone;
		}

		// Remove leading +.
		if ( '+' === $phone[0] ) {
			$phone = substr( $phone, 1 );
		}

		// Replace leading 8 to 7.
		if ( '8' === $phone[0] ) {
			$phone = substr( $phone, 1 );
			$phone = substr_replace( $phone, '7', 0, 0 );
		}

		// Phone number must begin with symbol "+" and must be at least 8 digits.
		return '+' . $phone;
	}
}