<?php
namespace AffWP\Settings;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Settings
 *
 * @covers Affiliate_WP_Settings
 * @group settings
 */
class Tests extends UnitTestCase {

	/**
	 * Settings instance.
	 *
	 * @access protected
	 * @var    Affiliate_WP_Settings
	 * @static
	 */
	protected static $settings;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$settings = new \Affiliate_WP_Settings;
	}

	/**
	 * Tests set up.
	 */
	public function setUp() {
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/settings/display-settings.php';
	}

	/**
	 * Tests tear down.
	 */
	public function tearDown() {
		affiliate_wp()->settings->set( array( 'license_key' => '' ), true );

		parent::tearDown();
	}

	/**
	 * @covers Affiliate_WP_Settings::set()
	 */
	public function test_set_method_should_update_options_property_in_memory_only() {
		$affiliates_area = 100000;
		self::$settings->set( array(
			'affiliates_page' => $affiliates_area
		) );

		$options = self::$settings->get_all();
		$actual  = get_option( 'affwp_settings', array() );

		$this->assertSame( $options['affiliates_page'], $affiliates_area );
		$this->assertNotSame( $actual['affiliates_page'], $affiliates_area );
	}

	/**
	 * @covers Affiliate_WP_Settings::set()
	 */
	public function test_set_method_with_save_trigger_should_update_settings() {
		$affiliates_area = rand( 1, 200 );
		self::$settings->set( array(
			'affiliates_page' => $affiliates_area
		), $save = true );

		$options = self::$settings->get_all();
		$actual  = get_option( 'affwp_settings', array() );

		$this->assertSame( $options['affiliates_page'], $affiliates_area );
		$this->assertSame( $actual['affiliates_page'], $affiliates_area );
	}

	/**
	 * @covers Affiliate_WP_Settings::is_setting_disabled()
	 */
	public function test_is_setting_disabled() {
		// Default should be false.
		$this->assertFalse( self::$settings->get( 'debug_mode' ) );

		// Define and reset $settings.
		define( 'AFFILIATE_WP_DEBUG', true );
		self::$settings = new \Affiliate_WP_Settings();

		// Constant should override the value.
		$this->assertTrue( self::$settings->get( 'debug_mode' ) );

		$registered_settings = self::$settings->get_registered_settings();
		$args = array_merge( $registered_settings['misc']['debug_mode'], array( 'id' => 'debug_mode' ) );

		$this->assertTrue( self::$settings->is_setting_disabled( $args ) );
	}

	/**
	 * @covers \Affiliate_WP_Settings::global_license_set()
	 *
	 * Note: This test needs to happen before the global license constant is defined
	 * within the scope of the test suite.
	 */
	public function test_global_license_set_where_constant_is_not_defined_should_return_false() {
		$this->assertFalse( \Affiliate_WP_Settings::global_license_set() );
	}

	/**
	 * @covers \Affiliate_WP_Settings::get_license_key()
	 */
	public function test_get_license_key_with_empty_key_and_saving_false_should_return_the_saved_key() {
		affiliate_wp()->settings->set( array( 'license_key' => 'baz' ) );

		$this->assertSame( 'baz', \Affiliate_WP_Settings::get_license_key() );
	}

	/**
	 * @covers \Affiliate_WP_Settings::get_license_key()
	 */
	public function test_get_license_key_with_empty_key_and_saving_true_should_return_empty() {
		affiliate_wp()->settings->set( array( 'license_key' => 'foo' ) );

		$this->assertSame( '', \Affiliate_WP_Settings::get_license_key( '', true ) );
	}

	/**
	 * @covers \Affiliate_WP_Settings::get_license_key()
	 */
	public function test_get_license_key_with_not_empty_key_and_saving_true_should_return_key() {
		$this->assertSame( 'bar', \Affiliate_WP_Settings::get_license_key( 'bar', true ) );
	}

	/**
	 * @covers \Affiliate_WP_Settings::get_license_key()
	 */
	public function test_get_license_key_with_not_empty_key_and_no_constant_and_savind_false_should_return_that_key() {
		$this->assertSame( 'foobar', \Affiliate_WP_Settings::get_license_key( 'foobar' ) );
	}

	/**
	 * @covers \Affiliate_WP_Settings::get_license_key()
	 *
	 * Note: This test needs to occur toward the end of the class so as not
	 * to unexpectedly pollute other tests.
	 */
	public function test_get_license_key_with_global_key_defined_should_return_that_key() {
		define( 'AFFILIATEWP_LICENSE_KEY', 'foobar' );

		static $settings;
		$settings = new \Affiliate_WP_Settings;

		$this->assertSame( 'foobar', $settings::get_license_key() );
	}

	/**
	 * @covers \Affiliate_WP_Settings::global_license_set()
	 *
	 * @depends test_get_license_key_with_global_key_defined_should_return_that_key
	 */
	public function test_global_license_set_where_constant_is_defined_and_not_empty_should_return_true() {
		$this->assertTrue( \Affiliate_WP_Settings::global_license_set() );
	}

}
