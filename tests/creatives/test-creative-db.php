<?php
namespace AffWP\Creative\Database;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Creatives_DB class
 *
 * @covers Affiliate_WP_Creatives_DB
 * @group database
 * @group creatives
 */
class Tests extends UnitTestCase {

	/**
	 * Creatives fixture.
	 *
	 * @access public
	 * @var array
	 * @static
	 */
	public static $creatives = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$creatives = parent::affwp()->creative->create_many( 4 );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::__construct()
	 */
	public function test_creatives_network_wide_table_name_should_be_affiliate_wp_creatives() {
		if ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
			$this->assertEquals( 'affiliate_wp_creatives', affiliate_wp()->creatives->table_name );
		}
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::__construct()
	 */
	public function test_creatives_not_network_wide_table_name_should_be_prefix_affiliate_wp_creatives() {
		if ( ! defined( 'AFFILIATE_WP_NETWORK_WIDE' ) ) {
			global $wpdb;

			$this->assertEquals( $wpdb->prefix . 'affiliate_wp_creatives', affiliate_wp()->creatives->table_name );
		}
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::$cache_group
	 */
	public function test_cache_group_should_be_creatives() {
		$this->assertSame( 'creatives', affiliate_wp()->creatives->cache_group );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::$query_object_type
	 */
	public function test_query_object_type_should_be_AffWP_Creative() {
		$this->assertSame( 'AffWP\Creative', affiliate_wp()->creatives->query_object_type );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::$primary_key
	 */
	public function test_primary_key_should_be_creative_id() {
		$this->assertSame( 'creative_id', affiliate_wp()->creatives->primary_key );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::$REST
	 */
	public function test_REST_should_be_AffWP_Creative_REST_v1_Endpoints() {
		$this->assertSame( 'AffWP\Creative\REST\v1\Endpoints', get_class( affiliate_wp()->creatives->REST ) );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::get_object()
	 */
	public function test_get_object_with_invalid_creative_id_should_return_false() {
		$this->assertFalse( affiliate_wp()->creatives->get_object( 0 ) );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::get_object()
	 */
	public function test_get_object_with_valid_creative_id_should_return_a_valid_object() {
		$this->assertInstanceOf( 'AffWP\Creative', affiliate_wp()->creatives->get_object( self::$creatives[0] ) );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::get_object()
	 */
	public function test_get_object_with_valid_creative_object_should_return_a_valid_object() {
		$object = affwp_get_creative( self::$creatives[1] );

		$this->assertInstanceOf( 'AffWP\Creative', affiliate_wp()->creatives->get_object( $object ) );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::get_columns()
	 */
	public function test_get_columns_should_return_all_columns() {
		$expected = array(
			'creative_id'  => '%d',
			'name'         => '%s',
			'description'  => '%s',
			'url'          => '%s',
			'text'         => '%s',
			'image'        => '%s',
			'status'       => '%s',
			'date'         => '%s',
		);

		$this->assertEqualSets( $expected, affiliate_wp()->creatives->get_columns() );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::get_column_defaults()
	 */
	public function test_get_column_defaults_should_return_all_column_defaults() {
		$expected = array(
			'date' => date( 'Y-m-d H:i:s' ),
		);

		$this->assertEqualSets( $expected, affiliate_wp()->creatives->get_column_defaults() );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_should_return_array_of_Creative_objects_if_not_count_query() {
		$results = affiliate_wp()->creatives->get_creatives();

		// Check a random creative.
		$this->assertContainsOnlyType( 'AffWP\Creative', $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_should_return_integer_if_count_query() {
		$results = affiliate_wp()->creatives->get_creatives( array(), $count = true );

		$this->assertTrue( is_numeric( $results ) );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_fields_ids_should_return_an_array_of_ids_only() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'ids'
		) );

		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_fields_with_valid_field_should_return_array_of_that_field() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'creative_id'
		) );

		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_invalid_fields_arg_should_return_regular_Creative_object_results() {
		$creatives = array_map( 'affwp_get_creative', self::$creatives );

		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'foo'
		) );

		$this->assertEqualSets( $creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_fields_ids_should_return_an_array_of_integer_ids() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'ids'
		) );

		$this->assertContainsOnlyType( 'integer', $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_with_no_fields_should_return_an_array_of_affiliate_objects() {
		$results = affiliate_wp()->creatives->get_creatives();

		$this->assertContainsOnlyType( 'AffWP\Creative', $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_with_multiple_valid_fields_should_return_an_array_of_stdClass_objects() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => array( 'creative_id', 'name' )
		) );

		$this->assertContainsOnlyType( 'stdClass', $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group database-fields
	 */
	public function test_get_creatives_fields_array_with_multiple_valid_fields_should_return_objects_with_those_fields_only() {
		$fields = array( 'creative_id', 'name' );

		$result = affiliate_wp()->creatives->get_creatives( array(
			'fields' => $fields
		) );

		$object_vars = get_object_vars( $result[0] );

		$this->assertEqualSets( $fields, array_keys( $object_vars ) );

	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_single_creative_id_should_return_only_that_creative() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'creative_id' => self::$creatives[2],
			'fields'      => 'ids',
		) );

		$this->assertEqualSets( array( self::$creatives[2] ), $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_multiple_creative_ids_should_return_only_those_creatives() {
		$creatives = array( self::$creatives[1], self::$creatives[3] );

		$results = affiliate_wp()->creatives->get_creatives( array(
			'creative_id' => $creatives,
			'fields'      => 'ids',
		) );

		$this->assertEqualSets( $creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_with_no_status_should_return_results_for_all_statuses() {
		$creative = $this->factory->creative->create( array(
			'status' => 'inactive'
		) );

		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array_merge( self::$creatives, array( $creative ) ), $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_with_valid_status_should_return_results_only_for_that_status() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'status' => 'active',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_with_invalid_status_should_return_no_results() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'status' => 'foo'
		) );

		$this->assertEqualSets( array(), $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_default_orderby_should_be_by_primary_key() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$creatives, $results );
		$this->assertTrue( $results[3] > $results[2] );
		$this->assertTrue( $results[2] > $results[1] );
		$this->assertTrue( $results[1] > $results[0] );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 */
	public function test_get_creatives_default_order_should_be_ascending() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group dates
	 */
	public function test_get_creatives_with_date_no_start_end_should_retrieve_creatives_for_today() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'date'   => 'today',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group dates
	 */
	public function test_get_creatives_with_today_creatives_yesterday_date_no_start_end_should_return_empty() {
		$results = affiliate_wp()->creatives->get_creatives( array(
			'date'   => 'yesterday',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array(), $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group dates
	 */
	public function test_get_creatives_date_start_should_only_retrieve_creatives_created_after_that_date() {
		$creatives = $this->factory->creative->create_many( 3, array(
			'date' => '2016-01-01',
		) );

		$results = affiliate_wp()->creatives->get_creatives( array(
			'date'   => array(
				'start' => '2016-01-02'
			),
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers Affiliate_WP_Creatives_DB::get_creatives()
	 * @group dates
	 */
	public function test_get_creatives_date_end_should_only_retrieve_creatives_created_before_that_date() {
		$creative = $this->factory->creative->create( array(
			'date' => '+1 day',
		) );

		$results = affiliate_wp()->creatives->get_creatives( array(
			'date'   => array( 'end' => 'today' ),
			'fields' => 'ids',
		) );

		// Should catch all but the one just created +1 day.
		$this->assertEqualSets( self::$creatives, $results );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::count()
	 */
	public function test_count_should_count_creatives() {
		$this->assertSame( 4, affiliate_wp()->creatives->count() );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::count()
	 */
	public function test_count_with_args_should_count_those_creatives() {
		$original_creative = affwp_get_creative( self::$creatives[0] );

		affwp_update_creative( array(
			'creative_id' => self::$creatives[0],
			'status'      => 'foo'
		) );

		$this->assertSame( 1, affiliate_wp()->creatives->count( array( 'status' => 'foo' ) ) );

		// Clean up.
		affwp_update_creative( array(
			'creative_id' => self::$creatives[0],
			'status'      => $original_creative->status
		) );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::add()
	 */
	public function test_add_should_always_return_the_creative_id() {
		$result = affiliate_wp()->creatives->add( array(
			'these' => 'args',
			'are'   => 'absurd',
		) );

		$this->assertNotFalse( $result );
		$this->assertTrue( is_numeric( $result ) );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::add()
	 * @group dates
	 */
	public function test_add_without_date_registered_should_use_current_date_and_time() {
		$creative_id = affiliate_wp()->creatives->add();

		$creative = affwp_get_creative( $creative_id );

		// Explicitly dropping seconds from the date strings for comparison.
		$expected = gmdate( 'Y-m-d H:i' );
		$actual   = gmdate( 'Y-m-d H:i', strtotime( $creative->date ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_Creatives_DB::add()
	 * @group dates
	 */
	public function test_add_with_date_registered_should_assume_local_time_and_remove_offset_on_add() {
		$creative_id = affiliate_wp()->creatives->add( array(
			'date' => '05/04/2017',
		) );

		$creative = affwp_get_creative( $creative_id );

		$expected_date = gmdate( 'Y-m-d H:i', strtotime( '05/04/2017' ) - affiliate_wp()->utils->wp_offset );
		$actual        = gmdate( 'Y-m-d H:i', strtotime( $creative->date ) );

		$this->assertSame( $expected_date, $actual );
	}

}
