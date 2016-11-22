<?php

    use Workerman\Worker;
    use Monolog\Logger;
    use Monolog\Processor\WebProcessor;
    use Monolog\Handler\SocketHandler;

    include_once 'header.php';
    /**
     * 启动UDP server
     */
    $udp_worker = new Worker($udpSocketName);
    $udp_worker->count = 1;
    $udp_worker->name = "massage_device_worker";
    // default = true, 收到 reload 信号后自动重启进程
    // false, 运行 reload 时,重载代码,客户端连接不断开
    //$udp_worker->reloadable = false;
    //Worker::$stdoutFile  = __DIR__ . '/../../storage/logs/debug_output_log';

    /**
     * 在workerman启动的时候, 启动给Laravel的TCP服务
     */
    $udp_worker->onWorkerStart = function () {

        global $tcpSocketName;

        $tcp_worker = new Worker($tcpSocketName);
        $tcp_worker->name = 'massage_laravel_worker';

        $tcp_worker->onMessage = function ($connection, $data) {

            global $active_socket;
            global $device_mac;

            $laravel_data = json_decode($data, true);
            $send_socket = pregSocketByMacnIp($laravel_data);

            if ($send_socket) {
                deviceHub($connection, $send_socket, $laravel_data);
            } else {
                $online_log = "ONLINE MACHINE:" . join("-", array_keys($active_socket)) . "\t\t";
                $offline_log = "\r\n MACHINE : {$device_mac}:{$laravel_data['ipport']} NOT ONLINE";
                slog("river_message_log", $online_log . $offline_log);
                $connection->send(0);
            }
            $arr_receive_from_laravel = array(
                'title' => 'Laravel->UDP',
                'ipport' => $connection->getRemoteIp() . ':' . $connection->getRemotePort(),
                'data' => is_array($laravel_data) ? join("---", $laravel_data) : "not array:--" . $laravel_data
            );

            slog('laravel_c05_request_log', $arr_receive_from_laravel);
        };
        $tcp_worker->listen();
    };
    // 根据 mac 与 ip 地址来匹配出 socket 链接, 因为 mac 会存在相同的情况
    function pregSocketByMacnIp($laravel_data)
    {
        global $active_socket;
        global  $device_mac;
        $commandPattern = new CommandPattern($laravel_data['command'], 'null', $laravel_data['payload']);
        $device_mac = $commandPattern->getDeviceMac();
        // 发现还是存在请求端口改变的情况,目前处理是舍去端口来做 socket 匹配.
        // 解决方案:要不干脆自动给每个请求指定一个以毫秒时间戳的数值作为端口好了呢?
        $socket = false;
        if (isset($active_socket[$device_mac . '_' . $laravel_data['ipport']]))
        {
            $socket = $active_socket[$device_mac . '_' . $laravel_data['ipport']];
        }

        return $socket;
    }

    function deviceHub($source_socket, $destination_socket, $arr_data)
    {

        var_dump($arr_data);
        $arr_formatData = foramtControlCommand($arr_data);
        $send_data = join("", $arr_formatData);
        var_dump($arr_formatData);
        var_dump($destination_socket);
        //echo strlen();
        $bin = pack("H*", $send_data);
        $dret = $destination_socket->send($bin);
        $source_socket->send($dret);

        if ($dret) {
            $arr_device_log = array(
                "sendto" => $destination_socket->getRemoteIp() . ':' . $destination_socket->getRemotePort(),
                'data' => $send_data,
                "ret" => var_export($dret, true)
            );

            slog("workerman_send05_log", $arr_device_log);
        }
    }

    /**
     * 处理UDP消息的主要函数
     * @param $connection
     * @param $data
     * @return bool
     */
    $udp_worker->onMessage = function ($connection, $data) {

        $ip = $connection->getRemoteIp();
        $port = $connection->getRemotePort();
        $arr_receive = array(
            'title' => 'Device->UDP',
            'ipport' => $ip . ":" . $port,
            'data' => bin2hex($data)
        );

        slog('udp_onmessage_log', $arr_receive);
        sendMessageByCurl($connection, $data);
    };

    /**
     * 格式化控制命令
     * @param $arr_laravel_data
     * @return array
     */
    function foramtControlCommand($arr_laravel_data)
    {
        global $aes;

        $arr_data = formatHead();
        $arr_data['ver_type_tkl'] = '40';
        $arr_data['code'] = '02';
        $arr_data['message_id'] = $arr_laravel_data['message_id'];
        $arr_data['service_code'] = $arr_laravel_data['service_code'];
        $arr_data['command'] = $arr_laravel_data['command'];
        $arr_data['payload'] = bin2hex($aes->encrypt($arr_laravel_data['payload']));
        dataLength($arr_data['payload'], $arr_data);

        return $arr_data;
    }
    /**
     * 生成给device的消息头
     * @return array
     */
    function formatHead()
    {
        return array(
            'ver_type_tkl' => '60',
            'code' => '45',
            'message_id' => '0000',
            'delimiter' => 'FF',
            'service_code' => '01',
            'groupid'   => '01000001',
            'data_len' => '0000',
            'command' => '00',
            'payload' => ''
        );
    }

    /**
     * @param $connection
     * @param $arr_device
     * @return mixed
     */
    function sendDeviceCommand($connection, $arr_device)
    {

        global $aes;

        $arr_data['ver_type_tkl'] = '40';
        $arr_data['code'] = '02';
        $arr_data['message_id'] = $arr_device['message_id'];
        $arr_data['delimiter'] = 'FF';
        $arr_data['service_code'] = $arr_device['service_code'];
        $arr_data['command'] = $arr_device['command'];
        $arr_data['payload'] = $aes->encrypt($arr_device['payload']);
        dataLength($arr_data['payload'], $arr_data);

        return $connection->send(hex2bin(join("", $arr_data)));
    }
    function dataLength($payload, &$arr_data)
    {
        $data_len_hex = base_convert(ceil(strlen($payload) / 2), 10, 16);
        $len = strlen($data_len_hex);
        if ($len < 4)
        {
            $data_len_hex = str_repeat('0', 4 - $len) . $data_len_hex;
        }

        $arr_data['data_len'] = $data_len_hex;
    }

    function sendMessageByCurl($connection, $data)
    {
        global $active_socket;
        global $aes;

        $arr_command_data = CommandPattern::FormatCommandPattern($data, $aes);
        $arr_data = $arr_command_data['data'];
        $command = $arr_command_data['data']['command'];

        $device_mac = $arr_command_data['device_mac'];
        $ipport = $connection->getRemoteIp() . ":" . $connection->getRemotePort();
        $key = $device_mac . '_' . $ipport;
        $active_socket[$key] = $connection;
        $arr_laravel_ret = telegramWithLaravel($command, $arr_data, $ipport);
        responseDevice($connection, $aes, $arr_data, $arr_laravel_ret);
    }

    function responseDevice($connection, $aes, $arr_data , $arr_laravel_ret)
    {

        if ($arr_data['command'] == "03") {
            $arr_data['payload'] = $arr_data['timestamp'] . $arr_data['device_mac'];
            unset($arr_data['timestamp'], $arr_data['device_mac']);
            $encrypt_data = $aes->encrypt($arr_data['payload']);
            $arr_data['payload'] = bin2hex($encrypt_data);
            dataLength($arr_data['payload'], $arr_data);
            $send_binary_string = join('', $arr_data);
        } else {
            if (isset($arr_laravel_ret['payload']) AND $arr_laravel_ret['payload']) {
                $arr_data = array_merge($arr_data, $arr_laravel_ret);
                $arr_format_data = formatPayloadFromLaravel($arr_data);
                $logfile = "command_" . $arr_data['command'] . '_disencrypt_log';
                slog($logfile, $arr_format_data);
                $encrypt_data = $aes->encrypt($arr_format_data['payload']);

                $arr_format_data['payload'] = bin2hex($encrypt_data);
                dataLength($arr_format_data['payload'], $arr_format_data);
                $send_binary_string = join('', $arr_format_data);
            }
        }

        if (isset($send_binary_string))
        {
            $logfile = "command_" . $arr_data['command'] . '_log';
            slog($logfile, array($send_binary_string));
            $ret = $connection->send(hex2bin($send_binary_string));
            var_dump($ret);
        }
    }

    function telegramWithLaravel($command, $arr_data, $ipport)
    {
        global $laravel_url;

        if ($command == '03') {
            $arr_data = array(
                'message_id' => $arr_data['message_id'],
                'service_code' => $arr_data['service_code'],
                'command' => $arr_data['command'],
                'payload' => $arr_data['timestamp'] . $arr_data['device_mac'] . 'FF'
            );
        }

        $arr_data['ipport'] = $ipport;
        $post = array('report' => json_encode($arr_data));
        slog('send_request_to_laravel_log', $post);

        return json_decode(scurl($laravel_url, $post), true);
    }
    /**
     * 格式化从laravel返回的数据
     * @param $arr_laravel_data
     * @return array|bool
     */
    function formatPayloadFromLaravel($arr_laravel_data)
    {

        $arr_data = formatHead();
        $arr_data['ver_type_tkl'] = '60';
        $arr_data['code'] = '45';
        $arr_data['message_id'] = $arr_laravel_data['message_id'];
        $arr_data['service_code'] = $arr_laravel_data['service_code'];
        $arr_data['groupid'] = $arr_laravel_data['groupid'];
        dataLength($arr_laravel_data['payload'], $arr_data);
        $arr_data['command'] = $arr_laravel_data['command'];
        $arr_data['payload'] = $arr_laravel_data['payload'];

        return $arr_data;
    }

    Worker::runAll();