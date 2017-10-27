<?php

namespace CarbonFramework\Routing\Conditions;

use CarbonFramework\Request;

/**
 * Check against a custom callable
 */
class Multiple implements ConditionInterface {
	/**
	 * Array of conditions to check
	 *
	 * @var array<ConditionInterface>
	 */
	protected $conditions = [];

	/**
	 * Constructor
	 *
	 * @param array $conditions
	 */
	public function __construct( $conditions ) {
		$this->conditions = array_map( [Factory::class, 'make'], $conditions );
	}

	/**
	 * {@inheritDoc}
	 */
	public function satisfied( Request $request ) {
		foreach ( $this->conditions as $condition ) {
			if ( ! $condition->satisfied( $request ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getArguments( Request $request ) {
		return [];
	}
}
