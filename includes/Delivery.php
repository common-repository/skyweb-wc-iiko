<?php

namespace SkyWeb\WC_Iiko;

use JsonSerializable;

defined( 'ABSPATH' ) || exit;

class Delivery implements JsonSerializable {

	protected $id;
	protected $complete_before;
	protected $phone;
	protected $order_type_id;
	protected $delivery_point;
	protected $order_service_type;
	protected $comment;
	protected $customer;
	protected $guests;
	protected $marketing_source_id;
	protected $operator_id;
	protected $items;
	protected $payments;

	public function __construct( $order_id, $iiko_order_id = null ) {

		$order = wc_get_order( $order_id );

		// string <uuid> Nullable
		// Order ID. Must be unique.
		// If sent null, it generates automatically on iikoTransport side.
		$this->id = $this->generate_iiko_id( $order_id, $iiko_order_id );

		// string <yyyy-MM-dd HH:mm:ss.fff> Nullable
		// Order fulfillment date.
		//// Date and time must be local for delivery terminal, without time zone (take a look at example).
		// If null, order is urgent and time is calculated based on customer settings, i.e. the shortest delivery time possible.
		// Permissible values: from current day and 14 days on.
		$this->complete_before = apply_filters( 'skyweb_wc_iiko_order_complete_before', null, $order_id );

		// Required.
		// string [ 8 .. 40 ] characters
		// Telephone number.
		// Must begin with symbol "+" and must be at least 8 digits.
		$this->phone = apply_filters( 'skyweb_wc_iiko_order_phone', preg_replace( '/\D/', '', $order->get_billing_phone() ) );

		if ( empty( $this->phone ) ) {
			Logs::add_wc_error_log( 'User phone is empty.', 'create-delivery' );
		} else {
			$this->phone = $this->trim_string( $this->phone, 40 );
		}

		// string <uuid> Nullable
		// Order type ID.
		// One of the fields required: orderTypeId or orderServiceType.
		$this->order_type_id = null;

		// string Nullable
		// Enum: "DeliveryByCourier" "DeliveryByClient"
		// Order service type.
		// One of the fields required: orderTypeId or orderServiceType.
		$this->order_service_type = $this->is_pickup() ? 'DeliveryByClient' : 'DeliveryByCourier';

		// object Nullable
		// Delivery point details.
		// Not required in case of customer pickup. Otherwise, required.
		$this->delivery_point = $this->delivery_point( $order_id, $order );

		// string Nullable
		// Order comment.
		$this->comment = $this->comment( $order_id, $order );

		// Required.
		// object
		// Customer.
		$this->customer = $this->customer( $order );

		// object Nullable
		// Guest details.
		$this->guests = $this->guests( $order_id );

		// string <uuid> Nullable
		// Marketing source (advertisement) ID.
		$this->marketing_source_id = null;

		// string <uuid> Nullable
		// Operator ID.
		$this->operator_id = null;

		// Required.
		// Array of iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.ProductOrderItem
		// (object) or
		// iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.CompoundOrderItem
		//(object)
		// Order items.
		$this->items = $this->order_items( $order );

		// Array of
		// iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.CashPayment
		// (object) or
		// iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.CardPayment
		// (object) or
		// iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.IikoCardPayment
		// (object) or
		// iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.ExternalPayment
		// (object) Nullable
		// Order payment components.
		$this->payments = apply_filters( 'skyweb_wc_iiko_order_payments', null, $order );
	}

	/**
	 * Check if the shipping method is pickup.
	 */
	protected function generate_iiko_id( $order_id, $iiko_order_id ) {

		if ( is_null( $iiko_order_id ) || empty( $iiko_order_id ) ) {

			$iiko_order_id = wp_generate_uuid4();
			wc_add_order_item_meta( $order_id, 'skyweb_wc_iiko_order_id', $iiko_order_id );

			return $iiko_order_id;
		}

		return sanitize_key( $iiko_order_id );
	}

	/**
	 * Check if the shipping method is pickup.
	 */
	protected function is_pickup() {

		$shipping_methods       = WC()->session->get( 'chosen_shipping_methods' ); // chosen_payment_method
		$chosen_shipping_method = $shipping_methods[0];

		return 0 === strpos( $chosen_shipping_method, 'local_pickup' );
	}

