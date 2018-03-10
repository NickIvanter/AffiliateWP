<?php
namespace AffWP\Admin\Notices;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Admin_Notices
 *
 * @covers \Affiliate_WP_Admin_Notices
 *
 * @group admin
 * @group notices
 */
class Tests extends UnitTestCase {

	/**
	 * Notices instance fixture.
	 *
	 * @access protected
	 * @var    \Affiliate_WP_Admin_Notices
	 * @static
	 */
	protected static $notices;

	/**
	 * User fixture for capabilities.
	 *
	 * @access protected
	 * @var    \WP_User
	 * @static
	 */
	protected static $user_id;

	/**
	 * Sets up fixtures once.
	 *
	 * @access public
	 * @static
	 */
	public static function wpSetUpBeforeClass() {
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-notices.php';

		self::$notices = new \Affiliate_WP_Admin_Notices;

		self::$user_id = parent::affwp()->user->create( array(
			'role' => 'administrator'
		) );
	}

	/**
	 * Helper to retrieve the Notices instance.
	 *
	 * @access protected
	 *
	 * @return \Affiliate_WP_Admin_Notices Notices instance.
	 */
	protected function notices() {
		return self::$notices;
	}

	/**
	 * Set up before each test.
	 */
	public function setUp() {
		parent::setUp();

		$this->_flush_roles();

		wp_set_current_user( self::$user_id );

		self::$notices->set_display_notices( false );

		affiliate_wp()->settings->set( array(
			'integrations' => array(
				'edd' => 'Easy Digital Downloads'
			)
		) );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_lacking_manage_affiliates_should_return_null() {
		wp_set_current_user( 0 );

		$this->assertNull( self::$notices->show_notices() );

		wp_set_current_user( self::$user_id );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_settings_updated_GET() {
		$this->_set_request_vars( array(
			'settings-updated' => true,
			'page'             => 'affiliate-wp-settings',
		) );

		$expected = '<div class="updated"><p>Settings updated.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affwp_message_GET() {
		$this->_set_request_vars( array(
			'affwp_notice'  => 'not_empty',
			'affwp_message' => 'AffiliateWP Rocks'
		) );

		$expected = '<div class="updated"><p>AffiliateWP Rocks</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_added_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_added_failed'
		) );

		$expected = '<div class="error"><p>Affiliate wasn&#8217;t added, please try again.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_updated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_updated'
		) );

		$expected = '<div class="updated"><p>Affiliate updated successfully';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_update_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_update_failed'
		) );

		$expected = '<div class="error"><p>Affiliate update failed, please try again</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_deleted() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_deleted'
		) );

		$expected = '<div class="updated"><p>Affiliate account(s) deleted successfully</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_delete_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_delete_failed'
		) );

		$expected = '<div class="error"><p>Affiliate deletion failed, please try again</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_activated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_activated'
		) );

		$expected = '<div class="updated"><p>Affiliate account activated</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_deactivated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_deactivated'
		) );

		$expected = '<div class="updated"><p>Affiliate account deactivated</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_accepted() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_accepted'
		) );

		$expected = '<div class="updated"><p>Affiliate request was accepted</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliate_rejected() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliate_rejected'
		) );

		$expected = '<div class="updated"><p>Affiliate request was rejected</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliates_migrated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliates_migrated'
		) );

		$expected = 'added successfully';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_affiliates_pro_migrated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'affiliates_pro_migrated'
		) );

		$expected = 'added successfully';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_stats_recounted() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'stats_recounted'
		) );

		$expected = '<div class="updated"><p>Affiliate stats have been recounted!</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_added() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_added'
		) );

		$expected = '<div class="updated"><p>Referral added successfully</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_add_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_add_failed'
		) );

		$expected = '<div class="error"><p>Referral wasn&#8217;t created, please try again.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_add_invalid_affiliate() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_add_invalid_affiliate'
		) );

		$expected = '<div class="error"><p>Referral not created because affiliate is invalid</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_updated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_updated'
		) );

		$expected = '<div class="updated"><p>Referral updated successfully</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_update_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_update_failed'
		) );

		$expected = '<div class="updated"><p>Referral update failed, please try again</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_deleted() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_deleted'
		) );

		$expected = '<div class="updated"><p>Referral deleted successfully</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_referral_delete_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'referral_delete_failed'
		) );

		$expected = '<div class="error"><p>Referral deletion failed, please try again</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_creative_updated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'creative_updated'
		) );

		$expected = '<div class="updated"><p>Creative updated successfully';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_creative_added() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'creative_added'
		) );

		$expected = '<div class="updated"><p>Creative added successfully</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_creative_deleted() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'creative_deleted'
		) );

		$expected = '<div class="updated"><p>Creative deleted successfully</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_creative_activated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'creative_activated'
		) );

		$expected = '<div class="updated"><p>Creative activated</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_creative_deactivated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'creative_deactivated'
		) );

		$expected = '<div class="updated"><p>Creative deactivated</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_settings_imported() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'settings-imported'
		) );

		$expected = '<div class="updated"><p>Settings successfully imported</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_license_revoked() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'license-revoked'
		) );

		$expected = '<div class="error"><p>Your license key has been disabled.';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_license_missing() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'license-missing'
		) );

		$expected = '<div class="error"><p>Invalid license.';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_license_invalid() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'license-invalid'
		) );

		$expected = '<div class="error"><p>Your license key is not active for this URL.';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_license_site_inactive() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'license-site_inactive'
		) );

		$expected = '<div class="error"><p>Your license key is not active for this URL.';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_license_item_name_mismatch() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'license-item_name_mismatch'
		) );

		$expected = '<div class="error"><p>This appears to be an invalid license key.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_license_no_activation_left() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'license-no_activations_left'
		) );

		$expected = '<div class="error"><p>Your license key has reached its activation limit.';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_api_key_generated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'api_key_generated'
		) );

		$expected = '<div class="updated"><p>The API keys were successfully generated.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_api_key_failed() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'api_key_failed'
		) );

		$expected = '<div class="error"><p>The API keys could not be generated.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_api_key_regenerated() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'api_key_regenerated'
		) );

		$expected = '<div class="updated"><p>The API keys were successfully regenerated.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::show_notices()
	 */
	public function test_show_notices_api_key_revoked() {
		$this->_set_request_vars( array(
			'affwp_notice' => 'api_key_revoked'
		) );

		$expected = '<div class="updated"><p>The API keys were successfully revoked.</p></div>';

		$this->assertContains( $expected, self::$notices->show_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::integration_notices()
	 */
	public function test_integration_notices_with_integration_should_output_nothing() {
		delete_user_meta( self::$user_id, '_affwp_no_integrations_dismissed' );

		$this->assertSame( '', self::$notices->integration_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::integration_notices()
	 */
	public function test_integration_notices_with_integration_set_and_notice_dismissed_meta_set_should_display_nothing() {
		add_user_meta( self::$user_id, '_affwp_no_integrations_dismissed', true );

		$this->assertSame( '', self::$notices->integration_notices() );

		// Clean up state.
		delete_user_meta( self::$user_id, '_affwp_no_integrations_dismissed' );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::integration_notices()
	 */
	public function test_integration_notices_with_no_integrations_and_notice_dismissed_meta_unset_should_display_notice() {
		delete_user_meta( self::$user_id, '_affwp_no_integrations_dismissed' );

		affiliate_wp()->settings->set( array(
			'integrations' => array()
		) );

		$expected = '<div class="error"><p>' . sprintf( __( 'There are currently no AffiliateWP <a href="%s">integrations</a> enabled. If you are using AffiliateWP without any integrations, you may disregard this message.', 'affiliate-wp' ), affwp_admin_url( 'settings', array( 'tab' => 'integrations' ) ) ) . '</p>';
		$expected .= '<p><a href="' . wp_nonce_url( add_query_arg( array( 'affwp_action' => 'dismiss_notices', 'affwp_notice' => 'no_integrations' ) ), 'affwp_dismiss_notice', 'affwp_dismiss_notice_nonce' ) . '">' . _x( 'Dismiss Notice', 'Integrations', 'affiliate-wp' ) . '</a></p></div>';

		$this->assertSame( $expected, self::$notices->integration_notices() );
	}

	/**
	 * @covers \Affiliate_WP_Admin_Notices::integration_notices()
	 */
	public function test_integration_notices_with_no_integrations_and_notice_dismissed_meta_set_should_display_nothing() {
		add_user_meta( self::$user_id, '_affwp_no_integrations_dismissed', true );

		affiliate_wp()->settings->set( array(
			'integrations' => array()
		) );

		$this->assertSame( '', self::$notices->integration_notices() );

		// Clean up state.
		delete_user_meta( self::$user_id, '_affwp_no_integrations_dismissed' );
	}

	/**
	 * Sets $_GET variables for the current test.
	 *
	 * @param array $vars $_GET variables to set.
	 */
	protected function _set_request_vars( $vars = array() ) {
		foreach ( $vars as $key => $value ) {
			$_GET[ $key ] = $value;
		}
	}

}
