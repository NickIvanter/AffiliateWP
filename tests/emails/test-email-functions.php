<?php
namespace AffWP\Email;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for functions in includes/emails/functions.php.
 *
 * @group emails
 * @group functions
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
		self::$affiliates = parent::affwp()->affiliate->create_many( 2 );
	}

	/**
	 * @covers ::affwp_email_tag_rejection_reason()
	 */
	public function test_email_tag_rejection_reason_with_invalid_affiliate_id_should_retrieve_empty_string() {
		$this->assertSame( '', affwp_email_tag_rejection_reason( null ) );
	}

	/**
	 * @covers ::affwp_email_tag_rejection_reason()
	 */
	public function test_email_tag_rejection_reason_with_no_stored_reason_should_retrieve_empty_string() {
		$this->assertSame( '', affwp_email_tag_rejection_reason( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_email_tag_rejection_reason()
	 */
	public function test_email_tag_rejection_reason_with_single_stored_reason_should_retrieve_that_reason() {
		// Add a rejection reason.
		affwp_add_affiliate_meta( self::$affiliates[0], '_rejection_reason', 'Horse reasons.' );

		$result = affwp_email_tag_rejection_reason( self::$affiliates[0] );

		$this->assertSame( 'Horse reasons.', $result );

		// Clean up.
		affwp_delete_affiliate_meta( self::$affiliates[0], '_rejection_reason' );
	}

	/**
	 * @covers ::affwp_email_tag_rejection_reason()
	 */
	public function test_email_tag_rejection_reason_with_multiple_stored_reasons_should_retrieve_the_first_reason_only() {
		// Add multiple reasons.
		affwp_add_affiliate_meta( self::$affiliates[1], '_rejection_reason', 'Horse reasons.' );
		affwp_add_affiliate_meta( self::$affiliates[1], '_rejection_reason', 'Cow reasons.' );

		$result = affwp_email_tag_rejection_reason( self::$affiliates[1] );

		$this->assertSame( 'Horse reasons.', $result );

		// Clean up.
		affwp_delete_affiliate_meta( self::$affiliates[1], '_rejection_reason' );
	}

}
