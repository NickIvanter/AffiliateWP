<?php
namespace AffWP\Tests;

require_once dirname( __FILE__ ) . '/factory.php';

/**
 * Defines a basic fixture to run multiple tests.
 *
 * Resets the state of the WordPress installation before and after every test.
 *
 * Includes utility functions and assertions useful for testing WordPress.
 *
 * All WordPress unit tests should inherit from this class.
 */
class UnitTestCase extends \WP_UnitTestCase {

	function __get( $name ) {
		if ( 'factory' === $name ) {
			return self::affwp();
		}
	}

	protected static function affwp() {
		static $factory = null;
		if ( ! $factory ) {
			$factory = new Factory();
		}
		return $factory;
	}

	public static function tearDownAfterClass() {
		self::_delete_all_data();

		return parent::tearDownAfterClass();
	}

	protected static function _delete_all_data() {
		global $wpdb;

		foreach ( array(
			affiliate_wp()->affiliates->table_name,
			affiliate_wp()->affiliate_meta->table_name,
			affiliate_wp()->creatives->table_name,
			affiliate_wp()->affiliates->payouts->table_name,
			affiliate_wp()->referrals->table_name,
			affiliate_wp()->REST->consumers->table_name,
			affiliate_wp()->visits->table_name
		) as $table ) {
			$wpdb->query( "DELETE FROM {$table}" );
		}
	}

	/**
	 * Helper to flush the $wp_roles global.
	 */
	public static function _flush_roles() {
		/*
		 * We want to make sure we're testing against the db, not just in-memory data
		 * this will flush everything and reload it from the db
		 */
		unset( $GLOBALS['wp_user_roles'] );
		global $wp_roles;
		if ( is_object( $wp_roles ) ) {
			if ( method_exists( $wp_roles, 'for_site' ) ) {
				$wp_roles->for_site();
			} else {
				$wp_roles->_init();
			}
		}
	}

	/**
	 * Retrieves the current time for use in test comparisons.
	 *
	 * Serves as a tests wrapper for core's current_time( 'mysql' ) with the distinct
	 * difference that it doesn't include seconds in the resulting string. This is an
	 * attempt to avoid race conditions for assertions that take longer than a second
	 * to execute.
	 *
	 * @access public
	 * @since  2.1
	 *
	 * @param string $time Time to build the Date object for for comparison.
	 * @return string Current time expressed as a string.
	 */
	public function get_current_time_for_comparison( $time = 'now' ) {
		return affiliate_wp()->utils->date( $time )->format( 'Y-m-d H:i' );
	}

	/**
	 * Retrieves the date_registered value for a given affiliate for use in test comparisons.
	 *
	 * Modifies the returned date to omit the seconds value. This is an attempt to avoid race
	 * conditions for assertions that take longer than a second to execute.
	 *
	 * @access public
	 * @since  2.1
	 *
	 * @param int|\AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param bool                 $gmt       Optional. Whether to use GMT timezone. Default false.
	 * @return string Affiliate registered date expressed as a string, otherwise an empty string.
	 */
	public function get_affiliate_date_for_comparison( $affiliate ) {
		$date_registered = '';

		if ( $affiliate = affwp_get_affiliate( $affiliate ) ) {
			$date_registered = $affiliate->date( 'Y-m-d H:i' );
		}

		return $date_registered;
	}

	/**
	 * Checks if all items in the array are of the given type.
	 *
	 * @since 2.1
	 *
	 * @param string $type     Type to check against.
	 * @param array  $actual   Actual array.
	 */
	public function assertContainsOnlyType( $type, $actual ) {
		$standard_types = array(
			'numeric', 'integer', 'int', 'float', 'string', 'boolean', 'bool',
			'null', 'array', 'object', 'resource', 'scalar'
		);


		if ( in_array( $type, $standard_types, true ) ) {
			$constraint = new \PHPUnit_Framework_Constraint_IsType( $type );
		} else {
			$constraint = new \PHPUnit_Framework_Constraint_IsInstanceOf( $type );
		}

		foreach ( $actual as $item ) {
			\PHPUnit_Framework_Assert::assertThat( $item, $constraint );
		}
	}
}
