<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/6
 * Time: 10:55
 */

//  现在剩下的问题，就是确定一个 token。用于 encode 与 decode
// create token
/*
function create_token()
{
	// 40 Bytes
	$data = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . time() . rand();
	return sha1($data);
}
*/

//
class Aes
{

	// CRYPTO_CIPHER_BLOCK_SIZE 32
	static $_secret_key = '1234567890abcdef1234567890abcdef';

	public function setKey($key)
	{
		$this->_secret_key = $key;
	}

	public function encode($data)
	{
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $this->_secret_key, $iv);
		$encrypted = mcrypt_generic($td, $data);
		mcrypt_generic_deinit($td);

		return $iv . $encrypted;
	}

	public function decode($data)
	{
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
		$iv = mb_substr($data, 0, 32, 'UTF-8');
		mcrypt_generic_init($td, $this->_secret_key, $iv);
		$data = mb_substr($data, 32, mb_strlen($data, 'UTF-8'), 'UTF-8');
		$data = mdecrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return trim($data);
	}
}

//
class Security
{

	public static $_key = '1234567890123456';
	public static $_skey = '62682a7a6c5f514471686e3473365636';

	public static function encrypt($input)
	{

		$key = hex2bin(self::$_skey);
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$input = hex2bin($input); // 二进制数据
		$input = Security::pkcs5_pad($input, $size);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = base64_encode($data);

		return $data;
	}

	private static function pkcs5_pad($text, $blocksize)
	{
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}
	public static function decrypt($sStr)
	{

		$sStr = hex2bin($sStr);
		$sKey = hex2bin(self::$_skey);
		$decrypted = mcrypt_decrypt(
			MCRYPT_RIJNDAEL_128,
			$sKey,
			base64_decode($sStr),
			MCRYPT_MODE_ECB
		);

		$decrypted_hex = bin2hex($decrypted);
		$complement = substr($decrypted_hex, strlen($decrypted_hex) - 2, 2);
		// modified at October/20/2016
		/*
		 * // baking's 去除补码方案
		if (ord($complement) < strlen($decrypted_hex)) {
			$decrypted_hex = substr($decrypted_hex, 0, -1 * ord($complement));
		}
		return $decrypted_hex;
		*/
		// massage's 去除补码方案
		return rtrim($decrypted_hex, $complement);
	}

}
