<?php

namespace Obsidian\Routing\Conditions;

use Obsidian\Request;

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
		$this->conditions = array_map( function( $condition ) {
			if ( is_a( $condition, ConditionInterface::class ) ) {
				return $condition;
			}
			return Factory::make( $condition );
		}, $conditions );
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

	/**
	 * Get all assigned conditions
	 *
	 * @return \Obsidian\Routing\Conditions\ConditionInterface[]
	 */
	public function getConditions() {
		return $this->conditions;
	}
}
