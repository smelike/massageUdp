<?php

    //echo $udp_url = "udp://" . $client_ip . ":" . $client_port;
    $command1 = "aa020a01652fff2510ffffffff8b";
    // AA 02 01 81 80 AA 02 08 83 65 2F 00 00 00 00 00 C1
    $arr_command = array('AA', '02', '01' , '81', '80', 'AA', '02', '08', '83', '65', '2F', '00', '00', '00', '00', '00', 'C1');
    $arr_bincommand = array();
    foreach ($arr_command as $c) {
      $arr_bincommand[] = hex2bin($c);
    }

    $udp_url = "udp://58.61.172.120:5001";
    $client = stream_socket_client($udp_url);
    // ?~H??~V?设?~G?~X??~P??~O?以?~S??~N??~J
    //if ($client)
    //$message = "400100470c03000ec607acb900ffffffff";
    $ret = stream_socket_sendto($client, join("", $arr_bincommand));
    echo "\r\n -- udp send request to device.";
    var_dump($ret);
