<?php

namespace Boomerang;

use Boomerang\Interfaces\Validator;

class ResponseValidator implements Validator {

	private $expectations = array();
	/**
	 * @var Response
	 */
	private $response;

	public function __construct( Response $response ) {
		$this->response = $response;
	}

	/**
	 * @return \Boomerang\Response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * @param int  $expected_status
	 * @param null $hop
	 * @return $this
	 */
	public function expectStatus( $expected_status = 200, $hop = null ) {

		$status = $this->response->getStatus($hop);

		if( $status != $expected_status ) {
			$this->expectations[] = new ExpectResult(true, $this, "Unexpected HTTP Status", $expected_status, $status);
		} else {
			$this->expectations[] = new ExpectResult(false, $this);
		}

		return $this;
	}

	/**
	 * @param string   $key
	 * @param string   $value
	 * @param null|int $hop
	 * @return $this
	 */
	public function expectHeader( $key, $value, $hop = null ) {
		$header = $this->response->getHeader($key, $hop);

		if( $header != $value ) {
			$this->expectations[] = new ExpectResult(true, $this, 'Unexpected Header Exact Match: ' . var_export($key, true), $value, $header);
		} else {
			$this->expectations[] = new ExpectResult(false, $this);
		}

		return $this;
	}

	/**
	 * @param  string  $key
	 * @param   string $value
	 * @param null|int $hop
	 * @return $this
	 */
	public function expectHeaderContains( $key, $value, $hop = null ) {
		$header = $this->response->getHeader($key, $hop);

		if( !$header || strpos($header, $value) === false ) {
			$this->expectations[] = new ExpectResult(true, $this, 'Unexpected Header Contains: ' . var_export($key, true), $value, $header);
		} else {
			$this->expectations[] = new ExpectResult(false, $this);
		}

		return $this;
	}

	/**
	 * @param string $expectedContent
	 * @return $this
	 */
	public function expectBody( $expectedContent ) {
		$content = $this->response->getBody();

		if( $content != $expectedContent ) {
			$this->expectations[] = new ExpectResult(true, $this, 'Unexpected body: ', $expectedContent, $content);
		} else {
			$this->expectations[] = new ExpectResult(false, $this);
		}

		return $this;
	}

	/**
	 * @param string $expectedContent
	 * @return $this
	 */
	public function expectBodyContains( $expectedContent ) {
		$content = $this->response->getBody();

		if( $content != $expectedContent ) {
			$this->expectations[] = new ExpectResult(true, $this, 'Unexpected body: ', $expectedContent, $content);
		} else {
			$this->expectations[] = new ExpectResult(false, $this);
		}

		return $this;
	}

	public function getExpectationResults() {
		return $this->expectations;
	}

}