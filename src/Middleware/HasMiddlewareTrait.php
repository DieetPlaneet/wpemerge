<?php

namespace Obsidian\Middleware;

use Closure;
use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Allow objects to have middleware
 */
trait HasMiddlewareTrait {
	/**
	 * Array of all registered middleware
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Check if the passed entity is a valid middleware
	 *
	 * @param  mixed   $middleware
	 * @return boolean
	 */
	protected function isMiddleware( $middleware ) {
		if ( is_callable( $middleware ) ) {
			return true;
		}

		return is_a( $middleware, MiddlewareInterface::class, true );
	}

	/**
	 * Get registered middleware
	 *
	 * @return array
	 */
	public function getMiddleware() {
		return $this->middleware;
	}

	/**
	 * Add middleware
	 * Accepts: a class name, an instance of a class, a callable, an array of any of the previous
	 *
	 * @param  string|callable|\Obsidian\Middleware\MiddlewareInterface|array $middleware
	 * @return object                                                         $this
	 */
	public function addMiddleware( $middleware ) {
		if ( is_callable( $middleware ) && is_array( $middleware ) ) {
			$middleware = [$middleware];
		}

		if ( ! is_array( $middleware ) ) {
			$middleware = [$middleware];
		}

		foreach ( $middleware as $item ) {
			if ( ! $this->isMiddleware( $item ) ) {
				throw new Exception( 'Passed middleware must be a callable or the name or instance of a class which implements the ' . MiddlewareInterface::class . ' interface.' );
			}
		}

		$this->middleware = array_merge( $this->getMiddleware(), $middleware );
		return $this;
	}

	/**
	 * Alias for addMiddleware
	 *
	 * @codeCoverageIgnore
	 * @param  string|callable|\Obsidian\Middleware\middlewareInterface|array $middleware
	 * @return object                                                         $this
	 */
	public function add( $middleware ) {
		return call_user_func_array( [$this, 'addMiddleware'], func_get_args() );
	}

	/**
	 * Execute an array of middleware recursively (last in, first out)
	 *
	 * @param  array             $middleware
	 * @param  \Obsidian\Request $request
	 * @param  Closure           $next
	 * @return ResponseInterface
	 */
	public function executeMiddleware( $middleware, $request, Closure $next ) {
		$top_middleware = array_pop( $middleware );

		if ( $top_middleware === null ) {
			return $next( $request );
		}

		$top_middleware_next = function( $request ) use ( $middleware, $next ) {
			return $this->executeMiddleware( $middleware, $request, $next );
		};

		if ( is_callable( $top_middleware ) ) {
			return call_user_func( $top_middleware, $request, $top_middleware_next );
		}

		if ( is_string( $top_middleware ) && class_exists( $top_middleware ) ) {
			$instance = new $top_middleware();
			return $instance->handle( $request, $top_middleware_next );
		}

		return $top_middleware->handle( $request, $top_middleware_next );
	}
}
