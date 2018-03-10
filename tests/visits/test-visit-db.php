<?php
namespace AffWP\Visit\Database;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Visits_DB class
 *
 * @covers Affiliate_WP_Visits_DB
 * @group database
 * @group visits
 */
class Tests extends UnitTestCase {

	/**
	 * Test affiliates.
	 *
	 * @access public
	 * @var array
	 */
	public static $affiliates = array();

	/**
	 * Test visits.
	 *
	 * @access public
	 * @var array
	 */
	public static $visits = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		update_option( 'gmt_offset', -5 );
		affiliate_wp()->utils->_refresh_wp_offset();

		self::$affiliates = parent::affwp()->affiliate->create_many( 4 );

		for ( $i = 0; $i <= 3; $i++ ) {
			self::$visits[ $i ] = parent::affwp()->visit->create( array(
				'context' => "foo-{$i}"
			) );
		}

		// Create a visit with an empty context.
		self::$visits[4] = parent::affwp()->visit->create();
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_columns()
	 */
	public function test_get_columns_should_return_all_columns() {
		$columns = array(
			'visit_id'     => '%d',
			'affiliate_id' => '%d',
			'referral_id'  => '%d',
			'url'          => '%s',
			'referrer'     => '%s',
			'campaign'     => '%s',
			'context'      => '%s',
			'ip'           => '%s',
			'date'         => '%s',
		);

		$this->assertEqualSets( $columns, affiliate_wp()->visits->get_columns() );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_should_return_array_of_Visit_objects_if_not_count_query() {
		$results = affiliate_wp()->visits->get_visits();

		// Check a random visit.
		$this->assertContainsOnlyType( 'AffWP\Visit', $results );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_should_turn_integer_if_count_query() {
		$results = affiliate_wp()->visits->get_visits( array(), $count = true );

		$this->assertTrue( is_numeric( $results ) );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 * @group database-fields
	 */
	public function test_get_visits_fields_ids_should_return_an_array_of_ids_only() {
		$results = affiliate_wp()->visits->get_visits( array(
			'fields' => 'ids'
		) );

		$this->assertEqualSets( self::$visits, $results );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 * @group database-fields
	 */
	public function test_get_visits_invalid_fields_arg_should_return_regular_Visit_object_results() {
		$visits = array_map( 'affwp_get_visit', self::$visits );

		$results = affiliate_wp()->visits->get_visits( array(
			'fields' => 'foo'
		) );

		$this->assertEqualSets( $visits, $results );

	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 * @group database-fields
	 */
	public function test_get_visits_fields_ids_should_return_an_array_of_integer_ids() {
		$results = affiliate_wp()->visits->get_visits( array(
			'fields' => 'ids'
		) );

		$this->assertContainsOnlyType( 'integer', $results );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 * @group database-fields
	 */
	public function test_get_visits_with_no_fields_should_return_an_array_of_affiliate_objects() {
		$results = affiliate_wp()->visits->get_visits();

		$this->assertContainsOnlyType( 'AffWP\Visit', $results );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 * @group database-fields
	 */
	public function test_get_visits_with_multiple_valid_fields_should_return_an_array_of_stdClass_objects() {
		$results = affiliate_wp()->visits->get_visits( array(
			'fields' => array( 'visit_id', 'affiliate_id' )
		) );

		$this->assertContainsOnlyType( 'stdClass', $results );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 * @group database-fields
	 */
	public function test_get_visits_fields_array_with_multiple_valid_fields_should_return_objects_with_those_fields_only() {
		$fields = array( 'visit_id', 'affiliate_id' );

		$result = affiliate_wp()->visits->get_visits( array(
			'fields' => $fields
		) );

		$object_vars = get_object_vars( $result[0] );

		$this->assertEqualSets( $fields, array_keys( $object_vars ) );

	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_singular_visit_id_should_return_that_visit() {
		$results = affiliate_wp()->visits->get_visits( array(
			'visit_id' => self::$visits[0],
			'fields'   => 'ids',
		) );

		$this->assertSame( self::$visits[0], $results[0] );
	}

	/**
	 * @covers Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_multiple_visits_should_return_only_those_visits() {
		$visits = array( self::$visits[1], self::$visits[3] );

		$results = affiliate_wp()->visits->get_visits( array(
			'visit_id' => $visits,
			'fields'   => 'ids',
		) );

		$this->assertEqualSets( $visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_single_context_should_return_visits_with_that_context() {
		$results = affiliate_wp()->visits->get_visits( array(
			'context' => 'foo-0',
			'fields'  => 'ids',
		) );

		$this->assertEqualSets( array( self::$visits[0] ), $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_array_of_contexts_should_return_visits_with_those_contexts() {
		$visits = array( self::$visits[1], self::$visits[3] );

		$results = affiliate_wp()->visits->get_visits( array(
			'context' => array( 'foo-1', 'foo-3' ),
			'fields'  => 'ids',
		) );

		$this->assertEqualSets( $visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_empty_context_and_not_equals_compare_should_return_visits_with_non_empty_context() {
		$results = affiliate_wp()->visits->get_visits( array(
			'fields'          => 'ids',
			'context_compare' => '!='
		) );

		$this->assertFalse( in_array( self::$visits[4], $results, true ) );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_array_contexts_and_not_equals_compare_should_return_visits_not_containing_those_contexts() {
		$visits = array( self::$visits[2], self::$visits[3], self::$visits[4] );

		$results = affiliate_wp()->visits->get_visits( array(
			'context'         => array( 'foo-0', 'foo-1' ),
			'context_compare' => '!=',
			'fields'          => 'ids',
		) );

		$this->assertEqualSets( $visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_EMPTY_context_compare_should_return_visits_with_empty_context_only() {
		$results = affiliate_wp()->visits->get_visits( array(
			'context_compare' => 'EMPTY',
			'fields'          => 'ids',
		) );

		$this->assertEqualSets( array( self::$visits[4] ), $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_NOT_EMPTY_context_compare_should_return_visits_with_not_empty_contexts() {
		$visits = array( self::$visits[0], self::$visits[1], self::$visits[2], self::$visits[3] );

		$results = affiliate_wp()->visits->get_visits( array(
			'context_compare' => 'NOT EMPTY',
			'fields'          => 'ids',
		) );

		$this->assertEqualSets( $visits, $results );
	}

	public function test_gmt_option_for_tests_is_not_utc_0() {
		$gmt_offset = get_option( 'gmt_offset', 0 );

		$this->assertNotSame( 0, $gmt_offset );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 * @group dates
	 */
	public function test_get_visits_with_date_no_start_end_should_retrieve_visits_for_today() {
		$results = affiliate_wp()->visits->get_visits( array(
			'date'   => 'today',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 * @group dates
	 */
	public function test_get_visits_with_today_visits_yesterday_date_no_start_end_should_return_empty() {
		$results = affiliate_wp()->visits->get_visits( array(
			'date'   => 'yesterday',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array(), $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 * @group dates
	 */
	public function test_get_visits_date_start_should_only_retrieve_visits_created_after_that_date() {
		$visits = $this->factory->visit->create_many( 3, array(
			'date' => '2016-01-01',
		) );

		$results = affiliate_wp()->visits->get_visits( array(
			'date'   => array(
				'start' => '2016-01-02'
			),
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 * @group dates
	 */
	public function test_get_visits_date_end_should_only_retrieve_visits_created_before_that_date() {
		$visit = $this->factory->visit->create( array(
			'date' => '+1 day',
		) );

		$results = affiliate_wp()->visits->get_visits( array(
			'date'   => array( 'end' => 'today' ),
			'fields' => 'ids',
		) );

		// Should catch all but the one just created +1 day.
		$this->assertEqualSets( self::$visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::get_visits()
	 */
	public function test_get_visits_with_no_context_and_no_context_compare_should_return_all_visits() {
		$results = affiliate_wp()->visits->get_visits( array(
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$visits, $results );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::add()
	 */
	public function test_add_with_context_under_50_chars_should_add_with_complete_sanitized_context() {
		/** @var \AffWP\Visit $visit */
		$visit = $this->factory->visit->create_and_get( array(
			'affiliate_id' => self::$affiliates[0],
			'context'      => 'affwp-test'
		) );

		$this->assertSame( 'affwp-test', $visit->context );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::add()
	 */
	public function test_add_with_context_over_50_chars_should_add_with_first_50_chars_of_sanitized_context() {
		$context = rand_str( 55 );

		$visit = $this->factory->visit->create_and_get( array(
			'affiliate_id' => self::$affiliates[0],
			'context'      => $context,
		) );

		$this->assertSame( substr( $context, 0, 50 ), $visit->context );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::add()
	 */
	public function test_add_with_completely_invalid_context_should_add_without_a_context() {
		$visit = $this->factory->visit->create_and_get( array(
			'affiliate_id' => self::$affiliates[0],
			'context'      => '(*&^%$#$%^',
		) );

		$this->assertEmpty( $visit->context );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::add()
	 * @group dates
	 */
	public function test_add_without_date_registered_should_use_current_date_and_time() {
		$visit_id = affiliate_wp()->visits->add( array(
			'affiliate_id' => self::$affiliates[0],
		) );

		$visit = affwp_get_visit( $visit_id );

		// Explicitly dropping seconds from the date strings for comparison.
		$expected = gmdate( 'Y-m-d H:i' );
		$actual   = gmdate( 'Y-m-d H:i', strtotime( $visit->date ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::add()
	 * @group dates
	 */
	public function test_add_with_date_registered_should_assume_local_time_and_remove_offset_on_add() {
		$visit_id = affiliate_wp()->visits->add( array(
			'affiliate_id' => self::$affiliates[0],
			'date'         => '05/04/2017',
		) );

		$visit = affwp_get_visit( $visit_id );

		$expected_date = gmdate( 'Y-m-d H:i', strtotime( '05/04/2017' ) - affiliate_wp()->utils->wp_offset );
		$actual        = gmdate( 'Y-m-d H:i', strtotime( $visit->date ) );

		$this->assertSame( $expected_date, $actual );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::update_visit()
	 */
	public function test_update_visit_with_context_under_50_chars_should_add_with_complete_sanitized_context() {
		affiliate_wp()->visits->update_visit( self::$visits[0], array(
			'context' => 'affwp-test'
		) );

		$result = affwp_get_visit( self::$visits[0] );

		$this->assertSame( 'affwp-test', $result->context );

		// Clean up.
		affiliate_wp()->visits->update_visit( self::$visits[0], array( 'context' => 'foo-0' ) );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::update_visit()
	 */
	public function test_update_visit_with_context_over_50_chars_should_add_with_first_50_chars_of_sanitized_context() {
		$context = rand_str( 55 );

		affiliate_wp()->visits->update_visit( self::$visits[0], array(
			'context' => $context
		) );

		$result = affwp_get_visit( self::$visits[0] );

		$this->assertSame( substr( $context, 0, 50 ), $result->context );

		// Clean up.
		affiliate_wp()->visits->update_visit( self::$visits[0], array( 'context' => 'foo-0' ) );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::update_visit()
	 * @group dates
	 */
	public function test_update_visit_with_invalid_date_string_should_not_update_date() {
		$visit = $this->factory->visit->create_and_get();

		affiliate_wp()->visits->update_visit( $visit->ID, array(
			'date' => 1
		) );

		$this->assertNotSame( 1, $visit->date );
	}

	/**
	 * @covers \Affiliate_WP_Visits_DB::update_visit()
	 * @group dates
	 */
	public function test_update_visit_with_valid_date_should_be_updated() {
		$visit = $this->factory->visit->create_and_get();

		affiliate_wp()->visits->update_visit( $visit->ID, array(
			'date' => '01/01/2001'
		) );

		$updated_visit = affwp_get_visit( $visit );
		$updated_date  = gmdate( 'Y-m-d H:i:s', strtotime( '01/01/2001' ) - affiliate_wp()->utils->wp_offset );

		$this->assertSame( $updated_date, $updated_visit->date );
	}
}
