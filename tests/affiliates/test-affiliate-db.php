<?php
namespace AffWP\Affiliate\Database;

use AffWP\Tests\UnitTestCase;

// Needed for actions to fire.
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/actions.php';

/**
 * Tests for Affiliate_WP_DB_Affiliates class
 *
 * @covers Affiliate_WP_DB_Affiliates
 * @group database
 * @group affiliates
 */
class Tests extends UnitTestCase {

	/**
	 * Users fixture.
	 *
	 * @access protected
	 * @var array
	 * @static
	 */
	public static $users = array();

	/**
	 * Affiliates fixture.
	 *
	 * @access protected
	 * @var array
	 * @static
	 */
	public static $affiliates = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		update_option( 'gmt_offset', -5 );
		affiliate_wp()->utils->_refresh_wp_offset();

		self::$users = parent::affwp()->user->create_many( 4 );

		foreach ( self::$users as $user_id ) {
			self::$affiliates[] = parent::affwp()->affiliate->create( array(
				'user_id' => $user_id
			) );
		}
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::$cache_group
	 */
	public function test_cache_group_should_be_affiliates() {
		$this->assertSame( 'affiliates', affiliate_wp()->affiliates->cache_group );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::$query_object_type
	 */
	public function test_query_object_type_should_be_AffWP_Affiliate() {
		$this->assertSame( 'AffWP\Affiliate', affiliate_wp()->affiliates->query_object_type );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::$primary_key
	 */
	public function test_primary_key_should_be_affiliate_id() {
		$this->assertSame( 'affiliate_id', affiliate_wp()->affiliates->primary_key );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::$REST
	 */
	public function test_REST_should_be_AffWP_Affiliate_REST_v1_Endpoints() {
		$this->assertSame( 'AffWP\Affiliate\REST\v1\Endpoints', get_class( affiliate_wp()->affiliates->REST ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_object()
	 */
	public function test_get_object_should_return_valid_object_when_passed_a_valid_affiliate_id() {
		$object = affiliate_wp()->affiliates->get_object( self::$affiliates[0] );
		$this->assertEquals( 'AffWP\Affiliate', get_class( $object ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_object()
	 */
	public function test_get_object_should_Return_false_when_passed_an_invalid_affiliate_id() {
		$this->assertFalse( affiliate_wp()->affiliates->get_object( 0 ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_object()
	 */
	public function test_get_object_should_return_valid_object_when_passed_a_valid_affiliate_object() {
		$object = affiliate_wp()->affiliates->get_object( affwp_get_affiliate( self::$affiliates[0] ) );

		$this->assertSame( 'AffWP\Affiliate', get_class( $object ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_columns()
	 */
	public function test_get_columns_should_return_all_columns() {
		$columns = affiliate_wp()->affiliates->get_columns();

		$expected = array(
			'affiliate_id'    => '%d',
			'user_id'         => '%d',
			'rate'            => '%s',
			'rate_type'       => '%s',
			'payment_email'   => '%s',
			'status'          => '%s',
			'earnings'        => '%s',
			'unpaid_earnings' => '%s',
			'referrals'       => '%d',
			'visits'          => '%d',
			'date_registered' => '%s',
		);

		$this->assertEqualSets( $expected, $columns );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_column_defaults()
	 */
	public function test_get_column_defaults_should_return_column_defaults() {
		$expected = array(
			'user_id'         => get_current_user_id(),
			'date_registered' => date( 'Y-m-d H:i:s' ),
		);

		$this->assertEqualSets( $expected, affiliate_wp()->affiliates->get_column_defaults() );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_should_return_array_of_Affiliate_objects_if_not_count_query() {
		$results = affiliate_wp()->affiliates->get_affiliates();

		$this->assertContainsOnlyType( 'AffWP\Affiliate', $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_should_return_integer_if_count_query() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(), $count = true );

		$this->assertTrue( is_numeric( $results ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_method_should_allow_searching_by_display_name() {
		$display_name = 'Foo';

		wp_update_user( array(
			'ID'           => self::$users[0],
			'display_name' => $display_name
		) );

		// Get affiliates based on the search term(s).
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'search' => 'foo',
		) );

		// Assert that results were found.
		$this->assertNotEmpty( $results );

		// Assert that the result we're looking for was found.
		$this->assertNotEmpty( wp_list_filter( $results, array( 'user_id' => self::$users[0] ) ) );

		// Clean up.
		wp_update_user( array(
			'ID'           => self::$users[0],
			'display_name' => '',
		) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_method_should_allow_searching_by_user_login() {
		$affiliate_id = $this->factory->affiliate->create( array(
			'user_id' => $user_id = $this->factory->user->create( array(
				'user_login' => 'foo_bar'
			) )
		) );

		// Get affiliates based on the search term(s).
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'search' => 'foo'
		) );

		// Assert that no results were found.
		$this->assertNotEmpty( $results );

		// Assert that the result we're looking for was found.
		$this->assertNotEmpty( wp_list_filter( $results, array( 'user_id' => $user_id ) ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_method_should_allow_searching_either_display_name_or_user_login() {
		$display_name = 'Bar Baz';
		$user_login   = 'foo_bar';

		$user = $this->factory->user->create( array(
			'display_name' => $display_name,
			'user_login'   => 'Scooby Doo'
		) );

		$user2 = $this->factory->user->create( array(
			'display_name' => 'Garfield',
			'user_login'   => $user_login
		) );

		$user3 = $this->factory->user->create( array(
			'display_name' => 'Nemo',
			'user_login'   => 'Dory'
		) );

		$affiliates = array();

		// Add affiliates.
		foreach ( array( $user, $user2, $user3 ) as $id ) {
			$affiliates[] = $this->factory->affiliate->create( array(
				'user_id' => $id
			) );
		}

		// Get affiliates based on the search term(s).
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'search' => 'bar'
		) );

		// Assert two results were found.
		$this->assertCount( 2, $results );

		// Assert that the first user was found.
		$this->assertNotEmpty( wp_list_filter( $results, array( 'user_id' => $user ) ) );

		// Assert that the second user was found.
		$this->assertNotEmpty( wp_list_filter( $results, array( 'user_id' => $user2 ) ) );

		// Assert that the third user wasn't found.
		$this->assertEmpty( wp_list_filter( $results, array( 'user_id' => $user3 ) ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_with_integer_user_id_should_return_affiliate_for_that_user() {
		// Query affiliates.
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'user_id' => self::$users[0],
			'fields'  => 'ids',
		) );

		$this->assertSame( self::$affiliates[0], $results[0] );
		$this->assertSame( self::$users[0], affwp_get_affiliate_user_id( $results[0] ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_with_array_of_user_ids_should_return_matching_affiliates() {
		// Query affiliates.
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'user_id' => self::$users,
			'fields'  => 'ids',
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_with_integer_affiliate_id_should_return_that_affiliate() {
		// Query affiliates.
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'affiliate_id' => self::$affiliates[0]
		) );

		$this->assertEqualSets( array( self::$affiliates[0] ), wp_list_pluck( $results, 'affiliate_id' ) );
		$this->assertEqualSets( array( self::$users[0] ), wp_list_pluck( $results, 'user_id' ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_with_array_of_affiliate_ids_should_return_matching_affiliates() {
		// Query affiliates.
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'affiliate_id' => self::$affiliates,
			'fields'       => 'ids',
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_number_should_return_that_number_if_available() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'number' => 2
		) );

		$this->assertCount( 2, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_number_all_should_return_all() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'number' => -1
		) );

		$this->assertCount( 4, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_offset_should_offset_that_number() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'offset' => 2,
			'fields' => 'ids',
			'order'  => 'ASC',
		) );

		$affiliates = array( self::$affiliates[2], self::$affiliates[3] );

		$this->assertEqualSets( $affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_exclude_with_single_affiliate_id_should_exclude_that_affiliate() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'exclude' => self::$affiliates[0],
			'fields'  => 'ids',
		) );

		$this->assertFalse( in_array( self::$affiliates[0], $results, true ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_exclude_with_multiple_affiliate_ids_should_exclude_those_affiliates() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'exclude' => array( self::$affiliates[0], self::$affiliates[1] ),
			'fields'  => 'ids',
		) );

		$this->assertEqualSets( array( self::$affiliates[2], self::$affiliates[3] ), $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_default_orderby_should_order_by_affiliate_id() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'order'  => 'ASC',
			'fields' => 'ids',
		) );

		// Order should be as created, 0, 1, 2, 3.
		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_status_should_order_by_status() {
		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[0],
			'status'       => 'active',
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[1],
			'status'       => 'rejected',
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[2],
			'status'       => 'inactive',
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => 'status',
			'order'   => 'ASC', // A-Z
			'fields'  => 'ids',
		) );

		$new_order = array(
			self::$affiliates[0],
			self::$affiliates[3],
			self::$affiliates[2],
			self::$affiliates[1],
		);

		// Order should be alphabetical: 0 (active), 3 (active), 2 (inactive), 1 (rejected).
		$this->assertEqualSets( $new_order, $results );

		// Clean up.
		foreach ( self::$affiliates as $affiliate ) {
			affwp_update_affiliate( array(
				'affiliate_id' => $affiliate,
				'status'       => 'active',
			) );
		}
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_date_should_order_by_registered_date() {
		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[0],
			'date_registered' => ( time() - WEEK_IN_SECONDS ),
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[1],
			'date_registered' => ( time() + WEEK_IN_SECONDS ),
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => 'date', // Default 'order' is DESC
			'fields'  => 'ids',
		) );

		$new_order = array(
			self::$affiliates[3],
			self::$affiliates[2],
			self::$affiliates[0],
			self::$affiliates[1]
		);

		// Order should be newest to oldest: 3, 2, 0, 1.
		$this->assertEqualSets( $new_order, $results );

		// Cleanup.
		foreach ( self::$affiliates as $affiliate ) {
			affwp_update_affiliate( array(
				'date_registered' => current_time( 'mysql' )
			) );
		}
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_name_should_order_by_user_display_name() {
		wp_update_user( array(
			'ID'           => self::$users[0],
			'display_name' => 'Bravo',
		) );

		wp_update_user( array(
			'ID'           => self::$users[1],
			'display_name' => 'Alpha',
		) );

		wp_update_user( array(
			'ID'           => self::$users[2],
			'display_name' => 'Charlie',
		) );

		wp_update_user( array(
			'ID'           => self::$users[3],
			'display_name' => 'Delta',
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => 'name', // Default 'order' is 'DESC'
			'fields'  => 'ids',
		) );

		$new_order = array(
			self::$affiliates[3],
			self::$affiliates[2],
			self::$affiliates[0],
			self::$affiliates[1]
		);

		// Order should be reverse alphabetical: 3 (Delta), 2 (Charlie), 0 (Beta), 1 (Alpha).
		$this->assertEqualSets( $new_order, self::$affiliates );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_username_should_order_by_user_login() {
		wp_update_user( array(
			'ID'         => self::$users[0],
			'user_login' => 'delta',
		) );

		wp_update_user( array(
			'ID'         => self::$users[1],
			'user_login' => 'foxtrot',
		) );

		wp_update_user( array(
			'ID'         => self::$users[2],
			'user_login' => 'echo',
		) );

		wp_update_user( array(
			'ID'         => self::$users[3],
			'user_login' => 'golf',
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => 'username',
			'order'   => 'ASC',
			'fields'  => 'ids',
		) );

		$new_order = array(
			self::$affiliates[0],
			self::$affiliates[2],
			self::$affiliates[1],
			self::$affiliates[3],
		);

		// Order should be 0 (delta), 2 (echo), 1 (foxtrot), 3 (golf).
		$this->assertEqualSets( $new_order, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_valid_referral_status_should_order_by_that_referral_status_count() {
		// Add 1 'unpaid' referral for affiliate 0.
		$referrals1 = $this->factory->referral->create( array(
			'affiliate_id' => self::$affiliates[0],
			'status'       => 'unpaid'
		) );

		// Add 3 'unpaid' referrals for affiliate 1.
		$referrals2 = $this->factory->referral->create_many( 3, array(
			'affiliate_id' => self::$affiliates[1],
			'status'       => 'unpaid'
		) );

		// Add 2 'paid' referrals for affiliate 2.
		$referrals3 = $this->factory->referral->create_many( 2, array(
			'affiliate_id' => self::$affiliates[2],
			'status'       => 'paid'
		) );

		// Add 2 'unpaid' referral for affiliate 3.
		$referrals4 = $this->factory->referral->create_many( 2, array(
			'affiliate_id' => self::$affiliates[3],
			'status'       => 'unpaid'
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => 'unpaid',
			'order'   => 'ASC', // Small to large.,
			'fields'  => 'ids',
		) );

		$new_order = array(
			self::$affiliates[2],
			self::$affiliates[0],
			self::$affiliates[3],
			self::$affiliates[1]
		);

		// Order should be 2 (zero paid), 0 (1 unpaid), 3 (2 unpaid), 1 (3 unpaid).
		$this->assertEqualSets( $new_order, $results );

		// Cleanup.
		$referrals = array_merge( (array) $referrals1, $referrals2, $referrals3, $referrals4 );

		$this->factory->referral->cleanup( $referrals );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_invalid_referral_status_should_default_to_order_by_primary_key() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => rand_str( 15 ),
			'fields'  => 'ids',
		) );

		// With invalid orderby, should return ordered by affiliate_id, descending.
		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_orderby_earnings_should_order_by_earnings() {
		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[0],
			'earnings'     => '20',
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[0],
			'earnings'     => '10',
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[2],
			'earnings'     => '30',
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby' => 'earnings',
			'order'   => 'ASC',
			'fields'  => 'ids',
		) );

		$new_order = array(
			self::$affiliates[3],
			self::$affiliates[1],
			self::$affiliates[0],
			self::$affiliates[2]
		);

		// Order should least to greatest: 3 (0), 1 (10), 0 (20), 2 (30).
		$this->assertEqualSets( $new_order, $results );

		// Cleanup.
		foreach ( self::$affiliates as $affiliate ) {
			affwp_update_affiliate( array(
				'affiliate_id' => $affiliate,
				'earnings'     => 0
			) );
		}
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_order_ASC_should_order_ascending() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'order'  => 'ASC', // default 'DESC'
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array_reverse( self::$affiliates ), $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 */
	public function test_get_affiliates_order_DESC_should_order_descending() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_fields_ids_should_return_an_array_of_ids_only() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => 'ids'
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_fields_ids_should_return_an_array_of_integer_ids() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => 'ids'
		) );

		$this->assertContainsOnlyType( 'integer', $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_with_no_fields_should_return_an_array_of_affiliate_objects() {
		$results = affiliate_wp()->affiliates->get_affiliates();

		$this->assertContainsOnlyType( 'AffWP\Affiliate', $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_with_multiple_valid_fields_should_return_an_array_of_stdClass_objects() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => array( 'affiliate_id', 'rate' )
		) );

		$this->assertContainsOnlyType( 'stdClass', $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_fields_valid_field_should_return_array_of_that_field_only() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => 'affiliate_id'
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_invalid_fields_arg_should_return_regular_Affiliate_object_results() {
		$affiliates = array_map( 'affwp_get_affiliate', self::$affiliates );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => 'foo'
		) );

		$this->assertEqualSets( $affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_fields_array_with_only_one_valid_field_should_return_an_array_of_those_values() {
		$result = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => array( 'user_id', 'foo' )
		) );

		$this->assertEqualSets( self::$users, $result );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_fields_array_with_multiple_valid_fields_should_return_objects_with_those_fields_only() {
		$fields = array( 'user_id', 'date_registered' );

		$result = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => $fields
		) );

		$object_vars = get_object_vars( $result[0] );

		$this->assertEqualSets( $fields, array_keys( $object_vars ) );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group database-fields
	 */
	public function test_get_affiliates_fields_array_with_multiple_valid_fields_should_return_array_of_stdClass_objects() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields' => array( 'user_id', 'rate' )
		) );

		$this->assertContainsOnlyType( 'stdClass', $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group dates
	 */
	public function test_get_affiliates_with_date_no_start_end_should_retrieve_affiliates_for_today() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'date_registered' => 'today',
			'fields'          => 'ids',
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group dates
	 */
	public function test_get_affiliates_with_today_affiliates_yesterday_date_no_start_end_should_return_empty() {
		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'date_registered' => 'yesterday',
			'fields'          => 'ids',
		) );

		$this->assertEqualSets( array(), $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group dates
	 */
	public function test_get_affiliates_date_start_should_only_retrieve_affiliates_created_after_that_date() {
		$affiliates = $this->factory->affiliate->create_many( 3, array(
			'date_registered' => '2016-01-01',
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields'          => 'ids',
			'date_registered' => array(
				'start' => '2016-01-02'
			),
		) );

		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers Affiliate_WP_DB_Affiliates::get_affiliates()
	 * @group dates
	 */
	public function test_get_affiliates_date_end_should_only_retrieve_affiliates_created_before_that_date() {
		$affiliate = $this->factory->affiliate->create( array(
			'date_registered' => '+1 day',
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields'          => 'ids',
			'date_registered' => array( 'end' => 'today' ),
		) );

		// Should catch all but the one just created +1 day.
		$this->assertEqualSets( self::$affiliates, $results );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::count()
	 */
	public function test_count_should_count_based_on_query_args() {
		$this->assertSame( 4, affiliate_wp()->affiliates->count() );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_affiliate_name()
	 */
	public function test_get_affiliate_name_with_invalid_affiliate_id_should_return_null() {
		$this->assertNull( affiliate_wp()->affiliates->get_affiliate_name( 0 ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_affiliate_name()
	 */
	public function test_get_affiliate_name_with_valid_affiliate_id_should_return_the_affiliate_name() {
		$user = get_userdata( affwp_get_affiliate_user_id( self::$affiliates[0] ) );

		$this->assertSame( $user->data->display_name, affiliate_wp()->affiliates->get_affiliate_name( self::$affiliates[0] ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_affiliate_name()
	 */
	public function test_get_affiliate_name_with_invalid_affiliate_object_should_return_null() {
		$this->assertNull( affiliate_wp()->affiliates->get_affiliate_name( new \stdClass() ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::get_affiliate_name()
	 */
	public function test_get_affiliate_name_with_valid_affiliate_object_should_return_the_affiliate_name() {
		$user      = get_userdata( affwp_get_affiliate_user_id( self::$affiliates[0] ) );
		$affiliate = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertSame( $user->data->display_name, affiliate_wp()->affiliates->get_affiliate_name( $affiliate ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::affiliate_exists()
	 */
	public function test_affiliate_exists_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affiliate_wp()->affiliates->affiliate_exists( 0 ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::affiliate_exists()
	 */
	public function test_affiliate_exists_with_valid_affiliate_id_should_return_true() {
		$this->assertTrue( affiliate_wp()->affiliates->affiliate_exists( self::$affiliates[0] ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::affiliate_exists()
	 */
	public function test_affiliate_exists_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affiliate_wp()->affiliates->affiliate_exists( new \stdClass() ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::affiliate_exists()
	 */
	public function test_affiliate_exists_with_valid_affiliate_object_should_return_true() {
		$object = affwp_get_affiliate( self::$affiliates[1] );

		$this->assertTrue( affiliate_wp()->affiliates->affiliate_exists( $object ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::add()
	 */
	public function test_add_with_existing_affiliate_user_id_should_return_false() {
		$this->assertFalse( affiliate_wp()->affiliates->add( array(
			'user_id' => self::$users[0]
		) ) );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::add()
	 */
	public function test_add_successful_should_return_id_of_the_new_affiliate() {
		$affiliate = affiliate_wp()->affiliates->add( array(
			'user_id' => $this->factory->user->create()
		) );

		$results = affiliate_wp()->affiliates->get_affiliates( array(
			'fields'  => 'ids',
			'number'  => 1,
			'orderby' => 'affiliate_id'
		) );

		$this->assertSame( $affiliate, $results[0] );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::add()
	 * @group dates
	 */
	public function test_add_without_date_registered_should_use_current_date_and_time() {
		$affiliate_id = affiliate_wp()->affiliates->add( array(
			'user_id' => $this->factory->user->create()
		) );

		$affiliate = affwp_get_affiliate( $affiliate_id );

		// Explicitly dropping seconds from the date strings for comparison.
		$expected = gmdate( 'Y-m-d H:i' );
		$actual   = gmdate( 'Y-m-d H:i', strtotime( $affiliate->date_registered ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::add()
	 * @group dates
	 */
	public function test_add_with_date_registered_should_assume_local_time_and_remove_offset_on_add() {
		$affiliate_id = affiliate_wp()->affiliates->add( array(
			'user_id'         => $this->factory->user->create(),
			'date_registered' => '05/04/2017',
		) );

		$affiliate = affwp_get_affiliate( $affiliate_id );

		$expected_date = gmdate( 'Y-m-d H:i', strtotime( '05/04/2017' ) - affiliate_wp()->utils->wp_offset );
		$actual        = gmdate( 'Y-m-d H:i', strtotime( $affiliate->date_registered ) );

		$this->assertSame( $expected_date, $actual );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::add()
	 */
	public function test_add_without_website_url_should_not_change_the_user_url() {
		$affiliate_id = affiliate_wp()->affiliates->add( array(
			'user_id' => $user_id = $this->factory->user->create()
		) );

		$user_data = get_user_by( 'id', $user_id );

		$this->assertSame( '', $user_data->user_url );
	}

	/**
	 * @covers \Affiliate_WP_DB_Affiliates::add()
	 */
	public function test_add_with_website_url_should_set_that_url() {
		$website_url = 'https://affiliatewp.com/awesome';

		$affiliate_id = affiliate_wp()->affiliates->add( array(
			'user_id'     => $user_id = $this->factory->user->create(),
			'website_url' => $website_url
		) );

		$user_data = get_user_by( 'id', $user_id );

		$this->assertSame( $website_url, $user_data->user_url );
	}

}
