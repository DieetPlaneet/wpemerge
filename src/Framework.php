<?php

namespace CarbonFramework;

use ReflectionException;
use ReflectionMethod;
use Exception;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use CarbonFramework\Facades\Facade;
use CarbonFramework\Support\AliasLoader;
use CarbonFramework\ServiceProviders\Routing as RoutingServiceProvider;
use CarbonFramework\ServiceProviders\Flash as FlashServiceProvider;
use CarbonFramework\ServiceProviders\OldInput as OldInputServiceProvider;

class Framework {
	protected static $booted = false;

	protected static $container = null;

	public static function debugging() {
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG );
	}

	public static function isBooted() {
		return static::$booted;
	}

	public static function verifyBoot() {
		if ( ! static::isBooted() ) {
			throw new Exception( get_called_class() . ' must be booted first.' );
		}
	}

	public static function getContainer() {
		if ( static::$container === null ) {
			static::$container = new Container();
		}
		return static::$container;
	}

	public static function boot( $config ) {
		if ( static::isBooted() ) {
			throw new Exception( get_called_class() . ' already booted.' );
		}
		static::$booted = true;

		$container = static::getContainer();

		$container['framework.config'] = array_merge( [
			'providers' => [],
		], $config );

		$container['framework.service_providers'] = array_merge( [
			RoutingServiceProvider::class,
			FlashServiceProvider::class,
			OldInputServiceProvider::class,
		], $container['framework.config']['providers'] );

		Facade::setFacadeApplication( $container );
		AliasLoader::getInstance()->register();

		static::loadServiceProviders( $container );
	}

	protected static function loadServiceProviders( $container ) {
		$container['framework.service_providers'] = apply_filters( 'carbon_framework_service_providers', $container['framework.service_providers'] );

		$service_providers = array_map( function( $service_provider ) {
			return new $service_provider();
		}, $container['framework.service_providers'] );

		static::registerServiceProviders( $service_providers, $container );
		static::bootServiceProviders( $service_providers, $container );
	}

	protected static function registerServiceProviders( $service_providers, $container ) {
		foreach ( $service_providers as $provider ) {
			$provider->register( $container );
		}
	}

	protected static function bootServiceProviders( $service_providers, $container ) {
		foreach ( $service_providers as $provider ) {
			$provider->boot( $container );
		}
	}

	public static function facade( $alias, $facade_class ) {
		AliasLoader::getInstance()->alias( $alias, $facade_class );
	}

	public static function resolve( $key ) {
		static::verifyBoot();

		if ( ! isset( static::getContainer()[ $key ] ) ) {
			return null;
		}
		return static::getContainer()[ $key ];
	}

	public static function instantiate( $class ) {
		static::verifyBoot();

		$instance = static::resolve( $class );
		if ( $instance === null ) {
			try {
				$reflection = new ReflectionMethod( $class, '__construct' );

				if ( ! $reflection->isPublic() ) {
					throw new Exception( $class . '::__construct() is not public.' );
				}

				$parameters = $reflection->getParameters();

				$required_parameters = array_filter( $parameters, function( $parameter ) {
					return ! $parameter->isOptional();
				} );

				if ( ! empty( $required_parameters ) ) {
					throw new Exception( $class . '::__construct() has requird parameters but could not be resolved from container. Did you miss to define it into the container?' );
				}
			} catch ( ReflectionException $e ) {
				// __constructor is not defined so we are free to create a new instance
			}

			$instance = new $class();
		}

		return $instance;
	}

	public static function respond( ResponseInterface $response ) {
		Response::respond( $response );
	}
}
