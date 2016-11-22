<?php

	use \Workerman\Worker;
	use \Workerman\WebServer;

	require_once './Workerman/Autoloader.php';

	$webServer = new WebServer("http://192.168.50.168:8821");
	$webServer = $webServer->addRoot("camera.hk", "./public");
	
	Worker::runAll();
