<?php

namespace WPEmergeTests\Application;

use Mockery;
use WPEmerge\Application\Application;
use WPEmerge\ServiceProviders\ServiceProviderInterface;
use WPEmerge\Support\Facade;
use Pimple\Container;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Application\Application
 */
class ApplicationTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->container = new Container();
		$this->subject = new Application( $this->container, false );
		$this->container[ WPEMERGE_APPLICATION_KEY ] = $this->subject;
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->container );
		unset( $this->subject );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$container = new Container();
		$subject = new Application( $container );
		$this->assertSame( $container, $subject->getContainer() );
	}

	/**
	 * @covers ::debugging
	 */
	public function testDebugging() {
		$this->assertTrue( $this->subject->debugging() );
		add_filter( 'wpemerge.debug', '__return_false' );
		$this->assertFalse( $this->subject->debugging() );
	}

	/**
	 * @covers ::isBootstrapped
	 * @covers ::bootstrap
	 */
	public function testIsBootstrapped() {
		$this->assertEquals( false, $this->subject->isBootstrapped() );
		$this->subject->bootstrap( [], false );
		$this->assertEquals( true, $this->subject->isBootstrapped() );
	}

	/**
	 * @covers ::getContainer
	 */
	public function testGetContainer_ReturnContainer() {
		$container = $this->subject->getContainer();
		$this->assertInstanceOf( Container::class, $container );
	}

	/**
	 * @covers ::verifyBootstrap
	 * @expectedException \Exception
	 * @expectedExceptionMessage must be bootstrapped first
	 */
	public function testVerifyBootstrap_NotBootstrapped_Exception() {
		$this->subject->resolve( 'foobar' );
	}

	/**
	 * @covers ::verifyBootstrap
	 */
	public function testVerifyBootstrap_Bootstrapped_NoException() {
		$this->subject->bootstrap( [], false );
		$this->subject->resolve( 'foobar' );
		$this->assertTrue( true );
	}

	/**
	 * @covers ::bootstrap
	 * @expectedException \Exception
	 * @expectedExceptionMessage already bootstrapped
	 */
	public function testBootstrap_CalledMultipleTimes_ThrowException() {
		$this->subject->bootstrap( [], false );
		$this->subject->bootstrap( [], false );
	}

	/**
	 * @covers ::bootstrap
	 * @covers ::registerServiceProviders
	 * @covers ::bootstrapServiceProviders
	 */
	public function testBootstrap_RegisterServiceProviders() {
		$this->subject->bootstrap( [
			'providers' => [
				ApplicationTestServiceProviderMock::class,
			]
		], false );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::bootstrap
	 */
	public function testBootstrap_RunKernel() {
		$this->subject->bootstrap( [
			'providers' => [
				ApplicationTestKernelServiceProviderMock::class,
			],
		], true );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::alias
	 */
	public function testAlias() {
		$expected = 'foobar';

		$container = $this->subject->getContainer();
		$container['test_service'] = function() {
			return new \WPEmergeTestTools\TestService();
		};
		$alias = 'TestServiceAlias';

		$this->subject->alias( $alias, \WPEmergeTestTools\TestServiceFacade::class );
		$this->assertSame( $expected, call_user_func( [$alias, 'getTest'] ) );
	}

	/**
	 * @covers ::resolve
	 */
	public function testResolve_NonexistantKey_ReturnNull() {
		$expected = null;
		$container_key = 'nonexistantcontainerkey';

		$this->subject->bootstrap( [], false );
		$this->assertSame( $expected, $this->subject->resolve( $container_key ) );
	}

	/**
	 * @covers ::resolve
	 */
	public function testResolve_ExistingKey_IsResolved() {
		$expected = 'foobar';
		$container_key = 'test';

		$container = $this->subject->getContainer();
		$container[ $container_key ] = $expected;

		$this->subject->bootstrap( [], false );
		$this->assertSame( $expected, $this->subject->resolve( $container_key ) );
	}
}

class ApplicationTestServiceProviderMock implements ServiceProviderInterface {
	public function __construct() {
		$this->mock = Mockery::mock( ServiceProviderInterface::class );
		$this->mock->shouldReceive( 'register' )
			->once();
		$this->mock->shouldReceive( 'bootstrap' )
			->once();
	}

	public function register( $container ) {
		call_user_func_array( [$this->mock, 'register'], func_get_args() );
	}

	public function bootstrap( $container ) {
		call_user_func_array( [$this->mock, 'bootstrap'], func_get_args() );
	}
}

class ApplicationTestKernelServiceProviderMock implements ServiceProviderInterface {
	public function register( $container ) {
		$mock = Mockery::mock();

		$mock->shouldReceive( 'bootstrap' )
			->once();

		$container[ WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY ] = $mock;
	}

	public function bootstrap( $container ) {
		// Do nothing.
	}
}
