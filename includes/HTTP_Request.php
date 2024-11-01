<?php

namespace SkyWeb\WC_Iiko;

defined( 'ABSPATH' ) || exit;

class HTTP_Request {

	/**
	 * Submit request.
	 *
	 * Return array with the api answer or false if there are any errors.
	 * Write errors into the $skyweb_wc_iiko_logs.
	 *
	 * @param string $url
	 * @param array $add_headers
	 * @param string $body
	 *
	 * @return false|array
	 */
	public static function remote_post( $url, $add_headers = array(), $body = '', $blocking = true ) {

		global $skyweb_wc_iiko_logs;

		if ( empty( $url ) ) {
			$skyweb_wc_iiko_logs->add_error( 'Request URL is empty.' );

			return false;
		}

		$url      = esc_url( 'https://api-ru.iiko.services/api/1/' . $url );
		$timeout  = absint( get_option( 'skyweb_wc_iiko_timeout' ) ) > 0 ? absint( get_option( 'skyweb_wc_iiko_timeout' ) ) : 10;
		$headers  = array(
			'Content-Type' => 'application/json; charset=utf-8',
			'Timeout'      => $timeout
		);
		$headers  = is_array( $add_headers ) && ! empty( $add_headers ) ? array_merge( $headers, $add_headers ) : $headers;
		$body     = is_array( $body ) ? $body : array();
		$args     = array(
			'method'      => 'POST',
			'timeout'     => $timeout,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => $blocking, // Currently not used. Always true.
			'headers'     => $headers,
			'body'        => wp_json_encode( $body ),
			'data_format' => 'body'
		);
		$response = wp_safe_remote_post( $url, $args );

		Logs::add_wc_debug_log( $response, 'remote-post' );

		// WP_Error.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_code    = $response->get_error_code();
			$skyweb_wc_iiko_logs->add_error( "Request failed. WP_Error: $error_code - {$error_message}." );

			return false;
		}

		// Wrong response code error.
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		if ( 200 !== $response_code ) {
			$skyweb_wc_iiko_logs->add_error( "Request failed. {$response_code} - {$response_message}." );

			Logs::add_wc_error_log( "Request failed. {$response_code} - {$response_message}.", 'remote-post' );

			// No return cause we can have iiko errors.
		}

		// Decode JSON response body to an associative array.
		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		// JSON decode error.
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$skyweb_wc_iiko_logs->add_error( 'Response body is not a correct JSON.' );
		}

		// Response body is empty error.
		if ( ! is_array( $response ) || empty( $response ) ) {
			$skyweb_wc_iiko_logs->add_error( 'Response is not an array or is empty.' );

			return false;
		}

		// Iiko error.
		if ( array_key_exists( 'errorDescription', $response ) ) {
			$error_number = isset( $response['error'] ) ? $response['error'] : '';
			$skyweb_wc_iiko_logs->add_error( "Iiko response contains the error: $error_number - {$response['errorDescription']}." );

			return false;
		}

		return $response;
	}
}