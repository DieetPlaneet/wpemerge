<?php
/**
 * Template used to override the loaded template file by WordPress when a route is handled
 */
// @codeCoverageIgnoreStart
use Obsidian\Framework;
$response = apply_filters( 'obsidian_response', null );
if ( $response !== null ) {
	Framework::respond( $response );
}
// @codeCoverageIgnoreEnd
