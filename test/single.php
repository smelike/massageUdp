<?php

	use Workerman\Worker;
  use Workerman\Connection\AsyncTcpConnection;

	require_once './Workerman/Autoloader.php';
  // 初始化一个worker容器，监听1234端口
	$worker = new Worker('http://192.168.50.168:8800');
  // 这里进程数必须设置为1
  $worker->count = 1;
  // worker进程启动后建立一个内部通讯端口
  /*
  $worker->onWorkerStart = function($worker)
  {
    
      // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
      $inner_text_worker = new Worker('udp://192.168.50.168:1234');
      $inner_text_worker->onMessage = function($connection, $buffer)
      {
          global $worker;
          // $data数组格式，里面有uid，表示向那个uid的页面推送数据
          echo 'udp-data';
          var_dump($buffer);
          $uid = isset($data['uid']) ? $data['uid'] : 'jamesxu_websocket';
          // 通过workerman，向uid的页面推送数据
          $ret = sendMessageByUid($uid, $buffer);
          // 返回推送结果
          $connection->send($ret ? $uid : $buffer);
      };
      $inner_text_worker->listen();
    
  };*/
  // 新增加一个属性，用来保存uid到connection的映射
  //$worker->uidConnections = array();
  // 当有客户端发来消息时执行的回调函数
  $worker->onMessage = function($connection, $data)use($worker)
  {
      /*
      echo "websocket-data:";
      var_dump($data);
      // 判断当前客户端是否已经验证,既是否设置了uid
      if(!isset($connection->uid))
      {
         // 没验证的话把第一个包当做uid（这里为了方便演示，没做真正的验证）
         $connection->uid = $data;
         /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
          * 实现针对特定uid推送数据
          */
         //$worker->uidConnections[$connection->uid] = $connection;
         //return;
      //}
        var_dump($data);
        udpRequest(join('#' , $data['post']));
	$connection->send("ok");

  };

  // 当有客户端连接断开时
  $worker->onClose = function ($connection)use($worker)
  {
      global $worker;
      if(isset($connection->uid) AND !empty($connection->uid))
      {
          // 连接断开时删除映射
          unset($worker->uidConnections[$connection->uid]);
      }
  };

  function sendMessageByUid ($uid, $message) {
      global $worker;
      //var_dump($worker->uidConnections[$uid]);
      if (isset ($worker->uidConnections[$uid]) AND !empty($connection->uid)) {
          $connection = $worker->uidConnections[$uid];
          $connection->send($message);
          return true;
      };
  };

  function udpRequest($data) 
  {
    $client = stream_socket_client("udp://192.168.50.191:1234");
    stream_socket_sendto($client, $data);
  };

	// 运行所有的worker（其实当前只定义了一个）
	Worker::runAll();
