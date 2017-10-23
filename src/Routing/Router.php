<?php

namespace CarbonFramework\Routing;

use Exception;
use Psr\Http\Message\ResponseInterface;
use CarbonFramework\Framework;
use CarbonFramework\Request;
use CarbonFramework\Response as FrameworkResponse;

/**
 * Provide routing
 */
class Router {
	use HasRoutesTrait;

	/**
	 * Hook into WordPress actions
	 * 
	 * @return null
	 */
	public function boot() {
		add_action( 'init', array( $this, 'registerRewriteRules' ), 1000 );
		add_action( 'template_include', array( $this, 'execute' ), 1000 );
	}

	/**
	 * Register route rewrite rules with WordPress
	 * 
	 * @return null
	 */
	public function registerRewriteRules() {
		$rules = apply_filters( 'carbon_framework_routing_rewrite_rules', [] );
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
		$global_middleware = Framework::resolve( 'framework.routing.global_middleware' );
		$request = Request::fromGlobals();

		foreach ( $routes as $route ) {
			$route->addMiddleware( $global_middleware );
		}

		foreach ( $routes as $route ) {
			if ( $route->satisfied( $request ) ) {
				return $this->handle( $request, $route );
			}
		}
		
		return $template;
	}

	/**
	 * Execute a route
	 * 
	 * @param  RouteInterface $route
	 * @return string
	 */
	protected function handle( Request $request, RouteInterface $route ) {
		$response = $route->handle( $request );

		if ( ! is_a( $response, ResponseInterface::class ) ) {
			if ( Framework::debugging() ) {
				throw new Exception( 'Response returned by controller is not valid (expectected ' . ResponseInterface::class . '; received ' . gettype( $response ) . ').' );
			}
			$response = FrameworkResponse::error( FrameworkResponse::response(), 500 );
		}

		add_filter( 'carbon_framework_response', function() use ( $response ) {
			return $response;
		} );

		return CARBON_FRAMEWORK_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'template.php';
	}
}
