<?php

namespace SkyWeb\WC_Iiko\Modules\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout_Field_Complete_Before {

	/**
	 * Initialization.
	 */
	public static function init() {

		$complete_before_position = ! empty( get_option( 'skyweb_wc_iiko_show_complete_before_position' ) ) ? sanitize_title( get_option( 'skyweb_wc_iiko_show_complete_before_position' ) ) : 'woocommerce_checkout_after_customer_details';

		if ( 'yes' === get_option( 'skyweb_wc_iiko_show_complete_before' ) ) {
			add_action( $complete_before_position, array( __CLASS__, 'complete_before_field_output' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'complete_before_field_update_order_meta' ) );
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'complete_before_field_display_admin_order_meta' ) );
			add_action( 'woocommerce_email_customer_details', array( __CLASS__, 'complete_before_field_email_customer_details' ), 99, 4 );
			add_action( 'wp_footer', array( __CLASS__, 'complete_before_script_footer' ) );
			add_filter( 'skyweb_wc_iiko_order_complete_before', array( __CLASS__, 'complete_before_format_to_iiko' ), 10, 2 );
		}
	}

	/**
	 * Display complete before field.
	 */
	public static function complete_before_field_output() {

		ob_start();

		echo '<h3>' . esc_html__( 'Time', 'skyweb-wc-iiko' ) . '</h3>';

		woocommerce_form_field( 'skyweb_wc_iiko_complete_before_type',
			array(
				'type'        => 'radio',
				'class'       => array( 'form-row-wide', 'delivery-field', 'delivery-date-field' ),
				'label'       => false,
				'label_class' => array( 'card__radio-label' ),
				'input_class' => array( 'card__radio' ),
				'options'     => array(
					'skyweb_wc_iiko_complete_before_asap' => __( 'As soon as possible', 'skyweb-wc-iiko' ),
					'skyweb_wc_iiko_complete_before_btt'  => __( 'By the time', 'skyweb-wc-iiko' ),
				),
				'required'    => false,
			), 'skyweb_wc_iiko_complete_before_asap' );

		woocommerce_form_field( 'skyweb_wc_iiko_complete_before_date',
			array(
				'type'     => 'text',
				'label'    => __( 'Date', 'skyweb-wc-iiko' ),
				'class'    => array( 'form-row-wide', 'delivery-field', 'delivery-date-field' ),
				'required' => false,
			), null );

		$html = ob_get_contents();
		ob_end_clean();

		$wrapper = '<div class="skyweb-wc-iiko-complete-before">%s</div>';

		$html = sprintf( $wrapper, $html );

		echo $html;
	}

	/**
	 * Update additional data fields.
	 **/
	public static function complete_before_field_update_order_meta( $order_id ) {

		if ( ! empty( $_POST['skyweb_wc_iiko_complete_before_type'] ) ) {
			if ( 'skyweb_wc_iiko_complete_before_asap' === $_POST['skyweb_wc_iiko_complete_before_type'] ) {
				update_post_meta( $order_id, 'skyweb_wc_iiko_complete_before_type', sanitize_text_field( __( 'As soon as possible', 'skyweb-wc-iiko' ) ) );
			}

			if ( 'skyweb_wc_iiko_complete_before_btt' === $_POST['skyweb_wc_iiko_complete_before_type'] ) {
				update_post_meta( $order_id, 'skyweb_wc_iiko_complete_before_type', sanitize_text_field( __( 'By the time', 'skyweb-wc-iiko' ) ) );
			}
		}

		if ( ! empty( $_POST['skyweb_wc_iiko_complete_before_date'] ) ) {
			update_post_meta( $order_id, 'skyweb_wc_iiko_complete_before_date', sanitize_text_field( $_POST['skyweb_wc_iiko_complete_before_date'] ) );
		}
	}

	/**
	 * Display custom checkout fields on admin order.
	 */
	public static function complete_before_field_display_admin_order_meta( $order ) {

		if ( get_post_meta( $order->get_id(), 'skyweb_wc_iiko_complete_before_type', true ) ) {
			echo '<p>';
			echo '<strong>' . __( 'Delivery Method', 'skyweb-wc-iiko' ) . ':</strong> ' . get_post_meta( $order->get_id(), 'skyweb_wc_iiko_complete_before_type', true );
			echo '</p>';
		}

		if ( get_post_meta( $order->get_id(), 'skyweb_wc_iiko_complete_before_date', true ) ) {
			echo '<p>';
			echo '<strong>' . __( 'Date', 'skyweb-wc-iiko' ) . ':</strong> ' . get_post_meta( $order->get_id(), 'skyweb_wc_iiko_complete_before_date', true );
			echo '</p>';
		}
	}

	/**
	 * Display custom checkout fields in emails.
	 */
	public static function complete_before_field_email_customer_details( $order, $sent_to_admin, $plain_text, $email ) {

		$result          = '';
		$delivery_method = ! empty( get_post_meta( $order->get_order_number(), 'skyweb_wc_iiko_complete_before_type', true ) ) ?
			get_post_meta( $order->get_order_number(), 'skyweb_wc_iiko_complete_before_type', true ) : '';
		$delivery_date   = ! empty( get_post_meta( $order->get_order_number(), 'skyweb_wc_iiko_complete_before_date', true ) ) ?
			get_post_meta( $order->get_order_number(), 'skyweb_wc_iiko_complete_before_date', true ) : '';

		if ( $plain_text === false ) {
			$result .= '<address class="address" style="font-size: 15px; padding: 12px; border: 1px solid #e5e5e5;">';
			$result .= '<strong>' . __( 'Delivery Method', 'skyweb-wc-iiko' ) . ':</strong> ' . $delivery_method . '<br/>';
			if ( $delivery_date ) {
				$result .= '<strong>' . __( 'Date', 'skyweb-wc-iiko' ) . ':</strong> ' . $delivery_date . '<br/>';
			}
			$result .= '</address ><br/>';

		} else {
			$result .= __( 'Delivery Method', 'skyweb-wc-iiko' ) . $delivery_method . PHP_EOL;
			$result .= __( 'Date', 'skyweb-wc-iiko' ) . $delivery_date . PHP_EOL;
		}

		echo $result;
	}

	/**
	 * Add script for complete before field.
	 */
	public static function complete_before_script_footer() {

		$start_time = ! empty( get_option( 'skyweb_wc_iiko_show_complete_before_start_time' ) ) ? sanitize_text_field( get_option( 'skyweb_wc_iiko_show_complete_before_start_time' ) ) : '11';
		$end_time   = ! empty( get_option( 'skyweb_wc_iiko_show_complete_before_end_time' ) ) ? sanitize_text_field( get_option( 'skyweb_wc_iiko_show_complete_before_end_time' ) ) : '23';
		?>

        <script>
            (function ($) {

                $(document).ready(function () {

                    /**
                     * Datetimepicker on checkout page.
                     *
                     * https://github.com/xdan/datetimepicker
                     */
                    if ($('body.woocommerce-checkout').length) {

                        let today = new Date();

                        function dateFormat(date, separator) {
                            let fullYear = date.getFullYear(),
                                month = date.getMonth() + 1, // Month from 0 to 11
                                day = date.getDate();

                            return fullYear + separator + (month <= 9 ? '0' + month : month) + separator + (day <= 9 ? '0' + day : day);
                        }

                        function timeFormat(date) {
                            let hours = date.getHours(),
                                minutes = date.getMinutes();
                            // seconds = date.getSeconds(),
                            // milliseconds = date.getMilliseconds();

                            return (hours <= 9 ? '0' + hours : hours) + ':' + (minutes <= 9 ? '0' + minutes : minutes);
                        }

                        let deliveryDate = $('#skyweb_wc_iiko_complete_before_date'),
                            minTime = today.getHours() < <?php echo $start_time; ?> ? '<?php echo $start_time; ?>:30' : timeFormat(today),
                            maxTime = '<?php echo $end_time; ?>:00',
                            twoWeeksLater = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000),
                            maxDate = dateFormat(twoWeeksLater, '/');

                        $.datetimepicker.setLocale('ru');

                        function delivery_datepicker(minTime) {

                            deliveryDate.datetimepicker({
                                format: 'Y-m-d H:i:s',
                                startDate: dateFormat(today, '/'),
                                startTime: timeFormat(today),
                                step: 30,
                                defaultDate: dateFormat(today, '/'),
                                defaultTime: timeFormat(today),
                                minDate: dateFormat(today, '/'),
                                maxDate: maxDate,
                                minTime: minTime,
                                maxTime: maxTime,
                                opened: true,
                                initTime: true,
                                inline: true,
                                todayButton: true,
                                prevButton: true,
                                nextButton: true,
                                defaultSelect: true,
                                scrollMonth: false,
                                scrollTime: true,
                                scrollInput: true,
                                yearStart: today.getFullYear(),
                                yearEnd: today.getFullYear(),
                            });
                        }

                        delivery_datepicker(minTime);

                        deliveryDate.change(function () {

                            let chosenDate = $(this).val().split(' ')[0];

                            if (chosenDate === dateFormat(today, '-')) {
                                delivery_datepicker(minTime);
                            } else {
                                delivery_datepicker('<?php echo $start_time; ?>:30');
                            }
                        });
                    }

                });/* $(document).ready */

            })(jQuery);
        </script>

		<?php
	}

	/**
	 * Complete before to iiko export.
	 */
	public static function complete_before_format_to_iiko( $date, $order_id ) {

		if ( is_null( $date ) ) {

			date_default_timezone_set( 'Europe/Moscow' );

			// TODO - date validation.
			// yyyy-MM-dd HH:mm:ss
			$date = get_post_meta( $order_id, 'skyweb_wc_iiko_complete_before_date', true );
			$now  = date( 'Y-m-d H:i:s', time() );

			if ( ! empty( $date ) ) {

				// If date/time from the past.
				if ( strtotime( $date ) < strtotime( $now ) ) {
					return null;
				}

				// If user forgot set time correct it.
				if ( strpos( $date, '00:00:00' ) ) {
					$nearest_time = date( 'H:i', time() + 1800 );
					$date         = str_replace( '00:00:00', $nearest_time . ':00', $date );
				}

				// If date/time is OK add msecs.
				$date .= '.000';

			} else {
				return null;
			}
		}

		return $date;
	}
}