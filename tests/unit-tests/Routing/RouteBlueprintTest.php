<?php

namespace WPEmergeTests\Routing;

use Mockery;
use WPEmerge\Routing\RouteBlueprint;
use WPEmerge\Routing\RouteInterface;
use WP_UnitTestCase;
use WPEmerge\Routing\Router;

/**
 * @coversDefaultClass \WPEmerge\Routing\RouteBlueprint
 */
class RouteBlueprintTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->router = Mockery::mock( Router::class );
		$this->subject = new RouteBlueprint( $this->router );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->router );
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
		$this->assertSame( $this->subject, $this->subject->methods( ['foo'] ) );
		$this->assertEquals( ['foo'], $this->subject->getAttribute( 'methods' ) );

		$this->assertSame( $this->subject, $this->subject->methods( ['bar'] ) );
		$this->assertEquals( ['foo', 'bar'], $this->subject->getAttribute( 'methods' ) );
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
	public function testWhere_EmptyString_EmptyStringAttribute() {
		$this->router->shouldReceive( 'mergeConditionAttribute' )
			->andReturn( '' )
			->once();

		$this->assertSame( $this->subject, $this->subject->where( 'foo' ) );

		$this->assertEquals( '', $this->subject->getAttribute( 'condition' ) );
	}

	/**
	 * @covers ::middleware
	 */
	public function testMiddleware() {
		$this->assertSame( $this->subject, $this->subject->middleware( ['foo'] ) );
		$this->assertEquals( ['foo'], $this->subject->getAttribute( 'middleware' ) );

		$this->assertSame( $this->subject, $this->subject->middleware( ['bar'] ) );
		$this->assertEquals( ['foo', 'bar'], $this->subject->getAttribute( 'middleware' ) );
	}

	/**
	 * @covers ::setNamespace
	 */
	public function testSetNamespace() {
		$this->assertSame( $this->subject, $this->subject->setNamespace( 'foo' ) );
		$this->assertEquals( 'foo', $this->subject->getAttribute( 'namespace' ) );

		$this->assertSame( $this->subject, $this->subject->setNamespace( 'bar' ) );
		$this->assertEquals( 'bar', $this->subject->getAttribute( 'namespace' ) );
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
			->andReturn( Mockery::mock( RouteInterface::class ) );
		$this->router->shouldReceive( 'addRoute' );

		$this->subject->handle( 'foo' );

		$this->assertEquals( 'foo', $this->subject->getAttribute( 'handler' ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_EmptyHandler_PassAttributes() {
		$attributes = ['foo' => 'bar'];

		$this->router->shouldReceive( 'route' )
			->with( $attributes )
			->andReturn( Mockery::mock( RouteInterface::class ) )
			->once();

		$this->router->shouldReceive( 'addRoute' );

		$this->subject->setAttributes( $attributes );
		$this->subject->handle();

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
		$subject = new RouteBlueprint( $this->router );
		$subject->get();
		$this->assertEquals( ['GET', 'HEAD'], $subject->getAttribute( 'methods' ) );


		$subject = new RouteBlueprint( $this->router );
		$subject->post();
		$this->assertEquals( ['POST'], $subject->getAttribute( 'methods' ) );


		$subject = new RouteBlueprint( $this->router );
		$subject->put();
		$this->assertEquals( ['PUT'], $subject->getAttribute( 'methods' ) );


		$subject = new RouteBlueprint( $this->router );
		$subject->patch();
		$this->assertEquals( ['PATCH'], $subject->getAttribute( 'methods' ) );


		$subject = new RouteBlueprint( $this->router );
		$subject->delete();
		$this->assertEquals( ['DELETE'], $subject->getAttribute( 'methods' ) );


		$subject = new RouteBlueprint( $this->router );
		$subject->options();
		$this->assertEquals( ['OPTIONS'], $subject->getAttribute( 'methods' ) );


		$subject = new RouteBlueprint( $this->router );
		$subject->any();
		$this->assertEquals( ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $subject->getAttribute( 'methods' ) );
	}

	/**
	 * @covers ::all
	 */
	public function testAll() {
		$handler = 'foo';
		$route = Mockery::mock( RouteInterface::class );

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
}
