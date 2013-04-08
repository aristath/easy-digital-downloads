<?php
/**
 * EDD AJAX Test Cases
 *
 * Taken from WordPress Unit Tests and adapted for Easy Digital
 * Downloads by Sunny Ratilal.
 *
 * Edit: Sunny Ratilal, April 2013
 */

abstract class EDD_AJAX_TestCase extends WP_UnitTestCase {
	protected $_last_ajax_response;

	protected $_actions = array(
		'edd_remove_from_cart', 'edd_add_to_cart', 'edd_apply_discount', 'checkout_login',
		'checkout_register', 'get_download_title', 'edd_local_tax_opt_in', 'edd_local_tax_opt_out',
		'edd_check_for_download_price_variations'
	);

	protected $_error_level = 0;

	public function setUp() {
		parent::setUp();

		foreach ($_action as $actions) {
			if ( function_exists( 'wp_ajax_' . str_replace( '-', '_', $action ) ) )
				add_action( 'wp_ajax_' . $action, 'wp_ajax_' . str_replace( '-', '_', $action ), 1 );

			add_filter( 'wp_die_ajax_handler', array( $this, 'getDieHandler' ), 1, 1 );

			if (!defined('DOING_AJAX'))
				define('DOING_AJAX', true);
			set_current_screen( 'ajax' );

			add_action( 'clear_auth_cookie', array( $this, 'logout' ) );

			$this->_error_level = error_reporting();

			error_reporting( $this->_error_level & ~E_WARNING );

			$this->factory->post->create_many( 5 );
		}
	}

	public function tearDown() {
		parent::tearDown();
		$_POST = array();
		$_GET = array();
		unset( $GLOBALS['post'] );
		unset( $GLOBALS['comment'] );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'getDieHandler' ), 1, 1 );
		remove_action( 'clear_auth_cookie', array( $this, 'logout' ) );
		error_reporting( $this->_error_level );
		set_current_screen( 'front' );
	}

	public function logout() {
		unset( $GLOBALS['current_user'] );
		$cookies = array(AUTH_COOKIE, SECURE_AUTH_COOKIE, LOGGED_IN_COOKIE, USER_COOKIE, PASS_COOKIE);
		foreach ( $cookies as $c )
			unset( $_COOKIE[$c] );
	}

	public function getDieHandler() {
		return array( $this, 'dieHandler' );
	}

	public function dieHandler( $message ) {
		$this->_last_response .= ob_get_clean();
		ob_end_clean();
		if ( '' === $this->_last_response ) {
				if ( is_scalar( $message) ) {
						throw new Exception( (string) $message );
				} else {
						throw new Exception( '0' );
				}
		} else {
				throw new Exception( $message );
		}
	}

	protected function _setRole( $role ) {
		$post = $_POST;
		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		$_POST = array_merge($_POST, $post);
	}

	protected function _handleAjax($action) {
		// Start output buffering
		ini_set( 'implicit_flush', false );
		ob_start();

		// Build the request
		$_POST['action'] = $action;
		$_GET['action']  = $action;
		$_REQUEST        = array_merge( $_POST, $_GET );

		// Call the hooks
		do_action( 'admin_init' );
		do_action( 'wp_ajax_' . $_REQUEST['action'], null );

		// Save the output
		$buffer = ob_get_clean();
		if ( !empty( $buffer ) )
				$this->_last_response = $buffer;
	}
}