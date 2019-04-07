<?php
/**
 * @package   WPEmerge
 * @author    Atanas Angelov <atanas.angelov.dev@gmail.com>
 * @copyright 2018 Atanas Angelov
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
 * @link      https://wpemerge.com/
 */

namespace WPEmerge\Flash;

use WPEmerge\Facades\Application;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

/**
 * Provide flash dependencies.
 *
 * @codeCoverageIgnore
 */
class FlashServiceProvider implements ServiceProviderInterface {
	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$this->registerConfiguration( $container );
		$this->registerDependencies( $container );
		$this->registerFacades();
	}

	/**
	 * Register configuration options.
	 *
	 * @param  \Pimple\Container $container
	 * @return void
	 */
	protected function registerConfiguration( $container ) {
		$container[ WPEMERGE_ROUTING_GLOBAL_MIDDLEWARE_KEY ] = array_merge(
			$container[ WPEMERGE_ROUTING_GLOBAL_MIDDLEWARE_KEY ],
			[
				\WPEmerge\Flash\FlashMiddleware::class,
			]
		);

		$container[ WPEMERGE_ROUTING_MIDDLEWARE_PRIORITY_KEY ] = array_merge(
			$container[ WPEMERGE_ROUTING_MIDDLEWARE_PRIORITY_KEY ],
			[
				\WPEmerge\Flash\FlashMiddleware::class => 10,
			]
		);
	}

	/**
	 * Register dependencies.
	 *
	 * @param  \Pimple\Container $container
	 * @return void
	 */
	protected function registerDependencies( $container ) {
		$container[ WPEMERGE_FLASH_KEY ] = function ( $c ) {
			$session = null;
			if ( isset( $c[ WPEMERGE_SESSION_KEY ] ) ) {
				$session = &$c[ WPEMERGE_SESSION_KEY ];
			} else if ( isset( $_SESSION ) ) {
				$session = &$_SESSION;
			}
			return new \WPEmerge\Flash\Flash( $session );
		};
	}

	/**
	 * Register facades.
	 *
	 * @return void
	 */
	protected function registerFacades() {
		Application::facade( 'Flash', \WPEmerge\Facades\Flash::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public function bootstrap( $container ) {
		// Nothing to bootstrap.
	}
}
