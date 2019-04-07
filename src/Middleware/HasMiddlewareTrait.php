<?php
/**
 * @package   WPEmerge
 * @author    Atanas Angelov <atanas.angelov.dev@gmail.com>
 * @copyright 2018 Atanas Angelov
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
 * @link      https://wpemerge.com/
 */

namespace WPEmerge\Middleware;

use Closure;
use WPEmerge\Exceptions\ConfigurationException;
use WPEmerge\Facades\Framework;
use WPEmerge\Facades\Router;
use WPEmerge\Helpers\MixedType;
use WPEmerge\Requests\RequestInterface;

/**
 * Allow objects to have middleware
 */
trait HasMiddlewareTrait {
	/**
	 * Array of all registered middleware.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Check if the passed entity is a valid middleware.
	 *
	 * @param  mixed   $middleware
	 * @return boolean
	 */
	protected function isMiddleware( $middleware ) {
		return (
			$middleware instanceof Closure
			||
			is_a( $middleware, MiddlewareInterface::class, true )
		);
	}

	/**
	 * Get registered middleware.
	 *
	 * @return array
	 */
	public function getMiddleware() {
		return $this->middleware;
	}

	/**
	 * Set registered middleware.
	 * Accepts: a class name, an instance of a class, a Closure or an array of any of the previous.
	 *
	 * @throws ConfigurationException
	 * @param  string|\Closure|\WPEmerge\Middleware\MiddlewareInterface|array $middleware
	 * @return void
	 */
	public function setMiddleware( $middleware ) {
		$middleware = MixedType::toArray( $middleware );

		foreach ( $middleware as $item ) {
			if ( ! $this->isMiddleware( $item ) ) {
				throw new ConfigurationException(
					'Passed middleware must be a closure or the name or instance of a class which ' .
					'implements the ' . MiddlewareInterface::class . ' interface.'
				);
			}
		}

		// TODO this router dependency should be avoided.
		$this->middleware = Router::sortMiddleware( $middleware );
	}

	/**
	 * Add middleware.
	 * Accepts: a class name, an instance of a class, a Closure or an array of any of the previous.
	 *
	 * @param  string|\Closure|\WPEmerge\Middleware\MiddlewareInterface|array $middleware
	 * @return static                                                         $this
	 */
	public function addMiddleware( $middleware ) {
		$middleware = MixedType::toArray( $middleware );

		$this->setMiddleware( array_merge( $this->getMiddleware(), $middleware ) );

		return $this;
	}

	/**
	 * Alias for addMiddleware.
	 *
	 * @codeCoverageIgnore
	 * @param  string|\Closure|\WPEmerge\Middleware\MiddlewareInterface|array $middleware
	 * @return static                                                         $this
	 */
	public function middleware( $middleware ) {
		return call_user_func_array( [$this, 'addMiddleware'], func_get_args() );
	}

	/**
	 * Execute an array of middleware recursively (last in, first out).
	 *
	 * @param  array                               $middleware
	 * @param  RequestInterface                    $request
	 * @param  Closure                             $next
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function executeMiddleware( $middleware, RequestInterface $request, Closure $next ) {
		$top_middleware = array_shift( $middleware );

		if ( $top_middleware === null ) {
			return $next( $request );
		}

		$top_middleware_next = function ( $request ) use ( $middleware, $next ) {
			return $this->executeMiddleware( $middleware, $request, $next );
		};

		return MixedType::value( $top_middleware, [$request, $top_middleware_next], 'handle', [Framework::class, 'instantiate'] );
	}
}
