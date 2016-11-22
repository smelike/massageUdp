<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 9/24/16
 * Time: 2:52 PM
 */

function email05($emailAddr, $arr_content)
{
    email($emailAddr, '控制命令下发 Email', $arr_content);
}

function email($emailAddr, $title = 'default no title', $content)
{
    if (is_array($content))
    {
        $content['date'] = date('Y-m-d H:i:s');
        $content = join("--", $content);
    }
    mail($emailAddr, $title, $content);
}