<?php
namespace AffWP\Admin\Actions;

use AffWP\Tests\UnitTestCase;

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/actions.php';

/**
 * Tests for functions in includes/admin/actions.php.
 *
 * @group admin
 * @group actions
 */
class Tests extends UnitTestCase {

	/**
	 * @covers ::affwp_process_add_affiliate_website()
	 */
	public function test_process_add_affiliate_website_should_return_false_if_website_url_not_set() {
		$this->assertFalse( affwp_process_add_affiliate_website( 0, array() ) );
	}

	/**
	 * @covers ::affwp_process_add_affiliate_website()
	 */
	public function test_process_add_affiliate_website_should_return_WP_Error_if_website_url_set_and_updating_the_user_failed() {
		$result = affwp_process_add_affiliate_website( 0, array( 'website_url' => 'foo' ) );

		$this->assertWPError( $result );
	}

	/**
	 * @covers ::affwp_process_add_affiliate_website()
	 */
	public function test_process_add_affiliate_website_should_return_user_id_if_updated_successfully() {
		$affiliate_id = $this->factory->affiliate->create( array(
			'user_id' => $user_id = $this->factory->user->create()
		) );

		$result = affwp_process_add_affiliate_website( $affiliate_id, array(
			'website_url' => 'https//affwp.rocks'
		) );

		$this->assertSame( $user_id, $result );
	}

	/**
	 * @covers ::affwp_process_add_affiliate_website()
	 */
	public function test_process_add_affiliate_website_should_set_user_url_if_website_url_set_and_valid_affiliate() {
		$website = 'https://affwp.rocks';

		$affiliate_id = $this->factory->affiliate->create( array(
			'user_id' => $user_id = $this->factory->user->create()
		) );

		affwp_process_add_affiliate_website( $affiliate_id, array(
			'website_url' => $website
		) );

		$user = get_user_by( 'id', $user_id );

		$this->assertSame( $website, $user->user_url );
	}

}
