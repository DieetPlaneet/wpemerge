<?php

namespace WPEmergeTests\Routing;

use ArrayAccess;
use Closure;
use Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use WPEmerge\Application\Application;
use WPEmerge\Application\InjectionFactory;
use WPEmerge\Exceptions\ErrorHandlerInterface;
use WPEmerge\Helpers\Handler;
use WPEmerge\Helpers\HandlerFactory;
use WPEmerge\Kernels\HttpKernel;
use WPEmerge\Requests\RequestInterface;
use WPEmerge\Responses\ResponsableInterface;
use WPEmerge\Responses\ResponseService;
use WPEmerge\Routing\HasQueryFilterInterface;
use WPEmerge\Routing\RouteInterface;
use WPEmerge\Routing\Router;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Kernels\HttpKernel
 */
class HttpKernelTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->app = Mockery::mock( Application::class )->shouldIgnoreMissing();
		$this->injection_factory = Mockery::mock( InjectionFactory::class )->shouldIgnoreMissing();
		$this->handler_factory = Mockery::mock( HandlerFactory::class )->shouldIgnoreMissing();
		$this->request = Mockery::mock( RequestInterface::class );
		$this->response_service = Mockery::mock( ResponseService::class )->shouldIgnoreMissing();
		$this->router = Mockery::mock( Router::class )->shouldIgnoreMissing();
		$this->error_handler = Mockery::mock( ErrorHandlerInterface::class )->shouldIgnoreMissing();
		$this->factory_handler = Mockery::mock( Handler::class );

		$this->handler_factory->shouldReceive( 'make' )
			->andReturn( $this->factory_handler );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->app );
		unset( $this->injection_factory );
		unset( $this->handler_factory );
		unset( $this->request );
		unset( $this->response_service );
		unset( $this->router );
		unset( $this->error_handler );
		unset( $this->factory_handler );
	}

	/**
	 * @covers ::toResponse
	 */
	public function testToResponse_String_OutputResponse() {
		$expected = 'foobar';
		$handler = function() use ( $expected ) {
			return $expected;
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->response_service->shouldReceive( 'output' )
			->andReturnUsing( function ( $output ) {
				return (new Psr7Response())->withBody( Psr7\stream_for( $output ) );
			} );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run( $this->request, [], $handler );
		$this->assertEquals( $expected, $response->getBody()->read( strlen( $expected ) ) );
	}

	/**
	 * @covers ::toResponse
	 */
	public function testToResponse_Array_JsonResponse() {
		$value = ['foo' => 'bar'];
		$expected = json_encode( $value );
		$handler = function() use ( $value ) {
			return $value;
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->response_service->shouldReceive( 'json' )
			->andReturnUsing( function ( $data ) {
				return (new Psr7Response())->withBody( Psr7\stream_for( json_encode( $data ) ) );
			} );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run( $this->request, [], $handler );
		$this->assertEquals( $expected, $response->getBody()->read( strlen( $expected ) ) );
	}

	/**
	 * @covers ::toResponse
	 */
	public function testToResponse_ResponsableInterface_Psr7Response() {
		$input = Mockery::mock( ResponsableInterface::class );
		$expected = ResponseInterface::class;
		$handler = function() use ( $input ) {
			return $input;
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$input->shouldReceive( 'toResponse' )
			->andReturn( Mockery::mock( ResponseInterface::class ) );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run( $this->request, [], $handler );
		$this->assertInstanceOf( $expected, $response );
	}

	/**
	 * @covers ::toResponse
	 */
	public function testToResponse_Response_SameResponse() {
		$expected = Mockery::mock( ResponseInterface::class );
		$handler = function() use ( $expected ) {
			return $expected;
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run( $this->request, [], $handler );
		$this->assertSame( $expected, $response );
	}

	/**
	 * @covers ::executeHandler
	 */
	public function testExecuteHandler_ValidResponse_Response() {
		$expected = Mockery::mock( ResponseInterface::class );
		$handler = function() use ( $expected ) {
			return $expected;
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$this->assertSame( $expected, $subject->run( $this->request, [], $handler ) );
	}

	/**
	 * @covers ::executeHandler
	 * @expectedException \Exception
	 * @expectedExceptionMessage Response returned by controller is not valid
	 */
	public function testExecuteHandler_InvalidResponse_Exception() {
		$handler = function() {
			return null;
		};
		$error_handler = Mockery::mock( ErrorHandlerInterface::class )->shouldIgnoreMissing();
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $error_handler );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$error_handler->shouldReceive( 'getResponse' )
			->andReturnUsing( function ( $request, $exception ) {
				throw $exception;
			} );

		$subject->run( $this->request, [], $handler );
	}

	/**
	 * @covers ::executeMiddleware
	 */
	public function testExecuteMiddleware_EmptyList_CallsHandler() {
		$expected = 'Test complete';
		$handler = function () use ( $expected ) {
			return ( new Psr7\Response() )->withBody( Psr7\stream_for( $expected ) );
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run( $this->request, [], $handler );

		$this->assertEquals( $expected, $response->getBody()->read( strlen( $expected ) ) );
	}

	/**
	 * @covers ::executeMiddleware
	 */
	public function testExecuteMiddleware_ClassNames_CallsClassNamesFirstThenClosure() {
		$handler = function () {
			return (new Psr7Response())->withBody( Psr7\stream_for( 'Handler' ) );
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->injection_factory->shouldReceive( 'make' )
			->andReturnUsing( function ( $class ) {
				return new $class();
			} );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run(
			$this->request,
			[
				HttpKernelTestMiddlewareStub1::class,
				HttpKernelTestMiddlewareStub2::class,
				HttpKernelTestMiddlewareStub3::class,
			],
			$handler
		);

		$this->assertEquals( 'FooBarBazHandler', $response->getBody()->read( 999 ) );
	}

	/**
	 * @covers ::executeMiddleware
	 */
	public function testExecuteMiddleware_ClassNameWithParameters_PassParameters() {
		$handler = function () {
			return (new Psr7Response())->withBody( Psr7\stream_for( '' ) );
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->injection_factory->shouldReceive( 'make' )
			->andReturnUsing( function ( $class ) {
				return new $class();
			} );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$response = $subject->run(
			$this->request,
			[
				HttpKernelTestMiddlewareStubWithParameters::class . ':Arg1,Arg2',
			],
			$handler
		);

		$this->assertEquals( 'Arg1Arg2', $response->getBody()->read( 999 ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_SatisfiedRequest_Response() {
		$request = Mockery::mock( RequestInterface::class );
		$route = Mockery::mock( RouteInterface::class );
		$response = Mockery::mock( ResponseInterface::class );
		$arguments = ['foo', 'bar'];
		$route_arguments = ['baz'];
		$subject = Mockery::mock( HttpKernel::class, [$this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $request, $this->router, $this->error_handler] )->makePartial();

		$this->router->shouldReceive( 'execute' )
			->andReturn( $route );

		$request->shouldReceive( 'withAttribute' )
			->andReturn( $request );

		$route->shouldReceive( 'getArguments' )
			->andReturn( $route_arguments );

		$route->shouldReceive( 'getMiddleware' )
			->andReturn( [] );

		$route->shouldReceive( 'getHandler' )
			->andReturn( $this->factory_handler );

		$subject->shouldReceive( 'run' )
			->andReturnUsing( function ( $request, $middleware, $handler ) use ( $response ) {
				return $response;
			} );

		$this->assertSame( $response, $subject->handle( $request, $arguments ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_UnsatisfiedRequest_Null() {
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->router->shouldReceive( 'execute' )
			->andReturn( null );

		$this->assertNull( $subject->handle( $this->request, [] ) );
	}

	/**
	 * @covers ::run
	 */
	public function testRun_Middleware_ExecutedInOrder() {
		$handler = function () {
			return ( new Psr7\Response() )->withBody( Psr7\stream_for( 'Handler' ) );
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->injection_factory->shouldReceive( 'make' )
			->andReturnUsing( function ( $class ) {
				return new $class();
			} );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$subject->setMiddleware( [
			'middleware2' => HttpKernelTestMiddlewareStub2::class,
			'middleware3' => HttpKernelTestMiddlewareStub3::class,
		] );

		$subject->setMiddlewareGroups( [
			'global' => [HttpKernelTestMiddlewareStub1::class],
		] );

		$subject->setMiddlewarePriority( [
			HttpKernelTestMiddlewareStub1::class,
			HttpKernelTestMiddlewareStub2::class,
		] );

		$response = $subject->run( $this->request, [
			'middleware3',
			'middleware2',
			'global',
		], $handler );

		$this->assertEquals( 'FooBarBazHandler', $response->getBody()->read( 999 ) );
	}

	/**
	 * @covers ::run
	 * @expectedException \Exception
	 * @expectedExceptionMessage Test exception handled
	 */
	public function testRun_Exception_UseErrorHandler() {
		$exception = new Exception();
		$handler = function () use ( $exception ) {
			throw $exception;
		};
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->factory_handler->shouldReceive( 'make' )
			->andReturn( $handler );

		$this->factory_handler->shouldReceive( 'execute' )
			->andReturnUsing( $handler );

		$this->error_handler->shouldReceive( 'getResponse' )
			->with( $this->request, $exception )
			->andReturnUsing( function() {
				throw new Exception( 'Test exception handled' );
			} );

		$subject->run( $this->request, [], $handler );
	}

	/**
	 * @covers ::filterRequest
	 */
	public function testFilterRequest_NoFilter_Unfiltered() {
		$route1 = Mockery::mock( RouteInterface::class );
		$route2 = Mockery::mock( RouteInterface::class, HasQueryFilterInterface::class );
		$route3 = Mockery::mock( RouteInterface::class, HasQueryFilterInterface::class );
		$route4 = Mockery::mock( RouteInterface::class, HasQueryFilterInterface::class );
		$query_vars = ['unfiltered'];
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->router->shouldReceive( 'getRoutes' )
			->andReturn( [$route1, $route2, $route3] );

		$route1->shouldReceive( 'isSatisfied' )
			->andReturn( false );

		$route1->shouldNotReceive( 'applyQueryFilter' );

		$route2->shouldReceive( 'isSatisfied' )
			->andReturn( false );

		$route2->shouldNotReceive( 'applyQueryFilter' );

		$route3->shouldReceive( 'isSatisfied' )
			->andReturn( true )
			->once();

		$route3->shouldReceive( 'applyQueryFilter' )
			->with( $this->request, $query_vars )
			->andReturn( $query_vars );

		$route4->shouldNotReceive( 'isSatisfied' );

		$this->assertEquals( ['unfiltered'], $subject->filterRequest( $query_vars ) );
	}

	/**
	 * @covers ::filterRequest
	 */
	public function testFilterRequest_Filter_Filtered() {
		$route1 = Mockery::mock( RouteInterface::class );
		$route2 = Mockery::mock( RouteInterface::class, HasQueryFilterInterface::class );
		$route3 = Mockery::mock( RouteInterface::class, HasQueryFilterInterface::class );
		$query_vars = ['unfiltered'];
		$subject = new HttpKernel( $this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler );

		$this->router->shouldReceive( 'getRoutes' )
			->andReturn( [$route1, $route2, $route3] );

		$route1->shouldReceive( 'isSatisfied' )
			->andReturn( false );

		$route1->shouldNotReceive( 'applyQueryFilter' );

		$route2->shouldReceive( 'isSatisfied' )
			->andReturn( true );

		$route2->shouldReceive( 'applyQueryFilter' )
			->with( $this->request, $query_vars )
			->andReturn( ['filtered'] );

		$route3->shouldNotReceive( 'isSatisfied' );

		$this->assertEquals( ['filtered'], $subject->filterRequest( $query_vars ) );
	}

	/**
	 * @covers ::filterTemplateInclude
	 */
	public function testFilterTemplateInclude_Response_Override() {
		$response = Mockery::mock( ResponseInterface::class )->shouldIgnoreMissing();
		$container = Mockery::mock( ArrayAccess::class );
		$subject = Mockery::mock( HttpKernel::class, [$this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler] )->makePartial();

		$subject->shouldReceive( 'handle' )
			->andReturn( $response );

		$this->app->shouldReceive( 'getContainer' )
			->andReturn( $container );

		$container->shouldReceive( 'offsetSet' )
			->with( WPEMERGE_RESPONSE_KEY, $response );

		$this->assertEquals( WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php', $subject->filterTemplateInclude( '' ) );
	}

	/**
	 * @covers ::filterTemplateInclude
	 */
	public function testFilterTemplateInclude_404_ForcesWPQuery404() {
		global $wp_query;

		$response = Mockery::mock( ResponseInterface::class );
		$container = Mockery::mock( ArrayAccess::class );
		$subject = Mockery::mock( HttpKernel::class, [$this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler] )->makePartial();

		$response->shouldReceive( 'getStatusCode' )
			->andReturn( 404 );

		$subject->shouldReceive( 'handle' )
			->andReturn( $response );

		$this->app->shouldReceive( 'getContainer' )
			->andReturn( $container );

		$container->shouldReceive( 'offsetSet' )
			->with( WPEMERGE_RESPONSE_KEY, $response );

		$this->assertFalse( $wp_query->is_404() );
		$subject->filterTemplateInclude( '' );
		$this->assertTrue( $wp_query->is_404() );
	}

	/**
	 * @covers ::filterTemplateInclude
	 */
	public function testFilterTemplateInclude_NoResponse_Passthrough() {
		$subject = Mockery::mock( HttpKernel::class, [$this->app, $this->injection_factory, $this->handler_factory, $this->response_service, $this->request, $this->router, $this->error_handler] )->makePartial();

		$subject->shouldReceive( 'handle' )
			->andReturn( null );

		$this->assertEquals( 'foo', $subject->filterTemplateInclude( 'foo' ) );
	}
}

class HttpKernelTestMiddlewareStub1 {
	public function handle( RequestInterface $request, Closure $next ) {
		$response = $next( $request );

		return $response->withBody( Psr7\stream_for( 'Foo' . $response->getBody()->read( 999 ) ) );
	}
}

class HttpKernelTestMiddlewareStub2 {
	public function handle( RequestInterface $request, Closure $next ) {
		$response = $next( $request );

		return $response->withBody( Psr7\stream_for( 'Bar' . $response->getBody()->read( 999 ) ) );
	}
}

class HttpKernelTestMiddlewareStub3 {
	public function handle( RequestInterface $request, Closure $next ) {
		$response = $next( $request );

		return $response->withBody( Psr7\stream_for( 'Baz' . $response->getBody()->read( 999 ) ) );
	}
}

class HttpKernelTestMiddlewareStubWithParameters {
	public function handle( RequestInterface $request, Closure $next, $param1, $param2 ) {
		$response = $next( $request );

		return $response->withBody( Psr7\stream_for( $param1 . $param2 . $response->getBody()->read( 999 ) ) );
	}
}
