<?php

namespace SkyWeb\WC_Iiko\API_Requests;

defined( 'ABSPATH' ) || exit;

class AJAX_API_Requests extends Import_API_Requests {

	/**
	 * Get organizations from iiko by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function get_organizations_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$organizations = $this->get_organizations();

		$this->check_response_for_ajax( $organizations, 'Organizations' );

		if ( is_array( $organizations ) && isset( $organizations['organizations'] ) && ! empty( $organizations['organizations'] ) ) {
			echo wp_json_encode( $organizations );

		} else {
			$this->logs->add_error( 'Response does not contain organizations.' );
			$this->echo_error_logs();
		}

		wp_die();
	}

	/**
	 * Get terminals from iiko by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function get_terminals_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$organization_id = $this->check_id( $_POST['organizationId'], 'Organization' );

		$this->check_parameter_for_ajax( $organization_id, 'Organization ID' );

		$terminals = $this->get_terminals( $organization_id );

		$this->check_response_for_ajax( $terminals, 'Terminals' );

		if ( is_array( $terminals ) && isset( $terminals['terminalGroups'] ) && ! empty( $terminals['terminalGroups'] ) ) {
			echo wp_json_encode( $terminals );

		} else {
			$this->logs->add_error( 'Organization does not have terminals.' );
			$this->echo_error_logs();
		}

		wp_die();
	}

	/**
	 * Get nomenclature from iiko by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function get_nomenclature_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$organization_id   = $this->check_id( $_POST['organizationId'], 'Organization' );
		$terminal_id       = $this->check_id( $_POST['terminalId'], 'Terminal', false );
		$organization_name = $this->check_name( $_POST['organizationName'], 'Organization', false );
		$terminal_name     = $this->check_name( $_POST['terminalName'], 'Terminal', false );

		$this->check_parameter_for_ajax( $organization_id, 'Organization ID' );
		$this->check_parameter_for_ajax( $terminal_id, 'Terminal ID', false );

		$nomenclature = $this->get_nomenclature( $organization_id, $terminal_id, $organization_name, $terminal_name );

		$this->check_response_for_ajax( $nomenclature, 'Nomenclature' );

		echo wp_json_encode( $nomenclature );

		wp_die();
	}

	/**
	 * Get cities from iiko by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function get_cities_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$organization_id = $this->check_id( $_POST['organizationId'], 'Organization' );

		$this->check_parameter_for_ajax( $organization_id, 'Organization ID' );

		$cities = $this->get_cities( $organization_id );

		$this->check_response_for_ajax( $cities, 'Cities' );

		echo wp_json_encode( array( 'cities' => $cities ) );

		wp_die();
	}

	/**
	 * Get streets from iiko by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function get_streets_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$organization_id = $this->check_id( $_POST['organizationId'], 'Organization' );
		$city_id         = $this->check_id( $_POST['cityId'], 'City' );
		$city_name       = $this->check_name( $_POST['cityName'], 'City', false );

		$this->check_parameter_for_ajax( $organization_id, 'Organization ID' );
		$this->check_parameter_for_ajax( $city_id, 'City ID' );

		$streets = $this->get_streets( $organization_id, $city_id, $city_name );

		$this->check_response_for_ajax( $streets, 'Streets' );

		echo wp_json_encode( array( 'streets' => $streets ) );

		wp_die();
	}

	/**
	 * Import nomenclature (groups and products) to WooCommerce by AJAX request.
	 *
	 * Echo JSON.
	 */
	public function import_nomenclature_ajax() {

		check_ajax_referer( 'skyweb_wc_iiko_import_action', 'skyweb_wc_iiko_import_nonce' );

		$response = $this->import_nomenclature( $_POST['iikoChosenGroups'] );

		echo wp_json_encode( $this->logs->return_logs( $response ) );

		wp_die();
	}
}