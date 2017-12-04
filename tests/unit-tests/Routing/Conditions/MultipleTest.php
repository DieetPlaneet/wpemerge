<?php

use Obsidian\Routing\Conditions\Custom;
use Obsidian\Routing\Conditions\Multiple;
use Obsidian\Request;

/**
 * @coversDefaultClass \Obsidian\Routing\Conditions\Multiple
 */
class MultipleTest extends WP_UnitTestCase {
    /**
     * @covers ::__construct
     * @covers ::getConditions
     * @covers ::getArguments
     */
    public function testConstruct() {
        $condition1 = new Custom( '__return_true' );
        $condition2 = new Custom( '__return_false' );
        $request = Mockery::mock( Request::class )->shouldIgnoreMissing();

        $subject = new Multiple( [$condition1, $condition2] );

        $this->assertEquals( [$condition1, $condition2], $subject->getConditions() );
        $this->assertEquals( [], $subject->getArguments( $request ) );
    }

    /**
     * @covers ::satisfied
     */
    public function testSatisfied() {
        $condition1 = new Custom( '__return_true' );
        $condition2 = new Custom( '__return_false' );
        $request = Mockery::mock( Request::class )->shouldIgnoreMissing();

        $subject1 = new Multiple( [$condition1] );
        $this->assertTrue( $subject1->satisfied( $request ) );

        $subject2 = new Multiple( [$condition2] );
        $this->assertFalse( $subject2->satisfied( $request ) );

        $subject3 = new Multiple( [$condition1, $condition2] );
        $this->assertFalse( $subject3->satisfied( $request ) );
    }
}
