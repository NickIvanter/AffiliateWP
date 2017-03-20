<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for AffWP\Utils\Batch_Process namespace.
 *
 * @covers \AffWP\Utils\Batch_Process\Registry
 *
 * @group utils
 * @group batch
 */
class Tests extends UnitTestCase {

	/**
	 * Utilities object.
	 *
	 * @access protected
	 * @var    \Affiliate_WP_Utilities
	 */
	protected static $utils;

	/**
	 * Test batch ID.
	 *
	 * @access protected
	 * @var    string
	 */
	protected static $batch_id = 'affwp';

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$utils = new \Affiliate_WP_Utilities;
	}

	/**
	 * Tear down run after each test.
	 */
	public function tearDown() {
		self::$utils->batch->remove_process( self::$batch_id );

		parent::tearDown();
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::get()
	 */
	public function test_get_should_return_an_array_of_batch_properties_for_a_registered_process() {
		self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php',
		) );

		$result = self::$utils->batch->get( self::$batch_id );

		$this->assertSame( 'array', gettype( $result ) );
		$this->assertSame( 'Affiliate_WP', $result['class'] );
		$this->assertSame( AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php', $result['file'] );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::get()
	 */
	public function test_get_should_return_false_for_invalid_batch_id() {
		$this->assertFalse( self::$utils->batch->get( 'foobar' ) );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_class_and_file_process_args_should_register_the_process() {
		self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php',
		) );

		$this->assertNotEmpty( self::$utils->batch->get( self::$batch_id ) );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_class_and_file_process_args_should_return_true() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php',
		) );

		$this->assertTrue( $result );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_empty_class_process_arg_should_return_wp_error() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'file' => AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php',
		) );

		$this->assertWPError( $result );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_empty_class_process_arg_should_return_WP_Error_with_invalid_batch_class_error_code() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'file' => AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php',
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'A batch process class must be specified.', $result->get_error_message() );
		$this->assertSame( 'invalid_batch_class', $result->get_error_code() );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_empty_file_process_arg_should_return_WP_Error() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
		) );

		$this->assertWPError( $result );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_empty_file_process_arg_should_return_WP_Error_with_missing_batch_class_file_error_code() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'No batch class handler file has been supplied.', $result->get_error_message() );
		$this->assertSame( 'missing_batch_class_file', $result->get_error_code() );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_invalid_file_process_arg_should_return_WP_Error() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'file' => AFFILIATEWP_PLUGIN_DIR . '../affiliate-wp.php',
		) );

		$this->assertWPError( $result );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_invalid_file_process_arg_should_return_WP_Error_with_invalid_batch_class_file_error_code() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
			'file' => AFFILIATEWP_PLUGIN_DIR . '../affiliate-wp.php',
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'An invalid batch class handler file has been supplied.', $result->get_error_message() );
		$this->assertSame( 'invalid_batch_class_file', $result->get_error_code() );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::register_process()
	 */
	public function test_register_process_with_valid_file_process_arg_containing_colon_should_return_true() {
		$result = self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
			'file'  => 'C:\\affiliate-wp.php',
		) );

		$this->assertTrue( $result );
	}

	/**
	 * @covers \AffWP\Utils\Batch_Process\Registry::remove_process()
	 */
	public function test_remove_process_should_remove_the_process() {
		self::$utils->batch->register_process( self::$batch_id, array(
			'class' => 'Affiliate_WP',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'affiliate-wp.php',
		) );

		self::$utils->batch->remove_process( self::$batch_id );

		$this->assertFalse( self::$utils->batch->get( self::$batch_id ) );
	}

}
