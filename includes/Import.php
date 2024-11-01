<?php

namespace SkyWeb\WC_Iiko;

use WC_Product_Attribute;
use WC_Product_Variation;
use WPSEO_Taxonomy_Meta;

defined( 'ABSPATH' ) || exit;

class Import {

	/**
	 * Insert or update product category.
	 *
	 * Return false if cannot insert/update the product category or array with keys 'iiko_id' and 'term_id' otherwise.
	 *
	 * @param array $product_cat_info
	 *
	 * @return array|false
	 */
	public static function insert_update_product_cat( $product_cat_info ) {

		global $skyweb_wc_iiko_logs;

		if ( ! is_array( $product_cat_info ) || empty( $product_cat_info ) ) {
			$skyweb_wc_iiko_logs->add_error( 'Product category information is empty.' );

			return false;
		}

		// Product category data.
		$product_cat_name                = wp_strip_all_tags( $product_cat_info['name'] );
		$product_cat_desc                = ! is_null( $product_cat_info['description'] ) ? $product_cat_info['description'] : ''; // Handled by sanitize_term() in wp_insert_term()/wp_update_term()
		$product_cat_iiko_id             = sanitize_key( $product_cat_info['id'] );
		$product_cat_thumb_urls          = $product_cat_info['imageLinks'];
		$product_cat_is_included_in_menu = true === $product_cat_info['isIncludedInMenu'];
		$product_cat_is_group_modifier   = true === $product_cat_info['isGroupModifier'];
		$product_cat_is_deleted          = true === $product_cat_info['isDeleted'];
		$product_cat_seo_title           = ! is_null( $product_cat_info['seoTitle'] ) ? sanitize_text_field( $product_cat_info['seoTitle'] ) : null;
		$product_cat_seo_desc            = ! is_null( $product_cat_info['seoText'] ) ? sanitize_text_field( $product_cat_info['seoText'] ) : null;

		// Skip excluded group.
		if ( false === $product_cat_is_included_in_menu ) {
			$skyweb_wc_iiko_logs->add_error( "Product category '$product_cat_name' is excluded in iiko." );

			return false;
		}

		// Skip modifier group.
		if ( true === $product_cat_is_group_modifier ) {
			$skyweb_wc_iiko_logs->add_error( "Product category '$product_cat_name' is a group modifier." );

			return false;
		}

		// Skip deleted group.
		if ( true === $product_cat_is_deleted ) {
			$skyweb_wc_iiko_logs->add_error( "Product category '$product_cat_name' is deleted in iiko." );

			return false;
		}

		// Check if the product category exists.
		$term = term_exists( $product_cat_name, 'product_cat' );

		// Update the product category if it exists.
		if ( $term !== 0 && $term !== null ) {

			$term_id = absint( $term['term_id'] );

			$updated_product_cat = wp_update_term(
				$term_id,
				'product_cat',
				array(
					'name'        => $product_cat_name,
					'description' => $product_cat_desc,
				) );

			// Process the result of updating the product category.
			$product_cat_process_result = self::insert_update_result(
				$updated_product_cat,
				$term_id,
				$product_cat_name,
				'update',
				'Product category'
			);

			// Create the product category if it doesn't exist.
		} else {

			$inserted_product_cat = wp_insert_term(
				$product_cat_name,
				'product_cat',
				array(
					'description' => $product_cat_desc,
					'parent'      => 0,
				)
			);

			$term_id = absint( $inserted_product_cat['term_id'] );

			// Process the result of product category insertion.
			$product_cat_process_result = self::insert_update_result(
				$inserted_product_cat,
				$term_id,
				$product_cat_name,
				'insert',
				'Product category'
			);
		}

		if ( false !== $product_cat_process_result ) {

			// Insert/update product category iiko id.
			self::insert_update_iiko_id( $term_id, $product_cat_iiko_id, 'product_cat' );

			// Insert/update product category image.
			self::insert_update_thumb( $term_id, $product_cat_name, $product_cat_thumb_urls );

			// Insert/update product category SEO data.
			if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
				self::insert_update_seo_data( $term_id, $product_cat_seo_title, $product_cat_seo_desc );
			}

			return array(
				'iiko_id' => $product_cat_iiko_id,
				'term_id' => $term_id,
			);
		}

