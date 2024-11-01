<?php

namespace SkyWeb\WC_Iiko\API_Requests;

defined( 'ABSPATH' ) || exit;

use SkyWeb\WC_Iiko\HTTP_Request;
use SkyWeb\WC_Iiko\Logs;

class Common_API_Requests {

	protected $logs;
	protected $api_login;
	protected $organization_id;
	protected $terminal_id;
	protected $city_id;

	/**
	 * Constructor.
	 */
	public function __construct() {

		global $skyweb_wc_iiko_logs;

		$this->logs            = &$skyweb_wc_iiko_logs;
		$this->api_login       = sanitize_key( get_option( 'skyweb_wc_iiko_apiLogin' ) );
		$this->organization_id = sanitize_key( get_option( 'skyweb_wc_iiko_organization_id' ) );
		$this->terminal_id     = sanitize_key( get_option( 'skyweb_wc_iiko_terminal_id' ) );
		$this->city_id         = sanitize_key( get_option( 'skyweb_wc_iiko_city_id' ) );
	}

	/**
	 * Echo error logs.
	 */
	protected function echo_error_logs() {
		echo wp_json_encode( $this->logs->return_logs() );
	}

	/**
	 * Check remote response for AJAX request.
	 */
	protected function check_response_for_ajax( $response, $title ) {

		if ( false === $response ) {
			$this->logs->add_error( "$title request failed." );
			$this->echo_error_logs();

			wp_die();
		}
	}

	/**
	 * Check AJAX parameter.
	 */
	protected function check_parameter_for_ajax( $parameter, $title, $required = true ) {

		if ( empty( $parameter ) and $required ) {
			$this->logs->add_error( "$title is empty." );
			$this->echo_error_logs();

			wp_die();
		}
	}

	/**
	 * Check organization ID.
	 */
	protected function check_id( $id, $title, $required = true ) {

		$id = sanitize_key( $id );

		if ( empty( $id ) && $required ) {
			$this->logs->add_error( "$title ID is empty." );

			return false;
		}

		return $id;
	}

	/**
	 * Check name.
	 */
	protected function check_name( $name, $title, $required = true ) {

		$name = sanitize_text_field( $name );

		if ( empty( $name ) && $required ) {
			$this->logs->add_error( "$title name is empty." );

			return false;
		}

		return $name;
	}

	/**
	 * Check array.
	 */
	protected function check_array( $array, $title ) {

		if ( ! is_array( $array ) || empty( $array ) ) {
			$this->logs->add_error( $title );
			Logs::add_wc_error_log( $title );

			return false;
		}

		return true;
	}

	/**
	 * Check object.
	 */
	protected function check_object( $object, $title ) {

		if ( ! is_object( $object ) || empty( $object ) ) {
			$this->logs->add_error( $title );
			Logs::add_wc_error_log( $title );

			return false;
		}

		return true;
	}

	/**
	 * Check groups.
	 */
	protected function check_groups( $groups ) {

		if ( ! is_array( $groups ) || empty( $groups ) ) {
			$this->logs->add_error( 'Chose groups to import.' );

			return false;
		}

		return array_map( function ( $group_id ) {
			return sanitize_key( $group_id );
		}, $groups );
	}

	/**
	 * Get and check transient.
	 */
	protected function get_cache( $transient, $title ) {

		$transient = get_transient( $transient );

		if ( false === $transient ) {
			$this->logs->add_error( "Error while getting iiko $title from the cache. Get the nomenclature one more time." );

			return false;
		}

		return $transient;
	}

	/**
	 * Update chosen organization/terminal ID and name in the plugin settings.
	 */
	protected function update_organization_terminal_settings( $organization_id, $terminal_id, $organization_name, $terminal_name ) {

		delete_option( 'skyweb_wc_iiko_organization_id' );
		delete_option( 'skyweb_wc_iiko_terminal_id' );
		delete_option( 'skyweb_wc_iiko_organization_name' );
		delete_option( 'skyweb_wc_iiko_terminal_name' );

		if ( ! update_option( 'skyweb_wc_iiko_organization_id', $organization_id ) ) {
			$this->logs->add_error( 'Cannot add organization ID to the plugin settings.' );

			return false;
		}

		if ( ! update_option( 'skyweb_wc_iiko_terminal_id', $terminal_id ) ) {
			$this->logs->add_error( 'Cannot add terminal ID to the plugin settings.' );

			return false;
		}

		if ( ! update_option( 'skyweb_wc_iiko_organization_name', $organization_name ) ) {
			$this->logs->add_error( 'Cannot add organization name to the plugin settings.' );
		}

		if ( ! update_option( 'skyweb_wc_iiko_terminal_name', $terminal_name ) ) {
			$this->logs->add_error( 'Cannot add terminal name to the plugin settings.' );
		}

		return true;
	}

