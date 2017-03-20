<?php
namespace AffWP\Utils;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Utilites.
 *
 * @covers \Affiliate_WP_Utilities
 * @group utils
 */
class Tests extends UnitTestCase {

	/**
	 * Utilities object.
	 *
	 * @access protected
	 * @var    \Affiliate_WP_Utilities
	 */
	protected static $utils;

	/**
	 * @var string
	 */
	protected static $username = 'foobar';

	/**
	 * Test user.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $user_id;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$utils = new \Affiliate_WP_Utilities;

		self::$user_id  = parent::affwp()->user->create( array(
			'user_login' => self::$username
		) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::$logs
	 */
	public function test_logs_should_be_an_Affiliate_WP_Logging_instance() {
		$this->assertInstanceOf( 'Affiliate_WP_Logging', self::$utils->logs );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::$batch
	 */
	public function test_batch_should_be_an_AffWP_Utils_Batch_Process_Registry_instance() {
		$this->assertInstanceOf( 'AffWP\Utils\Batch_Process\Registry', self::$utils->batch );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::$data
	 */
	public function test_data_should_be_an_AffWP_Utils_Data_Storage_instance() {
		$this->assertInstanceOf( 'AffWP\Utils\Data_Storage', self::$utils->data );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::$upgrades
	 */
	public function test_upgrades_should_be_an_Affiliate_WP_Upgrades_instance() {
		$this->assertInstanceOf( 'Affiliate_WP_Upgrades', self::$utils->upgrades );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::process_request_data()
	 */
	public function test_process_request_data_should_return_data_unchanged_if_old_key_empty() {
		$data = array( 'key' => 'value' );

		$result = self::$utils->process_request_data( $data );

		$this->assertEqualSets( $data, $result );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::process_request_data()
	 */
	public function test_process_request_data_should_return_data_unchanged_if_invalid_old_key() {
		$data = array( 'key' => 'value' );

		$result = self::$utils->process_request_data( $data, 'foo' );

		$this->assertEqualSets( $data, $result );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::process_request_data()
	 */
	public function test_process_request_data_should_unset_user_name_old_key_if_valid_user() {
		$data = array( 'user_name' => self::$username );

		$result = self::$utils->process_request_data( $data, 'user_name' );

		$this->assertArrayNotHasKey( 'user_name', $result );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::process_request_data()
	 */
	public function test_process_request_data_should_set_valid_user_id_if_valid_user_name_old_key() {
		$data = array( 'user_name' => self::$username );

		$result = self::$utils->process_request_data( $data, 'user_name' );

		$this->assertEqualSets( array( 'user_id' => self::$user_id ), $result );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::process_request_data()
	 */
	public function test_process_request_data_should_set_0_user_id_if_invalid_user_name_old_key() {
		$data = array( 'user_name' => 'foo' );

		$result = self::$utils->process_request_data( $data, 'user_name' );

		$this->assertArrayHasKey( 'user_id', $result );
		$this->assertSame( 0, $result['user_id'] );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::process_request_data()
	 */
	public function test_process_request_data_affwp_affiliate_user_name_old_key_should_convert_to_user_id_by_default() {
		$data = array( '_affwp_affiliate_user_name' => self::$username );

		$result = self::$utils->process_request_data( $data, '_affwp_affiliate_user_name' );

		$this->assertArrayHasKey( 'user_id', $result );
	}

}
