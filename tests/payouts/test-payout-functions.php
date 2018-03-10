<?php
namespace AffWP\Payout\Functions;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Payout functions in payout-functions.php.
 *
 * @group payouts
 * @group functions
 */
class Tests extends UnitTestCase {

	/**
	 * Affiliate fixture.
	 *
	 * @access protected
	 * @var int
	 */
	protected static $affiliate_id = 0;

	/**
	 * Referrals fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $referrals = array();

	/**
	 * Payout fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $payouts = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$affiliate_id = parent::affwp()->affiliate->create();

		self::$referrals = parent::affwp()->referral->create_many( 4, array(
			'affiliate_id' => self::$affiliate_id,
			'status'       => 'paid',
		) );

		self::$payouts = parent::affwp()->payout->create_many( 4, array(
			'affiliate_id' => self::$affiliate_id,
			'referrals'    => self::$referrals,
		) );
	}

	/**
	 * @covers ::affwp_get_payout()
	 */
	public function test_get_payout_with_an_invalid_payout_id_should_return_false() {
		$this->assertFalse( affwp_get_payout( 0 ) );
	}

	/**
	 * @covers ::affwp_get_payout()
	 */
	public function test_get_payout_with_a_valid_payout_id_should_return_a_payout_object() {
		$this->assertInstanceOf( 'AffWP\Affiliate\Payout', affwp_get_payout( self::$payouts[0] ) );
	}

	/**
	 * @covers ::affwp_get_payout()
	 */
	public function test_get_payout_with_an_invalid_payout_object_should_return_false() {
		$this->assertFalse( affwp_get_payout( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_payout()
	 */
	public function test_get_payout_with_a_valid_payout_object_should_return_a_payout_object() {
		$payout = affwp_get_payout( self::$payouts[0] );

		$this->assertInstanceOf( 'AffWP\Affiliate\Payout', affwp_get_payout( $payout ) );
	}

	/**
	 * @covers ::affwp_add_payout()
	 */
	public function test_add_payout_without_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_add_payout( array(
			'referrals' => range( 1, 3 )
		) ) );
	}

	/**
	 * @covers ::affwp_add_payout()
	 */
	public function test_add_payout_with_empty_referrals_should_return_false() {
		$this->assertFalse( affwp_add_payout( array(
			'affiliate_id' => 1
		) ) );
	}

	/**
	 * @covers ::affwp_add_payout()
	 */
	public function test_add_payout_should_return_payout_id_on_success() {
		$payout = affwp_add_payout( array(
			'affiliate_id' => $affiliate_id = $this->factory->affiliate->create(),
			'referrals'    => $this->factory->referral->create( array(
				'affiliate_id' => $affiliate_id
			) )
		) );

		$this->assertTrue( is_numeric( $payout ) );
	}

	/**
	 * @covers ::affwp_delete_payout()
	 */
	public function test_delete_payout_should_return_false_if_invalid_payout_id() {
		$this->assertFalse( affwp_delete_payout( 0 ) );
	}

	/**
	 * @covers ::affwp_delete_payout()
	 */
	public function test_delete_payout_should_return_false_if_invalid_payout_object() {
		$this->assertFalse( affwp_delete_payout( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_delete_payout()
	 */
	public function test_delete_payout_should_return_true_if_payout_deleted_successfully() {
		$payout = $this->factory->payout->create();

		$this->assertTrue( affwp_delete_payout( $payout ) );
	}

	/**
	 * @covers ::affwp_get_payout_referrals()
	 */
	public function test_get_payout_referrals_should_return_false_if_invalid_payout() {
		$this->assertFalse( affwp_get_payout_referrals( 0 ) );
		$this->assertFalse( affwp_get_payout_referrals( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_payout_referrals()
	 */
	public function test_get_payout_referrals_should_return_array_of_referral_objects() {
		$payout_referrals = affwp_get_payout_referrals( self::$payouts[0] );

		$this->assertSame( self::$referrals, wp_list_pluck( $payout_referrals, 'referral_id' ) );
		$this->assertInstanceOf( 'AffWP\Referral', $payout_referrals[0] );
	}

	/**
	 * @covers ::affwp_get_payout_status_label()
	 */
	public function test_get_payout_status_label_should_return_false_if_invalid_payout() {
		$this->assertFalse( affwp_get_payout_status_label( 0 ) );
		$this->assertFalse( affwp_get_payout_status_label( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_payout_status_label()
	 */
	public function test_get_payout_status_label_should_return_paid_status_by_default() {
		$this->assertSame( 'Paid', affwp_get_payout_status_label( self::$payouts[0] ) );
	}

	/**
	 * @covers ::affwp_get_payout_status_label()
	 */
	public function test_get_payout_status_label_should_return_payout_status_label() {
		$payout_id = $this->factory->payout->create( array(
			'status' => 'failed',
		) );

		$this->assertSame( 'Failed', affwp_get_payout_status_label( $payout_id ) );
	}

	/**
	 * @covers ::affwp_get_payout_status_label()
	 */
	public function test_get_payout_status_label_should_return_paid_if_invalid_status() {
		$payout_id = $this->factory->payout->create( array(
			'status' => 'foo'
		) );

		$this->assertSame( 'Paid', affwp_get_payout_status_label( $payout_id ) );
	}
}
