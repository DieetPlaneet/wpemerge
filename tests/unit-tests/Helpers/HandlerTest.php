<?php

namespace WPEmergeTests\Helpers;

use Mockery;
use WPEmerge\Helpers\Handler;
use stdClass;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Helpers\Handler
 */
class HandlerTest extends WP_UnitTestCase {
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
		$expected = function() {};

		$subject = new Handler( $expected );

		$this->assertSame( $expected, $subject->get() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 */
	public function testSet_Closure_Closure() {
		$expected = function() {};

		$subject = new Handler( $expected );

		$this->assertEquals( $expected, $subject->get() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @covers ::parseFromString
	 */
	public function testSet_ClassWithoutMethodWithDefault_Array() {
		$expected = [
			'class' => '\WPEmergeTestTools\TestService',
			'method' => 'defaultMethod',
			'namespace' => '',
		];

		$subject = new Handler( '\WPEmergeTestTools\TestService', 'defaultMethod' );

		$this->assertEquals( $expected, $subject->get() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @covers ::parseFromString
	 * @expectedException \WPEmerge\Exceptions\ConfigurationException
	 * @expectedExceptionMessage No or invalid handler
	 */
	public function testSet_ClassWithoutMethodWithoutDefault_Exception() {
		$subject = new Handler( '\WPEmergeTestTools\TestService' );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @covers ::parseFromString
	 */
	public function testSet_ClassAtMethod_Array() {
		$expected = [
			'class' => '\WPEmergeTestTools\TestService',
			'method' => 'getTest',
			'namespace' => '',
		];

		$subject = new Handler( '\WPEmergeTestTools\TestService@getTest' );

		$this->assertEquals( $expected, $subject->get() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @covers ::parseFromString
	 */
	public function testSet_ClassColonsMethod_Array() {
		$expected = [
			'class' => '\WPEmergeTestTools\TestService',
			'method' => 'getTest',
			'namespace' => '',
		];

		$subject = new Handler( '\WPEmergeTestTools\TestService::getTest' );

		$this->assertEquals( $expected, $subject->get() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @covers ::parseFromString
	 * @expectedException \WPEmerge\Exceptions\Exception
	 * @expectedExceptionMessage No or invalid handler
	 */
	public function testSet_InvalidString_ThrowException() {
		$subject = new Handler( '\WPEmergeTestTools\TestService' );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_Closure_CalledWithArguments() {
		$stub = new stdClass();
		$mock = Mockery::mock();
		$mock->shouldReceive( 'execute' )
			->with( $mock, $stub )
			->once();

		$closure = function( $mock, $stub ) {
			$mock->execute( $mock, $stub );
		};

		$subject = new Handler( $closure );
		$subject->execute( $mock, $stub );
		$this->assertTrue( true );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ClassAtMethod_CalledWithArguments() {
		$foo = 'foo';
		$bar = 'bar';
		$expected = (object) ['value' => $foo . $bar];

		$subject = new Handler( HandlerTestControllerMock::class . '@foobar' );
		$this->assertEquals( $expected, $subject->execute( 'foo', 'bar' ) );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ClosureReturningValue_ReturnSameValue() {
		$expected = new stdClass();
		$closure = function( $value ) {
			return $value;
		};

		$subject = new Handler( $closure );
		$this->assertSame( $expected, $subject->execute( $expected ) );
	}

	/**
	 * @covers ::execute
	 */
	public function testExecute_ClassWithPrefix_Execute() {
		$subject = new Handler( 'HandlerTestMock@foo', '', '\\WPEmergeTests\\Helpers\\' );
		$this->assertEquals( 'foo', $subject->execute() );
	}

	/**
	 * @covers ::execute
	 * @expectedException \WPEmerge\Application\ClassNotFoundException
	 * @expectedExceptionMessage Class not found
	 */
	public function testExecute_NonexistantClassWithPrefix_Exception() {
		$subject = new Handler( 'HandlerTestMock@foo', '', '\\WPEmergeTests\\NonexistantNamespace\\' );
		$this->assertEquals( 'foo', $subject->execute() );
	}

	/**
	 * @covers ::execute
	 * @expectedException \WPEmerge\Application\ClassNotFoundException
	 * @expectedExceptionMessage Class not found - tried
	 */
	public function testExecute_ClassWithoutPrefix_Exception() {
		$subject = new Handler( 'HandlerTestMock@foo' );
		$subject->execute();
	}
}

class HandlerTestMock {
	public function foo() {
		return 'foo';
	}
}

class HandlerTestControllerMock {
	public function foobar( $foo, $bar ) {
		return (object) ['value' => $foo . $bar];
	}
}
