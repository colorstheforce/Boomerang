<?php

namespace Boomerang\TypeExpectations\Iterate;

/**
 * Iterating Array Expectation
 *
 * Iterates over every element of an array, ensuring it is an array, and matching against passed structure expectations.
 *
 * @package Boomerang\TypeExpectations\Iterate
 */
class IterateArrayEx extends IterateStructureEx {

	public function match( $data ) {
		return parent::match($data) && static::validType($data);
	}

	/**
	 * Static so its overridable with static::
	 */
	protected static function validType( $data ) {
		return !self::isAssoc($data);
	}

	protected static function isAssoc( $arr ) {
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	public function getMatchingTypeName() {
		return 'array';
	}

}

