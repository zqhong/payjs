<?php
/**
 * Created by PhpStorm.
 * User: lixia
 * Date: 2018/4/18
 * Time: 19:24
 */
require __DIR__ . '/../vendor/autoload.php';

// 官方文档：https://payjs.cn/help/api-lie-biao/jiao-yi-xin-xi-tui-song.html
$config = [
    'merchantId' => '',                      //商户号
    'merchantKey' => '',                     //密钥
    'notifyUrl' => 'https://www.baidu.com/', //notify地址 接收微信支付异步通知的回调地址。必须为可直接访问的URL，不能带参数、session验证、csrf验证。留空则不通知 需要保留最后的斜杠
];
$payjs = new \Musnow\Payjs\Pay($config);


$ret = $payjs->checking($_REQUEST); //数据验签
print_r($ret);                   //返回数据