	/**
	 * Update nomenclature revision in the plugin settings.
	 */
	protected function update_nomenclature_revision_setting( $nomenclature_revision ) {

		delete_option( 'skyweb_wc_iiko_nomenclature_revision' );

		if ( ! update_option( 'skyweb_wc_iiko_nomenclature_revision', $nomenclature_revision ) ) {
			$this->logs->add_error( 'Cannot add nomenclature revision to the plugin settings.' );
		}
	}

	/**
	 * Update chosen city ID and streets in the plugin settings.
	 */
	protected function update_city_streets_setting( $city_name, $city_id, $streets ) {

		delete_option( 'skyweb_wc_iiko_city_id' );
		delete_option( 'skyweb_wc_iiko_city_name' );
		delete_option( 'skyweb_wc_iiko_streets' );
		delete_option( 'skyweb_wc_iiko_streets_amount' );

		if ( ! update_option( 'skyweb_wc_iiko_city_id', $city_id ) ) {
			$this->logs->add_error( 'Cannot add city ID to the plugin settings.' );

			return false;
		}

		if ( ! update_option( 'skyweb_wc_iiko_city_name', $city_name ) ) {
			$this->logs->add_error( 'Cannot add city name to the plugin settings.' );
		}

		if ( ! update_option( 'skyweb_wc_iiko_streets', $streets ) ) {
			$this->logs->add_error( 'Cannot add streets amount to the plugin settings.' );

			return false;
		}

		if ( ! update_option( 'skyweb_wc_iiko_streets_amount', count( $streets ) ) ) {
			$this->logs->add_error( 'Cannot add streets amount to the plugin settings.' );
		}

		return true;
	}

	/**
	 * Update nomenclature cache (transients).
	 */
	protected function update_nomenclature_cache( $nomenclature, $nomenclature_dishes, $nomenclature_goods, $nomenclature_modifiers ) {

		// Delete old nomenclature cache.
		delete_transient( 'skyweb_wc_iiko_groups' );
		delete_transient( 'skyweb_wc_iiko_simple_groups' );
		delete_transient( 'skyweb_wc_iiko_dishes' );
		delete_transient( 'skyweb_wc_iiko_goods' );
		delete_transient( 'skyweb_wc_iiko_modifiers' );
		delete_transient( 'skyweb_wc_iiko_sizes' );

		// Cache nomenclature for 10 minutes.
		$set_groups_transient = set_transient( 'skyweb_wc_iiko_groups', $nomenclature['groups'], 600 );
		set_transient( 'skyweb_wc_iiko_simple_groups', $nomenclature['simpleGroups'], 600 );
		set_transient( 'skyweb_wc_iiko_dishes', $nomenclature_dishes, 600 );
		set_transient( 'skyweb_wc_iiko_goods', $nomenclature_goods, 600 );
		set_transient( 'skyweb_wc_iiko_modifiers', $nomenclature_modifiers, 600 );
		set_transient( 'skyweb_wc_iiko_sizes', $nomenclature['sizes'], 600 );

		// Check nomenclature groups caching.
		if ( false === $set_groups_transient ) {
			$this->logs->add_error( 'Error while caching iiko nomenclature.' );

			return false;
		}

		return true;
	}

