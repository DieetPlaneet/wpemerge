<?php

namespace WPEmergeTests\Routing\Conditions;

use Mockery;
use stdClass;
use WPEmerge\Requests\RequestInterface;
use WPEmerge\Routing\Conditions\ConditionFactory;
use WPEmerge\Routing\Conditions\ConditionInterface;
use WPEmerge\Routing\Conditions\CustomCondition;
use WPEmerge\Routing\Conditions\MultipleCondition;
use WPEmerge\Routing\Conditions\NegateCondition;
use WPEmerge\Routing\Conditions\PostIdCondition;
use WPEmerge\Routing\Conditions\UrlCondition;
use WP_UnitTestCase;

/**
 * @coversDefaultClass \WPEmerge\Routing\Conditions\ConditionFactory
 */
class ConditionFactoryTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$app = new \TestApp( new \Pimple\Container(), false );
		$app->bootstrap( [], false );
		$condition_types = $app->resolve( WPEMERGE_ROUTING_CONDITION_TYPES_KEY );

		$this->request = Mockery::mock( RequestInterface::class );
		$this->subject = new ConditionFactory( $condition_types );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();

		unset( $this->request );
		unset( $this->subject );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromUrl
	 */
	public function testMake_Url_UrlCondition() {
		$expected_param = '/foo/bar/';
		$expected_class = UrlCondition::class;

		$condition = $this->subject->make( $expected_param );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertEquals( $expected_param, $condition->getUrl() );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_ConditionInArray_ConditionInstance() {
		$expected_param = 10;
		$expected_class = PostIdCondition::class;

		$condition = $this->subject->make( ['post_id', $expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertEquals( $expected_param, $condition->getArguments( $this->request )['post_id'] );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_CustomConditionWithClosureInArray_CustonCondition() {
		$expected_param = function() {};
		$expected_class = CustomCondition::class;

		$condition = $this->subject->make( ['custom', $expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_CustomConditionWithCallableInArray_CustomCondition() {
		$expected_param = 'phpinfo';
		$expected_class = CustomCondition::class;

		$condition = $this->subject->make( ['custom', $expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 * @covers ::getConditionTypeClass
	 */
	public function testMake_ClosureInArray_CustomCondition() {
		$expected_param = function() {};
		$expected_class = CustomCondition::class;

		$condition = $this->subject->make( [$expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_CallableInArray_CustomCondition() {
		$expected_param = 'phpinfo';
		$expected_class = CustomCondition::class;

		$condition = $this->subject->make( [$expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::makeFromArrayOfConditions
	 */
	public function testMake_ArrayOfConditionsInArray_MultipleCondition() {
		$expected_param1 = function() {};
		$expected_param2 = Mockery::mock( PostIdCondition::class );
		$expected_class = MultipleCondition::class;

		$condition = $this->subject->make( [ [ $expected_param1 ], $expected_param2 ] );
		$this->assertInstanceOf( $expected_class, $condition );

		$condition_conditions = $condition->getConditions();
		$this->assertSame( $expected_param1, $condition_conditions[0]->getCallable() );
		$this->assertSame( $expected_param2, $condition_conditions[1] );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::isNegatedCondition
	 * @covers ::parseNegatedCondition
	 */
	public function testMake_ExclamatedConditionName_NegateCondition() {
		$expected_class = NegateCondition::class;

		$condition = $this->subject->make( ['!query_var', 'foo', 'bar'] );
		$this->assertInstanceOf( $expected_class, $condition );

		$this->assertEquals( ['query_var' => 'foo', 'value' => 'bar'], $condition->getArguments( $this->request ) );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 * @covers ::getConditionTypeClass
	 * @expectedException \Exception
	 * @expectedExceptionMessage Unknown condition
	 */
	public function testMake_UnknownConditionType_Exception() {
		$expected_param = 'foobar';

		$condition = $this->subject->make( [ $expected_param ] );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @expectedException \Exception
	 * @expectedExceptionMessage No condition type
	 */
	public function testMake_NoConditionType_Exception() {
		$this->subject->make( [] );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromArray
	 * @expectedException \Exception
	 * @expectedExceptionMessage does not exist
	 */
	public function testMake_NonexistantConditionType_Exception() {
		$subject = new ConditionFactory( ['nonexistant_condition_type' => 'Nonexistant\\Condition\\Type\\Class'] );
		$subject->make( ['nonexistant_condition_type'] );
	}

	/**
	 * @covers ::make
	 * @covers ::makeFromClosure
	 */
	public function testMake_Closure_CustomCondition() {
		$expected_param = function() {};
		$expected_class = CustomCondition::class;

		$condition = $this->subject->make( $expected_param );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::make
	 */
	public function testMake_Callable_UrlCondition() {
		$expected_param = 'phpinfo';
		$expected_class = UrlCondition::class;

		$condition = $this->subject->make( $expected_param );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertEquals( '/' . $expected_param . '/', $condition->getUrl() );
	}

	/**
	 * @covers ::make
	 * @expectedException \WPEmerge\Exceptions\ConfigurationException
	 * @expectedExceptionMessage Invalid condition options
	 */
	public function testMake_Object_Exception() {
		$this->subject->make( new stdClass() );
	}

	/**
	 * @covers ::condition
	 */
	public function testCondition() {
		$condition = Mockery::mock( ConditionInterface::class );
		$subject = Mockery::mock( ConditionFactory::class )->makePartial();

		$this->assertSame( $condition, $subject->condition( $condition ) );

		$subject->shouldReceive( 'make' )
			->with( '' )
			->once();

		$subject->condition( '' );
		$this->assertTrue( true );
	}

	/**
	 * @covers ::merge
	 */
	public function testMerge() {
		$condition1 = Mockery::mock( ConditionInterface::class );
		$condition2 = Mockery::mock( ConditionInterface::class );

		$this->assertNull( $this->subject->merge( '', '' ) );
		$this->assertSame( $condition1, $this->subject->merge( $condition1, '' ) );
		$this->assertSame( $condition1, $this->subject->merge( '', $condition1 ) );
		$this->assertInstanceOf( MultipleCondition::class, $this->subject->merge( $condition1, $condition2 ) );
	}

	/**
	 * @covers ::mergeConditions
	 */
	public function testMergeConditions() {
		$this->assertInstanceOf( MultipleCondition::class, $this->subject->mergeConditions(
			Mockery::mock( ConditionInterface::class ),
			Mockery::mock( ConditionInterface::class )
		) );

		$url1 = Mockery::mock( UrlCondition::class );
		$url2 = Mockery::mock( UrlCondition::class )->shouldIgnoreMissing();
		$expected = Mockery::mock( ConditionInterface::class );

		$url1->shouldReceive( 'concatenate' )
			->andReturn( $expected );

		$this->assertSame( $expected, $this->subject->mergeConditions( $url1, $url2 ) );
	}
}
