<?php

namespace SkyWeb\WC_Iiko\Admin;

defined( 'ABSPATH' ) || exit;

class MetaFields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Product categories.
		// add_action( 'product_cat_add_form_fields', array( $this, 'product_cat_add_iiko_id' ), 10, 1 ); // Dynamic hook {post-type}_add_form_fields
		add_action( 'product_cat_edit_form_fields', array( $this, 'product_cat_edit_iiko_id' ), 10, 1 ); // Dynamic hook {post-type}_edit_form_fields
		// add_action( 'edited_product_cat', array( $this, 'product_cat_save_iiko_id' ), 10, 1 );
		// add_action( 'create_product_cat', array( $this, 'product_cat_save_iiko_id' ), 10, 1 );
		add_filter( 'manage_edit-product_cat_columns', array( $this, 'product_cat_iiko_id_list_title' ) );
		add_action( 'manage_product_cat_custom_column', array( $this, 'product_cat_iiko_id_list_column' ), 10, 3 );
		// add_filter( 'manage_edit-product_cat_sortable_columns', array( $this, 'product_cat_iiko_id_list_col_sort' ) );
		// Products.
		add_action( 'woocommerce_product_options_sku', array( $this, 'product_add_iiko_id' ) );
		// add_action( 'woocommerce_process_product_meta', array( $this, 'product_save_iiko_id' ) );
		add_filter( 'manage_edit-product_columns', array( $this, 'product_iiko_id_list_title' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'product_iiko_id_list_column' ), 10, 2 ); // Dynamic hook manage_{$post->post_type}_posts_custom_column
		// add_filter( "manage_edit-product_sortable_columns", array( $this, 'product_iiko_id_list_col_sort' ) );
	}

	/**
	 * iiko ID shorter.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function iiko_id_shorter( $id ) {

		$num_chars = 9;

		if ( ! empty( $id ) ) {

			$postfix = strlen( $id ) > $num_chars ? '...' : '';
			$id      = mb_substr( $id, 0, $num_chars ) . $postfix;

		} else {
			$id = '–';
		}

		return $id;
	}

	/**
	 * Display iiko group ID field on product category create page.
	 */
	public function product_cat_add_iiko_id() {

		ob_start();
		?>

        <label for="skyweb_wc_iiko_group_id">
			<?php esc_html_e( 'iiko group ID', 'skyweb-wc-iiko' ); ?>
        </label>

        <input type="text" name="skyweb_wc_iiko_group_id" id="skyweb_wc_iiko_group_id" readonly/>

        <p class="description">
			<?php esc_html_e( 'Uniqe iiko group ID for the product category.', 'skyweb-wc-iiko' ); ?>
        </p>

		<?php
		$html = ob_get_contents();
		ob_end_clean();

		$wrapper = '<div class="form-field">%s</div>';

		$html = sprintf( $wrapper, $html );

		echo $html;
	}

	/**
	 * Display iiko group ID field on product category edit page.
	 */
	public function product_cat_edit_iiko_id( $term ) {

		$term_id       = $term->term_id;
		$iiko_group_id = sanitize_key( get_term_meta( $term_id, 'skyweb_wc_iiko_group_id', true ) );

		ob_start();
		?>

        <th scope="row">
            <label for="skyweb_wc_iiko_group_id">
				<?php esc_html_e( 'iiko group ID', 'skyweb-wc-iiko' ); ?>
            </label>
        </th>

        <td>
            <input type="text" name="skyweb_wc_iiko_group_id" id="skyweb_wc_iiko_group_id"
                   value="<?php echo $iiko_group_id ? esc_attr( $iiko_group_id ) : ''; ?>" readonly/>

            <p class="description">
				<?php esc_html_e( 'Uniqe iiko group ID for the product category.', 'skyweb-wc-iiko' ); ?>
            </p>
        </td>


		<?php
		$html = ob_get_contents();
		ob_end_clean();

		$wrapper = '<tr class="form-field"><div class="form-field">%s</tr>';

		$html = sprintf( $wrapper, $html );

		echo $html;
	}

	/**
	 * Save iiko group ID for a WooCommerce product category.
	 */
	public function product_cat_save_iiko_id( $term_id ) {

		$iiko_group_id = sanitize_key( filter_input( INPUT_POST, 'skyweb_wc_iiko_group_id' ) );

		if ( ! empty( $iiko_group_id ) ) {
			update_term_meta( $term_id, 'skyweb_wc_iiko_group_id', $iiko_group_id );
		}
	}

	/**
	 * Display iiko group ID in product categories list.
	 */
	// Title.
	public function product_cat_iiko_id_list_title( $columns ) {

		$columns['skyweb_wc_iiko_group_id'] = ( is_array( $columns ) ) ? esc_html__( 'iiko ID', 'skyweb-wc-iiko' ) : array();

		return $columns;
	}

	// Column.
	public function product_cat_iiko_id_list_column( $columns, $column, $id ) {

		if ( 'skyweb_wc_iiko_group_id' === $column ) {
			$iiko_group_id = sanitize_key( get_term_meta( $id, 'skyweb_wc_iiko_group_id', true ) );
			$columns       = $this->iiko_id_shorter( $iiko_group_id );
		}

		return $columns;
	}

	// Sortable.
	public function product_cat_iiko_id_list_col_sort( $columns ) {

		if ( ! empty( $columns ) ) {

			$custom = array(
				'skyweb_wc_iiko_group_id' => 'skyweb_wc_iiko_group_id'
			);

			return wp_parse_args( $custom, $columns );
		}
	}

	/**
	 * Display iiko product ID field on product page.
	 */
	public function product_add_iiko_id() {

		woocommerce_wp_text_input(
			array(
				'id'                => 'skyweb_wc_iiko_product_id',
				'name'              => 'skyweb_wc_iiko_product_id',
				'label'             => esc_html__( 'iiko product ID', 'skyweb-wc-iiko' ),
				'description'       => esc_html__( 'Uniqe iiko product ID.', 'skyweb-wc-iiko' ),
				'desc_tip'          => 'true',
				'class'             => 'product_iiko_id',
				'custom_attributes' => array( 'readonly' => 'readonly' )
			)
		);
	}

	/**
	 * Save iiko product ID for a WooCommerce product.
	 */
	public function product_save_iiko_id( $post_id ) {

		$iiko_product_id = sanitize_key( filter_input( INPUT_POST, 'skyweb_wc_iiko_product_id' ) );

		if ( ! empty( $iiko_product_id ) ) {
			update_post_meta( $post_id, 'skyweb_wc_iiko_product_id', $iiko_product_id );
		}
	}

	/**
	 * Display iiko product ID in products list.
	 */
	// Title.
	public function product_iiko_id_list_title( $columns ) {

		$columns['skyweb_wc_iiko_product_id'] = ( is_array( $columns ) ) ? esc_html__( 'iiko ID', 'skyweb-wc-iiko' ) : array();

		return $columns;
	}

	// Column.
	public function product_iiko_id_list_column( $column_name, $post_id ) {

		if ( 'skyweb_wc_iiko_product_id' === $column_name ) {
			$iiko_product_id = sanitize_key( get_post_meta( $post_id, 'skyweb_wc_iiko_product_id', true ) );
			echo $this->iiko_id_shorter( $iiko_product_id );
		}
	}

	// Sortable.
	public function product_iiko_id_list_col_sort( $columns ) {

		if ( ! empty( $columns ) ) {

			$custom = array(
				'skyweb_wc_iiko_product_id' => 'skyweb_wc_iiko_product_id'
			);

			return wp_parse_args( $custom, $columns );
		}
	}
}