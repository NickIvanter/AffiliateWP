<?php
namespace AffWP\Utils\Registry;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for the Registry API.
 *
 * @group registry
 * @group utils
 */
class Tests extends UnitTestCase {

	/**
	 * Mock registry test fixture.
	 *
	 * @access protected
	 * @var    \AffWP\Utils\Registry
	 */
	protected $mockRegistry;

	/**
	 * Set up fixtures once.
	 */
	public function setUp() {
		parent::setUp();

		$this->mockRegistry = $this->getMockForAbstractClass( '\AffWP\Utils\Registry' );
	}

	/**
	 * Runs after each test to reset the items array.
	 *
	 * @access public
	 */
	public function tearDown() {
		$this->mockRegistry->_reset_items();

		parent::tearDown();
	}

	/**
	 * @covers \AffWP\Utils\Registry::register_item()
	 */
	public function test_register_item_should_register_the_item() {
		$this->mockRegistry->add_item( 'foobar', array(
			'class' => 'Foo\Bar',
			'file'  => 'path/to/foobar.php'
		) );

		$this->assertArrayHasKey( 'foobar', $this->mockRegistry->get_items() );
	}

	/**
	 * @covers \AffWP\Utils\Registry::get_items()
	 */
	public function test_get_items_should_be_empty_with_no_registered_items() {
		$this->assertEqualSets( array(), $this->mockRegistry->get_items() );
	}

	/**
	 * @covers \AffWP\Utils\Registry::get_items()
	 */
	public function test_get_items_should_return_registered_items() {
		$item = array(
			'foobar' => array(
				'class' => 'Foo\Bar',
				'file'  => 'path/to/foobar.php'
			)
		);

		// Add a item.
		$this->mockRegistry->add_item( 'foobar', array(
			'class' => 'Foo\Bar',
			'file'  => 'path/to/foobar.php'
		) );

		// Confirm the item is retrieved.
		$this->assertEqualSets( $item, $this->mockRegistry->get_items() );
	}
}
