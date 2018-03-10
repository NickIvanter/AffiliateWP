<?php
namespace AffWP\Tests\Factory;

class Affiliate extends \WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param array $args
	 * @param null  $generation_definitions
	 *
	 * @return \AffWP\Affiliate|false
	 */
	function create_and_get( $args = array(), $generation_definitions = null ) {
		return parent::create_and_get( $args, $generation_definitions );
	}

	function create_object( $args ) {
		$user = new \WP_UnitTest_Factory_For_User();

		// Only create the associated user if one wasn't supplied.
		if ( empty( $args['user_id'] ) ) {
			$args['user_id'] = $user->create();
		}

		return affiliate_wp()->affiliates->add( $args );
	}

	function update_object( $affiliate_id, $fields ) {
		return affiliate_wp()->affiliates->update( $affiliate_id, $fields, '', 'affiliate' );
	}

	public function delete( $affiliate ) {
		affwp_delete_affiliate( $affiliate );
	}

	public function delete_many( $affiliates ) {
		foreach ( $affiliates as $affiliate ) {
			$this->delete( $affiliate );
		}
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param $affiliate_id
	 *
	 * @return \AffWP\Affiliate|false
	 */
	function get_object_by_id( $affiliate_id ) {
		return affwp_get_affiliate( $affiliate_id );
	}
}
