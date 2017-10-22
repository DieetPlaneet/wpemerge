<?php

namespace CarbonFramework\Routing\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface for HasMiddlewareTrait
 */
interface HasMiddlewareInterface {
	/**
	 * Get registered middleware
	 *
	 * @return \CarbonFramework\Routing\Middleware\MiddlewareInterface[]
	 */
	public function getMiddleware();

	/**
	 * Add middleware
	 *
	 * @param  string|callable|\CarbonFramework\Routing\Middleware\MiddlewareInterface|array $middleware
	 * @return object
	 */
	public function addMiddleware( $middleware );

	/**
	 * Alias for addMiddleware
	 *
	 * @param  string|callable|\CarbonFramework\Routing\Middleware\MiddlewareInterface|array $middleware
	 * @return object
	 */
	public function add( $middleware );

	/**
	 * Execute an array of middleware recursively (last in, first out)
	 *
	 * @param  \CarbonFramework\Routing\Middleware\MiddlewareInterface[] $middleware
	 * @param  any                                                       $request
	 * @param  Closure                                                   $next
	 * @return ResponseInterface
	 */
	public function executeMiddleware( $middleware, $request, Closure $next );
}
