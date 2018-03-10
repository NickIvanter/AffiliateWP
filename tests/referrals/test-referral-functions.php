<?php
namespace AffWP\Referral\Functions;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Referral functions in referral-functions.php.
 *
 * @group referrals
 * @group functions
 */
class Tests extends UnitTestCase {

	/**
	 * Test affiliate ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected static $_affiliate_id = 0;

	/**
	 * Test referral ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected static $_referral_id = 0;

	/**
	 * Test visit ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected static $_visit_id = 0;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {

		$args = array(
			'user_id'         => 1,
			'earnings'        => 0,
			'unpaid_earnings' => 0,
		);

		self::$_affiliate_id = parent::affwp()->affiliate->create( $args );


		$args = array(
			'affiliate_id' => self::$_affiliate_id,
			'amount'       => 10,
			'status'       => 'pending',
			'context'      => 'tests',
			'custom'       => 4,
			'reference'    => 5
		);

		self::$_referral_id = parent::affwp()->referral->create( $args );

		$args = array(
			'affiliate_id' => self::$_affiliate_id
		);

		self::$_visit_id = parent::affwp()->visit->create( $args );
	}

	/**
	 * Runs during test teardown.
	 */
	public function tearDown() {
		affiliate_wp()->settings->set( array(
			'referral_url_blacklist' => '',
			'thousands_separator'    => ',',
			'decimal_separator'      => '.',
		) );

		parent::tearDown();
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_no_referral_should_return_false() {
		$this->assertFalse( affwp_get_referral() );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_invalid_referral_object_should_return_false() {
		$this->assertFalse( affwp_get_referral( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_valid_referral_object_should_return_a_referral_object() {
		$referral = affwp_get_referral( self::$_referral_id );

		$this->assertInstanceOf( 'AffWP\Referral', $referral );
		$this->assertEquals( self::$_referral_id, $referral->referral_id );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_invalid_referral_id_should_return_false() {
		$this->assertFalse( affwp_get_referral( 0 ) );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_string_custom_value_should_retrieve_the_string_unserialized() {
		$referral = $this->factory->referral->create_and_get( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => 'foobar',
		) );

		$this->assertFalse( is_serialized( $referral->custom ) );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_array_custom_value_should_retrieve_the_array_unserialized() {
		$referral = $this->factory->referral->create_and_get( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => array( 'foo', 'bar' ),
		) );

		$this->assertFalse( is_serialized( $referral->custom ) );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_non_whitelisted_class_serialized_in_custom_should_retrieve_empty_custom() {
		$referral = $this->factory->referral->create_and_get( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => $this->factory->affiliate->create_and_get()
		) );

		$this->assertSame( '', $referral->custom );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_array_non_whitelisted_custom_should_retrieve_empty_custom() {
		$referral = $this->factory->referral->create_and_get( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => array( $this->factory->affiliate->create_and_get() )
		) );

		$this->assertSame( '', $referral->custom );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_stdclass_custom_should_retrieve_the_serialized_object() {
		$object = new \stdClass();
		$object->foo = 'bar';

		$referral = $this->factory->referral->create_and_get( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => $object,
		) );

		$this->assertEquals( $object, $referral->custom );
	}

	/**
	 * @covers ::affwp_get_referral()
	 */
	public function test_get_referral_with_array_stdclass_custom_should_retrieve_the_serialized_object() {
		$object = new \stdClass();
		$object->foo = 'bar';

		$referral = $this->factory->referral->create_and_get( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => array( $object ),
		) );

		$this->assertEquals( array( $object ), $referral->custom );
	}

	/**
	 * @covers ::affwp_get_referral_status()
	 */
	public function test_get_referral_status_with_invalid_referral_id_should_return_false() {
		$this->assertFalse( affwp_get_referral_status( 0 ) );
	}

	/**
	 * @covers ::affwp_get_referral_status()
	 */
	public function test_get_referral_status_with_valid_referral_id_should_return_a_status() {
		$this->assertEquals( 'pending', affwp_get_referral_status( self::$_referral_id ) );
	}

	/**
	 * @covers ::affwp_get_referral_status()
	 */
	public function test_get_referral_status_with_invalid_referral_object_should_return_false() {
		$this->assertFalse( affwp_get_referral_status( affwp_get_referral() ) );
	}

	/**
	 * @covers ::affwp_get_referral_status()
	 */
	public function test_get_referral_status_With_valid_referral_object_should_return_a_status() {
		$referral = affwp_get_referral( self::$_referral_id );

		$this->assertEquals( 'pending', affwp_get_referral_status( $referral ) );
	}

	/**
	 * @covers ::affwp_get_referral_status_label()
	 */
	public function test_get_referral_status_label_with_no_referral_should_return_false() {
		$this->assertFalse( affwp_get_referral_status_label( null ) );
	}

	/**
	 * @covers ::affwp_get_referral_status_label()
	 */
	public function test_get_referral_status_label_with_invalid_referral_id_should_return_false() {
		$this->assertFalse( affwp_get_referral_status_label( 0 ) );
	}

	/**
	 * @covers ::affwp_get_referral_status_label()
	 */
	public function test_get_referral_status_label_with_valid_referral_id_should_return_the_label() {
		$this->assertEquals( 'Pending', affwp_get_referral_status_label( self::$_referral_id ) );
	}

	/**
	 * @covers ::affwp_get_referral_status_label()
	 */
	public function test_get_referral_status_label_with_invalid_referral_object_should_return_false() {
		$this->assertFalse( affwp_get_referral_status_label( affwp_get_referral() ) );
	}

	/**
	 * @covers ::affwp_get_referral_status_label()
	 */
	public function test_get_referral_status_label_with_valid_referral_object_should_return_the_label() {
		$referral = affwp_get_referral( self::$_referral_id );

		$this->assertEquals( 'Pending', affwp_get_referral_status_label( $referral ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_get_referral_status_with_no_referral_should_return_false() {
		$this->assertFalse( affwp_get_referral_status( null ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_invalid_referral_id_should_return_false() {
		$this->assertFalse( affwp_set_referral_status( 0 ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_valid_referral_id_and_valid_status_should_return_true() {
		$this->assertTrue( affwp_set_referral_status( self::$_referral_id , 'unpaid' ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_invalid_referral_object_should_return_false() {
		$this->assertFalse( affwp_set_referral_status( affwp_get_referral() ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_valid_referral_object_and_valid_status_should_return_true() {
		$referral = affwp_get_referral( self::$_referral_id );

		$this->assertTrue( affwp_set_referral_status( $referral, 'unpaid' ) );
	}

	/**
	 * @covers ::affwp_get_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_should_update_status() {
		$this->assertEquals( 'pending', affwp_get_referral_status( self::$_referral_id ) );

		affwp_set_referral_status( self::$_referral_id, 'unpaid' );
		$this->assertEquals( 'unpaid', affwp_get_referral_status( self::$_referral_id ) );

		affwp_set_referral_status( self::$_referral_id, 'rejected' );
		$this->assertEquals( 'rejected', affwp_get_referral_status( self::$_referral_id ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_paid_should_increase_earnings() {
		$referral     = affwp_get_referral( self::$_referral_id );
		$old_earnings = affwp_get_affiliate_earnings( self::$_affiliate_id );

		affwp_set_referral_status( $referral->referral_id, 'paid' );

		$new_earnings = affwp_get_affiliate_earnings( self::$_affiliate_id );

		$this->assertEquals( $old_earnings += $referral->amount, $new_earnings );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_paid_should_increase_referral_count() {
		$referral     = affwp_get_referral( self::$_referral_id );
		$old_count    = affwp_get_affiliate_referral_count( self::$_affiliate_id );

		affwp_set_referral_status( $referral, 'paid' );

		$new_count = affwp_get_affiliate_referral_count( self::$_affiliate_id );

		$this->assertEquals( ++$old_count, $new_count );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_unpaid_should_increase_unpaid_earnings() {
		$referral            = affwp_get_referral( self::$_referral_id );
		$old_unpaid_earnings = affwp_get_affiliate_unpaid_earnings( self::$_affiliate_id );

		// Should be zero to start.
		$this->assertSame( floatval( 0 ), $old_unpaid_earnings );

		// Switch to paid so we can switch from paid to unpaid.
		affwp_set_referral_status( $referral, 'paid' );

		affwp_set_referral_status( $referral, 'unpaid' );

		$new_unpaid_earnings = affwp_get_affiliate_unpaid_earnings( self::$_affiliate_id );

		$this->assertSame( floatval( $referral->amount ) + $old_unpaid_earnings, $new_unpaid_earnings );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_not_unpaid_old_status_unpaid_should_decrease_unpaid_earnings() {
		// Start off with an unpaid referral.
		affwp_set_referral_status( self::$_referral_id, 'unpaid' );

		// Switch to pending.
		affwp_set_referral_status( self::$_referral_id, 'pending' );

		$this->assertSame( floatval( 0 ), affwp_get_affiliate_unpaid_earnings( self::$_affiliate_id ) );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_unpaid_old_status_pending_should_be_marked_accepted() {
		affiliate_wp()->referrals->update( self::$_referral_id, array(
			'visit_id' => self::$_visit_id
		) );

		$referral = affwp_get_referral( self::$_referral_id );

		$this->assertEquals( 'pending', affwp_get_referral_status( $referral ) );

		affwp_set_referral_status( $referral, 'unpaid' );
		$this->assertEquals( 'unpaid', affwp_get_referral_status( $referral ) );

		$visit = affiliate_wp()->visits->get( $referral->visit_id );
		$this->assertEquals( $referral->referral_id, $visit->referral_id );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_not_paid_old_status_paid_should_decrease_earnings() {
		// Inflate earnings.
		affwp_increase_affiliate_earnings( self::$_affiliate_id, '30' );

		// Start with 'paid' (default 'pending').
		affwp_set_referral_status( self::$_referral_id, 'paid' );

		$referral_amount = affwp_get_referral( self::$_referral_id )->amount;
		$old_earnings    = affwp_get_affiliate_earnings( self::$_affiliate_id );

		// Switch to 'unpaid'.
		affwp_set_referral_status( self::$_referral_id, 'unpaid' );

		$new_earnings = affwp_get_affiliate_earnings( self::$_affiliate_id );

		// New earnings = $old_earnings minus the deducted referral amount.
		$this->assertEquals( $old_earnings - $referral_amount, $new_earnings );
	}

	/**
	 * @covers ::affwp_set_referral_status()
	 * @group referrals-status
	 */
	public function test_set_referral_status_with_new_status_not_paid_old_status_paid_should_decrease_referral_count() {
		// Inflate referral count.
		affwp_increase_affiliate_referral_count( self::$_affiliate_id );
		affwp_increase_affiliate_referral_count( self::$_affiliate_id );

		// Start with 'paid' (default 'pending').
		affwp_set_referral_status( self::$_referral_id, 'paid' );

		$referral = affwp_get_referral( self::$_referral_id );
		$old_count = affwp_get_affiliate_referral_count( self::$_affiliate_id );

		// Switch to 'unpaid'.
		affwp_set_referral_status( $referral, 'unpaid' );

		$new_count = affwp_get_affiliate_referral_count( self::$_affiliate_id );

		// New count = $old_count minus 1.
		$this->assertEquals( --$old_count, $new_count );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_empty_user_id_empty_affiliate_should_return_zero() {
		$this->assertSame( 0, affwp_add_referral( null ) );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_invalid_affiliate_id_should_return_zero() {
		$this->assertSame( 0, affwp_add_referral( array( 'affiliate_id' => 999999999999 ) ) );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_should_default_to_pending_status() {
		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id
		) );

		$this->assertEquals( 'pending', affwp_get_referral_status( $referral_id ) );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_should_return_referral_id_on_success() {
		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id
		) );

		$this->assertInstanceOf( 'AffWP\Referral', affwp_get_referral( $referral_id ) );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_un_associated_visit_id_should_store_visit_id() {
		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id,
			'visit_id'     => self::$_visit_id,
		) );

		$result = affwp_get_referral( $referral_id );

		$this->assertSame( self::$_visit_id, $result->visit_id );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_already_associated_visit_id_should_not_store_visit_id() {
		// Associate the visit.
		affiliate_wp()->referrals->update_referral( self::$_referral_id, array(
			'visit_id' => self::$_visit_id
		) );

		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id,
			'visit_id'     => self::$_visit_id,
		) );

		$result = affwp_get_referral( $referral_id );

		$this->assertNotSame( self::$_visit_id, $result->visit_id );
		$this->assertSame( 0, $result->visit_id );

		// Clean up.
		affiliate_wp()->referrals->update_referral( self::$_referral_id, array( 'visit_id' => 0 ) );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_empty_custom_field_should_store_empty_custom_value() {
		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => '',
		) );

		$result = affwp_get_referral( $referral_id );

		$this->assertSame( '', $result->custom );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_string_custom_value_should_store_that_value() {
		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => 'foo'
		) );

		$result = affwp_get_referral( $referral_id );

		$this->assertSame( 'foo', $result->custom );
	}

	/**
	 * @covers ::affwp_add_referral()
	 */
	public function test_add_referral_with_array_custom_value_should_store_that_array() {
		$custom = array( 'foo', 'bar' );

		$referral_id = affwp_add_referral( array(
			'affiliate_id' => self::$_affiliate_id,
			'custom'       => $custom,
		) );

		$result = affwp_get_referral( $referral_id );

		$this->assertEqualSets( $custom, $result->custom );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_no_referral_should_return_false() {
		$this->assertFalse( affwp_delete_referral( null ) );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_invalid_referral_id_should_return_false() {
		$this->assertFalse( affwp_delete_referral( 0 ) );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_valid_referral_id_should_return_true() {
		$this->assertTrue( affwp_delete_referral( self::$_referral_id ) );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_invalid_referral_object_should_return_false() {
		$this->assertFalse( affwp_delete_referral( affwp_get_referral() ) );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_valid_referral_object_should_return_true() {
		$referral = affwp_get_referral( self::$_referral_id );

		$this->assertTrue( affwp_delete_referral( $referral ) );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_status_paid_should_decrease_affiliate_earnings() {
		// Needs to be paid.
		affwp_set_referral_status( self::$_referral_id, 'paid' );

		// Inflate earnings.
		affwp_increase_affiliate_earnings( self::$_affiliate_id, '10' );

		$old_earnings = affwp_get_affiliate_earnings( self::$_affiliate_id );

		affwp_delete_referral( self::$_referral_id );

		$new_earnings = affwp_get_affiliate_earnings( self::$_affiliate_id );

		$this->assertSame( $old_earnings - 10, $new_earnings );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_status_paid_should_decrease_referral_count() {
		// Needs to be paid.
		affwp_set_referral_status( self::$_referral_id, 'paid' );

		$old_count = affwp_get_affiliate_referral_count( self::$_affiliate_id );

		affwp_delete_referral( self::$_referral_id );

		$new_count = affwp_get_affiliate_referral_count( self::$_affiliate_id );

		$this->assertEquals( --$old_count, $new_count );
	}

	/**
	 * @covers ::affwp_delete_referral()
	 */
	public function test_delete_referral_with_status_unpaid_should_decrease_unpaid_earnings() {
		// Needs to be unpaid.
		affwp_set_referral_status( self::$_referral_id, 'unpaid' );

		$old_unpaid_earnings = affwp_get_affiliate_unpaid_earnings( self::$_affiliate_id );

		affwp_delete_referral( self::$_referral_id );

		$new_unpaid_earnings = affwp_get_affiliate_unpaid_earnings( self::$_affiliate_id );

		$this->assertSame( $old_unpaid_earnings - 10, $new_unpaid_earnings );
	}

	/**
	 * @covers ::affwp_get_banned_urls()
	 * @group settings
	 */
	public function test_get_banned_urls_should_return_urls_entered_one_per_line() {
		$urls = array( 'https://google.com', 'http://google.de', 'http://google.co.nz' );

		affiliate_wp()->settings->set( array( 'referral_url_blacklist' => implode( "\n", $urls ) ) );

		$result = affwp_get_banned_urls();

		$this->assertEqualSets( $urls, $result );
	}

	/**
	 * @covers ::affwp_get_banned_urls()
	 * @group settings
	 */
	public function test_get_banned_urls_should_return_empty_array_if_empty() {
		$this->assertEmpty( affwp_get_banned_urls() );
	}

	/**
	 * @covers ::affwp_is_url_banned()
	 * @group settings
	 */
	public function test_is_url_banned_should_return_false_if_no_urls_are_set() {
		$this->assertFalse( affwp_is_url_banned( WP_TESTS_DOMAIN ) );
	}

	/**
	 * @covers ::affwp_is_url_banned()
	 * @group settings
	 */
	public function test_is_url_banned_should_return_false_if_url_not_in_blacklist() {
		affiliate_wp()->settings->set( array( 'referral_url_blacklist' => 'https://google.com' ) );

		$this->assertFalse( affwp_is_url_banned( WP_TESTS_DOMAIN ) );
	}

	/**
	 * @covers ::affwp_is_url_banned()
	 * @group settings
	 */
	public function test_is_url_banned_should_return_true_if_url_is_in_blacklist() {
		affiliate_wp()->settings->set( array( 'referral_url_blacklist' => WP_TESTS_DOMAIN ) );

		$this->assertTrue( affwp_is_url_banned( WP_TESTS_DOMAIN ) );
	}

	/**
	 * @covers ::affwp_calc_referral_amount()
	 */
	public function test_calc_referral_amount_should_normalize_a_percentage_rate_using_default_period_decimal_separator() {
		$result = affwp_calc_referral_amount( 10, self::$_affiliate_id, 0, '0.50' );

		$this->assertSame( '0.05', $result );
	}

	/**
	 * @covers ::affwp_calc_referral_amount()
	 */
	public function test_calc_referral_amount_should_normalize_a_percentage_rate_using_comma_decimal_separator() {
		$affiliate_id = $this->factory->affiliate->create();

		affiliate_wp()->settings->set( array(
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
		) );

		$result = affwp_calc_referral_amount( 10, $affiliate_id, 0, '0,50' );

		$this->assertSame( '0.05', $result );
	}

	/**
	 * @covers ::affwp_calc_referral_amount()
	 */
	public function test_calc_referral_amount_should_normalize_a_flat_rate_using_default_period_decimal_separator() {
		$affiliate_id = $this->factory->affiliate->create( array(
			'rate_type' => 'flat',
		) );

		$result = affwp_calc_referral_amount( 10, $affiliate_id, 0, '0.50' );

		$this->assertSame( '0.50', $result );
	}

	/**
	 * @covers ::affwp_calc_referral_amount()
	 */
	public function test_calc_referral_amount_should_normalize_a_flat_rate_using_comma_decimal_separator() {
		affiliate_wp()->settings->set( array(
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$_affiliate_id,
			'rate_type'    => 'flat',
		) );

		$result = affwp_calc_referral_amount( 10, self::$_affiliate_id, 0, '0,50' );

		$this->assertSame( '0.50', $result );

		// Clean up.
		affwp_update_affiliate( array(
			'affiliate_id' => self::$_affiliate_id,
			'rate_type'    => 'percentage',
		) );
	}
}