	/**
	 * Delivery point.
	 */
	protected function delivery_point( $order_id, $order ) {

		if ( 'DeliveryByClient' === $this->order_service_type ) {

			return null;

		} else {

			// Required.
			// object
			// Street.
			$address   = array();
			$street_id = sanitize_key( get_post_meta( $order_id, '_billing_iiko_street_id', true ) );

			// It's required specify only "classifierId" or "id" or "name" and "city".
			if ( ! empty( $street_id ) ) {

				// string <uuid> Nullable
				// ID.
				$address['street']['id'] = $street_id; // Already sanitized

			} else {

				$street_name = ! empty( $order->get_billing_address_1() ) ? wp_slash( $order->get_billing_address_1() ) : null;

				// If user set an arbitrary street (we don't have street ID) use default street.
				$default_street = get_option( 'skyweb_wc_iiko_default_street' );

				// Add the arbitrary street name to the order comment.
				$this->comment .= $street_name . PHP_EOL;

				$address['street'] = array(

					// string [ 0 .. 60 ] characters Nullable
					// Name.
					'name' => $this->trim_string( $default_street, 60 ),

					// string [ 0 .. 60 ] characters Nullable
					// City name.
					'city' => ! empty( $order->get_billing_city() ) ? $this->trim_string( wp_slash( $order->get_billing_city() ), 60 ) : null,
				);
			}

			// string [ 0 .. 10 ] characters Nullable
			// Postcode.
			$address['index'] = ! empty( $order->get_billing_postcode() ) ? $this->trim_string( wp_slash( $order->get_billing_postcode() ), 10 ) : null;

			// Required.
			// string [ 0 .. 100 ] characters
			// House.
			// In case useUaeAddressingSystem enabled max length - 100, otherwise - 10
			$address['house'] = ! empty( $order->get_billing_address_2() ) ? $this->trim_string( wp_slash( $order->get_billing_address_2() ), 10 ) : 'NOT SET';

			// string [ 0 .. 10 ] characters Nullable
			// Building.
			$address['building'] = null;

			// string [ 0 .. 100 ] characters Nullable
			// Apartment.
			// In case useUaeAddressingSystem enabled max length - 100, otherwise - 10
			$address['flat'] = null;

			// string [ 0 .. 10 ] characters Nullable
			// Entrance.
			$address['entrance'] = null;

			// string [ 0 .. 10 ] characters Nullable
			// Floor.
			$address['floor'] = null;

			// string [ 0 .. 10 ] characters Nullable
			// Intercom.
			$address['doorphone'] = null;

			// string <uuid> Nullable
			// Delivery area ID.
			$address['regionId'] = null;

			$delivery_point = array(

				// object Nullable
				// Delivery address coordinates.
				'coordinates'           => null,
				/* 'coordinates'           => array(
					// Required.
					// number <double>
					// Latitude
					'latitude'  => '',
					// Required.
					// number <double>
					// Longitude
					'longitude' => '',
				), */

				// object Nullable
				// Order delivery address.
				'address'               => $address,

				// string [ 0 .. 100 ] characters Nullable
				// Delivery location custom code in customer's API system.
				'externalCartographyId' => null,

				// string [ 0 .. 500 ] characters Nullable
				// Additional information.
				'comment'               => null,
			);

			return apply_filters( 'skyweb_wc_iiko_order_delivery_point', $delivery_point, $order_id );
		}
	}

	/**
	 * Customer.
	 */
	protected function comment( $order_id, $order ) {

		$comment_strings[] = $order_id;
		$comment_strings[] = ! empty( $this->comment ) ? $this->comment : '';
		$comment_strings[] = ! empty( $order->get_customer_note() ) ? $order->get_customer_note() : '';
		$comment_strings[] = $order->get_shipping_method();
		$comment_strings   = array_filter( $comment_strings );

		$i       = 1;
		$j       = count( $comment_strings );
		$comment = '';

		foreach ( $comment_strings as $comment_string ) {
			$string_end = $i ++ === $j ? '' : PHP_EOL;
			$comment    .= $comment_string . $string_end;
		}

		return wp_slash( apply_filters( 'skyweb_wc_iiko_order_comment',
			$comment,
			$order_id
		) );
	}

	/**
	 * Customer.
	 */
	protected function customer( $order ) {

		return array(
			// string <uuid> Nullable
			// Existing customer ID in RMS.
			'id'                            => null,

			// string [ 0 .. 60 ] characters Nullable
			// Name of customer.
			// Required for new customers (i.e. if "id" == null) Not required if "id" specified.
			'name'                          => ! empty( $order->get_billing_first_name() ) ? $this->trim_string( wp_slash( $order->get_billing_first_name() ), 60 ) : 'NOT SET',

			// string [ 0 .. 60 ] characters Nullable
			// Last name.
			'surname'                       => ! empty( $order->get_billing_last_name() ) ? $this->trim_string( wp_slash( $order->get_billing_last_name() ), 60 ) : null,

			// string [ 0 .. 60 ] characters Nullable
			// Comment.
			'comment'                       => null,

			// string <yyyy-MM-dd HH:mm:ss.fff> Nullable
			// Date of birth.
			'birthdate'                     => null,

			// string Nullable
			// Email.
			'email'                         => ! empty( $order->get_billing_email() ) ? sanitize_email( $order->get_billing_email() ) : null,

			// boolean
			// Whether user is included in promotional mailing list.
			'shouldReceivePromoActionsInfo' => false,

			// string
			// Enum: "NotSpecified" "Male" "Female"
			// Gender.
			'gender'                        => 'NotSpecified',
		);
	}

	/**
	 * Guests.
	 */
	protected function guests( $order_id ) {

		return apply_filters( 'skyweb_wc_iiko_order_guests',
			array(
				// Required.
				// integer <int32>
				// Number of persons in order. This field defines the number of cutlery sets
				'count'               => 1,

				// Required.
				// boolean
				// Attribute that shows whether order must be split among guests.
				'splitBetweenPersons' => false,
			),
			$order_id
		);
	}

