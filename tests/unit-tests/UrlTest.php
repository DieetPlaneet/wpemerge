<?php

use CarbonFramework\Url;

class UrlTest extends WP_UnitTestCase {
    /**
     * @covers Url::getCurrentPath
     */
    public function testGetCurrentPath_Home_Slash() {
        $expected = '/';

        $mock_request = $this->createMock( CarbonFramework\Request::class );
        $mock_request->method( 'getUrl' )->willReturn( 'http://example.org/' );

        $this->assertEquals( $expected, Url::getCurrentPath( $mock_request ) );
    }

    /**
     * @covers Url::getCurrentPath
     */
    public function testGetCurrentPath_Subpage_RelativePath() {
        $expected = '/foo/bar/';

        $mock_request = $this->createMock( CarbonFramework\Request::class );
        $mock_request->method( 'getUrl' )->willReturn( 'http://example.org/foo/bar/' );

        $this->assertEquals( $expected, Url::getCurrentPath( $mock_request ) );
    }

    /**
     * @covers Url::addLeadingSlash
     */
    public function testAddLeadingSlash() {
        $this->assertEquals( '/example', Url::addLeadingSlash('example') );
        $this->assertEquals( '/example', Url::addLeadingSlash('/example') );
    }

    /**
     * @covers Url::removeLeadingSlash
     */
    public function testRemoveLeadingSlash() {
        $this->assertEquals( 'example', Url::removeLeadingSlash('/example') );
        $this->assertEquals( 'example', Url::removeLeadingSlash('example') );
    }

    /**
     * @covers Url::addTrailingSlash
     */
    public function testAddTrailingSlash() {
        $this->assertEquals( 'example/', Url::addTrailingSlash('example') );
        $this->assertEquals( 'example/', Url::addTrailingSlash('example/') );
    }

    /**
     * @covers Url::removeTrailingSlash
     */
    public function testRemoveTrailingSlash() {
        $this->assertEquals( 'example', Url::removeTrailingSlash('example/') );
        $this->assertEquals( 'example', Url::removeTrailingSlash('example') );
    }
}
