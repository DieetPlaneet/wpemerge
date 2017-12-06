<?php

namespace Obsidian\Framework;

use ReflectionException;
use ReflectionMethod;
use Exception;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Obsidian\Response;
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
	protected $booted = false;

	/**
	 * IoC container
	 *
	 * @var Container
	 */
	protected $container = null;

	/**
	 * Array of framework service providers
	 *
	 * @var string[]
	 */
	protected $service_proviers = [
		RoutingServiceProvider::class,
		FlashServiceProvider::class,
		OldInputServiceProvider::class,
		TemplatingServiceProvider::class,
		ControllersServiceProvider::class,
	];

	/**
	 * Constructor
	 *
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Get whether WordPress is in debug mode
	 *
	 * @return boolean
	 */
	public function debugging() {
		$debugging = ( defined( 'WP_DEBUG' ) && WP_DEBUG );
		$debugging = apply_filters( 'obsidian.debug', $debugging );
		return $debugging;
	}

	/**
	 * Get whether the framework has been booted
	 *
	 * @return boolean
	 */
	public function isBooted() {
		return $this->booted;
	}

	/**
	 * Throw an exception if the framework has not been booted
	 *
	 * @codeCoverageIgnore
	 * @throws Exception
	 * @return void
	 */
	protected function verifyBoot() {
		if ( ! $this->isBooted() ) {
			throw new Exception( static::class . ' must be booted first.' );
		}
	}

	/**
	 * Get the IoC container instance
	 *
	 * @return Container
	 */
	public function getContainer() {
		return $this->container;
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
	public function boot( $config = [] ) {
		if ( $this->isBooted() ) {
			throw new Exception( static::class . ' already booted.' );
		}

		do_action( 'obsidian.booting' );

		$container = $this->getContainer();
		$this->loadConfig( $container, $config );
		$this->loadServiceProviders( $container );
		$this->booted = true;

		do_action( 'obsidian.booted' );
	}

	/**
	 * Load config into the service container
	 *
	 * @codeCoverageIgnore
	 * @param  Container $container
	 * @param  array     $config
	 * @return void
	 */
	protected function loadConfig( Container $container, $config ) {
		$container = $this->getContainer();
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
	protected function loadServiceProviders( Container $container ) {
		$container['framework.service_providers'] = array_merge(
			$this->service_proviers,
			$container['framework.config']['providers']
		);

		$container['framework.service_providers'] = apply_filters(
			'obsidian.service_providers',
			$container['framework.service_providers']
		);

		$service_providers = array_map( function( $service_provider ) {
			return new $service_provider();
		}, $container['framework.service_providers'] );

		$this->registerServiceProviders( $service_providers, $container );
		$this->bootServiceProviders( $service_providers, $container );
	}

	/**
	 * Register all service providers
	 *
	 * @codeCoverageIgnore
	 * @param  \Obsidian\ServiceProviders\ServiceProviderInterface[] $service_providers
	 * @param  Container                                             $container
	 * @return void
	 */
	protected function registerServiceProviders( $service_providers, Container $container ) {
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
	protected function bootServiceProviders( $service_providers, Container $container ) {
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
	public function facade( $alias, $facade_class ) {
		AliasLoader::getInstance()->alias( $alias, $facade_class );
	}

	/**
	 * Resolve a dependency from the IoC container
	 *
	 * @param  string   $key
	 * @return mixed|null
	 */
	public function resolve( $key ) {
		$this->verifyBoot();

		if ( ! isset( $this->getContainer()[ $key ] ) ) {
			return null;
		}

		return $this->getContainer()[ $key ];
	}

	/**
	 * Create and return a class instance
	 *
	 * @param  string $class
	 * @return object
	 */
	public function instantiate( $class ) {
		$this->verifyBoot();

		$instance = $this->resolve( $class );

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
	public function respond( ResponseInterface $response ) {
		Response::respond( $response );
	}
}
