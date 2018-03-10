<?php
namespace AffWP\Tracking;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Tracking.
 *
 * @covers Affiliate_WP_Tracking
 * @group tracking
 */
class Tests extends UnitTestCase {

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
		self::$affiliates = parent::affwp()->affiliate->create_many( 4 );
	}

	/**
	 * @covers Affiliate_WP_Tracking::strip_referral_from_paged_urls()
	 */
	public function test_strip_referral_from_paged_urls_should_remove_query_string_referral_vars() {
		$referral_var = affiliate_wp()->tracking->get_referral_var();
		$url          = WP_TESTS_DOMAIN . "/foobar/page/2/?{$referral_var}=2";

		// Non-trailing slashed:
		$stripped = affiliate_wp()->tracking->strip_referral_from_paged_urls( $url );
		$this->assertSame( WP_TESTS_DOMAIN . '/foobar/page/2/', $stripped );
	}

	/**
	 * @covers Affiliate_WP_Tracking::strip_referral_from_paged_urls()
	 */
	public function test_strip_referral_from_paged_urls_should_remove_pretty_referral_vars() {
		$referral_var = affiliate_wp()->tracking->get_referral_var();
		$url          = WP_TESTS_DOMAIN . "/foobar/{$referral_var}/2/page/3/";

		$stripped = affiliate_wp()->tracking->strip_referral_from_paged_urls( $url );

		$this->assertSame( WP_TESTS_DOMAIN . '/foobar/page/3/', $stripped );
	}

	/**
	 * @covers \Affiliate_WP_Tracking::get_affiliate_id_from_login()
	 */
	public function test_get_affiliate_id_from_login_with_empty_login_should_return_zero() {
		$result = affiliate_wp()->tracking->get_affiliate_id_from_login( '' );

		$this->assertSame( 0, $result );
	}

	/**
	 * @covers \Affiliate_WP_Tracking::get_affiliate_id_from_login()
	 */
	public function test_get_affiliate_id_from_login_with_invalid_login_should_return_zero() {
		$result = affiliate_wp()->tracking->get_affiliate_id_from_login( 'foobar' );

		$this->assertSame( 0, $result );
	}

	/**
	 * @covers \Affiliate_WP_Tracking::get_affiliate_id_from_login()
	 */
	public function test_get_affiliate_id_from_login_with_urlencoded_login_should_return_that_affiliate() {
		$username = 'foo bar';
		$affiliate_id = $this->factory->affiliate->create( array(
			'user_id' => $this->factory->user->create( array(
				'user_login' => $username
			) )
		) );

		$result = affiliate_wp()->tracking->get_affiliate_id_from_login( urlencode( $username ) );

		$this->assertSame( $affiliate_id, $result );
	}

	/**
	 * @covers \Affiliate_WP_Tracking::get_affiliate_id_from_login()
	 */
	public function test_get_affiliate_id_from_login_with_valid_login_should_return_that_affiliate_id() {
		$username = affwp_get_affiliate_username( self::$affiliates[0] );

		$result = affiliate_wp()->tracking->get_affiliate_id_from_login( $username );

		$this->assertSame( self::$affiliates[0], $result );
	}
}
