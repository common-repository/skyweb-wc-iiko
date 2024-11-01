<?php

namespace SkyWeb\WC_Iiko\Admin;

defined( 'ABSPATH' ) || exit;

use WC_Admin_Settings;
use WC_Settings_Page;

class Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'skyweb_wc_iiko_settings';
		$this->label = __( 'iikoCloud', 'skyweb-wc-iiko' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = array(
			''       => __( 'General', 'skyweb-wc-iiko' ),
			'import' => __( 'Import', 'skyweb-wc-iiko' ),
			'export' => __( 'Export', 'skyweb-wc-iiko' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings.
	 */
	public function output() {

		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {

		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}

	/**
	 * Prepare groups options for select.
	 */
	protected function prepare_groups_options() {

		$chosen_groups = get_option( 'skyweb_wc_iiko_chosen_groups' );

		if ( is_array( $chosen_groups ) && ! empty( $chosen_groups ) ) {
			return array_flip( $chosen_groups );
		}

		return array();
	}

	/**
	 * Get option ID in order to show in the option description.
	 * Special for organization, terminal and city.
	 */
	protected function get_option_id( $option_id ) {

		$option_id = get_option( $option_id );

		if ( empty( $option_id ) ) {
			return 'NOT SET';
		}

		return sanitize_key( $option_id );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		// Import section.
		if ( 'import' === $current_section ) {
			$settings = apply_filters(
				'skyweb_wc_iiko_import_settings',
				array(
					array(
						'title' => __( 'Import', 'skyweb-wc-iiko' ),
						'desc'  => sprintf(
							__( 'Some of these settings are updated automatically on %splugin page%s.', 'skyweb-wc-iiko' ),
							'<a href="' . admin_url( 'admin.php?page=skyweb_wc_iiko' ) . '" target="_blank">',
							'</a>'
						),
						'id'    => 'skyweb_wc_iiko_import_options',
						'type'  => 'title',
					),

					array(
						'title'             => __( 'Organization', 'skyweb-wc-iiko' ),
						'desc'              => sprintf(
							__( 'Organization ID - %s%s%s', 'skyweb-wc-iiko' ),
							'<code>',
							$this->get_option_id( 'skyweb_wc_iiko_organization_id' ),
							'</code>'
						),
						'id'                => 'skyweb_wc_iiko_organization_name',
						'type'              => 'text',
						'css'               => 'width: 300px;',
						'autoload'          => false,
						'desc_tip'          => __( "Updated automatically when you press 'Get Nomenclature' button.", 'skyweb-wc-iiko' ),
						'custom_attributes' => array(
							'disabled' => 'disabled',
						),
					),

					array(
						'title'             => __( 'Terminal', 'skyweb-wc-iiko' ),
						'desc'              => sprintf(
							__( 'Terminal ID - %s%s%s', 'skyweb-wc-iiko' ),
							'<code>',
							$this->get_option_id( 'skyweb_wc_iiko_terminal_id' ),
							'</code>'
						),
						'id'                => 'skyweb_wc_iiko_terminal_name',
						'type'              => 'text',
						'css'               => 'width: 300px;',
						'autoload'          => false,
						'desc_tip'          => __( "Updated automatically when you press 'Get Nomenclature' button.", 'skyweb-wc-iiko' ),
						'custom_attributes' => array(
							'disabled' => 'disabled',
						),
					),

					array(
						'title'             => __( 'Nomenclature revision', 'skyweb-wc-iiko' ),
						'id'                => 'skyweb_wc_iiko_nomenclature_revision',
						'type'              => 'text',
						'css'               => 'width: 300px;',
						'desc_tip'          => __( "Updated automatically when you press 'Get Nomenclature' button.", 'skyweb-wc-iiko' ),
						'custom_attributes' => array(
							'disabled' => 'disabled',
						),
					),

					array(
						'title'             => __( 'Chosen group(s)', 'skyweb-wc-iiko' ),
						'desc'              => __( 'Groups that you imported last time and which will be exported using CRON.', 'skyweb-wc-iiko' ),
						'id'                => 'skyweb_wc_iiko_chosen_groups_to_show',
						'default'           => '',
						'type'              => 'select',
						'desc_tip'          => true,
						'custom_attributes' => array(
							'multiple' => 'multiple',
							'size'     => '10',
							'disabled' => 'disabled',
						),
						'options'           => $this->prepare_groups_options(),
					),

					array(
						'title'   => __( 'Download images', 'skyweb-wc-iiko' ),
						'desc'    => __( 'Download groups/categories and dishes/products images from iiko', 'skyweb-wc-iiko' ),
						'id'      => 'skyweb_wc_iiko_download_images',
						'type'    => 'checkbox',
						'default' => 'no',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_import_options',
					),
				)
			);

			// Export section.
		} elseif ( 'export' === $current_section ) {
			$settings = apply_filters(
				'skyweb_wc_iiko_export_settings',
				array(
					array(
						'title' => __( 'Export', 'skyweb-wc-iiko' ),
						'type'  => 'title',
						'desc'  => sprintf(
							__( 'Some of these settings are updated automatically on %splugin page%s.', 'skyweb-wc-iiko' ),
							'<a href="' . admin_url( 'admin.php?page=skyweb_wc_iiko' ) . '" target="_blank">',
							'</a>'
						),
						'id'    => 'skyweb_wc_iiko_export_options',
					),

					array(
						'title'             => __( 'City', 'skyweb-wc-iiko' ),
						'desc'              => sprintf(
							__( 'City ID - %s%s%s', 'skyweb-wc-iiko' ),
							'<code>',
							$this->get_option_id( 'skyweb_wc_iiko_city_id' ),
							'</code>'
						),
						'id'                => 'skyweb_wc_iiko_city_name',
						'type'              => 'text',
						'css'               => 'width: 300px;',
						'desc_tip'          => __( "Updated automatically when you press 'Get Streets' button.", 'skyweb-wc-iiko' ),
						'custom_attributes' => array(
							'disabled' => 'disabled',
						),
					),

					array(
						'title'             => __( 'Streets amount', 'skyweb-wc-iiko' ),
						'desc'              => __( 'Streets amount retrieved from iiko.', 'skyweb-wc-iiko' ),
						'id'                => 'skyweb_wc_iiko_streets_amount',
						'type'              => 'number',
						'css'               => 'width: 100px;',
						'autoload'          => false,
						'desc_tip'          => __( "Updated automatically when you press 'Get Streets' button.", 'skyweb-wc-iiko' ),
						'custom_attributes' => array(
							'disabled' => 'disabled',
						),
					),

					array(
						'title' => __( 'Default street', 'skyweb-wc-iiko' ),
						'desc'  => __( 'The exact name of the default street in iiko.', 'skyweb-wc-iiko' ),
						'id'    => 'skyweb_wc_iiko_default_street',
						'type'  => 'text',
						'css'   => 'width: 300px;',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_export_options',
					),
				)
			);

			// General section.
		} else {
			$settings = apply_filters(
				'skyweb_wc_iiko_general_settings',
				array(
					array(
						'title' => __( 'IikoCloud API Settings', 'skyweb-wc-iiko' ),
						'type'  => 'title',
						'id'    => 'skyweb_wc_iiko_general_options',
					),

					array(
						'title'    => __( 'API Login', 'skyweb-wc-iiko' ),
						'desc'     => sprintf(
							__(
								'Unique identifier of the store, issued by the iiko. %sSee documentation%s or contact your personal manager for more details.',
								'skyweb-wc-iiko'
							),
							'<a href="https://ru.iiko.help/articles/#!api-documentations/connect-to-iiko-cloud" target="_blank">',
							'</a>'
						),
						'id'       => 'skyweb_wc_iiko_apiLogin',
						'type'     => 'text',
						'css'      => 'width: 100px;',
						'desc_tip' => __( 'If the apiLogin field is empty, then the plugin will not work.', 'skyweb-wc-iiko' ),
					),

					array(
						'title'             => __( 'Timeout in seconds', 'skyweb-wc-iiko' ),
						'desc'              => __( 'Time limit for API requests.', 'skyweb-wc-iiko' ),
						'id'                => 'skyweb_wc_iiko_timeout',
						'css'               => 'width: 50px;',
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => 1,
							'step' => 1,
						),
						'default'           => '10',
						'desc_tip'          => 'Default iikoCloud API value is 15 second.',
					),

					array(
						'title'    => __( 'Debug mode', 'skyweb-wc-iiko' ),
						'desc'     => __( 'Turn on debug mode', 'skyweb-wc-iiko' ),
						'desc_tip' => sprintf(
							__( 'See logs in %sWooCommerce > System Status > Logs%s.', 'skyweb-wc-iiko' ),
							'<a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '" target="_blank">',
							'</a>'
						),
						'id'       => 'skyweb_wc_iiko_debug_mode',
						'type'     => 'checkbox',
						'default'  => 'no',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'skyweb_wc_iiko_general_options',
					),
				)
			);
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}
}
