<?php

  use Workerman\Worker;
  require_once '/Library/WebServer/Documents/Camerawk/Workerman/Autoloader.php';
  // 初始化一个worker容器，监听1234端口
	$worker = new Worker('udp://192.168.50.168:1234');
  // 这里进程数必须设置为1
  $worker->count = 1;

  // worker进程启动后建立一个内部通讯端口
  // 新增加一个属性，用来保存uid到connection的映射
  //$worker->uidConnections = array();
  // 当有客户端发来消息时执行的回调函数
  $worker->onMessage = function($connection, $data)
  {
     echo 'John send data -';
     var_dump($data);
     $ip = $connection->getRemoteIp();
     // 接受设备的数据
      if ($data) {
	  $sendData = $data . $ip;
          $ret = sendMessageByCurl($sendData);
          $msg = $ret  ?  "Success" : "Failed";
          // 返回数据给 php
          $connection->send($msg);
      } else {

        // 日志记录
      }
  };

  // 针对uid推送数据
  function sendMessageByCurl($data)
  {
      $post = array('result' => json_encode($data));
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://192.168.50.162/camera/device/receive");
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      $ret = curl_exec($ch);
      var_dump($ret);
      // 最好做一个日志记录，用作成功与失败比例的统计
      curl_close($ch);
      return $ret;
  }

	// 运行所有的worker（其实当前只定义了一个）
	Worker::runAll();
