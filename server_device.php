<?php


/*
* aa020a01652fff2510ffffffff8b
* aa 02 01 02 03
*/

$command1 = "aa020a01652fff2510ffffffff8b";
$udp_url = "udp://58.61.172.120:5001";
$client = stream_socket_client($udp_url, $errno, $errstr, 30);
// 判断设备是否可以链接上
//if ($client)
$ret = stream_socket_sendto($client, hex2bin($command1));
echo "\r\n -- udp send request to device.";
var_dump($ret);
