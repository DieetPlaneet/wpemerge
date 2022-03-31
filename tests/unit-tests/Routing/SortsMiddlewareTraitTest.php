<?php

namespace WPEmergeTests\Routing;

use Mockery;
use WPEmerge\Routing\SortsMiddlewareTrait;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @coversDefaultClass \WPEmerge\Routing\SortsMiddlewareTrait
 */
class SortsMiddlewareTraitTest extends TestCase {
	public function tear_down() {
		Mockery::close();
	}

	/**
	 * @covers ::getMiddlewarePriorityForMiddleware
	 */
	public function testGetMiddlewarePriorityForMiddleware() {
		$subject = new SortsMiddlewareTraitTestImplementation();
		$subject->setMiddlewarePriority( [
			'middleware3',
			'middleware1',
		] );

		$this->assertEquals( -1, $subject->getMiddlewarePriorityForMiddleware( 'middleware2' ) );
		$this->assertEquals( 0, $subject->getMiddlewarePriorityForMiddleware( 'middleware1' ) );
		$this->assertEquals( 1, $subject->getMiddlewarePriorityForMiddleware( 'middleware3' ) );
		$this->assertEquals( 1, $subject->getMiddlewarePriorityForMiddleware( ['middleware3', 'foo'] ) );
	}

	/**
	 * @covers ::sortMiddleware
	 */
	public function testSortMiddleware() {
		$subject = new SortsMiddlewareTraitTestImplementation();
		$subject->setMiddlewarePriority( [
			'middleware1',
		] );

		$result = $subject->sortMiddleware( [
			'middleware2',
			'middleware1',
			'middleware3',
		] );

		$this->assertEquals( 'middleware1', $result[0] );
		$this->assertEquals( 'middleware2', $result[1] );
		$this->assertEquals( 'middleware3', $result[2] );
	}
}

class SortsMiddlewareTraitTestImplementation {
	use SortsMiddlewareTrait;
}
