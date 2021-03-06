<?php

namespace Boomerang;

$req      = new HttpRequest('http://httpbin.org/get');
$response = $req->makeRequest();

$valid = new HttpResponseValidator($response);
$valid->expectStatus(200)
	  ->expectHeaderContains('Content-Type', 'json');

Boomerang::addValidator($valid);
