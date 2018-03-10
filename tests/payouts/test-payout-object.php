<?php
namespace AffWP\Payout\Object;

use AffWP\Tests\UnitTestCase;
use AffWP\Affiliate\Payout;

/**
 * Tests for AffWP\Affiliate\Payout
 *
 * @covers AffWP\Affiliate\Payout
 * @covers AffWP\Base_Object
 *
 * @group payouts
 * @group objects
 */
class Tests extends UnitTestCase {

	/**
	 * @covers AffWP\Base_Object::get_instance()
	 */
	public function test_get_instance_with_invalid_payout_id_should_return_false() {
		$this->assertFalse( Payout::get_instance( 0 ) );
	}

	/**
	 * @covers AffWP\Base_Object::get_instance()
	 */
	public function test_get_instance_with_payout_id_should_return_Payout_object() {
		$payout_id = $this->factory->payout->create();

		$payout = Payout::get_instance( $payout_id );

		$this->assertInstanceOf( 'AffWP\Affiliate\Payout', $payout );
	}
}