	/**
	 * Prepare nomenclature for the further actions.
	 */
	protected function prepare_nomenclature( $nomenclature ) {

		// Prepare groups tree for the frontend.
		$nomenclature['groupsTree'] = $this->categories_tree( $nomenclature['groups'] );

		// Separate products by types: Dishes, Goods, Modifiers.
		$nomenclature_dishes    = array();
		$nomenclature_goods     = array();
		$nomenclature_modifiers = array();

		if ( is_array( $nomenclature['products'] ) && ! empty( $nomenclature['products'] ) ) {

			foreach ( $nomenclature['products'] as $product ) {

				if ( 'Dish' === $product['type'] ) {
					$nomenclature_dishes[] = $product;
				}

				if ( 'Good' === $product['type'] ) {
					$nomenclature_goods[] = $product;
				}

				if ( 'Modifier' === $product['type'] ) {
					$nomenclature_modifiers[] = $product;
				}
			}
		}

		// Create simple arrays with only basic data for output on admin page and remove doubled IDs.
		$nomenclature['simpleGroups']    = array_column( $nomenclature['groups'], 'name', 'id' );
		$nomenclature['simpleDishes']    = array_column( $nomenclature_dishes, 'name', 'id' );
		$nomenclature['simpleGoods']     = array_column( $nomenclature_goods, 'name', 'id' );
		$nomenclature['simpleModifiers'] = array_column( $nomenclature_modifiers, 'name', 'id' );
		$nomenclature['simpleSizes']     = array_column( $nomenclature['sizes'], 'name', 'id' );

		// Save nomenclature cache.
		if ( false === $this->update_nomenclature_cache( $nomenclature, $nomenclature_dishes, $nomenclature_goods, $nomenclature_modifiers ) ) {
			return false;
		}

		// Save nomenclature revision.
		if ( ! empty( $nomenclature['revision'] ) ) {
			$this->update_nomenclature_revision_setting( $nomenclature['revision'] );
		}

		return $nomenclature;
	}

	/**
	 * Build categories (iiko groups) tree.
	 *
	 * @param $categories
	 *
	 * @return array
	 */
	protected function categories_tree( $categories ) {

		if ( ! is_array( $categories ) || empty( $categories ) ) {
			return array();
		}

		$child_groups = array();

		foreach ( $categories as &$category ) {

			if ( is_null( $category['parentGroup'] ) ) {
				$child_groups[0][] = &$category;

			} else {
				$child_groups[ $category['parentGroup'] ][] = &$category;
			}
		}

		unset( $category );

		foreach ( $categories as &$category ) {

			if ( isset( $child_groups[ $category['id'] ] ) ) {
				$category['childGroups'] = $child_groups[ $category['id'] ];

			} else {
				$category['childGroups'] = null;
			}
		}

		return $child_groups[0];
	}

	/**
	 * Get access token from iiko.
	 *
	 * Return token or false if there is an error.
	 *
	 * @return false|string
	 */
	protected function get_access_token() {

		if ( empty( $this->api_login ) ) {
			$this->logs->add_error( 'iiko API login is not set. Check the login API in plugin settings.' );

			return false;
		}

		$url     = 'access_token';
		$headers = array();
		$body    = array(
			'apiLogin' => $this->api_login
		);

		$token_response = HTTP_Request::remote_post( $url, $headers, $body );

		if ( false === $token_response ) {
			$this->logs->add_error( 'Token request failed. Check the login API in plugin settings.' );

			return false;
		}

		if ( is_array( $token_response ) && isset( $token_response['token'] ) && ! empty( $token_response['token'] ) ) {
			return 'Bearer ' . $token_response['token'];

		} else {
			$this->logs->add_error( 'Response does not contain token.' );
		}

		return false;
	}

	/**
	 * Get organizations from iiko.
	 *
	 * @return array|false
	 */
	public function get_organizations() {

		$access_token = $this->get_access_token();

		// Required data for remote post.
		if ( false === $access_token ) {
			return false;
		}

		$url     = 'organizations';
		$headers = array(
			'Authorization' => $access_token
		);
		$body    = array(
			'organizationIds'      => null,
			'returnAdditionalInfo' => false,
			'includeDisabled'      => false
		);

		return HTTP_Request::remote_post( $url, $headers, $body );
	}

	/**
	 * Get terminals from iiko.
	 *
	 * @return array|bool
	 */
	public function get_terminals( $organization_id = null ) {

		$access_token = $this->get_access_token();

		// TODO - move to method.
		// Take organization ID from settings if parameter is empty.
		if ( empty( $organization_id ) && ! empty( $this->organization_id ) ) {
			$organization_id = $this->organization_id;
		}

		// Required data for remote post.
		if ( false === $access_token || empty( $organization_id ) ) {
			return false;
		}

		$url     = 'terminal_groups';
		$headers = array(
			'Authorization' => $access_token
		);
		$body    = array(
			'organizationIds' => array( $organization_id ),
			'includeDisabled' => false
		);

		return HTTP_Request::remote_post( $url, $headers, $body );
	}

