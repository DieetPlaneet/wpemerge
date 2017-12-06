<?php

namespace Obsidian\Routing;

use Obsidian\Support\Facade;

/**
 * Provide access to router service
 *
 * @codeCoverageIgnore
 */
class RouterFacade extends Facade {
    protected static function getFacadeAccessor() {
        return OBSIDIAN_ROUTING_ROUTER_KEY;
    }
}
