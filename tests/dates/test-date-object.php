<?php
namespace AffWP\Utils\Date;

use AffWP\Utils\Date;
use AffWP\Tests\UnitTestCase;

/**
 * Tests for AffWP\Utils\Date
 *
 * @covers AffWP\Utils\Date
 *
 * @group dates
 * @group objects
 */
class Tests extends UnitTestCase {

	/**
	 * Date string test fixture.
	 *
	 * @var string
	 */
	protected static $date_string = '01-02-2003 7:08:09';

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		update_option( 'gmt_offset', -5 );
		affiliate_wp()->utils->_refresh_wp_offset();
	}

	/**
	 * @covers \AffWP\Utils\Date::__construct()
	 * @group dates
	 */
	public function test_Date_should_extend_DateTime() {
		$this->assertInstanceOf( 'DateTime', $this->get_date_instance() );
	}

	/**
	 * @covers \AffWP\Utils\Date::__construct()
	 * @group dates
	 */
	public function test_Date_should_always_convert_date_to_WordPress_time() {
		$date     = $this->get_date_instance();
		$expected = gmdate( 'Y-m-d H:i:s', strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( 'mysql' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_empty_format_should_use_datetime_shorthand_format() {
		$date     = $this->get_date_instance();
		$expected = gmdate( affwp_get_date_format( 'datetime' ), strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( '' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_true_format_should_use_datetime_shorthand_format() {
		$date     = $this->get_date_instance();
		$expected = gmdate( affwp_get_date_format( 'datetime' ), strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( true ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_date_should_use_date_shorthand_format() {
		$date     = $this->get_date_instance();
		$expected = gmdate( affwp_get_date_format( 'date' ), strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( 'date' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_time_should_use_time_shorthand_format() {
		$date     = $this->get_date_instance();
		$expected = gmdate( affwp_get_date_format( 'time' ), strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( 'time' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_mysql_should_use_mysql_shorthand_format() {
		$date     = $this->get_date_instance();
		$expected = gmdate( affwp_get_date_format( 'mysql' ), strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( 'mysql' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_object_should_return_Date_object() {
		$date = $this->get_date_instance();

		$this->assertEquals( $date, $date->format( 'object' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_timestamp_should_return_original_timestamp() {
		$date = $this->get_date_instance();

		$this->assertSame( strtotime( self::$date_string ), $date->format( 'timestamp' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_wp_timestamp_should_return_WP_timestamp() {
		$date     = $this->get_date_instance();
		$expected = strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset;

		$this->assertSame( $expected, $date->format( 'wp_timestamp' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::format()
	 * @group dates
	 */
	public function test_format_generic_date_format_should_format_with_that_scheme() {
		$date     = $this->get_date_instance();
		$expected = gmdate( 'm/d/Y', strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset );

		$this->assertSame( $expected, $date->format( 'm/d/Y' ) );
	}

	/**
	 * @covers \AffWP\Utils\Date::getWPTimestamp()
	 * @group dates
	 */
	public function test_getWPTimestamp_should_return_timestamp_with_offset_applied() {
		$date     = $this->get_date_instance();
		$expected = strtotime( self::$date_string ) + affiliate_wp()->utils->wp_offset;

		$this->assertSame( $expected, $date->getWPTimestamp() );
	}

	/**
	 * Helper to retrieve a Date instance.
	 *
	 * @return \AffWP\Utils\Date
	 */
	protected function get_date_instance() {
		return new Date( self::$date_string, new \DateTimeZone( affwp_get_timezone() ) );
	}

}
