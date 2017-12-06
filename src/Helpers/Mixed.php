<?php

namespace WPEmerge\Helpers;

class Mixed {
	/**
	 * Converts a value to an array containing this value unless it is an array
	 *
	 * @param  mixed   $argument
	 * @return array
	 */
	public static function toArray( $argument ) {
		if ( ! is_array( $argument ) ) {
			$argument = [$argument];
		}

		return $argument;
	}

	/**
	 * Executes a value depending on what type it is and returns the result
	 * Callable: call; return result
	 * Instance: call method; return result
	 * Class:    instantiate; call method; return result
	 * Other:    return value without taking any action
	 *
	 * @param  mixed  $entity
	 * @param  array  $arguments
	 * @param  string $method
	 * @return mixed
	 */
	public static function value( $entity, $arguments = [], $method = '__invoke' ) {
		if ( is_callable( $entity ) ) {
			return call_user_func_array( $entity, $arguments );
		}

		if ( is_object( $entity ) ) {
			return call_user_func_array( [$entity, $method], $arguments );
		}

		if ( static::isClass( $entity ) ) {
			return call_user_func_array( [new $entity(), $method], $arguments );
		}

		return $entity;
	}

	/**
	 * Check if a value is a valid class name
	 *
	 * @param  mixed   $class_name
	 * @return boolean
	 */
	public static function isClass( $class_name ) {
		return ( is_string( $class_name ) && class_exists( $class_name ) );
	}
}
