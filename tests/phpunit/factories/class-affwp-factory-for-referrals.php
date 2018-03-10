<?php
namespace AffWP\Tests\Factory;

class Referral extends \WP_UnitTest_Factory_For_Thing {

	protected static $affiliate_id;

	function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	function create_object( $args ) {
		$affiliate = new Affiliate();

		// Only create the associated affiliate if one wasn't supplied.
		if ( empty( $args['affiliate_id'] ) ) {
			$args['affiliate_id'] = self::$affiliate_id = $affiliate->create();
		}

		return affiliate_wp()->referrals->add( $args );
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param array $args
	 * @param null  $generation_definitions
	 *
	 * @return \AffWP\Referral|int
	 */
	function create_and_get( $args = array(), $generation_definitions = null ) {
		return parent::create_and_get( $args, $generation_definitions );
	}

	function update_object( $referral_id, $fields ) {
		return affiliate_wp()->referrals->update( $referral_id, $fields, '', 'referral' );
	}

	public function delete( $referral ) {
		affwp_delete_referral( $referral );
	}

	public function delete_many( $referrals ) {
		foreach ( $referrals as $referral ) {
			$this->delete( $referral );
		}
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param $referral_id
	 *
	 * @return \AffWP\Referral|false
	 */
	function get_object_by_id( $referral_id ) {
		return affwp_get_referral( $referral_id );
	}

	function cleanup( $referrals = array() ) {
		if ( ! empty( $referrals ) ) {
			foreach ( $referrals as $referral ) {
				affwp_delete_referral( $referral );
			}
		}
		affwp_delete_affiliate( self::$affiliate_id );
	}
}
