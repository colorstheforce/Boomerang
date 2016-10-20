<?php

namespace Boomerang;

use Boomerang\Factories\HttpResponseFactory;

/**
 * Utility for generating HTTP Requests and receiving Responses into `HttpResponse` objects.
 *
 * @package Boomerang
 */
class HttpRequest {

	private $curlInfo;

	/**
	 * @var string
	 */
	private $tmp;

	private $maxRedirects = 10;
	private $headers = array();
	private $endpointParts;
	private $cookies = array();
	private $cookiesFollowRedirects = false;
	private $postData = array();
	private $lastRequestTime = null;

	/**
	 * @var HttpResponseFactory
	 */
	private $responseFactory;

	/**
	 * @param string              $endpoint URI to request.
	 * @param HttpResponseFactory $responseFactory A factory for creating Response objects.
	 */
	public function __construct( $endpoint, HttpResponseFactory $responseFactory = null ) {
		$this->setEndpoint($endpoint);
		$this->tmp = sys_get_temp_dir() ?: '/tmp';

		if( $responseFactory === null ) {
			$this->responseFactory = new HttpResponseFactory();
		}
	}

	/**
	 * Retrieve a url param by name
	 *
	 * @param string $param The name of the param.
	 * @return string|array|null Null on failure.
	 */
	public function getUrlParam( $param ) {
		$params = $this->getUrlParams();

		return isset($params[$param]) ? $params[$param] : null;
	}

	/**
	 * Set a url param by name.
	 *
	 * @param string                 $param The name of the param.
	 * @param string|int|float|array $value
	 */
	public function setUrlParam( $param, $value ) {
		$params         = $this->getUrlParams();
		$params[$param] = $value;

		$this->endpointParts['query'] = http_build_query($params);
	}

	/**
	 * Get all url params.
	 *
	 * @return array
	 */
	public function getUrlParams() {
		$query = !empty($this->endpointParts['query']) ? $this->endpointParts['query'] : '';
		parse_str($query, $result);

		return $result;
	}

	/**
	 * Set outgoing params as an array of ParamName => Value
	 *
	 * @param array $params Params to set
	 */
	public function setUrlParams( array $params ) {
		$this->endpointParts['query'] = http_build_query($params);
	}

	/**
	 * Retrieve an outgoing header by name
	 *
	 * @param string $key The name of the header.
	 * @return string|null Null on failure.
	 */
	public function getHeader( $key ) {
		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}

	/**
	 * Set an outgoing header by name.
	 *
	 * @param string $key The name of the header.
	 * @param string $value The value to set the header to.
	 */
	public function setHeader( $key, $value ) {
		$this->headers[$key] = $value;
	}

	/**
	 * Get all set headers.
	 *
	 * @return array
	 */
	public function getHeaders() {
		$headers = $this->headers;
		if( !isset($headers['Accept']) ) {
			$headers['Accept'] = $this->detectAccept($this->getEndpoint());
		}

		return $headers;
	}

	/**
	 * Set outgoing headers as an array of HeaderName => Value
	 *
	 * @param array $headers Headers to set
	 */
	public function setHeaders( array $headers ) {
		$this->headers = $headers;
	}

	/**
	 * Set outgoing basic auth header.
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function setBasicAuth( $username, $password = '' ) {
		$this->setHeader('Authorization', "Basic " . base64_encode("{$username}:{$password}"));
	}

	/**
	 * Retrieve an post value by name.
	 *
	 * @param $key
	 * @return string|null
	 */
	public function getPost( $key ) {
		return isset($this->postData[$key]) ? $this->postData[$key] : null;
	}

	/**
	 * Set a named key of the post value
	 *
	 * @param $key
	 * @param $value
	 */
	public function setPost( $key, $value ) {
		$this->postData[$key] = $value;
	}

	/**
	 * Retrieve all queued post-data as an array.
	 *
	 * @return array
	 */
	public function getPostData() {
		return $this->postData;
	}

