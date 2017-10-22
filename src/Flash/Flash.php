<?php

namespace CarbonFramework\Flash;

use Exception;

/**
 * Provide a way to flash data into the session for the next request
 */
class Flash {
	/**
	 * Storage array or object implementing ArrayAccess
	 * @var array|\ArrayAccess
	 */
	protected $storage = null;

	/**
	 * Key to store flashed data in storage with
	 * 
	 * @var string
	 */
	protected $storage_key = '__carbonFrameworkFlash';

	/**
	 * Constructor
	 * 
	 * @param array|\ArrayAccess $storage
	 */
	public function __construct( &$storage ) {
		if ( $this->isValidStorage( $storage ) ) {
			if ( ! isset( $storage[ $this->storage_key ] ) ) {
				$storage[ $this->storage_key ] = [];
			}
			$this->storage = &$storage[ $this->storage_key ];
		}
	}

	/**
	 * Return whether a storage object is valid
	 * 
	 * @param  any     $storage
	 * @return boolean
	 */
	protected function isValidStorage( $storage ) {
		return $storage !== null;
	}

	/**
	 * Throw an exception if storage is not valid
	 * 
	 * @throws Exception
	 * @return null
	 */
	protected function validateStorage() {
		if ( ! $this->isValidStorage( $this->storage ) ) {
			throw new Exception( 'Attempted to use Flash without an active session. Did you forget to call session_start()?' );
		}
	}

	/**
	 * Return whether the flash service is enabled
	 * 
	 * @return boolean
	 */
	public function enabled() {
		return $this->isValidStorage( $this->storage );
	}

	/**
	 * Get and clear the entire storage or the values for a key
	 * 
	 * @param  string|null $key
	 * @return array|\ArrayAccess
	 */
	public function get( $key = null ) {
		$this->validateStorage();

		$items = $this->peek( $key );
		$this->clear( $key );
		return $items;
	}

	/**
	 * Get the entire storage or the values for a key
	 * 
	 * @param  string|null $key
	 * @return array|\ArrayAccess
	 */
	public function peek( $key = null ) {
		$this->validateStorage();
		
		if ( $key === null ) {
			return $this->storage;
		}
		
		if ( isset( $this->storage[ $key ] ) ) {
			return $this->storage[ $key ];
		}

		return [];
	}

	/**
	 * Add values for a key
	 * 
	 * @param string $key
	 * @param any    $new_items
	 */
	public function add( $key, $new_items ) {
		$this->validateStorage();
		
		$new_items = is_array( $new_items ) ? $new_items : [$new_items];

		$items = (array) $this->peek( $key );
		$items = array_merge( $items, $new_items );
		
		if ( $key === null ) {
			$this->storage = $items;
		} else {
			$this->storage[ $key ] = $items;
		}
	}

	/**
	 * Clear the entire storage or the values for a key
	 * 
	 * @param  string|null $key
	 * @return null
	 */
	public function clear( $key = null ) {
		$this->validateStorage();
		
		if ( $key === null ) {
			$this->storage = [];
		} else {
			$this->storage[ $key ] = [];
		}
	}
}