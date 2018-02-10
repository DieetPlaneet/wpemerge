<?php
/**
 * Absolute path to framework's directory
 */
if ( ! defined( 'WPEMERGE_DIR' ) ) {
	define( 'WPEMERGE_DIR', __DIR__ );
}

/**
 * Service container keys and key prefixes
 */
if ( ! defined( 'WPEMERGE_FRAMEWORK_KEY' ) ) {
	define( 'WPEMERGE_FRAMEWORK_KEY', 'wpemerge.framework.framework' );
}

if ( ! defined( 'WPEMERGE_CONFIG_KEY' ) ) {
	define( 'WPEMERGE_CONFIG_KEY', 'wpemerge.config' );
}

if ( ! defined( 'WPEMERGE_SESSION_KEY' ) ) {
	define( 'WPEMERGE_SESSION_KEY', 'wpemerge.session' );
}

if ( ! defined( 'WPEMERGE_REQUEST_KEY' ) ) {
	define( 'WPEMERGE_REQUEST_KEY', 'wpemerge.request' );
}

if ( ! defined( 'WPEMERGE_RESPONSE_SERVICE_KEY' ) ) {
	define( 'WPEMERGE_RESPONSE_SERVICE_KEY', 'wpemerge.responses.response_service' );
}

if ( ! defined( 'WPEMERGE_ROUTING_ROUTER_KEY' ) ) {
	define( 'WPEMERGE_ROUTING_ROUTER_KEY', 'wpemerge.routing.router' );
}

if ( ! defined( 'WPEMERGE_ROUTING_CONDITIONS_KEY' ) ) {
	define( 'WPEMERGE_ROUTING_CONDITIONS_KEY', 'wpemerge.routing.conditions.' );
}

if ( ! defined( 'WPEMERGE_ROUTING_GLOBAL_MIDDLEWARE_KEY' ) ) {
	define( 'WPEMERGE_ROUTING_GLOBAL_MIDDLEWARE_KEY', 'wpemerge.routing.global_middleware' );
}

if ( ! defined( 'WPEMERGE_VIEW_SERVICE_KEY' ) ) {
	define( 'WPEMERGE_VIEW_SERVICE_KEY', 'wpemerge.view.view_service' );
}

if ( ! defined( 'WPEMERGE_VIEW_ENGINE_KEY' ) ) {
	define( 'WPEMERGE_VIEW_ENGINE_KEY', 'wpemerge.view.engine' );
}

if ( ! defined( 'WPEMERGE_VIEW_ENGINE_PHP_KEY' ) ) {
	define( 'WPEMERGE_VIEW_ENGINE_PHP_KEY', 'wpemerge.view.engine.php' );
}

if ( ! defined( 'WPEMERGE_FLASH_KEY' ) ) {
	define( 'WPEMERGE_FLASH_KEY', 'wpemerge.flash.flash' );
}

if ( ! defined( 'WPEMERGE_OLD_INPUT_KEY' ) ) {
	define( 'WPEMERGE_OLD_INPUT_KEY', 'wpemerge.old_input.old_input' );
}

if ( ! defined( 'WPEMERGE_SERVICE_PROVIDERS_KEY' ) ) {
	define( 'WPEMERGE_SERVICE_PROVIDERS_KEY', 'wpemerge.service_providers' );
}
