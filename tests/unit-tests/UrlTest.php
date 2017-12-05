<?php

use Obsidian\Url;

/**
 * @coversDefaultClass \Obsidian\Url
 */
class UrlTest extends WP_UnitTestCase {
    /**
     * @covers ::getPath
     */
    public function testGetPath_Home_Slash() {
        $expected = '/';

        $mock_request = $this->getMockBuilder( Obsidian\Request::class )->disableOriginalConstructor()->getMock();
        $mock_request->method( 'getUrl' )->willReturn( 'http://example.org/' );

        $this->assertEquals( $expected, Url::getPath( $mock_request ) );
    }

    /**
     * @covers ::getPath
     */
    public function testGetPath_Subpage_RelativePath() {
        $expected = '/foo/bar/';

        $mock_request = $this->getMockBuilder( Obsidian\Request::class )->disableOriginalConstructor()->getMock();
        $mock_request->method( 'getUrl' )->willReturn( 'http://example.org/foo/bar/' );

        $this->assertEquals( $expected, Url::getPath( $mock_request ) );
    }

    /**
     * @covers ::getPath
     */
    public function testGetPath_QueryString_StripsQueryString() {
        $expected = '/foo/bar/';

        $mock_request = $this->getMockBuilder( Obsidian\Request::class )->disableOriginalConstructor()->getMock();
        $mock_request->method( 'getUrl' )->willReturn( 'http://example.org/foo/bar/?foo=bar&baz=foobarbaz' );

        $this->assertEquals( $expected, Url::getPath( $mock_request ) );
    }

    /**
     * @covers ::addLeadingSlash
     */
    public function testAddLeadingSlash() {
        $this->assertEquals( '/example', Url::addLeadingSlash('example') );
        $this->assertEquals( '/example', Url::addLeadingSlash('/example') );
    }

    /**
     * @covers ::removeLeadingSlash
     */
    public function testRemoveLeadingSlash() {
        $this->assertEquals( 'example', Url::removeLeadingSlash('/example') );
        $this->assertEquals( 'example', Url::removeLeadingSlash('example') );
    }

    /**
     * @covers ::addTrailingSlash
     */
    public function testAddTrailingSlash() {
        $this->assertEquals( 'example/', Url::addTrailingSlash('example') );
        $this->assertEquals( 'example/', Url::addTrailingSlash('example/') );
    }

    /**
     * @covers ::removeTrailingSlash
     */
    public function testRemoveTrailingSlash() {
        $this->assertEquals( 'example', Url::removeTrailingSlash('example/') );
        $this->assertEquals( 'example', Url::removeTrailingSlash('example') );
    }
}
