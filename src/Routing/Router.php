<?php

namespace WPEmerge\Routing;

use Exception;
use Psr\Http\Message\ResponseInterface;
use WPEmerge;
use WPEmerge\Request;
use WPEmerge\Response;

/**
 * Provide routing for site requests (i.e. all non-api requests)
 */
class Router implements HasRoutesInterface {
	use HasRoutesTrait;

	/**
	 * Current active route
	 *
	 * @var RouteInterface
	 */
	protected $current_route = null;

	/**
	 * Hook into WordPress actions
	 *
	 * @codeCoverageIgnore
	 * @return void
	 */
	public function boot() {
		add_action( 'init', array( $this, 'registerRewriteRules' ), 1000 );
		add_action( 'template_include', array( $this, 'execute' ), 1000 );
	}

	/**
	 * Register route rewrite rules with WordPress
	 *
	 * @codeCoverageIgnore
	 * @return void
	 */
	public function registerRewriteRules() {
		$rules = apply_filters( 'wpemerge.routing.rewrite_rules', [] );
		foreach ( $rules as $rule => $rewrite_to ) {
			add_rewrite_rule( $rule, $rewrite_to, 'top' );
		}
	}

	/**
	 * Add global middlewares and execute the first satisfied route (if any)
	 *
	 * @param  string $template
	 * @return string
	 */
	public function execute( $template ) {
		$routes = $this->getRoutes();
		$global_middleware = WPEmerge::resolve( WPEMERGE_ROUTING_GLOBAL_MIDDLEWARE_KEY );
		$request = Request::fromGlobals();

		foreach ( $routes as $route ) {
			$route->addMiddleware( $global_middleware );
		}

		foreach ( $routes as $route ) {
			if ( $route->isSatisfied( $request ) ) {
				$this->setCurrentRoute( $route );
				return $this->handle( $request, $route, $template );
			}
		}

		return $template;
	}

	/**
	 * Execute a route
	 *
	 * @throws Exception
	 * @param  Request        $request
	 * @param  RouteInterface $route
	 * @param  string         $template
	 * @return string
	 */
	protected function handle( Request $request, RouteInterface $route, $template ) {
		$response = $route->handle( $request, $template );

		if ( ! is_a( $response, ResponseInterface::class ) ) {
			if ( WPEmerge::debugging() ) {
				throw new Exception( 'Response returned by controller is not valid (expectected ' . ResponseInterface::class . '; received ' . gettype( $response ) . ').' );
			}
			$response = Response::error( Response::response(), 500 );
		}

		add_filter( 'wpemerge.response', function() use ( $response ) {
			return $response;
		} );

		return WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'template.php';
	}

	/**
	 * Get the current route
	 *
	 * @return RouteInterface
	 */
	public function getCurrentRoute() {
		return $this->current_route;
	}

	/**
	 * Set the current route
	 *
	 * @param  RouteInterface
	 * @return void
	 */
	public function setCurrentRoute( RouteInterface $current_route ) {
		$this->current_route = $current_route;
	}

	/**
	 * Handle ALL requests
	 *
	 * @param  string|Closure|null $handler
	 * @return RouteInterface
	 */
	public function handleAll( $handler = null ) {
		// match ANY request method
		// match ANY url
		// by default, use built-in WordPress controller
		return $this->any( '*', $handler );
	}
}
