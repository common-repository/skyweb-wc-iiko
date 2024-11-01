<?php

namespace SkyWeb\WC_Iiko\API_Requests;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\Import;

class Import_API_Requests extends Common_API_Requests {

	/**
	 * Import groups (product categories) to WooCommerce.
	 *
	 * TODO - rewrite.
	 *
	 * @return array
	 */
	protected function import_groups( $param_groups ) {

		$chosen_groups    = array();
		$processed_groups = array();

		// Get groups from cache.
		$groups = $this->get_cache( 'skyweb_wc_iiko_groups', 'groups' );

		// TODO - return errors on bad checking.
		$this->check_array( $groups, 'Groups array is empty.' );

		$chosen_groups_iiko_ids = $this->check_groups( array_values( $param_groups ) );

		if ( false === $chosen_groups_iiko_ids ) {
			$chosen_groups_iiko_ids = array_values( get_option( 'skyweb_wc_iiko_chosen_groups' ) );
		}

		// [ (int) index => (string) 'iiko_group_id', ... ]
		$groups_iiko_ids = array_column( $groups, 'id' );

		foreach ( $chosen_groups_iiko_ids as $chosen_groups_iiko_id ) {

			$chosen_group = $groups[ array_search( $chosen_groups_iiko_id, $groups_iiko_ids ) ];

			if ( is_array( $chosen_group ) && ! empty( $chosen_group ) ) {

				$chosen_groups[ sanitize_text_field( $chosen_group['name'] ) ] = sanitize_key( $chosen_group['id'] );

				$imported_product_cat = Import::insert_update_product_cat( $chosen_group );

				if ( false !== $imported_product_cat ) {
					// [ (int) term_id => (string) 'iiko_group_id', ... ]
					$processed_groups[ $imported_product_cat['term_id'] ] = $imported_product_cat['iiko_id'];
				}
			}
		}

		// Update chosen groups in the plugin settings if we called the method with parameters (groups) and they are correct.
		if ( ! empty( $param_groups ) && false !== $chosen_groups_iiko_ids ) {

			delete_option( 'skyweb_wc_iiko_chosen_groups' );

			if ( ! update_option( 'skyweb_wc_iiko_chosen_groups', $chosen_groups ) ) {
				$this->logs->add_error( 'Cannot add chosen groups to the plugin settings.' );
			}
		}

		return $processed_groups;
	}

	/**
	 * Import products to WooCommerce.
	 *
	 * @return int
	 */
	protected function import_products( $processed_groups ) {

		// Get other nomenclature info from cache.
		$simple_groups = $this->get_cache( 'skyweb_wc_iiko_simple_groups', 'groups list' );
		$dishes        = $this->get_cache( 'skyweb_wc_iiko_dishes', 'dishes' );
		$goods         = $this->get_cache( 'skyweb_wc_iiko_goods', 'goods' );
		$modifiers     = $this->get_cache( 'skyweb_wc_iiko_modifiers', 'modifiers' );
		$sizes         = $this->get_cache( 'skyweb_wc_iiko_sizes', 'sizes' );

		// Check nomenclature.
		$simple_groups = is_array( $simple_groups ) && ! empty( $simple_groups ) ? $simple_groups : array();
		$dishes        = is_array( $dishes ) && ! empty( $dishes ) ? $dishes : array();
		$goods         = is_array( $goods ) && ! empty( $goods ) ? $goods : array();
		// Change keys onto iiko IDs and remove doubled IDs.
		$modifiers = is_array( $modifiers ) && ! empty( $modifiers ) ? array_column( $modifiers, null, 'id' ) : array();
		$sizes     = is_array( $sizes ) && ! empty( $sizes ) ? array_column( $sizes, null, 'id' ) : array();

		// Import only dishes and goods.
		$products = array_merge( $dishes, $goods );

		// TODO - return errors on bad checking.
		$this->check_array( $products, 'Products array is empty.' );

		// Successful processed products.
		$processed_products = 0;

		// [ (string) 'iiko_product_id' => (string) 'iiko_group_id', ... ]
		$product_group_iiko_ids = array_column( $products, 'parentGroup', 'id' );
		// [ (string) 'iiko_product_id' => (array), ... ]
		$products_reindexed_iiko_ids = array_column( $products, null, 'id' );

		// Find related to the group products and added it to WooCommerce.
		foreach ( $processed_groups as $product_cat_term_id => $product_cat_iiko_id ) {

			// [ (int) index => (string) 'iiko_product_id', ... ]
			$product_cat_related_products_ids = array_keys( $product_group_iiko_ids, $product_cat_iiko_id );

			foreach ( $product_cat_related_products_ids as $product_cat_related_product_id ) {

				$related_product = $products_reindexed_iiko_ids[ $product_cat_related_product_id ];

				$imported_product = Import::insert_update_product( $related_product, $product_cat_term_id, $modifiers, $sizes, $simple_groups );

				if ( false !== $imported_product ) {
					$processed_products ++;
				}
			}
		}

		return $processed_products;
	}

	/**
	 * Import nomenclature (groups and products) to WooCommerce.
	 *
	 * @return array
	 */
	public function import_nomenclature( $param_groups = array() ) {

		// Import iiko groups (WC product categories).
		$processed_groups = $this->import_groups( $param_groups );

		// TODO - return errors on bad checking.
		$this->check_array( $processed_groups, 'No imported groups.' );

		// Import iiko dishes and goods (WC products).
		$processed_products = $this->import_products( $processed_groups );

		return array(
			'importedGroups'   => count( $processed_groups ),
			'importedProducts' => $processed_products,
		);
	}
}