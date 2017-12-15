<?php

namespace WPEmerge\Routing;

use WPEmerge\Support\Facade;

/**
 * Provide access to the router service
 *
 * @codeCoverageIgnore
 */
class RouterFacade extends Facade {
	protected static function getFacadeAccessor() {
		return WPEMERGE_ROUTING_ROUTER_KEY;
	}
}
