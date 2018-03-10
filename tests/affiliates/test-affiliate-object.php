<?php
namespace AffWP\Affiliate\Object;

use AffWP\Tests\UnitTestCase;
use AffWP\Affiliate as Affiliate;

/**
 * Tests for AffWP\Affiliate
 *
 * @covers AffWP\Affiliate
 * @covers AffWP\Base_Object
 *
 * @group affiliates
 * @group objects
 */
class Tests extends UnitTestCase {

	/**
	 * User fixture.
	 *
	 * @access protected
	 * @var int
	 * @static
	 */
	protected static $user_id = 0;

	/**
	 * Affiliate fixture.
	 *
	 * @access protected
	 * @var int
	 * @static
	 */
	protected static $affiliate_id = 0;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$user_id = parent::affwp()->user->create();

		self::$affiliate_id = parent::affwp()->affiliate->create( array(
			'user_id' => self::$user_id
		) );
	}

	/**
	 * @covers AffWP\Base_Object::get_instance()
	 */
	public function test_get_instance_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( Affiliate::get_instance( 0 ) );
	}

	/**
	 * @covers AffWP\Base_Object::get_instance()
	 */
	public function test_get_instance_with_affiliate_id_should_return_Affiliate_object() {
		$affiliate = Affiliate::get_instance( self::$affiliate_id );

		$this->assertInstanceOf( 'AffWP\Affiliate', $affiliate );
	}

	/**
	 * @covers AffWP\Affiliate
	 */
	public function test_affiliate_user_object_should_be_lazy_loadable() {
		$this->assertInstanceOf( '\stdClass', affwp_get_affiliate( self::$affiliate_id )->user );
	}

	/**
	 * @covers AffWP\Affiliate
	 */
	public function test_lazy_loaded_user_object_should_contain_first_name_user_meta_in_data_object() {
		$first_name = rand_str( 10 );

		update_user_meta( self::$user_id, 'first_name', $first_name );

		$this->assertEquals( $first_name, affwp_get_affiliate( self::$affiliate_id )->user->first_name );
	}

	/**
	 * @covers AffWP\Affiliate
	 */
	public function test_lazy_loaded_user_object_should_contain_last_name_user_meta_in_data_object() {
		$last_name = rand_str( 10 );

		update_user_meta( self::$user_id, 'last_name', $last_name );

		$this->assertSame( $last_name, affwp_get_affiliate( self::$affiliate_id )->user->last_name );
	}

	/**
	 * @covers AffWP\Affiliate
	 */
	public function test_earnings_property_should_be_of_type_float() {
		affwp_increase_affiliate_earnings( self::$affiliate_id, '1.50' );

		$earnings = affwp_get_affiliate( self::$affiliate_id )->earnings;

		$this->assertSame( 'double', gettype( $earnings ) );
	}

	/**
	 * @covers \AffWP\Affiliate::$date
	 * @group dates
	 */
	public function test_date_property_should_return_value_of_date_registered() {
		$affiliate = affwp_get_affiliate( self::$affiliate_id );

		$this->assertSame( $affiliate->date, $affiliate->date_registered );
	}

}
