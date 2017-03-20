<?php
namespace AffWP\Campaign\Database;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Campaigns_DB class
 *
 * @covers Affiliate_WP_Campaigns_DB
 * @group database
 * @group campaigns
 */
class Tests extends UnitTestCase {

	/**
	 * Visits fixture.
	 *
	 * @access protected
	 * @var    array
	 * @static
	 */
	public static $visits = array();

	/**
	 * Affiliates fixture.
	 *
	 * @access protected
	 * @var    array
	 * @static
	 */
	public static $affiliates = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$affiliates = parent::affwp()->affiliate->create_many( 4 );

		for ( $i = 0; $i <= 3; $i++ ) {
			$campaign = rand_str( 10 );

			self::$visits[] = parent::affwp()->visit->create( array(
				'affiliate_id' => self::$affiliates[0],
				'campaign'     => $campaign,
				'url'          => WP_TESTS_DOMAIN . '/' . $campaign,
			) );
		}
	}

	/**
	 * @covers \Affiliate_WP_Campaigns_DB::$cache_group
	 */
	public function test_cache_group_should_be_campaigns() {
		$this->assertSame( 'campaigns', affiliate_wp()->campaigns->cache_group );
	}

	/**
	 * @covers \Affiliate_WP_Campaigns_DB::$primary_key
	 */
	public function test_primary_key_should_be_affiliate_id() {
		$this->assertSame( 'affiliate_id', affiliate_wp()->affiliates->primary_key );
	}

	/**
	 * @covers \Affiliate_WP_Campaigns_DB::count()
	 */
	public function test_count_should_return_a_count_of_campaigns() {
		$this->assertSame( 4, affiliate_wp()->campaigns->count() );
	}

	/**
	 * @covers \Affiliate_WP_Campaigns_DB::get_campaigns()
	 */
	public function test_get_campaigns_with_count_true_should_return_count_of_campaigns() {
		$this->assertSame( 4, affiliate_wp()->campaigns->get_campaigns( array(), $count = true ) );
	}
}
