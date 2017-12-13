<?php

namespace WPEmergeTests\View;

use Mockery;
use WPEmerge\View\View;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\View\View
 */
class ViewTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->subject = new View();
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->subject );
	}

	/**
	 * @covers ::setGlobal
	 * @covers ::getGlobals
	 */
	public function testSetGlobal() {
		$expected = ['foo' => 'bar'];

		$this->subject->setGlobal( 'foo', 'bar' );

		$this->assertEquals( $expected, $this->subject->getGlobals() );
	}

	/**
	 * @covers ::setGlobals
	 * @covers ::getGlobals
	 */
	public function testSetGlobals() {
		$expected = ['foo' => 'bar'];

		$this->subject->setGlobals( $expected );

		$this->assertEquals( $expected, $this->subject->getGlobals() );
	}

	/**
	 * @covers ::setComposer
	 * @covers ::getComposer
	 */
	public function testSetComposer() {
		$expected = function () { return []; };
		$view = 'foo';

		$this->subject->setComposer( $view, $expected );

		$this->assertSame( $expected, $this->subject->getComposer( $view )->get() );
	}

	/**
	 * @covers ::compose
	 */
	public function testCompose() {
		$expected = ['foo' => 'bar'];
		$view = 'foo';
		$composer = function() use ( $expected ) {
			return $expected;
		};

		$this->subject->setComposer( $view, $composer );

		$this->assertSame( $expected, $this->subject->compose( $view ) );
	}

	/**
	 * @covers ::compose
	 */
	public function testCompose_NonExistantComposer_ReturnEmptyArray() {
		$expected = [];
		$view = 'foo';

		$this->assertSame( $expected, $this->subject->compose( $view ) );
	}
}
