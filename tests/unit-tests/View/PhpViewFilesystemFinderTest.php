<?php

namespace WPEmergeTests\View;

use Mockery;
use WPEmerge\Helpers\MixedType;
use WPEmerge\View\PhpViewFilesystemFinder;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\View\PhpViewFilesystemFinder
 */
class PhpViewFilesystemFinderTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->subject = new PhpViewFilesystemFinder();
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->subject );
	}

	/**
	 * @covers ::exists
	 */
	public function testExists() {
		$this->assertTrue( $this->subject->exists( WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'view.php' ) );
		$this->assertTrue( $this->subject->exists( 'index.php' ) );
		$this->assertTrue( $this->subject->exists( 'index' ) );
		$this->assertFalse( $this->subject->exists( 'nonexistant' ) );
		$this->assertFalse( $this->subject->exists( '' ) );
	}

	/**
	 * @covers ::canonical
	 */
	public function testCanonical() {
		$expected = realpath( MixedType::normalizePath( locate_template( 'index.php', false ) ) );

		$this->assertEquals( $expected, $this->subject->canonical( $expected ) );
		$this->assertEquals( $expected, $this->subject->canonical( 'index.php' ) );
		$this->assertEquals( $expected, $this->subject->canonical( 'index' ) );
		$this->assertEquals( '', $this->subject->canonical( 'nonexistant' ) );
		$this->assertEquals( '', $this->subject->canonical( '' ) );
	}

	/**
	 * @covers ::resolveFilepath
	 * @covers ::resolveFromAbsoluteFilepath
	 */
	public function testResolveFilepath_AbsoluteFilepath() {
		$directory = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'fixtures';
		$file = $directory . DIRECTORY_SEPARATOR . 'view.php';

		$this->assertEquals( $file, $this->subject->resolveFilepath( $file ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( $directory ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistant' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( '' ) );
	}

	/**
	 * @covers ::resolveFilepath
	 * @covers ::resolveFromCustomDirectories
	 */
	public function testResolveFilepath_CustomDirectories() {
		$fixtures = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'fixtures';
		$subdirectory = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'subdirectory';
		$view = $fixtures . DIRECTORY_SEPARATOR . 'view.php';
		$subview = $subdirectory . DIRECTORY_SEPARATOR . 'subview.php';

		$this->subject->setDirectories( [] );
		$this->assertEquals( '', $this->subject->resolveFilepath( '/view.php' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( '/view' ) );

		$this->subject->setDirectories( [$fixtures] );
		$this->assertEquals( $view, $this->subject->resolveFilepath( '/view.php' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( 'view.php' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( '/view' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( 'view' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( '/nonexistant' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistant' ) );

		$this->subject->setDirectories( [WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR] );
		$this->assertEquals( $view, $this->subject->resolveFilepath( '/view.php' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( 'view.php' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( '/view' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( 'view' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( '/nonexistant' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistant' ) );

		$this->subject->setDirectories( [$fixtures, $subdirectory] );
		$this->assertEquals( $view, $this->subject->resolveFilepath( '/view.php' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( 'view.php' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( '/view' ) );
		$this->assertEquals( $view, $this->subject->resolveFilepath( 'view' ) );
		$this->assertEquals( $subview, $this->subject->resolveFilepath( '/subview.php' ) );
		$this->assertEquals( $subview, $this->subject->resolveFilepath( 'subview.php' ) );
		$this->assertEquals( $subview, $this->subject->resolveFilepath( '/subview' ) );
		$this->assertEquals( $subview, $this->subject->resolveFilepath( 'subview' ) );
	}

	/**
	 * @covers ::resolveFilepath
	 * @covers ::resolveFromWordPress
	 */
	public function testResolveFilepath_WordPress() {
		$index = realpath( MixedType::normalizePath( locate_template( 'index.php', false ) ) );

		$this->assertEquals( $index, $this->subject->resolveFilepath( '/index.php' ) );
		$this->assertEquals( $index, $this->subject->resolveFilepath( 'index.php' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistant' ) );
		$this->assertEquals( '', $this->subject->resolveFilepath( '' ) );
	}
}
