<?php
    /**
     * Created by PhpStorm.
     * User: james
     * Date: 9/22/16
     * Time: 4:43 PM
     */

    include_once './vendor/autoload.php';
    include __DIR__ . '/CommandPattern.php';
    include __DIR__ . '/UtilityFunction.php';
    include __DIR__ . '/Email.php';


    date_default_timezone_set("Asia/Shanghai");
    $aes = new Security();

    // create a log channel
    //$log = new Logger('test', new SocketHandler(), new WebProcessor());
    //$log->pushHandler(new StreamHandler('./logs/monolo.log', Logger::INFO));
    //$log->addInfo("monolog start logging..");

    $udpSocketName = "tcp://0.0.0.0:50000";
    $tcpSocketName = "tcp://0.0.0.0:50001";

    $active_socket = [];
    $laravel_url = 'http://qc02.xqopen.com/massage/msg/device/receive';
    $device_mac = '';


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

        if (empty($return)) {

            if ($return === false) {
                $error = "Command not exist";
            } else {
                $error = curl_errno($ch) ? curl_error($ch) : var_export($return);
            }

            $arr_scurl_log = ['title' => 'scurl', 'error'  => $error, 'date'  => date('Y-m-d H:i:s')];
            slog('scurl_log', $arr_scurl_log);
        }
        curl_close($ch);

        return $return;
    }

    /**
     *
     * workerman单独使用,所以需要单独的日志函数
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