	/**
	 * Get nomenclature from iiko.
	 * Save organization, terminal and revision data to settings.
	 * Save nomenclature to transients.
	 *
	 * @return array|bool
	 */
	public function get_nomenclature( $organization_id = null, $terminal_id = null, $organization_name = null, $terminal_name = null ) {

		$access_token = $this->get_access_token();

		// Update organization and terminal if we're passing them.
		if ( ! empty( $organization_id ) && ! empty( $terminal_id ) && ! empty( $organization_name ) && ! empty( $terminal_name ) ) {
			$this->update_organization_terminal_settings( $organization_id, $terminal_id, $organization_name, $terminal_name );
		}

		// Take organization ID from settings if parameter is empty.
		if ( empty( $organization_id ) && ! empty( $this->organization_id ) ) {
			$organization_id = $this->organization_id;
		}

		// Required data for remote post.
		if ( false === $access_token || empty( $organization_id ) ) {
			return false;
		}

		$url     = 'nomenclature';
		$headers = array(
			'Authorization' => $access_token
		);
		$body    = array(
			'organizationId' => $organization_id,
			'startRevision'  => 0
		);

		$nomenclature = HTTP_Request::remote_post( $url, $headers, $body );

		if ( is_array( $nomenclature ) && isset( $nomenclature['groups'] ) && ! empty( $nomenclature['groups'] ) ) {

			$nomenclature = $this->prepare_nomenclature( $nomenclature );

		} else {
			$this->logs->add_error( 'Organization does not have nomenclature groups.' );

			return false;
		}

		return $nomenclature;
	}

	/**
	 * Get cities from iiko.
	 *
	 * @return array|bool
	 */
	public function get_cities( $organization_id = null ) {

		$access_token = $this->get_access_token();

		// Take organization ID from settings if parameter is empty.
		if ( empty( $organization_id ) && ! empty( $this->organization_id ) ) {
			$organization_id = $this->organization_id;
		}

		// Required data for remote post.
		if ( false === $access_token || empty( $organization_id ) ) {
			return false;
		}

		$url     = 'cities';
		$headers = array(
			'Authorization' => $access_token
		);
		$body    = array(
			'organizationIds' => array( $organization_id )
		);

		$cities = HTTP_Request::remote_post( $url, $headers, $body );

		if ( is_array( $cities ) && isset( $cities['cities'] ) && ! empty( $cities['cities'] ) ) {

			// Change keys onto organization ID and remove doubled IDs.
			$cities = array_column( $cities['cities'], null, 'organizationId' );

			// Create a simple array with only basic data for output on admin page and remove doubled IDs.
			$cities = array_column( $cities[ $organization_id ]['items'], 'name', 'id' );

		} else {
			$this->logs->add_error( 'Response does not contain cities.' );

			return false;
		}

		return $cities;
	}

	/**
	 * Get streets from iiko.
	 *
	 * @return array|bool
	 */
	public function get_streets( $organization_id = null, $city_id = null, $city_name = null ) {

		$access_token = $this->get_access_token();

		// Update city and streets if we're passing city.
		if ( ! empty( $city_id ) && ! empty( $city_name ) ) {
			$update_settings = true;
		}

		// Take organization ID from settings if parameter is empty.
		if ( empty( $organization_id ) && ! empty( $this->organization_id ) ) {
			$organization_id = $this->organization_id;
		}

		// Take city ID from settings if parameter is empty.
		if ( empty( $city_id ) && ! empty( $this->city_id ) ) {
			$city_id = $this->city_id;
		}

		// Required data for remote post.
		if ( false === $access_token || empty( $organization_id ) || empty( $city_id ) ) {
			return false;
		}

		$url     = 'streets/by_city';
		$headers = array(
			'Authorization' => $access_token
		);
		$body    = array(
			'organizationId' => $organization_id,
			'cityId'         => $city_id
		);

		$streets = HTTP_Request::remote_post( $url, $headers, $body );

		if ( is_array( $streets ) && isset( $streets['streets'] ) && ! empty( $streets['streets'] ) ) {

			// Create a simple array with only basic data for output on admin page and remove doubled IDs.
			$streets = array_column( $streets['streets'], 'name', 'id' );

			// Update city and streets if we're passing them.
			// TODO - separate into two methods.
			if ( true === $update_settings ) {
				$this->update_city_streets_setting( $city_name, $city_id, $streets );
				// TODO - check update_city_streets_setting.
			}

		} else {
			$this->logs->add_error( 'City does not have streets.' );

			return false;
		}

		return $streets;
	}
}