<?php
namespace AffWP\Scripts;

use AffWP\Tests\UnitTestCase;

/**
 * Tests covering functions in includes/scripts.php.
 *
 * @group scripts
 * @group functions
 */
class Tests extends UnitTestCase {

	/**
	 * @dataProvider _admin_pages_dp
	 * @covers ::affwp_is_admin_page()
	 */
	public function test_affwp_is_admin_page_should_return_true_for_valid_admin_page( $page ) {
		$_GET['page'] = $page;

		$this->assertTrue( affwp_is_admin_page() );
	}

	/**
	 * @covers ::affwp_is_admin_page()
	 */
	public function test_affwp_is_admin_page_should_return_false_for_invalid_admin_page_GET() {
		$_GET['page'] = 'foo';

		$this->assertFalse( affwp_is_admin_page() );
	}

	/**
	 * @dataProvider _admin_pages_dp
	 * @covers ::affwp_is_admin_page()
	 */
	public function test_affwp_is_admin_page_should_return_true_for_specific_valid_admin_page( $page ) {
		$this->assertTrue( affwp_is_admin_page( $page ) );
	}

	/**
	 * @covers ::affwp_is_admin_page()
	 */
	public function test_affwp_is_admin_page_should_return_false_for_specific_invalid_admin_page() {
		$this->assertFalse( affwp_is_admin_page( 'foo' ) );
	}

	/**
	 * Data provider for affwp_is_admin_page_tests()
	 *
	 * @since 2.1
	 */
	public function _admin_pages_dp() {
		return array(
			array(
				'affiliate-wp',
				'affiliate-wp-affiliates',
				'affiliate-wp-referrals',
				'affiliate-wp-payouts',
				'affiliate-wp-visits',
				'affiliate-wp-creatives',
				'affiliate-wp-reports',
				'affiliate-wp-tools',
				'affiliate-wp-settings',
				'affwp-getting-started',
				'affwp-what-is-new',
				'affwp-credits'
			)
		);
	}
}
