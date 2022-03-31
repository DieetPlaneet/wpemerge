<?php

namespace WPEmergeTests\Routing;

use Mockery;
use WPEmerge\Routing\RouteBlueprint;
use WPEmerge\Routing\RouteInterface;
use WPEmerge\Routing\Router;
use WPEmerge\View\ViewInterface;
use WPEmerge\View\ViewService;
use WPEmergeTestTools\TestCase;

/**
 * @coversDefaultClass \WPEmerge\Routing\RouteBlueprint
 */
class RouteBlueprintTest extends TestCase {
	public $router;

	public $view_service;

	public $subject;

	public function set_up() {
		$this->router = Mockery::mock( Router::class )->shouldIgnoreMissing();
		$this->view_service = Mockery::mock( ViewService::class );
		$this->subject = Mockery::mock( RouteBlueprint::class, [$this->router, $this->view_service] )->makePartial();
	}

	public function tear_down() {
		Mockery::close();

		unset( $this->router );
		unset( $this->view_service );
		unset( $this->subject );
	}

	/**
	 * @covers ::setAttributes
	 * @covers ::getAttributes
	 */
	public function testSetAttributes() {
		$expected = ['foo' => 'bar'];
		$this->subject->setAttributes( $expected );
		$this->assertEquals( $expected, $this->subject->getAttributes() );
	}

	/**
	 * @covers ::setAttribute
	 * @covers ::getAttribute
	 */
	public function testSetAttribute() {
		$this->subject->setAttribute( 'foo', 'bar' );
		$this->assertEquals( 'bar', $this->subject->getAttribute( 'foo' ) );
	}

	/**
	 * @covers ::methods
	 */
	public function testMethods() {
		$router = Mockery::mock( Router::class )->makePartial();
		$subject = Mockery::mock( RouteBlueprint::class, [$router, $this->view_service] )->makePartial();

		$this->assertSame( $subject, $subject->methods( ['foo'] ) );
		$this->assertEquals( ['foo'], $subject->getAttribute( 'methods' ) );

		$this->assertSame( $subject, $subject->methods( ['bar'] ) );
		$this->assertEquals( ['foo', 'bar'], $subject->getAttribute( 'methods' ) );
	}

	/**
	 * @covers ::url
	 */
	public function testUrl() {
		$this->router->shouldReceive( 'mergeConditionAttribute' )
			->with( '', ['url', 'foo', ['bar' => 'baz']] )
			->andReturn( 'condition' )
			->once();

		$this->assertSame( $this->subject, $this->subject->url( 'foo', ['bar' => 'baz'] ) );

		$this->assertEquals( 'condition', $this->subject->getAttribute( 'condition' ) );
	}