	/**
	 * Create order items array.
	 *
	 * @param $order
	 *
	 * @return array
	 */
	protected function order_items( $order ) {

		$i           = 0;
		$products    = $order->get_items();
		$order_items = array();

		if ( empty( $products ) ) {
			Logs::add_wc_error_log( 'No products in cart.', 'create-delivery' );

			return null;
		}

		foreach ( $products as $product_obj ) {

			$size_iiko_id     = null;
			$product_modifier = null;

			$product      = $product_obj->get_product();
			$product_id   = $product_obj->get_product_id();
			$product_name = $product_obj->get_name();

			// Required parameters.
			$product_iiko_id = sanitize_key( get_post_meta( $product_id, 'skyweb_wc_iiko_product_id', true ) );
			$product_amount  = $product_obj->get_quantity();

			// Exclude products from export without iiko ID.
			if ( empty( $product_iiko_id ) ) {
				Logs::add_wc_notice_log( "Product $product_name does not have iiko ID.", 'create-delivery' );

				continue;
			}

			// Variation.
			if ( $product->is_type( 'variation' ) ) {

				$variation_id                   = $product_obj->get_variation_id();
				$size_iiko_id                   = sanitize_key( get_post_meta( $variation_id, 'skyweb_wc_iiko_product_size_id', true ) );
				$size_iiko_id                   = ! empty( $size_iiko_id ) ? $size_iiko_id : null;
				$product_modifier_iiko_id       = sanitize_key( get_post_meta( $variation_id, 'skyweb_wc_iiko_product_modifier_id', true ) );
				$product_modifier_iiko_id       = ! empty( $product_modifier_iiko_id ) ? $product_modifier_iiko_id : null;
				$product_modifier_group_iiko_id = sanitize_key( get_post_meta( $variation_id, 'skyweb_wc_iiko_product_modifier_group_id', true ) );
				$product_modifier_group_iiko_id = ! empty( $product_modifier_group_iiko_id ) ? $product_modifier_group_iiko_id : null;

				// Exclude variations from export without iiko size and modifier IDs.
				if ( empty( $size_iiko_id ) && empty( $product_modifier_iiko_id ) ) {
					Logs::add_wc_notice_log( "Variation $product_name does not have iiko size ID and iiko modifier ID.", 'create-delivery' );

					continue;
				}

				if ( ! empty( $product_modifier_iiko_id ) ) {

					$product_modifier = array(
						// Required.
						'productId'      => $product_modifier_iiko_id, // Already sanitized
						// Required.
						'amount'         => 1,
						'productGroupId' => $product_modifier_group_iiko_id, // Already sanitized
					);

				}
			}

			$order_items[] = array(
				// Required.
				// string <uuid>
				// ID of menu item.
				'productId'        => $product_iiko_id, // Already sanitized

				// Array of objects
				// (iikoTransport.PublicApi.Contracts.Deliveries.Request.CreateOrder.Modifier) Nullable
				// Modifiers.
				'modifiers'        => ! is_null( $product_modifier ) ? array( $product_modifier ) : array(),

				// number <double> Nullable
				// Price per item unit.
				'price'            => null,

				// string <uuid> Nullable
				// Unique identifier of the item in the order. MUST be unique for the whole system.
				//Therefore it must be generated with Guid.NewGuid().
				// If sent null, it generates automatically on iikoTransport side.
				'positionId'       => null,

				// string
				'type'             => 'Product',

				// Required.
				// number <double> [ 0 .. 999.999 ]
				// Quantity.
				'amount'           => floatval( $product_amount ),
				// TODO - from PHP 7.4
				/*'amount'                    => filter_var( $product_amount,
					FILTER_VALIDATE_FLOAT,
					array(
						'options' => array(
							'min_range' => 0,
							'max_range' => 999.999,
						),
					) ),*/

				// string <uuid> Nullable
				// Size ID. Required if a stock list item has a size scale.
				'productSizeId'    => $size_iiko_id, // Already sanitized

				// object Nullable
				// Combo details if combo includes order item.
				'comboInformation' => null,

				// string [ 0 .. 255 ] characters Nullable
				// Comment.
				'comment'          => null,
			);

			$i ++;
		}

		return $order_items;
	}

	/**
	 * Convert variable to string and trim it.
	 */
	public function trim_string( $val, $max ) {

		return mb_strimwidth( strval( $val ), 0, $max );
	}

	/**
	 * Get ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set ID.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Return JSON object representation.
	 */
	public function jsonSerialize() {
		return array(
			'id'                => $this->id,
			'completeBefore'    => $this->complete_before,
			'phone'             => $this->phone,
			'orderTypeId'       => $this->order_type_id,
			'orderServiceType'  => $this->order_service_type,
			'deliveryPoint'     => $this->delivery_point,
			'comment'           => $this->comment,
			'customer'          => $this->customer,
			'guests'            => $this->guests,
			'marketingSourceId' => $this->marketing_source_id,
			'operatorId'        => $this->operator_id,
			'items'             => $this->items,
			'payments'          => $this->payments,
		);
	}
}