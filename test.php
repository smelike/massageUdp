<?php
    /**
     * Created by PhpStorm.
     * User: james
     * Date: 8/30/16
     * Time: 12:28 PM
     */
    include_once './vendor/autoload.php';
    use Workerman\Worker;
    use Workerman\Lib\Timer;

    // 问题 1: 怎么基于现状重写 gateway 代码呢?
    // 问题 2: 使用定时器实现重发机制吗?

    //Worker::$stdoutFile = "/data/d2/logs/workerman_output";
    Worker::$stdoutFile = sys_get_temp_dir() . '/worker_debug_log';

    $coap_wk = new Worker('Coap://0.0.0.0:50008');
    $http_wk = new Worker('http://0.0.0.0:8080');
    $http_wk->count = 4;
    $http_wk->reusePort = true;
    $http_wk->reloadable = false;


    $http_wk->onWorkerReload = function() {
        echo 'worker reload';
    };

    $http_wk->onConnect = function($connection) {

        echo $ipport = $connection->getRemoteIp() . "\n";
        $ret = mail('582346465@qq.com', 'There is a connect', $ipport);
        var_dump($ret);
    };

    /*
    // 程序从上
    $http_wk->onWorkerStart = function ($worker) use($http_wk)
    {
        var_dump($http_wk);

        Timer::add(2, function() use ($http_wk){
            echo "99999";
            var_dump(count($http_wk->connections));
        });

    };
    */

    $http_wk->onMessage = function ($connect, $data) use($http_wk)
    {

        //var_dump($http_wk->connections);
        $connect->send(rand(999, 99999999));

    };

    $http_wk->onWorkerStop = function() {
        mail('jame@xqopen.com', 'WorkerStop', 'Workerman stop');
    };

    $http_wk->onClose = function($connection)
    {

    };

    /*
     *  Use connection's ip and port as uid
     *  to save connection
     * */
    function saveSocket($connection, $worker)
    {
        $connection->uid = $connection->getRemoteIp() . ":" . $connection->getRemotePort();
        $worker->uidConnections[$connection->uid] = $connection;
    }

    Worker::runAll();


