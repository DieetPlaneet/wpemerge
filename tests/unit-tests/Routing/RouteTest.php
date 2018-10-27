<?php

namespace WPEmergeTests\Routing;

use Mockery;
use Psr\Http\Message\ResponseInterface;
use WPEmerge;
use WPEmerge\Requests\Request;
use WPEmerge\Routing\Conditions\UrlCondition;
use WPEmerge\Routing\RouteHandler;
use WPEmerge\Routing\Route;
use WPEmerge\Routing\Conditions\ConditionInterface;
use stdClass;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Routing\Route
 */
class RouteTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}

	/**
	 * @covers ::__construct
	 * @covers ::getMethods
	 * @covers ::getCondition
	 * @covers ::getHandler
	 */
	public function testConstruct_ConditionInterface() {
		$expected_methods = ['FOO'];
		$expected_condition = Mockery::mock( ConditionInterface::class );
		$handler = function() {};
		$expected_handler = new RouteHandler( $handler );

		$subject = new Route( $expected_methods, $expected_condition, $handler );
		$this->assertEquals( $expected_methods, $subject->getMethods() );
		$this->assertSame( $expected_condition, $subject->getCondition() );
		$this->assertEquals( $expected_handler, $subject->getHandler() );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct_Closure() {
		$expected = function() {};

		$subject = new Route( [], $expected, function() {} );
		$this->assertEquals( $expected, $subject->getCondition()->getCallable() );
	}

	/**
	 * @covers ::__construct
	 * @expectedException \Exception
	 * @expectedExceptionMessage Route condition is not a valid
	 */
	public function testConstruct_Invalid() {
		$subject = new Route( [], new stdClass(), function() {} );
	}

	/**
	 * @covers ::getQueryFilter
	 * @covers ::setQueryFilter
	 */
	public function testGetQueryFilter() {
		$condition = Mockery::mock( ConditionInterface::class );
		$subject = new Route( [], $condition, function() {} );
		$filter = function() {};

		$subject->setQueryFilter( $filter );
		$this->assertSame( $filter, $subject->getQueryFilter() );
	}

	/**
	 * @covers ::addQueryFilter
	 * @covers ::removeQueryFilter
	 */
	public function testAddQueryFilter() {
		$condition = Mockery::mock( ConditionInterface::class );
		$subject = new Route( [], $condition, function() {} );

		$subject->addQueryFilter();
		$this->assertEquals( 1000, has_action( 'request', [$subject, 'applyQueryFilter'] ) );

		$subject->removeQueryFilter();
		$this->assertFalse( has_action( 'request', [$subject, 'applyQueryFilter'] ) );
	}

	/**
	 * @covers ::applyQueryFilter
	 */
	public function testApplyQueryFilter_NoFilter_NoChange() {
		$condition = Mockery::mock( ConditionInterface::class );
		$subject = new Route( [], $condition, function() {} );

		$this->assertEquals( [], $subject->applyQueryFilter( [] ) );
	}

	/**
	 * @covers ::applyQueryFilter
	 * @expectedException \WPEmerge\Exceptions\Exception
	 * @expectedExceptionMessage Routes with queries can only use URL conditions
	 */
	public function testApplyQueryFilter_NonUrlCondition_Exception() {
		$condition = Mockery::mock( ConditionInterface::class );
		$subject = new Route( [], $condition, function() {} );
		$subject->query( function() {} );

		$subject->applyQueryFilter( [] );
	}

	/**
	 * @covers ::applyQueryFilter
	 */
	public function testApplyQueryFilter_UnsatisfiedUrlCondition_NoChange() {
		$condition = Mockery::mock( UrlCondition::class );
		$subject = new Route( [], $condition, function() {} );
		$subject->query( function() {} );

		$condition->shouldReceive( 'isSatisfied' )
			  ->andReturn( false );

		$this->assertEquals( [], $subject->applyQueryFilter( [] ) );
	}

	/**
	 * @covers ::applyQueryFilter
	 */
	public function testApplyQueryFilter_SatisfiedUrlCondition_ArrayFiltered() {
		$arguments = ['arg1', 'arg2'];
		$condition = Mockery::mock( UrlCondition::class );
		$subject = new Route( [], $condition, function() {} );
		$subject->query( function( $query_vars, $arg1, $arg2 ) {
			return array_merge( $query_vars, [$arg1, $arg2] );
		} );

		$condition->shouldReceive( 'isSatisfied' )
			  ->andReturn( true );

		$condition->shouldReceive( 'getArguments' )
				  ->andReturn( $arguments );

		$this->assertEquals( ['arg0', 'arg1', 'arg2'], $subject->applyQueryFilter( ['arg0'] ) );
	}

	/**
	 * @covers ::isSatisfied
	 */
	public function testIsSatisfied() {
		$request = Mockery::mock( Request::class );
		$condition = Mockery::mock( ConditionInterface::class );

		$request->shouldReceive( 'getMethod' )
			->andReturn( 'FOO' );

		$condition->shouldReceive( 'isSatisfied' )
			->andReturn( true );

		$subject1 = new Route( ['BAR'], $condition, function() {} );
		$this->assertFalse( $subject1->isSatisfied( $request ) );

		$subject2 = new Route( ['FOO'], $condition, function() {} );
		$this->assertTrue( $subject2->isSatisfied( $request ) );

		$subject3 = new Route( ['FOO', 'BAR'], $condition, function() {} );
		$this->assertTrue( $subject3->isSatisfied( $request ) );
	}

	/**
	 * @covers ::isSatisfied
	 */
	public function testIsSatisfied_ConditionFalse_False() {
		$request = Mockery::mock( Request::class );
		$condition = Mockery::mock( ConditionInterface::class );

		$request->shouldReceive( 'getMethod' )
			->andReturn( 'FOO' );

		$condition->shouldReceive( 'isSatisfied' )
			->andReturn( false );

		$subject = new Route( ['FOO'], $condition, function() {} );
		$this->assertFalse( $subject->isSatisfied( $request ) );
	}

	/**
	 * @covers ::getArguments
	 */
	public function testGetArguments_PassThroughCondition() {
		$request = Mockery::mock( Request::class );
		$condition = Mockery::mock( ConditionInterface::class );
		$expected = ['foo'];

		$condition->shouldReceive( 'getArguments' )
				  ->with( $request )
				  ->andReturn( $expected );

		$subject = new Route( [], $condition, function() {} );
		$this->assertSame( $expected, $subject->getArguments( $request ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle() {
		$request = Mockery::mock( Request::class );
		$view = 'foobar.php';
		$condition = Mockery::mock( ConditionInterface::class );
		$expected = Mockery::mock( ResponseInterface::class );
		$subject = new Route( [], $condition, function( $a, $b, $c, $d ) use ( $request, $view, $expected ) {
			$this->assertEquals( [$request, $view, 'foo', 'bar'], [$a, $b, $c, $d] );
			return $expected;
		} );

		$condition->shouldReceive( 'getArguments' )
			->with( $request )
			->andReturn( ['foo', 'bar'] );

		$this->assertSame( $expected, $subject->handle( $request, $view ) );
	}
}
