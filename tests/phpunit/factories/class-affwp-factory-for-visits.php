<?php
namespace AffWP\Tests\Factory;

class Visit extends \WP_UnitTest_Factory_For_Thing {

	public static $affiliate_id;

	function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	function create_many( $count, $args = array(), $generation_definitions = null ) {
		return parent::create_many( $count, $args, $generation_definitions );
	}

	public function delete( $visit ) {
		affwp_delete_visit( $visit );
	}

	public function delete_many( $visits ) {
		foreach ( $visits as $visit_id ) {
			$this->delete( $visit_id );
		}
	}

	function create_object( $args ) {
		$affiliate = new Affiliate();

		// Only create the associated affiliate if one wasn't supplied.
		if ( empty( $args['affiliate_id'] ) ) {
			$args['affiliate_id'] = self::$affiliate_id = $affiliate->create();
		}

		return affiliate_wp()->visits->add( $args );
	}

	function update_object( $visit_id, $fields ) {
		return affiliate_wp()->visits->update( $visit_id, $fields, '', 'visit' );
	}

	function get_object_by_id( $visit_id ) {
		return affwp_get_visit( $visit_id );
	}

	function cleanup() {
		affwp_delete_affiliate( self::$affiliate_id );
	}
}
