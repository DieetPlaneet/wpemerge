<?php

namespace WPEmerge\Flash;

use ArrayAccess;
use Exception;
use WPEmerge\Helpers\Mixed;
use WPEmerge\Support\Arr;

/**
 * Provide a way to flash data into the session for the next request.
 */
class Flash {
	/**
	 * Keys for different request contexts.
	 */
	const CURRENT_KEY = 'current';
	const NEXT_KEY = 'next';

	/**
	 * Key to store flashed data in store with.
	 *
	 * @var string
	 */
	protected $store_key = '';

	/**
	 * Root store array or object implementing ArrayAccess.
	 *
	 * @var array|\ArrayAccess
	 */
	protected $store = null;

	/**
	 * Flash store array.
	 *
	 * @var array
	 */
	protected $flashed = [];

	/**
	 * Constructor.
	 *
	 * @param array|\ArrayAccess $store
	 * @param string             $store_key
	 */
	public function __construct( &$store, $store_key = '__wpemergeFlash' ) {
		$this->store_key = $store_key;
		$this->setStore( $store );
	}

	/**
	 * Get whether a store object is valid.
	 *
	 * @param  mixed   $store
	 * @return boolean
	 */
	protected function isValidStore( $store ) {
		return ( is_array( $store ) || $store instanceof ArrayAccess );
	}

	/**
	 * Throw an exception if store is not valid.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function validateStore() {
		if ( ! $this->isValidStore( $this->store ) ) {
			throw new Exception( 'Attempted to use Flash without an active session. Did you forget to call session_start()?' );
		}
	}

	/**
	 * Get the store for flash messages.
	 *
	 * @return array|\ArrayAccess
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * Set the store for flash messages.
	 *
	 * @param  array|\ArrayAccess $store
	 * @return void
	 */
	public function setStore( &$store ) {
		if ( ! $this->isValidStore( $store ) ) {
			return;
		}

		$this->store = &$store;

		if ( ! isset( $this->store[ $this->store_key ] ) ) {
			$this->store[ $this->store_key ] = [
				static::CURRENT_KEY => [],
				static::NEXT_KEY => [],
			];
		}

		$this->flashed = $store[ $this->store_key ];
	}

	/**
	 * Get whether the flash service is enabled.
	 *
	 * @return boolean
	 */
	public function enabled() {
		return $this->isValidStore( $this->store );
	}

	/**
	 * Save flashed data to store.
	 *
	 * @return void
	 */
	public function save() {
		$this->store[ $this->store_key ] = $this->flashed;
	}

	/**
	 * Get the entire store or the values for a key for a request.
	 *
	 * @param  boolean     $next
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed
	 */
	protected function getFromRequest( $next, $key = null, $default = null ) {
		$this->validateStore();

		$request_key = $next ? static::NEXT_KEY : static::CURRENT_KEY;

		if ( $key === null ) {
			return $this->flashed[ $request_key ];
		}

		return Arr::get( $this->flashed[ $request_key ], $key, $default );
	}

	/**
	 * Add values for a key for a request.
	 *
	 * @param  boolean $next
	 * @param  string  $key
	 * @param  mixed   $new_items
	 * @return void
	 */
	protected function addToRequest( $next, $key, $new_items ) {
		$this->validateStore();

		$request_key = $next ? static::NEXT_KEY : static::CURRENT_KEY;
		$new_items = Mixed::toArray( $new_items );
		$items = Mixed::toArray( $this->get( $key, [] ) );
		$this->flashed[ $request_key ][ $key ] = array_merge( $items, $new_items );
	}

	/**
	 * Remove all values or values for a key from a request.
	 *
	 * @param  boolean     $next
	 * @param  string|null $key
	 * @return void
	 */
	protected function clearFromRequest( $next, $key = null ) {
		$this->validateStore();

		$request_key = $next ? static::NEXT_KEY : static::CURRENT_KEY;
		$keys = $key === null ? array_keys( $store ) : [$key];
		$this->flashed[ $request_key ] = [];
	}

	/**
	 * Shift current store and replace it with next store.
	 *
	 * @return void
	 */
	public function shift() {
		$this->validateStore();

		$this->flashed[ static::CURRENT_KEY ] = $this->flashed[ static::NEXT_KEY ];
		$this->flashed[ static::NEXT_KEY ] = [];
	}

	/**
	 * Add values for a key for the next request.
	 *
	 * @param  string $key
	 * @param  mixed  $new_items
	 * @return void
	 */
	public function add( $key, $new_items ) {
		$this->addToRequest( true, $key, $new_items );
	}

	/**
	 * Add values for a key for the current request.
	 *
	 * @param string $key
	 * @param mixed  $new_items
	 */
	public function addNow( $key, $new_items ) {
		$this->addToRequest( false, $key, $new_items );
	}

	/**
	 * Get the entire store or the values for a key for the current request.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed
	 */
	public function get( $key = null, $default = null ) {
		return $this->getFromRequest( false, $key, $default );
	}

	/**
	 * Get the entire store or the values for a key for the next request.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed
	 */
	public function getNext( $key = null, $default = null ) {
		return $this->getFromRequest( true, $key, $default );
	}

	/**
	 * Clear the entire store or the values for a key for the current request.
	 *
	 * @param  string|null $key
	 * @return void
	 */
	public function clear( $key = null ) {
		$this->clearFromRequest( false, $key );
	}

	/**
	 * Clear the entire store or the values for a key for the next request.
	 *
	 * @param  string|null $key
	 * @return void
	 */
	public function clearNext( $key = null ) {
		$this->clearFromRequest( true, $key );
	}
}
