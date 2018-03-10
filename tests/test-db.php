<?php
namespace AffWP\Database;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_DB_Affiliates class
 *
 * @covers Affiliate_WP_DB
 * @group database
 */
class Tests extends UnitTestCase {

	/**
	 * Affiliate fixture.
	 *
	 * @access protected
	 * @var int
	 * @static
	 */
	protected static $affiliate_id = 0;

	/**
	 * Referral fixture.
	 *
	 * @access protected
	 * @var int
	 * @static
	 */
	protected static $referral_id = 0;

	/**
	 * Date string fixture.
	 *
	 * @access protected
	 * @var string
	 * @static
	 */
	protected static $date_string = '01/01/2001';

	/**
	 * Date string 'start' fixture.
	 *
	 * @access protected
	 * @var string
	 * @static
	 */
	protected static $date_string_start = '01/03/2001';

	/**
	 * Date string 'start' fixture.
	 *
	 * @access protected
	 * @var string
	 * @static
	 */
	protected static $date_string_end = '01/05/2001';

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		update_option( 'gmt_offset', -5 );
		affiliate_wp()->utils->_refresh_wp_offset();

		self::$affiliate_id = parent::affwp()->affiliate->create();

		self::$referral_id = parent::affwp()->referral->create( array(
			'affiliate_id' => self::$affiliate_id
		) );
	}

	/**
	 * @covers Affiliate_WP_DB::insert()
	 */
	public function test_insert_should_unslash_data_before_inserting_into_db() {
		$description = addslashes( "Couldn't be simpler" );

		// Confirm the incoming value is slashed. (Simulating $_POST, which is slashed by core).
		$this->assertSame( "Couldn\'t be simpler", $description );

		// Fire ->add() which fires ->insert().
		$referral_id = $this->factory->referral->create( array(
			'affiliate_id' => self::$affiliate_id,
			'description'  => $description
		) );

		$stored = affiliate_wp()->referrals->get_column( 'description', $referral_id );

		$this->assertSame( wp_unslash( $description ), $stored );
	}

	/**
	 * @covers Affiliate_WP_DB::update()
	 */
	public function test_update_should_unslash_data_before_inserting_into_db() {
		$description = addslashes( "Couldn't be simpler" );

		// Confirm the incoming value is slashed. (Simulating $_POST, which is slashed by core).
		$this->assertSame( "Couldn\'t be simpler", $description );

		// Fire ->update_referral() which fires ->update()
		$this->factory->referral->update_object( self::$referral_id, array(
			'description' => $description
		) );

		$stored = affiliate_wp()->referrals->get_column( 'description', self::$referral_id );

		$this->assertSame( wp_unslash( $description ), $stored );
	}

	/**
	 * @covers \Affiliate_WP_DB::get_by()
	 */
	public function test_get_by_with_empty_column_should_return_false() {
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$this->assertFalse( $db->get_by( '', 100 ) );
	}

	/**
	 * @covers \Affiliate_WP_DB::get_by()
	 */
	public function test_get_by_with_empty_row_id_should_return_false() {
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$this->assertFalse( $db->get_by( 'affiliate_id', '' ) );
	}

	/**
	 * @covers Affiliate_WP_DB::parse_fields()
	 * @group database-fields
	 */
	public function test_parse_fields_with_empty_array_should_return_wildcard() {
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$this->assertSame( '*', $db->parse_fields( array() ) );
	}

	/**
	 * @covers Affiliate_WP_DB::parse_fields()
	 * @group database-fields
	 */
	public function test_parse_fields_with_empty_string_should_return_wildcard() {
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$this->assertSame( '*', $db->parse_fields( '' ) );
	}

	/**
	 * @covers Affiliate_WP_DB::parse_fields()
	 * @group database-fields
	 */
	public function test_parse_fields_with_invalid_string_field_should_return_wildcard() {
		$result = affiliate_wp()->affiliates->parse_fields( 'foo' );

		$this->assertSame( '*', $result );
	}

	/**
	 * @covers Affiliate_WP_DB::parse_fields()
	 * @group database-fields
	 */
	public function test_parse_fields_with_valid_string_field_should_return_that_field() {
		$result = affiliate_wp()->affiliates->parse_fields( 'rate' );

		$this->assertSame( 'rate', $result );
	}
	/**
	 * @covers Affiliate_WP_DB::parse_fields()
	 * @group database-fields
	 */
	public function test_parse_fields_with_both_valid_and_invalid_fields_should_return_only_valid_fields() {
		$result = affiliate_wp()->affiliates->parse_fields( array( 'foo', 'user_id' ) );

		$this->assertSame( 'user_id', $result );
	}

	/**
	 * @covers Affiliate_WP_DB::parse_fields()
	 * @group database-fields
	 */
	public function test_parse_fields_with_multiple_valid_fields_should_return_comma_separated_list() {
		$result = affiliate_wp()->affiliates->parse_fields( array( 'user_id', 'rate' ) );

		$this->assertSame( 'user_id, rate', $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_string_date_empty_field_should_build_mdy_clause_with_date() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE 2001 = YEAR ( date ) AND 01 = MONTH ( date ) AND 01 = DAY ( date ) ";
		$actual   = $db->prepare_date_query( '', self::$date_string, '' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_date_empty_field_should_build_gte_clause_with_date() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 00:00:00' ";
		$actual   = $db->prepare_date_query( '', array( 'start' => self::$date_string_start ), '' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_end_date_empty_field_should_build_lte_clause_with_date() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array( 'end' => self::$date_string_end ), '' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_end_dates_empty_field_should_build_gte_lte_clause_with_date() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 00:00:00' AND `date` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array(
			'start' => self::$date_string_start,
			'end'   => self::$date_string_end
		), '' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_string_date_default_field_should_build_mdy_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE 2001 = YEAR ( date ) AND 01 = MONTH ( date ) AND 01 = DAY ( date ) ";
		$actual   = $db->prepare_date_query( '', self::$date_string );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_string_date_nondefault_field_should_build_mdy_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE 2001 = YEAR ( date_registered ) AND 01 = MONTH ( date_registered ) AND 01 = DAY ( date_registered ) ";
		$actual   = $db->prepare_date_query( '', self::$date_string, 'date_registered' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_date_default_field_should_build_gte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 00:00:00' ";
		$actual   = $db->prepare_date_query( '', array( 'start' => self::$date_string_start ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_date_nondefault_field_should_build_gte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date_registered` >= '2001-01-03 00:00:00' ";
		$actual   = $db->prepare_date_query( '', array( 'start' => self::$date_string_start ), 'date_registered' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_date_seconds_should_format_YmdHis_gte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$date      = '01/03/2001 05:06:07';
		$no_offset = date( 'Y-m-d H:i:s', strtotime( $date ) - affiliate_wp()->utils->wp_offset );

		$expected = "WHERE `date` >= '" . $no_offset . "' ";
		$actual   = $db->prepare_date_query( '', array( 'start' => $date ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_date_no_seconds_should_use_000000_gte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 00:00:00' ";
		$actual   = $db->prepare_date_query( '', array( 'start' => '01/03/2001' ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_date_should_remove_wp_offset() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 10:06:07' ";
		$actual   = $db->prepare_date_query( '', array( 'start' => '01/03/2001 05:06:07' ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_end_date_default_field_should_build_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array( 'end' => self::$date_string_end ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_end_date_nondefault_field_should_build_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date_registered` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array( 'end' => self::$date_string_end ), 'date_registered' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_end_date_seconds_should_format_YmdHis_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$date      = '01/05/2001 05:06:07';
		$no_offset = date( 'Y-m-d H:i:s', strtotime( $date ) - affiliate_wp()->utils->wp_offset );

		$expected = "WHERE `date` <= '" . $no_offset . "' ";
		$actual   = $db->prepare_date_query( '', array( 'end' => $date ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_end_date_no_seconds_should_use_235959_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array( 'end' => '01/05/2001' ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_end_date_should_remove_wp_offset() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` <= '2001-01-05 10:06:07' ";
		$actual   = $db->prepare_date_query( '', array( 'end' => '01/05/2001 05:06:07' ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_end_dates_default_field_should_build_gte_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 00:00:00' AND `date` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array(
			'start' => self::$date_string_start,
			'end'   => self::$date_string_end,
		) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_data_query_with_start_end_dates_nondefault_field_should_build_gte_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date_registered` >= '2001-01-03 00:00:00' AND `date_registered` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array(
			'start' => self::$date_string_start,
			'end'   => self::$date_string_end,
		), 'date_registered' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_end_dates_seconds_should_format_YmdHis_gte_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$date_start      = '01/03/2001 05:06:07';
		$no_offset_start = date( 'Y-m-d H:i:s', strtotime( $date_start ) - affiliate_wp()->utils->wp_offset );

		$date_end      = '01/05/2001 05:06:07';
		$no_offset_end = date( 'Y-m-d H:i:s', strtotime( $date_end ) - affiliate_wp()->utils->wp_offset );

		$expected = "WHERE `date` >= '" . $no_offset_start . "' AND `date` <= '" . $no_offset_end . "' ";
		$actual   = $db->prepare_date_query( '', array(
			'start' => $date_start,
			'end'   => $date_end,
		) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_end_dates_no_seconds_should_use_000000_235959_gte_lte_clause() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 00:00:00' AND `date` <= '2001-01-05 23:59:59' ";
		$actual   = $db->prepare_date_query( '', array(
			'start' => '01/03/2001',
			'end'   => '01/05/2001',
		) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::prepare_date_query()
	 * @group dates
	 */
	public function test_prepare_date_query_with_start_end_dates_should_remove_wp_offset() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		$expected = "WHERE `date` >= '2001-01-03 10:06:07' AND `date` <= '2001-01-05 10:06:07' ";
		$actual   = $db->prepare_date_query( '', array(
			'start' => '01/03/2001 05:06:07',
			'end'   => '01/05/2001 05:06:07',
		) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB::insert()
	 * @group filters
	 */
	public function test_insert_with_pre_insert_type_data_filter_falsey_array_should_prevent_insertion() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		add_filter( 'affwp_pre_insert_payout_data', '__return_empty_array' );

		$result = $db->insert( array( 'affiliate_id' => 5 ), 'payout' );

		remove_filter( 'affwp_pre_insert_payout_data', '__return_empty_array' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::insert()
	 * @group filters
	 */
	public function test_insert_with_pre_insert_type_data_filter_false_value_should_prevent_insertion() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		add_filter( 'affwp_pre_insert_payout_data', '__return_false' );

		$result = $db->insert( array( 'affiliate_id' => 5 ), 'payout' );

		remove_filter( 'affwp_pre_insert_payout_data', '__return_false' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::insert()
	 * @group filters
	 */
	public function test_insert_with_pre_insert_type_data_filter_empty_value_should_prevent_insertion() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		add_filter( 'affwp_pre_insert_payout_data', '__return_empty_string' );

		$result = $db->insert( array( 'affiliate_id' => 5 ), 'payout' );

		remove_filter( 'affwp_pre_insert_payout_data', '__return_empty_string' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::insert()
	 * @group filters
	 */
	public function test_insert_with_pre_insert_type_data_filter_null_value_should_prevent_insertion() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		add_filter( 'affwp_pre_insert_payout_data', '__return_null' );

		$result = $db->insert( array( 'affiliate_id' => 5 ), 'payout' );

		remove_filter( 'affwp_pre_insert_payout_data', '__return_null' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::insert()
	 * @group filters
	 */
	public function test_insert_with_pre_insert_type_data_filter_zero_value_should_prevent_insertion() {
		/** @var \Affiliate_WP_DB $db */
		$db = $this->getMockForAbstractClass( 'Affiliate_WP_DB' );

		add_filter( 'affwp_pre_insert_payout_data', '__return_zero' );

		$result = $db->insert( array( 'affiliate_id' => 5 ), 'payout' );

		remove_filter( 'affwp_pre_insert_payout_data', '__return_zero' );

		$this->assertFalse( $result );
	}

}
