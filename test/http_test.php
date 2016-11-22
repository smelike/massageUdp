<?php

	require_once './Workerman/Autoloader.php';

	use Workerman\Worker;
	
	$http_wk = new Worker('text://192.168.50.190:5678');

	$http_wk->onMessage = function ($connection, $data) 
	{
		
		var_dump($data);
		echo "<br/><br/>";
		$connection->send('hello world..');
	};

	Worker::runAll();
