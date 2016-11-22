<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 8/30/16
 * Time: 11:58 AM
 */

namespace Workerman\Protocols;


class Coap
{

    public static function input($buffer)
    {
        $pos = strpos($buffer, "\n");

        if ($pos === false)
        {
            return 0;
        }
        return $pos + 1;
    }

    public static function encode($buffer)
    {
        return json_encode($buffer) . "\n";
    }

    public static function decode($buffer)
    {
        return json_decode(trim($buffer), true);
    }
}