	/**
	 * Set all post data, whipping past values.
	 *
	 * @param array $post
	 */
	public function setPostData( array $post ) {
		$this->postData = $post;
	}

	/**
	 * Allows you to enable cookie's set by server re-posting following a redirect.
	 *
	 * Requires file system storage of a "cookie jar" file and is therefore disabled by default.
	 *
	 * @param bool $bool true/false to enable/disable respectively
	 */
	public function setCookiesFollowRedirects( $bool ) {
		$this->cookiesFollowRedirects = $bool;
	}

	/**
	 * Set outgoing cookies as an array of CookieName => Value
	 *
	 * @param array $cookies Cookies to set
	 */
	public function setCookies( array $cookies ) {
		$this->cookies = $cookies;
	}

	/**
	 * Set a named cookies outgoing value
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setCookie( $key, $value ) {
		$this->cookies[$key] = $value;
	}

	private function unparse_url( $parsed_url ) {
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
		$pass     = ($user || $pass) ? "{$pass}@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

		return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
	}

	/**
	 * Gets the request URI
	 *
	 * @return string
	 */
	public function getEndpoint() {
		return $this->unparse_url($this->endpointParts);
	}

	/**
	 * Sets the request URI
	 *
	 * @param string $endpoint
	 */
	public function setEndpoint( $endpoint ) {
		$this->endpointParts = parse_url($endpoint);
	}

	/**
	 * Execute the request
	 *
	 * @return HttpResponse An object representing the result of the request
	 */
	public function makeRequest() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->getEndpoint());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		if( $this->cookiesFollowRedirects ) {
			$cookiejar = tempnam($this->tmp, 'PHB');
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
		}

		if( $this->postData ) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->postData, '', '&'));
		}

		if( $this->cookies ) {
			curl_setopt($ch, CURLOPT_COOKIE, http_build_cookie($this->cookies));
		}

		curl_setopt($ch, CURLOPT_HEADER, 1);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getFlatHeaders());

		if( $this->maxRedirects ) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirects);
		}

		$startTime = microtime(true);
		$response  = curl_exec($ch);

		$this->lastRequestTime = microtime(true) - $startTime;

		$this->curlInfo = curl_getinfo($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers     = substr($response, 0, $header_size);

		$body = substr($response, $header_size);

		curl_close($ch);

		return $this->responseFactory->newInstance($body, $headers, $this);
	}

	/**
	 * @param string $endpoint
	 */
	private function detectAccept( $endpoint ) {
		$url  = parse_url($endpoint);
		$path = pathinfo($url['path']);
		if( isset($path['extension']) ) {
			switch( strtolower($path['extension']) ) {
				case 'json':
					return 'application/json';
					break;
				case 'xml':
					return 'application/xml';
			}
		}

		return 'application/json,application/xml,text/html,application/xhtml+xml,*/*';
	}

	/**
	 * Gets headers as a flattened array for cURL $key => $val --> $key: $val
	 *
	 * @return array
	 */
	private function getFlatHeaders() {
		$output = array();
		foreach( $this->getHeaders() as $key => $value ) {
			$output[] = "$key: $value";
		}

		return $output;
	}

	/**
	 * @return mixed
	 */
	public function getCurlInfo() {
		return $this->curlInfo;
	}

	/**
	 * Get the time the last request took in seconds a float
	 *
	 * @return null|float null if there is no last request
	 */
	public function getLastRequestTime() {
		return $this->lastRequestTime;
	}

	/**
	 * Get the current maximum number of redirects(hops) a request should follow.
	 *
	 * @return int
	 */
	public function getMaxRedirects() {
		return $this->maxRedirects;
	}

	/**
	 * Set the maximum number of redirects(hops) a request should follow.
	 *
	 * @param int $maxRedirects
	 */
	public function setMaxRedirects( $maxRedirects ) {
		$this->maxRedirects = $maxRedirects;
	}

}