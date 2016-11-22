<?php

    use Workerman\Worker;

    include './Workerman/Autoloader.php';
    include __DIR__ . '/CommandPattern.php';

    date_default_timezone_set("Asia/Shanghai");

    //$udpSocketName = "udp://0.0.0.0:50000";
    $udpSocketName = "tcp://0.0.0.0:50006";
    $tcpSocketName = "tcp://0.0.0.0:50001";

    $active_socket = [];
    $laravel_url = 'http://qc02.xqopen.com/massage/msg/device/receive';

    /**
     * 启动UDP server
     */
    $udp_worker = new Worker($udpSocketName);
    $udp_worker->count = 1;

    /**
     * 在workerman启动的时候, 启动给Laravel的TCP服务
     */
    $udp_worker->onWorkerStart = function () {
        global $tcpSocketName;

        $tcp_worker = new Worker($tcpSocketName);

        $tcp_worker->onMessage = function ($connection, $data) {

            global $active_socket;

            $laravel_data = json_decode($data, true);
            $dest_ipport = $laravel_data['ipport'];

            // 记录消息日志
            $ipport = $connection->getRemoteIp() . ':' . $connection->getRemotePort();
            $arr_receive = array(
                'title' => 'Laravel->UDP',
                'ipport' => $ipport,
                'data' => is_array($laravel_data) ? join("\t", $laravel_data) : $laravel_data
            );

            slog('tcp_onmessage_log', $arr_receive);
            $commandPattern = new CommandPattern($laravel_data['command'], 'null', $laravel_data['payload']);

            // 根据deviceid,取得保留的socket
            $device_mac = $commandPattern->getDeviceMac();
            $send_socket = pregSocketByMacnIp($device_mac, $dest_ipport);

            if ($send_socket) {
                deviceHub($connection, $send_socket, $laravel_data);
            } else {
                $online_log = "ONLINE MACHINE:" . join("-", array_keys($active_socket)) . "\t\t";
                $offline_log = "\r\n MACHINE : {$device_mac}{$dest_ipport} NOT ONLINE";
                $onOff_log = $online_log . $offline_log;
                slog("river_message_log", $onOff_log, FILE_APPEND);
                $connection->send(0);
            }
        };
        $tcp_worker->listen();
    };
    // 根据 mac 与 ip 地址来匹配出 socket 链接, 因为 mac 会存在相同的情况
    function pregSocketByMacnIp($mac, $ipport)
    {
        global $active_socket;

        foreach ($active_socket as $key => $socket) {
            if (strpos($key, $mac . '_' . $ipport) !== false) {
                return $active_socket[$mac . '_' . $ipport];
            }
        }
        return false;
    }

    function deviceHub($source_socket, $destination_socket, $arr_data)
    {

        $arr_formatData = foramtControlCommand($arr_data);
        $send_data = hex2bin(join("", $arr_formatData));
        $dret = $destination_socket->send($send_data);
        $source_socket->send($dret);

        if ($dret) {
            $sendto = $destination_socket->getRemoteIp() . ':' . $destination_socket->getRemotePort();
            $arr_device_log = array(
                "sendto" => $sendto,
                'data' => $send_data,
                "ret" => var_export($dret, true)
            );
            slog("device_response_log", $arr_device_log);
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
     * 生成给device的消息头
     * @return array
     */
    function formatHead()
    {
        $arr_head = array(
            'ver_type_tkl' => '60',
            'code' => '45',
            'message_id' => '0000',
            'delimiter' => 'FF',
            'service_code' => '01',
            'data_len' => '0000',
            'command' => '00',
            'payload' => ''
        );

        return $arr_head;
    }

    /**
     * 格式化控制命令
     * @param $arr_laravel_data
     * @return array
     */
    function foramtControlCommand($arr_laravel_data)
    {
        $arr_data = formatHead();
        $arr_data['ver_type_tkl'] = '40';
        $arr_data['code'] = '02';
        $arr_data['message_id'] = $arr_laravel_data['message_id'];
        $arr_data['service_code'] = $arr_laravel_data['service_code'];

        $len = base_convert(1 + strlen($arr_laravel_data['payload']) / 2, 10, 16);
        $arr_data['data_len'] = strlen($len) == 1 ? '000' . $len : (strlen($len) == 2 ? '00' . $len : $len);
        $arr_data['command'] = $arr_laravel_data['command'];
        $arr_data['payload'] = $arr_laravel_data['payload'];

        return $arr_data;
    }

    /**
     * @param $connection
     * @param $arr_device
     * @return mixed
     */
    function sendDeviceCommand($connection, $arr_device)
    {

        $arr_data['ver_type_tkl'] = '40';
        $arr_data['code'] = '02';
        $arr_data['message_id'] = $arr_device['message_id'];
        $arr_data['delimiter'] = 'FF';
        $arr_data['service_code'] = $arr_device['service_code'];
        $len = base_convert(1 + strlen($arr_device['payload']) / 2, 10, 16);
        $arr_data['data_len'] = strlen($len) == 1 ? '000' . $len : (strlen($len) == 2 ? '00' . $len : $len);
        $arr_data['command'] = $arr_device['command'];
        $arr_data['payload'] = $arr_device['payload'];

        return $connection->send(hex2bin(join("", $arr_data)));
    }

    function sendMessageByCurl($connection, $data)
    {
        global $active_socket;
        global $laravel_url;

        $arr_command_data = CommandPattern::FormatCommandPattern($data);
        $arr_data = $arr_command_data['data'];
        $command = $arr_command_data['data']['command'];
        $device_mac = $arr_command_data['device_mac'];
        $ipport = $connection->getRemoteIp() . ":" . $connection->getRemotePort();
        $ip_surfix = str_replace('.', '', $connection->getRemoteIp()) . ':' . $connection->getRemotePort();
        $key = $device_mac . '_' . $ip_surfix;

        $active_socket[$key] = $connection;

        if ($command == '03') {
            $arr_data['payload'] = $arr_data['timestamp'] . $arr_data['device_mac'];
            $arr_heart = array(
                'message_id' => $arr_data['message_id'],
                'service_code' => $arr_data['service_code'],
                'command' => $arr_data['command'],
                'ipport' => $ip_surfix,
                'payload' => $arr_data['payload'] . 'FF'
            );
            $post = array('report' => json_encode($arr_heart));
        } else {
            $arr_data['ipport'] = $ipport;
            $post = array('report' => json_encode($arr_data));
    //            unset($arr_data['ipport']);
        }

        $arr_laravel_ret = scurl($laravel_url, $post);
        $arr_laravel_ret = json_decode($arr_laravel_ret, true);

        unset($arr_data['ipport']);

        if ($arr_data['command'] == "03") {
            unset($arr_data['timestamp'], $arr_data['device_mac']);
            $string_binary_data = hex2bin(join("", $arr_data));
        } else {
            if (isset($arr_laravel_ret['payload']) AND $arr_laravel_ret['payload']) {
                $arr_data = array_merge($arr_data, $arr_laravel_ret);
                $arr_format_data = formatPayloadFromLaravel($arr_data);
                $string_binary_data = hex2bin(join("", $arr_format_data));
            }
        }

        $ret = $connection->send($string_binary_data);
        var_dump($ret);
    }

    /**
     * 格式化从laravel返回的数据
     * @param $arr_laravel_data
     * @return array|bool
     */
    function formatPayloadFromLaravel($arr_laravel_data)
    {
        if (empty($arr_laravel_data['payload'])) {
            return false;
        }

        $arr_data = formatHead();

        $arr_data['ver_type_tkl'] = '60';
        $arr_data['code'] = '45';
        $arr_data['message_id'] = $arr_laravel_data['message_id'];
        $arr_data['service_code'] = $arr_laravel_data['service_code'];

        $len = base_convert(1 + strlen($arr_laravel_data['payload']) / 2, 10, 16);
        $arr_data['data_len'] = strlen($len) == 1 ? '000' . $len : (strlen($len) == 2 ? '00' . $len : $len);
        $arr_data['command'] = $arr_laravel_data['command'];
        $arr_data['payload'] = $arr_laravel_data['payload'];

        return $arr_data;
    }


    /**
     * 通过80端口和lavarel通讯
     * @param $request_url
     * @param $post 四个字段给Laravel
     * @return mixed
     */
    function scurl($request_url, $post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $return = curl_exec($ch);
        curl_close($ch);

        return $return;
    }


    /**
     *
     * 因为workerman单独使用,所以需要单独的日志函数
     * @param $log_file
     * @param $arr_log
     */
    function slog($log_file, $log_data)
    {
        $logDirectory = __DIR__ . '/../../storage/logs/';
        $log_file .= '_' . date('Y-m-d');

        if (!file_exists($logDirectory)) {
            $logDirectory = './logs/';
        }
        $dd = date('Ymd');
        $day_directory = $logDirectory . $dd . '/';

        if (!file_exists($day_directory)) {
            mkdir($day_directory, 0777);
        }

        $log_file = $day_directory . $log_file;
        if (is_array($log_data) AND join("", $log_data)) {
            $log_data['datetime'] = date('Y-m-d H:i:s');
            $log_data = join("\t", $log_data) . "\r\n";
        } else if ($log_data) {
            $log_data .= "\t" . date('Y-m-d H:i:s') . "\r\n";
        } else {
            exit(0);
        }

        return file_put_contents($log_file, $log_data, FILE_APPEND);
    }

    Worker::runAll();