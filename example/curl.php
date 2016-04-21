<?php
require_once '../vendor/autoload.php';

use SmallDuck\Curl;

$curl = new Curl();
//$curl->setOption(CURLOPT_PROXY, '127.0.0.1:8888'); //使用Fiddler进行抓包
$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
$apiUrl = 'https://coding.net/api/';
$loginUrl = $apiUrl . 'v2/account/login';
$result = $curl->post($loginUrl, array(
    'account' => '875199116@qq.com',
    'password' => sha1('zsl728485'),
    'remember_me' => false,
));

echo json_encode($result);