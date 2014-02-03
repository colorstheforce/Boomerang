<?php

namespace Boomerang\TypeExpectations;

use Boomerang\ExpectationResults\FailingExpectationResult;
use Boomerang\ExpectationResults\PassingExpectationResult;
use Boomerang\Interfaces\TypeExpectationInterface;
use Boomerang\Interfaces\ValidatorInterface;

/**
 * Structure Expectation
 *
 * Used to define rules about structure.
 *
 * @package Boomerang\TypeExpectations
 */
class StructureEx implements TypeExpectationInterface {

	protected $structure;
	protected $path = array();
	protected $expectationResults = array();
	/**
	 * @var ValidatorInterface
	 */
	private $validator;

	/**
	 * @param TypeExpectationInterface|callable|mixed $structure
	 */
	function __construct( $structure ) {
		$this->structure = $structure;
	}

	/**
	 * @return ValidatorInterface
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * @access private
	 * @param ValidatorInterface $validator
	 */
	public function setValidator( $validator ) {
		$this->validator = $validator;
	}

	public function match( $data ) {
		list($pass, $expectations) = $this->__validate($data, $this->structure);
		$this->addExpectationResults($expectations);

		return $pass;
	}

	/**
	 * @param       $data
	 * @param       $validation
	 * @param array $path
	 * @return array
	 */
	protected function __validate( $data, $validation, array $path = null ) {

		$expectations = array();

		if( !$path ) {
			$path = $this->path;
		}

		$pathName = $this->makePathName($path);

		$pass = true;

		if( is_array($validation) ) {
			if( is_array($data) ) {
				foreach( $validation as $key => $value ) {
					if( array_key_exists($key, $data) ) {
						list($passing, $sub_expectations) = $this->__validate($data[$key], $value, array_merge($path, array( $key )));
						$expectations = array_merge($expectations, $sub_expectations);
						$pass         = $passing && $pass;
					} else {
						$expectations[] = new FailingExpectationResult($this->validator, "Missing key\n { {$pathName} } ", $key);
					}
				}
			} else {
				$expectations[] = new FailingExpectationResult($this->validator, "Unexpected scalar\n { {$pathName} } ", $validation, $data);
			}
		} elseif( $validation instanceof StructureEx ) {
			$validation->setPath($path);
			$validation->setValidator($this->validator);

			$pass         = $validation->match($data);
			$expectations = array_merge($expectations, $validation->getExpectationResults());
		} elseif( $validation instanceof TypeExpectationInterface ) {
			if( !$pass = $validation->match($data) ) {
				$expectations[] = new FailingExpectationResult($this->validator, "Unexpected structure type check result\n { {$pathName} } ", $validation->getMatchingTypeName(), gettype($data));
			} else {
				$expectations[] = new PassingExpectationResult($this->validator, "Expected structure type check result\n { {$pathName} } ", gettype($data));
			}

		} elseif( $validation instanceof \Closure ) {
			$result = $validation($data);
			$pass   = $result === true;
			if( !$pass ) {
				$expectations[] = new FailingExpectationResult($this->validator, "Unexpected \\Closure structure validator result\n { {$pathName} } ", true, $result);
			} else {
				$expectations[] = new PassingExpectationResult($this->validator, "Expected \\Closure structure validator result\n { {$pathName} } ", $result);
			}

		} elseif( is_scalar($validation) ) {
			if( !$pass = $validation == $data ) {
				$expectations[] = new FailingExpectationResult($this->validator, "Unexpected value\n { {$pathName} } ", $validation, $data);
			} else {
				$expectations[] = new PassingExpectationResult($this->validator, "Expected value\n { {$pathName} } ", $validation);
			}

		}

		return array( $pass, $expectations );
	}

	private function makePathName( array $path ) {
		$s_path = "root";
		foreach( $path as $loc ) {
			if( is_numeric($loc) ) {
				$s_path .= "[$loc]";
			} else {
				$s_path .= ".$loc";
			}
		}

		return $s_path;
	}

	/**
	 * @access private
	 * @param array $path
	 * @return mixed
	 */
	public function setPath( array $path ) {
		$this->path = $path;
	}

	/**
	 * @access private
	 * @return array
	 */
	public function getExpectationResults() {
		return $this->expectationResults;
	}

	/**
	 * @param array $expectations
	 */
	protected function addExpectationResults( array $expectations ) {
		$this->expectationResults = array_merge($this->expectationResults, $expectations);
	}

	public function getMatchingTypeName() {
		return 'structure';
	}

}