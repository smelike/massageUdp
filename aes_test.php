<?php

include './UtilityFunction.php';

$aes = new Security();

//echo $aes->_key;

//$input ="ffffffff000ec608-0e97804d41535341-474523";
/*
$input ="1234567890abcdef1234567890abcdef";

$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
//$input = Security::pkcs5_pad($input, $size);
$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
mcrypt_generic_init($td, '1234567890abcdef', $iv);
$data = mcrypt_generic($td, '1234567890abcdef1234567890abcdef');
mcrypt_generic_deinit($td);
mcrypt_module_close($td);
$data = base64_encode($data);

//echo "data:--" . $data;
//var_dump(hex2bin($input));

//echo "encrypt--";
$encryt_data = $aes->encrypt("ffffffff000ec6080e97804d41535341474523");
*/
//var_dump($encryt_data);
//echo "hexdata-";
//echo bin2hex($encryt_data);

//echo "\r\n\r\ndescrypt--";

//$out = "YpPf2/a9wAEkD+gmT0kvDilZbGr5fdlLMTbJTz+vAG8/ho/SMGqUyD8ox3+CuAIZ3mjpGem5sxyX8hy51EqPCA==";

//$out1 = '59705066322f61397741456b442b676d54306b7644696c5a6247723566646c4c4d54624a547a2b764147386c666a454e78446a7156773259363478767747506b';

//$ddata = '0148756549384858515534436a42314b53746959704e64387339376142436d65765665373767386a3246536f3d';
//$ddata2 = '48756549384858515534436a42314b53746959704e64387339376142436d65765665373767386a3246536f3d';

$long = "6b706833466a356341693568394a696d5441372f6457686f35552f58697a362f33674e446363726a3534633d40020012ff0101000001002c046b52454976467848337438484d4c7043586e734b676d686f35552f58697a362f33674e446363726a3534633d40020012ff0101000001002c0453793470394c7236647a4849316d644b755946324c47686f35552f58697a362f33674e446363726a3534633d";

$long2 = "343635764b77306b5465444c2f6d4c6e7a52495251332b6b5949685036685441737732483546523662656f3d";

$long3 = "4a7161526c6c4e6c4a4650584254674575496b4d494157435261624e7036336f4146422f507251336567733d";

$long4 = "4a7161526c6c4e6c4a4650584254674575496b4d494157435261624e7036336f4146422f507251336567733d";
$hexdata = bin2hex("H6AINbZ9qo0YgUeqazGhDj+Kh0b9LgBQnq0ZGF301J0=");
echo "descrypt:--";
var_dump($aes->decrypt($hexdata));

$plain = "57e0ae68000ec6080e978003400000000065d90200230000ffff";

echo "encrypt:---\r\n";
var_dump($aes->encrypt($plain));
//$descry
