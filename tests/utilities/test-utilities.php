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
	 * @covers \Affiliate_WP_Utilities::$wp_offset
	 * @group dates
	 */
	public function test_wp_offset_should_equal_value_of_offset_in_seconds() {
		$expected = get_option( 'gmt_offset', '' ) * HOUR_IN_SECONDS;

		$this->assertSame( $expected, self::$utils->wp_offset );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::$date_format
	 * @group dates
	 */
	public function test_date_format_should_equal_value_of_date_format_option() {
		$this->assertSame( get_option( 'date_format', '' ), self::$utils->date_format );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::$time_format
	 * @group dates
	 */
	public function test_time_format_should_equal_value_of_time_format_option() {
		$this->assertSame( get_option( 'time_format', '' ), self::$utils->time_format );
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

	/**
	 * @covers \Affiliate_WP_Utilities::get_date_format()
	 * @group dates
	 */
	public function test_get_date_format_empty_format_should_default_to_date_format() {
		$this->assertSame( get_option( 'date_format', '' ), self::$utils->get_date_format( '' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::get_date_format()
	 * @group dates
	 */
	public function test_get_date_format_date_should_return_date_format_value() {
		$this->assertSame( get_option( 'date_format', '' ), self::$utils->get_date_format( 'date' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::get_date_format()
	 * @group dates
	 */
	public function test_get_date_format_time_should_return_time_format_value() {
		$this->assertSame( get_option( 'time_format', '' ), self::$utils->get_date_format( 'time' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::get_date_format()
	 * @group dates
	 */
	public function test_get_date_format_datetime_should_return_date_and_time_format_values() {
		$expected = get_option( 'date_format', '' ) . ' ' . get_option( 'time_format', '' );

		$this->assertSame( $expected, self::$utils->get_date_format( 'datetime' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::get_date_format()
	 * @group dates
	 */
	public function test_get_date_format_mysql_should_return_mysql_format() {
		$this->assertSame( 'Y-m-d H:i:s', self::$utils->get_date_format( 'mysql' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::get_date_format()
	 * @group dates
	 */
	public function test_get_date_format_non_shorthand_format_should_return_that_format() {
		$this->assertSame( 'm/d/Y', self::$utils->get_date_format( 'm/d/Y' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::date()
	 * @group dates
	 */
	public function test_date_should_return_a_Date_object() {
		$this->assertInstanceOf( 'AffWP\Utils\Date', self::$utils->date() );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::date()
	 * @group dates
	 */
	public function test_date_should_return_a_DateTime_object() {
		$this->assertInstanceOf( 'DateTime', self::$utils->date() );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::date()
	 * @group dates
	 */
	public function test_date_should_be_converted_to_WordPress_time() {
		$date = self::$utils->date( '2003-01-02 10:09:08' );
		$expected = gmdate( 'Y-m-d H:i:s', strtotime( '2003-01-02 10:09:08' ) + self::$utils->wp_offset );

		$this->assertSame( $expected, $date->format( 'mysql' ) );
	}

	/**
	 * @covers \Affiliate_WP_Utilities::_refresh_wp_offset()
	 * @group dates
	 */
	public function test_refresh_wp_offset_should_refresh_the_offset_value_on_gmt_offset_update() {
		$original_offset = self::$utils->wp_offset / HOUR_IN_SECONDS;

		$new_offset = -7 * HOUR_IN_SECONDS;

		update_option( 'gmt_offset', -7 );

		self::$utils->_refresh_wp_offset();

		$this->assertSame( $new_offset, self::$utils->wp_offset );

		// Clean up.
		update_option( 'gmt_offset', $original_offset );
		self::$utils->_refresh_wp_offset();
	}

}
