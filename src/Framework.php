<?php

namespace Obsidian;

use ReflectionException;
use ReflectionMethod;
use Exception;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Obsidian\Support\Facade;
use Obsidian\Support\AliasLoader;
use Obsidian\Routing\RoutingServiceProvider;
use Obsidian\Flash\FlashServiceProvider;
use Obsidian\Input\OldInputServiceProvider;
use Obsidian\Templating\TemplatingServiceProvider;
use Obsidian\Controllers\ControllersServiceProvider;

/**
 * Main communication channel with the framework
 */
class Framework {
	/**
	 * Flag whether the framework has been booted
	 *
	 * @var boolean
	 */
	protected static $booted = false;

	/**
	 * IoC container
	 *
	 * @var Container
	 */
	protected static $container = null;

	/**
	 * Array of framework service providers
	 *
	 * @var string[]
	 */
	protected static $service_proviers = [
		RoutingServiceProvider::class,
		FlashServiceProvider::class,
		OldInputServiceProvider::class,
		TemplatingServiceProvider::class,
		ControllersServiceProvider::class,
	];

	/**
	 * Get whether WordPress is in debug mode
	 *
	 * @return boolean
	 */
	public static function debugging() {
		$debugging = ( defined( 'WP_DEBUG' ) && WP_DEBUG );
		$debugging = apply_filters( 'obsidian.debug', $debugging );
		return $debugging;
	}

	/**
	 * Get whether the framework has been booted
	 *
	 * @return boolean
	 */
	public static function isBooted() {
		return static::$booted;
	}

	/**
	 * Throw an exception if the framework has not been booted
	 *
	 * @codeCoverageIgnore
	 * @throws Exception
	 * @return void
	 */
	protected static function verifyBoot() {
		if ( ! static::isBooted() ) {
			throw new Exception( get_called_class() . ' must be booted first.' );
		}
	}

	/**
	 * Get the IoC container instance
	 *
	 * @return Container
	 */
	public static function getContainer() {
		// @codeCoverageIgnoreStart
		if ( static::$container === null ) {
			static::$container = new Container();
		}
		// @codeCoverageIgnoreEnd
		return static::$container;
	}

	/**
	 * Boot the framework
	 * WordPress's 'after_setup_theme' action is a good place to call this
	 *
	 * @codeCoverageIgnore
	 * @param  array     $config
	 * @throws Exception
	 * @return void
	 */
	public static function boot( $config = [] ) {
		if ( static::isBooted() ) {
			throw new Exception( get_called_class() . ' already booted.' );
		}

		do_action( 'obsidian.booting' );

		$container = static::getContainer();
		Facade::setFacadeApplication( $container );
		AliasLoader::getInstance()->register();

		static::loadConfig( $container, $config );
		static::loadServiceProviders( $container );

		static::$booted = true;

		do_action( 'obsidian.booted' );
	}

	/**
	 * Load config into the service container
	 *
	 * @param  Container $container
	 * @param  array     $config
	 * @return void
	 */
	protected static function loadConfig( Container $container, $config ) {
		$container = static::getContainer();
		$container['framework.config'] = array_merge( [
			'providers' => [],
		], $config );
	}

	/**
	 * Register and boot all service providers
	 *
	 * @codeCoverageIgnore
	 * @param  Container $container
	 * @return void
	 */
	protected static function loadServiceProviders( Container $container ) {
		$container['framework.service_providers'] = array_merge(
			static::$service_proviers,
			$container['framework.config']['providers']
		);

		$container['framework.service_providers'] = apply_filters(
			'obsidian.service_providers',
			$container['framework.service_providers']
		);

		$service_providers = array_map( function( $service_provider ) {
			return new $service_provider();
		}, $container['framework.service_providers'] );

		static::registerServiceProviders( $service_providers, $container );
		static::bootServiceProviders( $service_providers, $container );
	}

	/**
	 * Register all service providers
	 *
	 * @codeCoverageIgnore
	 * @param  \Obsidian\ServiceProviders\ServiceProviderInterface[] $service_providers
	 * @param  Container                                             $container
	 * @return void
	 */
	protected static function registerServiceProviders( $service_providers, Container $container ) {
		foreach ( $service_providers as $provider ) {
			$provider->register( $container );
		}
	}

	/**
	 * Boot all service providers
	 *
	 * @codeCoverageIgnore
	 * @param  \Obsidian\ServiceProviders\ServiceProviderInterface[] $service_providers
	 * @param  Container                                             $container
	 * @return void
	 */
	protected static function bootServiceProviders( $service_providers, Container $container ) {
		foreach ( $service_providers as $provider ) {
			$provider->boot( $container );
		}
	}

	/**
	 * Register a facade class
	 *
	 * @param  string $alias
	 * @param  string $facade_class
	 * @return void
	 */
	public static function facade( $alias, $facade_class ) {
		AliasLoader::getInstance()->alias( $alias, $facade_class );
	}

	/**
	 * Resolve a dependency from the IoC container
	 *
	 * @param  string   $key
	 * @return mixed|null
	 */
	public static function resolve( $key ) {
		static::verifyBoot();

		if ( ! isset( static::getContainer()[ $key ] ) ) {
			return null;
		}

		return static::getContainer()[ $key ];
	}

	/**
	 * Create and return a class instance
	 *
	 * @param  string $class
	 * @return object
	 */
	public static function instantiate( $class ) {
		static::verifyBoot();

		$instance = static::resolve( $class );

		if ( $instance === null ) {
			$instance = new $class();
		}

		return $instance;
	}

	/**
	 * Send output based on a response object
	 *
	 * @codeCoverageIgnore
	 * @param  ResponseInterface $response
	 * @return void
	 */
	public static function respond( ResponseInterface $response ) {
		Response::respond( $response );
	}
}