		return false;
	}

	/**
	 * Insert or update product.
	 *
	 * Return false if cannot insert/update the product or true otherwise.
	 *
	 * @param array $product_info
	 * @param string|int|array $terms
	 * @param array|false $modifiers
	 * @param array|false $sizes
	 * @param array $groups_list
	 *
	 * @return true|false
	 */
	public static function insert_update_product( $product_info, $terms, $modifiers, $sizes, $groups_list ) {

		global $skyweb_wc_iiko_logs;

		if ( ! is_array( $product_info ) || empty( $product_info ) ) {
			$skyweb_wc_iiko_logs->add_error( 'Product information is empty.' );

			return false;
		}

		// Make sure that all product category IDs are integer.
		$terms = is_array( $terms ) ? array_map( 'intval', $terms ) : absint( $terms );

		// Product data.
		$product_name         = wp_strip_all_tags( $product_info['name'] );
		$product_desc         = ! is_null( $product_info['description'] ) ? $product_info['description'] : ''; // Handled by sanitize_post() in wp_insert_post()
		$product_excerpt      = ! is_null( $product_info['additionalInfo'] ) ? $product_info['additionalInfo'] : ''; // Handled by sanitize_post() in wp_insert_post()
		$product_iiko_id      = sanitize_key( $product_info['id'] );
		$product_iiko_sku     = sanitize_key( $product_info['code'] );
		$product_sizes        = is_array( $product_info['sizePrices'] ) && ! empty( $product_info['sizePrices'] ) ? $product_info['sizePrices'] : false;
		$product_sizes_title  = esc_attr__( 'Size', 'skyweb-wc-iiko' );
		$is_product_modifiers = isset( $product_info['groupModifiers'][0] ) && ! empty( $product_info['groupModifiers'][0] );

		if ( $is_product_modifiers ) {
			$product_modifiers_info  = $product_info['groupModifiers'][0];
			$product_modifier_group  = ! is_null( $product_modifiers_info['id'] ) ? $product_modifiers_info['id'] : '';
			$product_modifiers       = is_array( $product_modifiers_info['childModifiers'] ) && ! empty( $product_modifiers_info['childModifiers'] ) ?
				$product_modifiers_info['childModifiers'] :
				false;
			$product_modifiers_title = isset( $product_modifiers_info['id'] ) ?
				$groups_list[ $product_modifiers_info['id'] ] :
				esc_attr__( 'Modifier', 'skyweb-wc-iiko' );
		} else {
			$product_modifier_group  = '';
			$product_modifiers       = false;
			$product_modifiers_title = '';
		}

		$product_price       = isset( $product_sizes[0]['price']['currentPrice'] ) ? floatval( $product_sizes[0]['price']['currentPrice'] ) : 0;
		$product_weight      = isset( $product_info['weight'] ) ? round( floatval( $product_info['weight'] ) * 1000 ) : 0;
		$product_thumb_urls  = $product_info['imageLinks'];
		$product_tags        = $product_info['tags'];
		$is_included_in_menu = true === $product_sizes[0]['price']['isIncludedInMenu'];
		$product_is_deleted  = true === $product_info['isDeleted'];
		$product_seo_title   = ! is_null( $product_info['seoTitle'] ) ? sanitize_text_field( $product_info['seoTitle'] ) : null;
		$product_seo_desc    = ! is_null( $product_info['seoText'] ) ? sanitize_text_field( $product_info['seoText'] ) : null;

		// Skip deleted product.
		if ( true === $product_is_deleted ) {
			$skyweb_wc_iiko_logs->add_error( "Product '$product_name' is deleted in iiko." );

			return false;
		}

		// Load post_exists function in front for CRON jobs.
		if ( ! is_admin() ) {
			require_once( ABSPATH . 'wp-admin/includes/post.php' );
		}

		// Check if the product exists.
		$post = post_exists( $product_name, '', '', 'product' );

		// Update the product if it exists.
		if ( $post !== 0 ) {

			$post_id = absint( $post );

			$updated_product = wp_insert_post( array(
				'ID'           => $post_id,
				'post_title'   => $product_name,
				'post_content' => $product_desc,
				'post_excerpt' => $product_excerpt,
				'post_status'  => 'publish',
				'post_type'    => 'product',
			), true );

			// Process the result of updating the product.
			$product_process_result = self::insert_update_result(
				$updated_product,
				$post_id,
				$product_name,
				'update',
				'Product'
			);

			// Create the product if it doesn't exist.
		} else {

			$inserted_product = wp_insert_post( array(
				'post_title'   => $product_name,
				'post_content' => $product_desc,
				'post_excerpt' => $product_excerpt,
				'post_status'  => 'publish',
				'post_type'    => 'product',
			), true );

			$post_id = absint( $inserted_product ); // redundant

			// Process the result of product insertion.
			$product_process_result = self::insert_update_result(
				$inserted_product,
				$post_id,
				$product_name,
				'insert',
				'Product'
			);
		}

		if ( false !== $product_process_result ) {

			// Relate the product to the product categories.
			self::relate_product_to_cat( $post_id, $terms, $product_name );

			// Insert/update product iiko ID.
			self::insert_update_iiko_id( $post_id, $product_iiko_id, 'product' );

			// Insert/update product metadata and variations.
			// Simple product - there are not sizes (there is only one size without size ID) and modifiers.
			if ( null === $product_sizes[0]['sizeId'] && false === $product_modifiers ) {

				self::insert_update_product_metadata( $post_id, $product_iiko_sku, $product_price, $product_weight, $is_included_in_menu );

				// Variable product.
			} else {

				// Remove current product attributes and variations.
				self::remove_product_attributes_variations( $post_id );

				// Make the product variable.
				wp_set_object_terms( $post_id, 'variable', 'product_type' );

				// 1. The product has sizes only. Adding sizes to the product as attributes with their prices.
				if ( null !== $product_sizes[0]['sizeId'] && false === $product_modifiers ) {

					// Searching sizes names bases on their IDs.
					$product_sizes_iiko_ids = array_column( $product_sizes, 'sizeId' );
					$product_sizes_values   = self::search_current_product_attrs_names( $product_sizes_iiko_ids, $sizes );

					// Prepare attributes for the product.
					$attributes[ $product_sizes_title ] = $product_sizes_values;

					self::insert_product_attributes( $post_id, $attributes );

					// Add variations to the product.
					$i = 1;
					foreach ( $product_sizes as $product_size ) {

						$product_size_iiko_id = sanitize_key( $product_size['sizeId'] );
						$product_sizes_name   = sanitize_text_field( $sizes[ $product_size_iiko_id ]['name'] );

						if ( ! empty( $product_sizes_name ) ) {

							$product_variation_price = isset( $product_size['price']['currentPrice'] ) ? floatval( $product_size['price']['currentPrice'] ) : 0;

							$variation_data = array(
								'attributes'    => array(
									sanitize_title( $product_sizes_title ) => $product_sizes_name,
								),
								'size_iiko_id'  => $product_size_iiko_id,
								'regular_price' => $product_variation_price,
								'sku'           => $product_iiko_sku . '-' . $i,
							);

							self::insert_product_variation( $post_id, $variation_data );

						} else {
							$skyweb_wc_iiko_logs->add_error( "Size name for product '$product_name' is empty." );
						}

						$i ++;
					}

					$skyweb_wc_iiko_logs->add_notice( "Product '$product_name' has sizes." );
				}

				// 2. The product has modifiers only. Adding modifiers to the product as attributes with the product price.
				if ( null === $product_sizes[0]['sizeId'] && false !== $product_modifiers ) {

					// Searching modifiers names bases on their IDs.
					$product_modifiers_iiko_ids = array_column( $product_modifiers, 'id' );
					$product_modifiers_values   = self::search_current_product_attrs_names( $product_modifiers_iiko_ids, $modifiers );

					// Prepare attributes for the product.
					$attributes[ $product_modifiers_title ] = $product_modifiers_values;

					self::insert_product_attributes( $post_id, $attributes );

					// Add variations to the product.
					$i = 1;
					foreach ( $product_modifiers as $product_modifier ) {

						$product_modifier_iiko_id       = sanitize_key( $product_modifier['id'] );
						$product_modifier_group_iiko_id = sanitize_key( $product_modifier_group );
						$product_modifiers_name         = sanitize_text_field( $modifiers[ $product_modifier_iiko_id ]['name'] );

						if ( ! empty( $product_modifiers_name ) ) {

							$variation_data = array(
								'attributes'             => array(
									sanitize_title( $product_modifiers_title ) => $product_modifiers_name,
								),
								'modifier_iiko_id'       => $product_modifier_iiko_id,
								'modifier_group_iiko_id' => $product_modifier_group_iiko_id,
								'regular_price'          => $product_price,
								'sku'                    => $product_iiko_sku . '-' . $i,
							);

							self::insert_product_variation( $post_id, $variation_data );

						} else {
							$skyweb_wc_iiko_logs->add_error( "Modifier name for product '$product_name' is empty." );
						}

						$i ++;
					}

					$skyweb_wc_iiko_logs->add_notice( "Product '$product_name' has modifiers." );
				}

				// 3. The product has both sizes and modifiers. Adding sizes and modifiers to the product as attributes combinations with the sizes prices.
				if ( null !== $product_sizes[0]['sizeId'] && false !== $product_modifiers ) {

					// Searching sizes and modifiers names bases on their IDs.
					$product_sizes_iiko_ids     = array_column( $product_sizes, 'sizeId' );
					$product_sizes_values       = self::search_current_product_attrs_names( $product_sizes_iiko_ids, $sizes );
					$product_modifiers_iiko_ids = array_column( $product_modifiers, 'id' );
					$product_modifiers_values   = self::search_current_product_attrs_names( $product_modifiers_iiko_ids, $modifiers );

					// Prepare attributes for the product.
					$attributes[ $product_sizes_title ]     = $product_sizes_values;
					$attributes[ $product_modifiers_title ] = $product_modifiers_values;

					self::insert_product_attributes( $post_id, $attributes );

					// Add variations to the product.
					$i = 1;
					foreach ( $product_sizes as $product_size ) {

						$product_size_iiko_id = sanitize_key( $product_size['sizeId'] );
						$product_sizes_name   = sanitize_text_field( $sizes[ $product_size_iiko_id ]['name'] );

						if ( ! empty( $product_sizes_name ) ) {

							$product_variation_price = isset( $product_size['price']['currentPrice'] ) ? floatval( $product_size['price']['currentPrice'] ) : 0;

							$j = 1;
							foreach ( $product_modifiers as $product_modifier ) {

								$product_modifier_iiko_id       = sanitize_key( $product_modifier['id'] );
								$product_modifier_group_iiko_id = sanitize_key( $product_modifier_group );
								$product_modifiers_name         = sanitize_text_field( $modifiers[ $product_modifier_iiko_id ]['name'] );

								if ( ! empty( $product_modifiers_name ) ) {

									$variation_data = array(
										'attributes'             => array(
											sanitize_title( $product_sizes_title )     => $product_sizes_name,
											sanitize_title( $product_modifiers_title ) => $product_modifiers_name,
										),
										'size_iiko_id'           => $product_size_iiko_id,
										'modifier_group_iiko_id' => $product_modifier_group_iiko_id,
										'modifier_iiko_id'       => $product_modifier_iiko_id,
										'regular_price'          => $product_variation_price,
										'sku'                    => $product_iiko_sku . '-' . $i . '-' . $j,
									);

									self::insert_product_variation( $post_id, $variation_data );

								} else {
									$skyweb_wc_iiko_logs->add_error( "Modifier name for product '$product_name' is empty." );
								}

								$j ++;
							}

						} else {
							$skyweb_wc_iiko_logs->add_error( "Size name for product '$product_name' is empty." );
						}

						$i ++;
					}

					$skyweb_wc_iiko_logs->add_notice( "Product '$product_name' has sizes and modifiers." );
				}

				// Set variable product SKU.
				$product = wc_get_product( $post_id );
				$product->set_sku( $product_iiko_sku );
				$product->save();
			}

			// Insert/update product tags.
			self::insert_update_product_tags( $post_id, $product_tags );

			// Insert/update product image.
			self::insert_update_thumb( $post_id, $product_name, $product_thumb_urls, 'product' );

			// Insert/update product SEO data.
			if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
				self::insert_update_seo_data( $post_id, $product_seo_title, $product_seo_desc, 'product' );
			}
		}

		return $product_process_result;
	}

	/**
	 * Add error to $skyweb_wc_iiko_logs based on wp_error information.
	 *
	 * @param $wp_error_obj
	 * @param $message
	 */
	private static function log_wp_error( $wp_error_obj, $message ) {

		global $skyweb_wc_iiko_logs;

		$wp_error_code    = $wp_error_obj->get_error_code();
		$wp_error_message = $wp_error_obj->get_error_message();
		$wp_error_data    = $wp_error_obj->get_error_data();
		$error_message    = sprintf( '%1$s: (%2$s) %3$s %4$s',
			$message,
			$wp_error_code,
			$wp_error_message,
			$wp_error_data
		);

		$skyweb_wc_iiko_logs->add_error( $error_message );
	}

	/**
	 * Process wp_update/wp_insert result.
	 *
	 * Return true if no errors, false otherwise.
	 *
	 * @param $wp_insert_update_result
	 * @param $id
	 * @param $name
	 * @param $action
	 * @param $type
	 *
	 * @return true|false
	 */
	private static function insert_update_result( $wp_insert_update_result, $id, $name, $action, $type ) {

		global $skyweb_wc_iiko_logs;

		if ( $wp_insert_update_result === 0 ) {

			$success_message = sprintf( '%1$s \'%2$s\' %3$s error.',
				$type,
				$name,
				$action
			);

			$skyweb_wc_iiko_logs->add_error( $success_message );

			return false;

		} elseif ( is_wp_error( $wp_insert_update_result ) ) {

			$error_message = sprintf( '%1$s \'%2$s\' %3$s error',
				$type,
				$name,
				$action
			);

			self::log_wp_error( $wp_insert_update_result, $error_message );

			return false;

		} else {

			$action_past_tense = 'update' === $action ? 'updated' : ( 'insert' === $action ? 'inserted' : '' );

			$success_message = sprintf( '%1$s \'%2$s\' is successfully %3$s (ID: %4$d).',
				$type,
				$name,
				$action_past_tense,
				$id
			);

			$skyweb_wc_iiko_logs->add_notice( $success_message );

			return true;
		}
	}

	/**
	 * Relate the product to the product categories.
	 *
	 * Return true on successful, false on failure.
	 *
	 * @param $id
	 * @param $terms
	 *
	 * @return true|false
	 */
	private static function relate_product_to_cat( $id, $terms, $name ) {

		$term_taxonomy_ids = wp_set_object_terms( $id, $terms, 'product_cat' );

		// Return WP_Error when failure create product and product category relation.
		if ( is_wp_error( $term_taxonomy_ids ) ) {

			self::log_wp_error( $term_taxonomy_ids, "Error while create relation between product $name and product category" );

			return false;
		}

		return true;
	}

	/**
	 * Insert or update iiko group/product ID.
	 *
	 * Return true on successful, false on failure.
	 *
	 * @param $id
	 * @param $iiko_id
	 * @param $type
	 *
	 * @return true|false
	 */
	private static function insert_update_iiko_id( $id, $iiko_id, $type ) {

		global $skyweb_wc_iiko_logs;

		switch ( $type ) {
			case 'product_cat':
				$inserted_updated_meta = update_term_meta( $id, 'skyweb_wc_iiko_group_id', $iiko_id );
				break;
			case 'product':
				$inserted_updated_meta = update_post_meta( $id, 'skyweb_wc_iiko_product_id', $iiko_id );
				break;
			case 'product_size':
				$inserted_updated_meta = update_post_meta( $id, 'skyweb_wc_iiko_product_size_id', $iiko_id );
				break;
			case 'product_modifier':
				$inserted_updated_meta = update_post_meta( $id, 'skyweb_wc_iiko_product_modifier_id', $iiko_id );
				break;
			case 'product_modifier_group':
				$inserted_updated_meta = update_post_meta( $id, 'skyweb_wc_iiko_product_modifier_group_id', $iiko_id );
				break;
			default:
				$skyweb_wc_iiko_logs->add_error( 'Incorrect post type while iiko id updating.' );

				return false;
		}

		// Return WP_Error when term_id is ambiguous between taxonomies.
		if ( is_wp_error( $inserted_updated_meta ) ) {

			self::log_wp_error( $inserted_updated_meta, 'Error while updating iiko ID.' );

			return false;
		}

		return true;
	}

	/**
	 * Insert or update product metadata.
	 *
	 * @param $post_id
	 * @param $sku
	 * @param $price
	 * @param $weight
	 * @param $is_included_in_menu
	 */
	private static function insert_update_product_metadata( $post_id, $sku, $price, $weight, $is_included_in_menu ) {

		// Hide disabled products and products without price.
		if ( true !== $is_included_in_menu || empty( $price ) ) {

			$product = wc_get_product( $post_id );
			$product->set_catalog_visibility( 'hidden' );
			$product->save();

		} else {

			$product = wc_get_product( $post_id );
			$product->set_catalog_visibility( 'visible' );
			$product->save();
		}

		update_post_meta( $post_id, '_stock_status', 'instock' );
		update_post_meta( $post_id, 'total_sales', '0' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_virtual', 'no' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', 'no' );
		update_post_meta( $post_id, '_weight', $weight );
		update_post_meta( $post_id, '_length', '' );
		update_post_meta( $post_id, '_width', '' );
		update_post_meta( $post_id, '_height', '' );
		update_post_meta( $post_id, '_sku', $sku );
		update_post_meta( $post_id, '_product_attributes', array() );
		update_post_meta( $post_id, '_sold_individually', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );
		update_post_meta( $post_id, '_backorder', 'no' );
		update_post_meta( $post_id, '_stock', '' );

		self::insert_update_product_price( $post_id, $price );

		wc_delete_product_transients( $post_id );
	}

	/**
	 * Insert or update product/variation price.
	 *
	 * @param $post_id
	 * @param $price
	 */
	private static function insert_update_product_price( $post_id, $price ) {

		if ( ! empty( $post_id ) && ! empty( $price ) ) {
			update_post_meta( $post_id, '_price', $price );
			update_post_meta( $post_id, '_regular_price', $price );
		}
	}

	/**
	 * Remove all product attributes and variations.
	 *
	 * @param int $product_id
	 */
	private static function remove_product_attributes_variations( $product_id ) {

		$product = wc_get_product( $product_id );

		// Remove all exist product attributes.
		$old_attributes = $product->get_attributes();

		if ( ! empty( $old_attributes ) ) {
			foreach ( $old_attributes as $old_attribute ) {
				wc_delete_attribute( $old_attribute->get_id() );
			}
		}

		// Remove all exist product variations.
		if ( $product->is_type( 'variable' ) ) {

			$variations = $product->get_available_variations();

			if ( ! empty( $variations ) ) {
				$variations_id = wp_list_pluck( $variations, 'variation_id' );

				foreach ( $variations_id as $variation_id ) {
					wp_delete_post( $variation_id, true );
				}
			}
		}

		$product->save();
	}

	/**
	 * Create a product attributes for a defined variable product ID.
	 *
	 * @param int $product_id
	 * @param array $attributes
	 */
	private static function insert_product_attributes( $product_id, $attributes ) {

		$i                  = 0;
		$default_attributes = array();
		$product            = wc_get_product( $product_id );

		foreach ( $attributes as $attribute_name => $attribute_values ) {

			$attribute[ $i ] = new WC_Product_Attribute();

			// Set the attribute options.
			$attribute[ $i ]->set_id( 0 );
			$attribute[ $i ]->set_name( $attribute_name );
			$attribute[ $i ]->set_options( $attribute_values );
			$attribute[ $i ]->set_position( $i );
			$attribute[ $i ]->set_visible( true );
			$attribute[ $i ]->set_variation( true );

			$default_attributes[ sanitize_title( $attribute_name ) ] = $attribute_values[0];

			$i ++;
		}

		if ( isset( $attribute ) && is_array( $attribute ) && ! empty( $attribute ) ) {

			// Set attributes to the product.
			$product->set_attributes( $attribute );
			$product->set_default_attributes( $default_attributes );
			$product->save();
		}
	}

	/**
	 * Create a product variation for a defined variable product ID.
	 *
	 * @param int $product_id
	 * @param array $variation_data
	 */
	private static function insert_product_variation( $product_id, $variation_data ) {

		// Create new variation object.
		$variation = new WC_Product_Variation();

		// Set the variation to the product.
		$variation->set_parent_id( $product_id );

		// Set the product attributes to the variation.
		foreach ( $variation_data['attributes'] as $variation_attribute_name => $variation_attribute_value ) {
			$attributes[ $variation_attribute_name ] = $variation_attribute_value;
		}

		$variation->set_attributes( $attributes );

		// Set the variation options.
		$variation->set_status( 'publish' );
		$variation->set_sku( $variation_data['sku'] );
		$variation->set_regular_price( $variation_data['regular_price'] );
		$variation->set_price( $variation_data['regular_price'] );

		// Save variation (returns variation id).
		$variation_id = $variation->save();

		$product = wc_get_product( $product_id );
		$product->save();

		// self::insert_update_product_price( $variation_id, $variation_data['regular_price'] );

		// Insert/update product size/modifier iiko ID.
		if ( isset( $variation_data['size_iiko_id'] ) ) {
			self::insert_update_iiko_id( $variation_id, $variation_data['size_iiko_id'], 'product_size' );
		}
		if ( isset( $variation_data['modifier_iiko_id'] ) ) {
			self::insert_update_iiko_id( $variation_id, $variation_data['modifier_iiko_id'], 'product_modifier' );
		}
		if ( isset( $variation_data['modifier_group_iiko_id'] ) ) {
			self::insert_update_iiko_id( $variation_id, $variation_data['modifier_group_iiko_id'], 'product_modifier_group' );
		}
	}

	/**
	 * Insert or update product tags.
	 *
	 * @param $id
	 * @param $tags
	 *
	 * @return true|false
	 */
	private static function insert_update_product_tags( $id, $tags ) {

		if ( ! is_array( $tags ) || empty( $tags ) ) {
			return false;
		}

		// Relates a product to tags.
		// Replaces all existing related tags.
		// Creates tags if it doesn't exist (using the slug).
		$inserted_updated_tags = wp_set_object_terms( $id, $tags, 'product_tag' );

		if ( is_wp_error( $inserted_updated_tags ) ) {

			self::log_wp_error( $inserted_updated_tags, 'Error while updating product tags' );

			return false;
		}

		return true;
	}

	/**
	 * Insert or update product category or product image.
	 *
	 * Return true on successful, false on failure.
	 *
	 * @param $id
	 * @param $thumb_desc
	 * @param $thumb_urls
	 * @param string $type
	 *
	 * @return true|false|void
	 */
	private static function insert_update_thumb( $id, $thumb_desc, $thumb_urls, $type = 'product_cat' ) {

		global $skyweb_wc_iiko_logs;

		if ( 'no' === get_option( 'skyweb_wc_iiko_download_images' ) ) {
			return;
		}

		$type_message = 'product_cat' === $type ? 'product category' : ( 'product' === $type ? 'product' : 'undefined' );

		if ( ! is_array( $thumb_urls ) || empty( $thumb_urls ) ) {
			$skyweb_wc_iiko_logs->add_error( "There is no image for $type_message $thumb_desc" );

			return false;
		}

		$first_thumb = esc_url( array_shift( $thumb_urls ) );

		// Load media_sideload_image function and dependencies in front for CRON jobs.
		if ( ! is_admin() ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		/*
		 * Add main thumb.
		 */
		// Downloads the image from the specified URL, saves it as an attachment.
		$thumb_id = media_sideload_image( $first_thumb, $id, $thumb_desc, 'id' );

		// Return WP_Error when fail to insert/update product thumbnail.
		if ( is_wp_error( $thumb_id ) ) {

			self::log_wp_error( $thumb_id, "Error while insert/update $type_message attachment for '$thumb_desc'" );

			return false;

		} else {

			if ( 'product_cat' === $type ) {

				$related_thumb = add_term_meta( $id, 'thumbnail_id', $thumb_id, true );

				if ( is_wp_error( $related_thumb ) ) {
					self::log_wp_error( $related_thumb, "Error while relate $type_message thumbnail for '$thumb_desc'" );

					return false;
				}

				if ( false === $related_thumb ) {
					$skyweb_wc_iiko_logs->add_error( "Error while relate $type_message thumbnail for '$thumb_desc'." );

					return false;
				}
			}

			if ( 'product' === $type ) {

				$related_thumb = set_post_thumbnail( $id, $thumb_id );

				if ( false === $related_thumb ) {
					$skyweb_wc_iiko_logs->add_error( "Error while relate $type_message thumbnail for '$thumb_desc'." );

					return false;
				}

				/*
				 * Add product gallery.
				 */
				if ( ! empty( $thumb_urls ) ) {

					$thumb_urls = array_map( function ( $value ) {
						return esc_url( $value );
					}, $thumb_urls );

					foreach ( $thumb_urls as $thumb_url ) {

						$thumb_id = media_sideload_image( $thumb_url, $id, $thumb_desc, 'id' );

						if ( is_wp_error( $thumb_id ) ) {

							self::log_wp_error( $thumb_id, "Error while insert/update $type_message attachment for '$thumb_desc'" );

							continue;
						}

						$thumb_ids[] = $thumb_id;
					}

					$related_thumb = update_post_meta( $id, '_product_image_gallery', implode( ',', $thumb_ids ) );

					if ( false === $related_thumb ) {
						$skyweb_wc_iiko_logs->add_error( "Error while relate $type_message image gallery for '$thumb_desc'." );

						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Insert or update product category or product SEO data.
	 *
	 * Return nothing.
	 * Work only with Yoast SEO plugin.
	 * $seo_title and $seo_desc should be already sanitized.
	 *
	 * @param $id
	 * @param $seo_desc
	 * @param $seo_title
	 * @param string $type
	 */
	private static function insert_update_seo_data( $id, $seo_title, $seo_desc, $type = 'product_cat' ) {

		if ( 'product' === $type ) {

			if ( ! empty( $seo_title ) ) {
				update_post_meta( $id, '_yoast_wpseo_title', $seo_title );
			}

			if ( ! empty( $seo_desc ) ) {
				update_post_meta( $id, '_yoast_wpseo_metadesc', $seo_desc );
			}
		}

		if ( 'product_cat' === $type ) {

			if ( ! empty( $seo_title ) ) {
				$meta_values['wpseo_title'] = $seo_title;
			}

			if ( ! empty( $seo_desc ) ) {
				$meta_values['wpseo_desc'] = $seo_desc;
			}

			if ( ! empty( $meta_values ) ) {
				WPSEO_Taxonomy_Meta::set_values( $id, 'product_cat', $meta_values );
			}
		}
	}

	/**
	 * Search product attributes names in all attributes (sizes and modifiers).
	 *
	 * @param array $product_attrs_ids
	 * @param $all_attrs
	 *
	 * @return boolean|array
	 */
	private static function search_current_product_attrs_names( $product_attrs_ids, $all_attrs ) {

		if ( ! is_array( $product_attrs_ids ) || empty( $product_attrs_ids ) ) {
			return false;
		}

		$product_attrs_names = array();

		foreach ( $product_attrs_ids as $product_attrs_id ) {
			$product_attrs_names[] = $all_attrs[ $product_attrs_id ];
		}

		if ( ! empty( $product_attrs_names ) ) {
			return array_column( $product_attrs_names, 'name' );
		} else {
			return false;
		}
	}
}