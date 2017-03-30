<?php

class TestWebServer {

	protected $pid = null;
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var int
	 */
	private $port;

	/**
	 * TestWebServer constructor.
	 */
	public function __construct( $host = "localhost", $port = 8123 ) {
		$this->host = $host;
		$this->port = $port;

		$this->start();
	}

	public function start() {
		$stdout = tempnam(sys_get_temp_dir(), 'boomserv-stdout-');
		$cmd    = "php -S {$this->host}:{$this->port} " . __DIR__ . '/server.php';

		$fullCmd = sprintf("%s > %s 2>&1 & echo $!",
			escapeshellcmd($cmd),
			escapeshellarg($stdout)
		);

		$this->pid = exec(
			$fullCmd,
			$o,
			$ret
		);

		if( !ctype_digit($this->pid) ) {
			throw new RuntimeException("Error starting server, received '{$this->pid}', expected int PID");
		}

		sleep(1); // just to make sure it's fully started up, maybe not nesessary

		if( !$this->isRunning() ) {
			throw new RuntimeException("Failed to start server. Is something already running on port {$port}?");
		}

		register_shutdown_function(function () {
			$this->shutdown();
		});
	}

	public function isRunning() {
		$result = shell_exec(sprintf("ps %d",
			$this->pid));
		if( count(preg_split("/\n/", $result)) > 2 ) {
			return true;
		}

		return false;
	}

	public function shutdown() {
		exec(sprintf('kill %d',
			$this->pid));
	}

	public function getServerRoot() {
		return "http://{$this->host}:{$this->port}";
	}
}