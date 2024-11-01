<?php

namespace SkyWeb\WC_Iiko;

use WP_Error;

defined( 'ABSPATH' ) || exit;

class Logs {

	private $logs;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logs = new WP_Error();
	}

	/**
	 * Add an error message.
	 *
	 * @param $message
	 */
	public function add_error( $message ) {
		$this->logs->add( 'skyweb_wc_iiko_errors', $message );
	}

	/**
	 * Add a notice message.
	 *
	 * @param $message
	 */
	public function add_notice( $message ) {
		$this->logs->add( 'skyweb_wc_iiko_notices', $message );
	}

	/**
	 * Get all error messages.
	 */
	public function get_errors() {
		return $this->logs->get_error_messages( 'skyweb_wc_iiko_errors' );
	}

	/**
	 * Get all notice messages.
	 */
	public function get_notices() {
		return $this->logs->get_error_messages( 'skyweb_wc_iiko_notices' );
	}

	/**
	 * Return all logs.
	 */
	public function return_logs( $add_info = array() ) {

		$logs            = array();
		$logs['errors']  = ! empty( $this->get_errors() ) ? $this->get_errors() : null;
		$logs['notices'] = ! empty( $this->get_notices() ) ? $this->get_notices() : null;

		if ( is_array( $add_info ) && ! empty( $add_info ) ) {
			$logs = array_merge( $logs, $add_info );
		}

		return $logs;
	}

	/**
	 * Debug.
	 */
	public function debug() {
		return $this->logs;
	}

	/**
	 * Add WC error log record.
	 */
	public static function add_wc_error_log( $message, $source = 'common' ) {
		if ( 'yes' === get_option( 'skyweb_wc_iiko_debug_mode' ) ) {
			$logger = wc_get_logger();
			$logger->error( wc_print_r( $message, true ), array( 'source' => 'skyweb-wc-iiko-' . $source ) );
		}
	}

	/**
	 * Add WC debug log record.
	 */
	public static function add_wc_debug_log( $message, $source = 'common' ) {
		if ( 'yes' === get_option( 'skyweb_wc_iiko_debug_mode' ) ) {
			$logger = wc_get_logger();
			$logger->debug( wc_print_r( $message, true ), array( 'source' => 'skyweb-wc-iiko-' . $source ) );
		}
	}

	/**
	 * Add WC notice log record.
	 */
	public static function add_wc_notice_log( $message, $source = 'common' ) {
		if ( 'yes' === get_option( 'skyweb_wc_iiko_debug_mode' ) ) {
			$logger = wc_get_logger();
			$logger->notice( wc_print_r( $message, true ), array( 'source' => 'skyweb-wc-iiko-' . $source ) );
		}
	}
}