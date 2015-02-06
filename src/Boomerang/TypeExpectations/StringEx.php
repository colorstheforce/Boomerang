<?php

namespace Boomerang\TypeExpectations;

use Boomerang\Interfaces\TypeExpectationInterface;

/**
 * String Expectation
 *
 * Define a string matching placeholder expectation
 *
 * @package Boomerang\TypeExpectations
 */
class StringEx implements TypeExpectationInterface {

	protected $minLength;
	protected $maxLength;

	/**
	 * @param null|int $minLength Optional minimum length in bytes of a valid value
	 * @param null|int $maxLength Optional maximum length in bytes of a valid value
	 */
	public function __construct( $minLength = null, $maxLength = null ) {
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}

	public function match( $data ) {
		return is_string($data)
			   && $this->rangeValidation($data);
	}

	protected function rangeValidation( $data ) {
		$len = strlen($data);

		return ($len >= $this->minLength || $this->minLength === null)
			   && ($len <= $this->maxLength || $this->maxLength === null);
	}

	public function getMatchingTypeName() {
		return sprintf('string{%s,%s}', intval($this->minLength), is_null($this->maxLength) ? '∞' : $this->maxLength);
	}

}