<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 8/30/16
 * Time: 3:33 PM
 */

namespace Workerman\Protocols;


class BinaryTransfer
{
    const   PACKAGE_HEAD_LEN = 5;

    public static function input($recv_buffer)
    {
        if (strlen($recv_buffer) < self::PACKAGE_HEAD_LEN)
        {
            return 0;
        }
        $package_data = unpack('Ntotal_len/Cname_len', $recv_buffer);
        return $package_data['total_len'];
    }

    public static function decode($recv_buffer)
    {
        $package_data = unpack('Ntotal_len/Cname_len', $recv_buffer);

        $name_len = $package_data['name_len'];

        $file_name = substr($recv_buffer, self::PACKAGE_HEAD_LEN, $name_len);

        $file_data = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $name_len);

        return array(
            'file_name' => $file_name,
            'file_data' => $file_data
        );
    }

    public static function encode($data)
    {
        return $data;
    }
}