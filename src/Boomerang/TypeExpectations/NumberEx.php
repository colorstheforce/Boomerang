<?php

namespace Boomerang\TypeExpectations;

use Boomerang\Interfaces\TypeExpectation;

class NumberEx implements TypeExpectation {

	protected $min;
	protected $max;

	public function __construct( $min = null, $max = null ) {
		$this->min = $min;
		$this->max = $max;
	}

	public function match( $data ) {
		// TODO: Implement match() method.
	}

	protected function rangeValidation( $data ) {
		return ($data >= $this->min || $this->min === null)
		&& ($data <= $this->max || $this->max === null);
	}

}