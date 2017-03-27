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
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
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

		// Clean up.
		affwp_delete_referral( $referral_id );
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

}
