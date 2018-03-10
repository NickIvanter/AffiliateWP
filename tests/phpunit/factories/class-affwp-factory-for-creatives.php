<?php
namespace AffWP\Tests\Factory;

class Creative extends \WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	function create_many( $count, $args = array(), $generation_definitions = null ) {
		return parent::create_many( $count, $args, $generation_definitions );
	}

	function create_object( $args ) {
		return affiliate_wp()->creatives->add( $args );
	}

	function update_object( $creative_id, $fields ) {
		return affiliate_wp()->creatives->update( $creative_id, $fields, '', 'creative' );
	}

	public function delete( $creative ) {
		affwp_delete_creative( $creative );
	}

	public function delete_many( $creatives ) {
		foreach ( $creatives as $creative ) {
			$this->delete( $creative );
		}
	}

	function get_object_by_id( $creative_id ) {
		return affwp_get_creative( $creative_id );
	}
}
