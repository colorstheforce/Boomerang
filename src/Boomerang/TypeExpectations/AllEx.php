<?php

namespace Boomerang\TypeExpectations;

use Boomerang\Interfaces\TypeExpectationInterface;

/**
 * All Expectation
 *
 * Defines a requirement to match **all** structure definitions expectations.
 *
 * Example:
 *
 *     new AllEx(
 *         array(1,2,3),
 *         function($data) { return count($data) == 3; }
 *     );
 *
 * @package Boomerang\TypeExpectations
 */
class AllEx extends StructureEx {

	/**
	 * @var TypeExpectationInterface[]
	 */
	protected $structures;

	/**
	 * @param TypeExpectationInterface|callable|mixed $structure,... One or more structure definitions to match
	 */
	function __construct( $structure ) {
		$this->structures = func_get_args();
	}

	function match($data) {
		$pass = true;

		foreach($this->structures as $struct) {
			list($passing, $expectations) = $this->__validate($data, $struct);
			$this->addExpectationResults($expectations);
			$pass = $pass && $passing;
		}

		return $pass;
	}

	public function getMatchingTypeName() {
		return 'All (&&) Matcher';
	}
}