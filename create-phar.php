<?php

$path = dirname(__FILE__) . '/';

$srcRoot = $path . "src";
$buildRoot = $path . "build";

$fpath = $buildRoot . "/phrisby.phar";
 
$phar = new Phar($fpath, 
	FilesystemIterator::CURRENT_AS_FILEINFO |     	FilesystemIterator::KEY_AS_FILENAME, "myapp.phar");

$phar->buildFromDirectory($path,'%(src|vendor)/.*\.php$%');

$phar->setStub(file_get_contents($path . 'build/stub.php'));

chmod($fpath, 0777);

echo "Done!\007" . PHP_EOL;