	/**
	 * @covers ::where
	 */
	public function testWhere_String_ConvertedToArraySyntax() {
		$this->router->shouldReceive( 'mergeConditionAttribute' )
			->with( '', ['foo', 'bar', 'baz'] )
			->once();

		$this->assertSame( $this->subject, $this->subject->where( 'foo', 'bar', 'baz' ) );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::where
	 */
	public function testWhere_String_StringAttribute() {
		$this->router->shouldReceive( 'mergeConditionAttribute' )
			->andReturn( 'foo' )
			->once();

		$this->assertSame( $this->subject, $this->subject->where( 'foo' ) );

		$this->assertEquals( 'foo', $this->subject->getAttribute( 'condition' ) );
	}

	/**
	 * @covers ::where
	 */
	public function testWhere_Null_NullAttribute() {
		$this->router->shouldReceive( 'mergeConditionAttribute' )
			->andReturn( null )
			->once();

		$this->assertSame( $this->subject, $this->subject->where( null ) );

		$this->assertNull( $this->subject->getAttribute( 'condition' ) );
	}

	/**
	 * @covers ::middleware
	 */
	public function testMiddleware() {
		$router = Mockery::mock( Router::class )->makePartial();
		$subject = Mockery::mock( RouteBlueprint::class, [$router, $this->view_service] )->makePartial();

		$this->assertSame( $subject, $subject->middleware( ['foo'] ) );
		$this->assertEquals( ['foo'], $subject->getAttribute( 'middleware' ) );

		$this->assertSame( $subject, $subject->middleware( ['bar'] ) );
		$this->assertEquals( ['foo', 'bar'], $subject->getAttribute( 'middleware' ) );
	}

	/**
	 * @covers ::setNamespace
	 */
	public function testSetNamespace() {
		$router = Mockery::mock( Router::class )->makePartial();
		$subject = Mockery::mock( RouteBlueprint::class, [$router, $this->view_service] )->makePartial();

		$this->assertSame( $subject, $subject->setNamespace( 'foo' ) );
		$this->assertEquals( 'foo', $subject->getAttribute( 'namespace' ) );

		$this->assertSame( $subject, $subject->setNamespace( 'bar' ) );
		$this->assertEquals( 'bar', $subject->getAttribute( 'namespace' ) );
	}

	/**
	 * @covers ::query
	 */
	public function testQuery() {
		$query1 = function ( $query_vars ) {
			return array_merge( $query_vars, ['bar'=>'bar'] );
		};

		$query2 = function ( $query_vars ) {
			return array_merge( $query_vars, ['baz'=>'baz'] );
		};

		$this->router->shouldReceive( 'mergeQueryAttribute' )
			->with( null, $query1 )
			->andReturn( $query1 )
			->once()
			->ordered();

		$this->router->shouldReceive( 'mergeQueryAttribute' )
			->with( $query1, $query2 )
			->once()
			->ordered();

		$this->assertSame( $this->subject, $this->subject->query( $query1 ) );
		$this->assertSame( $this->subject, $this->subject->query( $query2 ) );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( $this->subject, $this->subject->name( 'foo' ) );
		$this->assertEquals( 'foo', $this->subject->getAttribute( 'name' ) );

		$this->assertSame( $this->subject, $this->subject->name( 'bar' ) );
		$this->assertEquals( 'bar', $this->subject->getAttribute( 'name' ) );
	}

	/**
	 * @covers ::group
	 */
	public function testGroup() {
		$attributes = ['foo' => 'bar'];
		$routes = function () {};

		$this->subject->setAttributes( $attributes );

		$this->router->shouldReceive( 'group' )
			->with( $attributes, $routes )
			->once();

		$this->subject->group( $routes );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_Handler_SetHandlerAttribute() {
		$this->router->shouldReceive( 'route' )
			->andReturn( Mockery::mock( RouteInterface::class )->shouldIgnoreMissing() );

		$this->subject->handle( 'foo' );

		$this->assertEquals( 'foo', $this->subject->getAttribute( 'handler' ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_EmptyHandler_PassAttributes() {
		$attributes = ['foo' => 'bar'];
		$route = Mockery::mock( RouteInterface::class )->shouldIgnoreMissing();

		$this->router->shouldReceive( 'route' )
			->with( $attributes )
			->andReturn( $route )
			->once();

		$this->subject->setAttributes( $attributes );

		$this->subject->handle();

		$this->assertTrue( true );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_EmptyHandler_AddRouteToRouter() {
		$route = Mockery::mock( RouteInterface::class )->shouldIgnoreMissing();

		$this->router->shouldReceive( 'route' )
			->andReturn( $route );

		$this->router->shouldReceive( 'addRoute' )
			->with( $route )
			->once();

		$this->subject->handle();

		$this->assertTrue( true );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_EmptyHandler_ReturnRoute() {
		$route = Mockery::mock( RouteInterface::class )->shouldIgnoreMissing();

		$this->router->shouldReceive( 'route' )
			->andReturn( $route );

		$this->router->shouldReceive( 'addRoute' )
			->with( $route )
			->once();

		$this->subject->handle();

		$this->assertTrue( true );
	}

	/**
	 * @covers ::view
	 */
	public function testView() {
		$view_name = 'foo';
		$view = Mockery::mock( ViewInterface::class );
		$handler = null;

		$this->view_service->shouldReceive( 'make' )
			->with( $view_name )
			->andReturn( $view )
			->once();

		$this->subject->shouldReceive( 'handle' )
			->andReturnUsing( function ( $handler ) {
				return $handler();
			} )
			->once();

		$this->subject->view( $view_name );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::all
	 */
	public function testAll() {
		$handler = 'foo';
		$route = Mockery::mock( RouteInterface::class )->shouldIgnoreMissing();

		$this->router->shouldReceive( 'mergeMethodsAttribute' )
			->with( [], ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] )
			->andReturn( ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] );

		$this->router->shouldReceive( 'mergeConditionAttribute' )
			->with( '', ['url', '*', []] )
			->andReturn( '*' );

		$this->router->shouldReceive( 'route' )
			->with( [
				'handler' => $handler,
				'methods' => ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
				'condition' => '*',
			] )
			->andReturn( $route )
			->once();

		$this->router->shouldReceive( 'addRoute' )
			->with( $route )
			->once();

		$this->subject->all( $handler );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::get
	 * @covers ::post
	 * @covers ::put
	 * @covers ::patch
	 * @covers ::delete
	 * @covers ::options
	 * @covers ::any
	 */
	public function testMethodShortcuts() {
		$router = Mockery::mock( Router::class )->makePartial();

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->get();
		$this->assertEquals( ['GET', 'HEAD'], $subject->getAttribute( 'methods' ) );

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->post();
		$this->assertEquals( ['POST'], $subject->getAttribute( 'methods' ) );

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->put();
		$this->assertEquals( ['PUT'], $subject->getAttribute( 'methods' ) );

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->patch();
		$this->assertEquals( ['PATCH'], $subject->getAttribute( 'methods' ) );

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->delete();
		$this->assertEquals( ['DELETE'], $subject->getAttribute( 'methods' ) );

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->options();
		$this->assertEquals( ['OPTIONS'], $subject->getAttribute( 'methods' ) );

		$subject = new RouteBlueprint( $router, $this->view_service );
		$subject->any();
		$this->assertEquals( ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $subject->getAttribute( 'methods' ) );
	}
}
