<?php
require_once '../vendor/autoload.php';

use SmallDuck\Curl;

$curl = new Curl();
$curl->setOption(CURLOPT_PROXY, '127.0.0.1:8888'); //使用Fiddler进行抓包
$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
$result = $curl->post('https://coding.net/api/tweet/best_user/', array('username' => '123456'));

var_dump($result);exit;