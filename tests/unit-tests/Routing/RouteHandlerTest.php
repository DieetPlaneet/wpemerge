<?php

namespace WPEmergeTests\Routing;

use Mockery;
use WPEmerge\Helpers\Handler;
use WPEmerge\Responses\ConvertibleToResponseInterface;
use WPEmerge\Routing\RouteHandler;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Routing\RouteHandler
 */
class RouteHandlerTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}

	/**
	 * @covers ::__construct
	 * @covers ::get
	 */
	public function testConstruct() {
		$closure = function() {};
		$expected = new Handler( $closure );

		$subject = new RouteHandler( $closure );

		$this->assertEquals( $expected, $subject->get() );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ClosureReturningString_OutputResponse() {
		$expected = 'foobar';
		$closure = function( $value ) {
			return $value;
		};

		$subject = new RouteHandler( $closure );
		$response = $subject->execute( $expected );
		$this->assertEquals( $expected, $response->getBody()->read( strlen( $expected ) ) );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ClosureReturningArray_JsonResponse() {
		$value = ['foo' => 'bar'];
		$expected = json_encode( $value );
		$closure = function( $value ) {
			return $value;
		};

		$subject = new RouteHandler( $closure );
		$response = $subject->execute( $value );
		$this->assertEquals( $expected, $response->getBody()->read( strlen( $expected ) ) );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ConvertibleToResponseInterface_Psr7Response() {
		$input = Mockery::mock( ConvertibleToResponseInterface::class );
		$expected = ResponseInterface::class;
		$closure = function() use ( $input ) {
			return $input;
		};

		$input->shouldReceive( 'toResponse' )
			->andReturn( Mockery::mock( ResponseInterface::class ) );

		$subject = new RouteHandler( $closure );
		$this->assertInstanceOf( $expected, $subject->execute() );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ClosureReturningResponse_SameResponse() {
		$expected = Mockery::mock( ResponseInterface::class );
		$closure = function() use ( $expected ) {
			return $expected;
		};

		$subject = new RouteHandler( $closure );
		$this->assertSame( $expected, $subject->execute() );
	}
}
