<?php

namespace CarbonFramework\Routing;

use Closure;
use Exception;
use Psr\Http\Message\RequestInterface;
use CarbonFramework\Url;
use CarbonFramework\Routing\Conditions\ConditionInterface;
use CarbonFramework\Routing\Conditions\Url as UrlCondition;


class RouteGroup implements RouteInterface {
	use Routable {
		addRoute as routableAddRoute;
	}

	protected $target = null;

	public function __construct( $target, Closure $callable ) {
		if ( is_string( $target ) ) {
			$target = new UrlCondition( $target );
		}

		if ( ! is_a( $target, UrlCondition::class ) ) {
			throw new Exception( 'Route groups can only use route strings.' );
		}

		$this->target = $target;

		$callable( $this );
	}

	protected function getSatisfiedRoute() {
		$routes = $this->getRoutes();
		foreach ( $routes as $route ) {
			if ( $route->satisfied() ) {
				return $route;
			}
		}
		return null;
	}

	public function satisfied() {
		$route = $this->getSatisfiedRoute();
		return $route !== null;
	}

	public function handle( RequestInterface $request ) {
		$route = $this->getSatisfiedRoute();
		return $route ? $route->handle( $request ) : null;
	}

	public function addRoute( $methods, $target, $handler ) {
		if ( is_string( $target ) ) {
			$target = new UrlCondition( $target );
		}

		if ( ! is_a( $target, UrlCondition::class ) ) {
			throw new Exception( 'Routes inside route groups can only use route strings.' );
		}

		$target = $this->target->concatenate( $target );
		return $this->routableAddRoute( $methods, $target, $handler );
	}
}
